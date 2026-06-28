<?php

namespace App\Entity;

use App\Entity\Embeddable\Address;
use App\Repository\CompanySettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * The issuing company (Mandant). Single row today; modelled as an entity so a
 * future multi-mandant setup is a small change rather than a rewrite.
 */
#[ORM\Entity(repositoryClass: CompanySettingsRepository::class)]
class CompanySettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $legalName = '';

    #[ORM\Embedded(class: Address::class)]
    private Address $address;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $web = null;

    /** Firmenbuchnummer */
    #[ORM\Column(length: 40, nullable: true)]
    private ?string $companyRegisterNumber = null;

    /** USt-ID (z. B. ATU77877638) */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vatId = null;

    /** Steuer-Nummer */
    #[ORM\Column(length: 40, nullable: true)]
    private ?string $taxNumber = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $managingDirector = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $bankName = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bic = null;

    /** Relative path/URL of the logo used in PDFs. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $defaultCurrency = 'EUR';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '20.00'])]
    private string $defaultVatRate = '20.00';

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 14])]
    private int $defaultPaymentTermsDays = 14;

    /** Default validity (in days) for quotes (freibleibendes Angebot). */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 30])]
    private int $defaultQuoteValidityDays = 30;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $quoteIntroText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $quoteOutroText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $invoiceIntroText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $invoiceOutroText = null;

    public function __construct()
    {
        $this->address = new Address();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(?string $web): static
    {
        $this->web = $web;

        return $this;
    }

    public function getCompanyRegisterNumber(): ?string
    {
        return $this->companyRegisterNumber;
    }

    public function setCompanyRegisterNumber(?string $v): static
    {
        $this->companyRegisterNumber = $v;

        return $this;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function setVatId(?string $vatId): static
    {
        $this->vatId = $vatId;

        return $this;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(?string $taxNumber): static
    {
        $this->taxNumber = $taxNumber;

        return $this;
    }

    public function getManagingDirector(): ?string
    {
        return $this->managingDirector;
    }

    public function setManagingDirector(?string $v): static
    {
        $this->managingDirector = $v;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): static
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic(): ?string
    {
        return $this->bic;
    }

    public function setBic(?string $bic): static
    {
        $this->bic = $bic;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency(string $v): static
    {
        $this->defaultCurrency = $v;

        return $this;
    }

    public function getDefaultVatRate(): string
    {
        return $this->defaultVatRate;
    }

    public function setDefaultVatRate(string $v): static
    {
        $this->defaultVatRate = $v;

        return $this;
    }

    public function getDefaultPaymentTermsDays(): int
    {
        return $this->defaultPaymentTermsDays;
    }

    public function setDefaultPaymentTermsDays(int $v): static
    {
        $this->defaultPaymentTermsDays = $v;

        return $this;
    }

    public function getDefaultQuoteValidityDays(): int
    {
        return $this->defaultQuoteValidityDays;
    }

    public function setDefaultQuoteValidityDays(int $v): static
    {
        $this->defaultQuoteValidityDays = $v;

        return $this;
    }

    public function getQuoteIntroText(): ?string
    {
        return $this->quoteIntroText;
    }

    public function setQuoteIntroText(?string $v): static
    {
        $this->quoteIntroText = $v;

        return $this;
    }

    public function getQuoteOutroText(): ?string
    {
        return $this->quoteOutroText;
    }

    public function setQuoteOutroText(?string $v): static
    {
        $this->quoteOutroText = $v;

        return $this;
    }

    public function getInvoiceIntroText(): ?string
    {
        return $this->invoiceIntroText;
    }

    public function setInvoiceIntroText(?string $v): static
    {
        $this->invoiceIntroText = $v;

        return $this;
    }

    public function getInvoiceOutroText(): ?string
    {
        return $this->invoiceOutroText;
    }

    public function setInvoiceOutroText(?string $v): static
    {
        $this->invoiceOutroText = $v;

        return $this;
    }
}
