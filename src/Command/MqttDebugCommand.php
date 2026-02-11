<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\MqttShellClient;
use NashGao\InteractiveShell\Transport\UnixSocketTransport;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interactive debug shell for monitoring real-time MQTT traffic.
 *
 * Connects to the DebugTapServer via Unix socket and streams MQTT messages
 * with interactive filtering and control commands.
 *
 * Usage:
 *   php bin/hyperf.php mqtt:debug
 *   php bin/hyperf.php mqtt:debug --filter="topic:sensors/#"
 *   php bin/hyperf.php mqtt:debug --socket=/tmp/my-mqtt-debug.sock
 */
#[Command]
class MqttDebugCommand extends HyperfCommand
{
    protected string $description = 'Interactive debug shell for monitoring real-time MQTT traffic';

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->setName('mqtt:debug');
        $this->setDescription($this->description);
        $this->addOption('socket', 's', InputOption::VALUE_OPTIONAL, 'Unix socket path (default: from config or /tmp/mqtt-debug.sock)');
        $this->addOption('filter', 'f', InputOption::VALUE_OPTIONAL, 'Initial filter expression (e.g., "topic:sensors/#")');
        $this->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Connection timeout in seconds', '30');
        $this->addOption('format', null, InputOption::VALUE_OPTIONAL, 'Output format (compact, table, vertical, json)', 'compact');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get socket path from option, config, or default
        $socketPath = $this->getSocketPath($input);
        $timeoutOption = $input->getOption('timeout');
        $timeout = is_numeric($timeoutOption) ? (float) $timeoutOption : 30.0;
        $filterOption = $input->getOption('filter');
        $initialFilter = is_string($filterOption) ? $filterOption : null;

        // Check if socket exists (server must be running)
        if (! file_exists($socketPath)) {
            $output->writeln('<error>Debug tap server not running or socket not found.</error>');
            $output->writeln('');
            $output->writeln('To enable the debug tap server:');
            $output->writeln('1. Set <info>MQTT_DEBUG_ENABLED=true</info> in your .env file');
            $output->writeln('2. Ensure your MQTT subscriber is running');
            $output->writeln('');
            $output->writeln("Socket path: {$socketPath}");
            return 1;
        }

        // Create transport
        $transport = new UnixSocketTransport($socketPath, $timeout);

        // Create MQTT shell client with MQTT-specific features
        $shell = new MqttShellClient(
            transport: $transport,
            prompt: 'mqtt> ',
            defaultAliases: $this->getDefaultAliases(),
            config: new ShellConfig(messageHistoryLimit: $this->getMessageHistoryLimit()),
        );

        // Set initial filter if provided
        if ($initialFilter !== null && $initialFilter !== '') {
            // Convert legacy format (field:pattern) to SQL-like format
            $expression = $this->convertLegacyFilter($initialFilter);
            $shell->setFilter($expression);
            $output->writeln('<info>Initial filter set: ' . $expression . '</info>');
        }

        // Run the interactive shell
        return $shell->run($input, $output);
    }

    private function getSocketPath(InputInterface $input): string
    {
        // Priority: CLI option > config > default
        $option = $input->getOption('socket');
        if (is_string($option) && $option !== '') {
            return $option;
        }

        /** @var ConfigInterface $config */
        $config = $this->container->get(ConfigInterface::class);
        $configPath = $config->get('mqtt.default.debug.socket_path', '/tmp/mqtt-debug.sock');
        return is_string($configPath) ? $configPath : '/tmp/mqtt-debug.sock';
    }

    /**
     * Get default aliases from config or use defaults.
     *
     * @return array<string, string>
     */
    private function getDefaultAliases(): array
    {
        /** @var ConfigInterface $config */
        $config = $this->container->get(ConfigInterface::class);
        $aliases = $config->get('mqtt.default.debug.shell.aliases', []);

        return is_array($aliases) ? $aliases : [];
    }

    /**
     * Get message history limit from config.
     */
    private function getMessageHistoryLimit(): int
    {
        /** @var ConfigInterface $config */
        $config = $this->container->get(ConfigInterface::class);
        $limit = $config->get('mqtt.default.debug.shell.message_buffer', 500);

        return is_int($limit) ? $limit : 500;
    }

    /**
     * Convert legacy filter format (field:pattern) to SQL-like format.
     *
     * Examples:
     * - "topic:sensors/#" → "topic like 'sensors/#'"
     * - "topic:sensors/# qos:1" → "topic like 'sensors/#' and qos = 1"
     */
    private function convertLegacyFilter(string $filter): string
    {
        // If it already looks like SQL-like syntax, return as-is
        if (str_contains($filter, ' like ') || str_contains($filter, ' = ')) {
            return $filter;
        }

        $parts = [];
        $tokens = preg_split('/\s+/', trim($filter), -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return $filter;
        }

        foreach ($tokens as $token) {
            if (str_contains($token, ':')) {
                [$field, $pattern] = explode(':', $token, 2);
                if ($field === 'qos') {
                    $parts[] = "qos = {$pattern}";
                } elseif ($field === 'grep' || $field === 'contains') {
                    $parts[] = "message like '%{$pattern}%'";
                } else {
                    $parts[] = "{$field} like '{$pattern}'";
                }
            }
        }

        return empty($parts) ? $filter : implode(' and ', $parts);
    }
}
