<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;
use Nashgao\MQTT\Shell\Visualization\FlowTimeline;
use Nashgao\MQTT\Shell\Visualization\TopicTree;

/**
 * Handler for visualization commands.
 *
 * Commands:
 * - tree - Show topic tree visualization
 * - tree --depth=N - Limit tree depth
 * - flow - Show message flow timeline
 * - flow --last=N - Show last N messages
 * - flow --topic=pattern - Filter by topic pattern
 */
final class VisualizeHandler implements HandlerInterface
{
    private TopicTree $topicTree;

    private ?FlowTimeline $flowTimeline = null;

    private ?ShellConfig $config = null;

    public function __construct()
    {
        $this->topicTree = new TopicTree();
    }

    /**
     * Get or create the flow timeline with config.
     */
    private function getFlowTimeline(): FlowTimeline
    {
        if ($this->flowTimeline === null) {
            $this->flowTimeline = new FlowTimeline($this->config);
        }
        return $this->flowTimeline;
    }

    public function getCommands(): array
    {
        return ['tree', 'flow', 'visualize', 'viz'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        // Store config for lazy initialization
        $this->config ??= $context->config;

        $cmd = strtolower($command->command);

        // Handle tree visualization
        if ($cmd === 'tree') {
            return $this->handleTree($command, $context);
        }

        // Handle flow timeline
        if ($cmd === 'flow') {
            return $this->handleFlow($command, $context);
        }

        // Handle generic visualize/viz command
        if ($cmd === 'visualize' || $cmd === 'viz') {
            $subCommand = $command->getArgument(0);

            if ($subCommand === 'tree') {
                return $this->handleTree($command, $context);
            }

            if ($subCommand === 'flow') {
                return $this->handleFlow($command, $context);
            }

            // Show both by default
            $context->output->writeln($this->topicTree->render());
            $context->output->writeln('');
            $context->output->writeln($this->getFlowTimeline()->render());

            return HandlerResult::success();
        }

        return HandlerResult::failure('Unknown visualization command');
    }

    public function getDescription(): string
    {
        return 'Visualize MQTT topics and message flow';
    }

    public function getUsage(): array
    {
        return [
            'tree                   Show topic tree visualization',
            'tree --depth=2         Limit tree depth to 2 levels',
            'tree --clear           Clear topic tree',
            'flow                   Show message flow timeline (last 10)',
            'flow --last=20         Show last 20 messages in timeline',
            'flow --topic=sensors/# Filter flow by topic pattern',
            'flow --clear           Clear flow timeline',
            'viz                    Show both tree and flow',
        ];
    }

    /**
     * Track a new message for visualization.
     *
     * This should be called by the shell when messages are received.
     */
    public function trackMessage(Message $message, ?string $matchedRule = null): void
    {
        $this->topicTree->addMessage($message);
        $this->getFlowTimeline()->addMessage($message, $matchedRule);
    }

    /**
     * Handle tree visualization command.
     */
    private function handleTree(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        // Check for clear flag
        if ($command->hasOption('clear')) {
            $this->topicTree->clear();
            $context->output->writeln('<info>Topic tree cleared</info>');
            return HandlerResult::success();
        }

        // Get depth option
        $rawDepth = $command->getOption('depth');
        $depth = $rawDepth !== null && is_numeric($rawDepth) ? (int) $rawDepth : -1;

        // Render tree
        $output = $this->topicTree->render($depth);
        $context->output->writeln($output);

        return HandlerResult::success();
    }

    /**
     * Handle flow timeline command.
     */
    private function handleFlow(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $timeline = $this->getFlowTimeline();

        // Check for clear flag
        if ($command->hasOption('clear')) {
            $timeline->clear();
            $context->output->writeln('<info>Flow timeline cleared</info>');
            return HandlerResult::success();
        }

        // Get options - use null to let FlowTimeline use its configured default
        $rawLimit = $command->getOption('last') ?? $command->getOption('limit');
        $limit = $rawLimit !== null && is_numeric($rawLimit) ? (int) $rawLimit : null;

        $topicFilter = $command->getOption('topic');
        $topicFilter = is_string($topicFilter) ? $topicFilter : null;

        // Render timeline
        $output = $timeline->render($limit, $topicFilter);
        $context->output->writeln($output);

        return HandlerResult::success();
    }
}
