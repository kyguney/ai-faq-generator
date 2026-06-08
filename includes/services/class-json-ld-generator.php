<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Services;

/**
 * JSON-LD Generator service.
 *
 * Outputs FAQPage structured data (JSON-LD) in the document <head> for
 * singular posts containing generated FAQs. Hooks into wp_head at
 * priority 20, reads FAQ meta, validates, transforms, and outputs a
 * schema.org-compliant FAQPage script tag.
 *
 * @package WPBits\AiFaqGenerator\Includes\Services
 */
class JSON_LD_Generator
{
    /**
     * Maximum number of FAQ items to include in the schema.
     */
    private const MAX_ITEMS = 25;

    /**
     * Register the wp_head hook.
     */
    public function init(): void
    {
        add_action('wp_head', [$this, 'output_schema'], 20);
    }

    /**
     * Output FAQPage JSON-LD schema in the document head.
     * Hooked to wp_head at priority 20.
     */
    public function output_schema(): void
    {
        if (!is_singular()) {
            return;
        }

        $raw_meta = get_post_meta(get_the_ID(), '_aifaq_generated_faqs', true);

        if (!is_string($raw_meta) || $raw_meta === '') {
            return;
        }

        $schema = $this->build_schema($raw_meta);

        if ($schema === null) {
            return;
        }

        $json = wp_json_encode($schema, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        $json = $this->escape_script_tags($json);

        echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
    }

    /**
     * Build the FAQPage schema array from raw FAQ meta data.
     *
     * @param string $raw_meta The raw meta value from get_post_meta.
     * @return array|null The FAQPage schema array, or null if no valid items.
     */
    private function build_schema(string $raw_meta): ?array
    {
        $items = $this->parse_faq_items($raw_meta);

        if (empty($items)) {
            return null;
        }

        $items = array_slice($items, 0, self::MAX_ITEMS);

        $main_entity = array_map([$this, 'build_question_object'], $items);

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $main_entity,
        ];
    }

    /**
     * Decode and validate the raw meta string into FAQ items.
     *
     * @param string $raw_meta Raw JSON string from post meta.
     * @return array<int, array{question: string, answer: string}> Valid FAQ items.
     */
    private function parse_faq_items(string $raw_meta): array
    {
        $decoded = json_decode($raw_meta, true);

        if (!is_array($decoded)) {
            return [];
        }

        $valid_items = [];

        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!isset($item['question']) || !is_string($item['question'])) {
                continue;
            }

            if (!isset($item['answer']) || !is_string($item['answer'])) {
                continue;
            }

            if (trim($item['question']) === '' || trim($item['answer']) === '') {
                continue;
            }

            $valid_items[] = $item;
        }

        return $valid_items;
    }

    /**
     * Transform a single FAQ item into a schema.org Question object.
     *
     * @param array{question: string, answer: string} $item Validated FAQ item.
     * @return array The Question schema object.
     */
    private function build_question_object(array $item): array
    {
        return [
            '@type'          => 'Question',
            'name'           => $this->prepare_question_text($item['question']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $this->prepare_answer_text($item['answer']),
            ],
        ];
    }

    /**
     * Prepare question text: decode HTML entities, strip HTML tags.
     *
     * @param string $text Raw question text.
     * @return string Cleaned question text.
     */
    private function prepare_question_text(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);

        return $text;
    }

    /**
     * Prepare answer text: decode HTML entities, preserve HTML markup.
     *
     * @param string $text Raw answer text.
     * @return string Processed answer text.
     */
    private function prepare_answer_text(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }

    /**
     * Escape closing script tags in JSON output to prevent XSS.
     *
     * @param string $json Encoded JSON string.
     * @return string Safe JSON string for embedding in script tag.
     */
    private function escape_script_tags(string $json): string
    {
        return preg_replace('/<\/script/i', '<\\/script', $json);
    }
}
