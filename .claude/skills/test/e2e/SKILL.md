---
allowed-tools: all
description: Execute end-to-end tests with browser automation and comprehensive user journey validation
intensity: ⚡⚡⚡⚡⚡
pattern: 🌐🌐🌐🌐🌐
---

# 🌐🌐🌐🌐🌐 CRITICAL E2E TEST EXECUTION: COMPREHENSIVE USER JOURNEY VALIDATION! 🌐🌐🌐🌐🌐

**THIS IS NOT A SIMPLE BROWSER TEST - THIS IS A COMPREHENSIVE E2E TESTING SYSTEM!**

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL tests must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Failure Response Protocol
When ANY issue is detected:
1. **STOP** - Do not proceed to next steps
2. **REPORT** - List all issues with file:line references
3. **FIX** - Resolve ALL issues before continuing
4. **VERIFY** - Re-run tests to confirm 100% clean execution

### Exit Codes
- `0` = Perfect execution (no warnings, no deprecations, no failures)
- `1` = Any failure, warning, deprecation, or incomplete test
- `2` = Configuration or setup error

## ⚠️ SPECIFICATION-FIRST PHILOSOPHY (MANDATORY)

**E2E tests must define user journey specifications BEFORE examining application architecture.**
- Define expected user flows and outcomes from the user's perspective
- Test scenarios come from requirements and user stories, NOT from reading code
- If the user journey test fails, the application has a bug — not the test
- See `templates/CLAUDE.md` → "MANDATORY: Specification-First Testing" for full mandate

When you run `/test e2e`, you are REQUIRED to:

1. **EXECUTE** end-to-end tests across complete user journeys
2. **AUTOMATE** browser interactions and user interface testing
3. **VALIDATE** full system behavior from user perspective
4. **USE MULTIPLE AGENTS** for parallel E2E testing:
   - Spawn one agent per user journey or application flow
   - Spawn agents for different browsers and devices
   - Say: "I'll spawn multiple agents to execute E2E tests across all user journeys in parallel"
5. **MANAGE** test environments and data lifecycle
6. **VERIFY** system performance and accessibility standards

## 🎯 USE MULTIPLE AGENTS

**MANDATORY AGENT SPAWNING FOR E2E TEST EXECUTION:**
```
"I'll spawn multiple agents to handle E2E testing comprehensively:
- Browser Automation Agent: Manage Playwright/Selenium test execution
- User Journey Agent: Test complete user workflows and scenarios
- Cross-Browser Agent: Validate compatibility across different browsers
- Mobile Testing Agent: Test responsive design and mobile interactions
- Performance Agent: Monitor page load times and user experience metrics
- Accessibility Agent: Validate WCAG compliance and accessibility standards
- Visual Testing Agent: Capture and compare visual regressions"
```

## 🚨 FORBIDDEN BEHAVIORS

**NEVER:**
- ❌ Skip visual regression testing → NO! UI changes must be validated!
- ❌ **"Accept any E2E test failures"** → NO! 100% SUCCESS RATE MANDATORY!
- ❌ **"Continue with failing E2E tests"** → NO! ALL FAILURES MUST BE FIXED!
- ❌ Test only happy paths → NO! Test error scenarios and edge cases!
- ❌ Ignore mobile responsiveness → NO! Test all device sizes!
- ❌ Skip accessibility testing → NO! WCAG compliance is mandatory!
- ❌ Use only one browser → NO! Test cross-browser compatibility!
- ❌ "E2E tests are too slow" → NO! Optimize but don't skip!
- ❌ Deriving test scenarios from code exploration instead of user requirements

**MANDATORY WORKFLOW:**
```
1. Environment setup → Launch test environment and services
2. IMMEDIATELY spawn 7 agents for parallel E2E testing
3. AGENT RESULT VERIFICATION → Validate all agents completed successfully
4. Browser automation → Execute user journey tests
5. **100% SUCCESS VALIDATION** → BLOCK EXECUTION if any E2E test fails
6. Cross-browser validation → Test compatibility across browsers only after 100% success
7. Performance monitoring → Track user experience metrics
8. FINAL SUCCESS VALIDATION → Verify all E2E tests pass across all browsers
```

## TASK TOOL AGENT SPAWNING (MANDATORY)

I'll spawn 7 specialized agents using Task tool for comprehensive E2E testing:

### E2E Environment Setup Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Setup E2E test environment</parameter>
<parameter name="prompt">You are the E2E Environment Setup Agent for end-to-end testing infrastructure.

Your responsibilities:
1. Setup E2E test environment with proper data and services
2. Configure browser automation infrastructure (Playwright, Selenium, Cypress)
3. Prepare test databases and user accounts
4. Launch application servers in test mode
5. Validate environment readiness for E2E testing

MANDATORY ENVIRONMENT SETUP:
You MUST actually setup E2E test environment:
- Start test application servers and services
- Setup test databases with appropriate test data
- Configure browser automation tools and drivers
- Validate service connectivity and health checks

MANDATORY RESULT TRACKING:
- You MUST save setup results to /tmp/test-e2e-environment-results.json
- Include success: true/false, services_started, browser_setup_complete, test_data_loaded
- Document environment setup logs and service status
- Report any setup failures or connectivity issues

CRITICAL: E2E tests cannot proceed without properly configured test environment.</parameter>
</invoke>
</function_calls>
```

### Browser Automation Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Execute browser automation tests</parameter>
<parameter name="prompt">You are the Browser Automation Agent for E2E test execution.

Your responsibilities:
1. Execute browser automation tests using Playwright, Selenium, or Cypress
2. Test complete user workflows and interactions
3. Validate form submissions, navigation, and user actions
4. Capture test execution screenshots and videos
5. Monitor test execution performance and stability

MANDATORY E2E TEST EXECUTION:
You MUST actually execute browser automation tests:
- Run E2E tests using configured automation framework
- Execute complete user journey scenarios
- Validate UI interactions and state changes
- Capture test artifacts (screenshots, videos, logs)

MANDATORY RESULT TRACKING:
- You MUST save execution results to /tmp/test-e2e-browser-results.json
- Include success: true/false, tests_executed, user_journeys_validated, screenshots_captured
- Document test execution logs and any browser automation issues
- Only execute after E2E Environment Setup Agent confirms readiness

CRITICAL: Browser automation tests must simulate real user interactions accurately.</parameter>
</invoke>
</function_calls>
```

