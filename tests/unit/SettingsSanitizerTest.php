<?php
/**
 * Unit tests for the Settings class sanitize() and mask_api_key() methods.
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 4.4
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Admin\Settings;

class SettingsSanitizerTest extends TestCase
{
    private Settings $settings;

    protected function setUp(): void
    {
        global $afg_test_options;
        $afg_test_options = [];

        $this->settings = new Settings();
    }

    // ─── Constants ───────────────────────────────────────────────────────────

    #[Test]
    public function constants_are_defined_correctly(): void
    {
        $this->assertSame('afg_settings', Settings::OPTION_KEY);
        $this->assertSame('ai-faq-generator/v1', Settings::REST_NAMESPACE);
        $this->assertSame('/settings', Settings::REST_ROUTE);
        $this->assertSame(
            ['openai', 'openrouter', 'ollama', 'deepseek', 'localai', 'lmstudio'],
            Settings::ALLOWED_PROVIDERS
        );
        $this->assertSame([
            'provider'    => 'openai',
            'api_key'     => '',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'faq_count'   => 5,
            'base_url'    => 'https://api.openai.com',
        ], Settings::DEFAULTS);
    }

    // ─── sanitize(): Provider validation ─────────────────────────────────────

    #[Test]
    public function sanitize_accepts_valid_provider(): void
    {
        $result = $this->settings->sanitize(['provider' => 'deepseek']);
        $this->assertSame('deepseek', $result['provider']);
    }

    #[Test]
    public function sanitize_rejects_invalid_provider_and_keeps_current(): void
    {
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['provider' => 'ollama'];

        $settings = new Settings();
        $result = $settings->sanitize(['provider' => 'invalid-provider']);
        $this->assertSame('ollama', $result['provider']);
    }

    #[Test]
    public function sanitize_rejects_invalid_provider_uses_default_when_no_stored(): void
    {
        $result = $this->settings->sanitize(['provider' => 'not-a-provider']);
        $this->assertSame('openai', $result['provider']);
    }

    // ─── sanitize(): Temperature clamping ────────────────────────────────────

    #[Test]
    public function sanitize_clamps_temperature_below_zero(): void
    {
        $result = $this->settings->sanitize(['temperature' => -1.5]);
        $this->assertSame(0.0, $result['temperature']);
    }

    #[Test]
    public function sanitize_clamps_temperature_above_two(): void
    {
        $result = $this->settings->sanitize(['temperature' => 5.0]);
        $this->assertSame(2.0, $result['temperature']);
    }

    #[Test]
    public function sanitize_accepts_valid_temperature(): void
    {
        $result = $this->settings->sanitize(['temperature' => 1.3]);
        $this->assertSame(1.3, $result['temperature']);
    }

    #[Test]
    public function sanitize_clamps_temperature_at_boundary_zero(): void
    {
        $result = $this->settings->sanitize(['temperature' => 0.0]);
        $this->assertSame(0.0, $result['temperature']);
    }

    #[Test]
    public function sanitize_clamps_temperature_at_boundary_two(): void
    {
        $result = $this->settings->sanitize(['temperature' => 2.0]);
        $this->assertSame(2.0, $result['temperature']);
    }

    // ─── sanitize(): FAQ count clamping ──────────────────────────────────────

    #[Test]
    public function sanitize_clamps_faq_count_below_one(): void
    {
        $result = $this->settings->sanitize(['faq_count' => 0]);
        $this->assertSame(1, $result['faq_count']);
    }

    #[Test]
    public function sanitize_clamps_faq_count_above_twenty(): void
    {
        $result = $this->settings->sanitize(['faq_count' => 50]);
        $this->assertSame(20, $result['faq_count']);
    }

    #[Test]
    public function sanitize_accepts_valid_faq_count(): void
    {
        $result = $this->settings->sanitize(['faq_count' => 10]);
        $this->assertSame(10, $result['faq_count']);
    }

    #[Test]
    public function sanitize_clamps_negative_faq_count(): void
    {
        $result = $this->settings->sanitize(['faq_count' => -5]);
        $this->assertSame(1, $result['faq_count']);
    }

    // ─── sanitize(): Model validation ────────────────────────────────────────

    #[Test]
    public function sanitize_accepts_valid_model(): void
    {
        $result = $this->settings->sanitize(['model' => 'gpt-3.5-turbo']);
        $this->assertSame('gpt-3.5-turbo', $result['model']);
    }

    #[Test]
    public function sanitize_rejects_empty_model_and_keeps_current(): void
    {
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['model' => 'claude-3'];

        $settings = new Settings();
        $result = $settings->sanitize(['model' => '']);
        $this->assertSame('claude-3', $result['model']);
    }

    #[Test]
    public function sanitize_rejects_whitespace_only_model(): void
    {
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['model' => 'gpt-4o'];

        $settings = new Settings();
        $result = $settings->sanitize(['model' => '   ']);
        $this->assertSame('gpt-4o', $result['model']);
    }

    #[Test]
    public function sanitize_strips_html_from_model(): void
    {
        $result = $this->settings->sanitize(['model' => '<b>gpt-4o</b>']);
        $this->assertStringNotContainsString('<b>', $result['model']);
        $this->assertStringContainsString('gpt-4o', $result['model']);
    }

    // ─── sanitize(): API key sanitization ────────────────────────────────────

    #[Test]
    public function sanitize_applies_sanitize_text_field_to_api_key(): void
    {
        $result = $this->settings->sanitize(['api_key' => '<b>sk-test-key</b>']);
        $this->assertSame('sk-test-key', $result['api_key']);
    }

    #[Test]
    public function sanitize_preserves_current_api_key_when_not_provided(): void
    {
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['api_key' => 'sk-existing-key'];

        $settings = new Settings();
        $result = $settings->sanitize(['provider' => 'openai']);
        $this->assertSame('sk-existing-key', $result['api_key']);
    }

    // ─── mask_api_key() ──────────────────────────────────────────────────────

    #[Test]
    public function mask_api_key_shows_first_3_and_last_4_chars(): void
    {
        $masked = $this->settings->mask_api_key('sk-1234567890abcdef');
        $this->assertSame('sk-****cdef', $masked);
    }

    #[Test]
    public function mask_api_key_handles_exactly_7_chars(): void
    {
        $masked = $this->settings->mask_api_key('1234567');
        $this->assertSame('123****4567', $masked);
    }

    #[Test]
    public function mask_api_key_fully_masks_short_keys(): void
    {
        $masked = $this->settings->mask_api_key('abc');
        $this->assertSame('***', $masked);
    }

    #[Test]
    public function mask_api_key_handles_empty_string(): void
    {
        $masked = $this->settings->mask_api_key('');
        $this->assertSame('', $masked);
    }

    #[Test]
    public function mask_api_key_handles_6_char_key(): void
    {
        $masked = $this->settings->mask_api_key('abcdef');
        $this->assertSame('******', $masked);
    }
}
