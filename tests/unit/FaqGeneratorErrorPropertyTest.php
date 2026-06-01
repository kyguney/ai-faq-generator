<?php
/**
 * Property-based tests for Faq_Generator error handling.
 *
 * Uses PHPUnit data providers with 100+ random iterations per property
 * to simulate property-based testing.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Generator;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class FaqGeneratorErrorPropertyTest extends TestCase
{
    private Faq_Generator $generator;
    private AIProviderInterface $ai_provider;
    private Prompt_Builder $prompt_builder;

    protected function setUp(): void
    {
        global $afg_test_posts, $afg_test_options;
        $afg_test_posts = [];
        $afg_test_options = [];

        $this->ai_provider = $this->createMock(AIProviderInterface::class);
        $this->prompt_builder = $this->createMock(Prompt_Builder::class);
        $this->generator = new Faq_Generator($this->ai_provider, $this->prompt_builder);
    }

    protected function tearDown(): void
    {
        global $afg_test_posts, $afg_test_options;
        $afg_test_posts = [];
        $afg_test_options = [];
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    /**
     * Generate a random string of given length.
     */
    private static function randomString(int $minLength, int $maxLength): string
    {
        $length = mt_rand($minLength, $maxLength);
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 !@#$%^&*()_+-=[]{}|;:,.<>?/~`';

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $result;
    }

    // ─── Property 7: AI provider exception propagation ───────────────────────
    // Feature: generate-faq-service, Property 7: AI provider exception propagation
    // Validates: Requirements 3.4, 5.1

    /**
     * Data provider generating 100+ RuntimeExceptions with random messages.
     *
     * @return array<string, array{\RuntimeException}>
     */
    public static function aiProviderExceptionProvider(): array
    {
        $cases = [];

        // Edge cases
        $cases['empty_message'] = [new \RuntimeException('')];
        $cases['single_char_message'] = [new \RuntimeException('E')];
        $cases['numeric_message'] = [new \RuntimeException('404')];
        $cases['whitespace_message'] = [new \RuntimeException('   ')];
        $cases['newline_message'] = [new \RuntimeException("line1\nline2")];
        $cases['tab_message'] = [new \RuntimeException("error\there")];

        // Common error messages
        $commonMessages = [
            'API rate limit exceeded',
            'Invalid API key',
            'Service unavailable',
            'Connection timeout',
            'Internal server error',
            'Model not found',
            'Request too large',
            'Insufficient quota',
            'Network error: connection refused',
            'HTTP 500: Internal Server Error',
        ];

        foreach ($commonMessages as $i => $msg) {
            $cases["common_error_{$i}"] = [new \RuntimeException($msg)];
        }

        // Random messages
        for ($i = 0; $i < 90; $i++) {
            $message = self::randomString(1, 200);
            $cases["random_exception_iter_{$i}"] = [new \RuntimeException($message)];
        }

        return $cases;
    }

    /**
     * Property 7: AI provider exception propagation
     *
     * For any RuntimeException thrown by AIProviderInterface::generateFaqs(),
     * the Faq_Generator SHALL propagate that exception to the caller with
     * the original message string unchanged.
     *
     * **Validates: Requirements 3.4, 5.1**
     */
    #[Test]
    #[DataProvider('aiProviderExceptionProvider')]
    public function ai_provider_exception_propagates_with_original_message(\RuntimeException $exception): void
    {
        global $afg_test_posts;

        // Set up a published post so we reach the AI provider call
        $post = new \WP_Post();
        $post->post_title = 'Test Title';
        $post->post_content = 'Test content';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Configure Prompt_Builder to return a prompt string
        $this->prompt_builder
            ->method('build')
            ->willReturn('generated prompt');

        // Configure AI provider to throw the RuntimeException
        $this->ai_provider
            ->method('generateFaqs')
            ->willThrowException($exception);

        // Assert the same exception propagates with the original message
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $this->generator->generateFaqs(1);
    }
}
