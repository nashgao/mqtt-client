<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

use NashGao\InteractiveShell\Message\Message;

/**
 * Log action that writes matched data to a log file
 */
final class LogAction implements ActionInterface
{
    /**
     * @param string $logFile Path to log file
     */
    public function __construct(
        private readonly string $logFile,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $data, Message $originalMessage): void
    {
        $timestamp = date('Y-m-d H:i:s');

        // Extract topic from message payload
        $topic = '';
        if (is_array($originalMessage->payload) && isset($originalMessage->payload['topic'])) {
            $topic = is_string($originalMessage->payload['topic']) ? $originalMessage->payload['topic'] : '';
        }

        $logEntry = [
            'timestamp' => $timestamp,
            'topic' => $topic,
            'data' => $data,
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        // Append to file using native PHP functions
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get the log file path
     *
     * @return string Log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return "log:{$this->logFile}";
    }
}
