<?php

namespace App\Controller\Api;

use App\Entity\CompanySettings;
use App\Repository\CompanySettingsRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/company-settings')]
class CompanySettingsController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CompanySettingsRepository $repo,
        private readonly ApiPresenter $presenter,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json($this->presenter->companySettings($this->settings()));
    }

    #[Route('', methods: ['PUT', 'PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $c = $this->settings();
        $data = $this->body($request);

        foreach ([
            'legalName' => 'setLegalName',
            'phone' => 'setPhone',
            'email' => 'setEmail',
            'web' => 'setWeb',
            'companyRegisterNumber' => 'setCompanyRegisterNumber',
            'vatId' => 'setVatId',
            'taxNumber' => 'setTaxNumber',
            'managingDirector' => 'setManagingDirector',
            'bankName' => 'setBankName',
            'iban' => 'setIban',
            'bic' => 'setBic',
            'logoPath' => 'setLogoPath',
            'defaultCurrency' => 'setDefaultCurrency',
            'defaultVatRate' => 'setDefaultVatRate',
            'quoteIntroText' => 'setQuoteIntroText',
            'quoteOutroText' => 'setQuoteOutroText',
            'invoiceIntroText' => 'setInvoiceIntroText',
            'invoiceOutroText' => 'setInvoiceOutroText',
        ] as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $c->{$setter}($data[$field]);
            }
        }
        if (array_key_exists('defaultPaymentTermsDays', $data)) {
            $c->setDefaultPaymentTermsDays((int) $data['defaultPaymentTermsDays']);
        }
        if (array_key_exists('defaultQuoteValidityDays', $data)) {
            $c->setDefaultQuoteValidityDays((int) $data['defaultQuoteValidityDays']);
        }
        if (isset($data['address']) && is_array($data['address'])) {
            $this->hydrateAddress($c->getAddress(), $data['address']);
        }

        $this->em->flush();

        return $this->json($this->presenter->companySettings($c));
    }

    private function settings(): CompanySettings
    {
        $c = $this->repo->findOneBy([]);
        if (!$c instanceof CompanySettings) {
            $c = new CompanySettings();
            $this->em->persist($c);
            $this->em->flush();
        }

        return $c;
    }
}
