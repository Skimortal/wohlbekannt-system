<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MoneyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', [$this, 'money']),
            new TwigFilter('qty', [$this, 'qty']),
        ];
    }

    /** Integer cents -> German money string, e.g. 123456 -> "1.234,56". */
    public function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }

    /** Decimal string quantity -> German, trailing zeros trimmed, e.g. "42.000" -> "42", "1.500" -> "1,5". */
    public function qty(string $value): string
    {
        $n = (float) $value;
        $s = number_format($n, 3, ',', '.');
        $s = rtrim($s, '0');

        return rtrim($s, ',');
    }
}
