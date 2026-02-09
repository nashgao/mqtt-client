<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

use NashGao\InteractiveShell\Message\Message;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Alert action that displays formatted alerts in console output
 */
final class AlertAction implements ActionInterface
{
    /**
     * @param OutputInterface $output Console output interface
     * @param string $alertType Alert type (info, warning, error)
     */
    public function __construct(
        private readonly OutputInterface $output,
        private readonly string $alertType = 'info',
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $data, Message $originalMessage): void
    {
        $tag = match ($this->alertType) {
            'warning' => '<fg=yellow>WARNING</>',
            'error' => '<fg=red>ERROR</>',
            default => '<fg=blue>INFO</>',
        };

        // Extract topic from message payload
        $topic = '';
        if (is_array($originalMessage->payload) && isset($originalMessage->payload['topic'])) {
            $topic = is_string($originalMessage->payload['topic']) ? $originalMessage->payload['topic'] : '';
        }

        $this->output->writeln('');
        $this->output->writeln("┌─ {$tag} ─────────────────────────────────");
        $this->output->writeln("│ Topic: {$topic}");
        $this->output->writeln('│ Matched Data:');

        foreach ($data as $key => $value) {
            $valueStr = is_scalar($value) ? (string) $value : json_encode($value);
            $this->output->writeln("│   {$key}: {$valueStr}");
        }

        $this->output->writeln('└────────────────────────────────────────');
        $this->output->writeln('');
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return "alert:{$this->alertType}";
    }
}
