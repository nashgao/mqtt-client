<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell;

use NashGao\InteractiveShell\Command\AliasManager;
use NashGao\InteractiveShell\Parser\ShellParser;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for MqttShellClient alias resolution.
 *
 * SPECIFICATION TESTS: These tests verify that shell aliases
 * are properly expanded to their full command forms.
 *
 * @internal
 */
#[CoversClass(AliasManager::class)]
#[CoversClass(ShellParser::class)]
class MqttShellClientAliasTest extends AbstractTestCase
{
    /**
     * Default aliases as defined in MqttShellClient::getDefaultAliases().
     *
     * @return array<string, string>
     */
    private function getDefaultAliases(): array
    {
        return [
            // System
            'q' => 'exit',
            'quit' => 'exit',
            '?' => 'help',
            // Monitoring
            'f' => 'filter',
            'p' => 'pause',
            'r' => 'resume',
            's' => 'stats',
            'c' => 'filter clear',
            // History
            'h' => 'history',
            'l' => 'last',
            'll' => 'history --limit=50',
            // MQTT Operations
            'pub' => 'publish',
            'sub' => 'subscribe',
            'unsub' => 'unsubscribe',
            // Step-through mode
            'n' => 'next',
            // Visualization
            'viz' => 'visualize',
        ];
    }

    /**
     * SPECIFICATION: Single-letter alias 'p' should expand to 'pause'.
     */
    public function testPauseAliasExpandsCorrectly(): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types 'p'
        $parsed = $parser->parse('p');

        // Then: Command should be 'pause'
        $this->assertEquals('pause', $parsed->command);
    }

    /**
     * SPECIFICATION: Single-letter alias 'r' should expand to 'resume'.
     */
    public function testResumeAliasExpandsCorrectly(): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types 'r'
        $parsed = $parser->parse('r');

        // Then: Command should be 'resume'
        $this->assertEquals('resume', $parsed->command);
    }

    /**
     * SPECIFICATION: All default aliases should expand correctly.
     *
     * @param string $alias The alias to expand
     * @param string $expectedCommand The expected expanded command
     */
    #[DataProvider('defaultAliasProvider')]
    public function testAllDefaultAliasesExpandCorrectly(string $alias, string $expectedCommand): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types the alias
        $parsed = $parser->parse($alias);

        // Then: Command should match expected expansion
        $this->assertEquals($expectedCommand, $parsed->command);
    }

    /**
     * Provides default alias test cases.
     *
     * @return array<string, array{alias: string, expectedCommand: string}>
     */
    public static function defaultAliasProvider(): array
    {
        return [
            'q => exit' => ['alias' => 'q', 'expectedCommand' => 'exit'],
            'quit => exit' => ['alias' => 'quit', 'expectedCommand' => 'exit'],
            '? => help' => ['alias' => '?', 'expectedCommand' => 'help'],
            'f => filter' => ['alias' => 'f', 'expectedCommand' => 'filter'],
            'p => pause' => ['alias' => 'p', 'expectedCommand' => 'pause'],
            'r => resume' => ['alias' => 'r', 'expectedCommand' => 'resume'],
            's => stats' => ['alias' => 's', 'expectedCommand' => 'stats'],
            'h => history' => ['alias' => 'h', 'expectedCommand' => 'history'],
            'l => last' => ['alias' => 'l', 'expectedCommand' => 'last'],
            'pub => publish' => ['alias' => 'pub', 'expectedCommand' => 'publish'],
            'sub => subscribe' => ['alias' => 'sub', 'expectedCommand' => 'subscribe'],
            'unsub => unsubscribe' => ['alias' => 'unsub', 'expectedCommand' => 'unsubscribe'],
            'n => next' => ['alias' => 'n', 'expectedCommand' => 'next'],
            'viz => visualize' => ['alias' => 'viz', 'expectedCommand' => 'visualize'],
        ];
    }

    /**
     * SPECIFICATION: Alias expansion should work with arguments.
     */
    public function testAliasExpansionPreservesArguments(): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types alias with arguments
        $parsed = $parser->parse('f topic:sensors/#');

        // Then: Command should be expanded and arguments preserved
        $this->assertEquals('filter', $parsed->command);
        $this->assertContains('topic:sensors/#', $parsed->arguments);
    }

    /**
     * SPECIFICATION: Multi-word alias expansion should include all words.
     */
    public function testMultiWordAliasExpansion(): void
    {
        // Given: An alias manager with 'c' => 'filter clear'
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types 'c'
        $parsed = $parser->parse('c');

        // Then: Command should be 'filter' with 'clear' as argument
        $this->assertEquals('filter', $parsed->command);
        $this->assertContains('clear', $parsed->arguments);
    }

    /**
     * SPECIFICATION: Alias with options should expand correctly.
     */
    public function testAliasWithOptionsExpansion(): void
    {
        // Given: An alias manager with 'll' => 'history --limit=50'
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types 'll'
        $parsed = $parser->parse('ll');

        // Then: Command should be 'history' with --limit option
        $this->assertEquals('history', $parsed->command);
        $this->assertArrayHasKey('limit', $parsed->options);
        $this->assertEquals('50', $parsed->options['limit']);
    }

    /**
     * SPECIFICATION: Custom aliases should override defaults.
     */
    public function testCustomAliasesOverrideDefaults(): void
    {
        // Given: Default aliases merged with custom aliases
        $customAliases = [
            'r' => 'rule list', // Override default 'r' => 'resume'
        ];
        $mergedAliases = array_merge($this->getDefaultAliases(), $customAliases);
        $aliasManager = new AliasManager($mergedAliases);
        $parser = new ShellParser($aliasManager);

        // When: User types 'r' (which is now overridden)
        $parsed = $parser->parse('r');

        // Then: Command should be 'rule' (from 'rule list')
        $this->assertEquals('rule', $parsed->command);
        $this->assertContains('list', $parsed->arguments);
    }

    /**
     * SPECIFICATION: Unaffected aliases should still work after merge.
     */
    public function testUnaffectedAliasesStillWorkAfterMerge(): void
    {
        // Given: Default aliases merged with custom aliases
        $customAliases = [
            'r' => 'rule list', // Override 'r' only
            'i' => 'inject',    // New alias
        ];
        $mergedAliases = array_merge($this->getDefaultAliases(), $customAliases);
        $aliasManager = new AliasManager($mergedAliases);
        $parser = new ShellParser($aliasManager);

        // When: User types 'p' (not overridden)
        $parsed = $parser->parse('p');

        // Then: Command should still be 'pause'
        $this->assertEquals('pause', $parsed->command);
    }

    /**
     * SPECIFICATION: Non-aliased commands should pass through unchanged.
     */
    public function testNonAliasedCommandsPassThrough(): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types a non-aliased command
        $parsed = $parser->parse('unknowncommand arg1 arg2');

        // Then: Command should pass through unchanged
        $this->assertEquals('unknowncommand', $parsed->command);
        $this->assertEquals(['arg1', 'arg2'], $parsed->arguments);
    }

    /**
     * SPECIFICATION: Full command names should work even when alias exists.
     */
    public function testFullCommandNamesWorkEvenWithAlias(): void
    {
        // Given: An alias manager with default aliases
        $aliasManager = new AliasManager($this->getDefaultAliases());
        $parser = new ShellParser($aliasManager);

        // When: User types full command 'pause' (not alias 'p')
        $parsed = $parser->parse('pause');

        // Then: Command should remain 'pause'
        $this->assertEquals('pause', $parsed->command);
    }
}
