<?php
/**
 * Admin class
 *
 * Admin-specific functionality
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Admin;

class Admin
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_settings_assets']);
    }

    public function add_admin_menu(): void
    {
        add_menu_page(
            'AI FAQ Generator',
            'AI FAQ',
            'manage_options',
            'ai-faq-generator',
            [$this, 'render_admin_page'],
            'dashicons-format-chat',
            30
        );

        // Rename the default first submenu from "AI FAQ" to "Dashboard"
        add_submenu_page(
            'ai-faq-generator',
            'AI FAQ Generator',
            'Dashboard',
            'manage_options',
            'ai-faq-generator',
            [$this, 'render_admin_page']
        );

        // Settings submenu page (placeholder)
        add_submenu_page(
            'ai-faq-generator',
            'AI FAQ Generator Settings',
            'Settings',
            'manage_options',
            'ai-faq-generator-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        // Settings are now managed via REST API (class-settings.php).
        // This method is kept for backward compatibility with the admin_init hook.
    }

    public function render_admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>AI FAQ Generator</h1>';
        echo '<p>Welcome to AI FAQ Generator. Use the submenu to navigate.</p>';
        echo '</div>';
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>AI FAQ Generator Settings</h1>';
        echo '<div id="afg-settings-root"></div>';
        echo '</div>';
    }

    public function enqueue_settings_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'ai-faq_page_ai-faq-generator-settings') {
            return;
        }

        $asset_file = AFG_PLUGIN_PATH . 'build/settings.asset.php';

        if (!file_exists($asset_file)) {
            return;
        }

        $asset = require $asset_file;

        wp_enqueue_script(
            'afg-settings',
            plugins_url('build/settings.js', dirname(__FILE__)),
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'afg-settings',
            plugins_url('build/settings.css', dirname(__FILE__)),
            [],
            $asset['version']
        );

        wp_localize_script('afg-settings', 'afgSettings', [
            'restUrl' => rest_url('ai-faq-generator/v1'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }
}
