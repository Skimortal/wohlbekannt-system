<?php

namespace App\Controller\Api;

use App\Entity\Embeddable\Address;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
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
