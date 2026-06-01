<?php
/**
 * Class Ajax_Generate_Faqs
 *
 * Handles the AJAX request for generating FAQs.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes;

use WPBits\AiFaqGenerator\Includes\Services\Faq_Generator;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class Ajax_Generate_Faqs
{
    private ?Faq_Generator $faq_generator;

    /**
     * @param Faq_Generator|null $faq_generator Optional dependency for testing.
     */
    public function __construct(?Faq_Generator $faq_generator = null)
    {
        $this->faq_generator = $faq_generator;
    }

    /**
     * Register the AJAX action hook.
     */
    public function init(): void
    {
        add_action('wp_ajax_aifaq_generate_faqs', [$this, 'handle']);
    }

    /**
     * Handle the AJAX request.
     */
    public function handle(): void
    {
        // 1. Verify nonce
        if (! check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }

        // 2. Validate post_id
        $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : null;

        if (empty($post_id) || ! is_numeric($post_id) || (int) $post_id <= 0) {
            wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400);
        }

        $post_id = (int) $post_id;

        // 3. Check user capability
        if (! current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403);
        }

        // 4. Generate FAQs
        try {
            $faq_generator = $this->faq_generator ?? new Faq_Generator(
                new OpenAIClient(),
                new Prompt_Builder()
            );

            $faqs = $faq_generator->generateFaqs($post_id);
        } catch (\InvalidArgumentException $e) {
            wp_send_json_error(['message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }

        // 5. Store FAQ data as post meta
        $updated = update_post_meta($post_id, '_aifaq_generated_faqs', wp_json_encode($faqs));

        if ($updated === false) {
            wp_send_json_error(['message' => 'FAQ data could not be saved.'], 500);
        }

        // 6. Return success response
        wp_send_json_success(['faqs' => $faqs, 'count' => count($faqs)]);
    }
}
