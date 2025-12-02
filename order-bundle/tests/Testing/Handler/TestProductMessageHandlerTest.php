<?php

namespace OrderBundle\Tests\Testing\Handler;

use OrderBundle\Testing\Handler\TestProductMessageHandler;
use OrderBundle\Messaging\Product\Event\ProductCreatedMessage;
use OrderBundle\Dto\ProductDto;
use OrderBundle\Product\ValueObject\ProductName;
use OrderBundle\Product\ValueObject\ProductPrice;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TestProductMessageHandlerTest extends TestCase
{
    private TestProductMessageHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TestProductMessageHandler();
    }

    public function testHandleMessage(): void
    {
        $productDto = new ProductDto(
            Uuid::v7(),
            new ProductName('Test Product'),
            new ProductPrice(1999),
            new ProductQuantity(10)
        );
        
        $message = new ProductCreatedMessage($productDto);
        
        $this->handler->__invoke($message);
        
        $messages = $this->handler->getMessages();
        $this->assertCount(1, $messages);
        $this->assertSame($message, $messages[0]);
    }

    public function testGetMessagesReturnsEmptyArrayInitially(): void
    {
        $messages = $this->handler->getMessages();
        
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testClearMessages(): void
    {
        $productDto = new ProductDto(
            Uuid::v7(),
            new ProductName('Test Product'),
            new ProductPrice(1999),
            new ProductQuantity(10)
        );
        
        $message = new ProductCreatedMessage($productDto);
        $this->handler->__invoke($message);
        
        $this->assertCount(1, $this->handler->getMessages());
        
        $this->handler->clearMessages();
        
        $this->assertEmpty($this->handler->getMessages());
    }

    public function testMultipleMessages(): void
    {
        $productDto1 = new ProductDto(
            Uuid::v7(),
            new ProductName('Product 1'),
            new ProductPrice(1999),
            new ProductQuantity(10)
        );
        
        $productDto2 = new ProductDto(
            Uuid::v7(),
            new ProductName('Product 2'),
            new ProductPrice(2999),
            new ProductQuantity(5)
        );
        
        $message1 = new ProductCreatedMessage($productDto1);
        $message2 = new ProductCreatedMessage($productDto2);
        
        $this->handler->__invoke($message1);
        $this->handler->__invoke($message2);
        
        $messages = $this->handler->getMessages();
        $this->assertCount(2, $messages);
        $this->assertSame($message1, $messages[0]);
        $this->assertSame($message2, $messages[1]);
    }
}