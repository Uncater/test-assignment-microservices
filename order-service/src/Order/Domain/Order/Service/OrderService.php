<?php

namespace App\Order\Domain\Order\Service;

use App\Order\Domain\Order\Entity\Order;
use App\Order\Domain\Order\Entity\OrderWithProduct;
use App\Order\Domain\Order\Entity\PaginatedOrdersCollection;
use App\Order\Domain\Order\Exception\InsufficientStockException;
use App\Order\Domain\Order\Exception\ProductNotFoundException;
use App\Order\Domain\Order\Repository\OrderRepositoryInterface;
use App\Product\Domain\Product\Service\ProductServiceInterface;
use OrderBundle\Entity\PaginationInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductServiceInterface $productService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getOrders(int $page = 1, int $limit = 10): PaginatedOrdersCollection
    {
        $offset = ($page - 1) * $limit;

        $result = $this->orderRepository->findAll($offset, $limit);
        
        $enrichedOrders = [];
        foreach ($result['orders'] as $order) {
            $product = $this->productService->getProduct($order->productId);
            if ($product) {
                $enrichedOrders[] = new OrderWithProduct($order, $product);
            }
        }

        $paginationInfo = new PaginationInfo(
            total: $result['total'],
            page: $page,
            limit: $limit,
            pages: (int) ceil(max(1, $result['total']) / $limit)
        );

        return new PaginatedOrdersCollection($enrichedOrders, $paginationInfo);
    }

    public function getOrder(Uuid $id): ?OrderWithProduct
    {
        $order = $this->orderRepository->findOne($id);
        
        if (!$order) {
            return null;
        }

        $product = $this->productService->getProduct($order->productId);
        
        return new OrderWithProduct($order, $product);
    }

    public function createOrder(
        Uuid $orderId,
        Uuid $productId,
        string $customerName,
        int $quantityOrdered
    ): Order {
        $product = $this->productService->getProduct($productId);
        if (!$product) {
            $this->logger->warning('Attempted to create order for non-existent product', [
                'product_id' => (string) $productId,
                'customer_name' => $customerName
            ]);
            throw new ProductNotFoundException("Product with ID {$productId} not found");
        }

        if (!$this->productService->hasAvailableQuantity($product, $quantityOrdered)) {
            $this->logger->warning('Attempted to create order with insufficient stock', [
                'product_id' => (string) $productId,
                'requested_quantity' => $quantityOrdered,
                'available_quantity' => $product->quantity->value,
                'customer_name' => $customerName
            ]);
            throw new InsufficientStockException(
                (string) $productId,
                $quantityOrdered,
                $product->quantity->value
            );
        }

        $order = $this->orderRepository->create(
            orderId: $orderId,
            productId: $productId,
            customerName: $customerName,
            quantityOrdered: $quantityOrdered
        );

        $this->productService->decreaseProductQuantity($product, $quantityOrdered);

        $this->logger->info('Order created successfully', [
            'order_id' => (string) $orderId,
            'product_id' => (string) $productId,
            'customer_name' => $customerName,
            'quantity_ordered' => $quantityOrdered
        ]);

        return $order;
    }

}
