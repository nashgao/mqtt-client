<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Stats;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Stats\StatsCollector;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for StatsCollector.
 *
 * Tests statistics collection behavior through public APIs and display output.
 * Focuses on user-visible behavior rather than internal data structures.
 *
 * @internal
 */
#[CoversNothing]
class StatsCollectorTest extends AbstractTestCase
{
    private StatsCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new StatsCollector();
    }

    /**
     * SPECIFICATION: New collector should start with zero counts.
     */
    public function testNewCollectorStartsWithZeroCounts(): void
    {
        $this->assertEquals(0, $this->collector->getTotalMessages());
        $this->assertEquals(0, $this->collector->getIncomingMessages());
        $this->assertEquals(0, $this->collector->getOutgoingMessages());
        $this->assertEquals(0, $this->collector->getErrorCount());
    }

    /**
     * SPECIFICATION: Incoming messages should be counted separately.
     */
    public function testIncomingMessagesAreCountedSeparately(): void
    {
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'incoming'));
        $this->collector->record($this->createPublishMessage('test/topic2', 'payload', 'incoming'));

        $this->assertEquals(2, $this->collector->getTotalMessages());
        $this->assertEquals(2, $this->collector->getIncomingMessages());
        $this->assertEquals(0, $this->collector->getOutgoingMessages());
    }

    /**
     * SPECIFICATION: Outgoing messages should be counted separately.
     */
    public function testOutgoingMessagesAreCountedSeparately(): void
    {
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'outgoing'));

        $this->assertEquals(1, $this->collector->getTotalMessages());
        $this->assertEquals(0, $this->collector->getIncomingMessages());
        $this->assertEquals(1, $this->collector->getOutgoingMessages());
    }

    /**
     * SPECIFICATION: Direction aliases should work (in/out shorthand).
     */
    public function testDirectionAliasesWork(): void
    {
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'in'));
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'out'));

        $this->assertEquals(1, $this->collector->getIncomingMessages());
        $this->assertEquals(1, $this->collector->getOutgoingMessages());
    }

    /**
     * SPECIFICATION: Error messages should be tracked.
     */
    public function testErrorMessagesAreTracked(): void
    {
        $errorMsg = new Message(
            type: 'error',
            payload: 'Connection failed',
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );

        $this->collector->record($errorMsg);
        $this->collector->record($errorMsg);

        $this->assertEquals(2, $this->collector->getErrorCount());
    }

    /**
     * SPECIFICATION: QoS distribution should reflect message quality levels.
     */
    public function testQosDistributionReflectsMessageLevels(): void
    {
        // One QoS 0, two QoS 1, three QoS 2
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 0));
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 1));
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 1));
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 2));
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 2));
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming', 2));

        $distribution = $this->collector->getQosDistribution();
        $this->assertEquals(1, $distribution[0]);
        $this->assertEquals(2, $distribution[1]);
        $this->assertEquals(3, $distribution[2]);
    }

    /**
     * SPECIFICATION: Top topics should rank by message count.
     */
    public function testTopTopicsRankedByMessageCount(): void
    {
        // Different frequencies per topic
        for ($i = 0; $i < 10; $i++) {
            $this->collector->record($this->createPublishMessage('sensors/temperature', 'payload', 'incoming'));
        }
        for ($i = 0; $i < 5; $i++) {
            $this->collector->record($this->createPublishMessage('sensors/humidity', 'payload', 'incoming'));
        }
        for ($i = 0; $i < 3; $i++) {
            $this->collector->record($this->createPublishMessage('sensors/pressure', 'payload', 'incoming'));
        }

        $topTopics = $this->collector->getTopTopics(3);
        $topics = array_keys($topTopics);

        // Should be ordered by count (descending)
        $this->assertEquals('sensors/temperature', $topics[0]);
        $this->assertEquals('sensors/humidity', $topics[1]);
        $this->assertEquals('sensors/pressure', $topics[2]);

        // Should have correct counts
        $this->assertEquals(10, $topTopics['sensors/temperature']);
        $this->assertEquals(5, $topTopics['sensors/humidity']);
        $this->assertEquals(3, $topTopics['sensors/pressure']);
    }

    /**
     * SPECIFICATION: Latency metrics should track min/max/average.
     */
    public function testLatencyMetricsTrackMinMaxAverage(): void
    {
        $this->collector->recordLatency(10.0);
        $this->collector->recordLatency(20.0);
        $this->collector->recordLatency(30.0);
        $this->collector->recordLatency(40.0);
        $this->collector->recordLatency(50.0);

        // Min should be lowest
        $this->assertEquals(10.0, $this->collector->getMinLatency());

        // Max should be highest
        $this->assertEquals(50.0, $this->collector->getMaxLatency());

        // Average should be (10+20+30+40+50)/5 = 30
        $this->assertEquals(30.0, $this->collector->getAverageLatency());
    }

    /**
     * SPECIFICATION: Latency stats should be in display output.
     */
    public function testLatencyStatsAppearInDisplayOutput(): void
    {
        $this->collector->recordLatency(15.5);
        $this->collector->recordLatency(25.5);

        $display = $this->collector->formatDisplay();

        $this->assertStringContainsString('Latency Statistics:', $display);
        $this->assertStringContainsString('Min:', $display);
        $this->assertStringContainsString('Max:', $display);
        $this->assertStringContainsString('Average:', $display);
        $this->assertStringContainsString('P50', $display);
        $this->assertStringContainsString('P95', $display);
        $this->assertStringContainsString('P99', $display);
    }

    /**
     * SPECIFICATION: Summary should include all key statistics.
     */
    public function testSummaryIncludesAllKeyStatistics(): void
    {
        // Record some data
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'incoming'));
        $this->collector->record($this->createPublishMessage('test/topic', 'payload', 'outgoing'));
        $this->collector->recordLatency(25.0);

        $summary = $this->collector->getSummary();

        // Check all expected keys exist
        $this->assertArrayHasKey('total_messages', $summary);
        $this->assertArrayHasKey('rate', $summary);
        $this->assertArrayHasKey('incoming', $summary);
        $this->assertArrayHasKey('outgoing', $summary);
        $this->assertArrayHasKey('qos_distribution', $summary);
        $this->assertArrayHasKey('top_topics', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('subscribes', $summary);
        $this->assertArrayHasKey('disconnects', $summary);
        $this->assertArrayHasKey('uptime_seconds', $summary);

        // Check values are correct
        $this->assertEquals(2, $summary['total_messages']);
    }

    /**
     * SPECIFICATION: Display output should show all statistics.
     */
    public function testDisplayOutputShowsAllStatistics(): void
    {
        $this->collector->record($this->createPublishMessage('sensors/temp', 'payload', 'incoming'));
        $this->collector->recordLatency(15.5);

        $display = $this->collector->formatDisplay();

        // Check header
        $this->assertStringContainsString('MQTT Statistics', $display);

        // Check message counts
        $this->assertStringContainsString('Messages (total)', $display);
        $this->assertStringContainsString('Rate:', $display);
        $this->assertStringContainsString('Incoming:', $display);
        $this->assertStringContainsString('Outgoing:', $display);

        // Check QoS section
        $this->assertStringContainsString('QoS Distribution:', $display);
        $this->assertStringContainsString('QoS 0:', $display);
        $this->assertStringContainsString('QoS 1:', $display);
        $this->assertStringContainsString('QoS 2:', $display);

        // Check errors and uptime
        $this->assertStringContainsString('Errors:', $display);
        $this->assertStringContainsString('Uptime:', $display);
    }

    /**
     * SPECIFICATION: Reset should clear all statistics.
     */
    public function testResetClearsAllStatistics(): void
    {
        // Add data
        $this->collector->record($this->createPublishMessage('test', 'payload', 'incoming'));
        $this->collector->recordLatency(25.5);

        $this->assertGreaterThan(0, $this->collector->getTotalMessages());
        $this->assertGreaterThan(0, $this->collector->getAverageLatency());

        // Reset
        $this->collector->reset();

        // All should be zeroed
        $this->assertEquals(0, $this->collector->getTotalMessages());
        $this->assertEquals(0, $this->collector->getIncomingMessages());
        $this->assertEquals(0, $this->collector->getOutgoingMessages());
        $this->assertEquals(0, $this->collector->getErrorCount());
        $this->assertEquals(0.0, $this->collector->getAverageLatency());
        $this->assertEmpty($this->collector->getTopTopics());
    }

    /**
     * SPECIFICATION: Subscribe events should be counted.
     */
    public function testSubscribeEventsAreCounted(): void
    {
        $subscribeMsg = new Message(
            type: 'subscribe',
            payload: ['topics' => ['test/topic']],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );

        $this->collector->record($subscribeMsg);
        $this->collector->record($subscribeMsg);

        $summary = $this->collector->getSummary();
        $this->assertEquals(2, $summary['subscribes']);
    }

    /**
     * SPECIFICATION: Disconnect events should be counted.
     */
    public function testDisconnectEventsAreCounted(): void
    {
        $disconnectMsg = new Message(
            type: 'disconnect',
            payload: ['code' => 0],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );

        $this->collector->record($disconnectMsg);

        $summary = $this->collector->getSummary();
        $this->assertEquals(1, $summary['disconnects']);
    }

    /**
     * SPECIFICATION: Uptime should increase over time.
     */
    public function testUptimeIncreasesOverTime(): void
    {
        $uptime1 = $this->collector->getUptime();
        usleep(50000); // 50ms delay
        $uptime2 = $this->collector->getUptime();

        $this->assertGreaterThan(0.0, $uptime1);
        $this->assertGreaterThan($uptime1, $uptime2);
    }

    /**
     * SPECIFICATION: Rate should reflect recent message frequency.
     */
    public function testRateReflectsRecentMessageFrequency(): void
    {
        // Initially rate should be zero
        $this->assertEquals(0.0, $this->collector->getRate());

        // Record messages quickly
        for ($i = 0; $i < 10; $i++) {
            $this->collector->record($this->createPublishMessage("test/topic{$i}", 'payload', 'incoming'));
        }

        // Rate should be positive
        $rate = $this->collector->getRate();
        $this->assertGreaterThan(0.0, $rate);
    }

    /**
     * SPECIFICATION: Latency percentiles should represent distribution.
     */
    public function testLatencyPercentilesRepresentDistribution(): void
    {
        // Record latencies in known distribution
        for ($i = 1; $i <= 100; $i++) {
            $this->collector->recordLatency((float) $i);
        }

        // P50 should be around 50
        $p50 = $this->collector->getLatencyPercentile(50);
        $this->assertGreaterThan(40.0, $p50);
        $this->assertLessThan(60.0, $p50);

        // P95 should be around 95
        $p95 = $this->collector->getLatencyPercentile(95);
        $this->assertGreaterThan(90.0, $p95);
        $this->assertLessThanOrEqual(100.0, $p95);

        // P99 should be near 99
        $p99 = $this->collector->getLatencyPercentile(99);
        $this->assertGreaterThan(95.0, $p99);
        $this->assertLessThanOrEqual(100.0, $p99);
    }

    /**
     * SPECIFICATION: Empty latency measurements should return zero.
     */
    public function testEmptyLatencyMeasurementsReturnZero(): void
    {
        $this->assertEquals(0.0, $this->collector->getAverageLatency());
        $this->assertEquals(0.0, $this->collector->getMinLatency());
        $this->assertEquals(0.0, $this->collector->getMaxLatency());
        $this->assertEquals(0.0, $this->collector->getLatencyPercentile(50));
    }

    /**
     * SPECIFICATION: Latency stats should be structured correctly.
     */
    public function testLatencyStatsStructuredCorrectly(): void
    {
        $this->collector->recordLatency(10.0);
        $this->collector->recordLatency(20.0);
        $this->collector->recordLatency(30.0);

        $stats = $this->collector->getLatencyStats();

        $this->assertArrayHasKey('min', $stats);
        $this->assertArrayHasKey('max', $stats);
        $this->assertArrayHasKey('avg', $stats);
        $this->assertArrayHasKey('p50', $stats);
        $this->assertArrayHasKey('p95', $stats);
        $this->assertArrayHasKey('p99', $stats);
        $this->assertArrayHasKey('count', $stats);

        $this->assertEquals(10.0, $stats['min']);
        $this->assertEquals(30.0, $stats['max']);
        $this->assertEquals(3, $stats['count']);
    }

    /**
     * SPECIFICATION: Latency window should limit stored measurements.
     */
    public function testLatencyWindowLimitsStoredMeasurements(): void
    {
        $collector = new StatsCollector(300, 5); // Window of 5 measurements

        // Record more than window size
        for ($i = 1; $i <= 10; $i++) {
            $collector->recordLatency((float) $i);
        }

        // Total count should reflect all measurements
        $stats = $collector->getLatencyStats();
        $this->assertEquals(10, $stats['count']);

        // But histogram should only use window
        $histogram = $collector->getLatencyHistogram();
        $this->assertIsArray($histogram);
        $this->assertEquals(5, array_sum($histogram)); // Only 5 in window
    }

    /**
     * Helper to create a publish message.
     */
    private function createPublishMessage(
        string $topic,
        string $payload,
        string $direction = 'incoming',
        int $qos = 0,
    ): Message {
        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => $direction,
                'qos' => $qos,
            ],
        );
    }
}
