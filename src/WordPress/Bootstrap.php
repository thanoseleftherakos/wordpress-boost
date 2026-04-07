<?php

declare(strict_types=1);

namespace WordPressBoost\WordPress;

use RuntimeException;

/**
 * WordPress Bootstrap
 *
 * Loads WordPress in CLI context to provide access to WordPress functions and data.
 */
class Bootstrap
{
    private string $wpPath;
    private bool $loaded = false;
    private bool $isMultisite = false;

    public function __construct(string $wpPath)
    {
        $this->wpPath = rtrim($wpPath, '/');
    }

    /**
     * Load WordPress
     *
     * @throws RuntimeException If WordPress cannot be loaded
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $wpLoadPath = $this->findWpLoad();

        if ($wpLoadPath === null) {
            throw new RuntimeException(
                "Could not find wp-load.php. Make sure you're running from a WordPress installation directory " .
                "or specify the path with --path=/path/to/wordpress"
            );
        }

        // Set up CLI environment
        $this->setupCliEnvironment();

        // Load WordPress
        require_once $wpLoadPath;

        // Verify WordPress loaded correctly
        if (!defined('ABSPATH')) {
            throw new RuntimeException('WordPress failed to load properly.');
        }

        $this->loaded = true;
        $this->isMultisite = is_multisite();
    }

    /**
     * Find wp-load.php file
     */
    private function findWpLoad(): ?string
    {
        // Check provided path
        $directPath = $this->wpPath . '/wp-load.php';
        if (file_exists($directPath)) {
            return $directPath;
        }

        // Check if we're in a subdirectory (like wp-content/plugins/...)
        $path = $this->wpPath;
        $maxDepth = 10;

        for ($i = 0; $i < $maxDepth; $i++) {
            $testPath = $path . '/wp-load.php';
            if (file_exists($testPath)) {
                return $testPath;
            }

            $parentPath = dirname($path);
            if ($parentPath === $path) {
                break;
            }
            $path = $parentPath;
        }

        return null;
    }

    /**
     * Set up environment for CLI execution
     */
    private function setupCliEnvironment(): void
    {
        // Prevent WordPress from sending headers
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', false);
        }

        // Do NOT define WP_CLI = true. wordpress-boost is not WP-CLI, and
        // plugins like WooCommerce activate heavy CLI bootstrapping when
        // they detect that constant, which can hang or crash without the
        // real WP-CLI framework.  Use a dedicated constant instead.
        if (!defined('WP_BOOST_CLI')) {
            define('WP_BOOST_CLI', true);
        }

        // Disable cron on CLI to prevent interference
        if (!defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }

        // Suppress errors being sent to output (we'll handle them)
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        }

        // Set up $_SERVER variables that WordPress expects
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
        $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    /**
     * Check if WordPress is loaded
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Check if this is a multisite installation
     */
    public function isMultisite(): bool
    {
        return $this->isMultisite;
    }

    /**
     * Get WordPress path
     */
    public function getPath(): string
    {
        return $this->wpPath;
    }

    /**
     * Get WordPress version
     */
    public function getVersion(): string
    {
        global $wp_version;
        return $wp_version ?? 'unknown';
    }

    /**
     * Check if a plugin is active
     */
    public function isPluginActive(string $plugin): bool
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active($plugin);
    }

    /**
     * Check if ACF is active
     */
    public function isAcfActive(): bool
    {
        return class_exists('ACF') || function_exists('acf_get_field_groups');
    }

    /**
     * Check if WooCommerce is active
     */
    public function isWooCommerceActive(): bool
    {
        return class_exists('WooCommerce');
    }

    /**
     * Get the active theme
     */
    public function getActiveTheme(): array
    {
        $theme = wp_get_theme();

        return [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'template' => $theme->get_template(),
            'stylesheet' => $theme->get_stylesheet(),
            'is_child_theme' => $theme->parent() !== false,
            'parent_theme' => $theme->parent() ? $theme->parent()->get('Name') : null,
        ];
    }
}
