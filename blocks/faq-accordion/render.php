<?php
/**
 * Server-side rendering for the FAQ Accordion block.
 *
 * @package WPBits\AiFaqGenerator
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Blocks\FaqAccordion;

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

    $output = '<div class="wp-block-wpbits-faq-accordion">';

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

        $output .= '<details class="faq-accordion-item">';
        $output .= '<summary aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
        $output .= $question;
        $output .= '</summary>';
        $output .= '<div id="' . esc_attr($panel_id) . '" class="faq-accordion-content">';
        $output .= $answer;
        $output .= '</div>';
        $output .= '</details>';
    }

    $output .= '</div>';

    return $output;
}
