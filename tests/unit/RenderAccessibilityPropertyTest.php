<?php
/**
 * Property-based test for the FAQ Accordion block render callback.
 *
 * Feature: faq-accordion-block, Property 8: Accessibility Attributes Are Correct on Initial Render
 * Validates: Requirements 5.1, 6.2, 6.5
 *
 * For any non-empty array of valid FAQ items, the render callback should output HTML
 * where every FAQ item header has `aria-expanded="false"` and an `aria-controls` attribute
 * referencing a unique ID that matches the `id` attribute of the corresponding content panel.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;

// Load the render callback.
require_once dirname(__DIR__, 2) . '/blocks/faq-accordion/render.php';

class RenderAccessibilityPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 5.1, 6.2, 6.5**
     *
     * Property 8: Accessibility Attributes Are Correct on Initial Render.
     * For any non-empty array of valid FAQ items, every summary element has
     * aria-expanded="false" and aria-controls referencing a unique panel ID
     * that matches the id attribute of the corresponding content div.
     */
    #[Test]
    #[DataProvider('validFaqArrayProvider')]
    public function render_outputs_correct_accessibility_attributes(array $items): void
    {
        $html = render_faq_accordion_block(['items' => $items]);

        // Count valid items (non-empty question and answer).
        $validItems = array_filter($items, function (array $item): bool {
            return !empty($item['question'] ?? '') && !empty($item['answer'] ?? '');
        });
        $validCount = count($validItems);

        // Parse all summary elements.
        preg_match_all('/<summary([^>]*)>/', $html, $summaryMatches);
        $summaryAttributes = $summaryMatches[1];

        $this->assertCount(
            $validCount,
            $summaryAttributes,
            sprintf('Expected %d summary elements, found %d.', $validCount, count($summaryAttributes))
        );

        // Parse all content panel divs with id attributes.
        preg_match_all('/<div\s+id="([^"]+)"\s+class="faq-accordion-content"/', $html, $panelMatches);
        $panelIds = $panelMatches[1];

        $this->assertCount(
            $validCount,
            $panelIds,
            sprintf('Expected %d content panels with id, found %d.', $validCount, count($panelIds))
        );

        // Verify all panel IDs are unique.
        $uniquePanelIds = array_unique($panelIds);
        $this->assertCount(
            count($panelIds),
            $uniquePanelIds,
            'All panel IDs must be unique across the rendered output.'
        );

        $ariaControlsValues = [];

        foreach ($summaryAttributes as $index => $attrString) {
            // Verify aria-expanded="false" is present.
            $this->assertMatchesRegularExpression(
                '/aria-expanded="false"/',
                $attrString,
                sprintf('Summary at index %d must have aria-expanded="false".', $index)
            );

            // Extract aria-controls value.
            preg_match('/aria-controls="([^"]+)"/', $attrString, $ariaControlsMatch);
            $this->assertNotEmpty(
                $ariaControlsMatch,
                sprintf('Summary at index %d must have an aria-controls attribute.', $index)
            );

            $ariaControlsValue = $ariaControlsMatch[1];
            $ariaControlsValues[] = $ariaControlsValue;

            // Verify aria-controls references the corresponding panel ID.
            $this->assertSame(
                $panelIds[$index],
                $ariaControlsValue,
                sprintf(
                    'Summary at index %d: aria-controls="%s" must match panel id="%s".',
                    $index,
                    $ariaControlsValue,
                    $panelIds[$index]
                )
            );
        }

        // Verify all aria-controls values are unique.
        $uniqueAriaControls = array_unique($ariaControlsValues);
        $this->assertCount(
            count($ariaControlsValues),
            $uniqueAriaControls,
            'All aria-controls values must be unique.'
        );
    }

    /**
     * Data provider generating 110+ random valid FAQ item arrays.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function validFaqArrayProvider(): array
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

            $cases["random_valid_array_{$i}"] = [$items];
        }

        // Edge case: single item.
        $cases['single_item'] = [[
            ['question' => 'What is PHP?', 'answer' => 'A programming language.'],
        ]];

        // Edge case: maximum items (10).
        $tenItems = [];
        for ($i = 0; $i < 10; $i++) {
            $tenItems[] = ['question' => "Question {$i}?", 'answer' => "Answer {$i}."];
        }
        $cases['ten_items'] = [$tenItems];

        // Edge case: items with HTML content (safe HTML preserved by wp_kses_post).
        $cases['html_content'] = [[
            ['question' => 'What about <strong>bold</strong>?', 'answer' => '<p>It is preserved.</p>'],
            ['question' => 'And <em>italic</em>?', 'answer' => '<p>Also preserved.</p>'],
        ]];

        // Edge case: items with special characters.
        $cases['special_characters'] = [[
            ['question' => 'What about "quotes" & ampersands?', 'answer' => 'They are handled.'],
            ['question' => 'Unicode: Ünïcödé?', 'answer' => 'Supported: 日本語'],
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
     * Generate a random non-empty string that is non-empty after trim.
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
