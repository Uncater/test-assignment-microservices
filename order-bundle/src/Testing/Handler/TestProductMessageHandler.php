<?php

namespace OrderBundle\Testing\Handler;

class TestProductMessageHandler
{
    private array $messages = [];

    public function __invoke(object $message): void
    {
        $this->messages[] = $message;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clearMessages(): void
    {
        $this->messages = [];
    }
}