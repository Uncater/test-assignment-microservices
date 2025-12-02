<?php

namespace OrderBundle\Dto;

use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use Symfony\Component\Uid\Uuid;

final class ProductDto
{
    public function __construct(
        public readonly Uuid $id,
        public readonly ProductName $name,
        public readonly ProductPrice $price,
        public readonly ProductQuantity $quantity,
    ) {
    }
}
