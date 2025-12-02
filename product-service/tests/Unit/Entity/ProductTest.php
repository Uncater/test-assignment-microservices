<?php

namespace App\Tests\Unit\Entity;

use App\Product\Domain\Product\Entity\Product;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProductTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $id = Uuid::v7();
        $name = 'Test Product';
        $priceCents = 1999;
        $quantityValue = 10;

        $product = new Product($id, $name, $priceCents, $quantityValue);

        $this->assertEquals($id, $product->getId());
        $this->assertEquals($name, $product->getName()->value);
        $this->assertEquals($priceCents, $product->getPrice()->cents);
        $this->assertEquals($quantityValue, $product->getQuantity()->value);
    }

    public function testGettersReturnCorrectValueObjects(): void
    {
        $id = Uuid::v7();
        $name = 'Another Product';
        $priceCents = 2500;
        $quantityValue = 5;

        $product = new Product($id, $name, $priceCents, $quantityValue);

        $this->assertInstanceOf(ProductName::class, $product->getName());
        $this->assertInstanceOf(ProductPrice::class, $product->getPrice());
        $this->assertInstanceOf(ProductQuantity::class, $product->getQuantity());
    }

    public function testCreateFactoryMethod(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Factory Product');
        $price = new ProductPrice(3000);
        $quantity = new ProductQuantity(15);

        $product = Product::create($id, $name, $price, $quantity);

        $this->assertEquals($id, $product->getId());
        $this->assertEquals('Factory Product', $product->getName()->value);
        $this->assertEquals(3000, $product->getPrice()->cents);
        $this->assertEquals(15, $product->getQuantity()->value);
    }

    public function testUpdateQuantityThrowsException(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Test', 1000, 5);
        $newQuantity = new ProductQuantity(10);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot update readonly properties');

        $product->updateQuantity($newQuantity);
    }

    public function testDecreaseQuantityThrowsException(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Test', 1000, 5);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot update readonly properties');

        $product->decreaseQuantity(2);
    }

    public function testIncreaseQuantityThrowsException(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Test', 1000, 5);

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot update readonly properties');

        $product->increaseQuantity(3);
    }

    public function testCanHandleZeroPrice(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Free Product', 0, 1);

        $this->assertEquals(0, $product->getPrice()->cents);
        $this->assertEquals(0.0, $product->getPrice()->toDollars());
    }

    public function testCanHandleZeroQuantity(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Out of Stock', 1000, 0);

        $this->assertEquals(0, $product->getQuantity()->value);
    }

    public function testCanHandleLargeValues(): void
    {
        $id = Uuid::v7();
        $product = new Product($id, 'Expensive Product', 999999, 999999);

        $this->assertEquals(999999, $product->getPrice()->cents);
        $this->assertEquals(999999, $product->getQuantity()->value);
    }
}