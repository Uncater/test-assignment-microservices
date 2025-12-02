<?php

namespace OrderBundle\Tests\Product\ValueObject;

use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;

class ProductQuantityTest extends TestCase
{
    public function testConstructorSetsValue(): void
    {
        $quantity = new ProductQuantity(10);
        
        $this->assertEquals(10, $quantity->value);
    }

    public function testCanHandleZeroQuantity(): void
    {
        $quantity = new ProductQuantity(0);
        
        $this->assertEquals(0, $quantity->value);
    }

    public function testCanHandleLargeQuantity(): void
    {
        $quantity = new ProductQuantity(999999);
        
        $this->assertEquals(999999, $quantity->value);
    }

    public function testIsReadonly(): void
    {
        $quantity = new ProductQuantity(5);
        
        $reflection = new \ReflectionClass($quantity);
        $property = $reflection->getProperty('value');
        
        $this->assertTrue($property->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductQuantity::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function testCanHandleNegativeQuantity(): void
    {
        // This might be allowed for business reasons (e.g., backorders)
        $quantity = new ProductQuantity(-5);
        
        $this->assertEquals(-5, $quantity->value);
    }

    public function testToStringReturnsStringValue(): void
    {
        $quantity = new ProductQuantity(42);
        
        $this->assertEquals('42', (string) $quantity);
    }

    public function testEquality(): void
    {
        $quantity1 = new ProductQuantity(10);
        $quantity2 = new ProductQuantity(10);
        $quantity3 = new ProductQuantity(5);
        
        $this->assertEquals($quantity1->value, $quantity2->value);
        $this->assertNotEquals($quantity1->value, $quantity3->value);
    }
}