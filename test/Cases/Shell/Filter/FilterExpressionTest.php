<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Filter;

use Nashgao\MQTT\Shell\Filter\FilterExpression;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for FilterExpression.
 *
 * Tests filter expression behavior including cloning and clause management.
 *
 * @internal
 */
#[CoversNothing]
class FilterExpressionTest extends AbstractTestCase
{
    private FilterExpression $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new FilterExpression();
    }

    /**
     * SPECIFICATION: clone() should create an independent copy.
     */
    public function testCloneCreatesIndependentCopy(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $clone = $this->filter->clone();

        // Verify clone is a different instance
        $this->assertNotSame($this->filter, $clone);

        // Verify clone has same expression
        $this->assertEquals($this->filter->toSql(), $clone->toSql());
    }

    /**
     * SPECIFICATION: clone() should preserve all clauses.
     */
    public function testClonePreservesAllClauses(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addAnd("qos = 1");
        $this->filter->addOr("topic like 'devices/#'");
        $this->filter->addNot("topic like 'debug/#'");

        $clone = $this->filter->clone();

        // Both should have the same complete expression
        $this->assertEquals($this->filter->toSql(), $clone->toSql());
    }

    /**
     * SPECIFICATION: Modifying clone should not affect original.
     */
    public function testClonedFilterCanBeModifiedIndependently(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $originalExpression = $this->filter->toSql();

        $clone = $this->filter->clone();
        $clone->addAnd("qos = 2");

        // Original should be unchanged
        $this->assertEquals($originalExpression, $this->filter->toSql());

        // Clone should have the additional clause
        $this->assertNotEquals($originalExpression, $clone->toSql());
        $this->assertStringContainsString('qos = 2', $clone->toSql());
    }

    /**
     * SPECIFICATION: Cloning empty filter should work.
     */
    public function testCloneEmptyFilter(): void
    {
        $clone = $this->filter->clone();

        $this->assertNotSame($this->filter, $clone);
        $this->assertEquals('', $clone->toSql());
    }

    /**
     * SPECIFICATION: where() should set base filter expression.
     */
    public function testWhereSetBaseExpression(): void
    {
        $this->filter->where("topic like 'test/#'");

        $this->assertStringContainsString("topic like 'test/#'", $this->filter->toSql());
    }

    /**
     * SPECIFICATION: where() should replace existing filter.
     */
    public function testWhereReplacesExistingFilter(): void
    {
        $this->filter->where("topic like 'old/#'");
        $this->filter->where("topic like 'new/#'");

        $expression = $this->filter->toSql();
        $this->assertStringNotContainsString('old', $expression);
        $this->assertStringContainsString('new', $expression);
    }

    /**
     * SPECIFICATION: addAnd() should add AND clause.
     */
    public function testAddAndAppendsClause(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addAnd("qos = 1");

        $expression = $this->filter->toSql();
        $this->assertStringContainsString('AND', $expression);
        $this->assertStringContainsString('qos = 1', $expression);
    }

    /**
     * SPECIFICATION: addOr() should add OR clause.
     */
    public function testAddOrAppendsClause(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addOr("topic like 'devices/#'");

        $expression = $this->filter->toSql();
        $this->assertStringContainsString('OR', $expression);
        $this->assertStringContainsString('devices', $expression);
    }

    /**
     * SPECIFICATION: addNot() should add AND NOT clause.
     */
    public function testAddNotAppendsClause(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addNot("topic like 'debug/#'");

        $expression = $this->filter->toSql();
        $this->assertStringContainsString('AND NOT', $expression);
        $this->assertStringContainsString('debug', $expression);
    }

    /**
     * SPECIFICATION: clear() should remove all clauses.
     */
    public function testClearRemovesAllClauses(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addAnd("qos = 1");
        $this->filter->clear();

        $this->assertEquals('', $this->filter->toSql());
    }

    /**
     * SPECIFICATION: remove() should remove specific clause.
     */
    public function testRemoveSpecificClause(): void
    {
        $this->filter->where("topic like 'sensors/#'");
        $this->filter->addAnd("qos = 1");
        $this->filter->remove("qos = 1");

        $expression = $this->filter->toSql();
        $this->assertStringNotContainsString('qos = 1', $expression);
        $this->assertStringContainsString('sensors', $expression);
    }
}
