<?php
/**
 * Unit tests for the JSON_LD_Generator service.
 *
 * Validates: Requirements 1.1, 1.2, 1.4, 5.1, 5.2, 5.4
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Loader;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdGeneratorTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_actions, $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_actions = [];
        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
        $afg_test_current_post_id = 42;

        $this->generator = new JSON_LD_Generator();
    }

    protected function tearDown(): void
    {
        global $afg_test_actions, $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_actions = [];
        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
        $afg_test_current_post_id = 42;
    }

    // ─── Requirement 5.1, 5.2: init registers wp_head at priority 20 ─────────

    /**
     * Validates: Requirements 5.1, 5.2
     * init() registers wp_head action at priority 20 with a public method callback.
     */
    #[Test]
    public function init_registers_wp_head_action_at_priority_20(): void
    {
        global $afg_test_actions;

        $this->generator->init();

        $this->assertCount(1, $afg_test_actions);
        $this->assertSame('wp_head', $afg_test_actions[0]['hook']);
        $this->assertSame(20, $afg_test_actions[0]['priority']);
    }

    // ─── Requirement 5.4: callback is publicly accessible callable ───────────

    /**
     * Validates: Requirement 5.4
     * The callback registered by init() is a publicly accessible callable
     * (not a closure) for remove_action() compatibility.
     */
    #[Test]
    public function init_callback_is_publicly_accessible_callable(): void
    {
        global $afg_test_actions;

        $this->generator->init();

        $callback = $afg_test_actions[0]['callback'];

        // Must be an array (not a Closure)
        $this->assertIsArray($callback);

        // Must be a valid callable
        $this->assertIsCallable($callback);

        // Must reference the generator instance and a public method
        $this->assertSame($this->generator, $callback[0]);
        $this->assertSame('output_schema', $callback[1]);

        // Verify the method is public via reflection
        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        $this->assertTrue($reflection->isPublic());
    }

    // ─── Requirement 1.4: no output when is_singular() returns false ─────────

    /**
     * Validates: Requirement 1.4
     * output_schema() produces no output when is_singular() returns false.
     */
    #[Test]
    public function output_schema_produces_no_output_when_not_singular(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values;

        $afg_test_is_singular = false;
        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode([
            ['question' => 'Q1', 'answer' => 'A1'],
        ]);

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    // ─── Requirement 1.2: no output when meta is empty string ────────────────

    /**
     * Validates: Requirement 1.2
     * output_schema() produces no output when meta is an empty string.
     */
    #[Test]
    public function output_schema_produces_no_output_when_meta_is_empty_string(): void
    {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = '';

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    // ─── Requirement 1.2: no output when meta is empty JSON array ────────────

    /**
     * Validates: Requirement 1.2
     * output_schema() produces no output when meta is '[]' (empty JSON array).
     */
    #[Test]
    public function output_schema_produces_no_output_when_meta_is_empty_json_array(): void
    {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = '[]';

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    // ─── Requirement 1.2: no output when meta key is absent ──────────────────

    /**
     * Validates: Requirement 1.2
     * output_schema() produces no output when meta key is absent (returns default empty string).
     */
    #[Test]
    public function output_schema_produces_no_output_when_meta_key_is_absent(): void
    {
        global $afg_test_post_meta_values;

        // Do not set any meta value — stub returns '' by default
        $afg_test_post_meta_values = [];

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    // ─── Requirement 1.1: arrays with more than 25 items truncated to 25 ─────

    /**
     * Validates: Requirement 1.1
     * Arrays with more than 25 items are truncated to 25 Question objects.
     */
    #[Test]
    public function output_schema_truncates_to_25_items_when_more_than_25_provided(): void
    {
        global $afg_test_post_meta_values;

        // Create 30 valid FAQ items
        $faqs = [];
        for ($i = 1; $i <= 30; $i++) {
            $faqs[] = ['question' => "Question $i", 'answer' => "Answer $i"];
        }

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode($faqs);

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // Extract JSON from script tag
        $this->assertStringContainsString('<script type="application/ld+json">', $output);
        $json_content = strip_tags($output);
        $schema = json_decode($json_content, true);

        $this->assertNotNull($schema);
        $this->assertCount(25, $schema['mainEntity']);
    }

    // ─── Requirement 1.1: valid FAQ array produces correct JSON-LD ───────────

    /**
     * Validates: Requirements 1.1, 5.1, 5.2
     * A valid FAQ array produces correct script tag with FAQPage JSON-LD.
     */
    #[Test]
    public function output_schema_produces_correct_faqpage_jsonld_for_valid_input(): void
    {
        global $afg_test_post_meta_values;

        $faqs = [
            ['question' => 'What is WordPress?', 'answer' => 'WordPress is a CMS.'],
            ['question' => 'Is it free?', 'answer' => 'Yes, it is open-source.'],
        ];

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode($faqs);

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // Verify script tag wrapper
        $this->assertStringContainsString('<script type="application/ld+json">', $output);
        $this->assertStringContainsString('</script>', $output);

        // Extract and decode JSON
        $json_content = preg_replace(
            '/^<script type="application\/ld\+json">(.+)<\/script>\n?$/s',
            '$1',
            $output
        );
        $schema = json_decode($json_content, true);

        $this->assertNotNull($schema, 'JSON-LD output should be valid JSON');

        // Verify root structure
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('FAQPage', $schema['@type']);
        $this->assertArrayHasKey('mainEntity', $schema);
        $this->assertCount(2, $schema['mainEntity']);

        // Verify first Question object
        $q1 = $schema['mainEntity'][0];
        $this->assertSame('Question', $q1['@type']);
        $this->assertSame('What is WordPress?', $q1['name']);
        $this->assertSame('Answer', $q1['acceptedAnswer']['@type']);
        $this->assertSame('WordPress is a CMS.', $q1['acceptedAnswer']['text']);

        // Verify second Question object
        $q2 = $schema['mainEntity'][1];
        $this->assertSame('Question', $q2['@type']);
        $this->assertSame('Is it free?', $q2['name']);
        $this->assertSame('Answer', $q2['acceptedAnswer']['@type']);
        $this->assertSame('Yes, it is open-source.', $q2['acceptedAnswer']['text']);
    }

    // ─── Requirements 5.1, 5.2, 5.4: Loader integration end-to-end ──────────

    /**
     * Validates: Requirements 5.1, 5.2, 5.4
     * After Loader::init(), a wp_head action is registered at priority 20
     * with a callable referencing JSON_LD_Generator::output_schema.
     * This verifies the class map entry resolves correctly during autoload.
     */
    #[Test]
    public function loader_init_registers_json_ld_generator_on_wp_head_at_priority_20(): void
    {
        global $afg_test_actions, $afg_test_is_admin;

        $afg_test_actions = [];
        $afg_test_is_admin = false;

        $loader = new Loader();
        $loader->init();

        // Find the wp_head action registered at priority 20
        $wp_head_entries = array_filter($afg_test_actions, function ($entry) {
            return $entry['hook'] === 'wp_head' && $entry['priority'] === 20;
        });

        $this->assertNotEmpty($wp_head_entries, 'Loader::init() should register a wp_head action at priority 20');

        // Get the first matching entry
        $wp_head_entry = array_values($wp_head_entries)[0];
        $callback = $wp_head_entry['callback'];

        // Callback must be an array (not a Closure) for remove_action() compatibility
        $this->assertIsArray($callback, 'wp_head callback must be an array callable, not a closure');

        // Callback[0] must be an instance of JSON_LD_Generator
        $this->assertInstanceOf(
            JSON_LD_Generator::class,
            $callback[0],
            'wp_head callback must reference a JSON_LD_Generator instance'
        );

        // Callback[1] must be the 'output_schema' method
        $this->assertSame(
            'output_schema',
            $callback[1],
            'wp_head callback must reference the output_schema method'
        );

        // Verify the method is public (requirement 5.4)
        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        $this->assertTrue($reflection->isPublic(), 'output_schema must be a public method');
    }
}
