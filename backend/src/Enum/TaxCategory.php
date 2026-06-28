<?php

namespace App\Enum;

/**
 * Legal nature of the VAT treatment of a line. The numeric rate is stored
 * separately (AT standard 20, reduced 13/10). The category drives the legal
 * note printed on the document and how the line is summarised.
 */
enum TaxCategory: string
{
    case STANDARD = 'standard';            // AT Normalsteuersatz / ermäßigt (rate > 0)
    case REVERSE_CHARGE = 'reverse_charge'; // EU B2B, valid VAT ID -> recipient owes VAT (rate 0)
    case EXPORT = 'export';                 // third country (e.g. CH) -> no AT VAT (rate 0)
    case EXEMPT = 'exempt';                 // steuerbefreit, z. B. Kleinunternehmer §6 (rate 0)
    case ZERO = 'zero';                     // 0% but taxable

    public function legalNote(): ?string
    {
        return match ($this) {
            self::REVERSE_CHARGE => 'Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge).',
            self::EXPORT => 'Steuerfreie Ausfuhrlieferung / Drittland – keine österreichische USt.',
            self::EXEMPT => 'Steuerbefreit gemäß § 6 UStG.',
            default => null,
        };
    }
}
