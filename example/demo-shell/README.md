# MQTT Shell - User Guide

> **Full Documentation:** See [docs/INTERACTIVE_SHELL.md](../../docs/INTERACTIVE_SHELL.md) for comprehensive command reference and filter syntax.

A comprehensive guide for using the MQTT Debug Shell's JSON visualization and message inspection features.

---

## Quick Start

### Running the Demo Shell
```bash
php example/demo-shell/run-demo.php --scenario=telemetry
```

**Available scenarios:** `iot`, `smarthome`, `alerts`, `binary`, `telemetry`, `full`, `minimal`

**Options:**
```bash
php example/demo-shell/run-demo.php --speed=fast    # Fast messages (100ms)
php example/demo-shell/run-demo.php --speed=slow    # Slow messages (2s)
php example/demo-shell/run-demo.php -i              # Interactive mode
php example/demo-shell/run-demo.php -f "where topic like 'sensors/#'"  # With filter
```

### Command Line Options

| Option | Short | Description |
|--------|-------|-------------|
| `--scenario=NAME` | `-s` | Scenario preset (default: full) |
| `--speed=SPEED` | | slow (2s), normal (500ms), fast (100ms) |
| `--interactive` | `-i` | Manual message injection only |
| `--filter=EXPR` | `-f` | Initial filter expression |
| `--help` | | Show help |

### Scenario Descriptions

| Scenario | Description |
|----------|-------------|
| `iot` | Temperature, humidity, motion sensors |
| `smarthome` | Light controls, thermostat commands |
| `alerts` | Error and warning messages |
| `binary` | Binary payload examples |
| `telemetry` | Device heartbeats and metrics |
| `full` | All scenarios (default) |
| `minimal` | Just temperature and commands |

---

## Common Workflows

### Workflow 1: Fast Stream - Pause & Expand
When messages are flying by too fast to read:

```
mqtt> pause                    # Stop display (messages still recorded)
mqtt> history --limit=10       # Review recent messages with IDs
[#41] [12:34:55.123] IN  sensors/temp {"value": 23.5}
[#42] [12:34:56.789] IN  sensors/batch {"device":"sensor-001","readings":[{"t...

mqtt> expand 42                # View full message #42
mqtt> expand -3                # Or: 3rd from last
mqtt> resume                   # Continue watching
```

### Workflow 2: Step-Through Mode
For slow, careful message-by-message inspection:

```
mqtt> step                     # Enable step mode
[12:34:56.789] IN  sensors/batch {"device"... (PAUSED)
mqtt> inspect                  # View full details of current message
mqtt> next                     # Advance to next message (or just 'n')
mqtt> continue                 # Resume normal flow (or just 'c')
```

### Workflow 3: Bookmarking
Mark interesting messages for later review:

```
mqtt> bookmark                 # Bookmark last message
mqtt> bookmark 42              # Bookmark specific message
mqtt> bookmarks                # List all bookmarks
mqtt> expand @1                # View 1st bookmark
mqtt> expand @last             # View most recent bookmark
mqtt> unbookmark all           # Clear all
```

### Workflow 4: Handling Large JSON Payloads
For deeply nested JSON that floods the terminal:

```
mqtt> format depth 2           # Collapse JSON beyond level 2
mqtt> format schema            # Show structure only (no values)
mqtt> fields show temp,humidity  # Only show these fields
mqtt> jpath $.sensors.temperature  # Extract specific value
```

---

## Complete Command Reference

### Stream Control
| Command | Alias | Description |
|---------|-------|-------------|
| `pause` | `p` | Stop message display (messages still recorded) |
| `resume` | `r` | Resume message display |
| `step` | | Enable step-through mode |
| `step on` | | Enable step-through mode |
| `step off` | | Disable step-through mode |
| `step break where topic like 'sensors/#'` | | Add breakpoint for topic pattern |
| `step clear` | | Clear all breakpoints |
| `step status` | | Show step mode status and breakpoints |
| `next` | `n` | Advance to next message in step mode |
| `continue` | `c` | Resume normal flow from step mode |
| `inspect` | | View current message details in step mode |

