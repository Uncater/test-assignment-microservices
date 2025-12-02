<?php

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Product\Entity\Product;
use App\Product\Domain\Product\Repository\ProductRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class ProductRepository implements ProductRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(Product::class);
    }

    public function create(Uuid $id, string $name, int $priceCents, int $quantityValue): Product
    {
        $product = new Product(
            id: $id,
            name: $name,
            priceCents: $priceCents,
            quantityValue: $quantityValue
        );

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    public function findOne(Uuid $id): ?Product
    {
        return $this->repository->find($id);
    }

    public function findAll(int $offset = 0, int $limit = 10): array
    {
        $queryBuilder = $this->repository->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $products = $queryBuilder->getQuery()->getResult();

        $total = (int) $this->repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'products' => $products,
            'total' => $total
        ];
    }

    public function save(Product $product): void
    {
        $this->entityManager->persist($product);
        $this->entityManager->flush();
    }

    public function remove(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }

    public function count(): int
    {
        return (int) $this->repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function updateQuantity(Uuid $productId, int $newQuantity): bool
    {
        $result = $this->repository->createQueryBuilder('p')
            ->update()
            ->set('p.quantityValue', ':quantity')
            ->where('p.id = :id')
            ->setParameter('quantity', $newQuantity)
            ->setParameter('id', $productId, 'uuid')
            ->getQuery()
            ->execute();

        return $result > 0;
    }
}
