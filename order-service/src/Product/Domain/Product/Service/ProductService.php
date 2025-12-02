<?php

namespace App\Product\Domain\Product\Service;

use App\Product\Domain\Product\Entity\Product;
use App\Product\Infrastructure\Client\ProductServiceClientInterface;
use OrderBundle\Messaging\Product\Event\ProductQuantityDecreasedEvent;
use OrderBundle\Dto\ProductDto;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly ProductServiceClientInterface $productServiceClient,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function getProduct(Uuid $productId): ?Product
    {
        $productData = $this->productServiceClient->getProduct($productId);
        
        if (!$productData) {
            return null;
        }

        return Product::fromArray($productData);
    }

    public function hasAvailableQuantity(Product $product, int $requestedQuantity): bool
    {
        return $product->hasAvailableQuantity($requestedQuantity);
    }

    public function decreaseProductQuantity(Product $product, int $quantity): bool
    {
        $productDto = new ProductDto(
            id: $product->id,
            name: $product->name,
            price: $product->price,
            quantity: $product->quantity
        );
        $this->messageBus->dispatch(new ProductQuantityDecreasedEvent(
            product: $productDto,
            quantity: $quantity,
            reason: 'order_created'
        ));

        return true;
    }

}