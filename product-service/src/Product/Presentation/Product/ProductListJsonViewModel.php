<?php

namespace App\Product\Presentation\Product;

class ProductListJsonViewModel
{
    /**
     * @param ProductJsonViewModel[] $products
     */
    public function __construct(
        public readonly array $products,
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total,
        public readonly int $pages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn(ProductJsonViewModel $product) => $product->toArray(), $this->products),
            'meta' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $this->total,
                'pages' => $this->pages,
            ],
        ];
    }
}
