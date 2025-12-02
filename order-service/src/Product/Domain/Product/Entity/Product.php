<?php

namespace App\Product\Domain\Product\Entity;

use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use Symfony\Component\Uid\Uuid;

class Product
{
    public function __construct(
        public readonly Uuid $id,
        public readonly ProductName $name,
        public readonly ProductPrice $price,
        public readonly ProductQuantity $quantity
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: Uuid::fromString($data['id']),
            name: new ProductName($data['name']),
            price: ProductPrice::fromDollars($data['price']),
            quantity: new ProductQuantity($data['quantity'])
        );
    }

    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name->value,
            'price' => $this->price->toDollars(),
            'quantity' => $this->quantity->value
        ];
    }

    public function hasAvailableQuantity(int $requestedQuantity): bool
    {
        return $this->quantity->value >= $requestedQuantity;
    }
}
