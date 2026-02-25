#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Architectural Enforcement Script: Command Test Coverage
 *
 * This script ensures every Command class in src/Command/ has a corresponding
 * registration in the integration test's getExpectedCommands() method.
 *
 * Run: php scripts/check-command-tests.php
 * CI Usage: php scripts/check-command-tests.php || exit 1
 */

$srcCommandDir = __DIR__ . '/../src/Command';
$integrationTestFile = __DIR__ . '/../test/Cases/Command/CommandRegistrationIntegrationTest.php';

// Find all Command classes in src/Command/
$commandFiles = glob($srcCommandDir . '/*Command.php');

if ($commandFiles === false || empty($commandFiles)) {
    echo "✓ No command classes found in src/Command/\n";
    exit(0);
}

// Read the integration test file
if (!file_exists($integrationTestFile)) {
    echo "✗ Integration test file not found: {$integrationTestFile}\n";
    echo "  Create it with getExpectedCommands() listing all command classes.\n";
    exit(1);
}

$integrationTestContent = file_get_contents($integrationTestFile);

$missingCommands = [];
$foundCommands = [];

foreach ($commandFiles as $file) {
    $filename = basename($file, '.php');
    $className = "Nashgao\\MQTT\\Command\\{$filename}";

    // Check if this class is listed in getExpectedCommands()
    // Handle both fully qualified and imported class references
    $patterns = [
        $className . '::class',           // Fully qualified
        $filename . '::class',            // Short name with use statement
    ];

    $found = false;
    foreach ($patterns as $pattern) {
        if (strpos($integrationTestContent, $pattern) !== false) {
            $found = true;
            break;
        }
    }

    if ($found) {
        $foundCommands[] = $className;
    } else {
        $missingCommands[] = $className;
    }
}

echo "Command Test Coverage Check\n";
echo "===========================\n\n";

if (!empty($foundCommands)) {
    echo "✓ Registered commands (" . count($foundCommands) . "):\n";
    foreach ($foundCommands as $command) {
        echo "  - {$command}\n";
    }
    echo "\n";
}

if (!empty($missingCommands)) {
    echo "✗ Missing from integration test (" . count($missingCommands) . "):\n";
    foreach ($missingCommands as $command) {
        echo "  - {$command}\n";
    }
    echo "\n";
    echo "Add these to CommandRegistrationIntegrationTest::getExpectedCommands()\n";
    exit(1);
}

echo "✓ All commands are covered by integration tests!\n";
exit(0);
