# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nashgao/MQTT** is a PHP MQTT client library that provides coroutine-based MQTT 5 functionality integrated with the Hyperf framework. Built on top of [simps/mqtt](https://github.com/simps/mqtt), this library focuses on enabling MQTT 5's shared subscription feature for load-balanced message consumption across multiple subscribers.

### Design Philosophy

The library was specifically designed to leverage MQTT 5's **Shared Subscription** feature, which allows multiple subscribers to subscribe to the same topic while ensuring only one subscriber receives each message (load balancing). This is particularly useful for:

- **Queue Topics**: `$queue/topic` - Simple load balancing
- **Shared Topics**: `$share/group/topic` - Group-based load balancing with custom group names

```
                                               [subscriber1] got msg1
         msg1, msg2, msg3                    /
[publisher] ---------------> "$share/g/topic" -- [subscriber2] got msg2
                                             \
                                               [subscriber3] got msg3
```

## Architecture Overview

### Core Components

#### Client Layer
- **`Client`** (`src/Client.php`): Main facade providing proxy methods for all MQTT operations
- **`ClientFactory`** (`src/ClientFactory.php`): Factory for creating client instances with proper pool management
- **`ClientProxy`** (`src/ClientProxy.php`): Proxy implementation handling method delegation and connection management

#### Connection Management
- **`MQTTConnection`** (`src/MQTTConnection.php`): Wrapper around simps/mqtt client with Hyperf coroutine integration
- **`MQTTPool`** (`src/Pool/MQTTPool.php`): Connection pool implementation for efficient resource management
- **`PoolFactory`** (`src/Pool/PoolFactory.php`): Factory for creating and managing connection pools

#### Configuration System
- **`ClientConfig`** (`src/Config/ClientConfig.php`): Connection configuration (broker, credentials, timeouts)
- **`TopicConfig`** (`src/Config/TopicConfig.php`): Topic-specific configuration for subscriptions and publishing
- **`ConfigProvider`** (`src/ConfigProvider.php`): Hyperf service provider for dependency injection and event listeners

#### Event-Driven Architecture
The library uses Hyperf's event system for decoupled MQTT operations:

**Command Events** (trigger actions):
- **`PublishEvent`**: Dispatch to publish messages
- **`SubscribeEvent`**: Dispatch to subscribe to topics

**Notification Events** (reactions to MQTT events):
- **`OnReceiveEvent`**: Triggered when messages are received
- **`OnPublishEvent`**: Triggered when messages are published
- **`OnSubscribeEvent`**: Triggered when subscriptions are established
- **`OnDisconnectEvent`**: Triggered on connection loss

#### Listeners
- **`PublishListener`**: Handles PublishEvent dispatching
- **`SubscribeListener`**: Handles SubscribeEvent dispatching
- **`AfterWorkerStartListener`**: Handles auto-subscription on server startup
- **`OnReceiveListener`**: Handles incoming message processing
- **`ServerIdListener`**: Manages server identification for clustering

### Multi-Subscription Support

The library supports multiple subscription patterns:

1. **Auto-subscription**: Configure topics in `mqtt.php` with `auto_subscribe: true`
2. **Multi-subscriber**: Use `enable_multisub: true` to create multiple clients for the same topic
3. **Shared subscriptions**: Configure `enable_share_topic: true` with group names
4. **Queue subscriptions**: Enable `enable_queue_topic: true` for simple load balancing

## Configuration

### MQTT Broker Configuration
Key environment variables:
- `MQTT_HOST`: MQTT broker host (default: localhost)
- `MQTT_PORT`: MQTT broker port (default: 1883)
- `MQTT_USERNAME`: MQTT username (default: admin)
- `MQTT_PASSWORD`: MQTT password (default: public)
- `MQTT_PROTOCOL_LEVEL`: MQTT protocol level (default: 5)

### Connection Pool Configuration
Located in `config/autoload/mqtt.php`:
- `min_connections`: Minimum pool connections
- `max_connections`: Maximum pool connections
- `connect_timeout`: Connection timeout
- `wait_timeout`: Wait timeout for pool exhaustion

## Usage Patterns

### Publishing Messages
```php
// Via event dispatch
$dispatcher->dispatch(new PublishEvent('topic/test', 'message', 2));

// Direct client usage
$client = make(Client::class);
$client->publish('topic/test', 'message', 2);
```

### Subscribing to Topics
```php
// Via event dispatch
$event = new SubscribeEvent(topicConfigs: [
    new TopicConfig(['topic' => 'topic/test', 'qos' => 2])
]);
$dispatcher->dispatch($event);

// Direct client usage
$client->subscribe(['topic/test' => ['qos' => 2]]);
```

### Auto-subscription
Configure topics in `config/autoload/mqtt.php` with `auto_subscribe: true` to automatically subscribe when the Hyperf server starts.

## Dependencies

- PHP >= 8.2
- Hyperf Framework ~3.1
- simps/mqtt ~2.0
- Swoole >= 5.0 or Swow >= 1.5 (extensions)

## Testing Requirements

The test suite requires:
- PHPUnit with co-phpunit for coroutine testing
- Hyperf testing framework
- Redis for integration tests (optional)
- MQTT broker running for integration tests