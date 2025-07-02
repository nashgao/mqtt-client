<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Nashgao\MQTT\Client;

use function Hyperf\Support\make;

echo "MQTT Queue Subscription Example\n";
echo "===============================\n\n";

\Swoole\Coroutine\run(
    function () {
        echo "Creating client and setting up queue subscription...\n";

        /** @var Client $client */
        $client = make(Client::class);

        // Queue subscription example - multiple subscribers compete for messages
        $queueTopics = [
            '$queue/processing/tasks' => [
                'qos' => 1,
            ],
            '$queue/data/processing' => [
                'qos' => 2,
            ],
        ];

        echo "Subscribing to queue topics:\n";
        foreach (array_keys($queueTopics) as $topic) {
            echo "  - {$topic}\n";
        }

        $client->subscribe($queueTopics);

        echo "\n✅ Queue subscription setup complete!\n";
        echo "💡 In queue subscriptions, multiple clients can subscribe to the same topic,\n";
        echo "   but each message is delivered to only ONE subscriber (load balancing).\n\n";

        echo "Example use cases:\n";
        echo "  - Task processing queues\n";
        echo "  - Load distribution across workers\n";
        echo "  - Message buffering systems\n";
    }
);
