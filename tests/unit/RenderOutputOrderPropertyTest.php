<?php
/**
 * Property-based test for the FAQ Accordion render callback.
 *
 * Feature: faq-accordion-block, Property 5: Render Output Preserves Item Order and Structure
 * Validates: Requirements 4.2, 5.2
 *
 * For any array of valid FAQ items (non-empty question and answer), the render callback
 * should output HTML where each item appears as a collapsible section with the question
 * in the summary and the answer in the content div, in the same order as the input array.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;

class RenderOutputOrderPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 4.2, 5.2**
     *
     * Property 5: Render Output Preserves Item Order and Structure.
     * For any array of valid FAQ items (non-empty question and answer), the render
     * callback outputs HTML where each item appears with the question inside a <summary>
     * element and the answer inside a <div class="faq-accordion-content">, in the same
     * order as the input array.
     */
    #[Test]
    #[DataProvider('validFaqItemsProvider')]
    public function render_preserves_item_order_and_structure(array $items): void
    {
        $output = render_faq_accordion_block(['items' => $items]);

        // Output should not be empty for valid items.
        $this->assertNotEmpty($output, 'Render output must not be empty for valid FAQ items.');

        // Verify the output contains the wrapper div with base class.
        $this->assertMatchesRegularExpression(
            '/<div class="wp-block-wpbits-faq-accordion[^"]*">/',
            $output,
            'Output must contain the accordion wrapper div.'
        );

        // Extract all <summary>...</summary> contents in order.
        preg_match_all('#<summary[^>]*>(.*?)</summary>#s', $output, $summaryMatches);
        $renderedQuestions = $summaryMatches[1];

        // Extract all answer contents from the inner wrapper divs in order.
        preg_match_all('#<div[^>]*class="faq-accordion-content__inner"[^>]*>(.*?)</div>#s', $output, $contentMatches);
        $renderedAnswers = $contentMatches[1];

        // The number of rendered items must match the input count.
        $this->assertCount(
            count($items),
            $renderedQuestions,
            sprintf(
                'Number of rendered <summary> elements (%d) must match input item count (%d).',
                count($renderedQuestions),
                count($items)
            )
        );

        $this->assertCount(
            count($items),
            $renderedAnswers,
            sprintf(
                'Number of rendered content divs (%d) must match input item count (%d).',
                count($renderedAnswers),
                count($items)
            )
        );

        // Verify order: each rendered question/answer corresponds to the input item at the same index.
        foreach ($items as $index => $item) {
            $sanitizedQuestion = wp_kses_post($item['question']);
            $sanitizedAnswer = wp_kses_post($item['answer']);

            // Question is now wrapped in a heading tag (default h3) inside summary.
            $this->assertStringContainsString(
                $sanitizedQuestion,
                $renderedQuestions[$index],
                sprintf(
                    'Question at index %d must contain the sanitized question text.',
                    $index
                )
            );

            $this->assertSame(
                $sanitizedAnswer,
                $renderedAnswers[$index],
                sprintf(
                    'Answer at index %d must match. Expected "%s", got "%s".',
                    $index,
                    $sanitizedAnswer,
                    $renderedAnswers[$index]
                )
            );
        }
    }

    /**
     * Data provider generating 110+ random valid FAQ item arrays.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function validFaqItemsProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(98765);

        // Generate 110 random valid FAQ arrays.
        for ($i = 0; $i < 110; $i++) {
            $itemCount = mt_rand(1, 10);
            $items = [];

            for ($j = 0; $j < $itemCount; $j++) {
                $items[] = self::generateValidFaqItem();
            }

            $cases["random_valid_faq_array_{$i}"] = [$items];
        }

        // Edge case: single item.
        $cases['single_item'] = [[
            ['question' => 'What is WordPress?', 'answer' => 'A content management system.'],
        ]];

        // Edge case: two items to verify order.
        $cases['two_items_order'] = [[
            ['question' => 'First question?', 'answer' => 'First answer.'],
            ['question' => 'Second question?', 'answer' => 'Second answer.'],
        ]];

        // Edge case: maximum items (50).
        $maxItems = [];
        for ($i = 0; $i < 50; $i++) {
            $maxItems[] = ['question' => "Question number {$i}?", 'answer' => "Answer number {$i}."];
        }
        $cases['maximum_50_items'] = [$maxItems];

        // Edge case: items with unicode content.
        $cases['unicode_content'] = [[
            ['question' => 'Ünïcödé soru nedir?', 'answer' => 'Cevap: Ünïcödé desteklenir.'],
            ['question' => '日本語の質問は何ですか？', 'answer' => '日本語の回答です。'],
            ['question' => 'Вопрос на русском?', 'answer' => 'Ответ на русском.'],
        ]];

        // Edge case: items with allowed HTML in content.
        $cases['allowed_html_content'] = [[
            ['question' => 'What about <strong>bold</strong>?', 'answer' => 'It is <em>preserved</em>.'],
            ['question' => 'Links work?', 'answer' => 'Yes, <a href="#">links</a> are kept.'],
        ]];

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
     * Generate a random non-empty string (guaranteed non-empty after trim).
     */
    private static function generateNonEmptyString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-_()';
        $charsLength = strlen($chars);

        // Start with a letter to ensure non-empty after trim.
        $result = $chars[mt_rand(0, 25)];

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
