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
               $afg_test_registered_settings,
               $afg_test_settings_sections,
               $afg_test_current_user_can,
               $afg_test_settings_fields_calls,
               $afg_test_do_settings_sections_calls,
               $afg_test_submit_button_calls;

        $afg_test_actions = [];
        $afg_test_menu_pages = [];
        $afg_test_registered_settings = [];
        $afg_test_settings_sections = [];
        $afg_test_current_user_can = true;
        $afg_test_settings_fields_calls = [];
        $afg_test_do_settings_sections_calls = [];
        $afg_test_submit_button_calls = 0;

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
        $this->assertSame('AI FAQ Generator', $menu['menu_title']);
    }

    /**
     * Validates: Requirement 5.6
     * Test that afg_settings group is registered.
     */
    #[Test]
    public function register_settings_registers_afg_settings_group(): void
    {
        $this->admin->register_settings();

        global $afg_test_registered_settings;

        $this->assertCount(1, $afg_test_registered_settings);
        $this->assertSame('afg_settings', $afg_test_registered_settings[0]['option_group']);
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
     * Test that render method outputs settings form when user has capability.
     */
    #[Test]
    public function render_admin_page_outputs_settings_form_when_authorized(): void
    {
        global $afg_test_current_user_can,
               $afg_test_settings_fields_calls,
               $afg_test_do_settings_sections_calls,
               $afg_test_submit_button_calls;

        $afg_test_current_user_can = true;

        ob_start();
        $this->admin->render_admin_page();
        $output = ob_get_clean();

        // Verify settings_fields was called with the correct group.
        $this->assertContains('afg_settings', $afg_test_settings_fields_calls);

        // Verify do_settings_sections was called with the correct page.
        $this->assertContains('ai-faq-generator', $afg_test_do_settings_sections_calls);

        // Verify submit_button was called.
        $this->assertSame(1, $afg_test_submit_button_calls);

        // Verify the output contains the wrap container and heading.
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('AI FAQ Generator Settings', $output);
        $this->assertStringContainsString('<form', $output);
    }
}
