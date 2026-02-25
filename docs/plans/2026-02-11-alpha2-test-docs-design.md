# v0.2.1-alpha.2 Test & Documentation Design

**Date:** 2026-02-11
**Goal:** Fill test and documentation gaps from v0.2.1-alpha.1

## Context

The v0.2.1-alpha.1 release added several methods to fix PHPStan errors, but these methods lack dedicated tests and documentation.

## Scope

### Methods Requiring Tests

| Class | Methods | Complexity |
|-------|---------|------------|
| `MQTTPool` | `getMaxConnections()` | Simple delegate |
| `FilterExpression` | `clone()` | Medium (deep copy) |
| `MqttMessageFormatter` | `getDepthLimit()`, `setDepthLimit()`, `isSchemaMode()`, `setSchemaMode()` | Simple getters/setters |
| `MessageHistory` | `getLatest()`, `getLatestId()`, `get()` | Simple accessors |

## Test Plan

### Extend Existing Files

**test/Cases/PoolConfigTest.php**
- `testGetMaxConnections()` - unit test with mocked option
- `testGetMaxConnectionsIntegration()` - integration with real config

**test/Cases/Shell/Formatter/MqttMessageFormatterTest.php**
- `testDepthLimitGetterSetter()`
- `testSchemaModeGetterSetter()`
- `testDepthLimitClampsNegativeToZero()`

### Create New Files

**test/Cases/Shell/Filter/FilterExpressionTest.php**
- `testCloneCreatesIndependentCopy()`
- `testClonePreservesAllClauses()`
- `testClonedFilterCanBeModifiedIndependently()`

**test/Cases/Shell/History/MessageHistoryTest.php**
- `testGetLatestReturnsLastMessage()`
- `testGetLatestReturnsNullWhenEmpty()`
- `testGetLatestIdReturnsCorrectIndex()`
- `testGetByIdReturnsCorrectMessage()`
- `testGetByIdReturnsNullForInvalidId()`

**Estimated:** ~14 new tests

## Documentation Plan

### PHPDoc Updates
- `src/Pool/MQTTPool.php` - enhance getMaxConnections() docblock
- `src/Shell/Filter/FilterExpression.php` - enhance clone() docblock

### New docs/API.md
Complete Shell component API reference covering:
- FilterExpression (all public methods)
- MessageHistory (all public methods)
- MqttMessageFormatter (all public methods)
- StatsCollector (key public methods)
- Usage examples

## Execution Order

1. Create FilterExpressionTest.php
2. Create MessageHistoryTest.php
3. Extend MqttMessageFormatterTest.php
4. Extend PoolConfigTest.php
5. Run full test suite
6. Update PHPDoc comments
7. Create docs/API.md
8. Update CHANGELOG.md
9. Tag v0.2.1-alpha.2

## Versioning

Tag as `v0.2.1-alpha.2` upon completion.
