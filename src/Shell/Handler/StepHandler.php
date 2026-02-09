<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\Debug\StepStateChange;
use Nashgao\MQTT\Shell\Debug\StepThroughState;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for step-through debugging commands.
 *
 * Provides interactive message inspection capabilities:
 * - Step mode: Pause on each incoming message
 * - Breakpoints: Pause on messages matching patterns
 * - Inspection: View detailed message information
 * - Flow control: Advance, skip, or resume normal operation
 */
final class StepHandler implements HandlerInterface
{
    public function __construct(
        private readonly StepThroughState $stepState,
    ) {}

    public function getCommands(): array
    {
        return ['step', 'next', 'n', 'continue', 'c', 'inspect'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        return match ($cmd) {
            'step' => $this->handleStep($command, $context),
            'next', 'n' => $this->handleNext($context),
            'continue', 'c' => $this->handleContinue($context),
            'inspect' => $this->handleInspect($context),
            default => HandlerResult::failure('Unknown command'),
        };
    }

    public function getDescription(): string
    {
        return 'Step-through debugging for MQTT messages';
    }

    public function getUsage(): array
    {
        return [
            'step                       Enable step mode',
            'step on                    Enable step mode',
            'step off                   Disable step mode',
            'step break <field>:<pattern>  Add breakpoint (e.g., step break topic:sensors/#)',
            'step clear                 Clear all breakpoints',
            'step status                Show step mode status and breakpoints',
            'next / n                   Advance to next message',
            'continue / c               Resume normal flow (disable step mode)',
            'inspect                    Show detailed view of current message',
        ];
    }

    /**
     * Handle step command and its subcommands.
     */
    private function handleStep(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $subCommand = $command->getArgument(0);

        // No args or "on" - enable step mode
        if ($subCommand === null) {
            return $this->enableStepMode($context);
        }

        if (!is_string($subCommand)) {
            $context->output->writeln('<error>Invalid step command. Use "help step" for usage.</error>');
            return HandlerResult::failure('Invalid step command');
        }

        $subCommandLower = strtolower($subCommand);

        if ($subCommandLower === 'on') {
            return $this->enableStepMode($context);
        }

        // "off" - disable step mode
        if ($subCommandLower === 'off') {
            return $this->disableStepMode($context);
        }

        // "status" - show current state
        if ($subCommandLower === 'status') {
            return $this->showStatus($context);
        }

        // "clear" - clear breakpoints
        if ($subCommandLower === 'clear') {
            return $this->clearBreakpoints($context);
        }

        // "break field:pattern" - add breakpoint
        if ($subCommandLower === 'break') {
            $breakpointSpec = $command->getArgument(1);
            if (is_string($breakpointSpec)) {
                return $this->addBreakpoint($breakpointSpec, $context);
            }
            $context->output->writeln('<error>Missing breakpoint specification. Use: step break field:pattern</error>');
            return HandlerResult::failure('Missing breakpoint specification');
        }

        $context->output->writeln('<error>Invalid step command. Use "help step" for usage.</error>');
        return HandlerResult::failure('Invalid step command');
    }

    /**
     * Enable step-through mode.
     */
    private function enableStepMode(HandlerContext $context): HandlerResult
    {
        $this->stepState->setEnabled(true);
        $context->output->writeln('<info>Step mode enabled. Messages will pause for inspection.</info>');
        $context->output->writeln('<comment>Use "next" or "n" to advance to next message.</comment>');
        $context->output->writeln('<comment>Use "continue" or "c" to resume normal flow.</comment>');

        return HandlerResult::stepEnabled();
    }

    /**
     * Disable step-through mode.
     */
    private function disableStepMode(HandlerContext $context): HandlerResult
    {
        $this->stepState->setEnabled(false);
        $this->stepState->setWaitingForInput(false);
        $context->output->writeln('<info>Step mode disabled.</info>');

        return HandlerResult::stepDisabled();
    }

    /**
     * Show current step mode status and breakpoints.
     */
    private function showStatus(HandlerContext $context): HandlerResult
    {
        $enabled = $this->stepState->isEnabled();
        $waiting = $this->stepState->isWaitingForInput();
        $breakpoints = $this->stepState->getBreakpoints();

        $context->output->writeln('<info>Step Mode Status:</info>');
        $context->output->writeln(sprintf('  Enabled: %s', $enabled ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        $context->output->writeln(sprintf('  Waiting: %s', $waiting ? '<fg=yellow>Yes</>' : '<fg=green>No</>'));
        $context->output->writeln('');

        if (!empty($breakpoints)) {
            $context->output->writeln('<info>Breakpoints:</info>');
            foreach ($breakpoints as $field => $pattern) {
                $context->output->writeln(sprintf('  %s: %s', $field, $pattern));
            }
        } else {
            $context->output->writeln('<comment>No breakpoints configured.</comment>');
        }

        return HandlerResult::success();
    }

    /**
     * Add a breakpoint.
     */
    private function addBreakpoint(string $spec, HandlerContext $context): HandlerResult
    {
        $parts = explode(':', $spec, 2);
        if (count($parts) !== 2) {
            $context->output->writeln('<error>Invalid breakpoint format. Use: field:pattern</error>');
            $context->output->writeln('<comment>Example: step break topic:sensors/#</comment>');
            return HandlerResult::failure('Invalid breakpoint format');
        }

        [$field, $pattern] = $parts;
        $field = strtolower(trim($field));
        $pattern = trim($pattern);

        $validFields = ['topic', 'payload', 'qos', 'retain'];
        if (!in_array($field, $validFields, true)) {
            $context->output->writeln(sprintf(
                '<error>Invalid field "%s". Valid fields: %s</error>',
                $field,
                implode(', ', $validFields)
            ));
            return HandlerResult::failure('Invalid field');
        }

        $this->stepState->addBreakpoint($field, $pattern);
        $context->output->writeln(sprintf(
            '<info>Breakpoint added: %s:%s</info>',
            $field,
            $pattern
        ));

        return HandlerResult::success();
    }

    /**
     * Clear all breakpoints.
     */
    private function clearBreakpoints(HandlerContext $context): HandlerResult
    {
        $count = count($this->stepState->getBreakpoints());
        $this->stepState->clearBreakpoints();

        if ($count > 0) {
            $context->output->writeln(sprintf('<info>Cleared %d breakpoint(s).</info>', $count));
        } else {
            $context->output->writeln('<comment>No breakpoints to clear.</comment>');
        }

        return HandlerResult::success();
    }

    /**
     * Handle "next" command - advance to next message.
     */
    private function handleNext(HandlerContext $context): HandlerResult
    {
        if (!$this->stepState->isEnabled()) {
            $context->output->writeln('<error>Step mode is not enabled. Use "step" to enable.</error>');
            return HandlerResult::failure('Step mode not enabled');
        }

        if (!$this->stepState->isWaitingForInput()) {
            $context->output->writeln('<comment>Not currently paused. Waiting for next message...</comment>');
            return HandlerResult::success();
        }

        $this->stepState->setWaitingForInput(false);
        $context->output->writeln('<info>Advancing to next message...</info>');

        return HandlerResult::stepAdvance();
    }

    /**
     * Handle "continue" command - resume normal flow.
     */
    private function handleContinue(HandlerContext $context): HandlerResult
    {
        if (!$this->stepState->isEnabled()) {
            $context->output->writeln('<comment>Step mode is already disabled.</comment>');
            return HandlerResult::success();
        }

        $this->stepState->setEnabled(false);
        $this->stepState->setWaitingForInput(false);
        $context->output->writeln('<info>Resuming normal message flow...</info>');

        return HandlerResult::stepResume();
    }

    /**
     * Handle "inspect" command - show detailed current message.
     */
    private function handleInspect(HandlerContext $context): HandlerResult
    {
        $message = $this->stepState->getCurrentMessage();

        if ($message === null) {
            $context->output->writeln('<comment>No message currently paused for inspection.</comment>');
            $context->output->writeln('<comment>Enable step mode with "step" to inspect messages.</comment>');
            return HandlerResult::success();
        }

        // Show message in vertical format for detailed inspection
        $originalFormat = $context->formatter->getFormat();
        $context->formatter->setFormat('vertical');

        $context->output->writeln('<info>Current Message (Step Mode):</info>');
        $context->output->writeln('');
        $context->output->writeln($context->formatter->format($message));

        $context->formatter->setFormat($originalFormat);

        return HandlerResult::success();
    }
}
