<?php

declare(strict_types=1);

namespace WordPressBoost\Tools;

/**
 * WordPress Abilities API Tool
 *
 * Provides introspection into the WordPress Abilities API (6.9+).
 * Discovers abilities registered by any plugin (ACF, WooCommerce, etc.).
 */
class Abilities extends BaseTool
{
    public function getToolDefinitions(): array
    {
        return [
            $this->createToolDefinition(
                'abilities_status',
                'Check the status of the WordPress Abilities API: whether it is available, registered ability count, which plugins have registered abilities, and whether the official MCP Adapter is active',
            ),
            $this->createToolDefinition(
                'list_abilities',
                'List all registered WordPress abilities. Shows what actions AI agents can perform on this site via the Abilities API.',
                [
                    'category' => [
                        'type' => 'string',
                        'description' => 'Filter by ability category slug',
                    ],
                    'namespace' => [
                        'type' => 'string',
                        'description' => 'Filter by ability namespace (e.g., "acf", "core", "woocommerce")',
                    ],
                    'search' => [
                        'type' => 'string',
                        'description' => 'Search abilities by name or description',
                    ],
                ]
            ),
            $this->createToolDefinition(
                'get_ability',
                'Get detailed information about a specific WordPress ability including its input/output schema and annotations',
                [
                    'ability' => [
                        'type' => 'string',
                        'description' => 'The full ability name (e.g., "acf/field-groups", "core/get-site-info")',
                    ],
                ],
                ['ability']
            ),
            $this->createToolDefinition(
                'list_ability_categories',
                'List all registered ability categories with their descriptions and ability counts',
            ),
        ];
    }

    public function handles(string $name): bool
    {
        return in_array($name, ['abilities_status', 'list_abilities', 'get_ability', 'list_ability_categories']);
    }

    public function execute(string $name, array $arguments): mixed
    {
        // abilities_status always works, even without the API
        if ($name === 'abilities_status') {
            return $this->getStatus();
        }

        if (!$this->isAbilitiesApiAvailable()) {
            return [
                'error' => 'WordPress Abilities API is not available.',
                'suggestion' => 'Requires WordPress 6.9 or later.',
                'wordpress_version' => $GLOBALS['wp_version'] ?? 'unknown',
            ];
        }

        return match ($name) {
            'list_abilities' => $this->listAbilities(
                $arguments['category'] ?? null,
                $arguments['namespace'] ?? null,
                $arguments['search'] ?? null
            ),
            'get_ability' => $this->getAbility($arguments['ability']),
            'list_ability_categories' => $this->listCategories(),
            default => throw new \RuntimeException("Unknown tool: {$name}"),
        };
    }

    private function isAbilitiesApiAvailable(): bool
    {
        return class_exists('WP_Abilities_Registry');
    }

    private function isMcpAdapterActive(): bool
    {
        if (class_exists('WP\\MCP\\Core\\McpAdapter')) {
            return true;
        }

        if (!function_exists('is_plugin_active')) {
            return false;
        }

        $plugins = get_option('active_plugins', []);
        foreach ($plugins as $plugin) {
            if (str_contains($plugin, 'mcp-adapter') || str_contains($plugin, 'wordpress-mcp')) {
                return true;
            }
        }

        return false;
    }

    private function getStatus(): array
    {
        $available = $this->isAbilitiesApiAvailable();

        $status = [
            'available' => $available,
            'wordpress_version' => $GLOBALS['wp_version'] ?? 'unknown',
            'rest_namespace' => $available ? 'wp-abilities/v1' : null,
            'mcp_adapter_active' => $this->isMcpAdapterActive(),
        ];

        if (!$available) {
            $status['message'] = 'The WordPress Abilities API requires WordPress 6.9 or later.';
            return $status;
        }

        $registry = \WP_Abilities_Registry::get_instance();
        $abilities = $registry->get_all_registered();

        $namespaces = [];
        foreach ($abilities as $ability) {
            $name = $ability->get_name();
            $ns = $this->extractNamespace($name);
            if (!isset($namespaces[$ns])) {
                $namespaces[$ns] = 0;
            }
            $namespaces[$ns]++;
        }

        $categoryRegistry = \WP_Abilities_Category_Registry::get_instance();
        $categories = $categoryRegistry->get_all_registered();

        $status['total_abilities'] = count($abilities);
        $status['total_categories'] = count($categories);
        $status['namespaces'] = $namespaces;

        return $status;
    }