### Message Navigation
| Command | Alias | Description |
|---------|-------|-------------|
| `expand` | | Show last message in full |
| `expand -3` | | Show 3rd from last |
| `expand 42` | | Show message #42 |
| `expand @1` | | Show 1st bookmarked message |
| `expand @last` | | Show most recent bookmark |
| `history` | `h` | Show last 20 messages with IDs |
| `history --limit=50` | | Show last 50 messages |
| `history --search=error` | | Search messages containing "error" |
| `history --topic=sensors/#` | | Filter by topic pattern |
| `last` | `l` | Show last message in detail |

### Bookmarks
| Command | Alias | Description |
|---------|-------|-------------|
| `bookmark` | `bm` | Bookmark last message |
| `bookmark 42` | | Bookmark message #42 |
| `bookmarks` | | List all bookmarks |
| `unbookmark 1` | | Remove bookmark @1 |
| `unbookmark all` | | Clear all bookmarks |

### Output Format Control
| Command | Description |
|---------|-------------|
| `format` | Show current format settings |
| `format compact` | Single-line output (default) |
| `format table` | Tabular output |
| `format vertical` | Detailed output (like MySQL `\G`) |
| `format json` | JSON output for scripting |
| `format hex` | Hex dump output |
| `format depth 2` | Collapse JSON beyond level 2 |
| `format depth 0` | Unlimited depth (default) |
| `format schema` | Toggle schema mode (structure only) |

### Field Filtering
| Command | Description |
|---------|-------------|
| `fields` | Show current field filter settings |
| `fields show temp,humidity` | Only show these fields |
| `fields hide deviceId,meta` | Hide these fields |
| `fields clear` | Clear all field filters |

### JSON Path Extraction
| Command | Description |
|---------|-------------|
| `jpath $.temperature` | Extract from last message |
| `jpath $.sensors.humidity` | Nested path |
| `jpath $.readings[0].value` | Array index |
| `jpath $.devices[*].status` | All array elements |
| `jpath $.temperature -3` | From 3rd from last message |
| `jpath $.temperature 42` | From message #42 |

### Topic Filtering
| Command | Description |
|---------|-------------|
| `filter where topic like 'sensors/#'` | Filter by topic (MQTT wildcards) |
| `filter where direction = 'incoming'` | Show only incoming messages |
| `filter where direction = 'outgoing'` | Show only outgoing messages |
| `filter where qos = 2` | Filter by QoS level |
| `filter where topic like 'sensors/#' and qos >= 1` | Combine conditions |
| `filter clear` | Clear all filters |

### Statistics & Monitoring
| Command | Description |
|---------|-------------|
| `stats` | Show message statistics |
| `latency` | Show latency metrics |
| `visualize tree` | Show topic tree structure |
| `visualize flow` | Show message flow timeline |
| `tree` | Alias for visualize tree |
| `flow` | Alias for visualize flow |

### MQTT Operations
| Command | Description |
|---------|-------------|
| `publish topic payload` | Publish a message |
| `publish topic payload --qos=1` | Publish with QoS |
| `publish topic payload --retain` | Publish with retain flag |
| `subscribe topic` | Subscribe to topic |
| `unsubscribe topic` | Unsubscribe from topic |

### Export & Logging
| Command | Description |
|---------|-------------|
| `export` | Export history to stdout (JSON) |
| `export --file=msgs.json` | Export history to file |
| `export --format=csv` | Export as CSV |
| `export --limit=100` | Export only last 100 messages |
| `log start mylog.txt` | Start logging to file |
| `log stop` | Stop logging |
| `clear` | Clear message history |

### System
| Command | Description |
|---------|-------------|
| `help` | Show all commands |
| `help <command>` | Show help for specific command |
| `exit` | Exit the shell |

---

## Examples

### Debugging Sensor Data
```bash
# Start with IoT scenario
php example/demo-shell/run-demo.php --scenario=iot

# Filter to temperature sensors only
mqtt> filter where topic like 'sensors/+/temperature'

# Watch for a while, then pause to inspect
mqtt> pause
mqtt> history --limit=5
mqtt> expand -1

# Extract just the temperature values
mqtt> jpath $.temp
```

