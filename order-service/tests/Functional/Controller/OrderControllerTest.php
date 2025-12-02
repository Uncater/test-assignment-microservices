<?php

namespace App\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OrderBundle\Testing\Handler\TestProductMessageHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class OrderControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private TestProductMessageHandler $messageHandler;

    protected function setUp(): void
    {
        // Create client first
        $this->client = static::createClient();
        
        // Get the entity manager from the client's container
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        
        // Get the test message handler
        $this->messageHandler = $this->client->getContainer()->get(TestProductMessageHandler::class);
        
        // Ensure database schema exists and is clean for each test
        $this->ensureSchemaExists();
        $this->cleanDatabase();
        
        // Clear any previous messages
        $this->messageHandler->clearHandledMessages();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $this->cleanDatabase();
        
        // Close the entity manager
        $this->entityManager->close();
        
        parent::tearDown();
    }

    private function ensureSchemaExists(): void
    {
        // Create schema if it doesn't exist
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        try {
            $schemaTool->createSchema($metadata);
        } catch (\Exception $e) {
            // Schema might already exist, that's fine
        }
    }

    private function cleanDatabase(): void
    {
        // Truncate all tables to ensure clean state
        $connection = $this->entityManager->getConnection();
        try {
            $connection->executeStatement('TRUNCATE TABLE orders RESTART IDENTITY CASCADE');
        } catch (\Exception $e) {
            // Table might not exist yet, that's fine
        }
    }

    private function createOrderFixture(
        ?Uuid $orderId = null,
        ?Uuid $productId = null,
        string $customerName = 'Test Customer',
        int $quantityOrdered = 2
    ): string {
        $orderData = [
            'data' => [
                'productId' => (string) ($productId ?? Uuid::fromString('019abba0-fffb-780c-9b4f-9c4934250a44')),
                'customerName' => $customerName,
                'quantityOrdered' => $quantityOrdered
            ]
        ];

        if ($orderId) {
            $orderData['data']['orderId'] = (string) $orderId;
        }

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        return $responseData['data']['orderId'];
    }

    public function testHealthEndpoint(): void
    {
        $this->client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('ok', $responseData['status']);
        $this->assertSame('order-service', $responseData['service']);
    }

    public function testGetOrdersEmpty(): void
    {
        $this->client->request('GET', '/orders');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame([], $responseData['data']);
        $this->assertSame(1, $responseData['meta']['page']);
        $this->assertSame(10, $responseData['meta']['limit']);
        $this->assertSame(0, $responseData['meta']['total']);
        $this->assertSame(1, $responseData['meta']['pages']);
    }

    public function testGetOrdersWithData(): void
    {
        // Create test orders
        $productId = Uuid::fromString('019abba0-fffb-780c-9b4f-9c4934250a44'); // Known product ID
        $order1Id = $this->createOrderFixture(productId: $productId, customerName: 'John Doe', quantityOrdered: 2);
        $order2Id = $this->createOrderFixture(productId: $productId, customerName: 'Jane Smith', quantityOrdered: 1);

        $this->client->request('GET', '/orders');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $responseData['data']);
        $this->assertSame(2, $responseData['meta']['total']);
        $this->assertSame(1, $responseData['meta']['pages']);

        // Check first order (should be order2 due to DESC ordering)
        $firstOrder = $responseData['data'][0];
        $this->assertSame($order2Id, $firstOrder['orderId']);
        $this->assertSame('Jane Smith', $firstOrder['customerName']);
        $this->assertSame(1, $firstOrder['quantityOrdered']);
        $this->assertSame('Processing', $firstOrder['orderStatus']);
        $this->assertArrayHasKey('product', $firstOrder);
    }

    public function testGetOrdersPagination(): void
    {
        // Create 15 test orders
        $productId = Uuid::fromString('019abba0-fffb-780c-9b4f-9c4934250a44');
        for ($i = 1; $i <= 15; $i++) {
            $this->createOrderFixture(productId: $productId, customerName: "Customer {$i}");
        }

        // Test first page
        $this->client->request('GET', '/orders?page=1&limit=5');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(5, $responseData['data']);
        $this->assertSame(1, $responseData['meta']['page']);
        $this->assertSame(5, $responseData['meta']['limit']);
        $this->assertSame(15, $responseData['meta']['total']);
        $this->assertSame(3, $responseData['meta']['pages']);

        // Test second page
        $this->client->request('GET', '/orders?page=2&limit=5');
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(5, $responseData['data']);
        $this->assertSame(2, $responseData['meta']['page']);
    }

    public function testGetSingleOrderSuccess(): void
    {
        $productId = Uuid::fromString('019abba0-fffb-780c-9b4f-9c4934250a44');
        $orderId = $this->createOrderFixture(productId: $productId, customerName: 'Test Customer');

        $this->client->request('GET', '/orders/' . $orderId);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($orderId, $responseData['data']['orderId']);
        $this->assertSame('Test Customer', $responseData['data']['customerName']);
        $this->assertSame(2, $responseData['data']['quantityOrdered']);
        $this->assertSame('Processing', $responseData['data']['orderStatus']);
        $this->assertArrayHasKey('product', $responseData['data']);
        $this->assertSame([], $responseData['meta']);
    }

    public function testGetSingleOrderNotFound(): void
    {
        $nonExistentId = Uuid::v7();

        $this->client->request('GET', '/orders/' . $nonExistentId);

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Order not found', $responseData['meta']['error']);
    }

    public function testGetSingleOrderInvalidUuid(): void
    {
        $this->client->request('GET', '/orders/invalid-uuid');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Invalid order ID format', $responseData['meta']['error']);
    }

    public function testCreateOrderSuccess(): void
    {
        $productId = '019abba0-fffb-780c-9b4f-9c4934250a44'; // Known product with stock
        $orderData = [
            'data' => [
                'productId' => $productId,
                'customerName' => 'John Doe',
                'quantityOrdered' => 2
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('orderId', $responseData['data']);
        $this->assertSame($productId, $responseData['data']['product']['id']);
        $this->assertSame('John Doe', $responseData['data']['customerName']);
        $this->assertSame(2, $responseData['data']['quantityOrdered']);
        $this->assertSame('Processing', $responseData['data']['orderStatus']);
        $this->assertSame([], $responseData['meta']);

        // Order creation is verified through the API response
    }

    public function testCreateOrderWithCustomOrderId(): void
    {
        $customOrderId = Uuid::v7();
        $productId = '019abba0-fffb-780c-9b4f-9c4934250a44';
        $orderData = [
            'data' => [
                'orderId' => (string) $customOrderId,
                'productId' => $productId,
                'customerName' => 'Jane Smith',
                'quantityOrdered' => 1
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame((string) $customOrderId, $responseData['data']['orderId']);
    }

    public function testCreateOrderMissingCustomerName(): void
    {
        $orderData = [
            'data' => [
                'productId' => '019abba0-fffb-780c-9b4f-9c4934250a44',
                'quantityOrdered' => 2
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Customer name is required', $responseData['meta']['error']);
    }

    public function testCreateOrderInvalidQuantity(): void
    {
        $orderData = [
            'data' => [
                'productId' => '019abba0-fffb-780c-9b4f-9c4934250a44',
                'customerName' => 'John Doe',
                'quantityOrdered' => 0
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Quantity ordered must be greater than 0', $responseData['meta']['error']);
    }

    public function testCreateOrderProductNotFound(): void
    {
        $nonExistentProductId = Uuid::v7();
        $orderData = [
            'data' => [
                'productId' => (string) $nonExistentProductId,
                'customerName' => 'John Doe',
                'quantityOrdered' => 2
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Product not found', $responseData['meta']['error']);
    }

    public function testCreateOrderInvalidProductId(): void
    {
        $orderData = [
            'data' => [
                'productId' => 'invalid-uuid',
                'customerName' => 'John Doe',
                'quantityOrdered' => 2
            ]
        ];

        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($orderData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Invalid input data', $responseData['meta']['error']);
    }

    public function testCreateOrderInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid-json'
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNull($responseData['data']);
        $this->assertSame('Invalid input data', $responseData['meta']['error']);
    }
}
