<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell;

use NashGao\InteractiveShell\Transport\StreamingTransportInterface;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Filter\FilterExpression;
use Nashgao\MQTT\Shell\Filter\FilterPresetManager;
use Nashgao\MQTT\Shell\Formatter\MqttMessageFormatter;
use Nashgao\MQTT\Shell\History\MessageHistory;
use Nashgao\MQTT\Shell\Stats\StatsCollector;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MQTT-specific context passed to command handlers.
 *
 * Contains all MQTT-specific components (filter, formatter, history, stats, config).
 */
final readonly class HandlerContext
{
    public function __construct(
        public OutputInterface $output,
        public StreamingTransportInterface $transport,
        public FilterExpression $filter,
        public FilterPresetManager $presetManager,
        public MqttMessageFormatter $formatter,
        public MessageHistory $messageHistory,
        public StatsCollector $stats,
        public ShellConfig $config,
        public bool $verticalFormat = false,
        public bool $paused = false,
    ) {
    }
}
