<?php

namespace App\Entity;

use App\Repository\NumberRangeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Gapless sequential numbering per document type (Austrian requirement).
 * The actual atomic increment happens in NumberRangeService with a row lock;
 * this entity only stores the counter state.
 */
#[ORM\Entity(repositoryClass: NumberRangeRepository::class)]
class NumberRange
{
    public const KEY_QUOTE = 'quote';
    public const KEY_INVOICE = 'invoice';
    public const KEY_CREDIT_NOTE = 'credit_note';
    public const KEY_CANCELLATION = 'cancellation';
    public const KEY_CUSTOMER = 'customer';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private string $rangeKey;

    #[ORM\Column(length: 10)]
    private string $prefix = '';

    /** Next value to assign. */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $nextValue = 1;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 4])]
    private int $padding = 4;

    /** Reset the counter at the start of each calendar year. */
    #[ORM\Column(options: ['default' => false])]
    private bool $yearlyReset = false;

    /** Year the current counter belongs to (only used when yearlyReset). */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $currentYear = null;

    /** Include the year in the formatted number, e.g. "RE-2026-0001". */
    #[ORM\Column(options: ['default' => false])]
    private bool $includeYear = false;

    public function __construct(string $rangeKey, string $prefix = '')
    {
        $this->rangeKey = $rangeKey;
        $this->prefix = $prefix;
    }

    public function format(int $value, int $year): string
    {
        $number = str_pad((string) $value, $this->padding, '0', STR_PAD_LEFT);
        if ($this->includeYear) {
            return sprintf('%s%d-%s', $this->prefix, $year, $number);
        }

        return $this->prefix.$number;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRangeKey(): string
    {
        return $this->rangeKey;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $v): static
    {
        $this->prefix = $v;

        return $this;
    }

    public function getNextValue(): int
    {
        return $this->nextValue;
    }

    public function setNextValue(int $v): static
    {
        $this->nextValue = $v;

        return $this;
    }

    public function getPadding(): int
    {
        return $this->padding;
    }

    public function setPadding(int $v): static
    {
        $this->padding = $v;

        return $this;
    }

    public function isYearlyReset(): bool
    {
        return $this->yearlyReset;
    }

    public function setYearlyReset(bool $v): static
    {
        $this->yearlyReset = $v;

        return $this;
    }

    public function getCurrentYear(): ?int
    {
        return $this->currentYear;
    }

    public function setCurrentYear(?int $v): static
    {
        $this->currentYear = $v;

        return $this;
    }

    public function isIncludeYear(): bool
    {
        return $this->includeYear;
    }

    public function setIncludeYear(bool $v): static
    {
        $this->includeYear = $v;

        return $this;
    }
}
