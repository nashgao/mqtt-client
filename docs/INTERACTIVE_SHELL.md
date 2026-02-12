# MQTT Interactive Shell

A powerful command-line interface for debugging, monitoring, and analyzing MQTT message streams with SQL-like filtering, real-time statistics, and advanced debugging capabilities.

## Table of Contents

- [Quick Start](#quick-start)
- [Command Reference](#command-reference)
  - [Monitoring & Filtering](#monitoring--filtering)
  - [History & Navigation](#history--navigation)
  - [MQTT Operations](#mqtt-operations)
  - [Advanced Debugging](#advanced-debugging)
  - [Visualization](#visualization)
  - [System Commands](#system-commands)
- [Filter System](#filter-system)
- [Default Aliases](#default-aliases)
- [Demo Mode](#demo-mode)
- [Examples](#examples)
- [Integration Guide](#integration-guide)

---

## Quick Start

### Running the Demo

The fastest way to explore the shell is through demo mode:

```bash
# Full demo with all scenarios
php example/demo-shell/run-demo.php

# Specific scenario (iot, smarthome, alerts, telemetry, binary)
php example/demo-shell/run-demo.php --scenario=iot

# Fast message stream
php example/demo-shell/run-demo.php --speed=fast
```

### Example Session

```
mqtt> filter where topic like 'sensors/#'
Filter set: topic like 'sensors/#'

mqtt> stats
Messages: 142 | Rate: 12.3/s | Topics: 8

mqtt> pause
Message display paused

mqtt> history --limit=5
#138 sensors/temp/living    {"temp": 22.5}
#139 sensors/humidity/bath  {"humidity": 65}
#140 sensors/temp/bedroom   {"temp": 21.0}
#141 sensors/motion/front   {"detected": true}
#142 sensors/temp/kitchen   {"temp": 23.1}

mqtt> expand -1
Message #142
  Topic: sensors/temp/kitchen
  QoS: 1
  Payload: {"temp": 23.1}
  Timestamp: 2024-12-05 10:30:15

mqtt> resume
Message display resumed
```

### Key Features

- **SQL-like filtering** - Filter messages with familiar WHERE syntax
- **Real-time statistics** - Track message rates, topic distribution, latency
- **Message history** - Browse, search, and export captured messages
- **Step-through debugging** - Pause and inspect messages one at a time
- **JSON payload tools** - Extract fields, filter data, format output
- **Topic visualization** - Tree views and flow timelines
- **Rule engine** - Automate actions based on message patterns

---

## Command Reference

### Monitoring & Filtering

#### `filter` - SQL-Like Message Filtering

Set powerful filters using SQL-like syntax with MQTT wildcard support.

```bash
# Basic filter
filter where topic like 'sensors/#'

# Multiple conditions
filter where topic like 'sensors/#' and qos >= 1

# Show current filter
filter show

# Clear filter
filter clear

# Save/load presets
filter save mypreset
filter apply mypreset
filter list
```

See [Filter System](#filter-system) for complete syntax documentation.

---

#### `pause` / `resume` - Toggle Message Display

```bash
pause                    # Stop displaying incoming messages
resume                   # Resume message display
```

Messages continue to be captured while paused - only display is affected.

---

#### `stats` - Real-Time Statistics

```bash
stats                    # Show overview statistics
stats topics             # Show per-topic breakdown
stats qos                # Show QoS distribution
stats reset              # Reset all counters
```

**Output Example:**
```
Messages: 1,234 | Rate: 45.2/s | Topics: 12
Incoming: 1,100 | Outgoing: 134
QoS 0: 800 | QoS 1: 400 | QoS 2: 34
```

---

#### `format` - Message Display Format

```bash
format compact           # Single-line format (default)
format table             # Tabular format
format vertical          # Detailed format (like MySQL \G)
format json              # JSON format for scripting
format hex               # Hex dump for binary payloads

format depth 2           # Collapse JSON beyond level 2
format depth 0           # Unlimited depth (default)
```

**Vertical Format Suffix:**
```bash
history\G                # Show history in vertical format
last\G                   # Show last message vertically
```

---

#### `log` - File Logging

```bash
log start messages.log   # Start logging to file
log stop                 # Stop logging
log status               # Show logging status
```

---

#### `latency` - Latency Tracking

```bash
latency                  # Show latency statistics
latency histogram        # Show latency distribution
latency reset            # Reset latency data
```

---

### History & Navigation

#### `history` - Browse Message History

```bash
history                  # Show recent messages
history --limit=50       # Show last 50 messages
history --search=temp    # Search by content
history --topic=sensors/#  # Filter by topic pattern
```

---

#### `last` - Show Last Message

```bash
last                     # Show last message in detail
last\G                   # Vertical format
```

---

#### `expand` - View Message Details

Navigate and inspect messages by position, ID, or bookmark.

```bash
# By relative position
expand                   # Last message
expand -1                # Last message
expand -3                # 3rd from last

# By absolute ID
expand 42                # Message #42

# From bookmarks
expand @1                # Bookmark #1
expand @last             # Most recent bookmark
```

---

#### `bookmark` - Mark Messages for Review

```bash
bookmark                 # Bookmark last message
bookmark 42              # Bookmark message #42
bookmarks                # List all bookmarks
unbookmark 1             # Remove bookmark #1
unbookmark all           # Remove all bookmarks
```

**Aliases:** `bm` for `bookmark`

---

#### `export` - Export Message History

```bash
export                   # Export to default file
export --file=msgs.json  # Export to specific file
export --format=csv      # Export as CSV
export --format=json     # Export as JSON (default)
```

---

#### `clear` - Clear Message History

```bash
clear                    # Clear all captured messages
```

---

### MQTT Operations

#### `publish` - Send Messages

```bash
publish topic/path "payload"
publish topic/path '{"json": "data"}' --qos=1
publish topic/path "message" --qos=2 --retain
```

**Options:**
- `--qos=0|1|2` - Quality of Service level
- `--retain` - Set retain flag

---

#### `subscribe` / `unsubscribe` - Manage Subscriptions

```bash
subscribe sensors/#              # Subscribe to topic pattern
subscribe sensors/temp --qos=1   # Subscribe with QoS

unsubscribe sensors/#            # Unsubscribe from topic

subscriptions                    # List active subscriptions
```

---

### Advanced Debugging

#### `step` / `next` / `continue` - Step-Through Debugging

Enable step mode to pause at each message for inspection.

```bash
step                     # Toggle step mode
step on                  # Enable step mode
step off                 # Disable step mode

step break topic:sensors/#   # Add breakpoint
step clear               # Clear all breakpoints
step status              # Show step mode status

next                     # Advance to next message (alias: n)
continue                 # Resume normal flow (alias: c)
inspect                  # Show current message details
```

**Breakpoint Syntax:**
```bash
step break topic:sensors/#       # Break on topic pattern
step break qos:2                 # Break on QoS level
step break payload:error         # Break on payload content
```

---

#### `hex` / `hexdump` - Binary Payload Viewer

```bash
hex                      # Show last message as hex
hex 42                   # Show message #42 as hex
hex last                 # Explicit last message
hex 42 --bytes=64        # Limit to 64 bytes
```

**Output Example:**
```
00000000  7b 22 74 65 6d 70 22 3a  20 32 32 2e 35 7d        |{"temp": 22.5}|
```

---

#### `fields` - JSON Field Filtering

Control which fields are displayed in JSON payloads.

```bash
fields show temp,humidity    # Only show these fields
fields hide deviceId,meta    # Hide these fields
fields clear                 # Show all fields (default)
```

---

#### `jpath` - JSON Path Extraction

Extract specific values from JSON payloads using path expressions.

```bash
jpath $.temperature          # Top-level field
jpath $.sensors.humidity     # Nested path
jpath $.readings[0].value    # Array index
jpath $.devices[*].status    # All array elements

jpath $.temperature -3       # From 3rd from last message
jpath $.temperature 42       # From message #42
```

---

#### `rule` - Automation Rules

Create rules that trigger actions based on message patterns.

```bash
rule list                    # List all rules
rule add alerts "where topic like 'alerts/#'"
rule enable alerts           # Enable rule
rule disable alerts          # Disable rule
rule delete alerts           # Delete rule
rule test "where qos = 2" 42 # Test rule against message #42
```

---

### Visualization

#### `tree` - Topic Tree View

```bash
tree                     # Show full topic tree
tree --depth=2           # Limit depth
```

**Output Example:**
```
sensors/
├── temp/
│   ├── living (12 msgs)
│   ├── bedroom (8 msgs)
│   └── kitchen (15 msgs)
└── humidity/
    └── bathroom (5 msgs)
```

---

#### `flow` - Message Flow Timeline

```bash
flow                     # Show message flow
flow --last=20           # Show last 20 messages
```

---

#### `visualize` / `viz` - Combined Visualization

```bash
visualize                # Show both tree and flow
viz                      # Alias
```

---

### System Commands

#### `pool` - Connection Pool Management

```bash
pool list                # List available pools
pool status              # Show pool status
pool switch <name>       # Switch active pool
pool connections         # Show connection details
```

---

#### `help` - Command Help

```bash
help                     # Show all commands by category
help filter              # Detailed help for specific command
help <command>           # Any command's detailed help
```

---

#### `exit` - Quit Shell

```bash
exit                     # Exit the shell
quit                     # Alias
q                        # Alias
```

---

## Filter System

The filter system provides SQL-like query syntax for filtering MQTT messages in real-time.

### Basic Syntax

```sql
filter where <condition> [and|or <condition>...]
```

### Available Fields

**MQTT Protocol Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `topic` | string | MQTT topic path (supports + and # wildcards) |
| `qos` | int (0-2) | Quality of Service level |
| `retain` | bool | Retain flag |
| `dup` | bool | Duplicate flag |

**Shell Metadata:**

| Field | Type | Description |
|-------|------|-------------|
| `timestamp` | datetime | When the shell captured this message |
| `direction` | string | Message direction (`incoming`, `outgoing`) |
| `pool` | string | Connection pool name |
| `type` | string | Message type |

**Payload Access:**

| Field | Type | Description |
|-------|------|-------------|
| `payload.*` | any | Access JSON payload fields |
| `payload.temp` | - | Example: temperature field |
| `payload.sensor.value` | - | Example: nested sensor value |

### Comparison Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `=` | Equal | `qos = 1` |
| `!=` | Not equal | `direction != 'outgoing'` |
| `>` | Greater than | `qos > 0` |
| `<` | Less than | `payload.temp < 30` |
| `>=` | Greater or equal | `qos >= 1` |
| `<=` | Less or equal | `payload.humidity <= 80` |

### Pattern Matching

```sql
-- MQTT wildcard patterns (+ = single level, # = multi-level)
filter where topic like 'sensors/#'
filter where topic like 'sensors/+/temp'

-- Negative patterns
filter where topic not like 'debug/#'

-- SQL wildcards (% = any chars, _ = single char)
filter where topic like '%temp%'
```

### Logical Operators

```sql
-- AND - both conditions must match
filter where topic like 'sensors/#' and qos >= 1

-- OR - either condition matches
filter where topic like 'alerts/#' or qos = 2

-- NOT - negate condition
filter where not topic like 'debug/#'

-- Grouping with parentheses
filter where (topic like 'sensors/#' or topic like 'alerts/#') and qos >= 1
```

### Time-Based Filtering

```sql
-- Relative time (interval syntax)
filter where timestamp > now() - interval '5m'    -- Last 5 minutes
filter where timestamp > now() - interval '2h'    -- Last 2 hours
filter where timestamp > now() - interval '30s'   -- Last 30 seconds

-- Time-only patterns (assumes today)
filter where timestamp > '10:30'
filter where timestamp between '10:00' and '11:00'

-- Absolute timestamp
filter where timestamp > '2024-12-05T10:30:00'
```

### Incremental Filter Building

Build complex filters step by step:

```bash
# Start with base filter
filter where topic like 'sensors/#'

# Add conditions
filter add and qos >= 1
filter add or topic like 'alerts/#'
filter add and not topic like 'debug/#'

# Remove specific clause
filter remove topic like 'debug/#'

# Show current filter
filter show
# Output: topic like 'sensors/#' and qos >= 1 or topic like 'alerts/#'
```

### Named Presets

Save and reuse filter configurations:

```bash
# Save current filter as preset
filter save critical

# Apply saved preset
filter apply critical

# List all presets
filter list

# Delete preset
filter delete critical
```

**Note:** Presets are session-only (not persisted to disk).

### Filter Commands Summary

| Command | Description |
|---------|-------------|
| `filter where <expr>` | Set new filter |
| `filter add <clause>` | Add to existing filter |
| `filter remove <clause>` | Remove from filter |
| `filter show` | Display current filter |
| `filter clear` | Remove all filters |
| `filter save <name>` | Save as preset |
| `filter apply <name>` | Load preset |
| `filter list` | List presets |
| `filter delete <name>` | Delete preset |
| `filter help` | Show filter help |

---

## Default Aliases

The shell includes these built-in aliases for faster command entry:

| Alias | Command | Description |
|-------|---------|-------------|
| `q` | `exit` | Quit shell |
| `quit` | `exit` | Quit shell |
| `?` | `help` | Show help |
| `f` | `filter` | Quick filter access |
| `p` | `pause` | Pause display |
| `r` | `resume` | Resume display |
| `s` | `stats` | Show statistics |
| `c` | `filter clear` | Clear filter |
| `h` | `history` | Show history |
| `l` | `last` | Show last message |
| `ll` | `history --limit=50` | Extended history |
| `pub` | `publish` | Publish message |
| `sub` | `subscribe` | Subscribe to topic |
| `unsub` | `unsubscribe` | Unsubscribe |
| `n` | `next` | Next (step mode) |
| `viz` | `visualize` | Show visualization |
| `g` | `filter grep:` | Quick grep filter |
| `bm` | `bookmark` | Bookmark message |

---

## Demo Mode

The demo mode provides a simulated MQTT environment for exploring shell features.

### Running Demo

```bash
# Basic demo (all scenarios)
php example/demo-shell/run-demo.php

# With options
php example/demo-shell/run-demo.php [options]
```

### Scenarios

| Scenario | Description | Topics |
|----------|-------------|--------|
| `iot` | IoT sensor data | `sensors/temp/*`, `sensors/humidity/*`, `sensors/motion/*` |
| `smarthome` | Smart home controls | `lights/*`, `thermostat/*`, `commands/*` |
| `alerts` | Error and warnings | `alerts/error/*`, `alerts/warning/*` |
| `telemetry` | Device metrics | `telemetry/heartbeat/*`, `telemetry/metrics/*` |
| `binary` | Binary payloads | `binary/data/*` |
| `full` | All scenarios (default) | All topics |
| `minimal` | Basic demo | Temperature + commands only |

### Speed Options

| Speed | Interval | Use Case |
|-------|----------|----------|
| `slow` | 2 seconds | Careful observation |
| `normal` | 500ms | Default experience |
| `fast` | 100ms | High-volume testing |

### Command Line Options

```bash
--scenario=<name>   # Select scenario (iot, smarthome, alerts, etc.)
-s <name>           # Short form

--speed=<speed>     # Set message speed (slow, normal, fast)

--interactive       # Enable manual message injection
-i                  # Short form

--filter="<expr>"   # Set initial filter
-f "<expr>"         # Short form

--help              # Show help
```

### Interactive Mode

In interactive mode, you can manually inject messages:

```bash
php example/demo-shell/run-demo.php --interactive

mqtt> inject sensors/temp/test {"temp": 25.5}
Message injected to: sensors/temp/test

mqtt> inject alerts/error/test "System failure"
Message injected to: alerts/error/test
```

### Demo Examples

```bash
# IoT scenario with fast messages
php example/demo-shell/run-demo.php --scenario=iot --speed=fast

# Smart home with initial filter
php example/demo-shell/run-demo.php -s smarthome -f "where topic like 'lights/#'"

# Interactive telemetry testing
php example/demo-shell/run-demo.php --scenario=telemetry --interactive

# Minimal demo for learning
php example/demo-shell/run-demo.php --scenario=minimal --speed=slow
```

---

## Examples

### Filtering Sensor Data

```bash
# Filter temperature sensors only
mqtt> filter where topic like 'sensors/temp/#'

# Add QoS constraint
mqtt> filter add and qos >= 1

# Exclude debug messages
mqtt> filter add and not topic like 'debug/#'

# Check current filter
mqtt> filter show
topic like 'sensors/temp/#' and qos >= 1 and not topic like 'debug/#'

# Save for later use
mqtt> filter save temp_sensors
```

### Setting Up Debugging Breakpoints

```bash
# Enable step mode
mqtt> step on
Step mode enabled

# Add breakpoint for alert messages
mqtt> step break topic:alerts/#

# Add breakpoint for high-priority messages
mqtt> step break qos:2

# Wait for matching message...
[BREAK] Message matches breakpoint: topic:alerts/#

mqtt> inspect
Message #156
  Topic: alerts/error/database
  QoS: 2
  Payload: {"error": "Connection timeout", "code": 504}

mqtt> continue
Resuming normal flow
```

### Analyzing JSON Payloads

```bash
# Extract specific fields
mqtt> jpath $.temperature
Message #142: 22.5
Message #145: 23.1
Message #148: 21.8

# Filter displayed fields
mqtt> fields show temp,humidity,timestamp
Showing only: temp, humidity, timestamp

# View nested data
mqtt> jpath $.sensors[*].value
Message #150: [22.5, 65, true]
```

### Creating Automation Rules

```bash
# Add rule for critical alerts
mqtt> rule add critical "where topic like 'alerts/error/#' and qos = 2"
Rule 'critical' created

# Enable the rule
mqtt> rule enable critical
Rule 'critical' enabled

# Test against existing message
mqtt> rule test "where qos = 2" 42
Message #42 matches rule

# List all rules
mqtt> rule list
  critical: where topic like 'alerts/error/#' and qos = 2 [enabled]
```

### Exporting Data for Analysis

```bash
# Filter to relevant messages
mqtt> filter where topic like 'sensors/#' and timestamp > now() - interval '1h'

# Review what will be exported
mqtt> history --limit=10

# Export to JSON
mqtt> export --file=sensors_hourly.json --format=json
Exported 156 messages to sensors_hourly.json

# Export to CSV for spreadsheet analysis
mqtt> export --file=sensors.csv --format=csv
Exported 156 messages to sensors.csv
```

### Combining Features

```bash
# Complex debugging session
mqtt> filter where topic like 'sensors/#' and payload.temp > 30
mqtt> step break topic:alerts/#
mqtt> format vertical
mqtt> log start session.log

# Now messages matching high temp OR alerts will be logged
# Alerts will trigger breakpoints for inspection

# After investigation
mqtt> filter clear
mqtt> step off
mqtt> log stop
mqtt> export --file=investigation.json
```

---

## Integration Guide

### Using Shell in Your Application

```php
use Hyperf\Mqtt\Shell\MqttShellClient;
use Hyperf\Mqtt\Shell\Transport\TransportInterface;

// Create shell with your transport
$shell = new MqttShellClient($transport);

// Add custom aliases
$shell->addAlias('sensors', 'filter where topic like \'sensors/#\'');
$shell->addAlias('alerts', 'filter where topic like \'alerts/#\'');

// Run the shell
$shell->run();
```

### Custom Transport Implementation

```php
use Hyperf\Mqtt\Shell\Transport\TransportInterface;

class MyTransport implements TransportInterface
{
    public function read(): ?string
    {
        // Return user input or null
    }

    public function write(string $output): void
    {
        // Display output to user
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
```

### Extending with Custom Handlers

```php
use Hyperf\Mqtt\Shell\Handler\HandlerInterface;
use Hyperf\Mqtt\Shell\ShellContext;

class MyCustomHandler implements HandlerInterface
{
    public function getCommand(): string
    {
        return 'mycommand';
    }

    public function getDescription(): string
    {
        return 'My custom command description';
    }

    public function handle(string $args, ShellContext $context): string
    {
        // Implement command logic
        return "Command executed with: {$args}";
    }

    public function getHelp(): string
    {
        return <<<HELP
        Usage: mycommand [options]

        Options:
          --option1    First option
          --option2    Second option
        HELP;
    }
}

// Register handler
$shell->registerHandler(new MyCustomHandler());
```

### Programmatic Filter Application

```php
use Hyperf\Mqtt\Shell\Filter\FilterParser;
use Hyperf\Mqtt\Shell\Filter\FilterEngine;

$parser = new FilterParser();
$filter = $parser->parse("topic like 'sensors/#' and qos >= 1");

$engine = new FilterEngine();
$engine->setFilter($filter);

// Check if message matches
if ($engine->matches($message)) {
    // Process matching message
}
```

---

## Troubleshooting

### Common Issues

**Filter not matching expected messages:**
- Check topic pattern syntax (`+` for single level, `#` for multi-level)
- Verify field names are correct (`topic`, not `topics`)
- Use `filter show` to see current filter

**Messages not displaying:**
- Check if display is paused (`resume` to continue)
- Verify filter isn't too restrictive (`filter clear`)
- Check subscription status (`subscriptions`)

**Step mode not triggering:**
- Ensure step mode is enabled (`step status`)
- Verify breakpoint patterns match (`step break` syntax)
- Check if messages match current filter first

**History seems empty:**
- History only captures messages since shell started
- Use `clear` carefully - it removes all captured messages
- Check filter - history respects active filters

**Debug tap server messages not appearing in console:**
- By default, `DebugTapServer` prefers `StdoutLoggerInterface` for console output
- If you have `hyperf/logger` installed, the PSR `LoggerInterface` may bind to a file logger
- Override the DI binding to ensure stdout output:

```php
// config/autoload/dependencies.php
use Nashgao\MQTT\Debug\DebugTapServer;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;

return [
    DebugTapServer::class => function ($container) {
        return new DebugTapServer(
            $container->get(ConfigInterface::class),
            null, // Skip PSR LoggerInterface
            $container->get(StdoutLoggerInterface::class),
        );
    },
];
```

### Getting Help

```bash
# General help
mqtt> help

# Command-specific help
mqtt> help filter
mqtt> help step
mqtt> help export

# Filter syntax help
mqtt> filter help
```
