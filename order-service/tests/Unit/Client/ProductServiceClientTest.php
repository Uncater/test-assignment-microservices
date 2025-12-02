<?php

namespace App\Tests\Unit\Client;

use App\Product\Infrastructure\Client\ProductServiceClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ProductServiceClientTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ProductServiceClient $productServiceClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->productServiceClient = new ProductServiceClient(
            'http://product-service',
            $this->logger,
            $this->httpClient
        );
    }

    public function testGetProductSuccess(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $productData = [
            'id' => (string) $productId,
            'name' => 'Test Product',
            'price' => 19.99,
            'quantity' => 10
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($productData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://product-service/product/' . $productId)
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->getProduct($productId);

        // Assert
        $this->assertSame($productData, $result);
    }

    public function testGetProductNotFound(): void
    {
        // Arrange
        $productId = Uuid::v7();

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(404);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://product-service/product/' . $productId)
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->getProduct($productId);

        // Assert
        $this->assertNull($result);
    }

    public function testGetProductServerError(): void
    {
        // Arrange
        $productId = Uuid::v7();

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://product-service/product/' . $productId)
            ->willThrowException(new \Exception('Server error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch product from product service', [
                'product_id' => (string) $productId,
                'error' => 'Server error'
            ]);

        // Act
        $result = $this->productServiceClient->getProduct($productId);

        // Assert
        $this->assertNull($result);
    }

    public function testGetProductHttpException(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $exception = new \Exception('Network error');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://product-service/product/' . $productId)
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to fetch product from product service', [
                'product_id' => (string) $productId,
                'error' => 'Network error'
            ]);

        // Act
        $result = $this->productServiceClient->getProduct($productId);

        // Assert
        $this->assertNull($result);
    }

    public function testGetProductNullData(): void
    {
        // Arrange
        $productId = Uuid::v7();

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn([]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://product-service/product/' . $productId)
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->getProduct($productId);

        // Assert
        $this->assertNull($result);
    }

    public function testHasAvailableQuantityTrue(): void
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

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($productData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->hasAvailableQuantity($productId, $requestedQuantity);

        // Assert
        $this->assertTrue($result);
    }

    public function testHasAvailableQuantityFalse(): void
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

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->willReturn($productData);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->hasAvailableQuantity($productId, $requestedQuantity);

        // Assert
        $this->assertFalse($result);
    }

    public function testHasAvailableQuantityProductNotFound(): void
    {
        // Arrange
        $productId = Uuid::v7();
        $requestedQuantity = 5;

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(404);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        // Act
        $result = $this->productServiceClient->hasAvailableQuantity($productId, $requestedQuantity);

        // Assert
        $this->assertFalse($result);
    }

}
