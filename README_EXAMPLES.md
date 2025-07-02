# MQTT Examples and Tools

This document provides an overview of the examples and monitoring tools available in this MQTT package.

## 📚 Examples

The `example/` directory contains comprehensive examples demonstrating various aspects of the MQTT package:

### 1. Basic Client Example (`basic_client_example.php`)
**Purpose**: Demonstrates fundamental MQTT operations
- Basic client configuration
- Connecting to MQTT broker
- Publishing messages
- Subscribing to topics
- Error handling

**Usage**:
```bash
php example/basic_client_example.php
```

### 2. Advanced Metrics Example (`advanced_metrics_example.php`)
**Purpose**: Shows comprehensive metrics collection and monitoring
- Connection metrics tracking
- Performance monitoring
- Publishing statistics
- Subscription analytics
- Validation metrics
- Health monitoring
- Data export functionality

**Usage**:
```bash
php example/advanced_metrics_example.php
```

### 3. Pool Management Example (`pool_management_example.php`)
**Purpose**: Demonstrates MQTT connection pool management
- Pool configuration and initialization
- Load balancing across connections
- Health checking and monitoring
- Connection resilience and failover
- Pool statistics and maintenance

**Usage**:
```bash
php example/pool_management_example.php
```

### 4. Security & Validation Example (`security_validation_example.php`)
**Purpose**: Shows security best practices and validation
- Configuration validation
- Topic security validation
- Payload sanitization
- SSL/TLS configuration
- Authentication validation
- Security recommendations

**Usage**:
```bash
php example/security_validation_example.php
```

## 🔧 Monitoring Tools

### 1. MQTT Monitor (`bin/mqtt-monitor`)
**Purpose**: Command-line monitoring tool with multiple modes

**Features**:
- Real-time htop-style monitoring
- Snapshot mode for one-time metrics
- Export functionality (JSON, CSV)
- Multiple output formats
- Customizable refresh intervals

**Usage**:
```bash
# Real-time monitor
./bin/mqtt-monitor

# Generate JSON snapshot
./bin/mqtt-monitor --mode=snapshot --format=json

# Export to CSV
./bin/mqtt-monitor --mode=export --format=csv --output=metrics.csv

# Help
./bin/mqtt-monitor --help
```

### 2. MQTT Monitor Pro (`bin/mqtt-monitor-pro`)
**Purpose**: Advanced terminal UI for monitoring

**Features**:
- Beautiful ASCII art interface
- Multiple themes (dark, light, cyberpunk)
- Interactive navigation
- Live charts and sparklines
- Demo mode for testing
- Real-time metrics visualization

**Usage**:
```bash
# Start with default theme
./bin/mqtt-monitor-pro

# Use cyberpunk theme with demo data
./bin/mqtt-monitor-pro --theme=cyberpunk --demo

# Help
./bin/mqtt-monitor-pro --help
```

## 🚀 Getting Started

1. **Install dependencies**:
   ```bash
   composer install
   ```

2. **Run basic example**:
   ```bash
   php example/basic_client_example.php
   ```

3. **Try monitoring tools**:
   ```bash
   ./bin/mqtt-monitor --help
   ./bin/mqtt-monitor-pro --demo
   ```

## 🛠️ Development

### Running Tests
```bash
composer test
```

### Static Analysis
```bash
composer analyse
```

### Code Style
```bash
composer cs-fix
```

## 📋 Example Output

### Basic Client Example
```
🚀 Basic MQTT Client Example
==================================================

📝 Creating client configuration...
   ✅ Host: test.mosquitto.org
   ✅ Port: 1883
   ✅ Client ID: php_basic_example_12345

🔌 Creating MQTT client...
🌐 Connecting to MQTT broker...
   ✅ Connected successfully!

📥 Subscribing to topics...
   ✅ Subscribed to: test/php/basic/+

📤 Publishing messages...
   ✅ Published to test/php/basic/temperature: {"temperature":23.5,"unit":"C","timestamp":1751431642}...
   ✅ Published to test/php/basic/humidity: {"humidity":65.2,"unit":"%","timestamp":1751431642}...
   ✅ Published to test/php/basic/status: {"status":"online","device_id":"sensor_001","timestamp":1751431642}...

📊 Example completed successfully!
💡 Check your MQTT client or dashboard to see the published messages.

🔌 Disconnecting...
   ✅ Disconnected successfully!
```

### Metrics JSON Output
```json
{
    "timestamp": "2025-07-02T04:47:22+00:00",
    "connection": {
        "total_attempts": 10,
        "successful_connections": 9,
        "failed_connections": 1,
        "success_rate": 0.9,
        "average_connection_time": 0.108
    },
    "publish": {
        "total_attempts": 10,
        "successful": 8,
        "failed": 2,
        "success_rate": 80,
        "unique_topics_count": 8
    }
}
```

## 🏗️ Architecture

The examples demonstrate the following architectural components:

1. **Client Management**: Connection handling and lifecycle
2. **Pool Management**: Resource pooling and load balancing
3. **Metrics Collection**: Comprehensive monitoring and analytics
4. **Security Validation**: Input validation and security checks
5. **Error Handling**: Graceful error recovery and reporting
6. **Configuration Management**: Flexible configuration system

## 📝 Notes

- All examples include comprehensive error handling
- The monitoring tools support both interactive and non-interactive environments
- Examples use the public test.mosquitto.org broker for demonstration
- All tools support help commands for detailed usage information
- The package includes both simple and advanced monitoring options

## 🔍 Troubleshooting

### Common Issues

1. **Connection failures**: Check network connectivity and broker availability
2. **Permission errors**: Ensure proper file permissions for export functionality
3. **Missing extensions**: Install required PHP extensions (swoole, pcntl, posix)
4. **Terminal size**: Monitor tools work best with terminals 120x30 or larger

### Debug Mode

Most examples include verbose output to help with troubleshooting. Check the console output for detailed error messages and suggestions.