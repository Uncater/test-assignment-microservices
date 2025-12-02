<?php

namespace OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use Symfony\Component\Uid\Uuid;

#[ORM\MappedSuperclass]
abstract class AbstractProduct
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid', unique: true)]
        public readonly Uuid $id,

        #[ORM\Column(type: 'string', length: 255)]
        private readonly string $name,

        #[ORM\Column(type: 'integer')]
        private readonly int $priceCents,

        #[ORM\Column(type: 'integer')]
        private readonly int $quantityValue,
    ) {
    }

    public function name(): ProductName
    {
        return new ProductName($this->name);
    }

    public function price(): ProductPrice
    {
        return new ProductPrice($this->priceCents);
    }

    public function quantity(): ProductQuantity
    {
        return new ProductQuantity($this->quantityValue);
    }
}
