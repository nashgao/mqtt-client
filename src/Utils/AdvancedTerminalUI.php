<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

/**
 * Advanced Terminal UI inspired by modern CLI tools like btop, lazydocker, k9s
 * Features rich components, interactive panels, and beautiful styling.
 */
class AdvancedTerminalUI
{
    private array $panels = [];

    private array $widgets = [];

    private string $currentTheme = 'dark';

    private int $terminalWidth = 120;

    private int $terminalHeight = 30;

    private array $colors = [];

    private array $themes = [];

    public function __construct()
    {
        $this->initializeThemes();
        $this->detectTerminalSize();
        $this->initializePanels();
    }

    /**
     * Create a beautiful header with MQTT branding.
     */
    public function renderHeader(array $systemInfo = []): string
    {
        $output = [];

        // Top border with gradient effect
        $output[] = $this->colorize('╭' . str_repeat('─', $this->terminalWidth - 2) . '╮', 'border');

        // Title with MQTT logo and system info
        $title = '🚀 MQTT Monitor Pro';
        $version = 'v2.0';
        $time = date('H:i:s');

        $leftSection = " {$title} {$version}";
        $rightSection = "🕒 {$time} ";
        $centerSection = $systemInfo['status'] ?? '● RUNNING';

        $padding = $this->terminalWidth - strlen($leftSection) - strlen($rightSection) - strlen($centerSection) - 2;
        $leftPad = (int) ($padding / 2);
        $rightPad = $padding - $leftPad;

        $headerLine = '│' . $this->colorize($leftSection, 'accent')
                     . str_repeat(' ', max(0, $leftPad))
                     . $this->colorize($centerSection, 'success')
                     . str_repeat(' ', max(0, $rightPad))
                     . $this->colorize($rightSection, 'info') . '│';

        $output[] = $headerLine;

        // Bottom border
        $output[] = $this->colorize('╰' . str_repeat('─', $this->terminalWidth - 2) . '╯', 'border');

        return implode("\n", $output);
    }

    /**
     * Create sidebar with navigation and quick stats.
     */
    public function renderSidebar(array $metrics = []): string
    {
        $output = [];
        $width = 28;

        // Navigation menu
        $menuItems = [
            ['📊 Overview', true],
            ['🔗 Connections', false],
            ['📤 Publishing', false],
            ['📥 Subscriptions', false],
            ['✅ Validation', false],
            ['⚡ Performance', false],
            ['📈 Analytics', false],
            ['⚙️  Settings', false],
        ];

        $output[] = $this->createBox('🧭 Navigation', $width);
        foreach ($menuItems as [$item, $selected]) {
            $indicator = $selected ? '▶' : ' ';
            $color = $selected ? 'highlight' : 'fg';
            $output[] = $this->colorize("│ {$indicator} {$item}" . str_repeat(' ', max(0, $width - strlen(" {$indicator} {$item}") - 1)) . '│', $color);
        }
        $output[] = $this->colorize('├' . str_repeat('─', $width - 2) . '┤', 'border');

        // Quick stats
        $output[] = $this->colorize('│ 📈 Quick Stats' . str_repeat(' ', max(0, $width - 15)) . '│', 'accent');
        $output[] = $this->colorize('├' . str_repeat('─', $width - 2) . '┤', 'border');

        $stats = [
            ['Uptime', '02:34:56'],
            ['CPU', '15.2%'],
            ['Memory', '234 MB'],
            ['Connections', '47'],
            ['Messages/sec', '1.2K'],
        ];

        foreach ($stats as [$label, $value]) {
            $padding = max(0, $width - strlen($label) - strlen($value) - 4);
            $output[] = $this->colorize("│ {$label}" . str_repeat(' ', $padding) . $this->colorize($value, 'success') . ' │', 'fg');
        }

        $output[] = $this->colorize('╰' . str_repeat('─', $width - 2) . '╯', 'border');

        return implode("\n", $output);
    }

    /**
     * Create main dashboard area with widgets.
     */
    public function renderMainDashboard(array $data = []): string
    {
        $output = [];
        $width = $this->terminalWidth - 32;

        // Real-time metrics grid
        $output[] = $this->renderMetricsGrid($data, $width);
        $output[] = '';

        // Performance charts
        $output[] = $this->renderPerformanceCharts($data, $width);
        $output[] = '';

        // Live activity feed
        $output[] = $this->renderActivityFeed($data, $width);

        return implode("\n", $output);
    }

