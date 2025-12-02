<?php

namespace App\Product\Domain\Product\Entity;

use OrderBundle\Entity\AbstractProduct;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product extends AbstractProduct
{
    public function __construct(
        Uuid $id,
        string $name,
        int $priceCents,
        int $quantityValue
    ) {
        parent::__construct($id, $name, $priceCents, $quantityValue);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ProductName
    {
        return $this->name();
    }

    public function getPrice(): ProductPrice
    {
        return $this->price();
    }

    public function getQuantity(): ProductQuantity
    {
        return $this->quantity();
    }

    public function updateQuantity(ProductQuantity $quantity): void
    {
        // Since properties are readonly, we need to create a new instance
        // This would typically be handled by the repository/service layer
        throw new \BadMethodCallException('Cannot update readonly properties. Use repository methods instead.');
    }

    public function decreaseQuantity(int $amount): void
    {
        // Since properties are readonly, we need to create a new instance
        // This would typically be handled by the repository/service layer
        throw new \BadMethodCallException('Cannot update readonly properties. Use repository methods instead.');
    }

    public function increaseQuantity(int $amount): void
    {
        // Since properties are readonly, we need to create a new instance
        // This would typically be handled by the repository/service layer
        throw new \BadMethodCallException('Cannot update readonly properties. Use repository methods instead.');
    }

    public static function create(
        Uuid $id,
        ProductName $name,
        ProductPrice $price,
        ProductQuantity $quantity
    ): self {
        return new self(
            $id,
            $name->value,
            $price->cents,
            $quantity->value
        );
    }
}