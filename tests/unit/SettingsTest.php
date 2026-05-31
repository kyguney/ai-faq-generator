<?php
/**
 * Unit tests for the Settings REST handlers.
 *
 * Validates: Requirements 1.1, 1.2, 1.4, 4.1, 4.2
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Admin\Settings;

class SettingsTest extends TestCase
{
    private Settings $settings;

    protected function setUp(): void
    {
        global $afg_test_actions,
               $afg_test_options,
               $afg_test_rest_routes,
               $afg_test_current_user_can;

        $afg_test_actions = [];
        $afg_test_options = [];
        $afg_test_rest_routes = [];
        $afg_test_current_user_can = true;

        $this->settings = new Settings();
    }

    // ─── GET endpoint tests ──────────────────────────────────────────────────

    /**
     * Validates: Requirement 1.4
     * When no option exists, GET returns default values.
     */
    #[Test]
    public function get_settings_returns_defaults_when_no_option_exists(): void
    {
        $request = new \WP_REST_Request('GET', '/ai-faq-generator/v1/settings');

        $response = $this->settings->get_settings($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame('openai', $data['provider']);
        $this->assertSame('gpt-4o', $data['model']);
        $this->assertSame(0.7, $data['temperature']);
        $this->assertSame(5, $data['faq_count']);
        $this->assertFalse($data['has_api_key']);
        // Empty key should be masked to empty string
        $this->assertSame('', $data['api_key']);
    }

    /**
     * Validates: Requirements 1.2, 4.2
     * When settings exist, GET returns merged values with masked api_key.
     */
    #[Test]
    public function get_settings_returns_merged_settings_with_masked_api_key(): void
    {
        global $afg_test_options;

        $afg_test_options[Settings::OPTION_KEY] = [
            'provider'    => 'deepseek',
            'api_key'     => 'sk-abc1234567890xyz',
            'model'       => 'deepseek-chat',
            'temperature' => 1.2,
            'faq_count'   => 10,
        ];

        $request = new \WP_REST_Request('GET', '/ai-faq-generator/v1/settings');

        $response = $this->settings->get_settings($request);
        $data = $response->get_data();

        $this->assertSame(200, $response->get_status());
        $this->assertSame('deepseek', $data['provider']);
        $this->assertSame('deepseek-chat', $data['model']);
        $this->assertSame(1.2, $data['temperature']);
        $this->assertSame(10, $data['faq_count']);
        $this->assertTrue($data['has_api_key']);
        // API key should be masked: first 3 + **** + last 4
        $this->assertSame('sk-****0xyz', $data['api_key']);
        // Full key must NOT appear in response
        $this->assertNotSame('sk-abc1234567890xyz', $data['api_key']);
    }

    // ─── POST endpoint tests ─────────────────────────────────────────────────

    /**
     * Validates: Requirement 1.1
     * POST persists sanitized values via update_option.
     */
    #[Test]
    public function update_settings_persists_sanitized_values(): void
    {
        global $afg_test_options;

        $request = new \WP_REST_Request('POST', '/ai-faq-generator/v1/settings');
        $request->set_json_params([
            'provider'    => 'ollama',
            'api_key'     => 'my-secret-key-1234',
            'model'       => 'llama3',
            'temperature' => 1.5,
            'faq_count'   => 8,
        ]);

        $response = $this->settings->update_settings($request);
        $data = $response->get_data();

        // Verify response structure
        $this->assertSame(200, $response->get_status());
        $this->assertTrue($data['success']);
        $this->assertSame('ollama', $data['settings']['provider']);
        $this->assertSame('llama3', $data['settings']['model']);
        $this->assertSame(1.5, $data['settings']['temperature']);
        $this->assertSame(8, $data['settings']['faq_count']);
        $this->assertTrue($data['settings']['has_api_key']);
        // API key should be masked in response
        $this->assertSame('my-****1234', $data['settings']['api_key']);

        // Verify persistence in options store
        $stored = $afg_test_options[Settings::OPTION_KEY];
        $this->assertSame('ollama', $stored['provider']);
        $this->assertSame('my-secret-key-1234', $stored['api_key']);
        $this->assertSame('llama3', $stored['model']);
        $this->assertSame(1.5, $stored['temperature']);
        $this->assertSame(8, $stored['faq_count']);
    }

    /**
     * Validates: Requirement 1.1
     * POST with out-of-range values persists clamped values.
     */
    #[Test]
    public function update_settings_clamps_out_of_range_values(): void
    {
        global $afg_test_options;

        $request = new \WP_REST_Request('POST', '/ai-faq-generator/v1/settings');
        $request->set_json_params([
            'provider'    => 'openai',
            'model'       => 'gpt-4o',
            'temperature' => 5.0,
            'faq_count'   => 100,
        ]);

        $this->settings->update_settings($request);

        $stored = $afg_test_options[Settings::OPTION_KEY];
        $this->assertSame(2.0, $stored['temperature']);
        $this->assertSame(20, $stored['faq_count']);
    }

    // ─── Permission check tests ──────────────────────────────────────────────

    /**
     * Validates: Requirement 4.1, 4.2
     * permission_check returns false when user lacks manage_options.
     */
    #[Test]
    public function permission_check_rejects_users_without_manage_options(): void
    {
        global $afg_test_current_user_can;
        $afg_test_current_user_can = false;

        $request = new \WP_REST_Request('GET', '/ai-faq-generator/v1/settings');

        $result = $this->settings->permission_check($request);

        $this->assertFalse($result);
    }

    /**
     * Validates: Requirement 4.1, 4.2
     * permission_check returns true when user has manage_options.
     */
    #[Test]
    public function permission_check_allows_users_with_manage_options(): void
    {
        global $afg_test_current_user_can;
        $afg_test_current_user_can = true;

        $request = new \WP_REST_Request('GET', '/ai-faq-generator/v1/settings');

        $result = $this->settings->permission_check($request);

        $this->assertTrue($result);
    }

    // ─── Route registration tests ────────────────────────────────────────────

    /**
     * Validates: Requirement 1.1, 1.2
     * init() hooks register_routes on rest_api_init.
     */
    #[Test]
    public function init_registers_rest_api_init_hook(): void
    {
        $this->settings->init();

        global $afg_test_actions;

        $hooks = array_column($afg_test_actions, 'hook');
        $this->assertContains('rest_api_init', $hooks);
    }

    /**
     * Validates: Requirement 1.1, 1.2
     * register_routes registers the settings route with GET and POST methods.
     */
    #[Test]
    public function register_routes_registers_settings_endpoint(): void
    {
        $this->settings->register_routes();

        global $afg_test_rest_routes;

        $this->assertCount(1, $afg_test_rest_routes);

        $route = $afg_test_rest_routes[0];
        $this->assertSame('ai-faq-generator/v1', $route['namespace']);
        $this->assertSame('/settings', $route['route']);

        // Should have GET and POST handlers
        $this->assertCount(2, $route['args']);
        $this->assertSame('GET', $route['args'][0]['methods']);
        $this->assertSame('POST', $route['args'][1]['methods']);
    }
}
