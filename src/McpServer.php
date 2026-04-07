<?php

declare(strict_types=1);

namespace WordPressBoost;

use WordPressBoost\Transport\StdioTransport;
use WordPressBoost\Tools\SiteInfo;
use WordPressBoost\Tools\Hooks;
use WordPressBoost\Tools\Database;
use WordPressBoost\Tools\PostTypes;
use WordPressBoost\Tools\Taxonomies;
use WordPressBoost\Tools\RestApi;
use WordPressBoost\Tools\Shortcodes;
use WordPressBoost\Tools\RewriteRules;
use WordPressBoost\Tools\CronEvents;
use WordPressBoost\Tools\TemplateHierarchy;
use WordPressBoost\Tools\WpShell;
use WordPressBoost\Tools\DebugLog;
use WordPressBoost\Tools\WpCli;
use WordPressBoost\Tools\Acf;
use WordPressBoost\Tools\WooCommerce;
use WordPressBoost\Tools\Blocks;
use WordPressBoost\Tools\Documentation;
use WordPressBoost\Tools\DataGenerator;
use WordPressBoost\Tools\Urls;
use WordPressBoost\Tools\Environment;
use WordPressBoost\Tools\Security;
use WordPressBoost\Tools\Abilities;

/**
 * MCP Server
 *
 * Main server class that handles MCP protocol communication and routes requests to tools.
 */
class McpServer
{
    private const VERSION = '1.0.0';
    private const NAME = 'wordpress-boost';

    private StdioTransport $transport;
    private array $tools = [];
    private array $config;

    public function __construct()
    {
        $this->transport = new StdioTransport();
        $this->loadConfig();
        $this->registerTools();
    }

    /**
     * Load configuration
     */
    private function loadConfig(): void
    {
        $configPath = dirname(__DIR__) . '/config/boost.php';
        $this->config = file_exists($configPath) ? require $configPath : [];
    }

    /**
     * Register all available tools
     */
    private function registerTools(): void
    {
        // Core tools - always available
        $this->tools['site_info'] = new SiteInfo();
        $this->tools['hooks'] = new Hooks();
        $this->tools['database'] = new Database();
        $this->tools['post_types'] = new PostTypes();
        $this->tools['taxonomies'] = new Taxonomies();
        $this->tools['rest_api'] = new RestApi();
        $this->tools['shortcodes'] = new Shortcodes();
        $this->tools['rewrite_rules'] = new RewriteRules();
        $this->tools['cron_events'] = new CronEvents();
        $this->tools['template_hierarchy'] = new TemplateHierarchy();
        $this->tools['wp_shell'] = new WpShell();
        $this->tools['debug_log'] = new DebugLog();
        $this->tools['wp_cli'] = new WpCli();
        $this->tools['blocks'] = new Blocks();
        $this->tools['documentation'] = new Documentation();
        $this->tools['data_generator'] = new DataGenerator();
        $this->tools['urls'] = new Urls();
        $this->tools['environment'] = new Environment();
        $this->tools['security'] = new Security();

        // Abilities API (WordPress 6.9+) - always registered for discovery
        $this->tools['abilities'] = new Abilities();

        // Conditional tools based on active plugins
        if (class_exists('ACF') || function_exists('acf_get_field_groups')) {
            $this->tools['acf'] = new Acf();
        }

        if (class_exists('WooCommerce')) {
            $this->tools['woocommerce'] = new WooCommerce();
        }
    }

    /**
     * Run the MCP server
     */
    public function run(): void
    {
        while ($this->transport->isOpen()) {
            $message = $this->transport->read();

            if ($message === null) {
                continue;
            }

            $this->handleMessage($message);
        }
    }

    /**
     * Handle an incoming JSON-RPC message
     */
    private function handleMessage(array $message): void
    {
        $method = $message['method'] ?? null;
        $params = $message['params'] ?? [];
        $id = $message['id'] ?? null;

        if ($method === null) {
            if ($id !== null) {
                $this->transport->writeError(-32600, 'Invalid Request: missing method', $id);
            }
            return;
        }

        try {
            $result = $this->dispatch($method, $params);

            if ($id !== null) {
                $this->transport->writeResult($result, $id);
            }
        } catch (\Exception $e) {
            if ($id !== null) {
                $this->transport->writeError(-32603, $e->getMessage(), $id);
            }
        }
    }

    /**
     * Dispatch a method call
     */
    private function dispatch(string $method, array $params): mixed
    {
        return match ($method) {
            'initialize' => $this->handleInitialize($params),
            'notifications/initialized' => null,
            'tools/list' => $this->handleToolsList(),
            'tools/call' => $this->handleToolsCall($params),
            'ping' => ['pong' => true],
            default => throw new \RuntimeException("Unknown method: {$method}"),
        };
    }

    /**
     * Handle initialize request
     */
    private function handleInitialize(array $params): array
    {
        return [
            'protocolVersion' => '2025-11-25',
            'capabilities' => (object)[
                'tools' => (object)[
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => self::NAME,
                'version' => self::VERSION,
            ],
        ];
    }

    /**
     * Handle tools/list request
     */
    private function handleToolsList(): array
    {
        $toolDefinitions = [];

        foreach ($this->tools as $tool) {
            $definitions = $tool->getToolDefinitions();
            foreach ($definitions as $definition) {
                $toolDefinitions[] = $definition;
            }
        }

        return ['tools' => $toolDefinitions];
    }

    /**
     * Handle tools/call request
     */
    private function handleToolsCall(array $params): array
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if ($name === null) {
            throw new \RuntimeException('Tool name is required');
        }

        // Find the tool that handles this method
        foreach ($this->tools as $tool) {
            if ($tool->handles($name)) {
                $result = $tool->execute($name, $arguments);
                return $this->formatToolResult($result);
            }
        }

        throw new \RuntimeException("Unknown tool: {$name}");
    }

    /**
     * Format tool result for MCP response
     */
    private function formatToolResult($result): array
    {
        if (is_string($result)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $result,
                    ],
                ],
            ];
        }

        if (is_array($result)) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ];
        }

        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => (string) $result,
                ],
            ],
        ];
    }
}
