<?php
/**
 * Server-side rendering for the FAQ Accordion block.
 *
 * @package WPBits\AiFaqGenerator
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Blocks\FaqAccordion;

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
 * Resolve an icon identifier, handling legacy values and fallback.
 *
 * Maps old icon identifiers (chevron, plus, arrow) to their new SVG counterparts
 * and falls back to 'chevron-down' for unrecognized values.
 *
 * @param string $icon_id    The raw icon identifier from block attributes.
 * @param array  $legacy_map Associative array mapping legacy IDs to new IDs.
 * @return string A valid icon identifier.
 */
function resolve_icon_id(string $icon_id, array $legacy_map): string {
    if (isset($legacy_map[$icon_id])) {
        return $legacy_map[$icon_id];
    }
    $valid_icons = ['chevron-down', 'chevron-right', 'plus-minus', 'arrow-down', 'arrow-right', 'none'];
    return in_array($icon_id, $valid_icons, true) ? $icon_id : 'chevron-down';
}

/**
 * Get SVG icon markup for a given icon identifier and size.
 *
 * Returns an SVG string with the specified width and height for each supported icon.
 * Returns an empty string for unrecognized identifiers or 'none'.
 *
 * @param string $icon_id The resolved icon identifier.
 * @param int    $size    The icon width and height in pixels.
 * @return string SVG markup string, or empty string if icon is not found.
 */
function get_svg_icon_markup(string $icon_id, int $size): string {
    $icons = [
        'chevron-down'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="currentColor"><path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"/></svg>',
        'chevron-right' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="currentColor"><path d="M10.6 6L15 12l-4.4 6-1.2-.9L13 12 9.4 6.9 10.6 6z"/></svg>',
        'plus-minus'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'arrow-down'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="currentColor"><path d="M16.2 13.2l-4 4.6-4.4-4.6 1.2-1.2 2.2 2.4V6h2v8.4l2-2.4 1 1.2z"/></svg>',
        'arrow-right'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="currentColor"><path d="M14.3 6.7l-1.1 1.1 4 4H4v1.5h13.2l-4 4 1.1 1.1 5.5-5.6-5.5-5.1z"/></svg>',
    ];

    if (!isset($icons[$icon_id])) {
        return '';
    }

    return sprintf($icons[$icon_id], $size, $size);
}

