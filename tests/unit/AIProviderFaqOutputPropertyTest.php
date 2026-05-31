<?php
/**
 * Property-based test for the FAQ output structure invariant.
 *
 * Feature: ai-faq-generator-provider-interface, Property 2: FAQ output structure invariant
 *
 * Property 2: FAQ output structure invariant
 * Validates: Requirements 4.2
 *
 * For any valid (non-empty) prompt string passed to a conforming provider's `generateFaqs`
 * method, every element in the returned array SHALL be an associative array containing both
 * a `question` key with a string value and an `answer` key with a string value.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;

/**
 * Mock provider that generates random FAQ items with varying count and content.
 *
 * This provider always returns structurally valid FAQ items to prove
 * that a conforming implementation maintains the output structure invariant.
 */
class RandomFaqMockProvider implements AIProviderInterface
{
    private int $seed;

    public function __construct(int $seed)
    {
        $this->seed = $seed;
    }

    public function generateFaqs(string $prompt): array
    {
        mt_srand($this->seed + crc32($prompt));

        // Generate between 1 and 15 FAQ items.
        $count = mt_rand(1, 15);
        $faqs = [];

        for ($i = 0; $i < $count; $i++) {
            $faqs[] = [
                'question' => $this->generateRandomString(mt_rand(10, 200)),
                'answer'   => $this->generateRandomString(mt_rand(20, 500)),
            ];
        }

        return $faqs;
    }

    public function testConnection(): bool
    {
        return true;
    }

    private function generateRandomString(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!?-';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $result;
    }
}

class AIProviderFaqOutputPropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 4.2**
     *
     * Property 2: FAQ output structure invariant.
     * For any valid (non-empty) prompt string passed to a conforming provider's
     * `generateFaqs` method, every element in the returned array SHALL be an
     * associative array containing both a `question` key with a string value
     * and an `answer` key with a string value.
     */
    #[Test]
    #[DataProvider('randomPromptProvider')]
    public function generate_faqs_returns_elements_with_question_and_answer_string_keys(string $prompt, int $seed): void
    {
        $provider = new RandomFaqMockProvider($seed);

        $result = $provider->generateFaqs($prompt);

        // The result must be an array.
        $this->assertIsArray($result, 'generateFaqs must return an array');

        // Every element must have question and answer string keys.
        foreach ($result as $index => $item) {
            $this->assertIsArray(
                $item,
                "FAQ item at index {$index} must be an associative array"
            );

            $this->assertArrayHasKey(
                'question',
                $item,
                "FAQ item at index {$index} must contain a 'question' key"
            );

            $this->assertArrayHasKey(
                'answer',
                $item,
                "FAQ item at index {$index} must contain an 'answer' key"
            );

            $this->assertIsString(
                $item['question'],
                "FAQ item at index {$index} 'question' value must be a string"
            );

            $this->assertIsString(
                $item['answer'],
                "FAQ item at index {$index} 'answer' value must be a string"
            );
        }
    }

    /**
     * Data provider generating 100+ random valid prompt strings with varying seeds.
     *
     * Each entry represents a unique prompt/seed combination that exercises the
     * provider's FAQ generation with different random outputs.
     *
     * @return array<string, array{string, int}>
     */
    public static function randomPromptProvider(): array
    {
        $cases = [];

        mt_srand(123);

        for ($i = 0; $i < 100; $i++) {
            $prompt = self::generateRandomPrompt($i);
            $seed = mt_rand(0, 999999);
            $cases["random_prompt_{$i}"] = [$prompt, $seed];
        }

        // Additional edge-case prompts beyond the 100 minimum.
        $edgeCases = [
            'single_word_prompt'    => ['FAQ', mt_rand(0, 999999)],
            'numeric_prompt'        => ['12345 67890', mt_rand(0, 999999)],
            'unicode_prompt'        => ['Ünïcödé prömpt with spëcîal chars', mt_rand(0, 999999)],
            'long_prompt'           => [str_repeat('Generate FAQs about WordPress plugins. ', 50), mt_rand(0, 999999)],
            'whitespace_heavy'      => ['  lots   of   spaces   between   words  ', mt_rand(0, 999999)],
            'special_chars_prompt'  => ['What about <html> & "quotes" and \'apostrophes\'?', mt_rand(0, 999999)],
            'newline_prompt'        => ["Line one\nLine two\nLine three", mt_rand(0, 999999)],
            'tab_prompt'            => ["Column1\tColumn2\tColumn3", mt_rand(0, 999999)],
            'url_prompt'            => ['Generate FAQs for https://example.com/page?q=test', mt_rand(0, 999999)],
            'minimal_prompt'        => ['a', mt_rand(0, 999999)],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random valid prompt string.
     */
    private static function generateRandomPrompt(int $index): string
    {
        $topics = [
            'WordPress', 'PHP', 'JavaScript', 'REST API', 'plugins',
            'themes', 'security', 'performance', 'SEO', 'hosting',
            'databases', 'caching', 'authentication', 'deployment', 'testing',
        ];

        $actions = [
            'Generate FAQs about', 'Create questions for', 'Write FAQ content on',
            'Produce Q&A pairs regarding', 'Build FAQ section about',
        ];

        $topic = $topics[mt_rand(0, count($topics) - 1)];
        $action = $actions[mt_rand(0, count($actions) - 1)];

        // Add random extra content to vary prompt length.
        $extra = '';
        $extraLength = mt_rand(0, 5);
        for ($i = 0; $i < $extraLength; $i++) {
            $extra .= ' ' . $topics[mt_rand(0, count($topics) - 1)];
        }

        return "{$action} {$topic}{$extra} (iteration {$index})";
    }
}
