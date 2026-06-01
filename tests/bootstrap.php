<?php
/**
 * PHPUnit bootstrap file for AI FAQ Generator plugin tests.
 */

declare(strict_types=1);

// Define the plugin path constant needed by the Loader class.
if (!defined('AFG_PLUGIN_PATH')) {
    define('AFG_PLUGIN_PATH', dirname(__DIR__) . '/');
}

// Load plugin classes.
require_once AFG_PLUGIN_PATH . 'includes/class-loader.php';
require_once AFG_PLUGIN_PATH . 'includes/interfaces/class-ai-provider-interface.php';
require_once AFG_PLUGIN_PATH . 'admin/class-admin.php';
require_once AFG_PLUGIN_PATH . 'admin/class-settings.php';

// ─── WordPress function stubs for unit testing ───────────────────────────────
// These stubs record calls so tests can assert hook registrations and behavior.

/** @var array<int, array{hook: string, callback: mixed, priority: int}> */
global $afg_test_actions;
$afg_test_actions = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_menu_pages;
$afg_test_menu_pages = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_submenu_pages;
$afg_test_submenu_pages = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_registered_settings;
$afg_test_registered_settings = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_settings_sections;
$afg_test_settings_sections = [];

/** @var bool */
global $afg_test_current_user_can;
$afg_test_current_user_can = true;

/** @var array<int, string> */
global $afg_test_settings_fields_calls;
$afg_test_settings_fields_calls = [];

/** @var array<int, string> */
global $afg_test_do_settings_sections_calls;
$afg_test_do_settings_sections_calls = [];

/** @var int */
global $afg_test_submit_button_calls;
$afg_test_submit_button_calls = 0;

/** @var array<string, mixed> In-memory options store for testing */
global $afg_test_options;
$afg_test_options = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_rest_routes;
$afg_test_rest_routes = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_enqueued_scripts;
$afg_test_enqueued_scripts = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_enqueued_styles;
$afg_test_enqueued_styles = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_localized_scripts;
$afg_test_localized_scripts = [];

// ─── WordPress core function stubs ───────────────────────────────────────────

if (!function_exists('add_action')) {
    function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): void
    {
        global $afg_test_actions;
        $afg_test_actions[] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
        ];
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page(
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        string $icon_url = '',
        ?int $position = null
    ): string {
        global $afg_test_menu_pages;
        $afg_test_menu_pages[] = [
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug' => $menu_slug,
            'callback' => $callback,
            'icon_url' => $icon_url,
            'position' => $position,
        ];
        return $menu_slug;
    }
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page(
        string $parent_slug,
        string $page_title,
        string $menu_title,
        string $capability,
        string $menu_slug,
        $callback = '',
        ?int $position = null
    ): string|false {
        global $afg_test_submenu_pages;
        $afg_test_submenu_pages[] = [
            'parent_slug' => $parent_slug,
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'menu_slug' => $menu_slug,
            'callback' => $callback,
            'position' => $position,
        ];
        return $menu_slug;
    }
}

