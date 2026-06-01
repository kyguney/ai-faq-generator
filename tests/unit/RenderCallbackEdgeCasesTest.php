<?php
/**
 * Unit tests for the FAQ Accordion render callback edge cases.
 *
 * Feature: faq-accordion-block
 * Validates: Requirements 5.6, 7.2, 7.5
 *
 * Tests that the render callback handles edge cases gracefully:
 * - Empty items returns empty string
 * - Non-array items returns empty string
 * - Missing items attribute returns empty string
 * - Render always returns a string type
 * - Single valid item returns non-empty string containing the item content
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;

class RenderCallbackEdgeCasesTest extends TestCase
{
    /**
     * **Validates: Requirements 5.6**
     *
     * Empty items array should return an empty string with no visible markup.
     */
    #[Test]
    public function empty_items_array_returns_empty_string(): void
    {
        $result = render_faq_accordion_block(['items' => []]);

        $this->assertSame('', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * When items is null, the render callback should return an empty string.
     */
    #[Test]
    public function null_items_returns_empty_string(): void
    {
        $result = render_faq_accordion_block(['items' => null]);

        $this->assertSame('', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * When items is a string, the render callback should return an empty string.
     */
    #[Test]
    public function string_items_returns_empty_string(): void
    {
        $result = render_faq_accordion_block(['items' => 'not an array']);

        $this->assertSame('', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * When items is an integer, the render callback should return an empty string.
     */
    #[Test]
    public function integer_items_returns_empty_string(): void
    {
        $result = render_faq_accordion_block(['items' => 42]);

        $this->assertSame('', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * When items key is missing from attributes entirely, the render callback
     * should return an empty string.
     */
    #[Test]
    public function missing_items_key_returns_empty_string(): void
    {
        $result = render_faq_accordion_block([]);

        $this->assertSame('', $result);
    }

    /**
     * **Validates: Requirements 7.2**
     *
     * The render callback must always return a string type, never null or false.
     */
    #[Test]
    public function render_always_returns_string_type_with_empty_items(): void
    {
        $result = render_faq_accordion_block(['items' => []]);

        $this->assertIsString($result);
    }

    /**
     * **Validates: Requirements 7.2**
     *
     * The render callback must always return a string type even with invalid input.
     */
    #[Test]
    public function render_always_returns_string_type_with_null_items(): void
    {
        $result = render_faq_accordion_block(['items' => null]);

        $this->assertIsString($result);
    }

    /**
     * **Validates: Requirements 7.2**
     *
     * The render callback must always return a string type with valid items.
     */
    #[Test]
    public function render_always_returns_string_type_with_valid_items(): void
    {
        $result = render_faq_accordion_block(['items' => [
            ['question' => 'What is PHP?', 'answer' => 'A programming language.'],
        ]]);

        $this->assertIsString($result);
    }

    /**
     * **Validates: Requirements 5.6, 7.2**
     *
     * A single valid item should produce a non-empty string containing the
     * question and answer content.
     */
    #[Test]
    public function single_valid_item_returns_non_empty_string_with_content(): void
    {
        $question = 'What is WordPress?';
        $answer = 'A content management system.';

        $result = render_faq_accordion_block(['items' => [
            ['question' => $question, 'answer' => $answer],
        ]]);

        $this->assertNotEmpty($result);
        $this->assertStringContainsString($question, $result);
        $this->assertStringContainsString($answer, $result);
    }
}
