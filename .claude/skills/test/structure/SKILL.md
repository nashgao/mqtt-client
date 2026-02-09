---
allowed-tools: all
description: PHP test structure generation with intelligent framework detection and customizable templates
intensity: ⚡⚡⚡
pattern: 🏗️🏗️🏗️
---

# 🏗️🏗️🏗️ CRITICAL REQUIREMENT: PHP TEST STRUCTURE GENERATION! 🏗️🏗️🏗️

**THIS IS NOT A SIMPLE DIRECTORY CREATION - THIS IS A COMPREHENSIVE TEST STRUCTURE GENERATION SYSTEM!**

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL structure generation must achieve PERFECT setup:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Creations** - All directories and files must be created successfully
- ✅ **0 Errors** - No file system or permission errors allowed
- ✅ **0 Warnings** - Configuration warnings are treated as failures
- ✅ **0 Missing Components** - All required structure elements must exist
- ✅ **100% Completeness** - All framework-specific optimizations applied
- ✅ **0 Invalid Configs** - All tool configurations must be syntactically valid
- ✅ **0 Broken Inheritance** - All base classes must be properly structured

### Failure Response Protocol
When ANY issue is detected:
1. **STOP** - Do not proceed to next structure steps
2. **REPORT** - List all creation failures with path references
3. **FIX** - Resolve ALL structure issues before continuing
4. **VERIFY** - Re-validate structure to confirm 100% completeness

### Exit Codes
- `0` = Perfect structure generation (all components created and valid)
- `1` = Any creation failure, missing component, or invalid config
- `2` = Framework detection or system error

---

When you run `/test structure`, you are REQUIRED to:

1. **DETECT** PHP framework and project structure automatically
2. **GENERATE** comprehensive test directory hierarchy with proper organization
3. **CREATE** framework-specific configurations and support files
4. **CONFIGURE** development tools (phpstan, phpcs, infection)
5. **INTEGRATE** with existing annotation validation system
6. **USE MULTIPLE AGENTS** for parallel structure generation using Task tool

**FORBIDDEN BEHAVIORS:**
- ❌ "Create basic test directories" → NO! Use comprehensive structured hierarchy!
- ❌ "Skip framework detection" → NO! Framework-specific optimizations required!
- ❌ "Ignore tool configurations" → NO! Complete development tool setup required!
- ❌ "Generic PHP structure" → NO! Framework-specific templates mandatory!
- ❌ "Single-threaded generation" → NO! Use parallel agent coordination!

**MANDATORY WORKFLOW:**
```
1. Validation check → Verify PHP structure generation should proceed
2. Framework detection → Identify Laravel, Symfony, or Pure PHP with confidence scoring
3. IMMEDIATELY spawn 5 agents using Task tool for parallel structure generation
4. Agent result verification → Ensure all agents completed successfully
5. Structure validation → Execute comprehensive test structure verification
6. VERIFY structure → Ensure complete and functional test environment
```

**YOU ARE NOT DONE UNTIL:**
- ✅ All 5 agents spawned using Task tool and completed successfully
- ✅ Agent result verification confirms all agents completed
- ✅ Complete test directory hierarchy created and validated
- ✅ Framework-specific optimizations applied
- ✅ Tool configurations generated and functional
- ✅ Support files created with proper inheritance
- ✅ Annotation system integration verified
- ✅ Build output directories configured

---

🛑 **MANDATORY PHP STRUCTURE GENERATION PROTOCOL** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. FIRST: Execute should_generate_php_structure() validation
3. IF validation fails: Show appropriate skip message and exit gracefully
4. IF validation passes: Check current project structure and framework
5. Verify PHP environment and dependencies

Execute comprehensive test structure generation ONLY after validation approval with ZERO tolerance for incomplete setup.

**FORBIDDEN SHORTCUT PATTERNS:**
- "Basic directory creation is sufficient" → NO, comprehensive structure required
- "Skip tool configurations" → NO, complete development environment needed
- "Generic PHP setup" → NO, framework-specific optimizations required
- "Manual configuration is fine" → NO, automated generation required
- "Single agent execution is faster" → NO, parallel generation mandatory

You are generating PHP test structure for: $ARGUMENTS

Let me ultrathink about comprehensive test structure generation with framework intelligence.

🚨 **REMEMBER: Proper test structure is the foundation of maintainable PHP applications!** 🚨

**Comprehensive PHP Test Structure Generation Protocol:**

## TASK TOOL AGENT SPAWNING (MANDATORY)

I'll spawn 5 specialized agents using Task tool for comprehensive PHP test structure generation:

### Framework Detection Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">php-framework-detector</parameter>
<parameter name="description">Detect PHP framework and analyze project structure</parameter>
<parameter name="prompt">You are the Framework Detection Agent for PHP test structure generation.