/**
 * Render callback for the FAQ Accordion block.
 *
 * Receives block attributes, validates and sanitizes FAQ items,
 * and returns the accordion HTML using native <details>/<summary> elements.
 * The question text is rendered directly inside <summary> without an additional
 * heading tag wrapper — the semantic structure comes from <details>/<summary> itself.
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

    // Validate attributes using helper functions.
    $icon_position   = get_validated_icon_position($attributes);
    $open_first_item  = get_validated_boolean($attributes, 'openFirstItem');
    $enable_animation = get_validated_boolean($attributes, 'enableAnimation');

    // Styling attributes
    $title_color     = $attributes['titleColor'] ?? '';
    $title_font_size = $attributes['titleFontSize'] ?? 0;
    $title_font_family = $attributes['titleFontFamily'] ?? '';
    $title_padding = $attributes['titlePadding'] ?? ['top' => '16px', 'right' => '20px', 'bottom' => '16px', 'left' => '20px'];
    $title_pad_top = $title_padding['top'] ?? '16px';
    $title_pad_right = $title_padding['right'] ?? '20px';
    $title_pad_bottom = $title_padding['bottom'] ?? '16px';
    $title_pad_left = $title_padding['left'] ?? '20px';
    $content_color    = $attributes['contentColor'] ?? '';
    $content_font_size = $attributes['contentFontSize'] ?? 0;
    $content_font_family = $attributes['contentFontFamily'] ?? '';
    $content_padding = $attributes['contentPadding'] ?? ['top' => '16px', 'right' => '20px', 'bottom' => '16px', 'left' => '20px'];
    $content_pad_top = $content_padding['top'] ?? '16px';
    $content_pad_right = $content_padding['right'] ?? '20px';
    $content_pad_bottom = $content_padding['bottom'] ?? '16px';
    $content_pad_left = $content_padding['left'] ?? '20px';
    $item_spacing     = $attributes['itemSpacing'] ?? 8;
    $selected_icon    = $attributes['selectedIcon'] ?? 'chevron';
    $icon_color       = $attributes['iconColor'] ?? '';

    // Background color attributes
    $title_bg_color       = $attributes['titleBackgroundColor'] ?? '';
    $content_bg_color     = $attributes['contentBackgroundColor'] ?? '';

    // Title font style attributes
    $title_font_weight     = $attributes['titleFontWeight'] ?? '';
    $title_font_style_val  = $attributes['titleFontStyle'] ?? '';
    $title_text_decoration = $attributes['titleTextDecoration'] ?? '';
    $title_text_transform  = $attributes['titleTextTransform'] ?? '';

    // Build summary (title area) inline styles — all title styles go here now
    $summary_styles_arr = [];
    if (!empty($title_color)) {
        $summary_styles_arr[] = 'color:' . esc_attr($title_color) . ' !important';
    }
    if (!empty($title_font_size) && $title_font_size > 0) {
        $summary_styles_arr[] = 'font-size:' . absint($title_font_size) . 'px !important';
    }
    if (!empty($title_font_family)) {
        $summary_styles_arr[] = 'font-family:' . esc_attr($title_font_family) . ' !important';
    }
    $summary_styles_arr[] = 'padding:' . esc_attr($title_pad_top) . ' ' . esc_attr($title_pad_right) . ' ' . esc_attr($title_pad_bottom) . ' ' . esc_attr($title_pad_left);
    if (!empty($title_bg_color)) {
        $summary_styles_arr[] = 'background-color:' . esc_attr($title_bg_color);
    }
    if (!empty($title_font_weight)) {
        $summary_styles_arr[] = 'font-weight:' . esc_attr($title_font_weight);
    }
    if ($title_font_style_val === 'italic') {
        $summary_styles_arr[] = 'font-style:italic';
    }
    if ($title_text_decoration === 'underline') {
        $summary_styles_arr[] = 'text-decoration:underline';
    }
    if (!empty($title_text_transform) && $title_text_transform !== 'none') {
        $summary_styles_arr[] = 'text-transform:' . esc_attr($title_text_transform);
    }
    $summary_style = '';
    if (!empty($summary_styles_arr)) {
        $summary_style = ' style="' . implode(';', $summary_styles_arr) . '"';
    }

    // Build content area inline styles
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
    // Always output padding to override CSS custom property defaults
    $content_styles_arr[] = 'padding:' . esc_attr($content_pad_top) . ' ' . esc_attr($content_pad_right) . ' ' . esc_attr($content_pad_bottom) . ' ' . esc_attr($content_pad_left);
    if (!empty($content_bg_color)) {
        $content_styles_arr[] = 'background-color:' . esc_attr($content_bg_color);
    }
    $content_style = ' style="' . implode(';', $content_styles_arr) . '"';

    $item_style = '';
    if ($item_spacing !== 8) {
        $item_style = ' style="margin-bottom:' . absint($item_spacing) . 'px"';
    }

    // Legacy icon identifier mapping
    $legacy_icon_map = [
        'chevron' => 'chevron-down',
        'plus'    => 'plus-minus',
        'arrow'   => 'arrow-down',
    ];

    // Resolve icon identifier with legacy mapping and fallback
    $resolved_icon_id = resolve_icon_id($selected_icon, $legacy_icon_map);

    // Compute icon size proportional to title font size
    $icon_size = 20;
    if (!empty($title_font_size) && $title_font_size > 0) {
        $icon_size = (int) round($title_font_size * 1.1);
    }

    // Get SVG markup for the resolved icon
    $icon_markup = get_svg_icon_markup($resolved_icon_id, $icon_size);
    $show_icon = $resolved_icon_id !== 'none' && $icon_position !== 'none';

    // Build icon inline style (for independent icon color)
    $icon_style_attr = '';
    if (!empty($icon_color)) {
        $icon_style_attr = ' style="color:' . esc_attr($icon_color) . '"';
    }

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
        $output .= '<summary' . $summary_style . ' aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
        if ($show_icon) {
            $output .= '<span class="faq-accordion-icon"' . $icon_style_attr . '>' . $icon_markup . '</span>';
        }
        $output .= '<span class="faq-accordion-title">' . $question . '</span>';
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
