<?php

namespace App\Tests\Unit\MessageHandler;

use App\Product\Domain\Product\Entity\Product;
use App\Product\Domain\Product\Service\ProductService;
use App\Product\Infrastructure\MessageHandler\ProductQuantityDecreasedEventHandler;
use OrderBundle\Messaging\Product\Event\ProductQuantityDecreasedEvent;
use OrderBundle\Dto\ProductDto;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ProductQuantityDecreasedEventHandlerTest extends TestCase
{
    private ProductService $productService;
    private LoggerInterface $logger;
    private ProductQuantityDecreasedEventHandler $handler;

    protected function setUp(): void
    {
        $this->productService = $this->createMock(ProductService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ProductQuantityDecreasedEventHandler(
            $this->productService,
            $this->logger
        );
    }

    public function testHandleProductQuantityDecreasedEventSuccess(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantityToDecrease = 3;
        $currentQuantity = 10;
        $expectedNewQuantity = 7;

        $productDto = new ProductDto(
            id: $productId,
            name: new ProductName('Test Product'),
            price: ProductPrice::fromDollars(19.99),
            quantity: new ProductQuantity($currentQuantity)
        );

        $event = new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantityToDecrease,
            reason: 'order_created'
        );

        $product = $this->createMock(Product::class);
        $productQuantity = new \OrderBundle\Product\ValueObject\ProductQuantity($currentQuantity);
        
        $product->expects($this->once())
            ->method('quantity')
            ->willReturn($productQuantity);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        $this->productService
            ->expects($this->once())
            ->method('updateProductQuantity')
            ->with($productId, $expectedNewQuantity)
            ->willReturn(true);

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        // Act
        $this->handler->__invoke($event);
    }

    public function testHandleProductQuantityDecreasedEventProductNotFound(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantityToDecrease = 3;

        $productDto = new ProductDto(
            id: $productId,
            name: new ProductName('Test Product'),
            price: ProductPrice::fromDollars(19.99),
            quantity: new ProductQuantity(10)
        );

        $event = new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantityToDecrease,
            reason: 'order_created'
        );

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn(null);

        $this->productService
            ->expects($this->never())
            ->method('updateProductQuantity');

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Product not found for quantity decrease', [
                'product_id' => (string) $productId
            ]);

        // Act
        $this->handler->__invoke($event);
    }

    public function testHandleProductQuantityDecreasedEventNegativeQuantity(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantityToDecrease = 15;
        $currentQuantity = 10;
        $expectedNewQuantity = 0; // Should be set to 0 instead of negative

        $productDto = new ProductDto(
            id: $productId,
            name: new ProductName('Test Product'),
            price: ProductPrice::fromDollars(19.99),
            quantity: new ProductQuantity($currentQuantity)
        );

        $event = new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantityToDecrease,
            reason: 'order_created'
        );

        $product = $this->createMock(Product::class);
        $productQuantity = new \OrderBundle\Product\ValueObject\ProductQuantity($currentQuantity);
        
        $product->expects($this->once())
            ->method('quantity')
            ->willReturn($productQuantity);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        $this->productService
            ->expects($this->once())
            ->method('updateProductQuantity')
            ->with($productId, $expectedNewQuantity)
            ->willReturn(true);

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Quantity decrease would result in negative stock', $this->anything());

        // Act
        $this->handler->__invoke($event);
    }

    public function testHandleProductQuantityDecreasedEventUpdateFails(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantityToDecrease = 3;
        $currentQuantity = 10;
        $expectedNewQuantity = 7;

        $productDto = new ProductDto(
            id: $productId,
            name: new ProductName('Test Product'),
            price: ProductPrice::fromDollars(19.99),
            quantity: new ProductQuantity($currentQuantity)
        );

        $event = new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantityToDecrease,
            reason: 'order_created'
        );

        $product = $this->createMock(Product::class);
        $productQuantity = new \OrderBundle\Product\ValueObject\ProductQuantity($currentQuantity);
        
        $product->expects($this->once())
            ->method('quantity')
            ->willReturn($productQuantity);

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willReturn($product);

        $this->productService
            ->expects($this->once())
            ->method('updateProductQuantity')
            ->with($productId, $expectedNewQuantity)
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to decrease product quantity in database', $this->anything());

        // Act
        $this->handler->__invoke($event);
    }

    public function testHandleProductQuantityDecreasedEventException(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantityToDecrease = 3;

        $productDto = new ProductDto(
            id: $productId,
            name: new ProductName('Test Product'),
            price: ProductPrice::fromDollars(19.99),
            quantity: new ProductQuantity(10)
        );

        $event = new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantityToDecrease,
            reason: 'order_created'
        );

        $exception = new \Exception('Database error');

        $this->productService
            ->expects($this->once())
            ->method('getProduct')
            ->with($productId)
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to process product quantity decreased event', $this->anything());

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->handler->__invoke($event);
    }
}
