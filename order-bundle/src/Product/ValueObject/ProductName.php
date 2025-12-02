<?php

namespace OrderBundle\Product\ValueObject;

final class ProductName
{
    public function __construct(
        public readonly string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
