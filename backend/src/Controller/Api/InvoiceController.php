<?php

namespace App\Controller\Api;

use App\Entity\CompanySettings;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceItem;
use App\Entity\NumberRange;
use App\Entity\Payment;
use App\Entity\Quote;
use App\Enum\InvoiceStatus;
use App\Enum\InvoiceType;
use App\Enum\TaxCategory;
use App\Repository\CompanySettingsRepository;
use App\Repository\CustomerRepository;
use App\Repository\InvoiceRepository;
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

#[Route('/api/invoices')]
class InvoiceController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InvoiceRepository $repo,
        private readonly QuoteRepository $quotes,
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
        $qb = $this->repo->createQueryBuilder('i')->orderBy('i.createdAt', 'DESC');
        if ($status = $request->query->get('status')) {
            $qb->andWhere('i.status = :s')->setParameter('s', $status);
        }
        if ($type = $request->query->get('type')) {
            $qb->andWhere('i.type = :t')->setParameter('t', $type);
        }
        if ($customerId = $request->query->get('customerId')) {
            $qb->andWhere('i.customer = :c')->setParameter('c', (int) $customerId);
        }
        if ($search = trim((string) $request->query->get('q', ''))) {
            $qb->andWhere('i.number LIKE :q OR i.recipientName LIKE :q')->setParameter('q', '%'.$search.'%');
        }

        return $this->json(array_map(
            fn (Invoice $i) => $this->presenter->invoice($i, false),
            $qb->setMaxResults(200)->getQuery()->getResult()
        ));
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Invoice $invoice): JsonResponse
    {
        return $this->json($this->presenter->invoice($invoice));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $invoice = new Invoice();
        $this->applyDefaults($invoice);
        $this->hydrate($invoice, $this->body($request));
        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json($this->presenter->invoice($invoice), JsonResponse::HTTP_CREATED);
    }

    #[Route('/from-quote/{quoteId}', methods: ['POST'], requirements: ['quoteId' => '\d+'])]
    public function fromQuote(int $quoteId, Request $request): JsonResponse
    {
        $quote = $this->quotes->find($quoteId);
        if (!$quote instanceof Quote) {
            return $this->json(['error' => 'Angebot nicht gefunden.'], 404);
        }

        // Optional positions the customer accepted (quote item ids) get billed as
        // regular lines; the rest of the optional positions are left out.
        $data = $this->body($request);
        $includeOptional = array_map('intval', $data['includeOptionalItemIds'] ?? []);

        $invoice = new Invoice();
        $this->applyDefaults($invoice);
        $invoice->setRelatedQuote($quote);
        if ($quote->getCustomer() instanceof Customer) {
            $invoice->snapshotFromCustomer($quote->getCustomer());
        } else {
            $invoice->setRecipientName($quote->getRecipientName());
        }
        $invoice->setPricesIncludeVat($quote->isPricesIncludeVat());
        $invoice->setContactPerson($quote->getContactPerson());

        foreach ($quote->getItems() as $src) {
            if ($src->isOptional() && !in_array($src->getId(), $includeOptional, true)) {
                continue;
            }
            $item = new InvoiceItem();
            $this->copyLine($src, $item);
            $item->setOptional(false); // billed now -> regular line
            $invoice->addItem($item);
        }

        $this->totals->recompute($invoice, $invoice->getItems());
        $this->em->persist($invoice);
        $this->em->flush();

        return $this->json($this->presenter->invoice($invoice), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(Invoice $invoice, Request $request): JsonResponse
    {
        if (InvoiceStatus::DRAFT !== $invoice->getStatus()) {
            return $this->json(['error' => 'Nur Entwürfe können bearbeitet werden.'], 409);
        }
        $this->hydrate($invoice, $this->body($request));
        $invoice->touch();
        $this->em->flush();

        return $this->json($this->presenter->invoice($invoice));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Invoice $invoice): JsonResponse
    {
        if (InvoiceStatus::DRAFT !== $invoice->getStatus()) {
            return $this->json(['error' => 'Nur Entwürfe können gelöscht werden.'], 409);
        }
        $this->em->remove($invoice);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/finalize', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function finalize(Invoice $invoice): JsonResponse
    {
        if (InvoiceStatus::DRAFT !== $invoice->getStatus()) {
            return $this->json(['error' => 'Nur Entwürfe können festgeschrieben werden.'], 409);
        }
        if (null === $invoice->getNumber()) {
            $invoice->setNumber($this->numbers->next($this->rangeKeyFor($invoice->getType())));
        }
        if (null === $invoice->getDueDate()) {
            $settings = $this->settingsRepo->findOneBy([]);
            $days = $settings?->getDefaultPaymentTermsDays() ?? 14;
            $invoice->setDueDate($invoice->getIssueDate()->modify(sprintf('+%d days', $days)));
        }
        $invoice->setStatus(InvoiceStatus::SENT);
        $invoice->setSentAt(new \DateTimeImmutable());
        $invoice->touch();
        $this->em->flush();

        return $this->json($this->presenter->invoice($invoice));
    }

    #[Route('/{id}/payments', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addPayment(Invoice $invoice, Request $request): JsonResponse
    {
        if (in_array($invoice->getStatus(), [InvoiceStatus::DRAFT, InvoiceStatus::CANCELLED], true)) {
            return $this->json(['error' => 'Für Entwürfe/stornierte Rechnungen sind keine Zahlungen möglich.'], 409);
        }
        $data = $this->body($request);
        $payment = new Payment();
        $payment->setAmount((int) ($data['amount'] ?? 0));
        $payment->setMethod(($data['method'] ?? null) ?: null);
        $payment->setReference(($data['reference'] ?? null) ?: null);
        if (!empty($data['paidAt'])) {
            $payment->setPaidAt($this->parseDate($data['paidAt']));
        }
        $invoice->addPayment($payment);

        $invoice->setPaidAmount($invoice->getPaidAmount() + $payment->getAmount());
        $this->refreshPaymentStatus($invoice);
        $invoice->touch();
        $this->em->flush();

        return $this->json($this->presenter->invoice($invoice));
    }

    #[Route('/{id}/cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Invoice $invoice): JsonResponse
    {
        return $this->reverse($invoice, InvoiceType::CANCELLATION, NumberRange::KEY_CANCELLATION, true);
    }

    #[Route('/{id}/credit-note', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function creditNote(Invoice $invoice): JsonResponse
    {
        return $this->reverse($invoice, InvoiceType::CREDIT_NOTE, NumberRange::KEY_CREDIT_NOTE, false);
    }

    #[Route('/{id}/pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function pdf(Invoice $invoice): Response
    {
        $content = $this->pdf->renderInvoice($invoice);
        $filename = ($invoice->getNumber() ?? 'Rechnung-Entwurf').'.pdf';

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    /**
     * Creates a reversing document (Storno or Gutschrift) with negated amounts.
     * Storno additionally voids the original invoice.
     */
    private function reverse(Invoice $original, InvoiceType $type, string $rangeKey, bool $voidOriginal): JsonResponse
    {
        if (InvoiceType::INVOICE !== $original->getType()) {
            return $this->json(['error' => 'Nur Rechnungen können storniert/gutgeschrieben werden.'], 409);
        }
        if (InvoiceStatus::DRAFT === $original->getStatus()) {
            return $this->json(['error' => 'Entwürfe einfach löschen statt stornieren.'], 409);
        }

        $doc = new Invoice();
        $doc->setType($type);
        $doc->setCustomer($original->getCustomer());
        $doc->setRecipientName($original->getRecipientName());
        $doc->setRecipientContact($original->getRecipientContact());
        $doc->setRecipientCustomerNumber($original->getRecipientCustomerNumber());
        $doc->setRecipientVatId($original->getRecipientVatId());
        $this->hydrateAddress($doc->getRecipientAddress(), $this->presenter->address($original->getRecipientAddress()));
        $doc->setContactPerson($original->getContactPerson());
        $doc->setCurrency($original->getCurrency());
        $doc->setPricesIncludeVat($original->isPricesIncludeVat());
        $doc->setCancelsInvoice($original);
        $doc->setNumber($this->numbers->next($rangeKey));
        $doc->setStatus(InvoiceStatus::SENT);
        $doc->setSentAt(new \DateTimeImmutable());

        foreach ($original->getItems() as $src) {
            $item = new InvoiceItem();
            $this->copyLine($src, $item);
            $item->setUnitPrice(-$src->getUnitPrice());
            $doc->addItem($item);
        }
        $this->totals->recompute($doc, $doc->getItems());

        if ($voidOriginal) {
            $original->setStatus(InvoiceStatus::CANCELLED);
            $original->touch();
        }

        $this->em->persist($doc);
        $this->em->flush();

        return $this->json($this->presenter->invoice($doc), JsonResponse::HTTP_CREATED);
    }

    private function rangeKeyFor(InvoiceType $type): string
    {
        return match ($type) {
            InvoiceType::CREDIT_NOTE => NumberRange::KEY_CREDIT_NOTE,
            InvoiceType::CANCELLATION => NumberRange::KEY_CANCELLATION,
            default => NumberRange::KEY_INVOICE,
        };
    }

    private function refreshPaymentStatus(Invoice $invoice): void
    {
        if (InvoiceStatus::CANCELLED === $invoice->getStatus()) {
            return;
        }
        if ($invoice->getPaidAmount() >= $invoice->getTotalGross() && $invoice->getTotalGross() > 0) {
            $invoice->setStatus(InvoiceStatus::PAID);
        } elseif ($invoice->getPaidAmount() > 0) {
            $invoice->setStatus(InvoiceStatus::PARTIALLY_PAID);
        }
    }

    private function copyLine(\App\Entity\AbstractLineItem $src, InvoiceItem $dst): void
    {
        $dst->setPosition($src->getPosition());
        $dst->setOptional($src->isOptional());
        $dst->setTitle($src->getTitle());
        $dst->setDescription($src->getDescription());
        $dst->setQuantity($src->getQuantity());
        $dst->setUnit($src->getUnit());
        $dst->setUnitPrice($src->getUnitPrice());
        $dst->setVatRate($src->getVatRate());
        $dst->setTaxCategory($src->getTaxCategory());
    }

    private function applyDefaults(Invoice $invoice): void
    {
        $settings = $this->settingsRepo->findOneBy([]);
        if (!$settings instanceof CompanySettings) {
            return;
        }
        $invoice->setCurrency($settings->getDefaultCurrency());
        $invoice->setIntroText($settings->getInvoiceIntroText());
        $invoice->setOutroText($settings->getInvoiceOutroText());
        $invoice->setContactPerson($settings->getManagingDirector());
        $invoice->setDueDate(
            (new \DateTimeImmutable())->modify(sprintf('+%d days', $settings->getDefaultPaymentTermsDays()))
        );
    }

    /** @param array<string, mixed> $data */
    private function hydrate(Invoice $inv, array $data): void
    {
        if (array_key_exists('customerId', $data)) {
            $customer = $data['customerId'] ? $this->customers->find((int) $data['customerId']) : null;
            if ($customer instanceof Customer) {
                $inv->snapshotFromCustomer($customer);
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
                $inv->{$setter}($data[$field] ?: null);
            }
        }
        if (isset($data['recipientName'])) {
            $inv->setRecipientName((string) $data['recipientName']);
        }
        if (isset($data['currency'])) {
            $inv->setCurrency((string) $data['currency']);
        }
        if (array_key_exists('pricesIncludeVat', $data)) {
            $inv->setPricesIncludeVat((bool) $data['pricesIncludeVat']);
        }
        if (array_key_exists('issueDate', $data) && $data['issueDate']) {
            $inv->setIssueDate($this->parseDate($data['issueDate']));
        }
        if (array_key_exists('dueDate', $data)) {
            $inv->setDueDate($this->parseDate($data['dueDate']));
        }
        if (array_key_exists('servicePeriodStart', $data)) {
            $inv->setServicePeriodStart($this->parseDate($data['servicePeriodStart']));
        }
        if (array_key_exists('servicePeriodEnd', $data)) {
            $inv->setServicePeriodEnd($this->parseDate($data['servicePeriodEnd']));
        }
        if (isset($data['recipientAddress']) && is_array($data['recipientAddress'])) {
            $this->hydrateAddress($inv->getRecipientAddress(), $data['recipientAddress']);
        }
        if (array_key_exists('items', $data) && is_array($data['items'])) {
            $this->syncItems($inv, $data['items']);
        }

        $this->totals->recompute($inv, $inv->getItems());
    }

    /** @param array<int, array<string, mixed>> $itemsData */
    private function syncItems(Invoice $inv, array $itemsData): void
    {
        foreach ($inv->getItems()->toArray() as $existing) {
            $inv->removeItem($existing);
        }
        $pos = 0;
        foreach ($itemsData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = new InvoiceItem();
            $item->setPosition(isset($row['position']) ? (int) $row['position'] : $pos++);
            $item->setOptional((bool) ($row['optional'] ?? false));
            $item->setTitle((string) ($row['title'] ?? ''));
            $item->setDescription(($row['description'] ?? null) ?: null);
            $item->setQuantity((string) ($row['quantity'] ?? '1'));
            $item->setUnit((string) ($row['unit'] ?? 'Stk'));
            $item->setUnitPrice((int) ($row['unitPrice'] ?? 0));
            $item->setVatRate((string) ($row['vatRate'] ?? '20.00'));
            $item->setTaxCategory(TaxCategory::from((string) ($row['taxCategory'] ?? 'standard')));
            $inv->addItem($item);
        }
    }
}
