<?php

namespace App\Order\Presentation\Order;

use App\Order\Domain\Order\Entity\Order;
use App\Order\Domain\Order\Entity\OrderWithProduct;

class OrderJsonPresenter
{
    public function presentOrder(Order $order, ?array $product = null): OrderJsonViewModel
    {
        $productData = $product ?? [
            'id' => (string) $order->productId,
            'name' => 'Unknown Product',
            'price' => 0.0,
            'quantity' => 0
        ];

        return new OrderJsonViewModel(
            orderId: (string) $order->id,
            product: $productData,
            customerName: $order->customerName,
            quantityOrdered: $order->quantityOrdered,
            orderStatus: $order->getOrderStatus(),
        );
    }

    public function presentEnrichedOrder(array $enrichedOrder): OrderJsonViewModel
    {
        return $this->presentOrder(
            $enrichedOrder['order'],
            $enrichedOrder['product']
        );
    }

    /**
     * @param array[] $enrichedOrders
     * @return OrderJsonViewModel[]
     */
    public function presentOrders(array $enrichedOrders): array
    {
        return array_map(
            fn(array $enrichedOrder) => $this->presentEnrichedOrder($enrichedOrder),
            $enrichedOrders
        );
    }

    /**
     * @param array[] $enrichedOrders
     */
    public function presentOrderList(
        array $enrichedOrders,
        int $page,
        int $limit,
        int $total,
        int $pages
    ): OrderListJsonViewModel {
        $orderViewModels = $this->presentOrders($enrichedOrders);
        
        return new OrderListJsonViewModel(
            orders: $orderViewModels,
            page: $page,
            limit: $limit,
            total: $total,
            pages: $pages
        );
    }

    public function presentOrderWithProduct(OrderWithProduct $orderWithProduct): OrderJsonViewModel
    {
        $productData = $orderWithProduct->product ? $orderWithProduct->product->toArray() : null;
        
        return $this->presentOrder($orderWithProduct->order, $productData);
    }

    public function presentSingleOrder(OrderWithProduct|array|null $enrichedOrder, ?string $error = null): OrderSingleJsonViewModel
    {
        $orderViewModel = null;
        
        if ($enrichedOrder instanceof OrderWithProduct) {
            $orderViewModel = $this->presentOrderWithProduct($enrichedOrder);
        } elseif (is_array($enrichedOrder)) {
            $orderViewModel = $this->presentEnrichedOrder($enrichedOrder);
        }
        
        return new OrderSingleJsonViewModel(
            order: $orderViewModel,
            error: $error
        );
    }
}
