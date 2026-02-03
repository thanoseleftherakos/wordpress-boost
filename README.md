# WordPress Boost

Boost your WordPress development with AI-powered introspection, security auditing, and intelligent code generation.

Inspired by [Laravel Boost](https://laravel.com/docs/12.x/boost).

## Overview

WordPress Boost accelerates AI-assisted development by providing deep context about your WordPress codebase, comprehensive security auditing, test data generation, and curated AI guidelines. Works with any AI agent (Claude Code, Cursor, Windsurf, etc.).

- Hooks (actions & filters) and their callbacks
- Database schema and queries
- Post types, taxonomies, and REST endpoints
- Security vulnerabilities and site hardening status
- ACF field groups and WooCommerce configuration
- Test data generation
- And much more...

## Key Features

### 🔍 Deep WordPress Introspection
30+ specialized tools to scan hooks, post types, taxonomies, REST endpoints, database schema, and more - giving AI agents the context they need for accurate code generation.

### 🛡️ Security Auditing
Comprehensive security analysis with code vulnerability scanning (SQL injection, XSS, etc.) and site-level audits that check configuration, updates, permissions, and headers - complete with A-F grading.

### 📝 AI Guidelines & Skills
Pre-built guidelines and skills installed to your project's `.ai/` directory, teaching AI assistants WordPress best practices for themes, plugins, REST API, Gutenberg, ACF, and WooCommerce.

### 🧪 Test Data Generation
Generate realistic test posts, pages, users, taxonomy terms, WooCommerce products, and ACF field data using Faker - perfect for development and testing.

### ⚡ Development Tools
Execute PHP code in WordPress context (wp_shell), run database queries, search documentation, and read error logs - all accessible to your AI assistant.

## Installation

### With Composer (Recommended)

```bash
cd /path/to/wordpress
composer require thanoseleftherakos/wordpress-boost --dev
php vendor/bin/wp-boost --init
```

That's it! The `--init` flag creates a `.mcp.json` file and installs AI guidelines and skills to `.ai/` directory.

### CLI Options

```bash
php vendor/bin/wp-boost --init                # Full setup: .mcp.json + AI files
php vendor/bin/wp-boost --init --no-ai-files  # Only create .mcp.json config
php vendor/bin/wp-boost --guidelines-only     # Install only AI guidelines to .ai/guidelines/
php vendor/bin/wp-boost --skills-only         # Install only AI skills to .ai/skills/
php vendor/bin/wp-boost --version             # Display version
php vendor/bin/wp-boost --help                # Show help
```

### Without Composer

For WordPress projects that don't use Composer:

```bash
# Clone to a location outside your WordPress project
git clone https://github.com/thanoseleftherakos/wordpress-boost.git ~/wordpress-boost
cd ~/wordpress-boost
composer install

# Initialize in your WordPress project
cd /path/to/wordpress
php ~/wordpress-boost/bin/wp-boost --init
```

Then edit `.mcp.json` to use the absolute path:

```json
{
    "servers": {
        "wordpress-boost": {
            "command": "php",
            "args": ["/Users/YOUR_USERNAME/wordpress-boost/bin/wp-boost"]
        }
    }
}
```

## Quick Start

### For Cursor / VS Code / Windsurf

These editors auto-detect `.mcp.json` files. After running `--init`, just open your WordPress project and the MCP server will be available automatically.

### For Claude Code

```bash
cd /path/to/wordpress
claude mcp add wordpress-boost -- php vendor/bin/wp-boost
```

No `--path` needed - WordPress Boost auto-discovers your WordPress installation from the current directory.

## AI Guidelines & Skills

WordPress Boost includes curated AI guidelines and skills that help AI assistants write better WordPress code. These are installed to your project's `.ai/` directory.

### Guidelines (`.ai/guidelines/`)
- `wordpress-core.md` - Core WordPress patterns and best practices
- `theme-development.md` - Theme development standards
- `plugin-development.md` - Plugin development patterns
- `rest-api.md` - REST API development
- `gutenberg-blocks.md` - Block editor development
- `security.md` - WordPress security practices
- `acf.md` - Advanced Custom Fields patterns
- `woocommerce.md` - WooCommerce development

### Skills (`.ai/skills/`)
- `theme-development` - Theme creation and customization
- `plugin-development` - Plugin architecture and hooks
- `rest-api-development` - Custom REST endpoints
- `gutenberg-blocks` - Custom block development
- `acf-development` - ACF field groups and usage
- `woocommerce-development` - WooCommerce extensions
- `wp-cli-commands` - WP-CLI command creation

## Available Tools

### Site Information
| Tool | Description |
|------|-------------|
| `site_info` | WordPress version, PHP version, active theme, plugins, debug settings |
| `list_plugins` | All installed plugins with versions and status |
| `list_themes` | Available themes with parent/child relationships |

### Hooks Introspection
| Tool | Description |
|------|-------------|
| `list_hooks` | All registered actions & filters |
| `get_hook_callbacks` | Callbacks attached to a hook with priorities |
| `search_hooks` | Search hooks by pattern |

### WordPress Structure
| Tool | Description |
|------|-------------|
| `list_post_types` | Registered post types with configurations |
| `list_taxonomies` | Registered taxonomies |
| `list_shortcodes` | Registered shortcodes |
| `list_rest_endpoints` | WP REST API routes |
| `list_rewrite_rules` | URL rewrite rules |
| `list_cron_events` | Scheduled WP-Cron tasks |
| `template_hierarchy` | Template resolution information |

### Database Tools
| Tool | Description |
|------|-------------|
| `database_schema` | Table structures (core + plugin tables) |
| `database_query` | Execute SELECT queries via $wpdb |
| `get_option` | Read wp_options values |
| `list_options` | List available options |

### Development Context
| Tool | Description |
|------|-------------|
| `search_docs` | Search WordPress developer documentation |
| `wp_shell` | Execute PHP code in WordPress context |
| `last_error` | Read debug.log entries |
| `list_wp_cli_commands` | Available WP-CLI commands |

### URLs & Environment
| Tool | Description |
|------|-------------|
| `urls` | Get all WordPress URLs (home, site, admin, REST, content, etc.) |
| `environment` | Server environment info (PHP, MySQL, extensions, memory limits) |

### Security Tools
| Tool | Description |
|------|-------------|
| `site_security_audit` | Comprehensive site security audit with scoring (info disclosure, XML-RPC, login security, config, updates, permissions, headers) |
| `security_audit` | Scan files/directories for code vulnerabilities (SQL injection, XSS, etc.) |
| `security_check_file` | Check a specific file for security issues with line numbers |
| `list_security_functions` | List WordPress security functions by category (sanitization, escaping, nonces, etc.) |

### ACF Integration (when ACF is active)
| Tool | Description |
|------|-------------|
| `list_acf_field_groups` | All registered field groups |
| `list_acf_fields` | Fields within a group |
| `get_acf_schema` | Full ACF structure for code generation |

### WooCommerce Integration (when WooCommerce is active)
| Tool | Description |
|------|-------------|
| `woo_info` | WooCommerce version and settings |
| `list_product_types` | Registered product types |
| `woo_schema` | WooCommerce table structures |
| `list_payment_gateways` | Payment gateway configurations |
| `list_shipping_methods` | Shipping method configurations |

### Gutenberg Blocks
| Tool | Description |
|------|-------------|
| `list_block_types` | Registered block types |
| `list_block_patterns` | Registered block patterns |
| `list_block_categories` | Block categories |

### Data Generation (requires fakerphp/faker)
| Tool | Description |
|------|-------------|
| `create_posts` | Generate test posts |
| `create_pages` | Generate test pages |
| `create_users` | Generate test users |
| `create_terms` | Generate taxonomy terms |
| `create_products` | Generate WooCommerce products |
| `populate_acf` | Populate ACF fields with test data |

## Security

### wp_shell Safety

The `wp_shell` tool only works when `WP_DEBUG` is enabled. It also prevents dangerous operations:

- No `exec`, `shell_exec`, `system`, or similar functions
- No file writing operations
- No `eval` or `create_function`

### Database Security

- Only `SELECT` queries are allowed via `database_query`
- All queries use `$wpdb->prepare()` internally
- Results are limited to prevent memory issues

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Composer (for installation)

## Optional Dependencies

- `fakerphp/faker` - For data generation tools

## Development

### Running Tests

```bash
composer test
```

### Code Style

```bash
composer cs-fix
```

## How It Works

```
1. AI Agent (Claude Code, Cursor, etc.)
         ↓
2. MCP Protocol (JSON-RPC over stdio)
         ↓
3. WordPress Boost Server
         ↓
4. WordPress Bootstrap (wp-load.php)
         ↓
5. WordPress Functions & Data
```

WordPress Boost loads WordPress in CLI mode, giving full access to all WordPress functions, hooks, and data while communicating with AI agents via the MCP protocol.

## License

MIT License - see LICENSE file for details.

## Credits

- Built for the [Model Context Protocol](https://modelcontextprotocol.io/)
