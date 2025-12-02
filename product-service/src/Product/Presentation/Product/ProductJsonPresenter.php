<?php

namespace App\Product\Presentation\Product;

use OrderBundle\Entity\AbstractProduct;

class ProductJsonPresenter
{
    public function present(AbstractProduct $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => (string) $product->name(),
            'price' => $product->price()->toDollars(),
            'quantity' => $product->quantity()->value
        ];
    }

    public function presentSingleProduct(?AbstractProduct $product, ?string $error = null): object
    {
        if ($error) {
            return new class($error) {
                public function __construct(private string $error) {}
                public function toArray(): array { return ['error' => $this->error]; }
            };
        }
        
        return new class($this->present($product)) {
            public function __construct(private array $data) {}
            public function toArray(): array { return $this->data; }
        };
    }

    public function presentProductList(array $products, int $page, int $limit, int $total, int $pages): object
    {
        return new class($products, $page, $limit, $total, $pages) {
            public function __construct(
                private array $products,
                private int $page,
                private int $limit,
                private int $total,
                private int $pages
            ) {}
            
            public function toArray(): array {
                return [
                    'products' => array_map(fn($product) => [
                        'id' => (string) $product->id,
                        'name' => (string) $product->name(),
                        'price' => $product->price()->toDollars(),
                        'quantity' => $product->quantity()->value
                    ], $this->products),
                    'pagination' => [
                        'page' => $this->page,
                        'limit' => $this->limit,
                        'total' => $this->total,
                        'pages' => $this->pages
                    ]
                ];
            }
        };
    }

    public function presentCollection(array $products): array
    {
        return array_map([$this, 'present'], $products);
    }
}
