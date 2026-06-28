<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Enum\TaxCategory;
use App\Repository\ArticleRepository;
use App\Service\ApiPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/articles')]
class ArticleController extends ApiController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ArticleRepository $repo,
        private readonly ApiPresenter $presenter,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $onlyActive = $request->query->getBoolean('active', false);
        $qb = $this->repo->createQueryBuilder('a')->orderBy('a.name', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('a.name LIKE :q OR a.category LIKE :q')->setParameter('q', '%'.$q.'%');
        }
        if ($onlyActive) {
            $qb->andWhere('a.active = true');
        }

        return $this->json(array_map([$this->presenter, 'article'], $qb->getQuery()->getResult()));
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(Article $article): JsonResponse
    {
        return $this->json($this->presenter->article($article));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $article = new Article();
        $this->hydrate($article, $this->body($request));
        $this->em->persist($article);
        $this->em->flush();

        return $this->json($this->presenter->article($article), JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(Article $article, Request $request): JsonResponse
    {
        $this->hydrate($article, $this->body($request));
        $this->em->flush();

        return $this->json($this->presenter->article($article));
    }

    #[Route('/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Article $article): JsonResponse
    {
        $this->em->remove($article);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /** @param array<string, mixed> $data */
    private function hydrate(Article $a, array $data): void
    {
        if (isset($data['name'])) {
            $a->setName((string) $data['name']);
        }
        if (array_key_exists('description', $data)) {
            $a->setDescription($data['description'] ?: null);
        }
        if (isset($data['unit'])) {
            $a->setUnit((string) $data['unit']);
        }
        if (array_key_exists('unitPrice', $data)) {
            $a->setUnitPrice((int) $data['unitPrice']);
        }
        if (isset($data['vatRate'])) {
            $a->setVatRate((string) $data['vatRate']);
        }
        if (isset($data['taxCategory'])) {
            $a->setTaxCategory(TaxCategory::from($data['taxCategory']));
        }
        if (array_key_exists('category', $data)) {
            $a->setCategory($data['category'] ?: null);
        }
        if (array_key_exists('active', $data)) {
            $a->setActive((bool) $data['active']);
        }
    }
}
