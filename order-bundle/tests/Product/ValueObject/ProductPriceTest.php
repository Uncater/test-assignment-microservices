<?php

namespace OrderBundle\Tests\Product\ValueObject;

use OrderBundle\Product\ValueObject\ProductPrice;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function testConstructorSetsValue(): void
    {
        $price = new ProductPrice(1999);
        
        $this->assertEquals(1999, $price->cents);
    }

    public function testToDollarsConvertsCorrectly(): void
    {
        $price = new ProductPrice(1999);
        
        $this->assertEquals(19.99, $price->toDollars());
    }

    public function testCanHandleZeroPrice(): void
    {
        $price = new ProductPrice(0);
        
        $this->assertEquals(0, $price->cents);
        $this->assertEquals(0.0, $price->toDollars());
    }

    public function testCanHandleLargePrice(): void
    {
        $price = new ProductPrice(999999);
        
        $this->assertEquals(999999, $price->cents);
        $this->assertEquals(9999.99, $price->toDollars());
    }

    public function testRoundsToTwoDecimalPlaces(): void
    {
        $price = new ProductPrice(1);
        
        $this->assertEquals(0.01, $price->toDollars());
    }

    public function testHandlesOddCents(): void
    {
        $price = new ProductPrice(2555);
        
        $this->assertEquals(25.55, $price->toDollars());
    }

    public function testIsReadonly(): void
    {
        $price = new ProductPrice(1000);
        
        $reflection = new \ReflectionClass($price);
        $property = $reflection->getProperty('cents');
        
        $this->assertTrue($property->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductPrice::class);
        
        $this->assertTrue($reflection->isFinal());
    }

    public function testFromDollarsFactoryMethod(): void
    {
        $price = ProductPrice::fromDollars(19.99);
        
        $this->assertEquals(1999, $price->cents);
        $this->assertEquals(19.99, $price->toDollars());
    }

    public function testFromDollarsHandlesRounding(): void
    {
        $price = ProductPrice::fromDollars(19.999);
        
        // Should round to nearest cent
        $this->assertEquals(2000, $price->cents);
        $this->assertEquals(20.0, $price->toDollars());
    }
}