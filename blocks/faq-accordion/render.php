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
 * @param array $attributes Block attributes containing the FAQ items.
 * @return string The rendered HTML output, or empty string if no valid items.
 */
function render_faq_accordion_block(array $attributes): string {
    $items = $attributes['items'] ?? [];

    if (!is_array($items) || empty($items)) {
        return '';
    }

    // Validate new attributes using helper functions.
    $title_tag      = get_validated_title_tag($attributes);
    $icon_position  = get_validated_icon_position($attributes);
    $open_first_item = get_validated_boolean($attributes, 'openFirstItem');
    $enable_animation = get_validated_boolean($attributes, 'enableAnimation');

    // Build CSS class string: base class + icon-position class + optional animation class.
    $icon_class_map = [
        'left'  => 'has-icon-left',
        'right' => 'has-icon-right',
        'none'  => 'has-no-icon',
    ];
    $classes = 'wp-block-wpbits-faq-accordion ' . $icon_class_map[$icon_position];

    if ($enable_animation) {
        $classes .= ' has-animation';
    }

    $output = '<div class="' . esc_attr($classes) . '">';

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

        $output .= '<details class="faq-accordion-item"' . $open_attr . '>';
        $output .= '<summary aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
        $output .= '<' . $title_tag . '>' . $question . '</' . $title_tag . '>';
        $output .= '</summary>';
        $output .= '<div id="' . esc_attr($panel_id) . '" class="faq-accordion-content">';
        $output .= '<div class="faq-accordion-content__inner">';
        $output .= $answer;
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</details>';
    }

    $output .= '</div>';

    return $output;
}