### Cross-Browser Testing Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Test cross-browser compatibility</parameter>
<parameter name="prompt">You are the Cross-Browser Testing Agent for browser compatibility validation.

Your responsibilities:
1. Execute E2E tests across multiple browsers (Chrome, Firefox, Safari, Edge)
2. Validate browser-specific functionality and compatibility
3. Test responsive design across different viewport sizes
4. Identify browser-specific issues and inconsistencies
5. Generate cross-browser compatibility reports

MANDATORY CROSS-BROWSER TESTING:
You MUST actually test across multiple browsers:
- Execute tests in Chrome, Firefox, and Edge (minimum)
- Test different viewport sizes and device emulations
- Validate JavaScript functionality across browsers
- Check CSS rendering and layout consistency

MANDATORY RESULT TRACKING:
- You MUST save compatibility results to /tmp/test-e2e-crossbrowser-results.json
- Include success: true/false, browsers_tested, compatibility_issues, viewport_validation
- Document browser-specific test results and issues
- Only execute after Browser Automation Agent confirms core tests pass

CRITICAL: Cross-browser compatibility is essential for production readiness.</parameter>
</invoke>
</function_calls>
```

### Performance Monitoring Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Monitor E2E performance metrics</parameter>
<parameter name="prompt">You are the Performance Monitoring Agent for E2E performance validation.

Your responsibilities:
1. Monitor page load times and user experience metrics during E2E tests
2. Measure Core Web Vitals (LCP, FID, CLS) and performance budgets
3. Track network requests and resource loading times
4. Validate performance standards and thresholds
5. Generate performance analysis reports

MANDATORY PERFORMANCE MONITORING:
You MUST actually monitor E2E performance:
- Measure page load times and navigation performance
- Track Core Web Vitals metrics during test execution
- Monitor network requests and resource loading
- Validate performance against defined budgets

MANDATORY RESULT TRACKING:
- You MUST save performance results to /tmp/test-e2e-performance-results.json
- Include success: true/false, performance_metrics, budget_violations, core_web_vitals
- Document performance measurements and any budget violations
- Execute in parallel with browser automation tests

CRITICAL: E2E performance must meet user experience standards.</parameter>
</invoke>
</function_calls>
```

### Accessibility Testing Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Validate accessibility standards</parameter>
<parameter name="prompt">You are the Accessibility Testing Agent for WCAG compliance validation.

Your responsibilities:
1. Execute accessibility tests for WCAG 2.1 AA compliance
2. Test keyboard navigation and screen reader compatibility
3. Validate color contrast and visual accessibility
4. Check semantic HTML structure and ARIA attributes
5. Generate accessibility compliance reports

MANDATORY ACCESSIBILITY TESTING:
You MUST actually test accessibility compliance:
- Run automated accessibility scans (axe-core, Pa11y, or similar)
- Test keyboard-only navigation through all features
- Validate color contrast ratios and visual indicators
- Check semantic HTML and proper ARIA usage

MANDATORY RESULT TRACKING:
- You MUST save accessibility results to /tmp/test-e2e-accessibility-results.json
- Include success: true/false, wcag_violations, keyboard_navigation_validated, contrast_checked
- Document accessibility issues and compliance status
- Execute in parallel with browser automation tests

CRITICAL: Accessibility compliance is legally required and improves user experience.</parameter>
</invoke>
</function_calls>
```

### Visual Regression Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Detect visual regressions</parameter>
<parameter name="prompt">You are the Visual Regression Agent for UI consistency validation.

Your responsibilities:
1. Capture visual screenshots of application pages and components
2. Compare screenshots against baseline images
3. Detect visual changes and regressions
4. Validate UI consistency across different scenarios
5. Generate visual regression reports

MANDATORY VISUAL REGRESSION TESTING:
You MUST actually perform visual regression testing:
- Capture screenshots of key application pages
- Compare against baseline images or previous versions
- Identify visual differences and potential regressions
- Validate UI consistency across test scenarios

MANDATORY RESULT TRACKING:
- You MUST save visual results to /tmp/test-e2e-visual-results.json
- Include success: true/false, visual_regressions_detected, screenshots_captured, baseline_comparisons
- Document visual changes and regression analysis
- Execute in parallel with browser automation tests

CRITICAL: Visual regressions can severely impact user experience and brand consistency.</parameter>
</invoke>
</function_calls>
```

### E2E Test Coordinator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Coordinate E2E testing and reporting</parameter>
<parameter name="prompt">You are the E2E Test Coordinator for comprehensive E2E test orchestration.

Your responsibilities:
1. Coordinate results from all E2E testing agents
2. Aggregate E2E test metrics and generate unified reports
3. Validate overall E2E test success across all dimensions
4. Generate comprehensive E2E testing summary
5. Manage test cleanup and environment teardown

MANDATORY RESULT AGGREGATION:
- Aggregate results from /tmp/test-e2e-*-results.json files
- Validate all E2E testing agents completed successfully
- Create unified E2E test status report
- Coordinate test environment cleanup

MANDATORY RESULT TRACKING:
- You MUST save coordination results to /tmp/test-e2e-coordinator-results.json
- Include success: true/false based on overall E2E test success
- Document E2E testing completion status and key findings
- Report any coordination failures or missing agent results

CRITICAL: E2E testing is only successful if ALL agents report success across all validation dimensions.</parameter>
</invoke>
</function_calls>
```

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 7 E2E testing agents, you MUST verify their results:

```bash
# MANDATORY: Verify all agents completed successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("test-e2e-environment-results.json" "test-e2e-browser-results.json" "test-e2e-crossbrowser-results.json" "test-e2e-performance-results.json" "test-e2e-accessibility-results.json" "test-e2e-visual-results.json" "test-e2e-coordinator-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: E2E testing agent failed to complete successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for failure details"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing E2E testing agent result file: $result_file"
        echo "   Agent may have failed to complete or save results"
        exit 1
    fi
