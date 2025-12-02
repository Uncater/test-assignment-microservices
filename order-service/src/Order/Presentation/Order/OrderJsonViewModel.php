<?php

namespace App\Order\Presentation\Order;

class OrderJsonViewModel
{
    public function __construct(
        public readonly string $orderId,
        public readonly array $product,
        public readonly string $customerName,
        public readonly int $quantityOrdered,
        public readonly string $orderStatus,
    ) {
    }

    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'product' => $this->product,
            'customerName' => $this->customerName,
            'quantityOrdered' => $this->quantityOrdered,
            'orderStatus' => $this->orderStatus,
        ];
    }
}
