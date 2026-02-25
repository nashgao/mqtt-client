<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Config;

/**
 * Centralized configuration for MQTT Shell.
 *
 * Contains all tunable parameters that were previously hardcoded as magic numbers.
 * Use ShellConfig::default() for standard defaults or customize individual values.
 */
final readonly class ShellConfig
{
    /**
     * @param int $messageHistoryLimit Maximum messages retained in history (MessageHistory)
     * @param int $channelBufferSize Swoole channel buffer size for message queue
     * @param int $defaultHistoryCount Default number of messages for history command
     * @param int $searchResultLimit Default limit for search results
     * @param int $topicFilterLimit Default limit for topic filter results
     * @param int $rateWindowSeconds Window size for rate calculation (StatsCollector)
     * @param int $latencyWindowSize Sliding window size for latency measurements
     * @param int $topTopicsLimit Number of top topics to show in stats
     * @param int $flowTimelineLimit Maximum entries in flow timeline
     * @param int $defaultFlowRenderCount Default number of flow entries to render
     * @param int $payloadTruncation Default payload truncation length in compact format
     * @param int $topicDisplayLength Topic display length before truncation
     * @param int $histogramBuckets Default number of buckets for latency histogram
     * @param float $pollingLoopSleep Sleep duration in polling loop (seconds)
     * @param float $coroutineSleep Sleep duration in message receiver coroutine (seconds)
     * @param float $inputHandlerSleep Sleep duration in input handler coroutine (seconds)
     * @param float $mainLoopSleep Sleep duration in main Swoole coroutine (seconds)
     * @param float $activityTimeoutSeconds Timeout for activity detection
     * @param string $historyFile Path to readline history file
     * @param int $historyMaxEntries Maximum entries in readline history
     */
    public function __construct(
        // Message handling
        public int $messageHistoryLimit = 500,
        public int $channelBufferSize = 100,
        public int $defaultHistoryCount = 20,
        public int $searchResultLimit = 50,
        public int $topicFilterLimit = 50,

        // Statistics
        public int $rateWindowSeconds = 300,
        public int $latencyWindowSize = 1000,
        public int $topTopicsLimit = 5,
        public int $histogramBuckets = 10,

        // Flow visualization
        public int $flowTimelineLimit = 100,
        public int $defaultFlowRenderCount = 10,

        // Display formatting
        public int $payloadTruncation = 80,
        public int $topicDisplayLength = 30,
        public int $topicTruncationThreshold = 28,

        // Timing (in seconds)
        public float $pollingLoopSleep = 0.01,
        public float $coroutineSleep = 0.01,
        public float $inputHandlerSleep = 0.01,
        public float $mainLoopSleep = 0.1,
        public float $activityTimeoutSeconds = 60.0,

        // Readline/History
        public string $historyFile = '~/.mqtt_shell_history',
        public int $historyMaxEntries = 1000,
    ) {}

    /**
     * Create default configuration.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            messageHistoryLimit: self::getInt($config, 'messageHistoryLimit', 500),
            channelBufferSize: self::getInt($config, 'channelBufferSize', 100),
            defaultHistoryCount: self::getInt($config, 'defaultHistoryCount', 20),
            searchResultLimit: self::getInt($config, 'searchResultLimit', 50),
            topicFilterLimit: self::getInt($config, 'topicFilterLimit', 50),
            rateWindowSeconds: self::getInt($config, 'rateWindowSeconds', 300),
            latencyWindowSize: self::getInt($config, 'latencyWindowSize', 1000),
            topTopicsLimit: self::getInt($config, 'topTopicsLimit', 5),
            histogramBuckets: self::getInt($config, 'histogramBuckets', 10),
            flowTimelineLimit: self::getInt($config, 'flowTimelineLimit', 100),
            defaultFlowRenderCount: self::getInt($config, 'defaultFlowRenderCount', 10),
            payloadTruncation: self::getInt($config, 'payloadTruncation', 80),
            topicDisplayLength: self::getInt($config, 'topicDisplayLength', 30),
            topicTruncationThreshold: self::getInt($config, 'topicTruncationThreshold', 28),
            pollingLoopSleep: self::getFloat($config, 'pollingLoopSleep', 0.01),
            coroutineSleep: self::getFloat($config, 'coroutineSleep', 0.01),
            inputHandlerSleep: self::getFloat($config, 'inputHandlerSleep', 0.01),
            mainLoopSleep: self::getFloat($config, 'mainLoopSleep', 0.1),
            activityTimeoutSeconds: self::getFloat($config, 'activityTimeoutSeconds', 60.0),
            historyFile: self::getString($config, 'historyFile', '~/.mqtt_shell_history'),
            historyMaxEntries: self::getInt($config, 'historyMaxEntries', 1000),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function getInt(array $config, string $key, int $default): int
    {
        $value = $config[$key] ?? $default;
        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function getFloat(array $config, string $key, float $default): float
    {
        $value = $config[$key] ?? $default;
        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function getString(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    /**
     * Create configuration from JSON file.
     *
     * @param string $path Path to JSON configuration file
     */
    public static function fromFile(string $path): self
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \InvalidArgumentException("Cannot read config file: {$path}");
        }

        $config = json_decode($contents, true);
        if (! is_array($config)) {
            throw new \InvalidArgumentException("Invalid JSON in config file: {$path}");
        }

        return self::fromArray($config);
    }

    /**
     * Export configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messageHistoryLimit' => $this->messageHistoryLimit,
            'channelBufferSize' => $this->channelBufferSize,
            'defaultHistoryCount' => $this->defaultHistoryCount,
            'searchResultLimit' => $this->searchResultLimit,
            'topicFilterLimit' => $this->topicFilterLimit,
            'rateWindowSeconds' => $this->rateWindowSeconds,
            'latencyWindowSize' => $this->latencyWindowSize,
            'topTopicsLimit' => $this->topTopicsLimit,
            'histogramBuckets' => $this->histogramBuckets,
            'flowTimelineLimit' => $this->flowTimelineLimit,
            'defaultFlowRenderCount' => $this->defaultFlowRenderCount,
            'payloadTruncation' => $this->payloadTruncation,
            'topicDisplayLength' => $this->topicDisplayLength,
            'topicTruncationThreshold' => $this->topicTruncationThreshold,
            'pollingLoopSleep' => $this->pollingLoopSleep,
            'coroutineSleep' => $this->coroutineSleep,
            'inputHandlerSleep' => $this->inputHandlerSleep,
            'mainLoopSleep' => $this->mainLoopSleep,
            'activityTimeoutSeconds' => $this->activityTimeoutSeconds,
            'historyFile' => $this->historyFile,
            'historyMaxEntries' => $this->historyMaxEntries,
        ];
    }
}
