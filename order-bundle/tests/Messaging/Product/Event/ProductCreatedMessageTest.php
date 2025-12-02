<?php

namespace OrderBundle\Tests\Messaging\Product\Event;

use OrderBundle\Messaging\Product\Event\ProductCreatedMessage;
use OrderBundle\Dto\ProductDto;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProductCreatedMessageTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $productDto = new ProductDto(
            Uuid::v7(),
            new ProductName('Test Product'),
            new ProductPrice(1999),
            new ProductQuantity(10)
        );

        $message = new ProductCreatedMessage($productDto);

        $this->assertSame($productDto, $message->product);
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $reflection = new \ReflectionClass(ProductCreatedMessage::class);
        $property = $reflection->getProperty('product');

        $this->assertTrue($property->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductCreatedMessage::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testCanCreateWithDifferentProductDto(): void
    {
        $productDto1 = new ProductDto(
            Uuid::v7(),
            new ProductName('Product 1'),
            new ProductPrice(1999),
            new ProductQuantity(5)
        );

        $productDto2 = new ProductDto(
            Uuid::v7(),
            new ProductName('Product 2'),
            new ProductPrice(2999),
            new ProductQuantity(15)
        );

        $message1 = new ProductCreatedMessage($productDto1);
        $message2 = new ProductCreatedMessage($productDto2);

        $this->assertSame($productDto1, $message1->product);
        $this->assertSame($productDto2, $message2->product);
        $this->assertNotSame($message1->product, $message2->product);
    }

    public function testProductDtoRetainsItsProperties(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Test Product');
        $price = new ProductPrice(1999);
        $quantity = new ProductQuantity(10);

        $productDto = new ProductDto($id, $name, $price, $quantity);
        $message = new ProductCreatedMessage($productDto);

        $this->assertEquals($id, $message->product->id);
        $this->assertEquals($name, $message->product->name);
        $this->assertEquals($price, $message->product->price);
        $this->assertEquals($quantity, $message->product->quantity);
    }
}