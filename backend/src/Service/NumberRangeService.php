<?php

namespace App\Service;

use App\Entity\NumberRange;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Assigns gapless sequential document numbers. The counter row is locked
 * (PESSIMISTIC_WRITE) inside a transaction so concurrent requests cannot
 * produce duplicate or skipped numbers.
 */
class NumberRangeService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function next(string $key): string
    {
        return $this->em->wrapInTransaction(function () use ($key): string {
            $range = $this->em->createQuery(
                'SELECT r FROM App\Entity\NumberRange r WHERE r.rangeKey = :k'
            )
                ->setParameter('k', $key)
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$range instanceof NumberRange) {
                throw new \RuntimeException(sprintf('Nummernkreis "%s" ist nicht konfiguriert.', $key));
            }

            $year = (int) (new \DateTimeImmutable())->format('Y');

            if ($range->isYearlyReset() && $range->getCurrentYear() !== $year) {
                $range->setCurrentYear($year);
                $range->setNextValue(1);
            }

            $value = $range->getNextValue();
            $number = $range->format($value, $year);
            $range->setNextValue($value + 1);

            $this->em->flush();

            return $number;
        });
    }
}
