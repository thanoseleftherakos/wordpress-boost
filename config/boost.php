<?php

/**
 * WordPress Boost Configuration
 *
 * This file contains default configuration values for WordPress Boost.
 * These can be overridden via WordPress filters.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Server Information
    |--------------------------------------------------------------------------
    */
    'name' => 'wordpress-boost',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Only allow wp_shell when WP_DEBUG is enabled
        'require_debug_for_shell' => true,

        // Allow read-only mode for database queries
        'database_read_only' => true,

        // Maximum rows returned from database queries
        'max_query_rows' => 1000,

        // Allowed database operations (SELECT only by default)
        'allowed_query_types' => ['SELECT'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    */
    'cache' => [
        // Enable caching for expensive operations
        'enabled' => true,

        // Cache TTL in seconds
        'ttl' => 300,

        // Operations to cache
        'operations' => [
            'hooks' => true,
            'schema' => true,
            'post_types' => true,
            'taxonomies' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Settings
    |--------------------------------------------------------------------------
    */
    'tools' => [
        // Maximum number of hooks to return
        'max_hooks' => 500,

        // Maximum debug log lines to read
        'max_log_lines' => 100,

        // Include core WordPress hooks in listing
        'include_core_hooks' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Generation
    |--------------------------------------------------------------------------
    */
    'generators' => [
        // Default number of items to generate
        'default_count' => 10,

        // Maximum items per generation request
        'max_count' => 100,

        // Default post status for generated content
        'default_status' => 'draft',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Integrations
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        // Enable ACF tools when ACF is active
        'acf' => true,

        // Enable WooCommerce tools when WooCommerce is active
        'woocommerce' => true,

        // Enable Gutenberg block tools
        'gutenberg' => true,

        // Enable WordPress Abilities API tools (WordPress 6.9+)
        'abilities' => true,
    ],
];
