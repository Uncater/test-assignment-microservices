<?php

namespace OrderBundle\Messaging\Product\Event;

use OrderBundle\Dto\ProductDto;

final class ProductCreatedMessage
{
    public function __construct(
        public readonly ProductDto $product,
    ) {
    }
}
