<?php

namespace OrderBundle\Tests;

use OrderBundle\OrderBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OrderBundleTest extends TestCase
{
    public function testIsBundle(): void
    {
        $bundle = new OrderBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
    }

    public function testGetName(): void
    {
        $bundle = new OrderBundle();
        
        $this->assertEquals('OrderBundle', $bundle->getName());
    }

    public function testGetPath(): void
    {
        $bundle = new OrderBundle();
        
        $path = $bundle->getPath();
        $this->assertStringContainsString('order-bundle', $path);
        $this->assertStringEndsWith('src', $path);
    }

    public function testGetNamespace(): void
    {
        $bundle = new OrderBundle();
        
        $this->assertEquals('OrderBundle', $bundle->getNamespace());
    }

    public function testCanBeInstantiated(): void
    {
        $bundle = new OrderBundle();
        
        $this->assertInstanceOf(OrderBundle::class, $bundle);
    }
}