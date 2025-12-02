<?php

namespace App\Order\Presentation\Order;

class OrderSingleJsonViewModel
{
    public function __construct(
        public readonly ?OrderJsonViewModel $order,
        public readonly ?string $error = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => $this->order?->toArray(),
            'meta' => $this->error ? ['error' => $this->error] : [],
        ];
    }
}
