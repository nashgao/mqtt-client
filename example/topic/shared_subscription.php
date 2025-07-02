<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Nashgao\MQTT\Client;

use function Hyperf\Support\make;

echo "MQTT Shared Subscription Example\n";
echo "================================\n\n";

\Swoole\Coroutine\run(
    function () {
        echo "Creating client and setting up shared subscription...\n";

        /** @var Client $client */
        $client = make(Client::class);

        // Shared subscription example - messages distributed among group members
        $sharedTopics = [
            '$share/worker-group/sensors/data' => [
                'qos' => 1,
            ],
            '$share/processing-team/logs/analysis' => [
                'qos' => 2,
            ],
        ];

        echo "Subscribing to shared topics:\n";
        foreach (array_keys($sharedTopics) as $topic) {
            echo "  - {$topic}\n";
        }

        $client->subscribe($sharedTopics);

        echo "\n✅ Shared subscription setup complete!\n";
        echo "💡 In shared subscriptions, clients join a group and messages are\n";
        echo "   distributed among all group members (round-robin or load balancing).\n\n";

        echo "Example use cases:\n";
        echo "  - Distributed data processing\n";
        echo "  - Scalable microservices\n";
        echo "  - Worker pool implementations\n";
        echo "  - Fault-tolerant message processing\n\n";

        echo "Format: \$share/{GroupName}/{TopicFilter}\n";
        echo "  - worker-group: Group for sensor data processing\n";
        echo "  - processing-team: Group for log analysis\n";
    }
);
