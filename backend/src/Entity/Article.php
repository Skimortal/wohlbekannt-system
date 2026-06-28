<?php

namespace App\Entity;

use App\Enum\TaxCategory;
use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reusable service/product catalog item. Used to quickly build standard quotes;
 * values are copied onto the document line (snapshot), so editing an article
 * later never changes an issued document.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** Unit label, e.g. "Personen", "pauschal", "Stk", "Std". */
    #[ORM\Column(length: 30)]
    private string $unit = 'Stk';

    /** Net unit price in minor units (cents). */
    #[ORM\Column(type: Types::INTEGER)]
    private int $unitPrice = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $vatRate = '20.00';

    #[ORM\Column(length: 20, enumType: TaxCategory::class)]
    private TaxCategory $taxCategory = TaxCategory::STANDARD;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $v): static
    {
        $this->name = $v;

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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $v): static
    {
        $this->category = $v;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $v): static
    {
        $this->active = $v;

        return $this;
    }
}
