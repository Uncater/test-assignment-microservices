<?php

namespace OrderBundle\Tests\Dto;

use OrderBundle\Dto\ProductDto;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProductDtoTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Test Product');
        $price = new ProductPrice(1999);
        $quantity = new ProductQuantity(10);

        $dto = new ProductDto($id, $name, $price, $quantity);

        $this->assertEquals($id, $dto->id);
        $this->assertEquals($name, $dto->name);
        $this->assertEquals($price, $dto->price);
        $this->assertEquals($quantity, $dto->quantity);
    }

    public function testPropertiesAreReadonly(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Test Product');
        $price = new ProductPrice(1999);
        $quantity = new ProductQuantity(10);

        $dto = new ProductDto($id, $name, $price, $quantity);

        $reflection = new \ReflectionClass($dto);
        
        $this->assertTrue($reflection->getProperty('id')->isReadOnly());
        $this->assertTrue($reflection->getProperty('name')->isReadOnly());
        $this->assertTrue($reflection->getProperty('price')->isReadOnly());
        $this->assertTrue($reflection->getProperty('quantity')->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductDto::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function testCanCreateWithDifferentValues(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Another Product');
        $price = new ProductPrice(2500);
        $quantity = new ProductQuantity(5);

        $dto = new ProductDto($id, $name, $price, $quantity);

        $this->assertEquals('Another Product', $dto->name->value);
        $this->assertEquals(2500, $dto->price->cents);
        $this->assertEquals(5, $dto->quantity->value);
    }

    public function testCanHandleZeroValues(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('');
        $price = new ProductPrice(0);
        $quantity = new ProductQuantity(0);

        $dto = new ProductDto($id, $name, $price, $quantity);

        $this->assertEquals('', $dto->name->value);
        $this->assertEquals(0, $dto->price->cents);
        $this->assertEquals(0, $dto->quantity->value);
    }

    public function testValueObjectsRetainTheirBehavior(): void
    {
        $id = Uuid::v7();
        $name = new ProductName('Test Product');
        $price = new ProductPrice(1999);
        $quantity = new ProductQuantity(10);

        $dto = new ProductDto($id, $name, $price, $quantity);

        // Test that value objects still work as expected
        $this->assertEquals('Test Product', (string) $dto->name);
        $this->assertEquals(19.99, $dto->price->toDollars());
        $this->assertEquals('10', (string) $dto->quantity);
    }
}