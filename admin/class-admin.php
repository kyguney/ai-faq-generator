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
        register_setting('afg_settings', 'afg_settings');

        add_settings_section(
            'afg_main_section',
            'Main Settings',
            '__return_false',
            'ai-faq-generator'
        );
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
        echo '<form method="post" action="options.php">';
        settings_fields('afg_settings');
        do_settings_sections('ai-faq-generator');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
