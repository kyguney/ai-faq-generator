<?php
/**
 * Property-based test for OpenAIClient request structure invariant.
 *
 * Feature: openai-compatible-client, Property 1: Request structure invariant
 *
 * Property 1: Request structure invariant
 * Validates: Requirements 1.2, 1.3, 1.4, 1.5
 *
 * For any valid configuration (any non-empty model string, any temperature in [0.0, 2.0],
 * any non-empty api_key), the request sent by generateFaqs SHALL always contain:
 * a JSON body with `model`, `messages` (single element with role="user" and content=prompt),
 * and `temperature` fields; headers with Authorization="Bearer {api_key}" and
 * Content-Type="application/json"; and a timeout of 30 seconds.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\OpenAIClient;

class OpenAIClientRequestPropertyTest extends TestCase
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
     * **Validates: Requirements 1.2, 1.3, 1.4, 1.5**
     *
     * Property 1: Request structure invariant.
     * For any valid configuration, the request sent by generateFaqs SHALL contain
     * the correct body fields, headers, and timeout.
     */
    #[Test]
    #[DataProvider('configProvider')]
    public function request_structure_is_correct_for_any_valid_configuration(
        string $model,
        float $temperature,
        string $apiKey,
        string $prompt
    ): void {
        global $afg_test_options, $afg_test_wp_remote_post_args, $afg_test_wp_remote_post_return;

        // Set up the mock return value (valid response to avoid exceptions).
        $afg_test_wp_remote_post_return = [
            'response' => ['code' => 200],
            'body' => json_encode([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                ['question' => 'Q', 'answer' => 'A'],
                            ]),
                        ],
                    ],
                ],
            ]),
        ];

        // Configure the client via the options stub.
        $afg_test_options['afg_settings'] = [
            'api_key'     => $apiKey,
            'model'       => $model,
            'temperature' => $temperature,
            'base_url'    => 'https://api.openai.com',
        ];

        // Instantiate the client and call generateFaqs.
        $client = new OpenAIClient();
        $client->generateFaqs($prompt);

        // Verify wp_remote_post was called.
        $this->assertNotNull(
            $afg_test_wp_remote_post_args,
            'wp_remote_post must be called'
        );

        $args = $afg_test_wp_remote_post_args['args'];

        // Verify timeout is 30.
        $this->assertSame(30, $args['timeout'], 'Timeout must be 30 seconds');

        // Verify headers.
        $this->assertArrayHasKey('headers', $args, 'Request must include headers');
        $headers = $args['headers'];
        $this->assertSame(
            'Bearer ' . $apiKey,
            $headers['Authorization'],
            'Authorization header must be "Bearer {api_key}"'
        );
        $this->assertSame(
            'application/json',
            $headers['Content-Type'],
            'Content-Type header must be "application/json"'
        );

        // Verify body is valid JSON with required fields.
        $this->assertArrayHasKey('body', $args, 'Request must include body');
        $body = json_decode($args['body'], true);
        $this->assertIsArray($body, 'Body must be valid JSON');

        // Verify model field.
        $this->assertArrayHasKey('model', $body, 'Body must contain "model" field');
        $this->assertSame($model, $body['model'], 'Model must match configured value');

        // Verify temperature field.
        $this->assertArrayHasKey('temperature', $body, 'Body must contain "temperature" field');
        $this->assertEqualsWithDelta($temperature, $body['temperature'], 0.001, 'Temperature must match configured value');

        // Verify messages field.
        $this->assertArrayHasKey('messages', $body, 'Body must contain "messages" field');
        $this->assertIsArray($body['messages'], 'Messages must be an array');
        $this->assertCount(1, $body['messages'], 'Messages must be a single-element array');

        $message = $body['messages'][0];
        $this->assertSame('user', $message['role'], 'Message role must be "user"');
        $this->assertSame($prompt, $message['content'], 'Message content must be the prompt');
    }

    /**
     * Data provider generating 100+ random configurations.
     *
     * Each entry contains a random model string, temperature in [0.0, 2.0],
     * api_key string, and prompt string.
     *
     * @return array<string, array{string, float, string, string}>
     */
    public static function configProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(12345);

        for ($i = 0; $i < 110; $i++) {
            $model = self::generateRandomModel($i);
            $temperature = self::generateRandomTemperature();
            $apiKey = self::generateRandomApiKey();
            $prompt = self::generateRandomPrompt($i);

            $cases["config_{$i}"] = [$model, $temperature, $apiKey, $prompt];
        }

        return $cases;
    }

    /**
     * Generate a random model string.
     */
    private static function generateRandomModel(int $index): string
    {
        $prefixes = ['gpt-4o', 'gpt-3.5-turbo', 'claude-3', 'llama-3', 'deepseek-chat', 'mistral', 'gemma'];
        $suffixes = ['', '-mini', '-latest', '-0125', '-instruct', '-v2'];

        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];

        return $prefix . $suffix;
    }

    /**
     * Generate a random temperature in [0.0, 2.0].
     */
    private static function generateRandomTemperature(): float
    {
        return round(mt_rand(0, 200) / 100.0, 2);
    }

    /**
     * Generate a random API key string.
     */
    private static function generateRandomApiKey(): string
    {
        $prefixes = ['sk-', 'key-', 'api-', 'tok-', ''];
        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = mt_rand(20, 60);

        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $prefix . $key;
    }

    /**
     * Generate a random prompt string.
     */
    private static function generateRandomPrompt(int $index): string
    {
        $topics = [
            'WordPress security',
            'PHP best practices',
            'REST API design',
            'database optimization',
            'plugin development',
            'theme customization',
            'WooCommerce setup',
            'SEO strategies',
            'caching mechanisms',
            'user authentication',
        ];

        $formats = [
            'Generate %d FAQs about %s in JSON format.',
            'Create a list of %d questions and answers about %s.',
            'Write %d FAQ items covering %s topics.',
            'Produce %d Q&A pairs related to %s.',
            'List %d frequently asked questions about %s.',
        ];

        $topic = $topics[mt_rand(0, count($topics) - 1)];
        $format = $formats[mt_rand(0, count($formats) - 1)];
        $count = mt_rand(1, 20);

        return sprintf($format, $count, $topic);
    }
}
