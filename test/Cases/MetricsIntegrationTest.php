<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\ServerMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class MetricsIntegrationTest extends AbstractTestCase
{
    public function testPublishMetricsIntegration()
    {
        $publishMetrics = new PublishMetrics();

        // Test publish attempt tracking
        $publishMetrics->recordPublishAttempt();
        $this->assertEquals(1, $publishMetrics->getTotalPublishes());

        // Test successful publish
        $publishMetrics->recordSuccessfulPublish('sensors/temperature', 1, 100);
        $this->assertEquals(1, $publishMetrics->getSuccessfulPublishes());
        $this->assertEquals(100.0, $publishMetrics->getSuccessRate());

        // Test topic statistics
        $topicStats = $publishMetrics->getTopicStats('sensors/temperature');
        $this->assertNotNull($topicStats);
        $this->assertEquals(1, $topicStats['count']);
        $this->assertEquals(1, $topicStats['qos_levels'][1]);

        // Test QoS distribution
        $qosDistribution = $publishMetrics->getQosDistribution();
        $this->assertEquals(1, $qosDistribution[1]);

        // Test failed publish
        $publishMetrics->recordPublishAttempt();
        $publishMetrics->recordFailedPublish();
        $this->assertEquals(2, $publishMetrics->getTotalPublishes());
        $this->assertEquals(1, $publishMetrics->getFailedPublishes());
        $this->assertEquals(50.0, $publishMetrics->getSuccessRate());

        // Test summary
        $summary = $publishMetrics->getSummary();
        $this->assertArrayHasKey('total_attempts', $summary);
        $this->assertArrayHasKey('success_rate', $summary);
        $this->assertEquals(2, $summary['total_attempts']);
        $this->assertEquals(50.0, $summary['success_rate']);
    }

    public function testSubscriptionMetricsIntegration()
    {
        $subscriptionMetrics = new SubscriptionMetrics();

        // Test subscription attempt tracking
        $subscriptionMetrics->recordSubscriptionAttempt();
        $this->assertEquals(1, $subscriptionMetrics->getTotalSubscriptionAttempts());

        // Test successful subscription
        $topics = ['sensors/temperature' => 1, 'alerts/fire' => 2];
        $subscriptionMetrics->recordSuccessfulSubscription('pool1', 'client1', $topics);
        $this->assertEquals(1, $subscriptionMetrics->getSuccessfulSubscriptions());
        $this->assertEquals(100.0, $subscriptionMetrics->getSuccessRate());

        // Test topic subscription stats
        $topicStats = $subscriptionMetrics->getTopicSubscriptionStats('sensors/temperature');
        $this->assertNotNull($topicStats);
        $this->assertEquals(1, $topicStats['count']);

        // Test pool subscription stats
        $poolStats = $subscriptionMetrics->getPoolSubscriptionStats('pool1');
        $this->assertNotNull($poolStats);
        $this->assertEquals(1, $poolStats['successful']);
        $this->assertEquals(2, $poolStats['topics_subscribed']);

        // Test failed subscription
        $subscriptionMetrics->recordSubscriptionAttempt();
        $subscriptionMetrics->recordFailedSubscription('pool1', 'client2', $topics, 'Connection failed');
        $this->assertEquals(2, $subscriptionMetrics->getTotalSubscriptionAttempts());
        $this->assertEquals(1, $subscriptionMetrics->getFailedSubscriptions());
        $this->assertEquals(50.0, $subscriptionMetrics->getSuccessRate());

        // Test QoS distribution
        $qosDistribution = $subscriptionMetrics->getQosDistribution();
        $this->assertEquals(1, $qosDistribution[1]); // sensors/temperature
        $this->assertEquals(1, $qosDistribution[2]); // alerts/fire

        // Test summary
        $summary = $subscriptionMetrics->getSummary();
        $this->assertArrayHasKey('total_attempts', $summary);
        $this->assertArrayHasKey('success_rate', $summary);
        $this->assertEquals(2, $summary['total_attempts']);
        $this->assertEquals(50.0, $summary['success_rate']);
    }

    public function testServerMetricsIntegration()
    {
        $serverMetrics = new ServerMetrics();

        // Test server start
        $serverId = 'test_server_123';
        $serverMetrics->recordServerStart($serverId);
        $this->assertEquals($serverId, $serverMetrics->getServerId());
        $this->assertTrue($serverMetrics->isServerRunning());
        $this->assertEquals(1, $serverMetrics->getRestartCount());

        // Test uptime calculation
        $this->assertGreaterThan(0, $serverMetrics->getUptime());
        $this->assertStringContainsString('00h', $serverMetrics->getUptimeFormatted());

        // Test heartbeat
        $initialHeartbeat = $serverMetrics->getLastHeartbeat();
        usleep(1000); // Wait 1ms
        $serverMetrics->recordHeartbeat();
        $this->assertGreaterThan($initialHeartbeat, $serverMetrics->getLastHeartbeat());

        // Test server events
        $serverMetrics->recordServerEvent('custom_event', ['key' => 'value']);
        $events = $serverMetrics->getLifecycleEvents();
        $this->assertGreaterThan(0, count($events));

        $customEvents = $serverMetrics->getEventsByType('custom_event');
        $this->assertEquals(1, count($customEvents));
        $this->assertNotEmpty($customEvents);
        $firstCustomEvent = array_values($customEvents)[0];
        $this->assertEquals('custom_event', $firstCustomEvent['event']);

        // Test health status
        $healthStatus = $serverMetrics->getHealthStatus();
        $this->assertArrayHasKey('is_healthy', $healthStatus);
        $this->assertArrayHasKey('is_running', $healthStatus);
        $this->assertTrue($healthStatus['is_running']);

        // Test server stop
        $serverMetrics->recordServerStop('test_shutdown');
        $this->assertFalse($serverMetrics->isServerRunning());

        // Test summary
        $summary = $serverMetrics->getSummary();
        $this->assertArrayHasKey('server_id', $summary);
        $this->assertArrayHasKey('uptime', $summary);
        $this->assertArrayHasKey('restart_count', $summary);
        $this->assertEquals($serverId, $summary['server_id']);
        $this->assertEquals(1, $summary['restart_count']);
    }

    public function testMetricsClassesIntegrateWithExistingSystem()
    {
        // Test that our new metrics classes work with existing validation metrics
        $publishMetrics = new PublishMetrics();
        $subscriptionMetrics = new SubscriptionMetrics();
        $serverMetrics = new ServerMetrics();

        // Simulate a complete MQTT operation flow
        $serverMetrics->recordServerStart('integration_test_server');

        // Simulate subscription
        $subscriptionMetrics->recordSubscriptionAttempt();
        $subscriptionMetrics->recordSuccessfulSubscription(
            'test_pool',
            'test_client',
            ['test/topic' => 1]
        );

        // Simulate publishing
        $publishMetrics->recordPublishAttempt();
        $publishMetrics->recordSuccessfulPublish('test/topic', 1, 50);

        // Verify all metrics are tracking correctly
        $this->assertTrue($serverMetrics->isServerRunning());
        $this->assertEquals(1, $subscriptionMetrics->getSuccessfulSubscriptions());
        $this->assertEquals(1, $publishMetrics->getSuccessfulPublishes());

        // Test toArray methods work correctly
        $serverArray = $serverMetrics->toArray();
        $subscriptionArray = $subscriptionMetrics->toArray();
        $publishArray = $publishMetrics->toArray();

        $this->assertIsArray($serverArray);
        $this->assertIsArray($subscriptionArray);
        $this->assertIsArray($publishArray);

        // Verify critical metrics are present
        $this->assertArrayHasKey('server_id', $serverArray);
        $this->assertArrayHasKey('success_rate', $subscriptionArray);
        $this->assertArrayHasKey('success_rate', $publishArray);

        // Test reset functionality
        $publishMetrics->reset();
        $subscriptionMetrics->reset();
        $serverMetrics->reset();

        $this->assertEquals(0, $publishMetrics->getTotalPublishes());
        $this->assertEquals(0, $subscriptionMetrics->getTotalSubscriptionAttempts());
        $this->assertEmpty($serverMetrics->getServerId());
    }

    public function testMetricsDataConsistency()
    {
        $publishMetrics = new PublishMetrics();

        // Test edge cases and data consistency
        $this->assertEquals(0.0, $publishMetrics->getSuccessRate()); // No attempts yet

        // Test multiple operations
        for ($i = 0; $i < 10; ++$i) {
            $publishMetrics->recordPublishAttempt();
            if ($i % 2 === 0) {
                $publishMetrics->recordSuccessfulPublish("topic_{$i}", $i % 3, 100 + $i);
            } else {
                $publishMetrics->recordFailedPublish();
            }
        }

        $this->assertEquals(10, $publishMetrics->getTotalPublishes());
        $this->assertEquals(5, $publishMetrics->getSuccessfulPublishes());
        $this->assertEquals(5, $publishMetrics->getFailedPublishes());
        $this->assertEquals(50.0, $publishMetrics->getSuccessRate());

        // Test topic statistics are maintained correctly
        $allTopics = $publishMetrics->getAllTopicStats();
        $this->assertEquals(5, count($allTopics)); // 5 successful publishes to different topics

        // Test message size statistics
        $sizeStats = $publishMetrics->getMessageSizeStats();
        $this->assertArrayHasKey('min', $sizeStats);
        $this->assertArrayHasKey('max', $sizeStats);
        $this->assertArrayHasKey('avg', $sizeStats);
        $this->assertEquals(100, $sizeStats['min']);
        $this->assertEquals(108, $sizeStats['max']); // 100 + 8 (last even number)
    }
}
