<?php

namespace App\Product\Presentation\Product;

class ProductSingleJsonViewModel
{
    public function __construct(
        public readonly ?ProductJsonViewModel $product,
        public readonly ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => $this->product?->toArray(),
            'meta' => $this->error ? ['error' => $this->error] : [],
        ];
    }
}
