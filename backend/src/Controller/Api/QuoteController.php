<?php

namespace App\Controller\Api;

use App\Entity\CompanySettings;
use App\Entity\Customer;
use App\Entity\NumberRange;
use App\Entity\Quote;
use App\Entity\QuoteItem;
use App\Enum\QuoteStatus;
use App\Enum\TaxCategory;
use App\Repository\CompanySettingsRepository;
use App\Repository\CustomerRepository;
use App\Repository\QuoteRepository;
use App\Service\ApiPresenter;
use App\Service\NumberRangeService;
use App\Service\PdfService;
use App\Service\TotalsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/quotes')]
class QuoteController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly QuoteRepository $repo,
        private readonly CustomerRepository $customers,
        private readonly CompanySettingsRepository $settingsRepo,
        private readonly ApiPresenter $presenter,
        private readonly TotalsService $totals,
        private readonly NumberRangeService $numbers,
        private readonly PdfService $pdf,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->repo->createQueryBuilder('q')->orderBy('q.createdAt', 'DESC');
        if ($status = $request->query->get('status')) {
            $qb->andWhere('q.status = :s')->setParameter('s', $status);
        }
        if ($customerId = $request->query->get('customerId')) {
            $qb->andWhere('q.customer = :c')->setParameter('c', (int) $customerId);
        }
        if ($search = trim((string) $request->query->get('q', ''))) {
            $qb->andWhere('q.number LIKE :q OR q.recipientName LIKE :q')->setParameter('q', '%'.$search.'%');
        }
        $items = $qb->setMaxResults(200)->getQuery()->getResult();

        return $this->json(array_map(fn (Quote $q) => $this->presenter->quote($q, false), $items));
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Quote $quote): JsonResponse
    {
        return $this->json($this->presenter->quote($quote));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $quote = new Quote();
        $this->applyDefaults($quote);
        $this->hydrate($quote, $this->body($request));
        $this->em->persist($quote);
        $this->em->flush();

        return $this->json($this->presenter->quote($quote), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(Quote $quote, Request $request): JsonResponse
    {
        if (in_array($quote->getStatus(), [QuoteStatus::ACCEPTED, QuoteStatus::DECLINED], true)) {
            return $this->json(['error' => 'Angenommene oder abgelehnte Angebote können nicht mehr bearbeitet werden.'], 409);
        }
        $this->hydrate($quote, $this->body($request));
        $quote->touch();
        $this->em->flush();

        return $this->json($this->presenter->quote($quote));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Quote $quote): JsonResponse
    {
        if (QuoteStatus::DRAFT !== $quote->getStatus()) {
            return $this->json(['error' => 'Nur Entwürfe können gelöscht werden.'], 409);
        }
        $this->em->remove($quote);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(Quote $quote): JsonResponse
    {
        if (QuoteStatus::DRAFT !== $quote->getStatus()) {
            return $this->json(['error' => 'Nur Entwürfe können versendet werden.'], 409);
        }
        if (null === $quote->getNumber()) {
            $quote->setNumber($this->numbers->next(NumberRange::KEY_QUOTE));
        }
        $quote->setStatus(QuoteStatus::SENT);
        $quote->setSentAt(new \DateTimeImmutable());
        $quote->touch();
        $this->em->flush();

        return $this->json($this->presenter->quote($quote));
    }

    #[Route('/{id}/pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Quote $quote): Response
    {
        $content = $this->pdf->renderQuote($quote);
        $filename = ($quote->getNumber() ?? 'Angebot-Entwurf').'.pdf';

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    #[Route('/{id}/accept', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function accept(Quote $quote): JsonResponse
    {
        return $this->decide($quote, QuoteStatus::ACCEPTED);
    }

    #[Route('/{id}/decline', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function decline(Quote $quote): JsonResponse
    {
        return $this->decide($quote, QuoteStatus::DECLINED);
    }

    private function decide(Quote $quote, QuoteStatus $status): JsonResponse
    {
        if (QuoteStatus::SENT !== $quote->getStatus()) {
            return $this->json(['error' => 'Nur versendete Angebote können angenommen/abgelehnt werden.'], 409);
        }
        $quote->setStatus($status);
        $quote->setDecidedAt(new \DateTimeImmutable());
        $quote->touch();
        $this->em->flush();

        return $this->json($this->presenter->quote($quote));
    }

    private function applyDefaults(Quote $quote): void
    {
        $settings = $this->settingsRepo->findOneBy([]);
        if (!$settings instanceof CompanySettings) {
            return;
        }
        $quote->setCurrency($settings->getDefaultCurrency());
        $quote->setIntroText($settings->getQuoteIntroText());
        $quote->setOutroText($settings->getQuoteOutroText());
        $quote->setContactPerson($settings->getManagingDirector());
        $quote->setValidUntil(
            (new \DateTimeImmutable())->modify(sprintf('+%d days', $settings->getDefaultQuoteValidityDays()))
        );
    }

    /** @param array<string, mixed> $data */
    private function hydrate(Quote $q, array $data): void
    {
        if (array_key_exists('customerId', $data)) {
            $customer = $data['customerId'] ? $this->customers->find((int) $data['customerId']) : null;
            if ($customer instanceof Customer) {
                $q->snapshotFromCustomer($customer);
            }
        }
        foreach ([
            'recipientContact' => 'setRecipientContact',
            'recipientVatId' => 'setRecipientVatId',
            'contactPerson' => 'setContactPerson',
            'introText' => 'setIntroText',
            'outroText' => 'setOutroText',
            'internalNotes' => 'setInternalNotes',
        ] as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $q->{$setter}($data[$field] ?: null);
            }
        }
        if (isset($data['recipientName'])) {
            $q->setRecipientName((string) $data['recipientName']);
        }
        if (isset($data['currency'])) {
            $q->setCurrency((string) $data['currency']);
        }
        if (array_key_exists('pricesIncludeVat', $data)) {
            $q->setPricesIncludeVat((bool) $data['pricesIncludeVat']);
        }
        if (array_key_exists('issueDate', $data) && $data['issueDate']) {
            $q->setIssueDate($this->parseDate($data['issueDate']));
        }
        if (array_key_exists('validUntil', $data)) {
            $q->setValidUntil($this->parseDate($data['validUntil']));
        }
        if (isset($data['recipientAddress']) && is_array($data['recipientAddress'])) {
            $this->hydrateAddress($q->getRecipientAddress(), $data['recipientAddress']);
        }
        if (array_key_exists('items', $data) && is_array($data['items'])) {
            $this->syncItems($q, $data['items']);
        }

        $this->totals->recompute($q, $q->getItems());
    }

    /** @param array<int, array<string, mixed>> $itemsData */
    private function syncItems(Quote $q, array $itemsData): void
    {
        foreach ($q->getItems()->toArray() as $existing) {
            $q->removeItem($existing);
        }
        $pos = 0;
        foreach ($itemsData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = new QuoteItem();
            $item->setPosition(isset($row['position']) ? (int) $row['position'] : $pos++);
            $item->setOptional((bool) ($row['optional'] ?? false));
            $item->setTitle((string) ($row['title'] ?? ''));
            $item->setDescription(($row['description'] ?? null) ?: null);
            $item->setQuantity((string) ($row['quantity'] ?? '1'));
            $item->setUnit((string) ($row['unit'] ?? 'Stk'));
            $item->setUnitPrice((int) ($row['unitPrice'] ?? 0));
            $item->setVatRate((string) ($row['vatRate'] ?? '20.00'));
            $item->setTaxCategory(TaxCategory::from((string) ($row['taxCategory'] ?? 'standard')));
            $q->addItem($item);
        }
    }
}
