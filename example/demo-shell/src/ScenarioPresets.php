<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Examples\DemoShell;

/**
 * Pre-built scenario collections for common MQTT patterns.
 */
final class ScenarioPresets
{
    /**
     * IoT sensor data - temperature, humidity, motion readings.
     *
     * @return array<MessageScenario>
     */
    public static function iotSensors(): array
    {
        return [
            new MessageScenario(
                name: 'temperature',
                topicPattern: 'sensors/{room}/temperature',
                payloadType: 'json',
                payloadTemplate: ['temp' => '{value}', 'unit' => 'C'],
                variableRanges: [
                    'room' => ['kitchen', 'bedroom', 'living_room', 'garage'],
                    'value' => [18.0, 28.0],
                ],
                frequency: 2.0,
            ),
            new MessageScenario(
                name: 'humidity',
                topicPattern: 'sensors/{room}/humidity',
                payloadType: 'json',
                payloadTemplate: ['humidity' => '{value}', 'unit' => '%'],
                variableRanges: [
                    'room' => ['kitchen', 'bedroom', 'living_room'],
                    'value' => [30.0, 70.0],
                ],
                frequency: 1.0,
            ),
            new MessageScenario(
                name: 'motion',
                topicPattern: 'sensors/{room}/motion',
                payloadType: 'json',
                payloadTemplate: ['detected' => '{bool}', 'timestamp' => '{timestamp}'],
                variableRanges: ['room' => ['hallway', 'entrance', 'garage']],
                frequency: 0.3,
            ),
        ];
    }

    /**
     * Smart home commands and responses.
     *
     * @return array<MessageScenario>
     */
    public static function smartHome(): array
    {
        return [
            new MessageScenario(
                name: 'light_command',
                topicPattern: 'home/{room}/lights/set',
                payloadType: 'json',
                payloadTemplate: ['state' => '{onoff}', 'brightness' => '{value}'],
                direction: 'outgoing',
                qos: 1,
                variableRanges: [
                    'room' => ['kitchen', 'bedroom', 'living_room'],
                    'onoff' => ['on', 'off'],
                    'value' => [0, 100],
                ],
                frequency: 0.5,
            ),
            new MessageScenario(
                name: 'light_status',
                topicPattern: 'home/{room}/lights/status',
                payloadType: 'json',
                payloadTemplate: ['state' => '{onoff}', 'brightness' => '{value}', 'online' => true],
                direction: 'incoming',
                qos: 1,
                variableRanges: [
                    'room' => ['kitchen', 'bedroom', 'living_room'],
                    'onoff' => ['on', 'off'],
                    'value' => [0, 100],
                ],
                frequency: 0.5,
            ),
            new MessageScenario(
                name: 'thermostat',
                topicPattern: 'home/hvac/set',
                payloadType: 'json',
                payloadTemplate: ['target_temp' => '{value}', 'mode' => '{mode}'],
                direction: 'outgoing',
                qos: 2,
                variableRanges: [
                    'value' => [18, 26],
                    'mode' => ['heat', 'cool', 'auto', 'off'],
                ],
                frequency: 0.2,
            ),
        ];
    }

    /**
     * Alert and error scenarios for testing rules.
     *
     * @return array<MessageScenario>
     */
    public static function alerts(): array
    {
        return [
            new MessageScenario(
                name: 'high_temp_alert',
                topicPattern: 'alerts/temperature',
                payloadType: 'json',
                payloadTemplate: [
                    'level' => 'critical',
                    'room' => '{room}',
                    'temp' => '{value}',
                    'threshold' => 30,
                ],
                qos: 2,
                variableRanges: [
                    'room' => ['server_room', 'kitchen'],
                    'value' => [31.0, 45.0],
                ],
                frequency: 0.1,
            ),
            new MessageScenario(
                name: 'low_battery',
                topicPattern: 'alerts/battery',
                payloadType: 'json',
                payloadTemplate: [
                    'level' => 'warning',
                    'device' => '{device}',
                    'battery' => '{value}',
                ],
                qos: 1,
                variableRanges: [
                    'device' => ['sensor-001', 'sensor-002', 'doorbell'],
                    'value' => [5, 20],
                ],
                frequency: 0.05,
            ),
            new MessageScenario(
                name: 'connection_error',
                topicPattern: '$SYS/errors',
                payloadType: 'json',
                payloadTemplate: [
                    'type' => 'connection',
                    'code' => '{code}',
                    'message' => 'Connection timeout',
                ],
                variableRanges: ['code' => [1, 2, 3, 4, 5]],
                frequency: 0.05,
            ),
        ];
    }

