<?php

namespace OrderBundle\Product\ValueObject;

final class ProductQuantity
{
    public function __construct(
        public readonly int $value,
    ) {
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
