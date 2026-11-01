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
 *
 * Per-line discounts are applied inside the line snapshot. A document-level
 * discount (percent or fixed amount, in the document's price mode) is applied to
 * the sum of regular positions and split proportionally across the tax groups,
 * so every rate's tax base is reduced correctly.
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

        $baseKey = $incl ? 'gross' : 'net';
        $subtotal = array_sum(array_column($groups, $baseKey));
        $discount = $this->documentDiscount($document, $subtotal);
        $this->distribute($groups, $baseKey, $subtotal, $discount);

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
        $document->setDiscountTotal($discount);
        $document->setOptionalTotalGross($optionalGross);
        $document->setTaxBreakdown($breakdown);
    }

    /** Discount in cents (price mode); sign follows the subtotal (Storno = negative). */
    private function documentDiscount(AbstractDocument $document, int $subtotal): int
    {
        $value = (float) $document->getDiscountValue();
        if (0 === $subtotal || $value <= 0) {
            return 0;
        }

        return match ($document->getDiscountType()) {
            'percent' => (int) round($subtotal * min($value, 100.0) / 100),
            'amount' => ($subtotal < 0 ? -1 : 1) * min((int) round($value * 100), abs($subtotal)),
            default => 0,
        };
    }

    /**
     * Reduce each group's base by its proportional share of the discount; the
     * last group absorbs the rounding remainder so the shares add up exactly.
     *
     * @param array<string, array{rate: string, category: string, net: int, gross: int}> $groups
     */
    private function distribute(array &$groups, string $baseKey, int $subtotal, int $discount): void
    {
        if (0 === $discount || 0 === $subtotal) {
            return;
        }
        $remaining = $discount;
        $keys = array_keys($groups);
        $last = end($keys);
        foreach ($keys as $key) {
            $share = $key === $last
                ? $remaining
                : (int) round($discount * $groups[$key][$baseKey] / $subtotal);
            $groups[$key][$baseKey] -= $share;
            $remaining -= $share;
        }
    }
}