Your responsibilities:
1. Detect PHP framework (Laravel, Symfony, Pure PHP) with confidence scoring
2. Analyze existing project structure and architecture patterns
3. Identify MVC patterns and application organization
4. Determine appropriate test organization strategy
5. Verify PHP version and compatibility requirements

MANDATORY FRAMEWORK DETECTION:
You MUST actually analyze the project structure:
- Use Glob to find framework indicator files: artisan, bin/console, composer.json
- Use Grep to search composer.json for framework dependencies
- Detect Laravel: artisan + laravel/framework in composer.json
- Detect Symfony: bin/console + symfony/framework in composer.json
- Detect Pure PHP: composer.json with PHP packages but no major framework
- Calculate confidence scores: HIGH (90%+), MEDIUM (60-89%), LOW (<60%)

MANDATORY RESULT TRACKING:
- You MUST save detection results to /tmp/php-framework-detector-results.json
- Include success: true/false, framework_detected, confidence_level, project_structure_analysis
- Document framework version, architecture patterns, and compatibility requirements
- Report any detection failures or ambiguous framework indicators

CRITICAL: Accurate framework detection is required for appropriate test structure generation.</parameter>
</invoke>
</function_calls>
```

### Directory Structure Creator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">php-directory-creator</parameter>
<parameter name="description">Create comprehensive PHP test directory hierarchy</parameter>
<parameter name="prompt">You are the Directory Structure Creator for PHP test hierarchy generation.

Your responsibilities:
1. Create complete test directory structure based on framework detection
2. Generate hierarchical organization: Unit, Integration, Functional, Performance
3. Create framework-specific directory optimizations
4. Setup build and tools directories for development workflow
5. Ensure proper directory permissions and structure consistency

MANDATORY DIRECTORY CREATION:
You MUST actually create the directory structure:
- Create core test directories: tests/Unit, tests/Integration, tests/Functional
- Create specialized directories: tests/Performance/Benchmarks, tests/Security
- Create support directories: tests/Fixtures/data, tests/Fixtures/mocks, tests/Support
- Create tools directory: tools/ for phpstan, phpcs, infection configs
- Create build directories: build/coverage, build/logs, build/reports
- Apply framework-specific directory optimizations based on framework detection

MANDATORY RESULT TRACKING:
- You MUST save creation results to /tmp/php-directory-creator-results.json
- Include success: true/false, directories_created, framework_optimizations_applied
- Document specific directories created and their purposes
- Only execute after Framework Detection Agent confirms framework analysis

CRITICAL: Complete directory structure is foundation for organized PHP testing.</parameter>
</invoke>
</function_calls>
```

### Tool Configuration Generator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">php-tool-configurator</parameter>
<parameter name="description">Generate PHP development tool configurations</parameter>
<parameter name="prompt">You are the Tool Configuration Generator for PHP development environment setup.

Your responsibilities:
1. Generate PHPStan configuration with framework-specific optimizations
2. Create PHP_CodeSniffer configuration with appropriate coding standards
3. Setup Infection mutation testing configuration
4. Configure tool paths, exclusions, and framework-specific settings
5. Ensure all tools are properly integrated with test structure

MANDATORY TOOL CONFIGURATION:
You MUST actually generate tool configuration files:
- Create tools/phpstan.neon with level 8 analysis and framework includes
- Generate tools/phpcs.xml with PSR-12 standards and project-specific rules
- Setup tools/infection.json with mutation testing configuration
- Configure proper paths, exclusions, and framework-specific optimizations
- Ensure tools work with created directory structure

MANDATORY RESULT TRACKING:
- You MUST save configuration results to /tmp/php-tool-configurator-results.json
- Include success: true/false, tools_configured, config_files_created
- Document specific tool configurations and their optimizations
- Only execute after Directory Structure Creator completes directory creation

CRITICAL: Proper tool configuration ensures comprehensive code quality and testing.</parameter>
</invoke>
</function_calls>
```

### Support File Generator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">php-support-generator</parameter>
<parameter name="description">Generate PHP test support files and base classes</parameter>
<parameter name="prompt">You are the Support File Generator for PHP test infrastructure.

Your responsibilities:
1. Create framework-specific TestCase base classes
2. Generate DatabaseTestCase for database testing
3. Create test bootstrap file with proper environment setup
4. Generate helper functions and utility classes
5. Setup annotation-ready examples and documentation

MANDATORY SUPPORT FILE GENERATION:
You MUST actually create test support infrastructure:
- Generate tests/Support/TestCase.php with framework-specific base class
- Create tests/Support/DatabaseTestCase.php with database testing utilities
- Generate tests/bootstrap.php with proper environment initialization
- Create tests/Support/helpers.php with testing utility functions
- Generate annotation-ready test examples with @TestedBy/@Verified patterns

MANDATORY RESULT TRACKING:
- You MUST save generation results to /tmp/php-support-generator-results.json
- Include success: true/false, support_files_created, base_classes_generated
- Document specific support files created and their purposes
- Only execute after Tool Configuration Generator completes setup

CRITICAL: Support files provide the foundation for maintainable PHP test infrastructure.</parameter>
</invoke>
</function_calls>
```