    private function listAbilities(?string $category, ?string $namespace, ?string $search): array
    {
        $registry = \WP_Abilities_Registry::get_instance();
        $abilities = $registry->get_all_registered();

        $result = [];
        foreach ($abilities as $ability) {
            $name = $ability->get_name();
            $abilityCategory = $ability->get_category();
            $label = $ability->get_label();
            $description = $ability->get_description();

            // Apply filters
            if ($namespace !== null && $this->extractNamespace($name) !== $namespace) {
                continue;
            }

            if ($category !== null && $abilityCategory !== $category) {
                continue;
            }

            if ($search !== null) {
                $searchLower = strtolower($search);
                if (
                    !str_contains(strtolower($name), $searchLower) &&
                    !str_contains(strtolower($label), $searchLower) &&
                    !str_contains(strtolower($description), $searchLower)
                ) {
                    continue;
                }
            }

            $meta = $ability->get_meta();
            $annotations = $meta['annotations'] ?? [];

            $result[] = [
                'name' => $name,
                'namespace' => $this->extractNamespace($name),
                'label' => $label,
                'description' => $description,
                'category' => $abilityCategory,
                'annotations' => [
                    'readonly' => $annotations['readonly'] ?? false,
                    'destructive' => $annotations['destructive'] ?? false,
                    'idempotent' => $annotations['idempotent'] ?? false,
                ],
            ];
        }

        return [
            'count' => count($result),
            'abilities' => $result,
        ];
    }

    private function getAbility(string $abilityName): array
    {
        $registry = \WP_Abilities_Registry::get_instance();
        $ability = $registry->get_registered($abilityName);

        if ($ability === null) {
            return [
                'error' => "Ability not found: {$abilityName}",
                'suggestion' => 'Use list_abilities to see available abilities.',
            ];
        }

        $meta = $ability->get_meta();
        $annotations = $meta['annotations'] ?? [];

        $result = [
            'name' => $ability->get_name(),
            'namespace' => $this->extractNamespace($ability->get_name()),
            'label' => $ability->get_label(),
            'description' => $ability->get_description(),
            'category' => $ability->get_category(),
            'input_schema' => $ability->get_input_schema(),
            'output_schema' => $ability->get_output_schema(),
            'annotations' => [
                'readonly' => $annotations['readonly'] ?? false,
                'destructive' => $annotations['destructive'] ?? false,
                'idempotent' => $annotations['idempotent'] ?? false,
                'instructions' => $annotations['instructions'] ?? '',
            ],
            'show_in_rest' => $meta['show_in_rest'] ?? false,
        ];

        // Include MCP metadata if present
        if (!empty($meta['mcp'])) {
            $result['mcp'] = $meta['mcp'];
        }

        return $result;
    }

    private function listCategories(): array
    {
        $categoryRegistry = \WP_Abilities_Category_Registry::get_instance();
        $categories = $categoryRegistry->get_all_registered();

        // Count abilities per category
        $abilityCounts = [];
        $abilitiesRegistry = \WP_Abilities_Registry::get_instance();
        $abilities = $abilitiesRegistry->get_all_registered();
        foreach ($abilities as $ability) {
            $cat = $ability->get_category();
            if (!isset($abilityCounts[$cat])) {
                $abilityCounts[$cat] = 0;
            }
            $abilityCounts[$cat]++;
        }

        $result = [];
        foreach ($categories as $category) {
            $slug = $category->get_slug();
            $result[] = [
                'slug' => $slug,
                'label' => $category->get_label(),
                'description' => $category->get_description(),
                'ability_count' => $abilityCounts[$slug] ?? 0,
            ];
        }

        return [
            'count' => count($result),
            'categories' => $result,
        ];
    }

    private function extractNamespace(string $abilityName): string
    {
        $parts = explode('/', $abilityName, 2);
        return $parts[0] ?? $abilityName;
    }
}
