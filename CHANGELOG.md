# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1-alpha.10] - 2026-02-17

### Fixed
- **MQTT v5 topic alias handling**: Debug shell no longer silently drops messages when topic is null/empty (MQTT v5 topic alias feature)
  - Shows `(alias:X)` when topic alias is used
  - Shows `(unknown)` as fallback instead of dropping the message

### Added
- **Verbose debug logging**: New `MQTT_DEBUG_VERBOSE=true` config option to trace debug tap event flow
  - Logs event reception, processing, and broadcast operations
  - Helps diagnose issues with message not appearing in debug shell

## [0.2.1-alpha.9] - 2026-02-16

### Fixed
- **Debug shell subscribe event display**: Fixed "Array to string conversion" warning when displaying subscribe events in `mqtt:debug` shell
  - Subscribe topics format uses topic names as array keys (e.g., `['sensors/#' => ['qos' => 1]]`)
  - `extractSubscribeTopics()` now correctly uses `array_keys()` to extract topic names

## [0.2.1-alpha.8] - 2026-02-12

### Fixed
- **Hyperf coroutine context compatibility**: Fixed `Swoole\Error: API must be called in the coroutine` when running `mqtt:debug` command in Hyperf
  - Changed `MqttDebugCommand` from `execute()` to `handle()` to follow Hyperf's standard pattern
  - Hyperf automatically wraps `handle()` in coroutine context when `$coroutine = true` (default)
  - Updated `MqttShellClient` to detect existing coroutine context and avoid nested `\Swoole\Coroutine\run()`

## [0.2.1-alpha.7] - 2026-02-12

### Fixed
- **Swoole Unix socket client transport**: Fixed `TypeError: swoole_socket_set_option(): Argument #1 ($socket) must be of type Swoole\Coroutine\Socket, Socket given` when using `mqtt:debug` command in Swoole coroutine context
  - Created `SwooleUnixSocketTransport` that uses `Swoole\Coroutine\Socket` instead of native PHP sockets
  - `MqttDebugCommand` now auto-selects the appropriate transport based on Swoole availability
  - Completes the Swoole socket migration (alpha.6 fixed server-side, this fixes client-side)

### Added
- Integration tests for `SwooleUnixSocketTransport` verifying coroutine compatibility

## [0.2.1-alpha.6] - 2026-02-12

### Fixed
- **DebugTapServer logger resolution**: Now prefers `StdoutLoggerInterface` over PSR `LoggerInterface` so debug messages appear in console instead of log files
- **MqttShellClient Swoole go() function**: Fixed "Call to undefined function go()" error by using fully qualified `\Swoole\Coroutine\go()`
- **Swoole Socket API migration**: Migrated `DebugTapServer` and `WebDashboard` from native PHP `socket_*` functions to `Swoole\Coroutine\Socket` API to avoid PHP 8.2+ deprecation warnings
- Added troubleshooting documentation for logger binding issues

## [0.2.1-alpha.2] - 2026-02-11

### Added
- **Test Coverage**: Added 29 new unit tests for Shell components
  - `FilterExpressionTest` - tests for clone(), where(), addAnd/Or/Not(), clear(), remove()
  - `MessageHistoryTest` - tests for getLatest(), getLatestId(), get(), circular buffer
  - Extended `MqttMessageFormatterTest` with depth limit and schema mode tests
  - Extended `PoolConfigTest` with max connections validation tests
- **API Documentation**: Created comprehensive `docs/API.md` for Shell components
  - FilterExpression API with examples
  - MessageHistory API with examples
  - MqttMessageFormatter API with examples
  - StatsCollector API with examples
  - Complete usage workflow examples

### Changed
- Improved PHPDoc annotations for new methods

## [0.2.1-alpha.1] - 2026-02-10

### Fixed
- **Namespace Corrections**: Fixed incorrect `Simps\MQTT` namespace references to `Nashgao\MQTT`
  - `ValidationTrait.php` - corrected namespace declaration
  - `PublishListener.php` - corrected import statements
- **PHPStan Compliance**: Resolved all 79 PHPStan level 5 errors
  - Fixed type mismatches and PHPDoc return types
  - Removed redundant type checks on typed properties
  - Fixed arsort() return value handling in ErrorMetrics
- **Dead Code Removal**:
  - Removed unused `ConfigDefaults` trait
  - Removed unused properties: `$sortBy`, `$errorCounts`, `$panels`, `$widgets`, `$terminalHeight`
  - Removed unreachable catch blocks in HealthChecker
  - Removed unused `determineOverallStatus()` method
- **Missing Implementations**:
  - Added `MQTTPool::getMaxConnections()` method
  - Added missing methods to `MessageHistory` class
  - Added `clone()` method to FilterExpression
  - Added depth/schema mode methods to MqttMessageFormatter

### Changed
- Improved Client class `@method` PHPDoc annotations with proper return types
- Simplified PerformanceMetrics rate calculation
- Made formatHexBytes() public in MqttMessageFormatter

## [0.2.0] - 2026-02-10

### Added
- Interactive MQTT Debug Shell (`src/Shell/`) for real-time message inspection
  - `MqttShellClient` - Swoole coroutine client with polling fallback
  - SQL-like filter expressions with MQTT wildcard support
  - Message formatting (compact, table, vertical, JSON, hex)
  - Statistics collection with latency tracking and histograms
  - Message history with circular buffer
  - Rule engine for conditional message handling
  - Topic visualization (tree and timeline)
- `MqttDebugCommand` for Hyperf console integration
- Debug tap listener infrastructure (`src/Debug/`)
- Claude Code GitHub Actions workflows

### Changed
- Refactored Shell classes to be standalone (no external dependencies)
- Simplified topic configuration classes
- Improved code maintainability and reduced redundancy

### Fixed
- Protected members in final classes changed to private
- Format constant consistency in MqttMessageFormatter

## [0.1.5] - Previous Release
