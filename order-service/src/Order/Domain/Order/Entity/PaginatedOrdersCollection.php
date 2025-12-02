<?php

namespace App\Order\Domain\Order\Entity;

use OrderBundle\Entity\PaginationInfo;

class PaginatedOrdersCollection
{
    /**
     * @param OrderWithProduct[] $orders
     */
    public function __construct(
        public readonly array $orders,
        public readonly PaginationInfo $pagination
    ) {
    }

    public function toArray(): array
    {
        return [
            'orders' => array_map(fn(OrderWithProduct $orderWithProduct) => $orderWithProduct->toArray(), $this->orders),
            'pagination' => $this->pagination->toArray()
        ];
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function getPagination(): PaginationInfo
    {
        return $this->pagination;
    }
}