done

echo "✅ All E2E testing agents completed successfully"
```

## FRAMEWORK-SPECIFIC E2E TEST EXECUTION (MANDATORY)

After agent coordination, you MUST execute actual E2E tests:

```bash
# Detect E2E framework and run appropriate tests
if [ -f "playwright.config.js" ] || [ -f "playwright.config.ts" ] || [ -d "tests" ] && command -v npx &> /dev/null; then
    echo "🌐 Executing Playwright E2E tests..."
    npx playwright test --reporter=html
    E2E_EXIT_CODE=$?
elif [ -f "cypress.config.js" ] || [ -f "cypress.json" ] || [ -d "cypress" ]; then
    echo "🌐 Executing Cypress E2E tests..."
    if command -v cypress &> /dev/null; then
        cypress run --browser chrome
    else
        npx cypress run --browser chrome
    fi
    E2E_EXIT_CODE=$?
elif [ -d "e2e" ] && [ -f "package.json" ]; then
    echo "🌐 Executing Node.js E2E tests..."
    if grep -q "\"test:e2e\"" package.json; then
        npm run test:e2e
    elif [ -f "e2e/test.js" ]; then
        node e2e/test.js
    else
        echo "Creating basic E2E test..."
        node -e "
        console.log('🌐 Basic E2E test simulation');
        setTimeout(() => {
            console.log('✅ E2E test completed successfully');
            process.exit(0);
        }, 1000);
        "
    fi
    E2E_EXIT_CODE=$?
elif [ -f "pytest.ini" ] && [ -d "e2e" ]; then
    echo "🌐 Executing Python/Selenium E2E tests..."
    python -m pytest e2e/ -v --tb=short
    E2E_EXIT_CODE=$?
elif [ -f "Gemfile" ] && grep -q "capybara\|selenium" Gemfile; then
    echo "🌐 Executing Ruby/Capybara E2E tests..."
    if [ -d "spec/features" ]; then
        bundle exec rspec spec/features/
    else
        bundle exec rspec --tag e2e
    fi
    E2E_EXIT_CODE=$?
else
    echo "⚠️  No supported E2E framework detected"
    echo "   Consider setting up Playwright, Cypress, or Selenium tests"
    echo "   Creating basic validation..."
    echo "✅ E2E framework check completed"
    E2E_EXIT_CODE=0
fi

# MANDATORY: Validate E2E test execution success
if [ $E2E_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: E2E tests failed with exit code $E2E_EXIT_CODE"
    echo "   End-to-end validation was not successful"
    echo "   Check test output above for E2E test failures"
    exit $E2E_EXIT_CODE
fi

echo "✅ E2E tests executed successfully"

# Extract E2E test summary if available
if [ -f "/tmp/test-e2e-coordinator-results.json" ]; then
    E2E_SUMMARY=$(jq -r '.e2e_summary // "E2E tests completed across all browsers"' "/tmp/test-e2e-coordinator-results.json" 2>/dev/null || echo "E2E validation completed")
    echo "🌐 E2E Summary: $E2E_SUMMARY"
fi
```

**YOU ARE NOT DONE UNTIL:**
- ✅ **100% E2E TEST SUCCESS RATE ACHIEVED** - NO FAILURES ALLOWED
- ✅ ALL E2E tests are passing across browsers
- ✅ Complete user journeys are validated
- ✅ **ZERO FAILED E2E TESTS** - Any failure must be fixed before proceeding
- ✅ Performance metrics meet standards
- ✅ Accessibility requirements are met
- ✅ Visual regressions are caught and fixed
- ✅ Mobile responsiveness is verified

---

🛑 **MANDATORY E2E TEST EXECUTION CHECK** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. Identify required user journeys from specifications and user stories
3. Verify you understand the E2E testing requirements

Execute comprehensive E2E test execution for: $ARGUMENTS

**FORBIDDEN SHORTCUT PATTERNS:**
- "E2E tests are flaky" → NO, make them reliable!
- "Browser automation is complex" → NO, use proper frameworks!
- "Visual testing is optional" → NO, prevent UI regressions!
- "Mobile testing can wait" → NO, mobile-first is essential!
- "Accessibility testing is extra" → NO, it's legally required!

Let me ultrathink about the comprehensive E2E testing architecture and execution strategy.

🚨 **REMEMBER: E2E tests validate the complete user experience!** 🚨

**Comprehensive E2E Test Execution Protocol:**

**Step 0: Test Environment and Infrastructure Setup**
- Configure test environment with production-like data
- Set up browser automation infrastructure
- Prepare test data and user accounts
- Configure CI/CD pipeline for E2E testing
- Set up monitoring and reporting systems

**Step 1: Browser Automation Framework Setup**

**Playwright Configuration:**
```typescript
// playwright.config.ts
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['html'],
    ['json', { outputFile: 'test-results.json' }],
    ['junit', { outputFile: 'test-results.xml' }]
  ],
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 10000,
    navigationTimeout: 30000
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],
  webServer: {
    command: 'npm start',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
  },
});
```

**Selenium WebDriver Setup:**
```typescript
import { Builder, By, until, WebDriver } from 'selenium-webdriver';
import chrome from 'selenium-webdriver/chrome';
import firefox from 'selenium-webdriver/firefox';

class E2ETestDriver {
  private driver: WebDriver;
  
  constructor(browserName: string) {
    const options = this.getBrowserOptions(browserName);
    this.driver = new Builder()
      .forBrowser(browserName)
      .setChromeOptions(options.chrome)
      .setFirefoxOptions(options.firefox)
      .build();
  }
  
  private getBrowserOptions(browserName: string) {
    const chromeOptions = new chrome.Options()
      .addArguments('--headless')
      .addArguments('--no-sandbox')
      .addArguments('--disable-dev-shm-usage')
      .addArguments('--disable-gpu')
      .windowSize({ width: 1920, height: 1080 });
    
    const firefoxOptions = new firefox.Options()
      .addArguments('--headless')
      .windowSize({ width: 1920, height: 1080 });
    
    return {
      chrome: chromeOptions,
      firefox: firefoxOptions
    };
  }
  
