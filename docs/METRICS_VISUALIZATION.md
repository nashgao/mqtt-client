# MQTT Metrics Visualization & Real-time Monitoring

This document describes the comprehensive metrics visualization and real-time monitoring system for the MQTT library.

## Overview

The MQTT library now includes powerful visualization and monitoring capabilities:

- **Real-time htop-style monitoring** - Live dashboard with keyboard controls
- **Multiple export formats** - Dashboard, JSON, CSV outputs  
- **Method chaining** - Fluent API for all metrics operations
- **In-place updates** - Terminal display updates without scrolling
- **Interactive controls** - Keyboard shortcuts for navigation and control

## Key Components

### 1. MetricsVisualizer (`src/Utils/MetricsVisualizer.php`)

Main visualization engine supporting multiple output formats:

```php
use Nashgao\MQTT\Utils\MetricsVisualizer;

$visualizer = new MetricsVisualizer();
$visualizer
    ->setConnectionMetrics($connectionMetrics)
    ->setPerformanceMetrics($performanceMetrics)
    ->setPublishMetrics($publishMetrics)
    ->setSubscriptionMetrics($subscriptionMetrics)
    ->setValidationMetrics($validationMetrics);

// Generate different outputs
echo $visualizer->generateDashboard();        // ASCII dashboard
echo $visualizer->generateJson();             // JSON export
echo $visualizer->generateCsv();              // CSV format
echo $visualizer->generateRealTimeDisplay();  // Live display
```

### 2. MqttMonitor (`src/Utils/MqttMonitor.php`)

Real-time monitoring with htop-style interface:

```php
use Nashgao\MQTT\Utils\MqttMonitor;

$monitor = (new MqttMonitor())
    ->setRefreshInterval(1)
    ->addMetrics($connection, $performance, $publish, $subscription, $validation);

$monitor->start();  // Starts interactive monitoring
```

### 3. Method Chaining Support

All metrics classes now support fluent method chaining:

```php
// Connection metrics with chaining
$connectionMetrics
    ->recordConnectionAttempt()
    ->recordSuccessfulConnection(0.15)
    ->recordConnectionAttempt()
    ->recordFailedConnection();

// Publish metrics with chaining  
$publishMetrics
    ->recordPublishAttempt()
    ->recordSuccessfulPublish('sensor/temp', 1, 250)
    ->recordPublishAttempt()
    ->recordSuccessfulPublish('device/status', 0, 180);

// Subscription metrics with chaining
$subscriptionMetrics
    ->recordSubscriptionAttempt()
    ->recordSuccessfulSubscription('pool1', 'client1', ['sensor/+' => 1]);

// Validation metrics with chaining
$validationMetrics
    ->recordValidation('config_validation', true)
    ->recordValidation('topic_validation', false, 'Invalid topic format');
```

## CLI Tool Usage

### Basic Commands

```bash
# Start real-time monitor (htop-style)
./bin/mqtt-monitor

# One-time snapshot
./bin/mqtt-monitor --mode=snapshot --format=dashboard

# Export to files
./bin/mqtt-monitor --mode=export --format=json --output=metrics.json
./bin/mqtt-monitor --mode=export --format=csv
```

### Command-line Options

```bash
--mode=MODE          Operation mode:
                     - monitor:  Real-time display (default)
                     - snapshot: One-time metrics snapshot  
                     - export:   Export metrics to file

--format=FORMAT      Output format:
                     - dashboard: Formatted text dashboard (default)
                     - realtime:  Real-time colored display
                     - json:      JSON format
                     - csv:       CSV format

--interval=SECONDS   Refresh interval for monitor mode (1-10, default: 1)

--output=FILE        Output filename for export mode
                     (auto-generated if not specified)

--help, -h           Show help message
```

## Interactive Controls (Monitor Mode)

| Key | Action |
|-----|--------|
| `q`, `Q`, `Ctrl+C` | Quit the monitor |
| `h`, `H`, `?` | Toggle help screen |
| `r`, `R` | Reset all metrics counters |
| `e`, `E` | Export current metrics to JSON file |
| `+` | Increase refresh rate (faster updates) |
| `-` | Decrease refresh rate (slower updates) |
| `Space` | Force immediate refresh |

## Real-time Display Features

### 1. In-place Updates
- Terminal screen updates without scrolling
- Cursor hidden during monitoring
- Clean exit with screen restoration

### 2. System Overview
```
System: CPU 15.2% │ Memory 45.3MB │ Load 1.23 │ PHP 8.3.21
```

### 3. Metrics Grid
```
CONNECTIONS          │ PERFORMANCE          │ OPERATIONS
Active:  12          │ Avg RT:  45.2ms      │ Published: 1,250
Total:   145         │ Max RT:  123.4ms     │ Received:  890  
Failed:  3           │ Throughput: 85/s     │ Pub Rate:  98.5%
Rate:    97.9%       │ Memory:  4.2MB       │ Sub Rate:  100.0%
```

