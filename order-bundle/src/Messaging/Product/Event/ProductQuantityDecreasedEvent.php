<?php

namespace OrderBundle\Messaging\Product\Event;

use OrderBundle\Dto\ProductDto;

final class ProductQuantityDecreasedEvent
{
    public function __construct(
        public readonly ProductDto $product,
        public readonly int $quantity,
        public readonly string $reason = 'order_created'
    ) {
    }
}
