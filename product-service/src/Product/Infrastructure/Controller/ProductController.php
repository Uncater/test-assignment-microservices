<?php

namespace App\Product\Infrastructure\Controller;

use App\Product\Domain\Product\Service\ProductService;
use App\Product\Presentation\Product\ProductJsonPresenter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class ProductController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductJsonPresenter $presenter,
    ) {
    }

    #[Route('/products', name: 'product_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);

        $result = $this->productService->getProducts($page, $limit);

        $viewModel = $this->presenter->presentProductList(
            products: $result['products'],
            page: $result['page'],
            limit: $result['limit'],
            total: $result['total'],
            pages: $result['pages']
        );

        return new JsonResponse($viewModel->toArray());
    }

    #[Route('/product/{id}', name: 'product_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $uuid = Uuid::fromString($id);
        $product = $this->productService->getProduct($uuid);

        $error = $product ? null : 'Not found';
        $statusCode = $product ? 200 : 404;

        $viewModel = $this->presenter->presentSingleProduct($product, $error);

        return new JsonResponse($viewModel->toArray(), $statusCode);
    }

    #[Route('/product', name: 'product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $name = (string) ($payload['name'] ?? '');
        $price = (float) ($payload['price'] ?? 0);
        $quantity = (int) ($payload['quantity'] ?? 0);

        $product = $this->productService->createProduct($name, $price, $quantity);

        $viewModel = $this->presenter->presentSingleProduct($product);

        return new JsonResponse($viewModel->toArray(), 201);
    }
}