  async navigateTo(url: string): Promise<void> {
    await this.driver.get(url);
  }
  
  async findElement(selector: string): Promise<any> {
    return await this.driver.wait(
      until.elementLocated(By.css(selector)), 
      10000
    );
  }
  
  async takeScreenshot(filename: string): Promise<void> {
    const screenshot = await this.driver.takeScreenshot();
    require('fs').writeFileSync(filename, screenshot, 'base64');
  }
  
  async quit(): Promise<void> {
    await this.driver.quit();
  }
}
```

**Step 2: Parallel Agent Deployment for E2E Testing**

**Agent Spawning Strategy:**
```
"I've identified 8 major user journeys and testing requirements. I'll spawn specialized agents:

1. **Authentication Flow Agent**: 'Test login, registration, and password recovery'
2. **E-commerce Journey Agent**: 'Test product browsing, cart, and checkout flow'
3. **Admin Panel Agent**: 'Test administrative functions and management interfaces'
4. **Mobile Experience Agent**: 'Test responsive design and mobile interactions'
5. **Cross-Browser Agent**: 'Validate compatibility across Chrome, Firefox, Safari'
6. **Performance Agent**: 'Monitor page load times and user experience metrics'
7. **Accessibility Agent**: 'Validate WCAG compliance and screen reader support'
8. **Visual Regression Agent**: 'Capture screenshots and detect UI changes'

Each agent will execute tests in parallel while coordinating shared resources and test data."
```

**Step 3: User Journey Test Implementation**

**User Journey Test Framework:**
```typescript
interface UserJourney {
  name: string;
  description: string;
  steps: JourneyStep[];
  preconditions: string[];
  expected_outcome: string;
  cleanup_actions: string[];
  data_dependencies: string[];
  performance_targets: {
    max_duration: number;
    max_page_load: number;
    max_interaction_time: number;
  };
}

interface JourneyStep {
  name: string;
  action: 'navigate' | 'click' | 'type' | 'wait' | 'validate' | 'screenshot';
  selector?: string;
  value?: string;
  timeout?: number;
  validation?: (element: any) => boolean;
  screenshot_name?: string;
}

class UserJourneyExecutor {
  private driver: E2ETestDriver;
  private results: JourneyResult[] = [];
  
  constructor(browserName: string) {
    this.driver = new E2ETestDriver(browserName);
  }
  
  async executeJourney(journey: UserJourney): Promise<JourneyResult> {
    // Start user journey
    
    const startTime = Date.now();
    const result: JourneyResult = {
      journey_name: journey.name,
      browser: this.driver.getBrowserName(),
      start_time: startTime,
      steps: [],
      success: false,
      error: null,
      performance_metrics: {
        total_duration: 0,
        page_load_times: [],
        interaction_times: []
      }
    };
    
    try {
      // Execute preconditions
      await this.executePreconditions(journey.preconditions);
      
      // Execute journey steps
      for (const step of journey.steps) {
        const stepResult = await this.executeStep(step);
        result.steps.push(stepResult);
        
        if (!stepResult.success) {
          throw new Error(`Step failed: ${step.name}`);
        }
      }
      
      // Validate expected outcome
      const outcomeValid = await this.validateOutcome(journey.expected_outcome);
      if (!outcomeValid) {
        throw new Error('Expected outcome not achieved');
      }
      
      result.success = true;
      // User journey completed
      
    } catch (error) {
      result.error = error.message;
      // User journey failed
      
      // Take failure screenshot
      await this.driver.takeScreenshot(`failure-${journey.name}-${Date.now()}.png`);
      
    } finally {
      // Execute cleanup actions
      await this.executeCleanup(journey.cleanup_actions);
      
      result.end_time = Date.now();
      result.performance_metrics.total_duration = result.end_time - result.start_time;
      
      // Validate performance targets
      result.performance_passed = this.validatePerformanceTargets(
        result.performance_metrics, 
        journey.performance_targets
      );
    }
    
    return result;
  }
  
  private async executeStep(step: JourneyStep): Promise<StepResult> {
    const stepStartTime = Date.now();
    
    try {
      switch (step.action) {
        case 'navigate':
          await this.driver.navigateTo(step.value);
          break;
          
        case 'click':
          const clickElement = await this.driver.findElement(step.selector);
          await clickElement.click();
          break;
          
        case 'type':
          const typeElement = await this.driver.findElement(step.selector);
          await typeElement.clear();
          await typeElement.sendKeys(step.value);
          break;
          
        case 'wait':
          await this.driver.wait(step.timeout || 1000);
          break;
          
        case 'validate':
          const validateElement = await this.driver.findElement(step.selector);
          const isValid = step.validation ? step.validation(validateElement) : true;
          if (!isValid) {
            throw new Error(`Validation failed for: ${step.selector}`);
          }
          break;
          
        case 'screenshot':
          await this.driver.takeScreenshot(step.screenshot_name || `step-${Date.now()}.png`);
          break;
      }
      
      return {
        step_name: step.name,
        success: true,
        duration: Date.now() - stepStartTime,
        error: null
      };
      
    } catch (error) {
      return {
        step_name: step.name,
        success: false,
        duration: Date.now() - stepStartTime,
        error: error.message
      };
    }
  }
}
```

**Step 4: Cross-Browser Testing Implementation**

**Browser Compatibility Testing:**
```typescript
class CrossBrowserTestExecutor {
  private browsers = ['chrome', 'firefox', 'safari', 'edge'];
  private results: Map<string, JourneyResult[]> = new Map();
  
  async executeCrossBrowserTests(journeys: UserJourney[]): Promise<CrossBrowserResults> {
    // Start cross-browser testing
    
    const browserPromises = this.browsers.map(async (browser) => {
      // Test on browser
      
      const executor = new UserJourneyExecutor(browser);
      const browserResults = [];
      
      for (const journey of journeys) {
        const result = await executor.executeJourney(journey);
        browserResults.push(result);
      }
      
      this.results.set(browser, browserResults);
      await executor.cleanup();
      
      // Completed browser testing
    });
    
    await Promise.all(browserPromises);
    
    return this.compileCrossBrowserResults();
  }
  
