<?php

namespace App\Entity;

use App\Enum\TaxCategory;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Shared fields & money logic for quote and invoice line items.
 *
 * All monetary values are integer minor units (cents). `unitPrice` is stored as
 * entered, in the document's price mode (net or VAT-inclusive). lineNet/lineTax/
 * lineGross are computed snapshots so an issued document never changes.
 */
#[ORM\MappedSuperclass]
abstract class AbstractLineItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    /** Sort order within the document. */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    protected int $position = 0;

    /** Optional position ("Opt.") — excluded from the document total, summed separately. */
    #[ORM\Column(options: ['default' => false])]
    protected bool $optional = false;

    #[ORM\Column(length: 255)]
    protected string $title = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3, options: ['default' => '1.000'])]
    protected string $quantity = '1.000';

    #[ORM\Column(length: 30)]
    protected string $unit = 'Stk';

    /** Net or gross unit price (per document price mode), in cents. */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $unitPrice = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '20.00'])]
    protected string $vatRate = '20.00';

    #[ORM\Column(length: 20, enumType: TaxCategory::class)]
    protected TaxCategory $taxCategory = TaxCategory::STANDARD;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $lineNet = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $lineTax = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    protected int $lineGross = 0;

    /**
     * Recompute the snapshot totals from quantity, unitPrice and vatRate.
     * Tax is also computed per line for convenience; the authoritative document
     * tax is summed per rate group by the totals service to match legal rounding.
     */
    public function computeTotals(bool $pricesIncludeVat): void
    {
        $rate = (float) $this->vatRate;
        $raw = $this->unitPrice * (float) $this->quantity;

        if ($pricesIncludeVat) {
            $this->lineGross = (int) round($raw);
            $this->lineNet = $rate > 0 ? (int) round($this->lineGross / (1 + $rate / 100)) : $this->lineGross;
            $this->lineTax = $this->lineGross - $this->lineNet;
        } else {
            $this->lineNet = (int) round($raw);
            $this->lineTax = (int) round($this->lineNet * $rate / 100);
            $this->lineGross = $this->lineNet + $this->lineTax;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $v): static
    {
        $this->position = $v;

        return $this;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function setOptional(bool $v): static
    {
        $this->optional = $v;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $v): static
    {
        $this->title = $v;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $v): static
    {
        $this->description = $v;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $v): static
    {
        $this->quantity = $v;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $v): static
    {
        $this->unit = $v;

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $v): static
    {
        $this->unitPrice = $v;

        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function setVatRate(string $v): static
    {
        $this->vatRate = $v;

        return $this;
    }

    public function getTaxCategory(): TaxCategory
    {
        return $this->taxCategory;
    }

    public function setTaxCategory(TaxCategory $v): static
    {
        $this->taxCategory = $v;

        return $this;
    }

    public function getLineNet(): int
    {
        return $this->lineNet;
    }

    public function getLineTax(): int
    {
        return $this->lineTax;
    }

    public function getLineGross(): int
    {
        return $this->lineGross;
    }
}