    /**
     * Create interactive footer with hotkeys.
     */
    public function renderFooter(): string
    {
        $output = [];

        $hotkeys = [
            ['F1', 'Help'],
            ['F2', 'Themes'],
            ['F3', 'Export'],
            ['F4', 'Settings'],
            ['↑↓', 'Navigate'],
            ['ESC', 'Back'],
            ['Q', 'Quit'],
        ];

        $output[] = $this->colorize('╭' . str_repeat('─', $this->terminalWidth - 2) . '╮', 'border');

        $hotkeyLine = '│ ';
        foreach ($hotkeys as [$key, $action]) {
            $hotkeyLine .= $this->colorize($key, 'highlight') . ' ' . $this->colorize($action, 'fg') . '  ';
        }
        $padding = $this->terminalWidth - strlen(strip_tags($hotkeyLine)) - 1;
        $hotkeyLine .= str_repeat(' ', max(0, $padding)) . '│';
        $output[] = $hotkeyLine;

        $output[] = $this->colorize('╰' . str_repeat('─', $this->terminalWidth - 2) . '╯', 'border');

        return implode("\n", $output);
    }

    /**
     * Set color theme.
     */
    public function setTheme(string $theme): self
    {
        if (isset($this->themes[$theme])) {
            $this->currentTheme = $theme;
            $this->colors = $this->themes[$theme];
        }
        return $this;
    }

    /**
     * Render complete dashboard.
     */
    public function render(array $data = []): string
    {
        // Clear screen and move to top
        $output = ["\033[2J\033[H"];

        // Header
        $output[] = $this->renderHeader($data['system'] ?? []);
        $output[] = '';

        // Main content area (sidebar + dashboard)
        $sidebarLines = explode("\n", $this->renderSidebar($data['metrics'] ?? []));
        $dashboardLines = explode("\n", $this->renderMainDashboard($data));

        $maxLines = max(count($sidebarLines), count($dashboardLines));
        for ($i = 0; $i < $maxLines; ++$i) {
            $sidebarLine = $sidebarLines[$i] ?? str_repeat(' ', 30);
            $dashboardLine = $dashboardLines[$i] ?? '';
            $output[] = $sidebarLine . '  ' . $dashboardLine;
        }

        $output[] = '';

        // Footer
        $output[] = $this->renderFooter();

        return implode("\n", $output);
    }

    /**
     * Initialize color themes inspired by popular tools.
     */
    private function initializeThemes(): void
    {
        $this->themes = [
            'dark' => [
                'bg' => '233',           // Dark background
                'fg' => '255',           // White text
                'accent' => '39',        // Blue accent
                'success' => '46',       // Green
                'warning' => '226',      // Yellow
                'danger' => '196',       // Red
                'info' => '51',          // Cyan
                'muted' => '245',        // Gray
                'border' => '240',       // Dark gray
                'highlight' => '220',    // Light yellow
            ],
            'light' => [
                'bg' => '255',           // White background
                'fg' => '16',            // Black text
                'accent' => '21',        // Dark blue
                'success' => '28',       // Dark green
                'warning' => '166',      // Orange
                'danger' => '124',       // Dark red
                'info' => '31',          // Dark cyan
                'muted' => '244',        // Gray
                'border' => '250',       // Light gray
                'highlight' => '178',    // Light orange
            ],
            'cyberpunk' => [
                'bg' => '16',            // Black
                'fg' => '46',            // Bright green
                'accent' => '201',       // Magenta
                'success' => '118',      // Lime green
                'warning' => '208',      // Orange
                'danger' => '196',       // Bright red
                'info' => '51',          // Cyan
                'muted' => '240',        // Dark gray
                'border' => '46',        // Green border
                'highlight' => '226',    // Yellow
            ],
        ];

        $this->colors = $this->themes[$this->currentTheme];
    }

    /**
     * Detect terminal dimensions.
     */
    private function detectTerminalSize(): void
    {
        $output = [];
        exec('stty size 2>/dev/null', $output);
        if (! empty($output[0])) {
            [$height, $width] = explode(' ', trim($output[0]));
            $this->terminalHeight = (int) $height;
            $this->terminalWidth = (int) $width;
        }
    }

    /**
     * Initialize panel layout.
     */
    private function initializePanels(): void
    {
        $this->panels = [
            'header' => new Panel('header', 0, 0, $this->terminalWidth, 3),
            'sidebar' => new Panel('sidebar', 0, 3, 30, $this->terminalHeight - 6),
            'main' => new Panel('main', 30, 3, $this->terminalWidth - 30, $this->terminalHeight - 6),
            'footer' => new Panel('footer', 0, $this->terminalHeight - 3, $this->terminalWidth, 3),
        ];
    }