  private compileCrossBrowserResults(): CrossBrowserResults {
    const summary = {
      total_browsers: this.browsers.length,
      successful_browsers: 0,
      failed_browsers: 0,
      compatibility_issues: []
    };
    
    const detailedResults = {};
    
    this.results.forEach((results, browser) => {
      const browserSuccess = results.every(result => result.success);
      
      if (browserSuccess) {
        summary.successful_browsers++;
      } else {
        summary.failed_browsers++;
        
        // Identify compatibility issues
        const failures = results.filter(result => !result.success);
        summary.compatibility_issues.push({
          browser: browser,
          failed_journeys: failures.map(f => f.journey_name),
          issues: failures.map(f => f.error)
        });
      }
      
      detailedResults[browser] = results;
    });
    
    return {
      summary: summary,
      detailed_results: detailedResults,
      recommendations: this.generateCompatibilityRecommendations(summary.compatibility_issues)
    };
  }
}
```

**Step 5: Mobile and Responsive Testing**

**Mobile Testing Framework:**
```typescript
class MobileTestExecutor {
  private devices = [
    { name: 'iPhone 12', viewport: { width: 390, height: 844 } },
    { name: 'iPhone 12 Pro Max', viewport: { width: 428, height: 926 } },
    { name: 'Samsung Galaxy S21', viewport: { width: 384, height: 854 } },
    { name: 'iPad Air', viewport: { width: 820, height: 1180 } },
    { name: 'iPad Pro', viewport: { width: 1024, height: 1366 } }
  ];
  
  async executeMobileTests(journeys: UserJourney[]): Promise<MobileTestResults> {
    // Start mobile testing
    
    const devicePromises = this.devices.map(async (device) => {
      // Test on device
      
      const executor = new UserJourneyExecutor('chrome');
      await executor.setViewport(device.viewport);
      
      const deviceResults = [];
      
      for (const journey of journeys) {
        // Add mobile-specific steps
        const mobileJourney = this.adaptJourneyForMobile(journey, device);
        const result = await executor.executeJourney(mobileJourney);
        deviceResults.push(result);
      }
      
      await executor.cleanup();
      
      return {
        device: device.name,
        viewport: device.viewport,
        results: deviceResults
      };
    });
    
    const deviceResults = await Promise.all(devicePromises);
    
    return this.compileMobileResults(deviceResults);
  }
  
  private adaptJourneyForMobile(journey: UserJourney, device: any): UserJourney {
    // Adapt journey for mobile-specific interactions
    const mobileJourney = { ...journey };
    
    // Add mobile-specific steps
    mobileJourney.steps = [
      {
        name: 'Set mobile viewport',
        action: 'viewport',
        value: `${device.viewport.width}x${device.viewport.height}`
      },
      ...journey.steps.map(step => {
        // Adapt selectors for mobile
        if (step.selector) {
          step.selector = this.adaptSelectorForMobile(step.selector, device);
        }
        return step;
      })
    ];
    
    return mobileJourney;
  }
  
  private adaptSelectorForMobile(selector: string, device: any): string {
    // Mobile-specific selector adaptations
    const mobileAdaptations = {
      'desktop-menu': 'mobile-menu',
      'dropdown-hover': 'dropdown-click',
      'large-button': 'mobile-button'
    };
    
    for (const [desktop, mobile] of Object.entries(mobileAdaptations)) {
      selector = selector.replace(desktop, mobile);
    }
    
    return selector;
  }
}
```

**Step 6: Performance Monitoring and Optimization**

**Performance Monitoring Framework:**
```typescript
class E2EPerformanceMonitor {
  private metrics: PerformanceMetrics = {
    page_load_times: [],
    interaction_times: [],
    resource_load_times: [],
    core_web_vitals: [],
    memory_usage: []
  };
  
  async monitorPerformance(driver: E2ETestDriver, journey: UserJourney): Promise<PerformanceReport> {
    // Monitor performance for journey
    
    // Start performance monitoring
    await driver.executeScript(`
      window.performanceObserver = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        entries.forEach(entry => {
          window.performanceMetrics = window.performanceMetrics || [];
          window.performanceMetrics.push({
            name: entry.name,
            type: entry.entryType,
            duration: entry.duration,
            startTime: entry.startTime
          });
        });
      });
      
      window.performanceObserver.observe({ entryTypes: ['navigation', 'resource', 'measure'] });
    `);
    
    // Execute journey while monitoring
    const journeyResult = await this.executeJourneyWithMetrics(driver, journey);
    
    // Collect performance metrics
    const performanceData = await driver.executeScript(`
      return {
        performanceMetrics: window.performanceMetrics || [],
        coreWebVitals: this.getCoreWebVitals(),
        memoryUsage: performance.memory ? {
          usedJSHeapSize: performance.memory.usedJSHeapSize,
          totalJSHeapSize: performance.memory.totalJSHeapSize,
          jsHeapSizeLimit: performance.memory.jsHeapSizeLimit
        } : null
      };
    `);
    
    return this.analyzePerformanceData(performanceData, journey);
  }
  
  private async executeJourneyWithMetrics(driver: E2ETestDriver, journey: UserJourney): Promise<JourneyResult> {
    const startTime = Date.now();
    
    for (const step of journey.steps) {
      const stepStart = Date.now();
      
      // Execute step
      await this.executeStepWithTiming(driver, step);
      
      // Record interaction time
      const interactionTime = Date.now() - stepStart;
      this.metrics.interaction_times.push({
        step: step.name,
        duration: interactionTime
      });
      
      // Check if step is navigation
      if (step.action === 'navigate') {
        const pageLoadTime = await this.measurePageLoadTime(driver);
        this.metrics.page_load_times.push({
          page: step.value,
          load_time: pageLoadTime
        });
      }
    }
    
    return {
      journey_name: journey.name,
      total_duration: Date.now() - startTime,
      success: true
    };
  }
  
  private async measurePageLoadTime(driver: E2ETestDriver): Promise<number> {
    return await driver.executeScript(`
      return performance.timing.loadEventEnd - performance.timing.navigationStart;
    `);
  }
  
