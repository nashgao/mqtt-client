<?php

declare(strict_types=1);

use Nashgao\MQTT\Client;
use Simps\MQTT\Example\BaseExample;

use function Hyperf\Support\make;

require_once dirname(__DIR__, 2) . '/example/BaseExample.php';

class PublishExample extends BaseExample
{
    protected function main(): void
    {
        /** @var Client $client */
        $client = make(Client::class);
        $client->publish('topic/test', 'hi_mqtt', 2);
    }
}

(new PublishExample())->execute();
