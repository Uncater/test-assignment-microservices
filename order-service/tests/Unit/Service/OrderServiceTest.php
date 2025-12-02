<?php

namespace App\Tests\Unit\Service;

use App\Order\Domain\Order\Entity\Order;
use App\Order\Domain\Order\Entity\OrderWithProduct;
use App\Order\Domain\Order\Entity\PaginatedOrdersCollection;
use App\Order\Domain\Order\Exception\InsufficientStockException;
use App\Order\Domain\Order\Exception\ProductNotFoundException;
use App\Order\Domain\Order\Repository\OrderRepositoryInterface;
use App\Order\Domain\Order\Service\OrderService;
use App\Product\Domain\Product\Entity\Product;
use App\Product\Domain\Product\Service\ProductServiceInterface;
use OrderBundle\Entity\PaginationInfo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class OrderServiceTest extends TestCase
{
    private OrderRepositoryInterface $orderRepository;
    private ProductServiceInterface $productService;
    private LoggerInterface $logger;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->productService = $this->createMock(ProductServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orderService = new OrderService(
            $this->orderRepository,
            $this->productService,
            $this->logger
        );
    }

    public function testGetOrders(): void
    {
        // Arrange
        $orders = [
            new Order(Uuid::v7(), Uuid::v7(), 'Customer 1', 2),
            new Order(Uuid::v7(), Uuid::v7(), 'Customer 2', 3),
        ];
        
        $this->orderRepository
            ->expects($this->once())
            ->method('findAll')
            ->with(0, 10)
            ->willReturn(['orders' => $orders, 'total' => 2]);

        $product = Product::fromArray(['id' => '01234567-89ab-cdef-0123-456789abcdef', 'name' => 'Test Product', 'price' => 19.99, 'quantity' => 10]);
        
        $this->productService
            ->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($product);

        // Act
        $result = $this->orderService->getOrders(1, 10);

        // Assert
        $this->assertInstanceOf(PaginatedOrdersCollection::class, $result);
        $this->assertCount(2, $result->getOrders());
        $this->assertSame(2, $result->getPagination()->total);
        $this->assertSame(1, $result->getPagination()->page);
        $this->assertSame(10, $result->getPagination()->limit);
        $this->assertSame(1, $result->getPagination()->pages);
    }

    public function testGetOrdersPagination(): void
    {
        // Arrange
        $this->orderRepository
            ->expects($this->once())
            ->method('findAll')
            ->with(20, 5) // page 5, limit 5 = offset 20
            ->willReturn(['orders' => [], 'total' => 100]);

        // Act
        $result = $this->orderService->getOrders(5, 5);

        // Assert
        $this->assertInstanceOf(PaginatedOrdersCollection::class, $result);
        $this->assertSame(5, $result->getPagination()->page);
        $this->assertSame(5, $result->getPagination()->limit);
        $this->assertSame(100, $result->getPagination()->total);
        $this->assertSame(20, $result->getPagination()->pages); // 100 / 5 = 20 pages
    }

    public function testGetOrder(): void
    {
        // Arrange
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $order = new Order($orderId, $productId, 'Test Customer', 2);
        $product = Product::fromArray(['id' => (string) $productId, 'name' => 'Test Product', 'price' => 19.99, 'quantity' => 10]);
        
        $this->orderRepository
            ->expects($this->once())
            ->method('findOne')
            ->with($orderId)
            ->willReturn($order);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        // Act
        $result = $this->orderService->getOrder($orderId);

        // Assert
        $this->assertInstanceOf(OrderWithProduct::class, $result);
        $this->assertSame($order, $result->order);
        $this->assertInstanceOf(Product::class, $result->product);
        $this->assertSame((string) $productId, (string) $result->product->id);
        $this->assertSame('Test Product', $result->product->name->value);
    }

    public function testGetOrderNotFound(): void
    {
        // Arrange
        $orderId = Uuid::v7();
        
        $this->orderRepository
            ->expects($this->once())
            ->method('findOne')
            ->with($orderId)
            ->willReturn(null);

        // Act
        $result = $this->orderService->getOrder($orderId);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateOrderSuccess(): void
    {
        // Arrange
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'John Doe';
        $quantityOrdered = 2;
        
        $product = Product::fromArray(['id' => (string) $productId, 'name' => 'Test Product', 'price' => 19.99, 'quantity' => 10]);
        $order = new Order($orderId, $productId, $customerName, $quantityOrdered);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        $this->productService
            ->expects($this->once())
            ->method('hasAvailableQuantity')
            ->with($product, $quantityOrdered)
            ->willReturn(true);

        $this->orderRepository
            ->expects($this->once())
            ->method('create')
            ->with($orderId, $productId, $customerName, $quantityOrdered)
            ->willReturn($order);

        $this->productService
            ->expects($this->once())
            ->method('decreaseProductQuantity')
            ->with($product, $quantityOrdered)
            ->willReturn(true);

        // Act
        $result = $this->orderService->createOrder($orderId, $productId, $customerName, $quantityOrdered);

        // Assert
        $this->assertSame($order, $result);
    }

    public function testCreateOrderProductNotFound(): void
    {
        // Arrange
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'John Doe';
        $quantityOrdered = 2;

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn(null);

        // Act & Assert
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage("Product with ID {$productId} not found");

        $this->orderService->createOrder($orderId, $productId, $customerName, $quantityOrdered);
    }

    public function testCreateOrderInsufficientStock(): void
    {
        // Arrange
        $orderId = Uuid::v7();
        $productId = Uuid::v7();
        $customerName = 'John Doe';
        $quantityOrdered = 15;
        
        $product = Product::fromArray(['id' => (string) $productId, 'name' => 'Test Product', 'price' => 19.99, 'quantity' => 10]);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        $this->productService
            ->expects($this->once())
            ->method('hasAvailableQuantity')
            ->with($product, $quantityOrdered)
            ->willReturn(false);

        // Act & Assert
        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage("Insufficient stock for product {$productId}. Requested: {$quantityOrdered}, Available: 10");

        $this->orderService->createOrder($orderId, $productId, $customerName, $quantityOrdered);
    }

}
