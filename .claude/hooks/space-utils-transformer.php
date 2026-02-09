#!/usr/bin/env php
<?php

/**
 * Space-Utils Transformer Hook
 * 
 * Automatically transforms native PHP functions to space-utils equivalents
 * This hook runs before and after code generation to ensure space-utils usage
 */

declare(strict_types=1);

class SpaceUtilsTransformer
{
    private array $mappings;
    private array $imports = [];
    private bool $verbose;
    
    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
        $this->loadMappings();
    }
    
    /**
     * Load function mappings from config-driven registry files
     */
    private function loadMappings(): void
    {
        $this->mappings = [];
        
        // Load config to determine which mappings to use
        $configPath = __DIR__ . '/../config/space-utils.json';
        $config = null;
        
        if (file_exists($configPath)) {
            $configData = json_decode(file_get_contents($configPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $config = $configData['space_utils'] ?? null;
                $this->log("Loaded config from: $configPath");
            }
        }
        
        // Determine registry path from config or use default
        $registryPath = __DIR__ . '/../mappings/function-registry.json';
        if ($config && isset($config['mappings']['registry'])) {
            $registryPath = __DIR__ . '/' . $config['mappings']['registry'];
        }
        
        // Load core mappings
        if (file_exists($registryPath)) {
            $this->loadMappingFile($registryPath, 'core', 50, $config);
        }
        
        // Load custom mappings from config or defaults
        $customPaths = [];
        
        if ($config && isset($config['mappings']['custom_registries'])) {
            $priorities = $config['mappings']['priority_overrides'] ?? [];
            foreach ($config['mappings']['custom_registries'] as $customRegistry) {
                $path = getcwd() . '/' . $customRegistry;
                $type = str_replace(['-mappings', '.json'], '', basename($customRegistry));
                $priority = $priorities[$type] ?? 60;
                $customPaths[$path] = ['type' => $type, 'priority' => $priority];
            }
        } else {
            // Fallback to default custom paths
            $customPaths = [
                getcwd() . '/.claude/mappings/team-mappings.json' => ['type' => 'team', 'priority' => 60],
                getcwd() . '/.claude/mappings/custom-mappings.json' => ['type' => 'custom', 'priority' => 70],
                getcwd() . '/.claude/mappings/project-mappings.json' => ['type' => 'project', 'priority' => 80],
            ];
        }
        
        foreach ($customPaths as $path => $info) {
            if (file_exists($path)) {
                $this->loadMappingFile($path, $info['type'], $info['priority'], $config);
            }
        }
        
        if (empty($this->mappings)) {
            $this->error("No mapping files found");
            exit(1);
        }
        
        $totalMappings = array_sum(array_map('count', $this->mappings));
        $this->log("Loaded $totalMappings mappings from " . count($this->mappings) . " categories");
    }
    
    /**
     * Load mappings from a single file with config-based filtering
     */
    private function loadMappingFile(string $path, string $type, int $defaultPriority, ?array $config = null): void
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in $path: " . json_last_error_msg());
            return;
        }
        
        // Support both function_mappings and custom_mappings keys
        $mappings = $data['function_mappings'] ?? $data['custom_mappings'] ?? [];
        
        if (empty($mappings)) {
            $this->log("No mappings found in $path");
            return;
        }
        
        // Get enabled categories from config
        $enabledCategories = null;
        if ($config && isset($config['mappings']['enabled_categories'])) {
            $enabledCategories = $config['mappings']['enabled_categories'];
        }
        
        // Merge with existing mappings
        foreach ($mappings as $category => $functions) {
            // Skip disabled categories
            if ($enabledCategories !== null && isset($enabledCategories[$category]) && !$enabledCategories[$category]) {
                $this->log("Skipping disabled category: $category");
                continue;
            }
            
            if (!isset($this->mappings[$category])) {
                $this->mappings[$category] = [];
            }
            
            foreach ($functions as $name => $mapping) {
                // Set priority: use explicit priority or default based on file
                $mapping['priority'] = $mapping['priority'] ?? $defaultPriority;
                $mapping['source'] = basename($path);
                
                // Check if we should override existing mapping
                $existingPriority = $this->mappings[$category][$name]['priority'] ?? 0;
                
                if (!isset($this->mappings[$category][$name]) || $mapping['priority'] > $existingPriority) {
                    $this->mappings[$category][$name] = $mapping;
                    $this->log("Loaded mapping: $name from $type ($path)");
                }
            }
        }
    }
    
    /**
     * Transform PHP code to use space-utils
     */
    public function transform(string $code): string
    {
        $this->log("Starting transformation...");
        
        // Reset imports for this transformation
        $this->imports = [];
        
        // Transform each category of functions
        foreach ($this->mappings as $category => $functions) {
            foreach ($functions as $functionData) {
                $code = $this->replaceFunction($code, $functionData);
            }
        }
        
        // Add necessary imports at the top of the file
        if (!empty($this->imports)) {
            $code = $this->addImports($code);
        }
        
        // Apply pattern-based transformations
        $code = $this->applyPatternTransformations($code);
        
        $this->log("Transformation complete!");
        return $code;
    }
    
    /**
     * Replace native function with custom/space-utils equivalent
     */
    private function replaceFunction(string $code, array $functionData): string
    {
        $native = $functionData['native'];
        // Support both 'custom' and 'space_utils' fields
        $replacement = $functionData['custom'] ?? $functionData['space_utils'];
        $namespace = $functionData['namespace'] ?? null;
        $import = $functionData['import'] ?? null;
        
        // Check if this transformation has conditions
        if (isset($functionData['condition'])) {
            // For now, we'll apply transformations that make sense contextually
            // This could be enhanced with more sophisticated analysis
            if (!$this->shouldApplyTransformation($code, $functionData)) {
                return $code;
            }
        }
        
        // Build regex pattern for function call
        $pattern = $this->buildFunctionPattern($native);
        
        // Count replacements
        $count = 0;
        
        // Perform replacement
        $code = preg_replace_callback($pattern, function($matches) use ($replacement, $namespace, $import, &$count) {
            $count++;
            
            // Add import statement if specified
            if ($import && !in_array($import, $this->imports)) {
                $this->imports[] = $import;
            } elseif ($namespace && !in_array($namespace, $this->imports)) {
                $this->imports[] = $namespace;
            }
            
            // Build replacement (handles parameter placeholders)
            return $this->buildReplacement($replacement, $matches);
        }, $code);
        
        if ($count > 0) {
            $this->log("Replaced $count instances of $native with $replacement");
        }
        
        return $code;
    }
    
    /**
     * Build regex pattern for function detection
     */
    private function buildFunctionPattern(string $function): string
    {
        // Escape function name for regex
        $escaped = preg_quote($function, '/');
        
        // Match function call with potential whitespace
        return "/\\b{$escaped}\\s*\\(/";
    }
    
    /**
     * Build replacement string
     */
    private function buildReplacement(string $spaceUtils, array $matches): string
    {
        // Handle different replacement patterns
        if (strpos($spaceUtils, '::') !== false) {
            // Static method call
            $parts = explode('::', $spaceUtils);
            $class = $parts[0];
            $method = $parts[1];
            
            // Check if we need Collection::from() wrapper
            if ($class === 'Collection' && $method !== 'from') {
                return "Collection::from(\$array)->{$method}(";
            }
            
            return "{$class}::{$method}(";
        }
        
        // Direct replacement
        return $spaceUtils . '(';
    }
    
    /**
     * Add import statements to the code
     */
    private function addImports(string $code): string
    {
        $importStatements = array_map(fn($ns) => "use {$ns};", $this->imports);
        $imports = implode("\n", $importStatements);
        
        // Find the right place to insert imports
        if (preg_match('/^<\?php\s+/m', $code)) {
            // After opening PHP tag
            $code = preg_replace('/^(<\?php)\s+/m', "$1\n\n{$imports}\n\n", $code, 1);
        } elseif (preg_match('/^namespace\s+[^;]+;/m', $code)) {
            // After namespace declaration
            $code = preg_replace('/(^namespace\s+[^;]+;)\s*/m', "$1\n\n{$imports}\n\n", $code, 1);
        } else {
            // At the beginning
            $code = $imports . "\n\n" . $code;
        }
        
        return $code;
    }
    
    /**
     * Apply pattern-based transformations
     */
    private function applyPatternTransformations(string $code): string
    {
        // Transform array operations to Collection chains
        $code = $this->transformArrayChains($code);
        
        // Transform error handling patterns
        $code = $this->transformErrorHandling($code);
        
        // Transform data models to Entity pattern
        $code = $this->transformDataModels($code);
        
        return $code;
    }
    
    /**
     * Transform chained array operations to Collection
     */
    private function transformArrayChains(string $code): string
    {
        // Detect chained array operations
        $pattern = '/array_map\s*\([^)]+\)\s*\)\s*;/';
        
        // This is a simplified version - could be enhanced
        return $code;
    }
    
    /**
     * Transform error handling to monadic style
     */
    private function transformErrorHandling(string $code): string
    {
        // Transform try/catch to Result monad
        $pattern = '/try\s*\{([^}]+)\}\s*catch\s*\([^)]+\)\s*\{([^}]+)\}/';
        
        $code = preg_replace_callback($pattern, function($matches) {
            $tryBlock = trim($matches[1]);
            $catchBlock = trim($matches[2]);
            
            // Add Result to imports
            if (!in_array('SpacePlatform\\Utils\\Functional\\Result', $this->imports)) {
                $this->imports[] = 'SpacePlatform\\Utils\\Functional\\Result';
            }
            
            return "Result::try(fn() => {
    {$tryBlock}
})->onError(fn(\$error) => {
    {$catchBlock}
})";
        }, $code);
        
        return $code;
    }
    
    /**
     * Transform plain classes to Entity pattern
     */
    private function transformDataModels(string $code): string
    {
        // Detect plain data classes
        $pattern = '/class\s+(\w+)\s*\{([^}]*(?:public|protected|private)\s+[^}]+)\}/';
        
        $code = preg_replace_callback($pattern, function($matches) {
            $className = $matches[1];
            $classBody = $matches[2];
            
            // Check if it looks like a data model (has properties)
            if (preg_match('/(?:public|protected|private)\s+(?:\??\w+\s+)?\$\w+/', $classBody)) {
                // Add Entity to imports
                if (!in_array('SpacePlatform\\Utils\\Entity\\Entity', $this->imports)) {
                    $this->imports[] = 'SpacePlatform\\Utils\\Entity\\Entity';
                }
                
                return "class {$className} extends Entity {{$classBody}}";
            }
            
            return $matches[0];
        }, $code);
        
        return $code;
    }
    
    /**
     * Determine if transformation should be applied based on context
     */
    private function shouldApplyTransformation(string $code, array $functionData): bool
    {
        $condition = $functionData['condition'] ?? '';
        
        switch ($condition) {
            case 'when_parallel_processing_needed':
                // Check for loops with async operations, HTTP calls, etc.
                return (bool)preg_match('/foreach.*(?:Http|curl|file_get_contents|fopen)/s', $code);
                
            case 'when_complex_operations_needed':
                // Check for multiple array operations
                return substr_count($code, 'array_') > 3;
                
            case 'when_structure_and_validation_needed':
                // Check for data validation or structured data
                return (bool)preg_match('/validate|sanitize|filter/', $code);
                
            default:
                return true;
        }
    }
    
    /**
     * Log message if verbose
     */
    private function log(string $message): void
    {
        if ($this->verbose) {
            echo "[SPACE-UTILS] $message\n";
        }
    }
    
    /**
     * Output error message
     */
    private function error(string $message): void
    {
        fwrite(STDERR, "[ERROR] $message\n");
    }
    
    /**
     * Process a file
     */
    public function processFile(string $filepath): void
    {
        if (!file_exists($filepath)) {
            $this->error("File not found: $filepath");
            return;
        }
        
        $this->log("Processing: $filepath");
        
        $content = file_get_contents($filepath);
        $transformed = $this->transform($content);
        
        if ($content !== $transformed) {
            file_put_contents($filepath, $transformed);
            $this->log("File updated: $filepath");
        } else {
            $this->log("No changes needed: $filepath");
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $options = getopt('f:v', ['file:', 'verbose']);
    
    $verbose = isset($options['v']) || isset($options['verbose']);
    $file = $options['f'] ?? $options['file'] ?? null;
    
    if (!$file) {
        echo "Usage: {$argv[0]} -f <file> [-v]\n";
        echo "  -f, --file     PHP file to transform\n";
        echo "  -v, --verbose  Verbose output\n";
        exit(1);
    }
    
    $transformer = new SpaceUtilsTransformer($verbose);
    $transformer->processFile($file);
}