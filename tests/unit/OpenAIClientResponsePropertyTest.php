<?php
/**
 * Property-based test for OpenAIClient response parsing correctness.
 *
 * Feature: openai-compatible-client, Property 2: Response parsing correctness
 *
 * Property 2: Response parsing correctness
 * Validates: Requirements 2.1, 2.2, 2.3
 *
 * For any valid API response containing a JSON-encoded array of FAQ items at
 * choices[0].message.content, the generateFaqs method SHALL return a numerically
 * indexed array where every element is an associative array with both a `question`
 * key (non-empty string) and an `answer` key (non-empty string), preserving the
 * content from the API response.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\OpenAIClient;

class OpenAIClientResponsePropertyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_options = [];
        $afg_test_wp_remote_post_args = null;
        $afg_test_wp_remote_post_return = [];
    }

    protected function tearDown(): void
    {
        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;
        $afg_test_options = [];
        $afg_test_wp_remote_post_args = null;
        $afg_test_wp_remote_post_return = [];

        parent::tearDown();
    }

    /**
     * **Validates: Requirements 2.1, 2.2, 2.3**
     *
     * Property 2: Response parsing correctness.
     * For any valid API response containing FAQ items, generateFaqs SHALL return
     * a numerically indexed array preserving the content from the API response.
     */
    #[Test]
    #[DataProvider('faqResponseProvider')]
    public function response_parsing_returns_correct_faq_structure(array $inputFaqs): void
    {
        global $afg_test_options, $afg_test_wp_remote_post_return;

        // Configure the client with valid settings.
        $afg_test_options['afg_settings'] = [
            'api_key'     => 'sk-test-key-12345',
            'model'       => 'gpt-4o',
            'temperature' => 0.7,
            'base_url'    => 'https://api.openai.com',
        ];

        // Mock wp_remote_post to return a valid API response wrapping the FAQ array.
        $afg_test_wp_remote_post_return = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'id' => 'chatcmpl-' . bin2hex(random_bytes(6)),
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'gpt-4o',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => json_encode($inputFaqs),
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 50,
                    'completion_tokens' => 200,
                    'total_tokens' => 250,
                ],
            ]),
        ];

        $client = new OpenAIClient();
        $result = $client->generateFaqs('test prompt');

        // Verify the result is a numerically indexed array.
        $this->assertIsArray($result, 'Result must be an array');
        $this->assertCount(
            count($inputFaqs),
            $result,
            'Result must contain the same number of FAQ items as input'
        );

        // Verify each element matches the input FAQ structure exactly.
        foreach ($result as $index => $item) {
            $this->assertIsInt($index, 'Array must be numerically indexed');
            $this->assertIsArray($item, "Item at index {$index} must be an array");

            // Verify question key exists and matches.
            $this->assertArrayHasKey('question', $item, "Item at index {$index} must have 'question' key");
            $this->assertIsString($item['question'], "Question at index {$index} must be a string");
            $this->assertNotEmpty(trim($item['question']), "Question at index {$index} must be non-empty");
            $this->assertSame(
                $inputFaqs[$index]['question'],
                $item['question'],
                "Question at index {$index} must match input"
            );

            // Verify answer key exists and matches.
            $this->assertArrayHasKey('answer', $item, "Item at index {$index} must have 'answer' key");
            $this->assertIsString($item['answer'], "Answer at index {$index} must be a string");
            $this->assertNotEmpty(trim($item['answer']), "Answer at index {$index} must be non-empty");
            $this->assertSame(
                $inputFaqs[$index]['answer'],
                $item['answer'],
                "Answer at index {$index} must match input"
            );
        }
    }

    /**
     * Data provider generating 110 random FAQ arrays with varying count (1–20).
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function faqResponseProvider(): array
    {
        $cases = [];

        mt_srand(67890);

        for ($i = 0; $i < 110; $i++) {
            $faqCount = mt_rand(1, 20);
            $faqs = [];

            for ($j = 0; $j < $faqCount; $j++) {
                $faqs[] = [
                    'question' => self::generateRandomQuestion($i, $j),
                    'answer'   => self::generateRandomAnswer($i, $j),
                ];
            }

            $cases["faq_set_{$i}"] = [$faqs];
        }

        return $cases;
    }

    /**
     * Generate a random question string.
     */
    private static function generateRandomQuestion(int $setIndex, int $itemIndex): string
    {
        $subjects = [
            'WordPress', 'PHP', 'MySQL', 'JavaScript', 'REST API',
            'WooCommerce', 'Gutenberg', 'Docker', 'Nginx', 'Redis',
            'Laravel', 'Symfony', 'React', 'Vue.js', 'TypeScript',
        ];

        $verbs = [
            'configure', 'install', 'optimize', 'debug', 'deploy',
            'secure', 'update', 'migrate', 'scale', 'monitor',
            'integrate', 'customize', 'extend', 'test', 'refactor',
        ];

        $subject = $subjects[mt_rand(0, count($subjects) - 1)];
        $verb = $verbs[mt_rand(0, count($verbs) - 1)];

        $templates = [
            "How do I {$verb} {$subject}?",
            "What is the best way to {$verb} {$subject}?",
            "Can you explain how to {$verb} {$subject}?",
            "Why should I {$verb} {$subject}?",
            "When is it necessary to {$verb} {$subject}?",
            "What are the steps to {$verb} {$subject} properly?",
            "Is it possible to {$verb} {$subject} automatically?",
        ];

        return $templates[mt_rand(0, count($templates) - 1)];
    }

    /**
     * Generate a random answer string.
     */
    private static function generateRandomAnswer(int $setIndex, int $itemIndex): string
    {
        $intros = [
            'To accomplish this,',
            'The recommended approach is to',
            'You can achieve this by',
            'The best practice is to',
            'First, you need to',
            'According to the documentation,',
            'In most cases, you should',
        ];

        $actions = [
            'use the built-in configuration panel and adjust the settings accordingly.',
            'follow the official documentation step by step for best results.',
            'install the required dependencies and configure the environment variables.',
            'create a custom implementation that extends the base class.',
            'enable the feature flag in the settings and restart the service.',
            'run the migration script and verify the output matches expectations.',
            'update the configuration file with the correct parameters.',
            'implement proper error handling and logging for debugging.',
            'set up automated testing to catch regressions early.',
            'review the security best practices before deploying to production.',
        ];

        $intro = $intros[mt_rand(0, count($intros) - 1)];
        $action = $actions[mt_rand(0, count($actions) - 1)];

        return "{$intro} {$action}";
    }
}