### Structure Validator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">php-structure-validator</parameter>
<parameter name="description">Validate complete PHP test structure</parameter>
<parameter name="prompt">You are the Structure Validator for PHP test environment verification.

Your responsibilities:
1. Verify all directories were created correctly
2. Validate tool configurations are functional
3. Check support files have proper syntax and structure
4. Test bootstrap file loads without errors
5. Generate comprehensive structure validation report

MANDATORY STRUCTURE VALIDATION:
You MUST actually validate the generated structure:
- Verify essential directories exist: tests/Unit, tests/Integration, tests/Support, tools/, build/
- Validate configuration files: phpstan.neon, phpcs.xml, infection.json syntax
- Check support files: TestCase.php, DatabaseTestCase.php, bootstrap.php syntax
- Test bootstrap file execution and environment setup
- Verify framework-specific optimizations are properly applied

MANDATORY RESULT TRACKING:
- You MUST save validation results to /tmp/php-structure-validator-results.json
- Include success: true/false, structure_validated, issues_found, completeness_percentage
- Document any missing components or configuration errors
- Only execute after all other agents complete their work

CRITICAL: Complete validation ensures the test structure is ready for development use.</parameter>
</invoke>
</function_calls>
```

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 5 PHP structure agents, you MUST verify their results:

```bash
# MANDATORY: Verify all agents completed successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("php-framework-detector-results.json" "php-directory-creator-results.json" "php-tool-configurator-results.json" "php-support-generator-results.json" "php-structure-validator-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: PHP structure generation agent failed to complete successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for failure details"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing PHP structure generation agent result file: $result_file"
        echo "   Agent may have failed to complete or save results"
        exit 1
    fi
done

echo "✅ All PHP structure generation agents completed successfully"
```

## COMPREHENSIVE STRUCTURE VALIDATION (MANDATORY)

After agent coordination, you MUST execute structure validation:

```bash
# Load final structure validation results
if [ -f "/tmp/php-structure-validator-results.json" ]; then
    echo "🏗️ Validating PHP test structure completeness..."

    # Extract validation metrics from final report
    STRUCTURE_COMPLETE=$(jq -r '.structure_validated // false' "/tmp/php-structure-validator-results.json" 2>/dev/null || echo 'false')
    COMPLETENESS_PCT=$(jq -r '.completeness_percentage // 0' "/tmp/php-structure-validator-results.json" 2>/dev/null || echo '0')
    FRAMEWORK_DETECTED=$(jq -r '.framework_detected // "unknown"' "/tmp/php-framework-detector-results.json" 2>/dev/null || echo 'unknown')
    DIRECTORIES_CREATED=$(jq -r '.directories_created // 0' "/tmp/php-directory-creator-results.json" 2>/dev/null || echo '0')

    echo "🏗️ PHP Test Structure Generation Results:"
    echo "   Framework: $FRAMEWORK_DETECTED"
    echo "   Directories Created: $DIRECTORIES_CREATED"
    echo "   Structure Completeness: $COMPLETENESS_PCT%"
    echo "   Validation Status: $STRUCTURE_COMPLETE"

    # Validate minimum structure requirements
    if [ "$STRUCTURE_COMPLETE" != "true" ]; then
        echo "❌ CRITICAL: PHP test structure validation failed"
        echo "   Structure is incomplete or contains errors"
        echo "   Check validation results for specific issues"
        exit 1
    fi

    if [ "$COMPLETENESS_PCT" -lt 90 ]; then
        echo "⚠️  WARNING: Structure completeness below 90% threshold"
        echo "   Some components may be missing or improperly configured"
    fi

    echo "✅ PHP test structure generation completed successfully"
    echo "   Framework: $FRAMEWORK_DETECTED with full optimization"
    echo "   Structure: Complete hierarchical test organization"
    echo "   Tools: phpstan, phpcs, infection configured"
    echo "   Support: TestCase classes and bootstrap ready"
else
    echo "❌ CRITICAL: Structure validation results not found"
    echo "   PHP structure generation may have failed to complete"
    exit 1
