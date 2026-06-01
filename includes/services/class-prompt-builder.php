<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Services;

/**
 * Prompt Builder service.
 *
 * Constructs deterministic prompt strings for AI-powered FAQ generation.
 * Accepts post data (title, content) and a FAQ count setting, then produces
 * a well-structured instruction string that directs the AI provider to return
 * a JSON array of question/answer pairs.
 *
 * @package WPBits\AiFaqGenerator\Includes\Services
 */
class Prompt_Builder
{
    private const CONTENT_LIMIT = 2000;
    private const DEFAULT_FAQ_COUNT = 5;
    private const MIN_FAQ_COUNT = 1;
    private const MAX_FAQ_COUNT = 20;

    /**
     * Build the prompt string from post data and settings.
     *
     * @param string   $post_title   The post title (may contain HTML).
     * @param string   $post_content The post content (may contain HTML).
     * @param int|null $faq_count    Number of FAQs to generate (1-20, default 5).
     * @return string The assembled prompt string.
     */
    public function build(string $post_title, string $post_content, ?int $faq_count = null): string
    {
        $title = $this->sanitize_input($post_title);
        $content = $this->sanitize_input($post_content);
        $content = $this->truncate_content($content);
        $count = $this->resolve_faq_count($faq_count);

        return $this->assemble_prompt($title, $content, $count);
    }

    /**
     * Strip HTML tags and trim whitespace from input.
     *
     * @param string $input Raw input string.
     * @return string Sanitized string with no HTML and no leading/trailing whitespace.
     */
    private function sanitize_input(string $input): string
    {
        return trim(wp_strip_all_tags($input));
    }

    /**
     * Truncate content to the character limit.
     *
     * @param string $content Sanitized content string.
     * @return string Content cut to exactly 2000 characters if it exceeds the limit.
     */
    private function truncate_content(string $content): string
    {
        if (mb_strlen($content, 'UTF-8') > self::CONTENT_LIMIT) {
            return mb_substr($content, 0, self::CONTENT_LIMIT, 'UTF-8');
        }

        return $content;
    }

    /**
     * Validate and clamp FAQ count to allowed range.
     *
     * @param int|null $faq_count The requested FAQ count, or null for default.
     * @return int Clamped FAQ count between 1 and 20, or 5 when null.
     */
    private function resolve_faq_count(?int $faq_count): int
    {
        if ($faq_count === null) {
            return self::DEFAULT_FAQ_COUNT;
        }

        return max(self::MIN_FAQ_COUNT, min(self::MAX_FAQ_COUNT, $faq_count));
    }

    /**
     * Assemble the final prompt string with instructions and context.
     *
     * @param string $title   Sanitized post title (empty string if absent).
     * @param string $content Sanitized and truncated post content (empty string if absent).
     * @param int    $count   Validated FAQ count.
     * @return string The complete prompt string.
     */
    private function assemble_prompt(string $title, string $content, int $count): string
    {
        $parts = [];

        $parts[] = "Generate exactly {$count} frequently asked questions (FAQs) based on the provided content.";
        $parts[] = 'Return a JSON array as the top-level structure.';
        $parts[] = 'Each array element must be an object containing a "question" key and an "answer" key, both with non-empty string values.';
        $parts[] = 'Return only the raw JSON array without surrounding prose, markdown code fences, or any other formatting.';

        if ($title !== '') {
            $parts[] = "Title: {$title}";
        }

        if ($content !== '') {
            $parts[] = "Content: {$content}";
        }

        return implode("\n", $parts);
    }
}
