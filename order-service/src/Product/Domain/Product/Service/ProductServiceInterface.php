<?php

namespace App\Product\Domain\Product\Service;

use App\Product\Domain\Product\Entity\Product;
use Symfony\Component\Uid\Uuid;

interface ProductServiceInterface
{
    public function getProduct(Uuid $productId): ?Product;

    public function hasAvailableQuantity(Product $product, int $requestedQuantity): bool;

    public function decreaseProductQuantity(Product $product, int $quantity): bool;
}