### Investigating Alerts
```bash
# Run with alerts scenario
php example/demo-shell/run-demo.php --scenario=alerts

# Step through each alert
mqtt> step
mqtt> inspect
mqtt> next

# Bookmark important ones
mqtt> bookmark
mqtt> continue

# Later, review bookmarks
mqtt> bookmarks
mqtt> expand @1
```

### Handling Large Batch Messages
```bash
# When payloads are huge
mqtt> format depth 2            # Collapse deep nesting

# Or just see the structure
mqtt> format schema

# Or focus on specific fields
mqtt> fields show readings,timestamp
mqtt> fields hide metadata,debug

# Extract what you need
mqtt> jpath $.readings[*].value
```

### Interactive Testing
```bash
# Start in interactive mode
php example/demo-shell/run-demo.php --interactive

# Inject test messages manually
mqtt> inject sensors/temp {"temp": 25.5, "unit": "C"}
mqtt> inject --direction=out commands/light {"state": "on"}
mqtt> inject --qos=2 alerts/fire {"level": "critical"}
```

---

## Tips

1. **Use `\G` suffix** for vertical format on any command: `history\G`
2. **Relative expand** (`expand -3`) is faster than remembering IDs
3. **Bookmarks persist** during session - use them freely
4. **Schema mode** helps understand unfamiliar message structures
5. **Field filtering** reduces noise in verbose payloads
6. **JSON path wildcards** (`[*]`) extract from all array elements
7. **Use breakpoints** (`step break where topic like 'alerts/#'`) to pause only on specific patterns
8. **Combine filters**: `filter where topic like 'sensors/#' and qos = 1` filters by multiple criteria

---

## Command Aliases

For faster interaction, the shell supports these aliases:

| Alias | Command |
|-------|---------|
| `f` | `filter` |
| `p` | `pause` |
| `r` | `resume` |
| `s` | `stats` |
| `h` | `history` |
| `l` | `last` |
| `n` | `next` |
| `c` | `continue` |
| `bm` | `bookmark` |
| `pub` | `publish` |
| `sub` | `subscribe` |
| `unsub` | `unsubscribe` |
| `q` | `exit` |
| `viz` | `visualize` |

---

## Creating Custom Scenarios

See `scenarios/custom-scenario.php` for an example of creating
your own message scenarios.

### MessageScenario Parameters

```php
new MessageScenario(
    name: 'my_sensor',           // Unique scenario name
    topicPattern: 'sensors/{room}/temp',  // Topic with placeholders
    payloadType: 'json',         // 'json', 'string', or 'binary'
    payloadTemplate: [           // Payload structure
        'temperature' => '{value}',
        'unit' => 'C',
    ],
    qos: 0,                      // QoS level (0, 1, 2)
    direction: 'incoming',       // 'incoming' or 'outgoing'
    frequency: 1.0,              // Messages per second
    variableRanges: [            // Dynamic value ranges
        'room' => ['kitchen', 'bedroom'],
        'value' => [20.0, 30.0], // Numeric range
    ],
);
```

### Variable Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{name}` | Random value from variableRanges['name'] |
| `{timestamp}` | Current ISO 8601 timestamp |
| `{bool}` | Random true/false |
| `{uuid}` | Random UUID |
| `{onoff}` | Random "on"/"off" |

---

## Integrating Into Your Project

To use DemoTransport in your own tests or demos:

1. Copy the `src/` files to your project
2. Update namespaces as needed
3. Use DemoTransport instead of a real transport

```php
use YourNamespace\DemoTransport;
use YourNamespace\ScenarioPresets;
use Nashgao\MQTT\Shell\MqttShellClient;

$transport = new DemoTransport(messageIntervalMs: 500.0);
$transport->addScenarios(ScenarioPresets::iotSensors());

$shell = new MqttShellClient(transport: $transport);
$shell->run($input, $output);
```

---

## File Structure

```
example/demo-shell/
├── README.md               # This file (User Guide)
├── run-demo.php            # Standalone entry script
├── src/
│   ├── DemoTransport.php   # Mock transport implementation
│   ├── MessageScenario.php # Scenario data structure
│   ├── ScenarioPresets.php # Pre-built scenarios
│   ├── MessageGenerator.php # Message generation logic
│   └── InjectHandler.php   # Manual injection handler
└── scenarios/
    └── custom-scenario.php # Example custom scenario
```
