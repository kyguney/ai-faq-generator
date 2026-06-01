<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Services;

/**
 * FAQ Parser service.
 *
 * Parses raw AI response content into validated FAQ item arrays.
 * Handles markdown fence stripping, JSON decoding, item validation,
 * whitespace trimming, and re-indexing. Never throws exceptions —
 * returns an empty array on any failure.
 *
 * @package WPBits\AiFaqGenerator\Includes\Services
 */
class Faq_Parser
{
    /**
     * Parse raw AI response content into FAQ items.
     *
     * @param string $content Raw content from AI response (choices[0].message.content).
     * @return array<int, array{question: string, answer: string}> Validated FAQ items.
     */
    public function parse(string $content): array
    {
        try {
            $content = $this->strip_markdown_fences($content);
            $items = $this->decode_json($content);

            if ($items === null) {
                return [];
            }

            $result = [];

            foreach ($items as $item) {
                $validated = $this->validate_item($item);

                if ($validated === null) {
                    continue;
                }

                $result[] = $this->trim_values($validated);
            }

            return array_values($result);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Strip markdown code fences from content if present.
     *
     * @param string $content Raw content that may be wrapped in markdown fences.
     * @return string Content with fences removed, or original content if no fences detected.
     */
    private function strip_markdown_fences(string $content): string
    {
        try {
            $pattern = '/^\s*```(?:\w*)\s*\n([\s\S]*?)\n\s*```\s*$/';
            $result = preg_match($pattern, $content, $matches);

            if ($result === 1) {
                return $matches[1];
            }

            return $content;
        } catch (\Throwable $e) {
            return $content;
        }
    }

    /**
     * Decode JSON string into an indexed array.
     *
     * @param string $content JSON string to decode.
     * @return array<int, mixed>|null Decoded array or null if invalid.
     */
    private function decode_json(string $content): ?array
    {
        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !array_is_list($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Validate a single decoded item has required question and answer keys.
     *
     * @param mixed $item A single decoded array item.
     * @return array{question: string, answer: string}|null Validated item or null if invalid.
     */
    private function validate_item(mixed $item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        if (!array_key_exists('question', $item) || !array_key_exists('answer', $item)) {
            return null;
        }

        if (!is_string($item['question']) || !is_string($item['answer'])) {
            return null;
        }

        if (trim($item['question']) === '' || trim($item['answer']) === '') {
            return null;
        }

        return $item;
    }

    /**
     * Trim leading and trailing whitespace from question and answer values.
     *
     * @param array{question: string, answer: string} $item Validated FAQ item.
     * @return array{question: string, answer: string} Item with trimmed values.
     */
    private function trim_values(array $item): array
    {
        return [
            'question' => trim($item['question']),
            'answer' => trim($item['answer']),
        ];
    }
}