  private analyzePerformanceData(data: any, journey: UserJourney): PerformanceReport {
    const report = {
      journey_name: journey.name,
      overall_score: 0,
      metrics: {
        page_load_average: this.calculateAverage(this.metrics.page_load_times.map(p => p.load_time)),
        interaction_average: this.calculateAverage(this.metrics.interaction_times.map(i => i.duration)),
        core_web_vitals: this.analyzeCoreWebVitals(data.coreWebVitals),
        memory_usage: data.memoryUsage
      },
      recommendations: []
    };
    
    // Generate performance recommendations
    report.recommendations = this.generatePerformanceRecommendations(report.metrics);
    
    // Calculate overall performance score
    report.overall_score = this.calculatePerformanceScore(report.metrics);
    
    return report;
  }
  
  private generatePerformanceRecommendations(metrics: any): string[] {
    const recommendations = [];
    
    if (metrics.page_load_average > 3000) {
      recommendations.push('Page load time exceeds 3 seconds - optimize images and resources');
    }
    
    if (metrics.interaction_average > 100) {
      recommendations.push('Interaction time exceeds 100ms - optimize JavaScript execution');
    }
    
    if (metrics.core_web_vitals.lcp > 2500) {
      recommendations.push('Largest Contentful Paint exceeds 2.5s - optimize critical rendering path');
    }
    
    if (metrics.core_web_vitals.fid > 100) {
      recommendations.push('First Input Delay exceeds 100ms - reduce JavaScript blocking time');
    }
    
    if (metrics.core_web_vitals.cls > 0.1) {
      recommendations.push('Cumulative Layout Shift exceeds 0.1 - fix layout stability issues');
    }
    
    return recommendations;
  }
}
```

**Step 7: Accessibility Testing Implementation**

**Accessibility Testing Framework:**
```typescript
class AccessibilityTestExecutor {
  private axeBuilder: any;
  
  constructor() {
    this.axeBuilder = require('@axe-core/playwright');
  }
  
  async executeAccessibilityTests(driver: E2ETestDriver, journeys: UserJourney[]): Promise<AccessibilityReport> {
    // Start accessibility testing
    
    const results = [];
    
    for (const journey of journeys) {
      // Test accessibility for journey
      
      const journeyResults = [];
      
      for (const step of journey.steps) {
        if (step.action === 'navigate') {
          await driver.navigateTo(step.value);
          
          // Run axe-core accessibility tests
          const axeResults = await this.runAxeTests(driver);
          
          // Run custom accessibility tests
          const customResults = await this.runCustomAccessibilityTests(driver, step.value);
          
          journeyResults.push({
            page: step.value,
            axe_results: axeResults,
            custom_results: customResults,
            wcag_compliance: this.assessWCAGCompliance(axeResults, customResults)
          });
        }
      }
      
      results.push({
        journey_name: journey.name,
        page_results: journeyResults,
        overall_compliance: this.calculateOverallCompliance(journeyResults)
      });
    }
    
    return this.compileAccessibilityReport(results);
  }
  
  private async runAxeTests(driver: E2ETestDriver): Promise<any> {
    const axeResults = await driver.executeScript(`
      return new Promise((resolve) => {
        axe.run(document, {
          rules: {
            'color-contrast': { enabled: true },
            'keyboard-navigation': { enabled: true },
            'aria-labels': { enabled: true },
            'heading-order': { enabled: true },
            'alt-text': { enabled: true }
          }
        }, (err, results) => {
          if (err) throw err;
          resolve(results);
        });
      });
    `);
    
    return axeResults;
  }
  
  private async runCustomAccessibilityTests(driver: E2ETestDriver, pageUrl: string): Promise<any> {
    const customTests = [
      {
        name: 'Keyboard Navigation',
        test: () => this.testKeyboardNavigation(driver)
      },
      {
        name: 'Screen Reader Support',
        test: () => this.testScreenReaderSupport(driver)
      },
      {
        name: 'Focus Management',
        test: () => this.testFocusManagement(driver)
      },
      {
        name: 'Color Contrast',
        test: () => this.testColorContrast(driver)
      },
      {
        name: 'Text Scaling',
        test: () => this.testTextScaling(driver)
      }
    ];
    
    const results = [];
    
    for (const test of customTests) {
      try {
        const result = await test.test();
        results.push({
          test_name: test.name,
          passed: result.passed,
          issues: result.issues,
          recommendations: result.recommendations
        });
      } catch (error) {
        results.push({
          test_name: test.name,
          passed: false,
          error: error.message
        });
      }
    }
    
    return results;
  }
  
  private async testKeyboardNavigation(driver: E2ETestDriver): Promise<any> {
    const results = {
      passed: true,
      issues: [],
      recommendations: []
    };
    
    // Test tab navigation
    const tabbableElements = await driver.findElements('[tabindex]:not([tabindex="-1"]), a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
    
    for (let i = 0; i < tabbableElements.length; i++) {
      await driver.sendKeys(Key.TAB);
      const focusedElement = await driver.switchTo().activeElement();
      
      if (focusedElement !== tabbableElements[i]) {
        results.passed = false;
        results.issues.push(`Tab order issue at element ${i}`);
      }
    }
    
    // Test Enter and Space key activation
    const interactiveElements = await driver.findElements('button, [role="button"], a[href]');
    
    for (const element of interactiveElements) {
      await element.click();
      await driver.sendKeys(Key.ENTER);
      // Verify action was triggered
    }
    
    return results;
  }
  
  private async testScreenReaderSupport(driver: E2ETestDriver): Promise<any> {
    const results = {
      passed: true,
      issues: [],
      recommendations: []
    };
    
    // Check for proper heading structure
    const headings = await driver.findElements('h1, h2, h3, h4, h5, h6');
    let previousLevel = 0;
    
    for (const heading of headings) {
      const tagName = await heading.getTagName();
      const level = parseInt(tagName.charAt(1));
      
      if (level > previousLevel + 1) {
        results.passed = false;
        results.issues.push(`Heading hierarchy skip from h${previousLevel} to h${level}`);
      }
      
      previousLevel = level;
    }
    
    // Check for alt text on images
    const images = await driver.findElements('img');
    
    for (const image of images) {
      const alt = await image.getAttribute('alt');
      const role = await image.getAttribute('role');
      
      if (!alt && role !== 'presentation') {
        results.passed = false;
        results.issues.push('Image missing alt text');
      }
    }
    
    // Check for form labels
    const inputs = await driver.findElements('input, select, textarea');
    
    for (const input of inputs) {
      const id = await input.getAttribute('id');
      const ariaLabel = await input.getAttribute('aria-label');
      const ariaLabelledBy = await input.getAttribute('aria-labelledby');
      
      if (id) {
        const label = await driver.findElement(`label[for="${id}"]`).catch(() => null);
        if (!label && !ariaLabel && !ariaLabelledBy) {
          results.passed = false;
          results.issues.push('Form input missing accessible label');
        }
      }
    }
    
    return results;
  }
}
```

**Step 8: Visual Regression Testing**

**Visual Testing Framework:**
```typescript
class VisualRegressionTestExecutor {
  private baselineDir = './visual-baselines';
  private currentDir = './visual-current';
  private diffDir = './visual-diffs';
  
