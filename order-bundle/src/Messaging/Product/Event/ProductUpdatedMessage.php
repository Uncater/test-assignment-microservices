<?php

namespace OrderBundle\Messaging\Product\Event;

use OrderBundle\Dto\ProductDto;

final class ProductUpdatedMessage
{
    public function __construct(
        public readonly ProductDto $product,
    ) {
    }
}