if (!function_exists('register_setting')) {
    function register_setting(string $option_group, string $option_name, $args = []): void
    {
        global $afg_test_registered_settings;
        $afg_test_registered_settings[] = [
            'option_group' => $option_group,
            'option_name' => $option_name,
            'args' => $args,
        ];
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section(string $id, string $title, $callback, string $page, $args = []): void
    {
        global $afg_test_settings_sections;
        $afg_test_settings_sections[] = [
            'id' => $id,
            'title' => $title,
            'callback' => $callback,
            'page' => $page,
        ];
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        global $afg_test_current_user_can;
        return $afg_test_current_user_can;
    }
}

if (!function_exists('settings_fields')) {
    function settings_fields(string $option_group): void
    {
        global $afg_test_settings_fields_calls;
        $afg_test_settings_fields_calls[] = $option_group;
    }
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections(string $page): void
    {
        global $afg_test_do_settings_sections_calls;
        $afg_test_do_settings_sections_calls[] = $page;
    }
}

if (!function_exists('submit_button')) {
    function submit_button(): void
    {
        global $afg_test_submit_button_calls;
        $afg_test_submit_button_calls++;
    }
}

// ─── WordPress Options API stubs ─────────────────────────────────────────────

if (!function_exists('get_option')) {
    function get_option(string $option, $default = false)
    {
        global $afg_test_options;
        return $afg_test_options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, $value, $autoload = null): bool
    {
        global $afg_test_options;
        $afg_test_options[$option] = $value;
        return true;
    }
}

// ─── WordPress sanitization stubs ────────────────────────────────────────────

if (!function_exists('sanitize_text_field')) {
    /**
     * Mimics WordPress sanitize_text_field() behavior:
     * - Strips HTML tags
     * - Removes octets
     * - Removes line breaks and tabs (replaced with space)
     * - Collapses multiple whitespace into single space
     * - Trims leading/trailing whitespace
     */
    function sanitize_text_field(string $str): string
    {
        // Strip HTML tags (like wp_strip_all_tags).
        $filtered = strip_tags($str);
        // Remove percent-encoded octets.
        $filtered = preg_replace('/%[a-f0-9]{2}/i', '', $filtered);
        // Replace any whitespace character (including \n, \r, \t, \v, \f) with a space.
        $filtered = preg_replace('/[\s]+/u', ' ', $filtered);
        // Trim leading/trailing whitespace.
        $filtered = trim($filtered);
        return $filtered;
    }
}

// ─── WordPress REST API stubs ────────────────────────────────────────────────

if (!function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args = [], bool $override = false): bool
    {
        global $afg_test_rest_routes;
        $afg_test_rest_routes[] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
        return true;
    }
}

// ─── WordPress enqueue and localize stubs ────────────────────────────────────

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], $ver = false, $args = []): void
    {
        global $afg_test_enqueued_scripts;
        $afg_test_enqueued_scripts[] = [
            'handle' => $handle,
            'src'    => $src,
            'deps'   => $deps,
            'ver'    => $ver,
            'args'   => $args,
        ];
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], $ver = false, string $media = 'all'): void
    {
        global $afg_test_enqueued_styles;
        $afg_test_enqueued_styles[] = [
            'handle' => $handle,
            'src'    => $src,
            'deps'   => $deps,
            'ver'    => $ver,
            'media'  => $media,
        ];
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool
    {
        global $afg_test_localized_scripts;
        $afg_test_localized_scripts[] = [
            'handle'      => $handle,
            'object_name' => $object_name,
            'l10n'        => $l10n,
        ];
        return true;
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        return 'https://example.com/wp-content/plugins/ai-faq-generator/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action = ''): string
    {
        return 'test_nonce_' . $action;
    }
}

if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        return 'https://example.com/wp-json/' . ltrim($path, '/');
    }
}

// ─── WordPress REST API class stubs ──────────────────────────────────────────

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private array $params = [];
        private array $json_params = [];

        public function __construct(string $method = 'GET', string $route = '')
        {
        }

        public function set_param(string $key, $value): void
        {
            $this->params[$key] = $value;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        public function set_body(string $body): void
        {
            $this->json_params = json_decode($body, true) ?? [];
        }

        public function set_json_params(array $params): void
        {
            $this->json_params = $params;
        }

        public function get_json_params(): array
        {
            return $this->json_params;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        private $data;
        private int $status;

        public function __construct($data = null, int $status = 200)
        {
            $this->data = $data;
            $this->status = $status;
        }

        public function get_data()
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

// ─── WordPress HTTP API stubs ────────────────────────────────────────────────

/** @var array|null Captured arguments from the last wp_remote_post call */
global $afg_test_wp_remote_post_args;
$afg_test_wp_remote_post_args = null;

/** @var mixed Value to return from wp_remote_post stub */
global $afg_test_wp_remote_post_return;
$afg_test_wp_remote_post_return = [];

if (!function_exists('wp_remote_post')) {
    function wp_remote_post(string $url, array $args = [])
    {
        global $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_wp_remote_post_args = ['url' => $url, 'args' => $args];
        return $afg_test_wp_remote_post_return;
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        return $response['response']['code'] ?? 0;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        return $response['body'] ?? '';
    }
}

// ─── WordPress URL sanitization stub ─────────────────────────────────────────

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url, $protocols = null)
    {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        return $url;
    }
}

// ─── WP_Error class stub ─────────────────────────────────────────────────────

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;

        public function __construct(string $code = '', string $message = '')
        {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

// ─── Load OpenAIClient (after WP stubs are defined) ──────────────────────────

require_once AFG_PLUGIN_PATH . 'includes/class-openai-client.php';
