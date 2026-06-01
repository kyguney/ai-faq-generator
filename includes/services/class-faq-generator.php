<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Services;

use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;

/**
 * FAQ Generator service.
 *
 * Orchestrates the full FAQ generation pipeline: validates the post ID,
 * fetches post data, delegates prompt construction to the Prompt_Builder,
 * invokes the AI provider via AIProviderInterface, filters empty items,
 * and returns the structured FAQ array.
 *
 * @package WPBits\AiFaqGenerator\Includes\Services
 */
class Faq_Generator
{
    private AIProviderInterface $ai_provider;
    private Prompt_Builder $prompt_builder;

    /**
     * @param AIProviderInterface $ai_provider   The AI provider for FAQ generation.
     * @param Prompt_Builder      $prompt_builder The prompt builder for constructing prompts.
     */
    public function __construct(
        AIProviderInterface $ai_provider,
        Prompt_Builder $prompt_builder
    ) {
        $this->ai_provider = $ai_provider;
        $this->prompt_builder = $prompt_builder;
    }

    /**
     * Generate FAQs for a given WordPress post.
     *
     * @param int $post_id The WordPress post ID.
     * @return array<int, array{question: string, answer: string}>
     * @throws \InvalidArgumentException If post_id is zero or negative.
     * @throws \RuntimeException If post not found or not published.
     */
    public function generateFaqs(int $post_id): array
    {
        if ($post_id <= 0) {
            throw new \InvalidArgumentException("Invalid post ID: {$post_id}");
        }

        $post = get_post($post_id);

        if ($post === null) {
            throw new \RuntimeException("Post not found: {$post_id}");
        }

        if ($post->post_status !== 'publish') {
            throw new \RuntimeException("Post is not published: {$post_id}");
        }

        $settings = get_option('afg_settings', []);
        $faq_count = isset($settings['faq_count']) && $settings['faq_count'] !== null
            ? (int) $settings['faq_count']
            : null;

        $prompt = $this->prompt_builder->build($post->post_title, $post->post_content, $faq_count);

        $faqs = $this->ai_provider->generateFaqs($prompt);

        $filtered = array_filter($faqs, function (array $item): bool {
            return isset($item['question'], $item['answer'])
                && trim($item['question']) !== ''
                && trim($item['answer']) !== '';
        });

        return array_values($filtered);
    }
}
