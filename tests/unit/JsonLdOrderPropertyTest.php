<?php
/**
 * Property-based test for JSON-LD Generator order preservation.
 *
 * Feature: faqpage-jsonld-generator, Property 3: Order Preservation
 * Validates: Requirements 3.2
 *
 * For any FAQ meta array, the Question objects in the mainEntity array SHALL
 * appear in the same relative order as the corresponding valid items in the
 * input array.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdOrderPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $this->generator = new JSON_LD_Generator();

        // Set up environment for singular post context.
        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values;

        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 3.2**
     *
     * Property 3: Order Preservation.
     * For any FAQ meta array with distinct question values, the Question objects
     * in the mainEntity array appear in the same relative order as the input items.
     */
    #[Test]
    #[DataProvider('orderedFaqArrayProvider')]
    public function question_objects_preserve_input_order(array $inputItems): void
    {
        global $afg_test_post_meta_values;

        $json = json_encode($inputItems, JSON_UNESCAPED_UNICODE);
        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = $json;

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // Extract JSON-LD content from the script tag.
        $this->assertNotEmpty($output, 'Expected script tag output for valid FAQ items.');

        $matched = preg_match(
            '/<script type="application\/ld\+json">(.+?)<\/script>/s',
            $output,
            $matches
        );
        $this->assertSame(1, $matched, 'Output must contain a JSON-LD script tag.');

        $schema = json_decode($matches[1], true);
        $this->assertNotNull($schema, 'JSON-LD content must be valid JSON.');
        $this->assertArrayHasKey('mainEntity', $schema);

        $mainEntity = $schema['mainEntity'];

        // Extract the name values from Question objects.
        $outputNames = array_map(
            fn(array $question) => $question['name'],
            $mainEntity
        );

        // Expected names: plain text questions go through prepare_question_text
        // (html_entity_decode + strip_tags). Since we generate plain text without
        // HTML entities or tags, names should match input questions directly.
        $expectedNames = array_map(
            fn(array $item) => $item['question'],
            $inputItems
        );

        $this->assertSame(
            $expectedNames,
            $outputNames,
            'Question objects in mainEntity must appear in the same order as input items.'
        );
    }

    /**
     * Data provider generating 110+ random FAQ arrays with distinct question values.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function orderedFaqArrayProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(99887);

        // Generate 110 random FAQ arrays with distinct questions.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(2, 25);
            $items = [];
            $usedQuestions = [];

            for ($j = 0; $j < $itemCount; $j++) {
                // Generate a unique question string.
                do {
                    $question = self::generateDistinctQuestion($i, $j);
                } while (in_array($question, $usedQuestions, true));

                $usedQuestions[] = $question;

                $items[] = [
                    'question' => $question,
                    'answer'   => self::generateAnswer(),
                ];
            }

            $cases["random_ordered_array_{$i}"] = [$items];
        }

        // Edge case: exactly 2 items (minimum for order test).
        $cases['two_items'] = [[
            ['question' => 'First question here?', 'answer' => 'First answer.'],
            ['question' => 'Second question here?', 'answer' => 'Second answer.'],
        ]];

        // Edge case: maximum 25 items.
        $maxItems = [];
        for ($i = 0; $i < 25; $i++) {
            $maxItems[] = [
                'question' => "Question number {$i} in sequence?",
                'answer'   => "Answer number {$i} in sequence.",
            ];
        }
        $cases['max_25_items'] = [$maxItems];

        // Edge case: items with similar prefixes (order must be exact).
        $cases['similar_prefix_items'] = [[
            ['question' => 'How do I install?', 'answer' => 'Download and install.'],
            ['question' => 'How do I configure?', 'answer' => 'Open settings.'],
            ['question' => 'How do I uninstall?', 'answer' => 'Remove the plugin.'],
            ['question' => 'How do I update?', 'answer' => 'Click update button.'],
        ]];

        // Edge case: reversed alphabetical order (should not be sorted).
        $cases['reverse_alphabetical'] = [[
            ['question' => 'Zebra question?', 'answer' => 'Zebra answer.'],
            ['question' => 'Mango question?', 'answer' => 'Mango answer.'],
            ['question' => 'Apple question?', 'answer' => 'Apple answer.'],
        ]];

        return $cases;
    }

    /**
     * Generate a distinct question string using index-based seeding.
     */
    private static function generateDistinctQuestion(int $caseIndex, int $itemIndex): string
    {
        $prefixes = ['What is', 'How does', 'Why do', 'When should', 'Where can', 'Who uses', 'Can I', 'Is it possible to'];
        $subjects = ['WordPress', 'PHP', 'JSON-LD', 'SEO', 'schema.org', 'a plugin', 'the API', 'metadata', 'caching', 'rendering'];
        $suffixes = ['work', 'function', 'apply', 'help', 'change', 'improve', 'affect', 'process', 'handle', 'output'];

        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $subject = $subjects[mt_rand(0, count($subjects) - 1)];
        $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];

        // Include indices to ensure uniqueness.
        return "{$prefix} {$subject} {$suffix} (case {$caseIndex} item {$itemIndex})?";
    }

    /**
     * Generate a random non-empty answer string.
     */
    private static function generateAnswer(): string
    {
        $length = mt_rand(10, 200);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-';
        $charsLength = strlen($chars);

        $result = $chars[mt_rand(0, 25)]; // Start with a letter.

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a non-whitespace character.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }
}
