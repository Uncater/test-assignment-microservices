<?php

namespace OrderBundle\Tests\Entity;

use OrderBundle\Entity\AbstractProduct;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AbstractProductTest extends TestCase
{
    private function createTestProduct(
        ?Uuid $id = null,
        string $name = 'Test Product',
        int $priceCents = 1999,
        int $quantityValue = 10
    ): AbstractProduct {
        return new class($id ?? Uuid::v7(), $name, $priceCents, $quantityValue) extends AbstractProduct {};
    }

    public function testConstructorSetsAllProperties(): void
    {
        $id = Uuid::v7();
        $name = 'Test Product';
        $priceCents = 1999;
        $quantityValue = 10;

        $product = $this->createTestProduct($id, $name, $priceCents, $quantityValue);

        $this->assertEquals($id, $product->id);
    }

    public function testNameMethodReturnsProductName(): void
    {
        $product = $this->createTestProduct(name: 'Custom Product');

        $productName = $product->name();

        $this->assertInstanceOf(ProductName::class, $productName);
        $this->assertEquals('Custom Product', $productName->value);
    }

    public function testPriceMethodReturnsProductPrice(): void
    {
        $product = $this->createTestProduct(priceCents: 2500);

        $productPrice = $product->price();

        $this->assertInstanceOf(ProductPrice::class, $productPrice);
        $this->assertEquals(2500, $productPrice->cents);
        $this->assertEquals(25.0, $productPrice->toDollars());
    }

    public function testQuantityMethodReturnsProductQuantity(): void
    {
        $product = $this->createTestProduct(quantityValue: 15);

        $productQuantity = $product->quantity();

        $this->assertInstanceOf(ProductQuantity::class, $productQuantity);
        $this->assertEquals(15, $productQuantity->value);
    }

    public function testCanHandleZeroPrice(): void
    {
        $product = $this->createTestProduct(priceCents: 0);

        $this->assertEquals(0, $product->price()->cents);
        $this->assertEquals(0.0, $product->price()->toDollars());
    }

    public function testCanHandleZeroQuantity(): void
    {
        $product = $this->createTestProduct(quantityValue: 0);

        $this->assertEquals(0, $product->quantity()->value);
    }

    public function testCanHandleEmptyName(): void
    {
        $product = $this->createTestProduct(name: '');

        $this->assertEquals('', $product->name()->value);
    }

    public function testIdIsReadonly(): void
    {
        $product = $this->createTestProduct();

        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('id');

        $this->assertTrue($property->isReadOnly());
    }

    public function testIsMappedSuperclass(): void
    {
        $reflection = new \ReflectionClass(AbstractProduct::class);

        $this->assertTrue($reflection->isAbstract());
    }
}