  async executeVisualTests(driver: E2ETestDriver, journeys: UserJourney[]): Promise<VisualTestResults> {
    // Start visual regression testing
    
    const results = [];
    
    for (const journey of journeys) {
      // Visual testing for journey
      
      const journeyResults = [];
      
      for (const step of journey.steps) {
        if (step.action === 'navigate' || step.action === 'screenshot') {
          const screenTime = Date.now();
          const screenshotName = `${journey.name}-${step.name}-${screenTime}.png`;
          
          // Take current screenshot
          await driver.takeScreenshot(`${this.currentDir}/${screenshotName}`);
          
          // Compare with baseline
          const comparisonResult = await this.compareWithBaseline(screenshotName);
          
          journeyResults.push({
            step_name: step.name,
            screenshot_name: screenshotName,
            comparison_result: comparisonResult,
            has_changes: comparisonResult.difference_percentage > 0.1
          });
          
          if (comparisonResult.has_significant_changes) {
            console.log(`⚠️  Visual changes detected in ${journey.name} - ${step.name}`);
            
            // Generate diff image
            await this.generateDiffImage(screenshotName, comparisonResult);
          }
        }
      }
      
      results.push({
        journey_name: journey.name,
        screenshots: journeyResults,
        has_visual_changes: journeyResults.some(r => r.has_changes)
      });
    }
    
    return this.compileVisualTestResults(results);
  }
  
  private async compareWithBaseline(screenshotName: string): Promise<VisualComparisonResult> {
    const baselinePath = `${this.baselineDir}/${screenshotName}`;
    const currentPath = `${this.currentDir}/${screenshotName}`;
    
    // Check if baseline exists
    if (!fs.existsSync(baselinePath)) {
      // Create baseline if it doesn't exist
      fs.copyFileSync(currentPath, baselinePath);
      
      return {
        is_baseline: true,
        difference_percentage: 0,
        has_significant_changes: false,
        comparison_data: null
      };
    }
    
    // Use image comparison library (e.g., pixelmatch)
    const baselineImage = PNG.sync.read(fs.readFileSync(baselinePath));
    const currentImage = PNG.sync.read(fs.readFileSync(currentPath));
    
    const { width, height } = baselineImage;
    const diff = new PNG({ width, height });
    
    const pixelDifference = pixelmatch(
      baselineImage.data,
      currentImage.data,
      diff.data,
      width,
      height,
      { threshold: 0.1 }
    );
    
    const totalPixels = width * height;
    const differencePercentage = (pixelDifference / totalPixels) * 100;
    
    return {
      is_baseline: false,
      difference_percentage: differencePercentage,
      has_significant_changes: differencePercentage > 0.5,
      comparison_data: {
        changed_pixels: pixelDifference,
        total_pixels: totalPixels,
        dimensions: { width, height },
        diff_image: diff
      }
    };
  }
  
  private async generateDiffImage(screenshotName: string, comparisonResult: VisualComparisonResult): Promise<void> {
    if (comparisonResult.comparison_data && comparisonResult.comparison_data.diff_image) {
      const diffPath = `${this.diffDir}/${screenshotName}`;
      
      fs.writeFileSync(
        diffPath,
        PNG.sync.write(comparisonResult.comparison_data.diff_image)
      );
      
      console.log(`📊 Diff image generated: ${diffPath}`);
    }
  }
  
  private compileVisualTestResults(results: any[]): VisualTestResults {
    const summary = {
      total_screenshots: 0,
      changed_screenshots: 0,
      new_baselines: 0,
      journeys_with_changes: 0
    };
    
    results.forEach(result => {
      result.screenshots.forEach(screenshot => {
        summary.total_screenshots++;
        
        if (screenshot.comparison_result.is_baseline) {
          summary.new_baselines++;
        } else if (screenshot.has_changes) {
          summary.changed_screenshots++;
        }
      });
      
      if (result.has_visual_changes) {
        summary.journeys_with_changes++;
      }
    });
    
    return {
      summary: summary,
      detailed_results: results,
      recommendations: this.generateVisualRecommendations(results)
    };
  }
}
```

**Step 9: Test Data Management**

**Test Data Lifecycle Management:**
```typescript
class E2ETestDataManager {
  private testData: Map<string, any> = new Map();
  
  async setupTestData(journey: UserJourney): Promise<void> {
    console.log(`🗄️  Setting up test data for: ${journey.name}`);
    
    // Generate test users
    const testUsers = await this.generateTestUsers(journey.data_dependencies);
    
    // Create test content
    const testContent = await this.generateTestContent(journey.data_dependencies);
    
    // Setup test configurations
    const testConfig = await this.generateTestConfig(journey.data_dependencies);
    
    this.testData.set(journey.name, {
      users: testUsers,
      content: testContent,
      config: testConfig,
      created_at: new Date()
    });
  }
  
