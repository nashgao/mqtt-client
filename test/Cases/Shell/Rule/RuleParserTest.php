<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Rule;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Rule\Rule;
use Nashgao\MQTT\Shell\Rule\RuleEngine;
use Nashgao\MQTT\Shell\Rule\RuleParser;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for RuleParser.
 *
 * Tests the parser's behavior through rule matching rather than internal structure.
 * These tests verify that parsed rules work correctly when processing messages.
 *
 * @internal
 */
#[CoversNothing]
class RuleParserTest extends AbstractTestCase
{
    private RuleParser $parser;

    private RuleEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RuleParser();
        $this->engine = new RuleEngine();
    }

    /**
     * SPECIFICATION: Parser should extract SELECT * correctly.
     */
    public function testParseSelectAllExtractsAllFields(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/temperature'");

        // Create rule and test with a message
        $rule = new Rule(
            name: 'test',
            sql: "SELECT * FROM 'sensors/temperature'",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: null,
            actions: [],
        );
        $this->engine->addRule($rule);

        $message = $this->createMessage('sensors/temperature', ['temp' => 25, 'humidity' => 60]);
        $matches = $this->engine->process($message);

        // SELECT * should include all fields in result
        $this->assertArrayHasKey('test', $matches);
        $data = $matches['test'];
        $this->assertArrayHasKey('topic', $data);
        $this->assertArrayHasKey('payload', $data);
    }

    /**
     * SPECIFICATION: Parser should handle specific field selection.
     */
    public function testParseSpecificFieldsExtractsOnlyThoseFields(): void
    {
        $parsed = $this->parser->parse("SELECT topic, payload.temp FROM 'sensors/#'");

        $rule = new Rule(
            name: 'test',
            sql: "SELECT topic, payload.temp FROM 'sensors/#'",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: null,
            actions: [],
        );
        $this->engine->addRule($rule);

        $message = $this->createMessage('sensors/room1', ['temp' => 25, 'humidity' => 60]);
        $matches = $this->engine->process($message);

        $this->assertArrayHasKey('test', $matches);
        $data = $matches['test'];
        $this->assertArrayHasKey('topic', $data);
        $this->assertArrayHasKey('payload.temp', $data);
        $this->assertEquals('sensors/room1', $data['topic']);
        $this->assertEquals(25, $data['payload.temp']);
    }

    /**
     * SPECIFICATION: Parser should handle quoted topic patterns.
     */
    public function testParseFromWithQuotedTopicPatterns(): void
    {
        // Single quotes
        $parsed1 = $this->parser->parse("SELECT * FROM 'sensors/temperature'");
        $this->assertEquals('sensors/temperature', $parsed1['from']);

        // Double quotes
        $parsed2 = $this->parser->parse('SELECT * FROM "sensors/humidity"');
        $this->assertEquals('sensors/humidity', $parsed2['from']);

        // With wildcards
        $parsed3 = $this->parser->parse("SELECT * FROM 'sensors/+/temperature'");
        $this->assertEquals('sensors/+/temperature', $parsed3['from']);

        $parsed4 = $this->parser->parse("SELECT * FROM 'sensors/#'");
        $this->assertEquals('sensors/#', $parsed4['from']);
    }

    /**
     * SPECIFICATION: WHERE with equals operator should match exact values.
     */
    public function testWhereEqualsOperatorMatchesExactValue(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.status = 'active'");

        $rule = new Rule(
            name: 'test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.status = 'active'",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // Should match
        $activeMsg = $this->createMessage('sensors/room1', ['status' => 'active']);
        $this->assertArrayHasKey('test', $this->engine->process($activeMsg));

        // Should not match
        $inactiveMsg = $this->createMessage('sensors/room1', ['status' => 'inactive']);
        $this->assertEmpty($this->engine->process($inactiveMsg));
    }

    /**
     * SPECIFICATION: WHERE with comparison operators should work with numbers.
     */
    public function testWhereComparisonOperatorsWorkWithNumbers(): void
    {
        // Greater than
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.temp > 25");
        $rule = new Rule(
            name: 'gt_test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.temp > 25",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // 30 > 25 = true
        $this->assertArrayHasKey('gt_test', $this->engine->process(
            $this->createMessage('sensors/temp', ['temp' => 30])
        ));

        // 20 > 25 = false
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/temp', ['temp' => 20])
        ));
    }

    /**
     * SPECIFICATION: WHERE with less than operator should work correctly.
     */
    public function testWhereLessThanOperatorWorksCorrectly(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.temp < 30");
        $rule = new Rule(
            name: 'lt_test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.temp < 30",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // 25 < 30 = true
        $this->assertArrayHasKey('lt_test', $this->engine->process(
            $this->createMessage('sensors/temp', ['temp' => 25])
        ));

        // 35 < 30 = false
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/temp', ['temp' => 35])
        ));
    }

    /**
     * SPECIFICATION: WHERE with AND operator requires both conditions.
     */
    public function testWhereAndOperatorRequiresBothConditions(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.temp > 20 AND payload.humidity < 50");

        $rule = new Rule(
            name: 'and_test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.temp > 20 AND payload.humidity < 50",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // Both conditions met
        $this->assertArrayHasKey('and_test', $this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 25, 'humidity' => 40])
        ));

        // First condition fails
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 15, 'humidity' => 40])
        ));

        // Second condition fails
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 25, 'humidity' => 60])
        ));
    }

    /**
     * SPECIFICATION: WHERE with OR operator requires at least one condition.
     */
    public function testWhereOrOperatorRequiresAtLeastOneCondition(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.temp > 30 OR payload.humidity > 80");

        $rule = new Rule(
            name: 'or_test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.temp > 30 OR payload.humidity > 80",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // First condition met
        $this->assertArrayHasKey('or_test', $this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 35, 'humidity' => 40])
        ));

        // Second condition met
        $this->assertArrayHasKey('or_test', $this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 20, 'humidity' => 85])
        ));

        // Neither condition met
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 20, 'humidity' => 40])
        ));
    }

    /**
     * SPECIFICATION: Parser should handle case-insensitive keywords.
     */
    public function testParserHandlesCaseInsensitiveKeywords(): void
    {
        $sql = "select * from 'sensors/temp' where payload.temp > 20";
        $parsed = $this->parser->parse($sql);

        $rule = new Rule(
            name: 'case_test',
            sql: $sql,
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        $this->assertArrayHasKey('case_test', $this->engine->process(
            $this->createMessage('sensors/temp', ['temp' => 25])
        ));
    }

    /**
     * SPECIFICATION: Parser should reject invalid SELECT clause.
     */
    public function testParserRejectsInvalidSelectClause(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid SELECT clause');

        $this->parser->parse("FROM 'sensors/temp'");
    }

    /**
     * SPECIFICATION: Parser should reject unquoted topic in FROM clause.
     */
    public function testParserRejectsUnquotedTopic(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid FROM clause');

        $this->parser->parse('SELECT * FROM sensors/temp');
    }

    /**
     * SPECIFICATION: Parser should reject invalid condition syntax.
     */
    public function testParserRejectsInvalidConditionSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid condition syntax');

        $this->parser->parse("SELECT * FROM 'sensors/temp' WHERE payload.temp invalid 25");
    }

    /**
     * SPECIFICATION: Parser should handle float values in conditions.
     */
    public function testParserHandlesFloatValues(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE payload.temp = 25.5");

        $rule = new Rule(
            name: 'float_test',
            sql: "SELECT * FROM 'sensors/#' WHERE payload.temp = 25.5",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // Exact float match
        $this->assertArrayHasKey('float_test', $this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 25.5])
        ));

        // Different value
        $this->assertEmpty($this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 25.0])
        ));
    }

    /**
     * SPECIFICATION: Parser should handle nested field paths.
     */
    public function testParserHandlesNestedFieldPaths(): void
    {
        $parsed = $this->parser->parse("SELECT payload.sensor.reading FROM 'data/#' WHERE payload.sensor.reading > 20");

        $rule = new Rule(
            name: 'nested_test',
            sql: "SELECT payload.sensor.reading FROM 'data/#' WHERE payload.sensor.reading > 20",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        // Nested structure that matches
        $this->assertArrayHasKey('nested_test', $this->engine->process(
            $this->createMessage('data/device1', ['sensor' => ['reading' => 25]])
        ));
    }

    /**
     * SPECIFICATION: Parser should handle parenthesized conditions.
     */
    public function testParserHandlesParenthesizedConditions(): void
    {
        $parsed = $this->parser->parse("SELECT * FROM 'sensors/#' WHERE (payload.temp > 20)");

        $rule = new Rule(
            name: 'paren_test',
            sql: "SELECT * FROM 'sensors/#' WHERE (payload.temp > 20)",
            selectFields: $parsed['select'],
            fromTopic: $parsed['from'],
            whereCondition: $parsed['where'],
            actions: [],
        );
        $this->engine->addRule($rule);

        $this->assertArrayHasKey('paren_test', $this->engine->process(
            $this->createMessage('sensors/room', ['temp' => 25])
        ));
    }

    /**
     * Helper to create a test message.
     */
    private function createMessage(string $topic, array $payload): Message
    {
        return new Message(
            type: 'mqtt.message',
            payload: [
                'topic' => $topic,
                'message' => $payload,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );
    }
}
