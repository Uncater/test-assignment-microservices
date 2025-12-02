<?php

namespace App\Product\Domain\Product\Repository;

use App\Product\Domain\Product\Entity\Product;
use Symfony\Component\Uid\Uuid;

interface ProductRepositoryInterface
{
    public function create(Uuid $id, string $name, int $priceCents, int $quantityValue): Product;

    public function findOne(Uuid $id): ?Product;

    /**
     * @return array{products: Product[], total: int}
     */
    public function findAll(int $offset = 0, int $limit = 10): array;

    public function save(Product $product): void;

    public function remove(Product $product): void;

    public function count(): int;

    public function updateQuantity(Uuid $productId, int $newQuantity): bool;
}
