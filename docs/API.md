# Shell Components API Reference

This document provides API documentation for the MQTT Debug Shell components.

## Table of Contents

- [FilterExpression](#filterexpression)
- [MessageHistory](#messagehistory)
- [MqttMessageFormatter](#mqttmessageformatter)
- [StatsCollector](#statscollector)

---

## FilterExpression

SQL-like filter expressions for MQTT message filtering with wildcard support.

**Namespace:** `Nashgao\MQTT\Shell\Filter`

### Constructor

```php
$filter = new FilterExpression();
```

### Methods

#### where(string $expression): self

Sets the base filter expression, replacing any existing filter.

```php
$filter->where("topic like 'sensors/#'");
$filter->where("qos = 1");
```

#### addAnd(string $expression): self

Adds an AND clause to the existing filter.

```php
$filter->where("topic like 'sensors/#'")
       ->addAnd("qos >= 1");
```

#### addOr(string $expression): self

Adds an OR clause to the existing filter.

```php
$filter->where("topic like 'sensors/#'")
       ->addOr("topic like 'devices/#'");
```

#### addNot(string $expression): self

Adds an AND NOT clause to exclude matches.

```php
$filter->where("topic like 'sensors/#'")
       ->addNot("topic like 'debug/#'");
```

#### remove(string $expression): self

Removes a specific clause by its expression.

```php
$filter->remove("qos = 1");
```

#### clear(): self

Removes all filter clauses.

```php
$filter->clear();
```

#### clone(): self

Creates an independent copy of the filter expression.

```php
$copy = $filter->clone();
$copy->addAnd("retain = true"); // Original unchanged
```

#### matches(Message $message): bool

Tests if a message matches the filter criteria.

```php
if ($filter->matches($message)) {
    // Message matches the filter
}
```

#### getClauses(): array

Returns all filter clauses.

```php
$clauses = $filter->getClauses();
```

#### hasFilters(): bool

Checks if any filters are defined.

```php
if ($filter->hasFilters()) {
    // Apply filtering
}
```

#### toSql(): string

Returns the SQL-like representation of the filter.

```php
echo $filter->toSql();
// Output: topic like 'sensors/#' AND qos = 1
```

### Supported Operators

| Operator | Example | Description |
|----------|---------|-------------|
| `like` | `topic like 'sensors/#'` | MQTT wildcard pattern match |
| `=` | `qos = 1` | Exact match |
| `!=` | `retain != true` | Not equal |
| `>`, `>=` | `qos >= 1` | Greater than |
| `<`, `<=` | `size < 1024` | Less than |

### MQTT Wildcards

- `+` matches a single level: `sensors/+/temperature`
- `#` matches multiple levels: `sensors/#`

---

## MessageHistory

Circular buffer for storing and searching MQTT messages.

**Namespace:** `Nashgao\MQTT\Shell\History`

### Constructor

```php
$history = new MessageHistory(500); // Max 500 messages
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$maxMessages` | int | 500 | Maximum messages to retain |

### Methods

#### add(Message $message): void

Adds a message to the history buffer.

```php
$history->add($message);
```

#### getLast(int $count): array

Returns the N most recent messages.

```php
$recent = $history->getLast(10);
foreach ($recent as $msg) {
    echo $msg->payload['topic'];
}
```

#### getLatest(): ?Message

Returns the most recent message, or null if empty.

```php
$latest = $history->getLatest();
if ($latest !== null) {
    echo $latest->payload['message'];
}
```

#### getLatestId(): ?int

Returns the ID (index) of the most recent message.

```php
$id = $history->getLatestId();
```

#### get(int $id): ?Message

Retrieves a message by its ID.

```php
$message = $history->get(42);
```

#### count(): int

Returns the number of messages in history.

```php
echo "History contains " . $history->count() . " messages";
```

#### search(string $topic, ?int $limit = null): array

Searches messages by topic pattern with MQTT wildcard support.

```php
$matches = $history->search('sensors/+/temperature', 10);
```

#### clear(): void

Removes all messages from history.

```php
$history->clear();
```

#### setTopicMatcher(TopicMatcherInterface $matcher): void

Sets a custom topic matcher implementation.

```php
$history->setTopicMatcher(new CustomTopicMatcher());
```

---

## MqttMessageFormatter

Formats MQTT messages for debug shell output.

**Namespace:** `Nashgao\MQTT\Shell\Formatter`

### Constructor

```php
$formatter = new MqttMessageFormatter();
$formatter = new MqttMessageFormatter($shellConfig);
```

### Format Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `FORMAT_COMPACT` | `'compact'` | Single-line output |
| `FORMAT_TABLE` | `'table'` | Tabular format |
| `FORMAT_VERTICAL` | `'vertical'` | Key-value pairs |
| `FORMAT_JSON` | `'json'` | JSON output |
| `FORMAT_HEXDUMP` | `'hex'` | Hexadecimal dump |

### Methods

#### format(Message $message): string

Formats a message according to current settings.

```php
$output = $formatter->format($message);
echo $output;
```

#### setFormat(string $format): void

Sets the output format.

```php
$formatter->setFormat('table');
$formatter->setFormat('t'); // Alias
```

#### getFormat(): string

Returns the current format.

```php
echo $formatter->getFormat(); // 'compact'
```

#### setDepthLimit(int $depth): void

Sets the JSON depth limit (0 = unlimited).

```php
$formatter->setDepthLimit(3); // Only show 3 levels deep
```

#### getDepthLimit(): int

Returns the current depth limit.

```php
echo $formatter->getDepthLimit(); // 0
```

#### setSchemaMode(bool $enabled): void

Enables/disables schema mode (shows structure, not values).

```php
$formatter->setSchemaMode(true);
```

#### isSchemaMode(): bool

Checks if schema mode is enabled.

```php
if ($formatter->isSchemaMode()) {
    // Output will show types instead of values
}
```

#### setColorEnabled(bool $enabled): void

Enables/disables ANSI color output.

```php
$formatter->setColorEnabled(false); // Plain text
```

#### setPayloadTruncation(int $length): void

Sets maximum payload display length.

```php
$formatter->setPayloadTruncation(200);
```

### Format Aliases

| Alias | Full Name |
|-------|-----------|
| `c` | `compact` |
| `t` | `table` |
| `v` | `vertical` |
| `j` | `json` |
| `h` | `hex` |

---

## StatsCollector

Collects and reports MQTT message statistics.

**Namespace:** `Nashgao\MQTT\Shell\Stats`

### Constructor

```php
$stats = new StatsCollector();
```

### Methods

#### recordMessage(Message $message): void

Records a message for statistics.

```php
$stats->recordMessage($message);
```

#### getMessageCount(): int

Returns total message count.

```php
echo $stats->getMessageCount();
```

#### getTopicsCount(): int

Returns unique topic count.

```php
echo $stats->getTopicsCount();
```

#### getMessageRate(): float

Returns messages per second.

```php
echo $stats->getMessageRate() . " msg/s";
```

#### getTopTopics(int $limit = 10): array

Returns most active topics.

```php
$topTopics = $stats->getTopTopics(5);
foreach ($topTopics as $topic => $count) {
    echo "$topic: $count messages\n";
}
```

#### getLatencyStats(): array

Returns latency statistics.

```php
$latency = $stats->getLatencyStats();
echo "Avg: " . $latency['avg'] . "ms";
echo "P99: " . $latency['p99'] . "ms";
```

#### reset(): void

Resets all statistics.

```php
$stats->reset();
```

---

## Usage Examples

### Complete Filtering Workflow

```php
use Nashgao\MQTT\Shell\Filter\FilterExpression;
use Nashgao\MQTT\Shell\History\MessageHistory;
use Nashgao\MQTT\Shell\Formatter\MqttMessageFormatter;

// Set up components
$history = new MessageHistory(1000);
$filter = new FilterExpression();
$formatter = new MqttMessageFormatter();

// Configure filter
$filter->where("topic like 'sensors/#'")
       ->addAnd("qos >= 1")
       ->addNot("topic like 'sensors/debug/#'");

// Configure formatter
$formatter->setFormat('table');
$formatter->setColorEnabled(true);

// Process messages
foreach ($incomingMessages as $message) {
    $history->add($message);

    if ($filter->matches($message)) {
        echo $formatter->format($message);
    }
}

// Search history
$temperatureMessages = $history->search('sensors/+/temperature', 10);
```

### Statistics Dashboard

```php
use Nashgao\MQTT\Shell\Stats\StatsCollector;

$stats = new StatsCollector();

// Record messages
foreach ($messages as $msg) {
    $stats->recordMessage($msg);
}

// Display dashboard
echo "Total: " . $stats->getMessageCount() . " messages\n";
echo "Topics: " . $stats->getTopicsCount() . " unique\n";
echo "Rate: " . $stats->getMessageRate() . " msg/s\n";
echo "\nTop Topics:\n";
foreach ($stats->getTopTopics(5) as $topic => $count) {
    echo "  $topic: $count\n";
}
```
