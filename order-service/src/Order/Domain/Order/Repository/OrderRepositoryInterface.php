<?php

namespace App\Order\Domain\Order\Repository;

use App\Order\Domain\Order\Entity\Order;
use Symfony\Component\Uid\Uuid;

interface OrderRepositoryInterface
{
    public function create(
        Uuid $orderId,
        Uuid $productId,
        string $customerName,
        int $quantityOrdered,
        string $orderStatus = 'Processing'
    ): Order;

    public function findOne(Uuid $id): ?Order;

    /**
     * @return array{orders: Order[], total: int}
     */
    public function findAll(int $offset = 0, int $limit = 10): array;

    public function save(Order $order): void;

    public function remove(Order $order): void;

    public function count(): int;

    /**
     * @return Order[]
     */
    public function findByCustomerName(string $customerName): array;

    /**
     * @return Order[]
     */
    public function findByProductId(Uuid $productId): array;
}
