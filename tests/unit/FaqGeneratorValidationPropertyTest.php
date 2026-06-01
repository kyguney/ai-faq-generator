<?php
/**
 * Property-based tests for Faq_Generator input validation.
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

class FaqGeneratorValidationPropertyTest extends TestCase
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

    // ─── Property 1: Invalid post ID rejection ─────────────────────────────────
    // Feature: generate-faq-service, Property 1: Invalid post ID rejection
    // Validates: Requirements 1.4

    /**
     * Data provider generating 100+ integers ≤ 0.
     *
     * @return array<string, array{int}>
     */
    public static function invalidPostIdProvider(): array
    {
        $cases = [];
        $cases['zero'] = [0];
        $cases['negative_one'] = [-1];
        $cases['php_int_min'] = [PHP_INT_MIN];

        for ($i = 0; $i < 100; $i++) {
            $value = mt_rand(PHP_INT_MIN, -1);
            $cases["random_negative_{$i}"] = [$value];
        }

        return $cases;
    }

    /**
     * Property 1: Invalid post ID rejection
     *
     * For any integer less than or equal to zero, calling generateFaqs()
     * SHALL throw an InvalidArgumentException without calling get_post()
     * or any other dependency.
     *
     * **Validates: Requirements 1.4**
     */
    #[Test]
    #[DataProvider('invalidPostIdProvider')]
    public function invalid_post_id_throws_invalid_argument_exception(int $postId): void
    {
        // Dependencies should NEVER be called
        $this->prompt_builder
            ->expects($this->never())
            ->method('build');

        $this->ai_provider
            ->expects($this->never())
            ->method('generateFaqs');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid post ID: {$postId}");

        $this->generator->generateFaqs($postId);
    }

    // ─── Property 2: Non-existent post throws with ID in message ─────────────
    // Feature: generate-faq-service, Property 2: Non-existent post throws with ID in message
    // Validates: Requirements 1.3, 5.2

    /**
     * Data provider generating 100+ positive integers where get_post() returns null.
     *
     * @return array<string, array{int}>
     */
    public static function nonExistentPostIdProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 110; $i++) {
            $postId = mt_rand(1, 999999);
            $cases["non_existent_post_{$postId}_iter_{$i}"] = [$postId];
        }

        return $cases;
    }

    /**
     * Property 2: Non-existent post throws with ID in message
     *
     * For any positive integer post ID where get_post() returns null,
     * calling generateFaqs() SHALL throw a RuntimeException whose message
     * contains the string representation of that post ID.
     *
     * **Validates: Requirements 1.3, 5.2**
     */
    #[Test]
    #[DataProvider('nonExistentPostIdProvider')]
    public function non_existent_post_throws_runtime_exception_with_id(int $postId): void
    {
        // Do NOT add the post to $afg_test_posts — get_post() will return null

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage((string) $postId);

        $this->generator->generateFaqs($postId);
    }

    // ─── Property 3: Non-published post rejection ────────────────────────────
    // Feature: generate-faq-service, Property 3: Non-published post rejection
    // Validates: Requirements 1.5

    /**
     * Data provider generating 100+ posts with random non-"publish" statuses.
     *
     * @return array<string, array{int, string}>
     */
    public static function nonPublishedPostStatusProvider(): array
    {
        $knownStatuses = ['draft', 'pending', 'trash', 'private', 'future'];
        $cases = [];

        // Generate cases for each known non-publish status (5 statuses × 10 = 50 cases)
        foreach ($knownStatuses as $status) {
            for ($i = 0; $i < 10; $i++) {
                $postId = mt_rand(1, 10000);
                $cases["known_{$status}_post_{$postId}_iter_{$i}"] = [$postId, $status];
            }
        }

        // Generate 60 cases with random custom status strings
        $chars = 'abcdefghijklmnopqrstuvwxyz_-';
        for ($i = 0; $i < 60; $i++) {
            $postId = mt_rand(1, 10000);
            $length = mt_rand(3, 15);
            $customStatus = '';
            for ($j = 0; $j < $length; $j++) {
                $customStatus .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            // Ensure the custom status is never "publish"
            if ($customStatus === 'publish') {
                $customStatus = 'custom_status';
            }
            $cases["custom_status_{$customStatus}_post_{$postId}_iter_{$i}"] = [$postId, $customStatus];
        }

        return $cases;
    }

    /**
     * Property 3: Non-published post rejection
     *
     * For any post with a post_status value other than "publish",
     * calling generateFaqs() SHALL throw a RuntimeException without
     * invoking the Prompt_Builder or AI provider.
     *
     * **Validates: Requirements 1.5**
     */
    #[Test]
    #[DataProvider('nonPublishedPostStatusProvider')]
    public function non_published_post_throws_runtime_exception(int $postId, string $status): void
    {
        global $afg_test_posts;

        // Set up a post with the non-publish status
        $post = new \WP_Post();
        $post->post_title = 'Test Title';
        $post->post_content = 'Test content';
        $post->post_status = $status;
        $afg_test_posts[$postId] = $post;

        // Prompt_Builder and AI provider should NEVER be called
        $this->prompt_builder
            ->expects($this->never())
            ->method('build');

        $this->ai_provider
            ->expects($this->never())
            ->method('generateFaqs');

        // Assert RuntimeException is thrown
        $this->expectException(\RuntimeException::class);

        $this->generator->generateFaqs($postId);
    }
}
