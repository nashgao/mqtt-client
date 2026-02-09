#!/usr/bin/env php
<?php
/**
 * Example: Creating Custom Scenarios
 *
 * This example shows how to create your own message scenarios
 * for the MQTT Shell demo mode.
 *
 * Usage:
 *   php custom-scenario.php
 */

declare(strict_types=1);

// Load autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../src/MessageScenario.php';
require_once __DIR__ . '/../src/MessageGenerator.php';
require_once __DIR__ . '/../src/ScenarioPresets.php';
require_once __DIR__ . '/../src/DemoTransport.php';
require_once __DIR__ . '/../src/InjectHandler.php';

use Nashgao\MQTT\Examples\DemoShell\DemoTransport;
use Nashgao\MQTT\Examples\DemoShell\MessageScenario;
use Nashgao\MQTT\Shell\MqttShellClient;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();
$output->writeln('<info>MQTT Shell - Custom Scenario Example</info>');
$output->writeln('');

// Create custom scenarios for a fictional factory monitoring system
$factoryScenarios = [
    // Machine status updates
    new MessageScenario(
        name: 'machine_status',
        topicPattern: 'factory/{line}/machine/{id}/status',
        payloadType: 'json',
        payloadTemplate: [
            'state' => '{state}',
            'rpm' => '{rpm}',
            'temperature' => '{temp}',
            'timestamp' => '{timestamp}',
        ],
        qos: 1,
        direction: 'incoming',
        frequency: 2.0,
        variableRanges: [
            'line' => ['line-a', 'line-b', 'line-c'],
            'id' => ['m001', 'm002', 'm003', 'm004'],
            'state' => ['running', 'running', 'running', 'idle', 'maintenance'],
            'rpm' => [1000, 3500],
            'temp' => [45.0, 85.0],
        ],
    ),

    // Production counter updates
    new MessageScenario(
        name: 'production_count',
        topicPattern: 'factory/{line}/production',
        payloadType: 'json',
        payloadTemplate: [
            'units_produced' => '{count}',
            'defects' => '{defects}',
            'efficiency' => '{efficiency}',
        ],
        qos: 0,
        direction: 'incoming',
        frequency: 0.5,
        variableRanges: [
            'line' => ['line-a', 'line-b', 'line-c'],
            'count' => [100, 500],
            'defects' => [0, 10],
            'efficiency' => [85.0, 99.0],
        ],
    ),

    // Safety alerts
    new MessageScenario(
        name: 'safety_alert',
        topicPattern: 'factory/alerts/safety',
        payloadType: 'json',
        payloadTemplate: [
            'level' => '{level}',
            'zone' => '{zone}',
            'message' => 'Safety check required',
            'timestamp' => '{timestamp}',
        ],
        qos: 2,  // High QoS for safety alerts
        direction: 'incoming',
        frequency: 0.1,  // Rare events
        variableRanges: [
            'level' => ['warning', 'warning', 'critical'],
            'zone' => ['assembly', 'packaging', 'warehouse'],
        ],
    ),

    // Operator commands (outgoing)
    new MessageScenario(
        name: 'operator_command',
        topicPattern: 'factory/{line}/machine/{id}/command',
        payloadType: 'json',
        payloadTemplate: [
            'action' => '{action}',
            'operator_id' => 'op-{opid}',
        ],
        qos: 1,
        direction: 'outgoing',
        frequency: 0.3,
        variableRanges: [
            'line' => ['line-a', 'line-b'],
            'id' => ['m001', 'm002'],
            'action' => ['start', 'stop', 'pause', 'calibrate'],
            'opid' => [101, 102, 103],
        ],
    ),
];

// Create transport with custom scenarios
$transport = new DemoTransport(
    messageIntervalMs: 400.0,  // Slightly faster than default
    autoGenerate: true,
);

// Add our custom factory scenarios
foreach ($factoryScenarios as $scenario) {
    $transport->addScenario($scenario);
    $output->writeln("  Added scenario: <comment>{$scenario->name}</comment>");
}

$output->writeln('');
$output->writeln('<info>Custom scenarios loaded. Starting shell...</info>');
$output->writeln(str_repeat('-', 50));
$output->writeln('');

// Create and run the shell
$shell = new MqttShellClient(
    transport: $transport,
    prompt: 'factory> ',
    defaultAliases: [
        'i' => 'inject',
        's' => 'stats',
    ],
);

$input = new ArrayInput([]);
exit($shell->run($input, $output));
