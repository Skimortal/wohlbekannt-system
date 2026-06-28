<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $repo,
        private readonly ApiPresenter $presenter,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly Security $security,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->repo->createQueryBuilder('u')->orderBy('u.name', 'ASC')->getQuery()->getResult();

        return $this->json(array_map([$this->presenter, 'user'], $users));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->body($request);
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'E-Mail und Passwort sind erforderlich.'], 422);
        }
        if (null !== $this->repo->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Diese E-Mail wird bereits verwendet.'], 409);
        }

        $user = new User();
        $this->hydrate($user, $data);
        $user->setPassword($this->hasher->hashPassword($user, (string) $data['password']));

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($this->presenter->user($user), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(User $user, Request $request): JsonResponse
    {
        $data = $this->body($request);
        $isSelf = $this->security->getUser()?->getUserIdentifier() === $user->getUserIdentifier();

        // Don't let an admin lock themselves out.
        if ($isSelf && (isset($data['active']) && !$data['active'] || isset($data['role']) && 'admin' !== $data['role'])) {
            return $this->json(['error' => 'Eigenes Konto kann nicht deaktiviert oder herabgestuft werden.'], 409);
        }

        $this->hydrate($user, $data);
        if (!empty($data['password'])) {
            $user->setPassword($this->hasher->hashPassword($user, (string) $data['password']));
        }
        $this->em->flush();

        return $this->json($this->presenter->user($user));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(User $user): JsonResponse
    {
        if ($this->security->getUser()?->getUserIdentifier() === $user->getUserIdentifier()) {
            return $this->json(['error' => 'Eigenes Konto kann nicht gelöscht werden.'], 409);
        }
        $this->em->remove($user);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @param array<string, mixed> $data */
    private function hydrate(User $u, array $data): void
    {
        if (isset($data['email'])) {
            $u->setEmail((string) $data['email']);
        }
        if (array_key_exists('name', $data)) {
            $u->setName((string) ($data['name'] ?: $data['email'] ?? ''));
        }
        if (isset($data['role'])) {
            $u->setRoles('admin' === $data['role'] ? ['ROLE_ADMIN'] : []);
        }
        if (array_key_exists('active', $data)) {
            $u->setActive((bool) $data['active']);
        }
    }
}
