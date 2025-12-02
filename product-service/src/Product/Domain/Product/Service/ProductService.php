<?php

namespace App\Product\Domain\Product\Service;

use App\Product\Domain\Product\Entity\Product;
use App\Product\Domain\Product\Repository\ProductRepositoryInterface;
use OrderBundle\Dto\ProductDto;
use OrderBundle\Messaging\Product\Event\ProductCreatedMessage;
use OrderBundle\Messaging\Product\Event\ProductUpdatedMessage;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * @return array{products: Product[], total: int, page: int, limit: int, pages: int}
     */
    public function getProducts(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $result = $this->productRepository->findAll($offset, $limit);
        
        return [
            'products' => $result['products'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil(max(1, $result['total']) / $limit)
        ];
    }

    public function getProduct(Uuid $id): ?Product
    {
        return $this->productRepository->findOne($id);
    }

    public function createProduct(string $name, float $price, int $quantity): Product
    {
        $id = Uuid::v7();
        $priceVo = ProductPrice::fromDollars($price);
        
        $product = $this->productRepository->create(
            id: $id,
            name: $name,
            priceCents: $priceVo->cents,
            quantityValue: $quantity
        );

        $dto = new ProductDto(
            id: $product->id,
            name: new ProductName($name),
            price: new ProductPrice($priceVo->cents),
            quantity: new ProductQuantity($quantity)
        );

        $this->messageBus->dispatch(new ProductCreatedMessage($dto));

        return $product;
    }

    public function updateProductQuantity(Uuid $productId, int $newQuantity): bool
    {
        $updated = $this->productRepository->updateQuantity($productId, $newQuantity);
        
        if (!$updated) {
            return false;
        }

        $product = $this->productRepository->findOne($productId);
        
        if ($product) {
            $dto = new ProductDto(
                id: $product->id,
                name: $product->name(),
                price: $product->price(),
                quantity: $product->quantity()
            );

            $this->messageBus->dispatch(new ProductUpdatedMessage($dto));
        }

        return true;
    }

}