    /**
     * Create metrics grid with sparklines.
     */
    private function renderMetricsGrid(array $data, int $width): string
    {
        $output = [];
        $cardWidth = (int) (($width - 8) / 3);  // 3 cards per row

        $cards = [
            [
                'title' => '🔗 Connections',
                'value' => $data['connections']['active'] ?? 0,
                'change' => '+12%',
                'sparkline' => [5, 8, 12, 15, 18, 22, 25, 23, 20, 24],
                'color' => 'success',
            ],
            [
                'title' => '📤 Published',
                'value' => number_format($data['published']['total'] ?? 0),
                'change' => '+8%',
                'sparkline' => [10, 15, 12, 18, 22, 19, 25, 28, 24, 30],
                'color' => 'info',
            ],
            [
                'title' => '⚡ Throughput',
                'value' => ($data['throughput'] ?? 0) . '/s',
                'change' => '+15%',
                'sparkline' => [20, 25, 22, 28, 35, 32, 38, 42, 40, 45],
                'color' => 'warning',
            ],
        ];

        $cardLines = [];
        foreach ($cards as $card) {
            $cardLines[] = $this->createMetricCard($card, $cardWidth);
        }

        // Combine cards horizontally
        $maxLines = max(array_map('count', $cardLines));
        for ($i = 0; $i < $maxLines; ++$i) {
            $line = '';
            foreach ($cardLines as $cardLine) {
                $line .= ($cardLine[$i] ?? str_repeat(' ', $cardWidth + 2)) . '  ';
            }
            $output[] = rtrim($line);
        }

        return implode("\n", $output);
    }

    /**
     * Create individual metric card with sparkline.
     */
    private function createMetricCard(array $card, int $width): array
    {
        $output = [];

        // Top border
        $output[] = $this->colorize('╭' . str_repeat('─', $width) . '╮', 'border');

        // Title
        $title = ' ' . $card['title'];
        $padding = $width - strlen($title) - 1;
        $output[] = $this->colorize('│' . $title . str_repeat(' ', max(0, $padding)) . '│', 'accent');

        // Value and change
        $value = ' ' . $card['value'];
        $change = $card['change'] . ' ';
        $valuePadding = $width - strlen($value) - strlen($change) - 1;
        $output[] = $this->colorize('│' . $this->colorize($value, $card['color'])
                   . str_repeat(' ', max(0, $valuePadding))
                   . $this->colorize($change, 'success') . '│', 'fg');

        // Sparkline
        $sparkline = ' ' . $this->createSparkline($card['sparkline'], $width - 2);
        $sparkPadding = $width - strlen($sparkline) - 1;
        $output[] = $this->colorize('│' . $sparkline . str_repeat(' ', max(0, $sparkPadding)) . '│', 'muted');

        // Bottom border
        $output[] = $this->colorize('╰' . str_repeat('─', $width) . '╯', 'border');

        return $output;
    }

    /**
     * Create sparkline chart.
     */
    private function createSparkline(array $data, int $width): string
    {
        if (empty($data)) {
            return str_repeat('─', $width);
        }

        $min = min($data);
        $max = max($data);
        $range = $max - $min ?: 1;

        $chars = ['▁', '▂', '▃', '▄', '▅', '▆', '▇', '█'];
        $sparkline = '';

        $dataWidth = min(count($data), $width);
        for ($i = 0; $i < $dataWidth; ++$i) {
            $normalized = ($data[$i] - $min) / $range;
            $charIndex = (int) ($normalized * (count($chars) - 1));
            $sparkline .= $chars[$charIndex];
        }

        return $sparkline;
    }

    /**
     * Create performance charts section.
     */
    private function renderPerformanceCharts(array $data, int $width): string
    {
        $output = [];
        $chartWidth = (int) (($width - 4) / 2);

        // Create two charts side by side
        $chart1 = $this->createAdvancedChart(
            '📈 Response Time (ms)',
            $data['response_times'] ?? array_map(fn () => rand(10, 100), range(1, 20)),
            $chartWidth,
            8
        );

        $chart2 = $this->createAdvancedChart(
            '📊 Message Volume',
            $data['message_volume'] ?? array_map(fn () => rand(50, 200), range(1, 20)),
            $chartWidth,
            8
        );

        // Combine charts horizontally
        $maxLines = max(count($chart1), count($chart2));
        for ($i = 0; $i < $maxLines; ++$i) {
            $line1 = $chart1[$i] ?? str_repeat(' ', $chartWidth + 2);
            $line2 = $chart2[$i] ?? str_repeat(' ', $chartWidth + 2);
            $output[] = $line1 . '  ' . $line2;
        }

        return implode("\n", $output);
    }

