<?php
/**
 * Property-based tests for the Settings sanitizer and API key masking.
 *
 * Uses PHPUnit DataProvider pattern with 100+ generated inputs per property
 * to verify sanitization invariants hold across all valid and invalid inputs.
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 4.4
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Admin\Settings;

class SettingsPropertyTest extends TestCase
{
    private Settings $settings;

    protected function setUp(): void
    {
        $this->settings = new Settings();

        // Reset the stored option to defaults before each test.
        global $afg_test_options;
        $afg_test_options = [];
    }

    // ─── Property 2: Invalid provider rejection ──────────────────────────────

    /**
     * **Validates: Requirements 3.1**
     *
     * Property 2: Invalid provider rejection.
     * For any string NOT in ALLOWED_PROVIDERS, the sanitizer SHALL reject it
     * and the stored provider value SHALL remain unchanged (defaults to 'openai').
     */
    #[Test]
    #[DataProvider('invalidProviderProvider')]
    public function sanitizer_rejects_invalid_provider_and_retains_previous(string $invalidProvider): void
    {
        // Set a known current provider in the stored option.
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['provider' => 'deepseek'];

        $result = $this->settings->sanitize(['provider' => $invalidProvider]);

        $this->assertSame(
            'deepseek',
            $result['provider'],
            "Invalid provider '{$invalidProvider}' should be rejected; previous value 'deepseek' should be retained."
        );
    }

    /**
     * Data provider generating 100+ strings that are NOT in ALLOWED_PROVIDERS.
     *
     * @return array<string, array{string}>
     */
    public static function invalidProviderProvider(): array
    {
        $cases = [];
        $allowed = Settings::ALLOWED_PROVIDERS;

        mt_srand(100);

        for ($i = 0; $i < 100; $i++) {
            $invalid = self::generateRandomString(mt_rand(1, 30));
            // Ensure it's not accidentally a valid provider.
            while (in_array($invalid, $allowed, true)) {
                $invalid = self::generateRandomString(mt_rand(1, 30));
            }
            $cases["random_invalid_provider_{$i}"] = [$invalid];
        }

        // Edge cases
        $edgeCases = [
            'empty_string'       => [''],
            'uppercase_openai'   => ['OpenAI'],
            'with_spaces'        => ['open ai'],
            'with_special_chars' => ['openai!'],
            'numeric'            => ['12345'],
            'partial_match'      => ['open'],
            'trailing_space'     => ['openai '],
            'leading_space'      => [' openai'],
            'null_byte'          => ["openai\0"],
            'unicode'            => ['öpenai'],
        ];

        return array_merge($cases, $edgeCases);
    }

    // ─── Property 3: Numeric field clamping ──────────────────────────────────

    /**
     * **Validates: Requirements 3.2, 3.3**
     *
     * Property 3: Numeric field clamping.
     * For any numeric input, temperature is clamped to [0.0, 2.0]
     * and faq_count is clamped to [1, 20].
     */
    #[Test]
    #[DataProvider('numericInputProvider')]
    public function sanitizer_clamps_numeric_fields_to_valid_ranges(float $temperature, int $faqCount): void
    {
        $result = $this->settings->sanitize([
            'temperature' => $temperature,
            'faq_count'   => $faqCount,
        ]);

        // Temperature must be in [0.0, 2.0]
        $this->assertGreaterThanOrEqual(
            0.0,
            $result['temperature'],
            "Temperature must be >= 0.0, got {$result['temperature']} for input {$temperature}"
        );
        $this->assertLessThanOrEqual(
            2.0,
            $result['temperature'],
            "Temperature must be <= 2.0, got {$result['temperature']} for input {$temperature}"
        );

        // Verify clamping formula: max(0.0, min(2.0, input))
        $expectedTemp = max(0.0, min(2.0, $temperature));
        $this->assertEqualsWithDelta(
            $expectedTemp,
            $result['temperature'],
            0.0001,
            "Temperature should be clamped to max(0.0, min(2.0, {$temperature}))"
        );

        // FAQ count must be in [1, 20]
        $this->assertGreaterThanOrEqual(
            1,
            $result['faq_count'],
            "faq_count must be >= 1, got {$result['faq_count']} for input {$faqCount}"
        );
        $this->assertLessThanOrEqual(
            20,
            $result['faq_count'],
            "faq_count must be <= 20, got {$result['faq_count']} for input {$faqCount}"
        );

        // Verify clamping formula: max(1, min(20, input))
        $expectedFaq = max(1, min(20, $faqCount));
        $this->assertSame(
            $expectedFaq,
            $result['faq_count'],
            "faq_count should be clamped to max(1, min(20, {$faqCount}))"
        );
    }

    /**
     * Data provider generating 100+ numeric input combinations.
     *
     * @return array<string, array{float, int}>
     */
    public static function numericInputProvider(): array
    {
        $cases = [];

        mt_srand(200);

        for ($i = 0; $i < 100; $i++) {
            // Generate temperature in range [-10.0, 12.0] to test both in-range and out-of-range
            $temp = (mt_rand(-1000, 1200) / 100.0);
            // Generate faq_count in range [-50, 100] to test both in-range and out-of-range
            $faq = mt_rand(-50, 100);
            $cases["random_numeric_{$i}"] = [$temp, $faq];
        }

        // Edge cases
        $edgeCases = [
            'both_at_min'        => [0.0, 1],
            'both_at_max'        => [2.0, 20],
            'temp_below_min'     => [-1.0, 10],
            'temp_above_max'     => [3.5, 10],
            'faq_below_min'      => [1.0, 0],
            'faq_above_max'      => [1.0, 100],
            'both_below_min'     => [-100.0, -50],
            'both_above_max'     => [999.9, 999],
            'zero_values'        => [0.0, 0],
            'negative_both'      => [-5.5, -10],
            'large_positive'     => [PHP_FLOAT_MAX, PHP_INT_MAX],
            'large_negative'     => [-PHP_FLOAT_MAX, PHP_INT_MIN],
        ];

        return array_merge($cases, $edgeCases);
    }

    // ─── Property 4: Whitespace model rejection ──────────────────────────────

    /**
     * **Validates: Requirements 3.4**
     *
     * Property 4: Whitespace model rejection.
     * For any string composed entirely of whitespace characters (including empty string),
     * the sanitizer SHALL reject it as a model value and the stored model SHALL remain unchanged.
     */
    #[Test]
    #[DataProvider('whitespaceModelProvider')]
    public function sanitizer_rejects_whitespace_only_model_and_retains_previous(string $whitespaceModel): void
    {
        // Set a known current model in the stored option.
        global $afg_test_options;
        $afg_test_options[Settings::OPTION_KEY] = ['model' => 'gpt-4o'];

        $result = $this->settings->sanitize(['model' => $whitespaceModel]);

        $this->assertSame(
            'gpt-4o',
            $result['model'],
            "Whitespace-only model should be rejected; previous value 'gpt-4o' should be retained."
        );
    }

    /**
     * Data provider generating 100+ whitespace-only strings.
     *
     * @return array<string, array{string}>
     */
    public static function whitespaceModelProvider(): array
    {
        $cases = [];
        $whitespaceChars = [' ', "\t", "\n", "\r", "\v", "\f"];

        mt_srand(300);

        for ($i = 0; $i < 100; $i++) {
            $length = mt_rand(1, 20);
            $str = '';
            for ($j = 0; $j < $length; $j++) {
                $str .= $whitespaceChars[mt_rand(0, count($whitespaceChars) - 1)];
            }
            $cases["random_whitespace_{$i}"] = [$str];
        }

        // Edge cases
        $edgeCases = [
            'empty_string'     => [''],
            'single_space'     => [' '],
            'single_tab'       => ["\t"],
            'single_newline'   => ["\n"],
            'crlf'             => ["\r\n"],
            'mixed_whitespace' => [" \t\n\r\v\f "],
            'many_spaces'      => [str_repeat(' ', 100)],
            'many_tabs'        => [str_repeat("\t", 50)],
            'many_newlines'    => [str_repeat("\n", 30)],
        ];

        return array_merge($cases, $edgeCases);
    }

    // ─── Property 5: API key masking ─────────────────────────────────────────

    /**
     * **Validates: Requirements 4.4**
     *
     * Property 5: API key masking.
     * For any stored API key string of length >= 7, the masked output SHALL show
     * only the first 3 and last 4 characters, with '****' in the middle.
     */
    #[Test]
    #[DataProvider('apiKeyMaskingProvider')]
    public function mask_api_key_shows_only_first_3_and_last_4_chars(string $apiKey): void
    {
        $masked = $this->settings->mask_api_key($apiKey);

        // The masked key must NOT equal the full key.
        $this->assertNotSame(
            $apiKey,
            $masked,
            "Masked key must not be the same as the original key."
        );

        // First 3 characters must match.
        $this->assertSame(
            substr($apiKey, 0, 3),
            substr($masked, 0, 3),
            "First 3 characters of masked key must match original."
        );

        // Last 4 characters must match.
        $this->assertSame(
            substr($apiKey, -4),
            substr($masked, -4),
            "Last 4 characters of masked key must match original."
        );

        // Middle must be '****'.
        $middle = substr($masked, 3, -4);
        $this->assertSame(
            '****',
            $middle,
            "Middle of masked key must be '****'."
        );

        // Total length must be 3 + 4 + 4 = 11.
        $this->assertSame(
            11,
            strlen($masked),
            "Masked key length must be 11 (3 + 4 asterisks + 4)."
        );

        // The full original key must NOT appear in the masked output.
        $this->assertStringNotContainsString(
            $apiKey,
            $masked,
            "Full API key must never appear in masked output."
        );
    }

    /**
     * Data provider generating 100+ API keys of length >= 7.
     *
     * @return array<string, array{string}>
     */
    public static function apiKeyMaskingProvider(): array
    {
        $cases = [];

        mt_srand(400);

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

        for ($i = 0; $i < 100; $i++) {
            $length = mt_rand(7, 100);
            $key = '';
            for ($j = 0; $j < $length; $j++) {
                $key .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            $cases["random_api_key_{$i}"] = [$key];
        }

        // Edge cases
        $edgeCases = [
            'exactly_7_chars'    => ['sk-abcd'],
            'exactly_8_chars'    => ['sk-abcde'],
            'long_key_50'        => [str_repeat('x', 50)],
            'long_key_128'       => [str_repeat('A', 128)],
            'openai_format'      => ['sk-proj-abcdefghijklmnopqrstuvwxyz1234567890'],
            'with_dashes'        => ['key-abc-def-ghi-jkl-mno'],
            'with_underscores'   => ['key_abc_def_ghi_jkl_mno'],
            'numeric_key'        => ['1234567890123456'],
            'mixed_special'      => ['aB3-dE5_gH7-iJ9_kL1'],
        ];

        return array_merge($cases, $edgeCases);
    }

    // ─── Property 6: Text field sanitization ─────────────────────────────────

    /**
     * **Validates: Requirements 3.5**
     *
     * Property 6: Text field sanitization.
     * For any string containing HTML tags or script content provided as model or api_key,
     * the sanitizer SHALL strip all HTML/script tags before storage.
     */
    #[Test]
    #[DataProvider('htmlInjectionProvider')]
    public function sanitizer_strips_html_and_script_tags_from_text_fields(string $maliciousInput): void
    {
        $result = $this->settings->sanitize([
            'model'   => $maliciousInput,
            'api_key' => $maliciousInput,
        ]);

        // Model: must not contain any HTML tags.
        $this->assertDoesNotMatchRegularExpression(
            '/<[^>]*>/',
            $result['model'],
            "Model field must not contain HTML tags after sanitization."
        );

        // API key: must not contain any HTML tags.
        $this->assertDoesNotMatchRegularExpression(
            '/<[^>]*>/',
            $result['api_key'],
            "API key field must not contain HTML tags after sanitization."
        );

        // Neither field should contain <script> content.
        $this->assertStringNotContainsString(
            '<script',
            strtolower($result['model']),
            "Model field must not contain script tags."
        );
        $this->assertStringNotContainsString(
            '<script',
            strtolower($result['api_key']),
            "API key field must not contain script tags."
        );
    }

    /**
     * Data provider generating 100+ strings with HTML/script tags.
     *
     * @return array<string, array{string}>
     */
    public static function htmlInjectionProvider(): array
    {
        $cases = [];

        mt_srand(500);

        $tags = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert(1)>',
            '<div>content</div>',
            '<a href="javascript:void(0)">link</a>',
            '<iframe src="evil.com"></iframe>',
            '<style>body{display:none}</style>',
            '<svg onload=alert(1)>',
            '<input type="text" onfocus="alert(1)">',
            '<marquee>scrolling</marquee>',
            '<object data="evil.swf"></object>',
        ];

        $prefixes = ['model-', 'gpt-4o', 'key-', 'test', 'value_', ''];
        $suffixes = ['-end', '_final', '', '-v2', '.1', ' extra'];

        for ($i = 0; $i < 100; $i++) {
            $tag = $tags[mt_rand(0, count($tags) - 1)];
            $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
            $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];
            $input = $prefix . $tag . $suffix;
            $cases["random_html_injection_{$i}"] = [$input];
        }

        // Edge cases
        $edgeCases = [
            'script_only'           => ['<script>alert("xss")</script>'],
            'nested_script'         => ['<div><script>evil()</script></div>'],
            'uppercase_script'      => ['<SCRIPT>alert(1)</SCRIPT>'],
            'mixed_case_script'     => ['<ScRiPt>alert(1)</sCrIpT>'],
            'self_closing_tag'      => ['<br/>model-name'],
            'unclosed_tag'          => ['<div>unclosed'],
            'multiple_tags'         => ['<b><i><u>text</u></i></b>'],
            'event_handler'         => ['<img src=x onerror="alert(document.cookie)">'],
            'data_uri'              => ['<a href="data:text/html,<script>alert(1)</script>">click</a>'],
            'encoded_entities'      => ['<script>alert(&#39;xss&#39;)</script>'],
            'valid_text_with_angle' => ['model > v2'],
        ];

        return array_merge($cases, $edgeCases);
    }

    // ─── Helper methods ──────────────────────────────────────────────────────

    /**
     * Generate a random string of given length.
     */
    private static function generateRandomString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_-. ';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
