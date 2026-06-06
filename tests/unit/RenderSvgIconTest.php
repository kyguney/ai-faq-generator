<?php
/**
 * Unit tests for the FAQ Accordion render.php SVG icon output and style application.
 *
 * Feature: accordion-style-enhancements
 * Validates: Requirements 7.5, 7.6, 8.1, 8.2, 8.3, 8.4
 *
 * Tests that:
 * - Legacy icon values map correctly via resolve_icon_id()
 * - SVG markup is present for valid icons via get_svg_icon_markup()
 * - "none" produces no icon element in rendered output
 * - Backward compatibility for legacy icon identifiers
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\render_faq_accordion_block;
use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\resolve_icon_id;
use function WPBits\AiFaqGenerator\Blocks\FaqAccordion\get_svg_icon_markup;

class RenderSvgIconTest extends TestCase
{
    /**
     * Legacy icon map matching the one used in render.php.
     */
    private array $legacyMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->legacyMap = [
            'chevron' => 'chevron-down',
            'plus'    => 'plus-minus',
            'arrow'   => 'arrow-down',
        ];
    }

    // ─── Legacy icon mapping tests ───────────────────────────────────────────

    /**
     * **Validates: Requirements 8.1**
     *
     * Legacy "chevron" identifier maps to "chevron-down".
     */
    #[Test]
    public function legacy_chevron_maps_to_chevron_down(): void
    {
        $result = resolve_icon_id('chevron', $this->legacyMap);

        $this->assertSame('chevron-down', $result);
    }

    /**
     * **Validates: Requirements 8.2**
     *
     * Legacy "plus" identifier maps to "plus-minus".
     */
    #[Test]
    public function legacy_plus_maps_to_plus_minus(): void
    {
        $result = resolve_icon_id('plus', $this->legacyMap);

        $this->assertSame('plus-minus', $result);
    }

    /**
     * **Validates: Requirements 8.3**
     *
     * Legacy "arrow" identifier maps to "arrow-down".
     */
    #[Test]
    public function legacy_arrow_maps_to_arrow_down(): void
    {
        $result = resolve_icon_id('arrow', $this->legacyMap);

        $this->assertSame('arrow-down', $result);
    }

    /**
     * **Validates: Requirements 8.4**
     *
     * Unrecognized identifier falls back to "chevron-down".
     */
    #[Test]
    public function unrecognized_identifier_falls_back_to_chevron_down(): void
    {
        $result = resolve_icon_id('bogus', $this->legacyMap);

        $this->assertSame('chevron-down', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * Valid icon IDs that are already in the new format pass through unchanged.
     */
    #[Test]
    public function valid_icon_ids_pass_through_unchanged(): void
    {
        $result = resolve_icon_id('arrow-right', $this->legacyMap);

        $this->assertSame('arrow-right', $result);
    }

    // ─── SVG markup output tests ─────────────────────────────────────────────

    /**
     * **Validates: Requirements 7.5**
     *
     * get_svg_icon_markup returns SVG markup for "chevron-down".
     */
    #[Test]
    public function svg_markup_present_for_chevron_down(): void
    {
        $result = get_svg_icon_markup('chevron-down', 20);

        $this->assertStringContainsString('<svg', $result);
        $this->assertStringContainsString('</svg>', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * get_svg_icon_markup returns non-empty SVG string for all valid icon IDs.
     */
    #[Test]
    public function svg_markup_present_for_all_valid_icons(): void
    {
        $validIcons = ['chevron-down', 'chevron-right', 'plus-minus', 'arrow-down', 'arrow-right'];

        foreach ($validIcons as $iconId) {
            $result = get_svg_icon_markup($iconId, 20);

            $this->assertNotEmpty($result, "Expected non-empty SVG for icon: {$iconId}");
            $this->assertStringContainsString('<svg', $result, "Expected <svg in output for icon: {$iconId}");
        }
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * SVG markup includes the correct width and height attributes for the given size.
     */
    #[Test]
    public function svg_markup_includes_correct_size(): void
    {
        $result = get_svg_icon_markup('chevron-down', 24);

        $this->assertStringContainsString('width="24"', $result);
        $this->assertStringContainsString('height="24"', $result);
    }

    /**
     * **Validates: Requirements 7.6**
     *
     * "none" icon ID produces an empty string from get_svg_icon_markup.
     */
    #[Test]
    public function none_produces_empty_string_from_get_svg_icon_markup(): void
    {
        $result = get_svg_icon_markup('none', 20);

        $this->assertSame('', $result);
    }

    // ─── Full render integration tests ───────────────────────────────────────

    /**
     * **Validates: Requirements 7.6**
     *
     * Full render with selectedIcon "none" has no icon element in output.
     */
    #[Test]
    public function full_render_with_none_icon_has_no_icon_element(): void
    {
        $result = render_faq_accordion_block([
            'items'        => [['question' => 'Q1', 'answer' => 'A1']],
            'selectedIcon' => 'none',
            'iconPosition' => 'left',
        ]);

        $this->assertStringNotContainsString('faq-accordion-icon', $result);
    }

    /**
     * **Validates: Requirements 7.5**
     *
     * Full render with "chevron-down" has SVG in icon element.
     */
    #[Test]
    public function full_render_with_chevron_down_has_svg_in_icon_element(): void
    {
        $result = render_faq_accordion_block([
            'items'        => [['question' => 'Q1', 'answer' => 'A1']],
            'selectedIcon' => 'chevron-down',
            'iconPosition' => 'left',
        ]);

        $this->assertStringContainsString('<span class="faq-accordion-icon">', $result);
        $this->assertStringContainsString('<svg', $result);
    }

    /**
     * **Validates: Requirements 8.1**
     *
     * Full render with legacy "chevron" produces SVG output (backward compatibility).
     */
    #[Test]
    public function full_render_with_legacy_chevron_produces_svg(): void
    {
        $result = render_faq_accordion_block([
            'items'        => [['question' => 'Q1', 'answer' => 'A1']],
            'selectedIcon' => 'chevron',
            'iconPosition' => 'left',
        ]);

        $this->assertStringContainsString('<span class="faq-accordion-icon">', $result);
        $this->assertStringContainsString('<svg', $result);
    }
}
