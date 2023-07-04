<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

/**
 * after sending publish event, dispatch this event to return results of if publish is successful or not.
 */
readonly class OnPublishEvent
{
    public function __construct(
        public string $poolName,
        public string $topic,
        public string|array|null $message,
        public int $qos,
        public mixed $result
    ) {
    }
}
