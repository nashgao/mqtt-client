# MQTT Shell Prompt Design

**Date:** 2026-02-26
**Status:** Approved
**Complexity:** Simple (~25 lines)

## Problem

The MQTT debug shell has no visible prompt indicator, causing:
1. **"Is it ready?"** - Uncertainty whether the shell is waiting for input
2. **"Where am I?"** - When messages scroll by, losing track of the input line

## Solution

Implement a "status line with re-prompt" approach that displays `mqtt> ` at strategic moments.

## Core Behavior

The prompt appears in three situations:

1. **After welcome message** - When shell starts, print `mqtt> ` so user knows it's ready
2. **After command execution** - When a command completes, re-print the prompt
3. **After quiet period** - If messages stop for 2+ seconds, print the prompt

### What happens during message flow
- Messages continue to display normally
- The prompt does NOT interrupt mid-stream
- Only appears after natural pauses
- If user is typing when quiet-period would fire, prompt is suppressed

## Implementation

### Files Changed

1. `src/Shell/MqttShellClient.php` - Add prompt display logic (~20 lines)
2. `src/Shell/Config/ShellConfig.php` - Add configuration options (~5 lines)

### New State Tracking

```php
private float $lastActivityTime = 0.0;
private const QUIET_THRESHOLD = 2.0; // seconds
```

### Prompt Display Points

1. **After welcome** (in `run()` method):
   ```php
   $output->write($this->prompt);
   ```

2. **After command execution** (end of `handleInput()`):
   ```php
   $output->write($this->prompt);
   ```

3. **Quiet-period check** (in message display coroutine):
   - Track `$lastActivityTime` when messages display
   - Check if `time() - $lastActivityTime > QUIET_THRESHOLD`
   - If quiet and not paused, print prompt

### Configuration Options

```php
// In ShellConfig.php
public bool $showPrompt = true,
public float $promptQuietThreshold = 2.0,
```

## Design Decisions

### Why not a fixed bottom bar?
More complex (~50-80 lines), terminal compatibility concerns, potential conflict with readline.

### Why not startup-only prompt?
Only solves "is it ready?" at startup, not during extended use.

### Why 2 seconds for quiet threshold?
Balances responsiveness with avoiding prompt spam during bursty message patterns. Configurable if needed.

## Usage

Default behavior - works out of the box:
```php
$shell = new MqttShellClient($transport);
// Prompt appears automatically
```

Custom configuration:
```php
$config = ShellConfig::fromArray([
    'showPrompt' => true,
    'promptQuietThreshold' => 3.0,
]);
$shell = new MqttShellClient($transport, 'mqtt> ', [], $config);
```

Disable prompt (for scripting):
```php
$config = ShellConfig::fromArray(['showPrompt' => false]);
```
