<?php

namespace App\Order\Infrastructure\Controller;

use App\Order\Domain\Order\Exception\InsufficientStockException;
use App\Order\Domain\Order\Exception\ProductNotFoundException;
use App\Order\Domain\Order\Service\OrderService;
use App\Order\Presentation\Order\OrderJsonPresenter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class OrderController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderJsonPresenter $presenter,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/orders', name: 'order_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $result = $this->orderService->getOrders($page, $limit);

        $viewModel = $this->presenter->presentOrderList(
            enrichedOrders: $result->getOrders(),
            page: $result->getPagination()->page,
            limit: $result->getPagination()->limit,
            total: $result->getPagination()->total,
            pages: $result->getPagination()->pages
        );

        return new JsonResponse($viewModel->toArray());
    }

    #[Route('/orders/{id}', name: 'order_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
            $enrichedOrder = $this->orderService->getOrder($uuid);

            $error = $enrichedOrder ? null : 'Order not found';
            $statusCode = $enrichedOrder ? 200 : 404;

            $viewModel = $this->presenter->presentSingleOrder($enrichedOrder, $error);

            return new JsonResponse($viewModel->toArray(), $statusCode);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid UUID provided for order lookup', [
                'provided_id' => $id,
                'error' => $e->getMessage()
            ]);

            $viewModel = $this->presenter->presentSingleOrder(null, 'Invalid order ID format');
            return new JsonResponse($viewModel->toArray(), 400);
        }
    }

    #[Route('/order', name: 'order_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $data = $payload['data'] ?? [];

        try {
            // Extract and validate input
            $orderId = isset($data['orderId']) ? Uuid::fromString($data['orderId']) : Uuid::v7();
            $productId = Uuid::fromString($data['productId'] ?? '');
            $customerName = (string) ($data['customerName'] ?? '');
            $quantityOrdered = (int) ($data['quantityOrdered'] ?? 0);

            // Validate required fields
            if (empty($customerName)) {
                return new JsonResponse([
                    'data' => null,
                    'meta' => ['error' => 'Customer name is required']
                ], 400);
            }

            if ($quantityOrdered <= 0) {
                return new JsonResponse([
                    'data' => null,
                    'meta' => ['error' => 'Quantity ordered must be greater than 0']
                ], 400);
            }

            // Create the order
            $order = $this->orderService->createOrder(
                orderId: $orderId,
                productId: $productId,
                customerName: $customerName,
                quantityOrdered: $quantityOrdered
            );

            // Get enriched order data for response
            $enrichedOrder = $this->orderService->getOrder($order->id);
            $viewModel = $this->presenter->presentSingleOrder($enrichedOrder);

            return new JsonResponse($viewModel->toArray(), 201);

        } catch (ProductNotFoundException $e) {
            $this->logger->warning('Order creation failed - product not found', [
                'error' => $e->getMessage(),
                'payload' => $data
            ]);

            return new JsonResponse([
                'data' => null,
                'meta' => ['error' => 'Product not found']
            ], 404);

        } catch (InsufficientStockException $e) {
            $this->logger->warning('Order creation failed - insufficient stock', [
                'error' => $e->getMessage(),
                'payload' => $data
            ]);

            return new JsonResponse([
                'data' => null,
                'meta' => ['error' => 'Insufficient stock available']
            ], 400);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Order creation failed - invalid input', [
                'error' => $e->getMessage(),
                'payload' => $data
            ]);

            return new JsonResponse([
                'data' => null,
                'meta' => ['error' => 'Invalid input data']
            ], 400);

        } catch (\Exception $e) {
            $this->logger->error('Order creation failed - unexpected error', [
                'error' => $e->getMessage(),
                'payload' => $data
            ]);

            return new JsonResponse([
                'data' => null,
                'meta' => ['error' => 'Internal server error']
            ], 500);
        }
    }
}
