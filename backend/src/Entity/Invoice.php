<?php

namespace App\Entity;

use App\Enum\InvoiceStatus;
use App\Enum\InvoiceType;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
class Invoice extends AbstractDocument
{
    #[ORM\Column(length: 20, enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(length: 20, enumType: InvoiceType::class)]
    private InvoiceType $type = InvoiceType::INVOICE;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    /** Leistungszeitraum / Leistungsdatum. */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $servicePeriodStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $servicePeriodEnd = null;

    /** Amount already paid, in cents. */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $paidAmount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    /** The quote this invoice was generated from. */
    #[ORM\ManyToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Quote $relatedQuote = null;

    /** For a CANCELLATION/CREDIT_NOTE: the invoice it reverses. */
    #[ORM\ManyToOne(targetEntity: Invoice::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Invoice $cancelsInvoice = null;

    /** @var Collection<int, InvoiceItem> */
    #[ORM\OneToMany(targetEntity: InvoiceItem::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    /** @var Collection<int, Payment> */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'invoice', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['paidAt' => 'ASC'])]
    private Collection $payments;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    public function getOpenAmount(): int
    {
        return $this->totalGross - $this->paidAmount;
    }

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $v): static
    {
        $this->status = $v;

        return $this;
    }

    public function getType(): InvoiceType
    {
        return $this->type;
    }

    public function setType(InvoiceType $v): static
    {
        $this->type = $v;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeImmutable $v): static
    {
        $this->dueDate = $v;

        return $this;
    }

    public function getServicePeriodStart(): ?\DateTimeImmutable
    {
        return $this->servicePeriodStart;
    }

    public function setServicePeriodStart(?\DateTimeImmutable $v): static
    {
        $this->servicePeriodStart = $v;

        return $this;
    }

    public function getServicePeriodEnd(): ?\DateTimeImmutable
    {
        return $this->servicePeriodEnd;
    }

    public function setServicePeriodEnd(?\DateTimeImmutable $v): static
    {
        $this->servicePeriodEnd = $v;

        return $this;
    }

    public function getPaidAmount(): int
    {
        return $this->paidAmount;
    }

    public function setPaidAmount(int $v): static
    {
        $this->paidAmount = $v;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $v): static
    {
        $this->sentAt = $v;

        return $this;
    }

    public function getRelatedQuote(): ?Quote
    {
        return $this->relatedQuote;
    }

    public function setRelatedQuote(?Quote $v): static
    {
        $this->relatedQuote = $v;

        return $this;
    }

    public function getCancelsInvoice(): ?Invoice
    {
        return $this->cancelsInvoice;
    }

    public function setCancelsInvoice(?Invoice $v): static
    {
        $this->cancelsInvoice = $v;

        return $this;
    }

    /** @return Collection<int, InvoiceItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(InvoiceItem $item): static
    {
        if ($this->items->removeElement($item) && $item->getInvoice() === $this) {
            $item->setInvoice(null);
        }

        return $this;
    }

    /** @return Collection<int, Payment> */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setInvoice($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment) && $payment->getInvoice() === $this) {
            $payment->setInvoice(null);
        }

        return $this;
    }
}
