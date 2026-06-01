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

/** @var bool */
global $afg_test_is_admin;
$afg_test_is_admin = true;

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
global $afg_test_registered_scripts;
$afg_test_registered_scripts = [];

/** @var int|false */
global $afg_test_current_post_id;
$afg_test_current_post_id = 42;

/** @var array<int, array<string, mixed>> */
global $afg_test_enqueued_styles;
$afg_test_enqueued_styles = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_localized_scripts;
$afg_test_localized_scripts = [];

// ─── WordPress core function stubs ───────────────────────────────────────────

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        global $afg_test_is_admin;
        return $afg_test_is_admin ?? true;
    }
}

if (!function_exists('register_meta')) {
    /** @var array<int, array<string, mixed>> */
    global $afg_test_registered_meta;
    $afg_test_registered_meta = [];

    function register_meta(string $object_type, string $meta_key, array $args = []): bool
    {
        global $afg_test_registered_meta;
        $afg_test_registered_meta[] = [
            'object_type' => $object_type,
            'meta_key'    => $meta_key,
            'args'        => $args,
        ];
        return true;
    }
}

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
    function current_user_can(string $capability, ...$args): bool
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

// ─── WordPress HTML sanitization stubs ───────────────────────────────────────

if (!function_exists('wp_kses_post')) {
    /**
     * Mimics WordPress wp_kses_post() behavior for testing:
     * - Strips dangerous tags (script, iframe, object, embed, form, style)
     * - Strips event handler attributes (onclick, onerror, onload, etc.)
     * - Strips javascript: URLs
     * - Preserves safe HTML (strong, em, a, p, br, ul, ol, li, etc.)
     */
    function wp_kses_post(string $content): string
    {
        // Remove dangerous tags and their content.
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<iframe\b[^>]*>.*?<\/iframe>/is', '', $content);
        $content = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $content);
        $content = preg_replace('/<embed\b[^>]*\/?>/is', '', $content);
        $content = preg_replace('/<form\b[^>]*>.*?<\/form>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $content);

        // Remove self-closing dangerous tags.
        $content = preg_replace('/<script\b[^>]*\/?>/is', '', $content);
        $content = preg_replace('/<iframe\b[^>]*\/?>/is', '', $content);
        $content = preg_replace('/<object\b[^>]*\/?>/is', '', $content);

        // Remove event handler attributes from remaining tags.
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s+on\w+\s*=\s*\S+/i', '', $content);

        // Remove javascript: URLs in href/src attributes.
        $content = preg_replace('/(<[^>]+)(href|src)\s*=\s*["\']?\s*javascript:[^"\'>\s]*["\']?/i', '$1$2=""', $content);

        return $content;
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Mimics WordPress esc_attr() behavior for testing.
     */
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// ─── WordPress sanitization stubs ────────────────────────────────────────────

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string
    {
        $text = strip_tags($text);
        if ($remove_breaks) {
            $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
        }
        return trim($text);
    }
}

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

if (!function_exists('wp_register_script')) {
    /** @var array<int, array<string, mixed>> */
    global $afg_test_registered_scripts;
    $afg_test_registered_scripts = [];

    function wp_register_script(string $handle, string $src = '', array $deps = [], $ver = false, $args = []): bool
    {
        global $afg_test_registered_scripts;
        $afg_test_registered_scripts[] = [
            'handle' => $handle,
            'src'    => $src,
            'deps'   => $deps,
            'ver'    => $ver,
            'args'   => $args,
        ];
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = '', string $scheme = 'admin'): string
    {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('get_the_ID')) {
    /** @var int|false */
    global $afg_test_current_post_id;
    $afg_test_current_post_id = 42;

    function get_the_ID(): int|false
    {
        global $afg_test_current_post_id;
        return $afg_test_current_post_id;
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

// ─── WP_Post class stub ──────────────────────────────────────────────────────

if (!class_exists('WP_Post')) {
    class WP_Post
    {
        public string $post_title = '';
        public string $post_content = '';
        public string $post_status = '';
    }
}

// ─── get_post function stub ──────────────────────────────────────────────────

/** @var array<int, WP_Post|null> In-memory posts store for testing */
global $afg_test_posts;
$afg_test_posts = [];

if (!function_exists('get_post')) {
    function get_post(int $post_id): ?WP_Post
    {
        global $afg_test_posts;
        return $afg_test_posts[$post_id] ?? null;
    }
}

// ─── WordPress AJAX function stubs ───────────────────────────────────────────

/** @var bool Whether check_ajax_referer should pass */
global $afg_test_check_ajax_referer_result;
$afg_test_check_ajax_referer_result = true;

// Alias for backward compatibility with tests using the old name.
global $afg_test_ajax_referer_valid;
$afg_test_ajax_referer_valid = true;

/** @var array|null Last wp_send_json_error call data */
global $afg_test_json_error_response;
$afg_test_json_error_response = null;

/** @var array|null Last wp_send_json_success call data */
global $afg_test_json_success_response;
$afg_test_json_success_response = null;

/** @var array|null Combined JSON response (for tests using $afg_test_json_response) */
global $afg_test_json_response;
$afg_test_json_response = null;

/** @var bool|int Return value for update_post_meta */
global $afg_test_update_post_meta_return;
$afg_test_update_post_meta_return = true;

/** @var array<int, array<string, mixed>> Captured update_post_meta calls */
global $afg_test_update_post_meta_calls;
$afg_test_update_post_meta_calls = [];

/**
 * Exception thrown by wp_send_json_* stubs to halt handler execution.
 * This simulates the die() that WordPress normally calls.
 */
class Afg_Test_Json_Response_Exception extends \Exception
{
    public ?array $data;
    public ?int $status_code;
    public bool $success;

    public function __construct(string $message = '', ?array $data = null, ?int $status_code = null, bool $success = false)
    {
        $this->data = $data;
        $this->status_code = $status_code;
        $this->success = $success;
        parent::__construct($message);
    }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $stop = true)
    {
        global $afg_test_check_ajax_referer_result, $afg_test_ajax_referer_valid;
        // Support both global names - if either is explicitly set to false, fail.
        if ($afg_test_check_ajax_referer_result === false || $afg_test_ajax_referer_valid === false) {
            return false;
        }
        return 1;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, int $status_code = 200, int $options = 0): void
    {
        global $afg_test_json_error_response, $afg_test_json_response;
        $afg_test_json_error_response = [
            'data'   => $data,
            'status' => $status_code,
        ];
        $afg_test_json_response = [
            'success' => false,
            'data'    => $data,
            'status'  => $status_code,
        ];
        throw new Afg_Test_Json_Response_Exception(
            'wp_send_json_error',
            is_array($data) ? $data : null,
            $status_code,
            false
        );
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, int $status_code = 200, int $options = 0): void
    {
        global $afg_test_json_success_response, $afg_test_json_response;
        $afg_test_json_success_response = [
            'data'   => $data,
            'status' => $status_code,
        ];
        $afg_test_json_response = [
            'success' => true,
            'data'    => $data,
            'status'  => $status_code,
        ];
        throw new Afg_Test_Json_Response_Exception(
            'wp_send_json_success',
            is_array($data) ? $data : null,
            $status_code,
            true
        );
    }
}

if (!function_exists('update_post_meta')) {
    /** @var array<string, array<string, mixed>> In-memory post meta store */
    global $afg_test_post_meta;
    $afg_test_post_meta = [];

    /** @var bool Whether update_post_meta should succeed */
    global $afg_test_update_post_meta_return;
    $afg_test_update_post_meta_return = true;

    function update_post_meta(int $post_id, string $meta_key, $meta_value, $prev_value = '')
    {
        global $afg_test_post_meta, $afg_test_update_post_meta_return;
        if (!$afg_test_update_post_meta_return) {
            return false;
        }
        $afg_test_post_meta["{$post_id}_{$meta_key}"] = $meta_value;
        return true;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $options = 0, int $depth = 512)
    {
        return json_encode($data, $options, $depth);
    }
}

// ─── WordPress output escaping and sanitization stubs ─────────────────────────

if (!function_exists('wp_kses_post')) {
    /**
     * Mimics WordPress wp_kses_post() for testing purposes.
     * Strips script/style tags but allows safe HTML.
     */
    function wp_kses_post(string $data): string
    {
        // Remove script and style tags and their content.
        $data = preg_replace('#<script[^>]*>.*?</script>#is', '', $data);
        $data = preg_replace('#<style[^>]*>.*?</style>#is', '', $data);
        // Remove event handler attributes (onclick, onerror, etc.).
        $data = preg_replace('/\s*on\w+\s*=\s*"[^"]*"/i', '', $data);
        $data = preg_replace("/\s*on\w+\s*=\s*'[^']*'/i", '', $data);
        // Allow safe HTML tags (p, a, strong, em, br, ul, ol, li, h1-h6, span, div, etc.).
        return strip_tags($data, '<p><a><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><span><div><blockquote><code><pre><img><table><thead><tbody><tr><th><td>');
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Mimics WordPress esc_attr() for testing purposes.
     */
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// ─── Load OpenAIClient (after WP stubs are defined) ──────────────────────────

require_once AFG_PLUGIN_PATH . 'includes/class-openai-client.php';

// ─── Load Prompt_Builder service ─────────────────────────────────────────────

require_once __DIR__ . '/../includes/services/class-prompt-builder.php';

// ─── Load Faq_Generator service ──────────────────────────────────────────────

require_once AFG_PLUGIN_PATH . 'includes/services/class-faq-generator.php';

// ─── Load Ajax_Generate_Faqs handler ─────────────────────────────────────────

require_once AFG_PLUGIN_PATH . 'includes/class-ajax-generate-faqs.php';

// ─── WordPress Block API stubs ───────────────────────────────────────────────

/** @var array<int, array<string, mixed>> */
global $afg_test_registered_blocks;
$afg_test_registered_blocks = [];

/** @var mixed Return value for register_block_type stub */
global $afg_test_register_block_type_return;
$afg_test_register_block_type_return = true;

if (!function_exists('register_block_type')) {
    /**
     * Stub for WordPress register_block_type().
     * Records calls and returns a configurable value for testing.
     *
     * @param string|WP_Block_Type $block_type Block type name or path.
     * @param array $args Optional arguments for block type.
     * @return mixed
     */
    function register_block_type($block_type, array $args = [])
    {
        global $afg_test_registered_blocks, $afg_test_register_block_type_return;
        $afg_test_registered_blocks[] = [
            'block_type' => $block_type,
            'args'       => $args,
        ];
        return $afg_test_register_block_type_return;
    }
}

/** @var array<int, string> */
global $afg_test_error_log_messages;
$afg_test_error_log_messages = [];

// ─── Load FAQ Accordion block render callback ────────────────────────────────

require_once AFG_PLUGIN_PATH . 'blocks/faq-accordion/render.php';

// ─── Load FAQ Accordion Block registration ───────────────────────────────────

require_once AFG_PLUGIN_PATH . 'blocks/faq-accordion/class-faq-accordion-block.php';

