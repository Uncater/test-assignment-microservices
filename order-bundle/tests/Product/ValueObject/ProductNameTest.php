<?php

namespace OrderBundle\Tests\Product\ValueObject;

use OrderBundle\Product\ValueObject\ProductName;
use PHPUnit\Framework\TestCase;

class ProductNameTest extends TestCase
{
    public function testConstructorSetsValue(): void
    {
        $name = new ProductName('Test Product');
        
        $this->assertEquals('Test Product', $name->value);
    }

    public function testToStringReturnsValue(): void
    {
        $name = new ProductName('Another Product');
        
        $this->assertEquals('Another Product', (string) $name);
    }

    public function testCanHandleEmptyString(): void
    {
        $name = new ProductName('');
        
        $this->assertEquals('', $name->value);
        $this->assertEquals('', (string) $name);
    }

    public function testCanHandleLongString(): void
    {
        $longName = str_repeat('A', 1000);
        $name = new ProductName($longName);
        
        $this->assertEquals($longName, $name->value);
    }

    public function testCanHandleSpecialCharacters(): void
    {
        $specialName = 'Product with émojis 🚀 & symbols!@#$%';
        $name = new ProductName($specialName);
        
        $this->assertEquals($specialName, $name->value);
    }

    public function testIsReadonly(): void
    {
        $name = new ProductName('Test');
        
        $reflection = new \ReflectionClass($name);
        $property = $reflection->getProperty('value');
        
        $this->assertTrue($property->isReadOnly());
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductName::class);
        
        $this->assertTrue($reflection->isFinal());
    }
}