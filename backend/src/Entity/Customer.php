<?php

namespace App\Entity;

use App\Entity\Embeddable\Address;
use App\Enum\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Index(name: 'idx_customer_number', columns: ['customer_number'])]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Kundennummer, assigned from the "customer" number range. */
    #[ORM\Column(length: 30, unique: true, nullable: true)]
    private ?string $customerNumber = null;

    #[ORM\Column(length: 20, enumType: CustomerType::class)]
    private CustomerType $type = CustomerType::PERSON;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    /** Ansprechpartner beim Kunden (z. B. bei Firmen). */
    #[ORM\Column(length: 200, nullable: true)]
    private ?string $contactPerson = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $phone = null;

    /** USt-ID des Kunden — Voraussetzung für EU-Reverse-Charge. */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vatId = null;

    #[ORM\Embedded(class: Address::class)]
    private Address $address;

    /** Optional override of the company default payment terms. */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $paymentTermsDays = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->address = new Address();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getDisplayName(): string
    {
        if (CustomerType::COMPANY === $this->type && $this->companyName) {
            return $this->companyName;
        }

        return trim(($this->firstName ?? '').' '.($this->lastName ?? '')) ?: ($this->companyName ?? '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(?string $v): static
    {
        $this->customerNumber = $v;

        return $this;
    }

    public function getType(): CustomerType
    {
        return $this->type;
    }

    public function setType(CustomerType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $v): static
    {
        $this->companyName = $v;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $v): static
    {
        $this->firstName = $v;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $v): static
    {
        $this->lastName = $v;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $v): static
    {
        $this->email = $v;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $v): static
    {
        $this->phone = $v;

        return $this;
    }

    public function getVatId(): ?string
    {
        return $this->vatId;
    }

    public function setVatId(?string $v): static
    {
        $this->vatId = $v;

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

    public function getPaymentTermsDays(): ?int
    {
        return $this->paymentTermsDays;
    }

    public function setPaymentTermsDays(?int $v): static
    {
        $this->paymentTermsDays = $v;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $v): static
    {
        $this->notes = $v;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
