<?php

namespace App\Tests\Unit\Service;

use App\Product\Domain\Product\Service\ProductService;
use App\Product\Infrastructure\Client\ProductServiceClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProductServiceTest extends TestCase
{
    private ProductServiceClientInterface $productServiceClient;
    private MessageBusInterface $messageBus;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->productServiceClient = $this->createMock(ProductServiceClientInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->productService = new ProductService(
            $this->productServiceClient,
            $this->messageBus
        );
    }

    public function testDecreaseProductQuantity(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $quantity = 5;
        $productData = [
            'id' => (string) $productId,
            'name' => 'Test Product',
            'price' => 19.99,
            'quantity' => 10
        ];
        
        $product = \App\Product\Domain\Product\Entity\Product::fromArray($productData);

        $this->productServiceClient
            ->expects($this->never())
            ->method('getProduct');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($productId, $quantity) {
                return $message instanceof \OrderBundle\Messaging\Product\Event\ProductQuantityDecreasedEvent
                    && $message->product->id->equals($productId)
                    && $message->quantity === $quantity
                    && $message->reason === 'order_created';
            }))
            ->willReturnCallback(function ($message) {
                return new Envelope($message);
            });

        // Act
        $result = $this->productService->decreaseProductQuantity($product, $quantity);

        // Assert
        $this->assertTrue($result);
    }

    public function testHasAvailableQuantity(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $requestedQuantity = 5;
        $productData = [
            'id' => (string) $productId,
            'name' => 'Test Product',
            'price' => 19.99,
            'quantity' => 10
        ];
        
        $product = \App\Product\Domain\Product\Entity\Product::fromArray($productData);

        // Act
        $result = $this->productService->hasAvailableQuantity($product, $requestedQuantity);

        // Assert
        $this->assertTrue($result);
    }

    public function testHasAvailableQuantityInsufficientStock(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $requestedQuantity = 15;
        $productData = [
            'id' => (string) $productId,
            'name' => 'Test Product',
            'price' => 19.99,
            'quantity' => 10
        ];
        
        $product = \App\Product\Domain\Product\Entity\Product::fromArray($productData);

        // Act
        $result = $this->productService->hasAvailableQuantity($product, $requestedQuantity);

        // Assert
        $this->assertFalse($result);
    }
}
