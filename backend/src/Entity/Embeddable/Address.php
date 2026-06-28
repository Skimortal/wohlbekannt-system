<?php

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(length: 200, nullable: true)]
    public ?string $street = null;

    #[ORM\Column(length: 200, nullable: true)]
    public ?string $addressLine2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    public ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    public ?string $city = null;

    /** ISO 3166-1 alpha-2, default Austria. */
    #[ORM\Column(length: 2, options: ['default' => 'AT'])]
    public string $countryCode = 'AT';

    public function isEmpty(): bool
    {
        return null === $this->street && null === $this->postalCode && null === $this->city;
    }
}
