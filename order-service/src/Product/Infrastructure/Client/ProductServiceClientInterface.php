<?php

namespace App\Product\Infrastructure\Client;

use Symfony\Component\Uid\Uuid;

interface ProductServiceClientInterface
{
    /**
     * @return array{id: string, name: string, price: float, quantity: int}|null
     */
    public function getProduct(Uuid $productId): ?array;

    public function hasAvailableQuantity(Uuid $productId, int $requestedQuantity): bool;
}
