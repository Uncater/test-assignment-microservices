<?php

namespace App\Order\Presentation\Order;

class OrderListJsonViewModel
{
    /**
     * @param OrderJsonViewModel[] $orders
     */
    public function __construct(
        public readonly array $orders,
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total,
        public readonly int $pages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(fn(OrderJsonViewModel $order) => $order->toArray(), $this->orders),
            'meta' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $this->total,
                'pages' => $this->pages,
            ],
        ];
    }
}
