<?php

namespace OrderBundle\Tests\Messaging\Product\Event;

use OrderBundle\Dto\ProductDto;
use OrderBundle\Messaging\Product\Event\ProductQuantityDecreasedEvent;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class ProductQuantityDecreasedEventTest extends TestCase
{
    private ProductDto $testProductDto;

    protected function setUp(): void
    {
        $this->testProductDto = new ProductDto(
            Uuid::v7(),
            new ProductName('Test Product'),
            ProductPrice::fromDollars(19.99),
            new ProductQuantity(10)
        );
    }

    public function testConstructorSetsAllProperties(): void
    {
        $quantity = 5;
        $reason = 'order_created';

        $event = new ProductQuantityDecreasedEvent($this->testProductDto, $quantity, $reason);

        $this->assertSame($this->testProductDto, $event->product);
        $this->assertSame($quantity, $event->quantity);
        $this->assertSame($reason, $event->reason);
    }

    public function testConstructorUsesDefaultReason(): void
    {
        $quantity = 3;

        $event = new ProductQuantityDecreasedEvent($this->testProductDto, $quantity);

        $this->assertSame($this->testProductDto, $event->product);
        $this->assertSame($quantity, $event->quantity);
        $this->assertSame('order_created', $event->reason);
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $event = new ProductQuantityDecreasedEvent($this->testProductDto, 5, 'test_reason');

        $reflection = new \ReflectionClass($event);
        
        foreach (['product', 'quantity', 'reason'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertTrue($property->isReadOnly(), "Property {$propertyName} should be readonly");
        }
    }

    public function testIsFinalClass(): void
    {
        $reflection = new \ReflectionClass(ProductQuantityDecreasedEvent::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testCanHandleZeroQuantity(): void
    {
        $event = new ProductQuantityDecreasedEvent($this->testProductDto, 0);

        $this->assertSame(0, $event->quantity);
    }

    public function testCanHandleNegativeQuantity(): void
    {
        $event = new ProductQuantityDecreasedEvent($this->testProductDto, -5);

        $this->assertSame(-5, $event->quantity);
    }

    public function testCanHandleLargeQuantity(): void
    {
        $event = new ProductQuantityDecreasedEvent($this->testProductDto, 999999);

        $this->assertSame(999999, $event->quantity);
    }

    public function testCanHandleCustomReasons(): void
    {
        $reasons = [
            'order_cancelled',
            'inventory_adjustment',
            'damaged_goods',
            'return_processed',
            ''
        ];

        foreach ($reasons as $reason) {
            $event = new ProductQuantityDecreasedEvent($this->testProductDto, 1, $reason);
            $this->assertSame($reason, $event->reason);
        }
    }

    public function testProductDtoRetainsItsProperties(): void
    {
        $event = new ProductQuantityDecreasedEvent($this->testProductDto, 5);

        $this->assertSame('Test Product', (string) $event->product->name);
        $this->assertSame(19.99, $event->product->price->toDollars());
        $this->assertSame(10, $event->product->quantity->value);
    }

    public function testCanCreateWithDifferentProductDto(): void
    {
        $anotherProduct = new ProductDto(
            Uuid::v7(),
            new ProductName('Another Product'),
            new ProductPrice(5000),
            new ProductQuantity(20)
        );

        $event = new ProductQuantityDecreasedEvent($anotherProduct, 3, 'custom_reason');

        $this->assertSame($anotherProduct, $event->product);
        $this->assertSame(3, $event->quantity);
        $this->assertSame('custom_reason', $event->reason);
    }
}
