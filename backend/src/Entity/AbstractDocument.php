<?php

namespace App\Entity;

use App\Entity\Embeddable\Address;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Shared fields for quotes and invoices: recipient snapshot, price mode,
 * monetary totals (cents) and the per-rate tax breakdown. Recipient data is
 * snapshotted at issue time so the document stays correct if the customer
 * record later changes.
 */
#[ORM\MappedSuperclass]
abstract class AbstractDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    /** Document number (e.g. AN-1032 / RE-0001); null while DRAFT. */
    #[ORM\Column(length: 30, unique: true, nullable: true)]
    protected ?string $number = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    protected ?Customer $customer = null;

    // --- recipient snapshot ---
    #[ORM\Column(length: 200)]
    protected string $recipientName = '';

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $recipientContact = null;

    #[ORM\Column(length: 30, nullable: true)]
    protected ?string $recipientCustomerNumber = null;

    #[ORM\Column(length: 20, nullable: true)]
    protected ?string $recipientVatId = null;

    #[ORM\Embedded(class: Address::class)]
    protected Address $recipientAddress;

    /** Issuer-side contact shown as "Ihr Ansprechpartner". */
    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $contactPerson = null;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    protected string $currency = 'EUR';

    /** True = unit prices are VAT-inclusive (Bruttopreise). */
    #[ORM\Column(options: ['default' => true])]
    protected bool $pricesIncludeVat = true;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    protected \DateTimeImmutable $issueDate;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $introText = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $outroText = null;

    /** Internal notes, not printed. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $internalNotes = null;

    // --- snapshot totals (cents), excluding optional positions ---
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $totalNet = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $totalTax = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $totalGross = 0;

    /** Sum (gross, cents) of optional positions — shown separately. */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $optionalTotalGross = 0;

    /**
     * Per-rate breakdown: list of {rate, category, net, tax} (cents).
     *
     * @var array<int, array{rate: string, category: string, net: int, tax: int}>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $taxBreakdown = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->recipientAddress = new Address();
        $this->issueDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** Copy current customer data into the document snapshot. */
    public function snapshotFromCustomer(Customer $c): void
    {
        $this->customer = $c;
        $this->recipientName = $c->getDisplayName();
        $this->recipientContact = $c->getContactPerson();
        $this->recipientCustomerNumber = $c->getCustomerNumber();
        $this->recipientVatId = $c->getVatId();

        $addr = new Address();
        $src = $c->getAddress();
        $addr->street = $src->street;
        $addr->addressLine2 = $src->addressLine2;
        $addr->postalCode = $src->postalCode;
        $addr->city = $src->city;
        $addr->countryCode = $src->countryCode;
        $this->recipientAddress = $addr;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $v): static
    {
        $this->number = $v;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $v): static
    {
        $this->customer = $v;

        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $v): static
    {
        $this->recipientName = $v;

        return $this;
    }

    public function getRecipientContact(): ?string
    {
        return $this->recipientContact;
    }

    public function setRecipientContact(?string $v): static
    {
        $this->recipientContact = $v;

        return $this;
    }

    public function getRecipientCustomerNumber(): ?string
    {
        return $this->recipientCustomerNumber;
    }

    public function setRecipientCustomerNumber(?string $v): static
    {
        $this->recipientCustomerNumber = $v;

        return $this;
    }

    public function getRecipientVatId(): ?string
    {
        return $this->recipientVatId;
    }

    public function setRecipientVatId(?string $v): static
    {
        $this->recipientVatId = $v;

        return $this;
    }

    public function getRecipientAddress(): Address
    {
        return $this->recipientAddress;
    }

    public function setRecipientAddress(Address $v): static
    {
        $this->recipientAddress = $v;

        return $this;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $v): static
    {
        $this->contactPerson = $v;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $v): static
    {
        $this->currency = $v;

        return $this;
    }

    public function isPricesIncludeVat(): bool
    {
        return $this->pricesIncludeVat;
    }

    public function setPricesIncludeVat(bool $v): static
    {
        $this->pricesIncludeVat = $v;

        return $this;
    }

    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $v): static
    {
        $this->issueDate = $v;

        return $this;
    }

    public function getIntroText(): ?string
    {
        return $this->introText;
    }

    public function setIntroText(?string $v): static
    {
        $this->introText = $v;

        return $this;
    }

    public function getOutroText(): ?string
    {
        return $this->outroText;
    }

    public function setOutroText(?string $v): static
    {
        $this->outroText = $v;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $v): static
    {
        $this->internalNotes = $v;

        return $this;
    }

    public function getTotalNet(): int
    {
        return $this->totalNet;
    }

    public function setTotalNet(int $v): static
    {
        $this->totalNet = $v;

        return $this;
    }

    public function getTotalTax(): int
    {
        return $this->totalTax;
    }

    public function setTotalTax(int $v): static
    {
        $this->totalTax = $v;

        return $this;
    }

    public function getTotalGross(): int
    {
        return $this->totalGross;
    }

    public function setTotalGross(int $v): static
    {
        $this->totalGross = $v;

        return $this;
    }

    public function getOptionalTotalGross(): int
    {
        return $this->optionalTotalGross;
    }

    public function setOptionalTotalGross(int $v): static
    {
        $this->optionalTotalGross = $v;

        return $this;
    }

    /** @return array<int, array{rate: string, category: string, net: int, tax: int}> */
    public function getTaxBreakdown(): array
    {
        return $this->taxBreakdown;
    }

    /** @param array<int, array{rate: string, category: string, net: int, tax: int}> $v */
    public function setTaxBreakdown(array $v): static
    {
        $this->taxBreakdown = $v;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
