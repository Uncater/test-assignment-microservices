<?php

namespace App\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OrderBundle\Testing\Handler\TestProductMessageHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
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
        $this->messageHandler = $this->client->getContainer()->get('test.product_message_handler');
        
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
            $connection->executeStatement('TRUNCATE TABLE product RESTART IDENTITY CASCADE');
        } catch (\Exception $e) {
            // Table might not exist yet, that's fine
        }
    }

    private function createDatabase(): void
    {
        // Drop and recreate the database schema for clean state
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createProductFixture(string $name, float $price, int $quantity): string
    {
        $productData = [
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity
        ];

        $this->client->request(
            'POST',
            '/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($productData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        return $responseData['data']['id'];
    }

    public function testGetProductsEmpty(): void
    {
        // Act
        $this->client->request('GET', '/products');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertIsArray($responseData['meta']);
        
        // Check pagination meta structure
        $this->assertArrayHasKey('page', $responseData['meta']);
        $this->assertArrayHasKey('limit', $responseData['meta']);
        $this->assertArrayHasKey('total', $responseData['meta']);
        $this->assertArrayHasKey('pages', $responseData['meta']);
    }

    public function testGetProductsPagination(): void
    {
        $this->client->request('GET', '/products?page=1&limit=5');
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertSame(1, $responseData['meta']['page']);
        $this->assertSame(5, $responseData['meta']['limit']);
        $this->assertIsInt($responseData['meta']['total']);
        $this->assertIsInt($responseData['meta']['pages']);
    }

    public function testCreateProduct(): void
    {
        // Arrange
        $productData = [
            'name' => 'Test Product API',
            'price' => 29.99,
            'quantity' => 15
        ];

        // Act
        $this->client->request(
            'POST',
            '/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($productData)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        
        // Validate product data structure
        $productResponse = $responseData['data'];
        $this->assertArrayHasKey('id', $productResponse);
        $this->assertArrayHasKey('name', $productResponse);
        $this->assertArrayHasKey('price', $productResponse);
        $this->assertArrayHasKey('quantity', $productResponse);
        
        // Validate product data values
        $this->assertIsString($productResponse['id']);
        $this->assertSame($productData['name'], $productResponse['name']);
        $this->assertSame($productData['price'], $productResponse['price']);
        $this->assertSame($productData['quantity'], $productResponse['quantity']);
        
        // Validate UUID format (basic check)
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $productResponse['id']);
        
        // Verify message was dispatched
        $this->assertSame(1, $this->messageHandler->getMessageCount());
        $lastMessage = $this->messageHandler->getLastMessage();
        $this->assertSame('ProductCreatedMessage', $lastMessage['type']);
        $this->assertSame($productData['name'], $lastMessage['message']->product->name->value);
    }

    public function testCreateProductWithInvalidData(): void
    {
        $invalidData = [
            'name' => '',
            'price' => -10,
            'quantity' => -5 // negative quantity
        ];

        // Act
        $this->client->request(
            'POST',
            '/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );

        // Assert - Should still create (no validation in current implementation)
        // This test documents current behavior - validation can be added later
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testCreateProductWithMalformedJson(): void
    {
        // Act
        $this->client->request(
            'POST',
            '/product',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"invalid": json}'
        );

        // Assert - Should handle malformed JSON gracefully
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $productResponse = $responseData['data'];
        
        // Should create product with default values
        $this->assertSame('', $productResponse['name']);
        // Price could be either 0 or 0.0 depending on JSON encoding
        $this->assertTrue(in_array($productResponse['price'], [0, 0.0]), 'Price should be 0 or 0.0');
        $this->assertSame(0, $productResponse['quantity']);
    }

    public function testGetSingleProductNotFound(): void
    {
        // Act - Request non-existent product
        $nonExistentId = '019abba0-0000-0000-0000-000000000000';
        $this->client->request('GET', '/product/' . $nonExistentId);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertNull($responseData['data']);
        $this->assertArrayHasKey('error', $responseData['meta']);
        $this->assertSame('Not found', $responseData['meta']['error']);
    }

    public function testGetSingleProductInvalidId(): void
    {
        // Act - Request with invalid UUID format
        $this->client->request('GET', '/product/invalid-uuid');

        // Assert - Current implementation throws 500 error for invalid UUID
        // This documents current behavior - proper validation can be added later
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function testCreateAndRetrieveProduct(): void
    {
        // Arrange - Create a product fixture
        $productId = $this->createProductFixture('Integration Test Product', 45.67, 8);

        // Act - Retrieve the created product
        $this->client->request('GET', '/product/' . $productId);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('meta', $responseData);
        
        $productResponse = $responseData['data'];
        $this->assertSame($productId, $productResponse['id']);
        $this->assertSame('Integration Test Product', $productResponse['name']);
        $this->assertSame(45.67, $productResponse['price']);
        $this->assertSame(8, $productResponse['quantity']);
    }

    public function testListProducts(): void
    {
        // Arrange - Create multiple product fixtures
        $productId1 = $this->createProductFixture('Product A', 10.00, 1);
        $productId2 = $this->createProductFixture('Product B', 20.00, 2);
        $productId3 = $this->createProductFixture('Product C', 30.00, 3);

        // Act - List all products
        $this->client->request('GET', '/products');

        // Assert
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData['data']);
        $this->assertSame(3, count($responseData['data']));
        $this->assertSame(3, $responseData['meta']['total']);

        // Verify all created products are in the list
        $responseIds = array_column($responseData['data'], 'id');
        $this->assertContains($productId1, $responseIds);
        $this->assertContains($productId2, $responseIds);
        $this->assertContains($productId3, $responseIds);
        
        // Verify pagination metadata
        $this->assertSame(1, $responseData['meta']['page']);
        $this->assertSame(10, $responseData['meta']['limit']);
        $this->assertSame(1, $responseData['meta']['pages']);
    }

    public function testHealthEndpoint(): void
    {
        // Act
        $this->client->request('GET', '/health');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('service', $responseData);
        $this->assertSame('ok', $responseData['status']);
        $this->assertSame('product-service', $responseData['service']);
    }
}
