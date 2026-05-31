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
            'AI FAQ Generator Settings',
            'AI FAQ Generator',
            'manage_options',
            'ai-faq-generator',
            [$this, 'render_admin_page'],
            'dashicons-format-chat',
            30
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
        echo '<h1>AI FAQ Generator Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('afg_settings');
        do_settings_sections('ai-faq-generator');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
