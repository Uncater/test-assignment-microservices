<?php

namespace App\Order\Domain\Order\Exception;

class InsufficientStockException extends \Exception
{
    public function __construct(string $productId, int $requested, int $available)
    {
        parent::__construct("Insufficient stock for product {$productId}. Requested: {$requested}, Available: {$available}");
    }
}