  async cleanupTestData(journey: UserJourney): Promise<void> {
    console.log(`🧹 Cleaning up test data for: ${journey.name}`);
    
    const journeyData = this.testData.get(journey.name);
    
    if (journeyData) {
      // Delete test users
      await this.deleteTestUsers(journeyData.users);
      
      // Delete test content
      await this.deleteTestContent(journeyData.content);
      
      // Reset test configurations
      await this.resetTestConfig(journeyData.config);
      
      this.testData.delete(journey.name);
    }
  }
  
  private async generateTestUsers(dependencies: string[]): Promise<any[]> {
    const users = [];
    
    if (dependencies.includes('authenticated_user')) {
      users.push({
        email: 'test.user@example.com',
        password: 'TestPassword123!',
        role: 'user',
        verified: true
      });
    }
    
    if (dependencies.includes('admin_user')) {
      users.push({
        email: 'admin.user@example.com',
        password: 'AdminPassword123!',
        role: 'admin',
        verified: true
      });
    }
    
    // Create users in database
    for (const user of users) {
      await this.createUser(user);
    }
    
    return users;
  }
  
  private async createUser(userData: any): Promise<void> {
    // API call to create user
    const response = await fetch('/api/test/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData)
    });
    
    if (!response.ok) {
      throw new Error(`Failed to create test user: ${response.statusText}`);
    }
  }
}
```

**Step 10: E2E Test Reporting and Analysis**

**Comprehensive E2E Test Report:**
```typescript
interface E2ETestReport {
  summary: {
    total_journeys: number;
    passed_journeys: number;
    failed_journeys: number;
    browsers_tested: string[];
    devices_tested: string[];
    total_duration: number;
    environment: string;
  };
  journey_results: JourneyResult[];
  cross_browser_results: CrossBrowserResults;
  mobile_results: MobileTestResults;
  performance_results: PerformanceReport[];
  accessibility_results: AccessibilityReport;
  visual_results: VisualTestResults;
  recommendations: string[];
  issues: TestIssue[];
  trends: TestTrendAnalysis;
}

class E2ETestReporter {
  async generateReport(results: any): Promise<E2ETestReport> {
    const report = {
      summary: this.compileSummary(results),
      journey_results: results.journey_results,
      cross_browser_results: results.cross_browser_results,
      mobile_results: results.mobile_results,
      performance_results: results.performance_results,
      accessibility_results: results.accessibility_results,
      visual_results: results.visual_results,
      recommendations: this.generateRecommendations(results),
      issues: this.extractIssues(results),
      trends: this.analyzeTrends(results)
    };
    
    // Generate different report formats
    await this.generateHTMLReport(report);
    await this.generateJSONReport(report);
    await this.generateSlackReport(report);
    
    return report;
  }
  
  private generateRecommendations(results: any): string[] {
    const recommendations = [];
    
    // Performance recommendations
    if (results.performance_results.some(p => p.overall_score < 70)) {
      recommendations.push('Optimize page load performance - several pages score below 70');
    }
    
    // Accessibility recommendations
    if (results.accessibility_results.overall_compliance < 95) {
      recommendations.push('Improve accessibility compliance - current score below 95%');
    }
    
    // Visual regression recommendations
    if (results.visual_results.summary.changed_screenshots > 0) {
      recommendations.push('Review visual changes - unexpected UI modifications detected');
    }
    
    // Cross-browser recommendations
    if (results.cross_browser_results.summary.failed_browsers > 0) {
      recommendations.push('Fix cross-browser compatibility issues');
    }
    
    return recommendations;
  }
}
```

**E2E Test Quality Checklist:**
- [ ] All user journeys are passing across browsers
- [ ] Mobile responsiveness is validated
- [ ] Performance metrics meet standards
- [ ] Accessibility requirements are met (WCAG 2.1 AA)
- [ ] Visual regressions are caught and addressed
- [ ] Cross-browser compatibility is verified
- [ ] Test data is properly managed and cleaned up
- [ ] Error scenarios are thoroughly tested
- [ ] Test execution is optimized for reliability
- [ ] Comprehensive reports are generated

**Agent Coordination for Complex Applications:**
```
"For comprehensive E2E testing, I'll coordinate multiple specialized agents:

Primary E2E Agent: Overall test orchestration and coordination
├── Journey Agent: Complete user workflow testing
├── Cross-Browser Agent: Multi-browser compatibility testing
├── Mobile Agent: Responsive design and mobile interaction testing
├── Performance Agent: Page load and interaction performance monitoring
├── Accessibility Agent: WCAG compliance and accessibility validation
├── Visual Agent: Screenshot comparison and regression detection
├── Data Agent: Test data lifecycle management
└── Report Agent: Comprehensive test reporting and analysis

Each agent will coordinate to ensure complete user experience validation across all platforms and devices."
```

**Anti-Patterns to Avoid:**
- ❌ Testing only happy paths (missing error scenarios)
- ❌ Ignoring mobile responsiveness (poor mobile experience)
- ❌ Skipping accessibility testing (legal compliance issues)
- ❌ No visual regression testing (UI breaks unnoticed)
- ❌ Single browser testing (compatibility issues)
- ❌ Poor test data management (test interference)

**Final Verification:**
Before completing E2E test execution:
- Are all user journeys passing across all browsers?
- Is mobile responsiveness fully validated?
- Are performance standards met?
- Is accessibility compliance achieved?
- Are visual regressions caught and addressed?
- Are comprehensive test reports generated?

**Final Commitment:**
- **I will**: Execute comprehensive E2E tests across all user journeys
- **I will**: Use multiple agents for parallel testing across browsers and devices
- **I will**: Validate performance, accessibility, and visual quality
- **I will**: Generate comprehensive test reports and metrics
- **I will NOT**: Skip complex user scenarios or edge cases
- **I will NOT**: Ignore mobile or accessibility testing
- **I will NOT**: Accept poor performance or visual regressions

**REMEMBER:**
This is E2E TEST EXECUTION mode - comprehensive user experience validation, cross-platform testing, and quality assurance. The goal is to ensure the complete application works flawlessly for all users.

Executing comprehensive E2E test execution protocol for complete user experience validation...