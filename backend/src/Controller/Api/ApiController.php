<?php

namespace App\Controller\Api;

use App\Entity\Embeddable\Address;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
    /**
     * Returns a paginated list when ?page>=1 is given ({items,total,page,limit}),
     * otherwise a plain array (capped) for backward compatibility (dropdowns,
     * dashboard, MCP).
     *
     * @param callable(object): array $map
     */
    protected function listResponse(QueryBuilder $qb, Request $request, callable $map, int $legacyCap = 200): JsonResponse
    {
        $page = $request->query->getInt('page', 0);

        if ($page < 1) {
            $items = $qb->setMaxResults($legacyCap)->getQuery()->getResult();

            return $this->json(array_map($map, $items));
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 25)));
        $query = $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit)->getQuery();
        $paginator = new Paginator($query, false);

        return $this->json([
            'items' => array_map($map, iterator_to_array($paginator)),
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /** @return array<string, mixed> */
    protected function body(Request $request): array
    {
        $data = json_decode($request->getContent() ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    protected function parseDate(?string $value): ?\DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }

    /** @param array<string, mixed>|null $data */
    protected function hydrateAddress(Address $address, ?array $data): void
    {
        if (null === $data) {
            return;
        }
        $address->street = $data['street'] ?? null;
        $address->addressLine2 = $data['addressLine2'] ?? null;
        $address->postalCode = $data['postalCode'] ?? null;
        $address->city = $data['city'] ?? null;
        $address->countryCode = $data['countryCode'] ?? 'AT';
    }
}
