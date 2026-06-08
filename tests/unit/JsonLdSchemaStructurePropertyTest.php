<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 1: Schema Structure Invariant
 * Validates: Requirements 1.1, 2.1, 2.2, 2.3, 2.4, 2.5
 *
 * For any valid FAQ meta array containing at least one valid FAQ item (with
 * non-empty question and answer), the generated JSON-LD output SHALL contain
 * a root object with @context equal to "https://schema.org", @type equal to
 * "FAQPage", and a mainEntity array where each element has @type equal to
 * "Question", a name string, and an acceptedAnswer object with @type equal
 * to "Answer" and a text string.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdSchemaStructurePropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_current_post_id, $afg_test_post_meta_values;

        $this->generator = new JSON_LD_Generator();

        // Set up globals for singular post context.
        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_current_post_id, $afg_test_post_meta_values;

        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 1.1, 2.1, 2.2, 2.3, 2.4, 2.5**
     *
     * Property 1: Schema Structure Invariant.
     * For any valid FAQ meta array containing at least one valid FAQ item,
     * the generated JSON-LD SHALL have a root object with @context = "https://schema.org",
     * @type = "FAQPage", and mainEntity array where each element has @type = "Question",
     * a name string, and an acceptedAnswer object with @type = "Answer" and text string.
     */
    #[Test]
    #[DataProvider('validFaqArrayProvider')]
    public function output_schema_produces_valid_faqpage_structure(
        array $inputItems,
        string $encodedMeta
    ): void {
        global $afg_test_post_meta_values;

        // Set the FAQ meta for post ID 42.
        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = $encodedMeta;

        // Capture output from output_schema().
        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // Output must not be empty for valid FAQ arrays.
        $this->assertNotEmpty($output, 'output_schema() must produce output for valid FAQ arrays.');

        // Extract JSON from script tag.
        $pattern = '#<script type="application/ld\+json">(.*?)</script>#s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $output,
            'Output must contain a <script type="application/ld+json"> tag.'
        );

        preg_match($pattern, $output, $matches);
        $jsonContent = $matches[1];

        // Un-escape the script tag escaping for JSON parsing.
        $jsonContent = str_replace('<\\/script', '</script', $jsonContent);

        $schema = json_decode($jsonContent, true);
        $this->assertNotNull($schema, 'JSON content must be valid JSON.');

        // Assert root @context.
        $this->assertArrayHasKey('@context', $schema);
        $this->assertSame(
            'https://schema.org',
            $schema['@context'],
            '@context must be "https://schema.org".'
        );

        // Assert root @type.
        $this->assertArrayHasKey('@type', $schema);
        $this->assertSame(
            'FAQPage',
            $schema['@type'],
            '@type must be "FAQPage".'
        );

        // Assert mainEntity is an array.
        $this->assertArrayHasKey('mainEntity', $schema);
        $this->assertIsArray($schema['mainEntity'], 'mainEntity must be an array.');
        $this->assertNotEmpty($schema['mainEntity'], 'mainEntity must not be empty for valid items.');

        // Assert each mainEntity element has the correct structure.
        foreach ($schema['mainEntity'] as $index => $question) {
            // @type = "Question"
            $this->assertArrayHasKey(
                '@type',
                $question,
                sprintf('mainEntity[%d] must have @type key.', $index)
            );
            $this->assertSame(
                'Question',
                $question['@type'],
                sprintf('mainEntity[%d] @type must be "Question".', $index)
            );

            // name is a string
            $this->assertArrayHasKey(
                'name',
                $question,
                sprintf('mainEntity[%d] must have name key.', $index)
            );
            $this->assertIsString(
                $question['name'],
                sprintf('mainEntity[%d] name must be a string.', $index)
            );

            // acceptedAnswer is an object/array
            $this->assertArrayHasKey(
                'acceptedAnswer',
                $question,
                sprintf('mainEntity[%d] must have acceptedAnswer key.', $index)
            );
            $this->assertIsArray(
                $question['acceptedAnswer'],
                sprintf('mainEntity[%d] acceptedAnswer must be an object.', $index)
            );

            // acceptedAnswer @type = "Answer"
            $this->assertArrayHasKey(
                '@type',
                $question['acceptedAnswer'],
                sprintf('mainEntity[%d] acceptedAnswer must have @type key.', $index)
            );
            $this->assertSame(
                'Answer',
                $question['acceptedAnswer']['@type'],
                sprintf('mainEntity[%d] acceptedAnswer @type must be "Answer".', $index)
            );

            // acceptedAnswer text is a string
            $this->assertArrayHasKey(
                'text',
                $question['acceptedAnswer'],
                sprintf('mainEntity[%d] acceptedAnswer must have text key.', $index)
            );
            $this->assertIsString(
                $question['acceptedAnswer']['text'],
                sprintf('mainEntity[%d] acceptedAnswer text must be a string.', $index)
            );
        }
    }

    /**
     * Data provider generating 110+ random valid FAQ arrays.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>, string}>
     */
    public static function validFaqArrayProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(12345);

        // Generate 110 random valid FAQ arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 25);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $items[] = self::generateValidFaqItem();
            }

            $encodedMeta = json_encode($items, JSON_UNESCAPED_UNICODE);

            $cases["random_valid_faq_array_{$i}"] = [$items, $encodedMeta];
        }

        // Edge case: single item.
        $singleItem = [['question' => 'What is WordPress?', 'answer' => 'A content management system.']];
        $cases['single_item'] = [$singleItem, json_encode($singleItem)];

        // Edge case: maximum 25 items.
        $maxItems = [];
        for ($i = 0; $i < 25; $i++) {
            $maxItems[] = ['question' => "Question {$i}?", 'answer' => "Answer {$i}."];
        }
        $cases['max_25_items'] = [$maxItems, json_encode($maxItems)];

        // Edge case: Unicode content.
        $unicodeItems = [
            ['question' => '日本語の質問は何ですか？', 'answer' => '日本語の回答です。'],
            ['question' => 'Что такое PHP?', 'answer' => 'PHP — это язык программирования.'],
        ];
        $cases['unicode_content'] = [$unicodeItems, json_encode($unicodeItems, JSON_UNESCAPED_UNICODE)];

        // Edge case: items with special characters.
        $specialItems = [
            ['question' => 'What about "quotes" & ampersands?', 'answer' => 'They work fine in JSON-LD.'],
        ];
        $cases['special_characters'] = [$specialItems, json_encode($specialItems)];

        // Edge case: items with long content.
        $longItems = [
            ['question' => str_repeat('Long question ', 20) . '?', 'answer' => str_repeat('Long answer text. ', 50)],
        ];
        $cases['long_content'] = [$longItems, json_encode($longItems)];

        return $cases;
    }

    /**
     * Generate a random valid FAQ item with non-empty question and answer.
     *
     * @return array{question: string, answer: string}
     */
    private static function generateValidFaqItem(): array
    {
        return [
            'question' => self::generateNonEmptyString(mt_rand(5, 100)),
            'answer' => self::generateNonEmptyString(mt_rand(10, 300)),
        ];
    }

    /**
     * Generate a random non-empty string that is non-empty after trim.
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_()[]{}';
        $charsLength = strlen($chars);

        // Ensure at least one non-whitespace character at the start.
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