    /**
     * Create advanced ASCII chart with axes and labels.
     */
    private function createAdvancedChart(string $title, array $data, int $width, int $height): array
    {
        $output = [];

        // Title
        $output[] = $this->colorize('╭─ ' . $title . ' ' . str_repeat('─', max(0, $width - strlen($title) - 4)) . '╮', 'border');

        if (empty($data)) {
            $output[] = $this->colorize('│' . str_repeat(' ', max(0, $width)) . '│', 'border');
            $output[] = $this->colorize('│' . $this->colorize('No data available', 'muted') . str_repeat(' ', max(0, $width - 18)) . '│', 'border');
            $output[] = $this->colorize('╰' . str_repeat('─', $width) . '╯', 'border');
            return $output;
        }

        $min = min($data);
        $max = max($data);
        $range = $max - $min ?: 1;

        // Chart area
        $chartWidth = $width - 10;  // Leave space for Y-axis labels
        $dataPoints = array_slice($data, -$chartWidth);  // Show last N points

        for ($y = $height - 1; $y >= 0; --$y) {
            $line = '│';

            // Y-axis label
            $value = $min + ($range * $y / ($height - 1));
            $label = sprintf('%6.1f', $value);
            $line .= $this->colorize($label, 'muted') . ' ';

            // Chart content
            for ($x = 0; $x < $chartWidth; ++$x) {
                if ($x < count($dataPoints)) {
                    $dataValue = $dataPoints[$x];
                    $normalizedValue = ($dataValue - $min) / $range;
                    $pixelHeight = (int) ($normalizedValue * ($height - 1));

                    if ($pixelHeight == $y) {
                        $line .= $this->colorize('●', 'accent');
                    } elseif ($pixelHeight > $y) {
                        $line .= $this->colorize('│', 'success');
                    } else {
                        $line .= ' ';
                    }
                } else {
                    $line .= ' ';
                }
            }

            $line .= str_repeat(' ', max(0, $width - strlen(strip_tags($line)) + 1)) . '│';
            $output[] = $line;
        }

        // X-axis
        $xAxisLine = '│' . str_repeat(' ', 7) . str_repeat('─', max(0, $chartWidth)) . str_repeat(' ', max(0, $width - $chartWidth - 7)) . '│';
        $output[] = $this->colorize($xAxisLine, 'border');

        // Bottom border
        $output[] = $this->colorize('╰' . str_repeat('─', $width) . '╯', 'border');

        return $output;
    }

    /**
     * Create activity feed with real-time events.
     */
    private function renderActivityFeed(array $data, int $width): string
    {
        $output = [];

        $output[] = $this->colorize('╭─ 🔴 Live Activity Feed ' . str_repeat('─', max(0, $width - 22)) . '╮', 'border');

        $events = $data['events'] ?? [
            ['time' => '10:23:45', 'type' => 'connection', 'message' => 'Client client_001 connected from 192.168.1.100'],
            ['time' => '10:23:44', 'type' => 'publish', 'message' => 'Published to sensor/temperature (QoS 1, 245 bytes)'],
            ['time' => '10:23:43', 'type' => 'subscribe', 'message' => 'Client subscribed to alerts/# pattern'],
            ['time' => '10:23:42', 'type' => 'error', 'message' => 'Connection timeout for client_002'],
            ['time' => '10:23:41', 'type' => 'validation', 'message' => 'Config validation passed for pool_main'],
        ];

        foreach (array_slice($events, 0, 8) as $event) {
            $icon = match ($event['type']) {
                'connection' => '🔗',
                'publish' => '📤',
                'subscribe' => '📥',
                'error' => '❌',
                'validation' => '✅',
                default => '📝'
            };

            $color = match ($event['type']) {
                'connection' => 'success',
                'publish' => 'info',
                'subscribe' => 'warning',
                'error' => 'danger',
                'validation' => 'success',
                default => 'fg'
            };

            $time = $this->colorize($event['time'], 'muted');
            $message = substr($event['message'], 0, $width - 15);
            $line = "│ {$time} {$icon} " . $this->colorize($message, $color);
            $padding = $width - strlen(strip_tags($line)) + 1;
            $output[] = $line . str_repeat(' ', max(0, $padding)) . '│';
        }

        $output[] = $this->colorize('╰' . str_repeat('─', $width) . '╯', 'border');

        return implode("\n", $output);
    }

    /**
     * Colorize text with ANSI codes.
     */
    private function colorize(string $text, string $colorName): string
    {
        $colorCode = $this->colors[$colorName] ?? $this->colors['fg'];
        return "\033[38;5;{$colorCode}m{$text}\033[0m";
    }

    /**
     * Create a bordered box.
     */
    private function createBox(string $title, int $width): string
    {
        $titlePadding = $width - strlen($title) - 4;
        return $this->colorize("╭─ {$title} " . str_repeat('─', max(0, $titlePadding)) . '╮', 'border');
    }
}

/**
 * Panel class for layout management.
 */
class Panel
{
    public function __construct(
        public string $name,
        public int $x,
        public int $y,
        public int $width,
        public int $height
    ) {}
}
