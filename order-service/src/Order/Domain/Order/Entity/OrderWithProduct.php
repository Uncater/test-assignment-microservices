<?php

namespace App\Order\Domain\Order\Entity;

use App\Order\Domain\Order\Entity\Order;
use App\Product\Domain\Product\Entity\Product;

class OrderWithProduct
{
    public function __construct(
        public readonly Order $order,
        public readonly Product $product
    ) {
    }

    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'product' => $this->product->toArray()
        ];
    }
}
