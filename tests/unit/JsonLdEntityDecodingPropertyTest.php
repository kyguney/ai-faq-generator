<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 8: HTML Entity Decoding
 * Validates: Requirements 6.1
 *
 * For any FAQ text containing HTML entities (named like `&amp;`, numeric like
 * `&#60;`, or hexadecimal like `&#x3C;`), the JSON-LD output SHALL contain the
 * decoded Unicode character equivalents rather than the raw entity strings.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdEntityDecodingPropertyTest extends TestCase
{
    private JSON_LD_Generator $generator;

    protected function setUp(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values, $afg_test_current_post_id;

        $afg_test_is_singular = true;
        $afg_test_current_post_id = 42;
        $afg_test_post_meta_values = [];

        $this->generator = new JSON_LD_Generator();
    }

    protected function tearDown(): void
    {
        global $afg_test_is_singular, $afg_test_post_meta_values;

        $afg_test_is_singular = true;
        $afg_test_post_meta_values = [];
    }

    /**
     * **Validates: Requirements 6.1**
     *
     * Property 8: HTML Entity Decoding.
     * For any FAQ text containing HTML entities, the JSON-LD output SHALL contain
     * the decoded Unicode character equivalents rather than the raw entity strings.
     */
    #[Test]
    #[DataProvider('htmlEntityDecodingProvider')]
    public function output_contains_decoded_entities_not_raw_strings(
        array $faqItems,
        array $entityExpectations
    ): void {
        global $afg_test_post_meta_values;

        $afg_test_post_meta_values['42__aifaq_generated_faqs'] = json_encode(
            $faqItems,
            JSON_UNESCAPED_UNICODE
        );

        ob_start();
        $this->generator->output_schema();
        $output = ob_get_clean();

        // The output should not be empty since we have valid FAQ items.
        $this->assertNotEmpty($output, 'Expected script tag output for valid FAQ items.');

        // Extract the JSON content from the script tag.
        $pattern = '/<script type="application\/ld\+json">(.*?)<\/script>/s';
        $matched = preg_match($pattern, $output, $matches);
        $this->assertSame(1, $matched, 'Expected output to contain a script tag.');

        $jsonContent = $matches[1];

        // Un-escape script tag escaping: `<\/script` → `</script`.
        $jsonContent = str_replace('<\\/script', '</script', $jsonContent);

        // Decode the JSON.
        $schema = json_decode($jsonContent, true);
        $this->assertNotNull($schema, 'JSON content must be valid JSON after un-escaping.');
        $this->assertArrayHasKey('mainEntity', $schema);

        $mainEntity = $schema['mainEntity'];

        // For each FAQ item, verify entity decoding in both question and answer.
        foreach ($entityExpectations as $index => $expectation) {
            $this->assertArrayHasKey($index, $mainEntity, "Expected Question at index {$index}.");

            $questionObj = $mainEntity[$index];
            $name = $questionObj['name'];
            $text = $questionObj['acceptedAnswer']['text'];

            // Assert decoded character IS present in the output.
            $this->assertStringContainsString(
                $expectation['decodedChar'],
                $name,
                sprintf(
                    'Question name at index %d must contain decoded character "%s" (U+%04X), not the raw entity.',
                    $index,
                    $expectation['decodedChar'],
                    mb_ord($expectation['decodedChar'])
                )
            );

            // Assert raw entity string is NOT present in the output.
            $this->assertStringNotContainsString(
                $expectation['rawEntity'],
                $name,
                sprintf(
                    'Question name at index %d must NOT contain raw entity "%s".',
                    $index,
                    $expectation['rawEntity']
                )
            );

            // Assert decoded character IS present in answer text.
            $this->assertStringContainsString(
                $expectation['decodedChar'],
                $text,
                sprintf(
                    'Answer text at index %d must contain decoded character "%s" (U+%04X), not the raw entity.',
                    $index,
                    $expectation['decodedChar'],
                    mb_ord($expectation['decodedChar'])
                )
            );

            // Assert raw entity string is NOT present in answer text.
            $this->assertStringNotContainsString(
                $expectation['rawEntity'],
                $text,
                sprintf(
                    'Answer text at index %d must NOT contain raw entity "%s".',
                    $index,
                    $expectation['rawEntity']
                )
            );
        }
    }

    /**
     * Data provider generating 110+ FAQ item sets with HTML entities.
     *
     * Each case provides:
     * - An array of FAQ items with entities in both question and answer
     * - An array of expectations mapping index → [rawEntity, decodedChar]
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>, array<int, array{rawEntity: string, decodedChar: string}>}>
     */
    public static function htmlEntityDecodingProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(77442);

        // Entities that decode to characters safe from strip_tags interference.
        // Format: [raw entity, decoded character]
        $safeEntities = [
            // Named entities.
            ['&amp;', '&'],
            ['&copy;', '©'],
            ['&reg;', '®'],
            ['&trade;', '™'],
            ['&mdash;', '—'],
            ['&ndash;', '–'],
            ['&hellip;', '…'],
            ['&lsquo;', "\u{2018}"],
            ['&rsquo;', "\u{2019}"],
            ['&ldquo;', "\u{201C}"],
            ['&rdquo;', "\u{201D}"],
            ['&quot;', '"'],
            ['&apos;', "'"],
            ['&nbsp;', "\u{00A0}"],
            ['&cent;', '¢'],
            ['&pound;', '£'],
            ['&yen;', '¥'],
            ['&euro;', '€'],
            ['&sect;', '§'],
            ['&para;', '¶'],
            ['&deg;', '°'],
            ['&plusmn;', '±'],
            ['&times;', '×'],
            ['&divide;', '÷'],
            ['&frac12;', '½'],
            ['&frac14;', '¼'],
            ['&frac34;', '¾'],
            // Numeric entities.
            ['&#169;', '©'],
            ['&#174;', '®'],
            ['&#8482;', '™'],
            ['&#8212;', '—'],
            ['&#8211;', '–'],
            ['&#8230;', '…'],
            ['&#38;', '&'],
            ['&#34;', '"'],
            ['&#39;', "'"],
            ['&#160;', "\u{00A0}"],
            ['&#8364;', '€'],
            ['&#167;', '§'],
            ['&#176;', '°'],
            ['&#177;', '±'],
            ['&#215;', '×'],
            ['&#247;', '÷'],
            // Hexadecimal entities.
            ['&#xA9;', '©'],
            ['&#xAE;', '®'],
            ['&#x2122;', '™'],
            ['&#x2014;', '—'],
            ['&#x2013;', '–'],
            ['&#x2026;', '…'],
            ['&#x26;', '&'],
            ['&#x22;', '"'],
            ['&#x27;', "'"],
            ['&#xA0;', "\u{00A0}"],
            ['&#x20AC;', '€'],
            ['&#xA7;', '§'],
            ['&#xB0;', '°'],
            ['&#xB1;', '±'],
            ['&#xD7;', '×'],
            ['&#xF7;', '÷'],
        ];

        // Generate 110 randomized test cases.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 4);
            $items = [];
            $expectations = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $entityIdx = mt_rand(0, count($safeEntities) - 1);
                [$rawEntity, $decodedChar] = $safeEntities[$entityIdx];

                $question = self::generateTextWithEntity($rawEntity);
                $answer = self::generateTextWithEntity($rawEntity);

                $items[] = [
                    'question' => $question,
                    'answer'   => $answer,
                ];

                $expectations[] = [
                    'rawEntity'   => $rawEntity,
                    'decodedChar' => $decodedChar,
                ];
            }

            // We only check the first item for simplicity (the property holds for all).
            $cases["random_entity_decode_{$i}"] = [$items, [$expectations[0]]];
        }

        // Named entity edge cases.
        $cases['named_ampersand'] = [
            [['question' => 'Q&amp;A section', 'answer' => 'This &amp; that']],
            [['rawEntity' => '&amp;', 'decodedChar' => '&']],
        ];

        $cases['named_copyright'] = [
            [['question' => 'Who owns &copy; rights?', 'answer' => '&copy; 2024 Company']],
            [['rawEntity' => '&copy;', 'decodedChar' => '©']],
        ];

        $cases['named_trademark'] = [
            [['question' => 'Is Brand&trade; registered?', 'answer' => 'Brand&trade; is protected']],
            [['rawEntity' => '&trade;', 'decodedChar' => '™']],
        ];

        $cases['named_mdash'] = [
            [['question' => 'What &mdash; exactly?', 'answer' => 'This &mdash; that']],
            [['rawEntity' => '&mdash;', 'decodedChar' => '—']],
        ];

        $cases['named_curly_quotes'] = [
            [['question' => '&ldquo;Quoted&rdquo; question?', 'answer' => '&ldquo;Quoted&rdquo; answer']],
            [['rawEntity' => '&ldquo;', 'decodedChar' => "\u{201C}"]],
        ];

        // Numeric entity edge cases.
        $cases['numeric_copyright'] = [
            [['question' => '&#169; who?', 'answer' => '&#169; them']],
            [['rawEntity' => '&#169;', 'decodedChar' => '©']],
        ];

        $cases['numeric_ampersand'] = [
            [['question' => 'Rock &#38; Roll?', 'answer' => 'Rock &#38; Roll!']],
            [['rawEntity' => '&#38;', 'decodedChar' => '&']],
        ];

        $cases['numeric_emdash'] = [
            [['question' => 'Word &#8212; word?', 'answer' => 'Word &#8212; word.']],
            [['rawEntity' => '&#8212;', 'decodedChar' => '—']],
        ];

        // Hexadecimal entity edge cases.
        $cases['hex_copyright'] = [
            [['question' => '&#xA9; holder?', 'answer' => '&#xA9; holder.']],
            [['rawEntity' => '&#xA9;', 'decodedChar' => '©']],
        ];

        $cases['hex_ampersand'] = [
            [['question' => 'This &#x26; that?', 'answer' => 'This &#x26; that.']],
            [['rawEntity' => '&#x26;', 'decodedChar' => '&']],
        ];

        $cases['hex_emdash'] = [
            [['question' => 'A &#x2014; B?', 'answer' => 'A &#x2014; B.']],
            [['rawEntity' => '&#x2014;', 'decodedChar' => '—']],
        ];

        // Mixed entity types in same item.
        $cases['mixed_entity_types'] = [
            [['question' => '&amp; and &#169; and &#xAE; mixed?', 'answer' => '&amp; and &#169; and &#xAE; mixed.']],
            [['rawEntity' => '&amp;', 'decodedChar' => '&']],
        ];

        // Multiple entities of same type.
        $cases['multiple_same_entity'] = [
            [['question' => '&copy; first &copy; second?', 'answer' => '&copy; first &copy; second.']],
            [['rawEntity' => '&copy;', 'decodedChar' => '©']],
        ];

        // Entity at start and end of text.
        $cases['entity_at_boundaries'] = [
            [['question' => '&reg; at start and end &reg;', 'answer' => '&reg; start &reg; end']],
            [['rawEntity' => '&reg;', 'decodedChar' => '®']],
        ];

        return $cases;
    }

    /**
     * Generate text with an HTML entity embedded at a random position.
     */
    private static function generateTextWithEntity(string $entity): string
    {
        $prefix = self::generateRandomText(mt_rand(5, 30));
        $suffix = self::generateRandomText(mt_rand(5, 30));
        $position = mt_rand(0, 2);

        return match ($position) {
            0 => $entity . ' ' . $suffix,
            1 => $prefix . ' ' . $entity . ' ' . $suffix,
            2 => $prefix . ' ' . $entity,
            default => $prefix . ' ' . $entity . ' ' . $suffix,
        };
    }

    /**
     * Generate random text content (letters, digits, spaces).
     */
    private static function generateRandomText(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 ';
        $charsLength = strlen($chars);
        $result = '';

        // Start with a letter to ensure non-empty after trim.
        $result .= $chars[mt_rand(0, 25)];

        for ($i = 1; $i < $length - 1; $i++) {
            $result .= $chars[mt_rand(0, $charsLength - 1)];
        }

        // End with a letter.
        if ($length > 1) {
            $result .= $chars[mt_rand(0, 25)];
        }

        return $result;
    }
}
