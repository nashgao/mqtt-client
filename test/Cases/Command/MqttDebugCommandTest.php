<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Command;

use Nashgao\MQTT\Command\MqttDebugCommand;
use Nashgao\MQTT\ConfigProvider;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface;

/**
 * Smoke tests for MqttDebugCommand.
 *
 * These tests verify basic command registration and configuration,
 * catching issues like:
 * - Missing ConfigProvider registration
 * - Duplicate option definitions
 * - Invalid command configuration
 *
 * @internal
 */
#[CoversClass(MqttDebugCommand::class)]
class MqttDebugCommandTest extends AbstractTestCase
{
    /**
     * Test that the command can be instantiated without errors.
     *
     * This catches duplicate option definitions (signature + configure conflict).
     */
    public function testCommandCanBeInstantiated(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new MqttDebugCommand($container);

        $this->assertInstanceOf(MqttDebugCommand::class, $command);
    }

    /**
     * Test that the command has the correct name.
     */
    public function testCommandHasCorrectName(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new MqttDebugCommand($container);

        $this->assertSame('mqtt:debug', $command->getName());
    }

    /**
     * Test that the command has the expected options.
     */
    public function testCommandHasExpectedOptions(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new MqttDebugCommand($container);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('socket'), 'Missing --socket option');
        $this->assertTrue($definition->hasOption('filter'), 'Missing --filter option');
        $this->assertTrue($definition->hasOption('timeout'), 'Missing --timeout option');
        $this->assertTrue($definition->hasOption('format'), 'Missing --format option');
    }

    /**
     * Test that options have correct shortcuts.
     */
    public function testOptionsHaveCorrectShortcuts(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $command = new MqttDebugCommand($container);
        $definition = $command->getDefinition();

        $this->assertSame('s', $definition->getOption('socket')->getShortcut());
        $this->assertSame('f', $definition->getOption('filter')->getShortcut());
        $this->assertSame('t', $definition->getOption('timeout')->getShortcut());
    }

    /**
     * Test that MqttDebugCommand is registered in ConfigProvider.
     *
     * This catches the missing 'commands' key issue.
     */
    public function testCommandIsRegisteredInConfigProvider(): void
    {
        $configProvider = new ConfigProvider();
        $config = $configProvider();

        $this->assertArrayHasKey('commands', $config, 'ConfigProvider missing commands key');
        $this->assertContains(
            MqttDebugCommand::class,
            $config['commands'],
            'MqttDebugCommand not registered in ConfigProvider'
        );
    }
}
