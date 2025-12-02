<?php

namespace App\Tests\Unit\Service;

use App\Product\Domain\Product\Entity\Product;
use App\Product\Domain\Product\Repository\ProductRepositoryInterface;
use App\Product\Domain\Product\Service\ProductService;
use OrderBundle\Messaging\Product\Event\ProductCreatedMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProductServiceTest extends TestCase
{
    private ProductRepositoryInterface $repository;
    private MessageBusInterface $messageBus;
    private ProductService $productService;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->productService = new ProductService($this->repository, $this->messageBus);
    }

    public function testGetProducts(): void
    {
        // Arrange
        $products = [
            new Product(Uuid::v7(), 'Product 1', 1000, 5),
            new Product(Uuid::v7(), 'Product 2', 2000, 10),
        ];
        
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with(0, 10)
            ->willReturn(['products' => $products, 'total' => 2]);

        // Act
        $result = $this->productService->getProducts(1, 10);

        // Assert
        $this->assertSame($products, $result['products']);
        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(1, $result['pages']);
    }

    public function testGetProductsPagination(): void
    {
        // Arrange
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->with(20, 5) // page 5, limit 5 = offset 20
            ->willReturn(['products' => [], 'total' => 100]);

        // Act
        $result = $this->productService->getProducts(5, 5);

        // Assert
        $this->assertSame(5, $result['page']);
        $this->assertSame(5, $result['limit']);
        $this->assertSame(100, $result['total']);
        $this->assertSame(20, $result['pages']); // 100 / 5 = 20 pages
    }

    public function testGetProduct(): void
    {
        // Arrange
        $id = Uuid::v7();
        $product = new Product($id, 'Test Product', 1500, 3);
        
        $this->repository
            ->expects($this->once())
            ->method('findOne')
            ->with($id)
            ->willReturn($product);

        // Act
        $result = $this->productService->getProduct($id);

        // Assert
        $this->assertSame($product, $result);
    }

    public function testGetProductNotFound(): void
    {
        // Arrange
        $id = Uuid::v7();
        
        $this->repository
            ->expects($this->once())
            ->method('findOne')
            ->with($id)
            ->willReturn(null);

        // Act
        $result = $this->productService->getProduct($id);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateProduct(): void
    {
        // Arrange
        $product = new Product(Uuid::v7(), 'New Product', 2999, 7);
        
        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->isInstanceOf(Uuid::class),
                'New Product',
                2999, // $29.99 in cents
                7
            )
            ->willReturn($product);

        $productDto = new \OrderBundle\Dto\ProductDto(
            Uuid::v7(),
            new \OrderBundle\Product\ValueObject\ProductName('Test Product'),
            new \OrderBundle\Product\ValueObject\ProductPrice(1999),
            new \OrderBundle\Product\ValueObject\ProductQuantity(7)
        );
        $envelope = new Envelope(new ProductCreatedMessage($productDto));
        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ProductCreatedMessage::class))
            ->willReturn($envelope);

        // Act
        $result = $this->productService->createProduct('New Product', 29.99, 7);

        // Assert
        $this->assertSame($product, $result);
    }

}
