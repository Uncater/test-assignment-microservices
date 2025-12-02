<?php

namespace App\Order\Infrastructure\Repository;

use App\Order\Domain\Order\Entity\Order;
use App\Order\Domain\Order\Repository\OrderRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class OrderRepository implements OrderRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->repository = $this->entityManager->getRepository(Order::class);
    }

    public function create(
        Uuid $orderId,
        Uuid $productId,
        string $customerName,
        int $quantityOrdered,
        string $orderStatus = 'Processing'
    ): Order {
        $order = new Order(
            id: $orderId,
            productId: $productId,
            customerName: $customerName,
            quantityOrdered: $quantityOrdered,
            orderStatus: $orderStatus
        );

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    public function findOne(Uuid $id): ?Order
    {
        return $this->repository->find($id);
    }

    public function findAll(int $offset = 0, int $limit = 10): array
    {
        $queryBuilder = $this->repository->createQueryBuilder('o')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('o.id', 'DESC');

        $orders = $queryBuilder->getQuery()->getResult();

        $total = (int) $this->repository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'orders' => $orders,
            'total' => $total
        ];
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    public function remove(Order $order): void
    {
        $this->entityManager->remove($order);
        $this->entityManager->flush();
    }

    public function count(): int
    {
        return (int) $this->repository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByCustomerName(string $customerName): array
    {
        return $this->repository->createQueryBuilder('o')
            ->where('o.customerName = :customerName')
            ->setParameter('customerName', $customerName)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByProductId(Uuid $productId): array
    {
        return $this->repository->createQueryBuilder('o')
            ->where('o.productId = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
