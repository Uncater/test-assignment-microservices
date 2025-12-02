<?php

namespace OrderBundle\Product\ValueObject;

final class ProductPrice
{
    public function __construct(
        public readonly int $cents,
    ) {
    }

    public static function fromDollars(float $dollars): self
    {
        return new self((int) round($dollars * 100));
    }

    public function toDollars(): float
    {
        return $this->cents / 100;
    }
}
