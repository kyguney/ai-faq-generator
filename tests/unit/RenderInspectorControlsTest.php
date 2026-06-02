<?php
/**
 * Unit tests for the FAQ Accordion render.php inspector control attributes.
 *
 * Feature: block-inspector-controls
 * Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
 *
 * Tests that render_faq_accordion_block correctly applies:
 * - Title tag heading elements inside <summary>
 * - openFirstItem open attribute on first <details> only
 * - iconPosition CSS class on wrapper
 * - enableAnimation CSS class on wrapper
 * - Backward compatibility with default values
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;
use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\get_validated_title_tag;
use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\get_validated_icon_position;
use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\get_validated_boolean;

class RenderInspectorControlsTest extends TestCase
{
    /**
     * Helper: a minimal valid FAQ items array for tests.
     */
    private function validItems(int $count = 2): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = ['question' => "Question {$i}?", 'answer' => "Answer {$i}."];
        }
        return $items;
    }

    // ─── Title Tag Tests ─────────────────────────────────────────────────────

    /**
     * **Validates: Requirements 7.1**
     *
     * Rendering with titleTag "h2" wraps question text inside <h2> within <summary>.
     */
    #[Test]
    public function title_tag_h2_renders_h2_heading_inside_summary(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'titleTag' => 'h2',
        ]);

        $this->assertStringContainsString('<summary', $html);
        $this->assertStringContainsString('<h2>Question 1?</h2>', $html);
    }

    /**
     * **Validates: Requirements 7.1**
     *
     * Rendering with titleTag "h3" wraps question text inside <h3> within <summary>.
     */
    #[Test]
    public function title_tag_h3_renders_h3_heading_inside_summary(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'titleTag' => 'h3',
        ]);

        $this->assertStringContainsString('<h3>Question 1?</h3>', $html);
    }

    /**
     * **Validates: Requirements 7.1**
     *
     * Rendering with titleTag "h4" wraps question text inside <h4> within <summary>.
     */
    #[Test]
    public function title_tag_h4_renders_h4_heading_inside_summary(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'titleTag' => 'h4',
        ]);

        $this->assertStringContainsString('<h4>Question 1?</h4>', $html);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * Invalid titleTag value falls back to h3 in rendered output.
     */
    #[Test]
    public function invalid_title_tag_falls_back_to_h3(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'titleTag' => 'h1',
        ]);

        $this->assertStringContainsString('<h3>Question 1?</h3>', $html);
        $this->assertStringNotContainsString('<h1>', $html);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * get_validated_title_tag returns h3 for missing titleTag attribute.
     */
    #[Test]
    public function get_validated_title_tag_returns_h3_for_missing_attribute(): void
    {
        $this->assertSame('h3', get_validated_title_tag([]));
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * get_validated_title_tag returns h3 for arbitrary string value.
     */
    #[Test]
    public function get_validated_title_tag_returns_h3_for_arbitrary_string(): void
    {
        $this->assertSame('h3', get_validated_title_tag(['titleTag' => 'div']));
        $this->assertSame('h3', get_validated_title_tag(['titleTag' => '']));
        $this->assertSame('h3', get_validated_title_tag(['titleTag' => 'H3']));
    }

    // ─── Open First Item Tests ───────────────────────────────────────────────

    /**
     * **Validates: Requirements 7.2**
     *
     * openFirstItem true adds "open" attribute to first <details> only.
     */
    #[Test]
    public function open_first_item_true_adds_open_to_first_details_only(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(3),
            'openFirstItem' => true,
        ]);

        // Count open attributes on details elements.
        preg_match_all('/<details[^>]*>/', $html, $matches);
        $detailsElements = $matches[0];

        $this->assertCount(3, $detailsElements);

        // First <details> should have "open".
        $this->assertMatchesRegularExpression('/\bopen\b/', $detailsElements[0]);

        // Second and third should NOT have "open".
        $this->assertDoesNotMatchRegularExpression('/\bopen\b/', $detailsElements[1]);
        $this->assertDoesNotMatchRegularExpression('/\bopen\b/', $detailsElements[2]);
    }

    /**
     * **Validates: Requirements 7.2**
     *
     * openFirstItem false adds no "open" attribute to any <details>.
     */
    #[Test]
    public function open_first_item_false_adds_no_open_attribute(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(3),
            'openFirstItem' => false,
        ]);

        preg_match_all('/<details[^>]*>/', $html, $matches);
        $detailsElements = $matches[0];

        foreach ($detailsElements as $index => $element) {
            $this->assertDoesNotMatchRegularExpression(
                '/\bopen\b/',
                $element,
                "Details element at index {$index} should not have open attribute."
            );
        }
    }

    /**
     * **Validates: Requirements 7.7**
     *
     * openFirstItem with non-boolean value is treated as false (no open attribute).
     */
    #[Test]
    public function open_first_item_non_boolean_treated_as_false(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(2),
            'openFirstItem' => 'yes',
        ]);

        preg_match_all('/<details[^>]*>/', $html, $matches);
        foreach ($matches[0] as $element) {
            $this->assertDoesNotMatchRegularExpression('/\bopen\b/', $element);
        }
    }

    // ─── Icon Position Tests ─────────────────────────────────────────────────

    /**
     * **Validates: Requirements 7.3**
     *
     * iconPosition "left" produces "has-icon-left" CSS class on wrapper.
     */
    #[Test]
    public function icon_position_left_produces_has_icon_left_class(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'iconPosition' => 'left',
        ]);

        $this->assertStringContainsString('has-icon-left', $html);
        $this->assertStringNotContainsString('has-icon-right', $html);
        $this->assertStringNotContainsString('has-no-icon', $html);
    }

    /**
     * **Validates: Requirements 7.3**
     *
     * iconPosition "right" produces "has-icon-right" CSS class on wrapper.
     */
    #[Test]
    public function icon_position_right_produces_has_icon_right_class(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'iconPosition' => 'right',
        ]);

        $this->assertStringContainsString('has-icon-right', $html);
        $this->assertStringNotContainsString('has-icon-left', $html);
        $this->assertStringNotContainsString('has-no-icon', $html);
    }

    /**
     * **Validates: Requirements 7.3**
     *
     * iconPosition "none" produces "has-no-icon" CSS class on wrapper.
     */
    #[Test]
    public function icon_position_none_produces_has_no_icon_class(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'iconPosition' => 'none',
        ]);

        $this->assertStringContainsString('has-no-icon', $html);
        $this->assertStringNotContainsString('has-icon-left', $html);
        $this->assertStringNotContainsString('has-icon-right', $html);
    }

    /**
     * **Validates: Requirements 7.6**
     *
     * Invalid iconPosition falls back to "has-icon-left" class.
     */
    #[Test]
    public function invalid_icon_position_falls_back_to_has_icon_left(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'iconPosition' => 'center',
        ]);

        $this->assertStringContainsString('has-icon-left', $html);
    }

    /**
     * **Validates: Requirements 7.6**
     *
     * get_validated_icon_position returns "left" for missing attribute.
     */
    #[Test]
    public function get_validated_icon_position_returns_left_for_missing(): void
    {
        $this->assertSame('left', get_validated_icon_position([]));
    }

    /**
     * **Validates: Requirements 7.6**
     *
     * get_validated_icon_position returns "left" for invalid string.
     */
    #[Test]
    public function get_validated_icon_position_returns_left_for_invalid_string(): void
    {
        $this->assertSame('left', get_validated_icon_position(['iconPosition' => 'top']));
        $this->assertSame('left', get_validated_icon_position(['iconPosition' => '']));
    }

    // ─── Enable Animation Tests ──────────────────────────────────────────────

    /**
     * **Validates: Requirements 7.4**
     *
     * enableAnimation true adds "has-animation" CSS class to wrapper.
     */
    #[Test]
    public function enable_animation_true_adds_has_animation_class(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'enableAnimation' => true,
        ]);

        $this->assertStringContainsString('has-animation', $html);
    }

    /**
     * **Validates: Requirements 7.4**
     *
     * enableAnimation false does NOT add "has-animation" CSS class.
     */
    #[Test]
    public function enable_animation_false_does_not_add_has_animation_class(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'enableAnimation' => false,
        ]);

        $this->assertStringNotContainsString('has-animation', $html);
    }

    /**
     * **Validates: Requirements 7.7**
     *
     * enableAnimation with non-boolean value is treated as false (no class).
     */
    #[Test]
    public function enable_animation_non_boolean_treated_as_false(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(1),
            'enableAnimation' => 'true',
        ]);

        $this->assertStringNotContainsString('has-animation', $html);
    }

    /**
     * **Validates: Requirements 7.7**
     *
     * get_validated_boolean returns false for non-boolean values.
     */
    #[Test]
    public function get_validated_boolean_returns_false_for_non_boolean(): void
    {
        $this->assertFalse(get_validated_boolean([], 'openFirstItem'));
        $this->assertFalse(get_validated_boolean(['openFirstItem' => 1], 'openFirstItem'));
        $this->assertFalse(get_validated_boolean(['openFirstItem' => 'true'], 'openFirstItem'));
        $this->assertFalse(get_validated_boolean(['openFirstItem' => null], 'openFirstItem'));
    }

    /**
     * **Validates: Requirements 7.7**
     *
     * get_validated_boolean returns true only for strict boolean true.
     */
    #[Test]
    public function get_validated_boolean_returns_true_for_strict_true(): void
    {
        $this->assertTrue(get_validated_boolean(['enableAnimation' => true], 'enableAnimation'));
        $this->assertTrue(get_validated_boolean(['openFirstItem' => true], 'openFirstItem'));
    }

    // ─── Backward Compatibility Tests ────────────────────────────────────────

    /**
     * **Validates: Requirements 7.5, 7.6, 7.7**
     *
     * Rendering without any new attributes uses defaults:
     * - titleTag defaults to h3
     * - openFirstItem defaults to false (no open)
     * - iconPosition defaults to left (has-icon-left)
     * - enableAnimation defaults to false (no has-animation)
     */
    #[Test]
    public function rendering_without_new_attributes_uses_defaults(): void
    {
        $html = render_faq_accordion_block([
            'items' => $this->validItems(2),
        ]);

        // Default title tag is h3.
        $this->assertStringContainsString('<h3>Question 1?</h3>', $html);
        $this->assertStringContainsString('<h3>Question 2?</h3>', $html);

        // Default openFirstItem is false — no open attribute.
        preg_match_all('/<details[^>]*>/', $html, $matches);
        foreach ($matches[0] as $element) {
            $this->assertDoesNotMatchRegularExpression('/\bopen\b/', $element);
        }

        // Default iconPosition is left.
        $this->assertStringContainsString('has-icon-left', $html);
        $this->assertStringNotContainsString('has-icon-right', $html);
        $this->assertStringNotContainsString('has-no-icon', $html);

        // Default enableAnimation is false.
        $this->assertStringNotContainsString('has-animation', $html);
    }

    /**
     * **Validates: Requirements 7.5, 7.6, 7.7**
     *
     * Rendering with only items attribute (legacy block) produces valid output
     * with all defaults applied correctly.
     */
    #[Test]
    public function legacy_block_with_only_items_renders_valid_html(): void
    {
        $html = render_faq_accordion_block([
            'items' => [
                ['question' => 'What is FAQ?', 'answer' => 'Frequently Asked Questions.'],
            ],
        ]);

        // Should produce valid wrapper with base class and icon-left class.
        $this->assertStringContainsString('wp-block-wpbits-faq-accordion', $html);
        $this->assertStringContainsString('has-icon-left', $html);
        $this->assertStringContainsString('<h3>What is FAQ?</h3>', $html);
        $this->assertStringContainsString('Frequently Asked Questions.', $html);
        $this->assertStringNotContainsString('has-animation', $html);
    }
}
