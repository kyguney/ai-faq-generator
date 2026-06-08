<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 5: Script Tag Escaping
 * Validates: Requirements 4.2
 *
 * For any FAQ content containing case-insensitive occurrences of `</script`
 * (in any combination of upper/lower case), the final output string SHALL NOT
 * contain a literal `</script` sequence that could terminate the script element.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdScriptEscapingPropertyTest extends TestCase
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
     * **Validates: Requirements 4.2**
     *
     * Property 5: Script Tag Escaping.
     * For any FAQ content containing case-insensitive occurrences of `</script`,
     * the final output JSON content SHALL NOT contain a literal `</script` sequence.
     */
    #[Test]
    #[DataProvider('scriptTagEscapingProvider')]
    public function output_never_contains_literal_closing_script_tag(array $faqItems): void
    {
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

        // Extract the JSON content from between the script tags.
        $pattern = '/<script type="application\/ld\+json">(.*?)<\/script>/s';
        $matched = preg_match($pattern, $output, $matches);
        $this->assertSame(1, $matched, 'Expected output to contain a script tag.');

        $jsonContent = $matches[1];

        // The JSON content portion must NOT contain a literal `</script` (case-insensitive).
        $this->assertDoesNotMatchRegularExpression(
            '/<\/script/i',
            $jsonContent,
            'JSON content within the script tag must not contain a literal "</script" sequence (case-insensitive).'
        );
    }

    /**
     * Data provider generating 110+ FAQ item sets containing `</script` in various cases.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function scriptTagEscapingProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(99887);

        // Various case combinations of </script to inject.
        $scriptVariants = [
            '</script>',
            '</SCRIPT>',
            '</Script>',
            '</sCrIpT>',
            '</ScRiPt>',
            '</scriPT>',
            '</SCRIPT ',
            '</script ',
            '</script',
            '</sCRIPt',
            '</ScripT>',
            '</sCript>',
        ];

        // Generate 110 randomized test cases.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 5);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $variant = $scriptVariants[mt_rand(0, count($scriptVariants) - 1)];
                $position = mt_rand(0, 2); // 0 = beginning, 1 = middle, 2 = end.
                $multipleOccurrences = mt_rand(0, 3) === 0; // 25% chance of multiple.

                $question = self::generateTextWithScriptTag($variant, $position, $multipleOccurrences);
                $answer = self::generateTextWithScriptTag(
                    $scriptVariants[mt_rand(0, count($scriptVariants) - 1)],
                    mt_rand(0, 2),
                    mt_rand(0, 3) === 0
                );

                $items[] = [
                    'question' => $question,
                    'answer'   => $answer,
                ];
            }

            $cases["random_script_escape_{$i}"] = [$items];
        }

        // Edge case: script tag only in question.
        $cases['script_in_question_only'] = [[
            ['question' => 'What about </script> tags?', 'answer' => 'They should be escaped.'],
        ]];

        // Edge case: script tag only in answer.
        $cases['script_in_answer_only'] = [[
            ['question' => 'How to handle tags?', 'answer' => 'Escape </SCRIPT> properly.'],
        ]];

        // Edge case: multiple script tags in one field.
        $cases['multiple_in_one_field'] = [[
            ['question' => '</script>What</SCRIPT>about</Script>this?', 'answer' => 'All escaped.'],
        ]];

        // Edge case: mixed case in same item.
        $cases['mixed_case_same_item'] = [[
            ['question' => 'Test </sCrIpT> here', 'answer' => 'And </ScRiPt> here too.'],
        ]];

        // Edge case: script tag at very beginning.
        $cases['at_beginning'] = [[
            ['question' => '</script>First word', 'answer' => '</SCRIPT>Start of answer.'],
        ]];

        // Edge case: script tag at very end.
        $cases['at_end'] = [[
            ['question' => 'Ends with</script>', 'answer' => 'Also ends</SCRIPT>'],
        ]];

        return $cases;
    }

    /**
     * Generate text with a `</script` variant injected at a specified position.
     */
    private static function generateTextWithScriptTag(
        string $variant,
        int $position,
        bool $multiple
    ): string {
        $prefix = self::generateRandomText(mt_rand(5, 40));
        $suffix = self::generateRandomText(mt_rand(5, 40));
        $middle = self::generateRandomText(mt_rand(5, 20));

        $injection = $multiple
            ? $variant . $middle . $variant
            : $variant;

        return match ($position) {
            0 => $injection . ' ' . $suffix,
            1 => $prefix . ' ' . $injection . ' ' . $suffix,
            2 => $prefix . ' ' . $injection,
            default => $prefix . ' ' . $injection . ' ' . $suffix,
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
