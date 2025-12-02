<?php

namespace App\Tests\Unit\Entity;

use App\Order\Domain\Order\Entity\Order;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class OrderTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'John Doe';
        $quantityOrdered = 5;

        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->assertEquals($orderId, $order->id);
        $this->assertEquals($productId, $order->productId);
        $this->assertEquals($customerName, $order->customerName);
        $this->assertEquals($quantityOrdered, $order->quantityOrdered);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'Jane Smith';
        $quantityOrdered = 3;

        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->assertSame($orderId, $order->id);
        $this->assertSame($productId, $order->productId);
        $this->assertSame($customerName, $order->customerName);
        $this->assertSame($quantityOrdered, $order->quantityOrdered);
    }

    public function testCanHandleZeroQuantity(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'Test Customer';
        $quantityOrdered = 0;

        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->assertEquals(0, $order->quantityOrdered);
    }

    public function testCanHandleLargeQuantity(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'Test Customer';
        $quantityOrdered = 999999;

        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->assertEquals(999999, $order->quantityOrdered);
    }

    public function testCustomerNameCanBeEmpty(): void
    {
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = '';
        $quantityOrdered = 1;

        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->assertEquals('', $order->customerName);
    }
}