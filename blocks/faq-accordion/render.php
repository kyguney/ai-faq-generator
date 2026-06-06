<?php
/**
 * Server-side rendering for the FAQ Accordion block.
 *
 * @package WPBits\AiFaqGenerator
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Blocks\FaqAccordion;

/**
 * Get a validated title tag from block attributes.
 *
 * Returns one of 'h2', 'h3', or 'h4'. Falls back to 'h3' if the value
 * is missing or not in the allowed set.
 *
 * @param array $attributes Block attributes.
 * @return string Validated heading tag.
 */
function get_validated_title_tag(array $attributes): string {
    $tag = $attributes['titleTag'] ?? 'h3';
    return in_array($tag, ['h2', 'h3', 'h4'], true) ? $tag : 'h3';
}

/**
 * Get a validated icon position from block attributes.
 *
 * Returns one of 'left', 'right', or 'none'. Falls back to 'left' if the value
 * is missing or not in the allowed set.
 *
 * @param array $attributes Block attributes.
 * @return string Validated icon position.
 */
function get_validated_icon_position(array $attributes): string {
    $pos = $attributes['iconPosition'] ?? 'left';
    return in_array($pos, ['left', 'right', 'none'], true) ? $pos : 'left';
}

/**
 * Get a validated boolean value from block attributes.
 *
 * Uses strict === true comparison to ensure only boolean true activates behavior.
 * Any other value (including truthy strings, integers, etc.) is treated as false.
 *
 * @param array $attributes Block attributes.
 * @param string $key        The attribute key to check.
 * @return bool True only if the attribute is strictly boolean true.
 */
function get_validated_boolean(array $attributes, string $key): bool {
    return isset($attributes[$key]) && $attributes[$key] === true;
}

/**
 * Render callback for the FAQ Accordion block.
 *
 * Receives block attributes, validates and sanitizes FAQ items,
 * and returns the accordion HTML using native <details>/<summary> elements.
 *
 * Uses get_block_wrapper_attributes() to merge custom classes (icon position,
 * animation) with WordPress supports-generated classes and inline styles.
 *
 * @param array    $attributes Block attributes containing the FAQ items.
 * @param string   $content    Block inner content (unused for dynamic blocks).
 * @param \WP_Block $block     Block instance.
 * @return string The rendered HTML output, or empty string if no valid items.
 */
function render_faq_accordion_block(array $attributes, string $content = '', ?\WP_Block $block = null): string {
    $items = $attributes['items'] ?? [];

    if (!is_array($items) || empty($items)) {
        return '';
    }

    // Validate new attributes using helper functions.
    $title_tag       = get_validated_title_tag($attributes);
    $icon_position   = get_validated_icon_position($attributes);
    $open_first_item  = get_validated_boolean($attributes, 'openFirstItem');
    $enable_animation = get_validated_boolean($attributes, 'enableAnimation');

    // New styling attributes
    $title_color     = $attributes['titleColor'] ?? '';
    $title_font_size = $attributes['titleFontSize'] ?? 0;
    $title_font_family = $attributes['titleFontFamily'] ?? '';
    $title_padding    = $attributes['titlePadding'] ?? 16;
    $content_color    = $attributes['contentColor'] ?? '';
    $content_font_size = $attributes['contentFontSize'] ?? 0;
    $content_font_family = $attributes['contentFontFamily'] ?? '';
    $content_padding  = $attributes['contentPadding'] ?? 16;
    $item_spacing     = $attributes['itemSpacing'] ?? 8;
    $selected_icon    = $attributes['selectedIcon'] ?? 'chevron';

    // Build inline styles
    $title_style = '';
    $title_styles_arr = [];
    if (!empty($title_color)) {
        $title_styles_arr[] = 'color:' . esc_attr($title_color);
    }
    if (!empty($title_font_size) && $title_font_size > 0) {
        $title_styles_arr[] = 'font-size:' . absint($title_font_size) . 'px';
    }
    if (!empty($title_font_family)) {
        $title_styles_arr[] = 'font-family:' . esc_attr($title_font_family);
    }
    if ($title_padding !== 16) {
        $title_styles_arr[] = 'padding:' . absint($title_padding) . 'px';
    }
    if (!empty($title_styles_arr)) {
        $title_style = ' style="' . implode(';', $title_styles_arr) . '"';
    }

    $content_style = '';
    $content_styles_arr = [];
    if (!empty($content_color)) {
        $content_styles_arr[] = 'color:' . esc_attr($content_color);
    }
    if (!empty($content_font_size) && $content_font_size > 0) {
        $content_styles_arr[] = 'font-size:' . absint($content_font_size) . 'px';
    }
    if (!empty($content_font_family)) {
        $content_styles_arr[] = 'font-family:' . esc_attr($content_font_family);
    }
    if ($content_padding !== 16) {
        $content_styles_arr[] = 'padding:' . absint($content_padding) . 'px';
    }
    if (!empty($content_styles_arr)) {
        $content_style = ' style="' . implode(';', $content_styles_arr) . '"';
    }

    $item_style = '';
    if ($item_spacing !== 8) {
        $item_style = ' style="margin-bottom:' . absint($item_spacing) . 'px"';
    }

    // Icon character mapping
    $icon_chars = [
        'chevron' => '▾',
        'chevron-right' => '▸',
        'plus' => '+',
        'arrow' => '→',
    ];
    $icon_char = $icon_chars[$selected_icon] ?? '▾';
    $show_icon = $selected_icon !== 'none' && $icon_position !== 'none';

    // Build extra classes: icon-position class + optional animation class.
    $icon_class_map = [
        'left'  => 'has-icon-left',
        'right' => 'has-icon-right',
        'none'  => 'has-no-icon',
    ];
    $extra_classes = $icon_class_map[$icon_position];

    if ($enable_animation) {
        $extra_classes .= ' has-animation';
    }

    $wrapper_attributes = get_block_wrapper_attributes(['class' => $extra_classes]);
    $output = '<div ' . $wrapper_attributes . '>';

    $is_first_valid_item = true;

    foreach ($items as $index => $item) {
        if (!is_array($item)) {
            continue;
        }

        $question_value = $item['question'] ?? '';
        $answer_value   = $item['answer'] ?? '';

        if (!is_string($question_value) || !is_string($answer_value)) {
            continue;
        }

        if (empty($question_value) || empty($answer_value)) {
            continue;
        }

        $question = wp_kses_post($question_value);
        $answer   = wp_kses_post($answer_value);
        $panel_id = 'faq-panel-' . ($index + 1);

        // Add open attribute to the first valid item when openFirstItem is true.
        $open_attr = '';
        if ($open_first_item && $is_first_valid_item) {
            $open_attr = ' open';
            $is_first_valid_item = false;
        } else {
            $is_first_valid_item = false;
        }

        $output .= '<details class="faq-accordion-item"' . $item_style . $open_attr . '>';
        $output .= '<summary aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
        if ($show_icon) {
            $icon_order = $icon_position === 'right' ? ' style="order:1"' : '';
            $output .= '<span class="faq-accordion-icon"' . $icon_order . '>' . $icon_char . '</span>';
        }
        $output .= '<' . $title_tag . $title_style . '>' . $question . '</' . $title_tag . '>';
        $output .= '</summary>';
        $output .= '<div id="' . esc_attr($panel_id) . '" class="faq-accordion-content"' . $content_style . '>';
        $output .= '<div class="faq-accordion-content__inner">';
        $output .= $answer;
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</details>';
    }

    $output .= '</div>';

    return $output;
}
