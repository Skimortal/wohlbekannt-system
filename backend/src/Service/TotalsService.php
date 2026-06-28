<?php

namespace App\Service;

use App\Entity\AbstractDocument;
use App\Entity\AbstractLineItem;

/**
 * Recomputes line snapshots and document totals.
 *
 * Tax is summed per (rate, category) group on the group base — not by adding up
 * per-line tax — so rounding matches the legally expected per-rate calculation.
 * Optional positions are excluded from the document total and reported as a
 * separate gross sum (mirroring the printed quote).
 */
class TotalsService
{
    /**
     * @param iterable<AbstractLineItem> $items
     */
    public function recompute(AbstractDocument $document, iterable $items): void
    {
        $incl = $document->isPricesIncludeVat();

        // group key "rate|category" => ['rate' => , 'category' => , 'net' => , 'gross' => ]
        $groups = [];
        $optionalGross = 0;

        foreach ($items as $item) {
            $item->computeTotals($incl);

            if ($item->isOptional()) {
                $optionalGross += $item->getLineGross();
                continue;
            }

            $key = $item->getVatRate().'|'.$item->getTaxCategory()->value;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'rate' => $item->getVatRate(),
                    'category' => $item->getTaxCategory()->value,
                    'net' => 0,
                    'gross' => 0,
                ];
            }
            $groups[$key]['net'] += $item->getLineNet();
            $groups[$key]['gross'] += $item->getLineGross();
        }

        $breakdown = [];
        $totalNet = 0;
        $totalTax = 0;

        foreach ($groups as $g) {
            $rate = (float) $g['rate'];
            if ($incl) {
                $gross = $g['gross'];
                $net = $rate > 0 ? (int) round($gross / (1 + $rate / 100)) : $gross;
                $tax = $gross - $net;
            } else {
                $net = $g['net'];
                $tax = (int) round($net * $rate / 100);
            }

            $breakdown[] = [
                'rate' => $g['rate'],
                'category' => $g['category'],
                'net' => $net,
                'tax' => $tax,
            ];
            $totalNet += $net;
            $totalTax += $tax;
        }

        // stable ordering by rate desc for predictable display
        usort($breakdown, static fn ($a, $b) => (float) $b['rate'] <=> (float) $a['rate']);

        $document->setTotalNet($totalNet);
        $document->setTotalTax($totalTax);
        $document->setTotalGross($totalNet + $totalTax);
        $document->setOptionalTotalGross($optionalGross);
        $document->setTaxBreakdown($breakdown);
    }
}