### 4. Performance Graphs
```
Response Time (ms)                     │ Throughput (ops/s)
   89.5 |                             │    156.7 |     ●    
   67.1 |      ●                      │    117.5 |   ● │ ●  
   44.7 |    ● │ ●                    │     78.3 |  ●  │ │ ●
   22.4 | ●  │ │ │  ●                 │     39.2 | ●   │ │  │
    0.0 |────────────────────────────  │      0.0 |──────────── 
```

### 5. Top Operations
```
Top Topics by Activity:
  sensor/temperature   ████████████████████ 1250
  device/status        ██████████████       890
  metrics/system       ████████             670
  logs/application     ████                 230
  alert/critical       ██                   45
```

## Examples

### Basic Usage

```php
require_once 'vendor/autoload.php';

use Nashgao\MQTT\Utils\MetricsVisualizer;
use Nashgao\MQTT\Metrics\ConnectionMetrics;

// Create and populate metrics with method chaining
$metrics = (new ConnectionMetrics())
    ->recordConnectionAttempt()
    ->recordSuccessfulConnection(0.15)
    ->recordConnectionAttempt()
    ->recordSuccessfulConnection(0.12);

// Visualize
$visualizer = (new MetricsVisualizer())
    ->setConnectionMetrics($metrics);

echo $visualizer->generateDashboard();
```

### Real-time Monitoring

```php
use Nashgao\MQTT\Utils\MqttMonitor;

$monitor = new MqttMonitor();
$monitor
    ->setRefreshInterval(2)  // 2-second refresh
    ->addMetrics($connection, $performance, $publish, $subscription, $validation)
    ->start();  // Start interactive monitoring
```

### Export Functionality

```php
// Export to different formats
$filename1 = $monitor->exportMetrics('json', 'mqtt_metrics.json');
$filename2 = $monitor->exportMetrics('csv', 'mqtt_metrics.csv'); 
$filename3 = $monitor->exportMetrics('dashboard', 'mqtt_dashboard.txt');

echo "Exported to: $filename1, $filename2, $filename3\n";
```

## Sample Output Formats

### Dashboard Format
```
╭─────────────────────────────────────────────────────────────────────────────╮
│                           MQTT Metrics Dashboard                            │
│                              2025-07-02 00:58:50 UTC                        │
╰─────────────────────────────────────────────────────────────────────────────╯

┌─ Connection Metrics ─────────────────────────────────────────────────────────┐
│ Active Connections:       47                                    │
│ Total Connections:         0                                    │
│ Failed Connections:        3                                    │
│ Success Rate:           94.0%                                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

### JSON Format
```json
{
    "timestamp": "2025-07-02T00:58:50+00:00",
    "connection": {
        "total_attempts": 50,
        "successful_connections": 47,
        "failed_connections": 3,
        "success_rate": 0.94,
        "average_connection_time": 0.135
    },
    "performance": { ... },
    "publish": { ... }
}
```

### CSV Format
```csv
timestamp,metric_type,metric_name,value,unit
2025-07-02T00:58:50+00:00,connection,total_attempts,50,count
2025-07-02T00:58:50+00:00,connection,successful_connections,47,count
2025-07-02T00:58:50+00:00,connection,success_rate,0.94,percentage
```

## Integration with Existing Code

The visualization system integrates seamlessly with existing MQTT operations:

```php
// In your MQTT application
$connectionMetrics = new ConnectionMetrics();
$publishMetrics = new PublishMetrics(); 

// Record metrics during operations (with method chaining)
$client->connect();
$connectionMetrics->recordConnectionAttempt()->recordSuccessfulConnection();

$client->publish('sensor/data', $message);
$publishMetrics->recordPublishAttempt()->recordSuccessfulPublish('sensor/data', 1, strlen($message));

// Visualize anytime
$visualizer = (new MetricsVisualizer())
    ->setConnectionMetrics($connectionMetrics)
    ->setPublishMetrics($publishMetrics);
    
echo $visualizer->generateDashboard();
```

## Performance Considerations

- **Memory efficient**: Limited history buffers (100 items max)
- **Non-blocking I/O**: Responsive keyboard input handling
- **Optimized rendering**: Minimal screen redraws
- **Configurable refresh**: Adjustable update intervals (1-10 seconds)

## Technical Details

### Terminal Handling
- Uses ANSI escape sequences for positioning and colors
- Supports standard terminal dimensions (80+ columns)
- Handles terminal resize gracefully
- Restores terminal state on exit

### Color Coding
- **Green**: Good performance/high success rates
- **Yellow**: Warning/moderate performance  
- **Red**: Error/poor performance
- **Cyan**: Information/neutral
- **White**: Headers and labels

### Graph Rendering
- ASCII art charts using Unicode box-drawing characters
- Automatic scaling based on data ranges
- Real-time trend visualization
- Configurable dimensions

This comprehensive visualization system provides powerful insights into MQTT operations while maintaining the library's performance and simplicity.