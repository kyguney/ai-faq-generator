<?php
/**
 * Property-based test for the JSON_LD_Generator service.
 *
 * Feature: faqpage-jsonld-generator, Property 6: Unicode Preservation
 * Validates: Requirements 4.1
 *
 * For any FAQ content containing Unicode characters (non-ASCII), those characters
 * SHALL appear unescaped (not as \uXXXX sequences) in the JSON-LD output.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\JSON_LD_Generator;

class JsonLdUnicodePropertyTest extends TestCase
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
     * **Validates: Requirements 4.1**
     *
     * Property 6: Unicode Preservation.
     * For any FAQ content containing Unicode characters (non-ASCII), those characters
     * SHALL appear unescaped (not as \uXXXX sequences) in the JSON-LD output.
     */
    #[Test]
    #[DataProvider('unicodePreservationProvider')]
    public function output_preserves_unicode_characters_unescaped(
        array $faqItems,
        array $expectedUnicodeChars
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
        $this->assertNotEmpty($output, 'Expected script tag output for valid FAQ items with Unicode content.');

        // Extract the JSON content from between the script tags.
        $pattern = '/<script type="application\/ld\+json">(.*?)<\/script>/s';
        $matched = preg_match($pattern, $output, $matches);
        $this->assertSame(1, $matched, 'Expected output to contain a script tag.');

        $jsonContent = $matches[1];

        // Assert no \uXXXX escape sequences exist in the JSON content.
        $this->assertDoesNotMatchRegularExpression(
            '/\\\\u[0-9a-fA-F]{4}/',
            $jsonContent,
            'JSON-LD output must not contain \\uXXXX escape sequences for Unicode characters.'
        );

        // Assert each expected Unicode character appears literally in the output.
        foreach ($expectedUnicodeChars as $char) {
            $this->assertStringContainsString(
                $char,
                $jsonContent,
                sprintf(
                    'Expected Unicode character "%s" to appear literally (unescaped) in the JSON-LD output.',
                    $char
                )
            );
        }
    }

    /**
     * Data provider generating 110+ FAQ item sets with Unicode characters from various ranges.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>, array<int, string>}>
     */
    public static function unicodePreservationProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(77742);

        // Unicode character pools by category.
        $cjk = ['你好', '世界', '日本語', '中文', '韓國', '漢字', '東京', '北京', '上海', '大阪'];
        $cyrillic = ['Привет', 'Мир', 'Россия', 'Москва', 'Книга', 'Дом', 'Школа', 'Город', 'Река', 'Лес'];
        $arabic = ['مرحبا', 'عالم', 'كتاب', 'مدرسة', 'بيت', 'شمس', 'قمر', 'ماء', 'نور', 'سلام'];
        $emoji = ['🎉', '👍', '🚀', '❤️', '🌍', '🎈', '🔥', '✨', '💡', '🎯', '🌟', '🎵'];
        $accented = ['café', 'naïve', 'über', 'résumé', 'piñata', 'crème', 'jalapeño', 'São Paulo', 'Zürich', 'Ångström'];
        $greek = ['Αθήνα', 'Σπάρτη', 'φιλοσοφία', 'δημοκρατία', 'μαθηματικά'];
        $thai = ['สวัสดี', 'ขอบคุณ', 'ประเทศไทย'];
        $hebrew = ['שלום', 'עולם', 'ספר'];

        $allPools = [$cjk, $cyrillic, $arabic, $emoji, $accented, $greek, $thai, $hebrew];

        // Generate 110 randomized test cases.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 5);
            $items = [];
            $expectedChars = [];

            for ($j = 0; $j < $itemCount; $j++) {
                // Pick 1-3 random pools to mix.
                $numPools = mt_rand(1, 3);
                $selectedPools = [];
                for ($p = 0; $p < $numPools; $p++) {
                    $selectedPools[] = $allPools[mt_rand(0, count($allPools) - 1)];
                }

                $questionUnicode = self::pickRandomFromPools($selectedPools);
                $answerUnicode = self::pickRandomFromPools($selectedPools);

                $question = self::generateTextWithUnicode($questionUnicode);
                $answer = self::generateTextWithUnicode($answerUnicode);

                $items[] = [
                    'question' => $question,
                    'answer'   => $answer,
                ];

                // Collect expected Unicode characters for assertion.
                foreach ($questionUnicode as $char) {
                    $expectedChars[] = $char;
                }
                foreach ($answerUnicode as $char) {
                    $expectedChars[] = $char;
                }
            }

            $expectedChars = array_unique($expectedChars);

            $cases["random_unicode_{$i}"] = [$items, $expectedChars];
        }

        // Edge case: CJK only.
        $cases['cjk_only'] = [
            [['question' => '你好世界是什么意思？', 'answer' => '你好世界是中文问候语。']],
            ['你好', '世界', '中文'],
        ];

        // Edge case: Cyrillic only.
        $cases['cyrillic_only'] = [
            [['question' => 'Что такое Россия?', 'answer' => 'Россия — большая страна в Мире.']],
            ['Россия', 'Мире'],
        ];

        // Edge case: Arabic only.
        $cases['arabic_only'] = [
            [['question' => 'ما هو عالم البرمجة؟', 'answer' => 'مرحبا بك في عالم البرمجة.']],
            ['عالم', 'مرحبا'],
        ];

        // Edge case: Emoji only.
        $cases['emoji_only'] = [
            [['question' => 'What does 🎉 mean?', 'answer' => 'It means celebration! 👍🚀❤️']],
            ['🎉', '👍', '🚀', '❤️'],
        ];

        // Edge case: Accented characters.
        $cases['accented_only'] = [
            [['question' => 'Where is the café?', 'answer' => 'Near the crème brûlée shop in São Paulo.']],
            ['café', 'crème', 'São'],
        ];

        // Edge case: Mix of all Unicode ranges in one item.
        $cases['mixed_all_ranges'] = [
            [[
                'question' => '你好 Привет مرحبا 🎉 café Αθήνα สวัสดี שלום — what?',
                'answer'   => '世界 Мир عالم 👍 über δημοκρατία ขอบคุณ עולם — answer!',
            ]],
            ['你好', 'Привет', 'مرحبا', '🎉', 'café', 'Αθήνα', 'สวัสดี', 'שלום', '世界', 'Мир', 'عالم', '👍', 'über', 'δημοκρατία', 'ขอบคุณ', 'עולם'],
        ];

        // Edge case: Unicode mixed with ASCII in same strings.
        $cases['unicode_mixed_with_ascii'] = [
            [[
                'question' => 'How do you say hello in Japanese (日本語)?',
                'answer'   => 'You say こんにちは (konnichiwa) which means hello.',
            ]],
            ['日本語', 'こんにちは'],
        ];

        return $cases;
    }

    /**
     * Pick random Unicode strings from multiple pools.
     *
     * @param array<int, array<int, string>> $pools
     * @return array<int, string>
     */
    private static function pickRandomFromPools(array $pools): array
    {
        $picked = [];
        $numPicks = mt_rand(1, 3);

        for ($i = 0; $i < $numPicks; $i++) {
            $pool = $pools[mt_rand(0, count($pools) - 1)];
            $picked[] = $pool[mt_rand(0, count($pool) - 1)];
        }

        return $picked;
    }

    /**
     * Generate text containing Unicode characters mixed with ASCII.
     *
     * @param array<int, string> $unicodeChars Unicode strings to embed.
     * @return string Text with Unicode characters.
     */
    private static function generateTextWithUnicode(array $unicodeChars): string
    {
        $parts = [];

        // Start with some ASCII text.
        $parts[] = self::generateRandomAsciiText(mt_rand(3, 15));

        // Interleave Unicode characters with ASCII.
        foreach ($unicodeChars as $char) {
            $parts[] = $char;
            $parts[] = self::generateRandomAsciiText(mt_rand(2, 10));
        }

        return implode(' ', $parts);
    }

    /**
     * Generate random ASCII text (letters, digits, spaces).
     */
    private static function generateRandomAsciiText(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
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
