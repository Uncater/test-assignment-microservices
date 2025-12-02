<?php

namespace App\Product\Infrastructure\MessageHandler;

use App\Product\Domain\Product\Service\ProductService;
use OrderBundle\Messaging\Product\Event\ProductQuantityDecreasedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProductQuantityDecreasedEventHandler
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProductQuantityDecreasedEvent $event): void
    {
        $this->logger->info('Processing product quantity decreased event', [
            'product_id' => (string) $event->product->id,
            'quantity_to_decrease' => $event->quantity,
            'reason' => $event->reason
        ]);

        try {
            $product = $this->productService->getProduct($event->product->id);
            
            if (!$product) {
                $this->logger->warning('Product not found for quantity decrease', [
                    'product_id' => (string) $event->product->id
                ]);
                return;
            }

            $currentQuantity = $product->quantity()->value;
            $newQuantity = $currentQuantity - $event->quantity;

            if ($newQuantity < 0) {
                $this->logger->error('Quantity decrease would result in negative stock', [
                    'product_id' => (string) $event->product->id,
                    'current_quantity' => $currentQuantity,
                    'quantity_to_decrease' => $event->quantity,
                    'calculated_quantity' => $newQuantity
                ]);
                
                $newQuantity = 0;
            }

            $success = $this->productService->updateProductQuantity(
                $event->product->id,
                $newQuantity
            );

            if (!$success) {
                $this->logger->error('Failed to decrease product quantity in database', [
                    'product_id' => (string) $event->product->id,
                    'new_quantity' => $newQuantity
                ]);
                return;
            }

            $this->logger->info('Product quantity decreased successfully', [
                'product_id' => (string) $event->product->id,
                'previous_quantity' => $currentQuantity,
                'new_quantity' => $newQuantity,
                'decreased_by' => $event->quantity
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to process product quantity decreased event', [
                'product_id' => (string) $event->product->id,
                'quantity_to_decrease' => $event->quantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
