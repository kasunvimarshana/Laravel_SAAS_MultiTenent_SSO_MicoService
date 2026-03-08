<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Message Broker Driver
    |--------------------------------------------------------------------------
    | Supported: "rabbitmq", "kafka", "sync"
    */
    'driver' => env('MESSAGE_BROKER_DRIVER', 'sync'),

    'connections' => [
        'rabbitmq' => [
            'host'     => env('RABBITMQ_HOST', 'localhost'),
            'port'     => (int) env('RABBITMQ_PORT', 5672),
            'username' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASS', 'guest'),
            'vhost'    => env('RABBITMQ_VHOST', '/'),
        ],

        'kafka' => [
            'brokers'  => env('KAFKA_BROKERS', 'localhost:9092'),
            'group_id' => env('KAFKA_GROUP_ID', 'saas-consumer'),
        ],

        'sync' => [],
    ],
];
