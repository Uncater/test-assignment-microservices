<?php

namespace App\Product\Infrastructure\Client;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductServiceClient implements ProductServiceClientInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private readonly string $productServiceUrl,
        private readonly LoggerInterface $logger,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function getProduct(Uuid $productId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $this->productServiceUrl . '/product/' . $productId);
            
            if ($response->getStatusCode() === 404) {
                return null;
            }

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Product service returned non-200 status', [
                    'status_code' => $response->getStatusCode(),
                    'product_id' => (string) $productId
                ]);
                return null;
            }

            $data = $response->toArray();
            
            if (empty($data)) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch product from product service', [
                'product_id' => (string) $productId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function hasAvailableQuantity(Uuid $productId, int $requestedQuantity): bool
    {
        $productData = $this->getProduct($productId);
        
        if (!$productData) {
            return false;
        }

        return $productData['quantity'] >= $requestedQuantity;
    }
}
