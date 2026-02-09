#!/usr/bin/env php
<?php
/**
 * MQTT Shell Demo Mode - Standalone Script
 *
 * This example demonstrates how to run the MQTT shell with
 * simulated messages (no real broker required).
 *
 * Usage:
 *   php run-demo.php                    # Full demo
 *   php run-demo.php --scenario=iot     # IoT sensors only
 *   php run-demo.php --speed=fast       # Fast message generation
 *   php run-demo.php --interactive      # Manual message injection
 */

declare(strict_types=1);

// Load autoloader
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',  // When in example/demo-shell/
    __DIR__ . '/../../../autoload.php',       // When installed via composer
];

$autoloaded = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    fwrite(STDERR, "Error: Could not find Composer autoloader.\n");
    fwrite(STDERR, "Please run 'composer install' in the project root.\n");
    exit(1);
}

// Load example classes
require_once __DIR__ . '/src/MessageScenario.php';
require_once __DIR__ . '/src/MessageGenerator.php';
require_once __DIR__ . '/src/ScenarioPresets.php';
require_once __DIR__ . '/src/DemoTransport.php';
require_once __DIR__ . '/src/InjectHandler.php';

use Nashgao\MQTT\Examples\DemoShell\DemoTransport;
use Nashgao\MQTT\Examples\DemoShell\InjectHandler;
use Nashgao\MQTT\Examples\DemoShell\ScenarioPresets;
use Nashgao\MQTT\Shell\MqttShellClient;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Parse command line options
$options = getopt('s:if:', ['scenario:', 'speed:', 'interactive', 'filter:', 'help']);

// Show help
if (isset($options['help'])) {
    echo <<<'HELP'
MQTT Shell Demo Mode - Standalone Script

Usage:
  php run-demo.php [options]

Options:
  -s, --scenario=NAME    Scenario preset to load (default: full)
                         Available: iot, smarthome, alerts, binary, telemetry, full, minimal
  --speed=SPEED          Message speed: slow (2s), normal (500ms), fast (100ms)
  -i, --interactive      Interactive mode - manually inject messages
  -f, --filter=EXPR      Initial filter expression (e.g., "topic:sensors/#")
  --help                 Show this help message

Examples:
  php run-demo.php                         # Full demo with all scenarios
  php run-demo.php --scenario=iot          # Only IoT sensor data
  php run-demo.php --speed=fast            # Fast message generation
  php run-demo.php -i                      # Interactive mode (inject manually)
  php run-demo.php -f "topic:alerts/#"     # Filter to alerts only

Available Scenarios:
  iot        - Temperature, humidity, motion sensors
  smarthome  - Light controls, thermostat commands
  alerts     - Error and warning messages
  binary     - Binary payload examples
  telemetry  - Device heartbeats and metrics
  full       - All scenarios (default)
  minimal    - Just temperature and commands

HELP;
    exit(0);
}

$scenario = $options['scenario'] ?? $options['s'] ?? 'full';
$speed = $options['speed'] ?? 'normal';
$interactive = isset($options['interactive']) || isset($options['i']);
$filter = $options['filter'] ?? $options['f'] ?? null;

// Configure message interval based on speed
$interval = match($speed) {
    'slow' => 2000.0,
    'fast' => 100.0,
    default => 500.0,
};

// Create output
$output = new ConsoleOutput();

// Show banner
$output->writeln('<info>MQTT Shell Demo Mode (Example)</info>');
$output->writeln('<comment>No broker required - using simulated messages</comment>');
$output->writeln('');

// Create demo transport
$transport = new DemoTransport(
    messageIntervalMs: $interval,
    autoGenerate: !$interactive,
);

// Load scenarios
$scenarios = ScenarioPresets::getByName((string) $scenario);
foreach ($scenarios as $s) {
    $transport->addScenario($s);
}

// Show loaded scenarios
$output->writeln('<info>Loaded scenarios:</info>');
foreach ($scenarios as $s) {
    $direction = $s->direction === 'outgoing' ? 'OUT' : 'IN';
    $output->writeln("  [{$direction}] {$s->name} ({$s->topicPattern})");
}
$output->writeln('');

// Show speed info
$speedDisplay = match($speed) {
    'slow' => 'slow (2s interval)',
    'fast' => 'fast (100ms interval)',
    default => 'normal (500ms interval)',
};
$output->writeln("<comment>Speed: {$speedDisplay}</comment>");

if ($interactive) {
    $output->writeln('<comment>Interactive mode: Use "inject <topic> <payload>" to add messages</comment>');
    $output->writeln('<comment>Example: inject sensors/temp {"temp": 25.5}</comment>');
}

$output->writeln('');
$output->writeln('<info>Type "help" for available commands</info>');
$output->writeln(str_repeat('-', 50));
$output->writeln('');

// Create shell with demo transport
$shell = new MqttShellClient(
    transport: $transport,
    prompt: 'mqtt-demo> ',
    defaultAliases: [
        'i' => 'inject',
        's' => 'stats',
        't' => 'tree',
        'f' => 'flow',
        'r' => 'rule list',
    ],
    messageHistoryLimit: 500,
);

// Set initial filter if provided
if ($filter !== null && $filter !== '') {
    // Convert legacy format (field:pattern) to SQL-like format
    $filterExpression = (string) $filter;
    if (str_contains($filterExpression, ':') && !str_contains($filterExpression, ' like ')) {
        $parts = [];
        foreach (preg_split('/\s+/', $filterExpression, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (str_contains($token, ':')) {
                [$field, $pattern] = explode(':', $token, 2);
                if ($field === 'qos') {
                    $parts[] = "qos = {$pattern}";
                } else {
                    $parts[] = "{$field} like '{$pattern}'";
                }
            }
        }
        if (!empty($parts)) {
            $filterExpression = implode(' and ', $parts);
        }
    }
    $shell->setFilter($filterExpression);
    $output->writeln('<info>Initial filter set: ' . $filterExpression . '</info>');
}

// Run the shell
$input = new ArrayInput([]);
exit($shell->run($input, $output));
