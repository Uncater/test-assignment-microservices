<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\Transport\AmqpExt\Connection as AmqpConnection;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'messenger' => [
            'transports' => [
                'product_events' => [
                    'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                    'options' => [
                        'exchange' => [
                            'name' => 'product.events',
                            'type' => 'topic',
                        ],
                    ],
                ],
            ],
            'routing' => [
                'OrderBundle\Messaging\Product\Event\ProductCreatedMessage' => [
                    'senders' => ['product_events'],
                    'options' => [
                        'routing_key' => 'product.created',
                    ],
                ],
                'OrderBundle\Messaging\Product\Event\ProductUpdatedMessage' => [
                    'senders' => ['product_events'],
                    'options' => [
                        'routing_key' => 'product.updated',
                    ],
                ],
            ],
        ],
    ]);
};
