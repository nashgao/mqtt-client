<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Command;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Framework\ApplicationFactory;
use Hyperf\Testing\TestCase;
use Nashgao\MQTT\Command\MqttDebugCommand;
use Nashgao\MQTT\ConfigProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Application;

/**
 * Integration tests that verify commands are properly registered and discoverable.
 *
 * These tests boot the real Hyperf container and verify:
 * - Commands are registered in ConfigProvider
 * - Commands can be resolved by the container
 * - Commands are added to the Symfony Application
 *
 * @internal
 */
#[CoversClass(MqttDebugCommand::class)]
#[CoversClass(ConfigProvider::class)]
class CommandRegistrationIntegrationTest extends TestCase
{
    /**
     * Get all command classes that should be registered.
     *
     * This is the source of truth - add new commands here.
     *
     * @return array<class-string>
     */
    public static function getExpectedCommands(): array
    {
        return [
            MqttDebugCommand::class,
        ];
    }

    /**
     * Test that all expected commands are registered in ConfigProvider.
     */
    public function testAllCommandsRegisteredInConfigProvider(): void
    {
        $configProvider = new ConfigProvider();
        $config = $configProvider();

        $this->assertArrayHasKey('commands', $config, 'ConfigProvider missing commands key');

        foreach (self::getExpectedCommands() as $commandClass) {
            $this->assertContains(
                $commandClass,
                $config['commands'],
                "Command {$commandClass} not registered in ConfigProvider"
            );
        }
    }

    /**
     * Test that all expected commands can be resolved from the container.
     *
     * This catches constructor issues, missing dependencies, and option conflicts.
     */
    public function testAllCommandsResolvableFromContainer(): void
    {
        foreach (self::getExpectedCommands() as $commandClass) {
            $command = $this->getContainer()->get($commandClass);

            $this->assertInstanceOf(
                $commandClass,
                $command,
                "Failed to resolve {$commandClass} from container"
            );
        }
    }

    /**
     * Test that all expected commands have valid names.
     */
    public function testAllCommandsHaveValidNames(): void
    {
        $expectedNames = [
            MqttDebugCommand::class => 'mqtt:debug',
        ];

        foreach ($expectedNames as $commandClass => $expectedName) {
            $command = $this->getContainer()->get($commandClass);

            $this->assertSame(
                $expectedName,
                $command->getName(),
                "Command {$commandClass} has wrong name"
            );
        }
    }

    /**
     * Test that commands are discoverable via Hyperf's ApplicationFactory.
     *
     * This is the ultimate integration test - it simulates what happens
     * when you run `php bin/hyperf.php`.
     */
    public function testCommandsDiscoverableViaApplicationFactory(): void
    {
        // Register the ConfigProvider commands in config
        $config = $this->getContainer()->get(ConfigInterface::class);

        // Get commands from ConfigProvider
        $configProvider = new ConfigProvider();
        $providerConfig = $configProvider();

        // Set commands in config (simulating what Hyperf does during boot)
        $config->set('commands', $providerConfig['commands'] ?? []);

        // Create the application via factory
        $factory = new ApplicationFactory();
        $application = $factory($this->getContainer());

        $this->assertInstanceOf(Application::class, $application);

        // Verify each expected command is in the application
        foreach (self::getExpectedCommands() as $commandClass) {
            $command = $this->getContainer()->get($commandClass);
            $commandName = $command->getName();

            $this->assertTrue(
                $application->has($commandName),
                "Command '{$commandName}' ({$commandClass}) not found in Application"
            );
        }
    }
}
