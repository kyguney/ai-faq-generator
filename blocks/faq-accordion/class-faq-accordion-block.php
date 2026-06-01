<?php
/**
 * FAQ Accordion Block registration.
 *
 * @package WPBits\AiFaqGenerator
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Blocks\FaqAccordion;

/**
 * Register the FAQ Accordion block.
 *
 * Calls register_block_type() with the block directory path and render callback.
 * Logs an error if registration fails.
 *
 * @return void
 */
function register_faq_accordion_block(): void {
    require_once __DIR__ . '/render.php';

    $result = register_block_type(
        AFG_PLUGIN_PATH . 'blocks/faq-accordion',
        [
            'render_callback' => __NAMESPACE__ . '\\render_faq_accordion_block',
        ]
    );

    if ( $result === false || is_wp_error( $result ) ) {
        error_log( 'FAQ Accordion Block: Failed to register block.' );
    }
}

add_action( 'init', __NAMESPACE__ . '\\register_faq_accordion_block' );
