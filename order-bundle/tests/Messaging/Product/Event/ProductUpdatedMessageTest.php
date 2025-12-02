<?php

namespace OrderBundle\Tests\Messaging\Product\Event;

use OrderBundle\Messaging\Product\Event\ProductUpdatedMessage;
use OrderBundle\Dto\ProductDto;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProductUpdatedMessageTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $productDto = new ProductDto(
            Uuid::v7(),
            new ProductName('Updated Product'),
            new ProductPrice(2999),
            new ProductQuantity(5)
        );

        $message = new ProductUpdatedMessage($productDto);

        $this->assertSame($productDto, $message->product);
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $reflection = new \ReflectionClass(ProductUpdatedMessage::class);
        $property = $reflection->getProperty('product');

        $this->assertTrue($property->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductUpdatedMessage::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testCanCreateWithDifferentProductDto(): void
    {
        $productDto1 = new ProductDto(
            Uuid::v7(),
            new ProductName('Updated Product 1'),
            new ProductPrice(1999),
            new ProductQuantity(3)
        );

        $productDto2 = new ProductDto(
            Uuid::v7(),
            new ProductName('Updated Product 2'),
            new ProductPrice(3999),
            new ProductQuantity(7)
        );

        $message1 = new ProductUpdatedMessage($productDto1);
        $message2 = new ProductUpdatedMessage($productDto2);

        $this->assertSame($productDto1, $message1->product);
        $this->assertSame($productDto2, $message2->product);
        $this->assertNotSame($message1->product, $message2->product);
    }

    public function testProductDtoRetainsItsProperties(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Updated Product');
        $price = new ProductPrice(2999);
        $quantity = new ProductQuantity(5);

        $productDto = new ProductDto($id, $name, $price, $quantity);
        $message = new ProductUpdatedMessage($productDto);

        $this->assertEquals($id, $message->product->id);
        $this->assertEquals($name, $message->product->name);
        $this->assertEquals($price, $message->product->price);
        $this->assertEquals($quantity, $message->product->quantity);
    }

    public function testCanHandleZeroValues(): void
    {
        $productDto = new ProductDto(
            Uuid::v7(),
            new ProductName(''),
            new ProductPrice(0),
            new ProductQuantity(0)
        );

        $message = new ProductUpdatedMessage($productDto);

        $this->assertSame($productDto, $message->product);
        $this->assertEquals('', $message->product->name->value);
        $this->assertEquals(0, $message->product->price->cents);
        $this->assertEquals(0, $message->product->quantity->value);
    }
}