<?php

namespace App\Order\Domain\Order\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'uuid')]
        public readonly Uuid $id,

        #[ORM\Column(type: 'uuid')]
        public readonly Uuid $productId,

        #[ORM\Column(type: 'string', length: 255)]
        public readonly string $customerName,

        #[ORM\Column(type: 'integer')]
        public readonly int $quantityOrdered,

        #[ORM\Column(type: 'string', length: 50)]
        private string $orderStatus = 'Processing'
    ) {
    }

    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    public function updateStatus(string $status): void
    {
        $this->orderStatus = $status;
    }

    public function isProcessing(): bool
    {
        return $this->orderStatus === 'Processing';
    }

    public function isCompleted(): bool
    {
        return $this->orderStatus === 'Completed';
    }

    public function isCancelled(): bool
    {
        return $this->orderStatus === 'Cancelled';
    }

    public function complete(): void
    {
        $this->orderStatus = 'Completed';
    }

    public function cancel(): void
    {
        $this->orderStatus = 'Cancelled';
    }
}
