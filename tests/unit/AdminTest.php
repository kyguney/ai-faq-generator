<?php
/**
 * Unit tests for the Admin class.
 *
 * Validates: Requirements 5.3, 5.4, 5.5, 5.6
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Admin\Admin;

class AdminTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        // Reset all global stub trackers before each test.
        global $afg_test_actions,
               $afg_test_menu_pages,
               $afg_test_submenu_pages,
               $afg_test_registered_settings,
               $afg_test_settings_sections,
               $afg_test_current_user_can,
               $afg_test_settings_fields_calls,
               $afg_test_do_settings_sections_calls,
               $afg_test_submit_button_calls,
               $afg_test_enqueued_scripts,
               $afg_test_enqueued_styles,
               $afg_test_localized_scripts;

        $afg_test_actions = [];
        $afg_test_menu_pages = [];
        $afg_test_submenu_pages = [];
        $afg_test_registered_settings = [];
        $afg_test_settings_sections = [];
        $afg_test_current_user_can = true;
        $afg_test_settings_fields_calls = [];
        $afg_test_do_settings_sections_calls = [];
        $afg_test_submit_button_calls = 0;
        $afg_test_enqueued_scripts = [];
        $afg_test_enqueued_styles = [];
        $afg_test_localized_scripts = [];

        $this->admin = new Admin();
    }

    /**
     * Validates: Requirement 5.3, 5.6
     * Test that init() registers admin_menu and admin_init hooks.
     */
    #[Test]
    public function init_registers_admin_menu_and_admin_init_hooks(): void
    {
        $this->admin->init();

        global $afg_test_actions;

        $hooks = array_column($afg_test_actions, 'hook');

        $this->assertContains('admin_menu', $hooks);
        $this->assertContains('admin_init', $hooks);
    }

    /**
     * Validates: Requirement 5.3
     * Test that menu is registered with correct slug and capability.
     */
    #[Test]
    public function add_admin_menu_registers_menu_with_correct_slug_and_capability(): void
    {
        $this->admin->add_admin_menu();

        global $afg_test_menu_pages;

        $this->assertCount(1, $afg_test_menu_pages);

        $menu = $afg_test_menu_pages[0];
        $this->assertSame('ai-faq-generator', $menu['menu_slug']);
        $this->assertSame('manage_options', $menu['capability']);
        $this->assertSame('AI FAQ', $menu['menu_title']);
        $this->assertSame('dashicons-format-chat', $menu['icon_url']);
    }

    /**
     * Validates: Requirement 4.1
     * Test that add_admin_menu registers a "Dashboard" submenu and a "Settings" submenu.
     */
    #[Test]
    public function add_admin_menu_registers_settings_submenu(): void
    {
        $this->admin->add_admin_menu();

        global $afg_test_submenu_pages;

        $this->assertCount(2, $afg_test_submenu_pages);

        // First submenu renames the default entry to "Dashboard".
        $dashboard = $afg_test_submenu_pages[0];
        $this->assertSame('ai-faq-generator', $dashboard['parent_slug']);
        $this->assertSame('Dashboard', $dashboard['menu_title']);
        $this->assertSame('manage_options', $dashboard['capability']);
        $this->assertSame('ai-faq-generator', $dashboard['menu_slug']);

        // Second submenu is the Settings page.
        $settings = $afg_test_submenu_pages[1];
        $this->assertSame('ai-faq-generator', $settings['parent_slug']);
        $this->assertSame('AI FAQ Generator Settings', $settings['page_title']);
        $this->assertSame('Settings', $settings['menu_title']);
        $this->assertSame('manage_options', $settings['capability']);
        $this->assertSame('ai-faq-generator-settings', $settings['menu_slug']);
    }

    /**
     * Validates: Requirement 5.6
     * Test that register_settings is a no-op (settings managed via REST API).
     */
    #[Test]
    public function register_settings_is_noop(): void
    {
        $this->admin->register_settings();

        global $afg_test_registered_settings;

        $this->assertCount(0, $afg_test_registered_settings);
    }

    /**
     * Validates: Requirement 5.4
     * Test that render method checks manage_options capability.
     * When user lacks the capability, no output should be produced.
     */
    #[Test]
    public function render_admin_page_returns_early_without_manage_options(): void
    {
        global $afg_test_current_user_can;
        $afg_test_current_user_can = false;

        ob_start();
        $this->admin->render_admin_page();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Validates: Requirement 5.5
     * Test that render_admin_page outputs the dashboard page when user has capability.
     */
    #[Test]
    public function render_admin_page_outputs_settings_form_when_authorized(): void
    {
        global $afg_test_current_user_can;

        $afg_test_current_user_can = true;

        ob_start();
        $this->admin->render_admin_page();
        $output = ob_get_clean();

        // Verify the output contains the wrap container and heading.
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('AI FAQ Generator', $output);
    }

    /**
     * Validates: Requirement 2.1
     * Test that render_settings_page outputs the React mount point when user has capability.
     */
    #[Test]
    public function render_settings_page_outputs_react_mount_point_when_authorized(): void
    {
        global $afg_test_current_user_can;

        $afg_test_current_user_can = true;

        ob_start();
        $this->admin->render_settings_page();
        $output = ob_get_clean();

        // Verify the output contains the wrap container and heading.
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('AI FAQ Generator Settings', $output);
        // Verify the React mount point is present.
        $this->assertStringContainsString('<div id="afg-settings-root"></div>', $output);
    }

    /**
     * Test that render_settings_page returns early without manage_options.
     */
    #[Test]
    public function render_settings_page_returns_early_without_manage_options(): void
    {
        global $afg_test_current_user_can;
        $afg_test_current_user_can = false;

        ob_start();
        $this->admin->render_settings_page();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    /**
     * Validates: Requirement 6.1
     * Test that settings assets are enqueued when hook_suffix matches the settings page.
     */
    #[Test]
    public function enqueue_settings_assets_enqueues_when_hook_matches(): void
    {
        global $afg_test_enqueued_scripts,
               $afg_test_enqueued_styles,
               $afg_test_localized_scripts;

        $this->admin->enqueue_settings_assets('ai-faq_page_ai-faq-generator-settings');

        // Verify script was enqueued with correct handle.
        $this->assertCount(1, $afg_test_enqueued_scripts);
        $this->assertSame('afg-settings', $afg_test_enqueued_scripts[0]['handle']);
        $this->assertStringContainsString('build/settings.js', $afg_test_enqueued_scripts[0]['src']);

        // Verify style was enqueued with correct handle.
        $this->assertCount(1, $afg_test_enqueued_styles);
        $this->assertSame('afg-settings', $afg_test_enqueued_styles[0]['handle']);
        $this->assertStringContainsString('build/settings.css', $afg_test_enqueued_styles[0]['src']);

        // Verify wp_localize_script was called with afgSettings object.
        $this->assertCount(1, $afg_test_localized_scripts);
        $this->assertSame('afg-settings', $afg_test_localized_scripts[0]['handle']);
        $this->assertSame('afgSettings', $afg_test_localized_scripts[0]['object_name']);
        $this->assertArrayHasKey('restUrl', $afg_test_localized_scripts[0]['l10n']);
        $this->assertArrayHasKey('nonce', $afg_test_localized_scripts[0]['l10n']);
    }

    /**
     * Validates: Requirement 6.2
     * Test that settings assets are NOT enqueued for other admin pages.
     */
    #[Test]
    public function enqueue_settings_assets_does_not_enqueue_for_other_pages(): void
    {
        global $afg_test_enqueued_scripts,
               $afg_test_enqueued_styles,
               $afg_test_localized_scripts;

        $this->admin->enqueue_settings_assets('toplevel_page_some-other-plugin');

        // No scripts should be enqueued.
        $this->assertEmpty($afg_test_enqueued_scripts);

        // No styles should be enqueued.
        $this->assertEmpty($afg_test_enqueued_styles);

        // No localized scripts should be set.
        $this->assertEmpty($afg_test_localized_scripts);
    }
}
