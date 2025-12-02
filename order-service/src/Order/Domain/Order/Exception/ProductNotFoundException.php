<?php

namespace App\Order\Domain\Order\Exception;

class ProductNotFoundException extends \Exception
{
    public function __construct(string $productId)
    {
        parent::__construct("Product with ID {$productId} not found");
    }
}