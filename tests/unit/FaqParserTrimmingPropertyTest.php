<?php
/**
 * Property-based test for the Faq_Parser service.
 *
 * Feature: faq-response-parser, Property 4: Whitespace Trimming Preserves Internal Content
 * Validates: Requirements 4.1, 4.2, 4.3
 *
 * For any valid FAQ item whose question or answer value contains leading/trailing whitespace,
 * parse() SHALL return the item with all leading and trailing whitespace removed, while
 * preserving all internal whitespace (whitespace between non-whitespace characters) unchanged.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserTrimmingPropertyTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    /**
     * **Validates: Requirements 4.1, 4.2, 4.3**
     *
     * Property 4: Whitespace Trimming Preserves Internal Content.
     * For any valid FAQ item with leading/trailing whitespace in question or answer,
     * parse() removes leading/trailing whitespace while preserving internal whitespace unchanged.
     */
    #[Test]
    #[DataProvider('whitespaceTrimProvider')]
    public function parse_trims_leading_trailing_whitespace_and_preserves_internal(
        string $coreQuestion,
        string $coreAnswer,
        string $paddedQuestion,
        string $paddedAnswer
    ): void {
        $json = json_encode([
            ['question' => $paddedQuestion, 'answer' => $paddedAnswer],
        ]);

        $result = $this->parser->parse($json);

        $this->assertCount(1, $result, 'Valid padded item should produce exactly one result.');

        $this->assertSame(
            $coreQuestion,
            $result[0]['question'],
            sprintf(
                'Leading/trailing whitespace must be removed from question. '
                . 'Expected: "%s", Got: "%s"',
                addcslashes($coreQuestion, "\t\n\r"),
                addcslashes($result[0]['question'], "\t\n\r")
            )
        );

        $this->assertSame(
            $coreAnswer,
            $result[0]['answer'],
            sprintf(
                'Leading/trailing whitespace must be removed from answer. '
                . 'Expected: "%s", Got: "%s"',
                addcslashes($coreAnswer, "\t\n\r"),
                addcslashes($result[0]['answer'], "\t\n\r")
            )
        );
    }

    /**
     * Data provider generating 110+ random FAQ items with random leading/trailing whitespace.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function whitespaceTrimProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(54321);

        // Generate 110 random combinations.
        for ($i = 0; $i < 110; $i++) {
            $coreQuestion = self::generateCoreContent(mt_rand(3, 80));
            $coreAnswer = self::generateCoreContent(mt_rand(3, 150));

            $leadingQ = self::generateWhitespacePadding(mt_rand(1, 10));
            $trailingQ = self::generateWhitespacePadding(mt_rand(1, 10));
            $leadingA = self::generateWhitespacePadding(mt_rand(1, 10));
            $trailingA = self::generateWhitespacePadding(mt_rand(1, 10));

            $paddedQuestion = $leadingQ . $coreQuestion . $trailingQ;
            $paddedAnswer = $leadingA . $coreAnswer . $trailingA;

            $cases["random_whitespace_trim_{$i}"] = [
                $coreQuestion,
                $coreAnswer,
                $paddedQuestion,
                $paddedAnswer,
            ];
        }

        // Edge cases: only spaces as padding.
        $cases['spaces_only_padding'] = [
            'What is PHP?',
            'A server-side language.',
            '   What is PHP?   ',
            '    A server-side language.    ',
        ];

        // Edge cases: tabs as padding.
        $cases['tabs_only_padding'] = [
            'What is PHP?',
            'A server-side language.',
            "\t\tWhat is PHP?\t\t",
            "\t\tA server-side language.\t\t",
        ];

        // Edge cases: newlines as padding.
        $cases['newlines_only_padding'] = [
            'What is PHP?',
            'A server-side language.',
            "\n\nWhat is PHP?\n\n",
            "\n\nA server-side language.\n\n",
        ];

        // Edge cases: carriage returns as padding.
        $cases['carriage_returns_padding'] = [
            'What is PHP?',
            'A server-side language.',
            "\r\rWhat is PHP?\r\r",
            "\r\rA server-side language.\r\r",
        ];

        // Edge cases: mixed whitespace padding.
        $cases['mixed_whitespace_padding'] = [
            'What is PHP?',
            'A server-side language.',
            " \t\n\r What is PHP? \r\n\t ",
            "\r\n\t A server-side language.\t\n\r ",
        ];

        // Edge cases: internal whitespace with spaces between words.
        $cases['internal_spaces_preserved'] = [
            'What   is   PHP?',
            'A   server-side   language.',
            "  What   is   PHP?  ",
            "  A   server-side   language.  ",
        ];

        // Edge cases: internal tabs between words.
        $cases['internal_tabs_preserved'] = [
            "What\tis\tPHP?",
            "A\tserver-side\tlanguage.",
            "  What\tis\tPHP?  ",
            "\tA\tserver-side\tlanguage.\t",
        ];

        // Edge cases: internal newlines between words.
        $cases['internal_newlines_preserved'] = [
            "What\nis\nPHP?",
            "A\nserver-side\nlanguage.",
            "  What\nis\nPHP?\n",
            "\nA\nserver-side\nlanguage.\n",
        ];

        // Edge cases: internal mixed whitespace preserved.
        $cases['internal_mixed_whitespace_preserved'] = [
            "What \t is \n PHP?",
            "A \r\n server-side \t language.",
            "\t What \t is \n PHP? \n",
            "\r\n A \r\n server-side \t language. \t",
        ];

        // Edge cases: single character core content.
        $cases['single_char_question_answer'] = [
            'Q',
            'A',
            "  \t Q \n ",
            " \r A \t\n ",
        ];

        return $cases;
    }

    /**
     * Generate a non-empty "core" string that may contain internal whitespace.
     *
     * The core content starts and ends with a non-whitespace character,
     * but may contain spaces, tabs, or other whitespace between words.
     */
    private static function generateCoreContent(int $length): string
    {
        // Characters for word content (non-whitespace).
        $wordChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,!?-_\'\"()[]{}@#$%&*';
        // Internal whitespace characters (can appear between words).
        $internalWhitespace = [' ', "\t", '  ', ' '];

        $wordCharsLength = strlen($wordChars);
        $result = '';

        // Ensure first character is non-whitespace.
        $result .= $wordChars[mt_rand(0, $wordCharsLength - 1)];

        for ($i = 1; $i < $length - 1; $i++) {
            // ~20% chance of inserting internal whitespace.
            if (mt_rand(1, 5) === 1) {
                $result .= $internalWhitespace[mt_rand(0, count($internalWhitespace) - 1)];
            } else {
                $result .= $wordChars[mt_rand(0, $wordCharsLength - 1)];
            }
        }

        // Ensure last character is non-whitespace.
        if ($length > 1) {
            $result .= $wordChars[mt_rand(0, $wordCharsLength - 1)];
        }

        return $result;
    }

    /**
     * Generate random whitespace padding using spaces, tabs, newlines, and carriage returns.
     */
    private static function generateWhitespacePadding(int $length): string
    {
        $whitespaceChars = [' ', "\t", "\n", "\r"];
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $whitespaceChars[mt_rand(0, count($whitespaceChars) - 1)];
        }

        return $result;
    }
}