fi
```

## Target Directory Structure Definition

**Complete PHP Test Structure:**
```
├── tests/                         # Test Directory
│   ├── Unit/                      # Unit Tests
│   │   ├── UnitTests/             # Controller Unit Tests
│   │   ├── Service/               # Service Layer Unit Tests
│   │   └── Model/                 # Model Unit Tests
│   ├── Integration/               # Integration Tests
│   │   ├── Database/              # Database Integration Tests
│   │   └── Api/                   # API Integration Tests
│   ├── Functional/                # Functional Tests
│   ├── Performance/               # Performance Tests
│   │   └── Benchmarks/            # Performance Benchmark Tests
│   ├── Security/                  # Security Tests
│   ├── Fixtures/                  # Test Data and Fixtures
│   │   ├── data/                  # Test Data Files
│   │   └── mocks/                 # Mock Objects
│   ├── Support/                   # Test Support Classes
│   │   ├── TestCase.php           # Base Test Case
│   │   └── DatabaseTestCase.php   # Database Test Case
│   └── bootstrap.php              # Test Bootstrap File
├── tools/                         # Development Tools
│   ├── phpstan.neon              # Static Analysis Configuration
│   ├── phpcs.xml                 # Code Style Checking
│   └── infection.json            # Mutation Testing Configuration
├── build/                         # Build Output
│   ├── coverage/                 # Code Coverage Reports
│   ├── logs/                     # Test Logs
│   └── reports/                  # Various Test Reports
```

**Framework-Specific Directory Examples:**

**Laravel Structure Extensions:**
```
├── tests/Feature/              # Laravel Feature Tests
├── tests/Unit/Http/            # HTTP Layer Tests
│   ├── Controllers/            # Controller Tests
│   ├── Middleware/             # Middleware Tests
│   └── Requests/               # Request Tests
├── tests/Unit/Models/          # Eloquent Model Tests
├── tests/Unit/Services/        # Service Layer Tests
└── tests/Integration/Console/   # Artisan Command Tests
```

**Symfony Structure Extensions:**
```
├── tests/Unit/Controller/      # Symfony Controller Tests
├── tests/Unit/Entity/          # Doctrine Entity Tests
├── tests/Unit/Repository/      # Repository Tests
├── tests/Unit/Form/            # Form Tests
├── tests/Unit/Command/         # Console Command Tests
└── tests/Integration/Messenger/ # Message Handler Tests
```

**Success Metrics**

- 🎯 **Framework Detection**: Accurate framework identification with confidence scoring
- 🎯 **Complete Structure**: All required directories and files created
- 🎯 **Tool Integration**: phpstan, phpcs, infection properly configured
- 🎯 **Support Files**: Base test classes and bootstrap functionality
- 🎯 **Validation Success**: 100% structure completeness verification

**Framework-Specific Tool Configuration**

The agents will generate appropriate tool configurations based on detected framework:

- **PHPStan**: Level 8 analysis with framework-specific includes
- **PHP_CodeSniffer**: PSR-12 standards with project-specific rules
- **Infection**: Mutation testing with proper exclusions and thresholds

**Annotation System Integration**

The generated structure includes annotation-ready test examples:

```php
class ExampleControllerTest extends TestCase
{
    /**
     * @TestedBy(method="App\\Http\\Controllers\\ExampleController::index")
     * @covers \\App\\Http\\Controllers\\ExampleController::index
     */
    public function testIndex(): void
    {
        // Test implementation
    }
}
**Agent Coordination**

The command spawns these specialized agents:

1. **php-framework-detector**: Framework detection and confidence analysis
2. **php-directory-creator**: Hierarchical directory structure creation
3. **php-tool-configurator**: Tool configuration generation
4. **php-support-generator**: Base classes and bootstrap files
5. **php-structure-validator**: Complete structure validation

**Anti-Patterns to Avoid:**
- ❌ Creating incomplete directory structure (missing critical directories)
- ❌ Ignoring framework-specific optimizations (generic setup)
- ❌ Skipping tool configuration generation (incomplete development environment)
- ❌ Missing support files or bootstrap configuration (broken test environment)
- ❌ No integration with existing annotation system (lost functionality)
- ❌ Generic PHP setup without framework detection (suboptimal structure)

**Final Verification:**
Before completing PHP test structure generation:
- Have I spawned all 5 agents using Task tool for true parallel processing?
- Are framework-specific optimizations properly applied?
- Are all tool configurations generated and functional?
- Are support files created with proper inheritance structure?
- Is the annotation system properly integrated?
- Are build directories configured for reports and coverage?

**REMEMBER:**
This is OPTIONAL PHP TEST STRUCTURE GENERATION mode - intelligent validation FIRST, then comprehensive directory creation with framework intelligence, tool configuration, and annotation integration. The goal is to create appropriate test structures that only apply PHP generation when contextually relevant and user-approved.

**Key Behaviors:**
- ✅ Validate before generating (respects opt-outs and confidence scoring)
- ✅ Graceful skipping with helpful alternative suggestions
- ✅ Comprehensive PHP structure ONLY for confirmed PHP projects
- ✅ Backwards compatibility for legitimate PHP projects

Executing smart PHP test structure generation protocol with validation-first approach...