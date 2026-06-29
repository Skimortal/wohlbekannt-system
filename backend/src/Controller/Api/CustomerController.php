<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Entity\NumberRange;
use App\Enum\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\ApiPresenter;
use App\Service\NumberRangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/customers')]
class CustomerController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerRepository $repo,
        private readonly ApiPresenter $presenter,
        private readonly NumberRangeService $numbers,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $qb = $this->repo->createQueryBuilder('c')->orderBy('c.createdAt', 'DESC');
        if ('' !== $q) {
            $qb->andWhere('c.companyName LIKE :q OR c.firstName LIKE :q OR c.lastName LIKE :q OR c.customerNumber LIKE :q OR c.email LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }
        return $this->listResponse($qb, $request, [$this->presenter, 'customer']);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Customer $customer): JsonResponse
    {
        return $this->json($this->presenter->customer($customer));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $customer = new Customer();
        $this->hydrate($customer, $this->body($request));

        if (null === $customer->getCustomerNumber()) {
            $customer->setCustomerNumber($this->numbers->next(NumberRange::KEY_CUSTOMER));
        }

        $this->em->persist($customer);
        $this->em->flush();

        return $this->json($this->presenter->customer($customer), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(Customer $customer, Request $request): JsonResponse
    {
        $this->hydrate($customer, $this->body($request));
        $this->em->flush();

        return $this->json($this->presenter->customer($customer));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Customer $customer): JsonResponse
    {
        $this->em->remove($customer);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @param array<string, mixed> $data */
    private function hydrate(Customer $c, array $data): void
    {
        if (isset($data['type'])) {
            $c->setType(CustomerType::from($data['type']));
        }
        if (array_key_exists('companyName', $data)) {
            $c->setCompanyName($data['companyName'] ?: null);
        }
        if (array_key_exists('firstName', $data)) {
            $c->setFirstName($data['firstName'] ?: null);
        }
        if (array_key_exists('lastName', $data)) {
            $c->setLastName($data['lastName'] ?: null);
        }
        if (array_key_exists('contactPerson', $data)) {
            $c->setContactPerson($data['contactPerson'] ?: null);
        }
        if (array_key_exists('email', $data)) {
            $c->setEmail($data['email'] ?: null);
        }
        if (array_key_exists('phone', $data)) {
            $c->setPhone($data['phone'] ?: null);
        }
        if (array_key_exists('vatId', $data)) {
            $c->setVatId($data['vatId'] ?: null);
        }
        if (array_key_exists('paymentTermsDays', $data)) {
            $c->setPaymentTermsDays(null !== $data['paymentTermsDays'] ? (int) $data['paymentTermsDays'] : null);
        }
        if (array_key_exists('notes', $data)) {
            $c->setNotes($data['notes'] ?: null);
        }
        if (array_key_exists('customerNumber', $data) && $data['customerNumber']) {
            $c->setCustomerNumber($data['customerNumber']);
        }
        if (isset($data['address']) && is_array($data['address'])) {
            $this->hydrateAddress($c->getAddress(), $data['address']);
        }
    }
}
