<?php
/**
 * PHPUnit bootstrap file for AI FAQ Generator plugin tests.
 */

declare(strict_types=1);

// Define the plugin path constant needed by the Loader class.
if (!defined('AFG_PLUGIN_PATH')) {
    define('AFG_PLUGIN_PATH', dirname(__DIR__) . '/');
}

// Load the Loader class directly (no Composer autoload in plugin yet).
require_once AFG_PLUGIN_PATH . 'includes/class-loader.php';

// Load the Admin class.
require_once AFG_PLUGIN_PATH . 'admin/class-admin.php';

// ─── WordPress function stubs for unit testing ───────────────────────────────
// These stubs record calls so tests can assert hook registrations and behavior.

/** @var array<int, array{hook: string, callback: mixed, priority: int}> */
global $afg_test_actions;
$afg_test_actions = [];

/** @var array<int, array<string, mixed>> */
global $afg_test_menu_pages;
$afg_test_menu_pages = [];

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
