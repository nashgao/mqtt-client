# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.6] - 2026-02-09

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