    /**
     * Binary payload scenarios.
     *
     * @return array<MessageScenario>
     */
    public static function binaryData(): array
    {
        return [
            new MessageScenario(
                name: 'firmware_chunk',
                topicPattern: 'devices/{device}/firmware',
                payloadType: 'binary',
                payloadTemplate: ['size' => 64],
                qos: 2,
                variableRanges: ['device' => ['esp32-001', 'esp32-002']],
                frequency: 0.1,
            ),
            new MessageScenario(
                name: 'sensor_raw',
                topicPattern: 'sensors/{device}/raw',
                payloadType: 'binary',
                payloadTemplate: ['size' => 16],
                qos: 0,
                variableRanges: ['device' => ['adc-001', 'adc-002']],
                frequency: 0.2,
            ),
        ];
    }

    /**
     * Device telemetry with structured data.
     *
     * @return array<MessageScenario>
     */
    public static function telemetry(): array
    {
        return [
            new MessageScenario(
                name: 'device_heartbeat',
                topicPattern: 'devices/{device}/heartbeat',
                payloadType: 'json',
                payloadTemplate: [
                    'uptime' => '{value}',
                    'free_memory' => '{memory}',
                    'timestamp' => '{timestamp}',
                ],
                qos: 0,
                variableRanges: [
                    'device' => ['gateway-01', 'gateway-02', 'edge-node-01'],
                    'value' => [1000, 86400],
                    'memory' => [1024, 8192],
                ],
                frequency: 0.5,
            ),
            new MessageScenario(
                name: 'metrics',
                topicPattern: 'metrics/{service}',
                payloadType: 'json',
                payloadTemplate: [
                    'cpu' => '{cpu}',
                    'memory' => '{memory}',
                    'requests' => '{requests}',
                ],
                qos: 0,
                variableRanges: [
                    'service' => ['api', 'worker', 'scheduler'],
                    'cpu' => [0.0, 100.0],
                    'memory' => [20.0, 90.0],
                    'requests' => [0, 1000],
                ],
                frequency: 1.0,
            ),
        ];
    }

    /**
     * Full demo preset with all scenarios.
     *
     * @return array<MessageScenario>
     */
    public static function fullDemo(): array
    {
        return array_merge(
            self::iotSensors(),
            self::smartHome(),
            self::alerts(),
            self::binaryData(),
            self::telemetry(),
        );
    }

    /**
     * Minimal demo with just a few scenarios for quick testing.
     *
     * @return array<MessageScenario>
     */
    public static function minimal(): array
    {
        return [
            new MessageScenario(
                name: 'temperature',
                topicPattern: 'sensors/room1/temperature',
                payloadType: 'json',
                payloadTemplate: ['temp' => '{value}', 'unit' => 'C'],
                variableRanges: ['value' => [20.0, 25.0]],
                frequency: 1.0,
            ),
            new MessageScenario(
                name: 'command',
                topicPattern: 'commands/device1',
                payloadType: 'json',
                payloadTemplate: ['action' => '{action}'],
                direction: 'outgoing',
                qos: 1,
                variableRanges: ['action' => ['start', 'stop', 'restart']],
                frequency: 0.3,
            ),
        ];
    }

    /**
     * Get all available preset names.
     *
     * @return array<string>
     */
    public static function getAvailablePresets(): array
    {
        return ['iot', 'smarthome', 'alerts', 'binary', 'telemetry', 'full', 'minimal'];
    }

    /**
     * Get scenarios by preset name.
     *
     * @return array<MessageScenario>
     */
    public static function getByName(string $name): array
    {
        return match ($name) {
            'iot' => self::iotSensors(),
            'smarthome' => self::smartHome(),
            'alerts' => self::alerts(),
            'binary' => self::binaryData(),
            'telemetry' => self::telemetry(),
            'full' => self::fullDemo(),
            'minimal' => self::minimal(),
            default => self::fullDemo(),
        };
    }
}
