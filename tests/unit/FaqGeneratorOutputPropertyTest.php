<?php
/**
 * Property-based tests for Faq_Generator output correctness.
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

class FaqGeneratorOutputPropertyTest extends TestCase
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

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Generate a random non-empty, non-whitespace-only string.
     */
    private static function randomNonEmptyString(int $minLength = 1, int $maxLength = 100): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?/~`';

        // Ensure at least one non-whitespace character
        $length = mt_rand($minLength, $maxLength);
        $result = $chars[mt_rand(0, strlen($chars) - 1)]; // Start with a non-whitespace char

        for ($i = 1; $i < $length; $i++) {
            $choice = mt_rand(0, 3);
            if ($choice === 0) {
                $result .= ' '; // Add some spaces for variety
            } elseif ($choice === 1) {
                $result .= $specialChars[mt_rand(0, strlen($specialChars) - 1)];
            } else {
                $result .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
        }

        return $result;
    }

    /**
     * Generate a random whitespace-only or empty string.
     */
    private static function randomWhitespaceString(): string
    {
        $whitespaceChars = [' ', "\t", "\n", "\r", "  ", "\t\t", "   ", " \t ", "\n\r"];
        $choice = mt_rand(0, 10);

        if ($choice === 0) {
            return ''; // Empty string
        }

        // Build a whitespace-only string
        $length = mt_rand(1, 5);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $whitespaceChars[mt_rand(0, count($whitespaceChars) - 1)];
        }

        return $result;
    }

    /**
     * Generate a valid FAQ item with non-empty question and answer.
     *
     * @return array{question: string, answer: string}
     */
    private static function randomValidFaqItem(): array
    {
        return [
            'question' => self::randomNonEmptyString(3, 80),
            'answer' => self::randomNonEmptyString(5, 200),
        ];
    }

    /**
     * Generate an invalid FAQ item (empty/whitespace question or answer).
     *
     * @return array{question: string, answer: string}
     */
    private static function randomInvalidFaqItem(): array
    {
        $choice = mt_rand(0, 2);

        if ($choice === 0) {
            // Empty/whitespace question, valid answer
            return [
                'question' => self::randomWhitespaceString(),
                'answer' => self::randomNonEmptyString(5, 100),
            ];
        } elseif ($choice === 1) {
            // Valid question, empty/whitespace answer
            return [
                'question' => self::randomNonEmptyString(3, 80),
                'answer' => self::randomWhitespaceString(),
            ];
        } else {
            // Both empty/whitespace
            return [
                'question' => self::randomWhitespaceString(),
                'answer' => self::randomWhitespaceString(),
            ];
        }
    }

    // ─── Property 8: Valid FAQ passthrough ───────────────────────────────────
    // Feature: generate-faq-service, Property 8: Valid FAQ passthrough
    // Validates: Requirements 4.1, 4.2

    /**
     * Data provider generating 100+ FAQ arrays where all items have
     * non-empty, non-whitespace question and answer.
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>}>
     */
    public static function validFaqArrayProvider(): array
    {
        $cases = [];

        // Single item arrays
        for ($i = 0; $i < 20; $i++) {
            $faqs = [self::randomValidFaqItem()];
            $cases["single_item_iter_{$i}"] = [$faqs];
        }

        // Two item arrays
        for ($i = 0; $i < 20; $i++) {
            $faqs = [
                self::randomValidFaqItem(),
                self::randomValidFaqItem(),
            ];
            $cases["two_items_iter_{$i}"] = [$faqs];
        }

        // Three to five item arrays
        for ($i = 0; $i < 30; $i++) {
            $count = mt_rand(3, 5);
            $faqs = [];
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = self::randomValidFaqItem();
            }
            $cases["multi_items_iter_{$i}"] = [$faqs];
        }

        // Larger arrays (6-15 items)
        for ($i = 0; $i < 20; $i++) {
            $count = mt_rand(6, 15);
            $faqs = [];
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = self::randomValidFaqItem();
            }
            $cases["large_array_iter_{$i}"] = [$faqs];
        }

        // Edge case: very large array (20 items)
        $largeFaqs = [];
        for ($j = 0; $j < 20; $j++) {
            $largeFaqs[] = self::randomValidFaqItem();
        }
        $cases['twenty_items'] = [$largeFaqs];

        // Arrays with items containing special characters
        for ($i = 0; $i < 15; $i++) {
            $faqs = [];
            $count = mt_rand(1, 5);
            for ($j = 0; $j < $count; $j++) {
                $faqs[] = [
                    'question' => self::randomNonEmptyString(10, 100),
                    'answer' => self::randomNonEmptyString(10, 200),
                ];
            }
            $cases["special_chars_iter_{$i}"] = [$faqs];
        }

        return $cases;
    }

    /**
     * Property 8: Valid FAQ passthrough
     *
     * For any FAQ array returned by the AI provider where all items have
     * non-empty, non-whitespace-only question and answer values, the
     * Faq_Generator SHALL return that array unchanged.
     *
     * **Validates: Requirements 4.1, 4.2**
     */
    #[Test]
    #[DataProvider('validFaqArrayProvider')]
    public function valid_faq_array_is_returned_unchanged(array $faqs): void
    {
        global $afg_test_posts;

        // Set up a published post
        $post = new \WP_Post();
        $post->post_title = 'Test Title';
        $post->post_content = 'Test content';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Configure Prompt_Builder to return a prompt
        $this->prompt_builder
            ->method('build')
            ->willReturn('generated prompt');

        // Configure AI provider to return the valid FAQ array
        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn($faqs);

        $result = $this->generator->generateFaqs(1);

        $this->assertSame($faqs, $result);
    }

    // ─── Property 9: Empty/whitespace item filtering ─────────────────────────
    // Feature: generate-faq-service, Property 9: Empty/whitespace item filtering
    // Validates: Requirements 4.4

    /**
     * Data provider generating 100+ FAQ arrays containing a mix of valid
     * and invalid items (empty/whitespace question or answer).
     *
     * @return array<string, array{array<int, array{question: string, answer: string}>, array<int, array{question: string, answer: string}>}>
     */
    public static function mixedFaqArrayProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 110; $i++) {
            $validCount = mt_rand(1, 5);
            $invalidCount = mt_rand(1, 4);
            $totalCount = $validCount + $invalidCount;

            // Generate valid and invalid items
            $validItems = [];
            for ($j = 0; $j < $validCount; $j++) {
                $validItems[] = self::randomValidFaqItem();
            }

            $invalidItems = [];
            for ($j = 0; $j < $invalidCount; $j++) {
                $invalidItems[] = self::randomInvalidFaqItem();
            }

            // Interleave valid and invalid items in a random order
            $allItems = [];
            $validIdx = 0;
            $invalidIdx = 0;
            $expectedValid = [];

            // Create a shuffled order of 'v' (valid) and 'i' (invalid)
            $order = array_merge(
                array_fill(0, $validCount, 'v'),
                array_fill(0, $invalidCount, 'i')
            );
            shuffle($order);

            foreach ($order as $type) {
                if ($type === 'v') {
                    $allItems[] = $validItems[$validIdx];
                    $expectedValid[] = $validItems[$validIdx];
                    $validIdx++;
                } else {
                    $allItems[] = $invalidItems[$invalidIdx];
                    $invalidIdx++;
                }
            }

            $cases["mixed_iter_{$i}"] = [$allItems, $expectedValid];
        }

        return $cases;
    }

    /**
     * Property 9: Empty/whitespace item filtering
     *
     * For any FAQ array containing items where the question or answer is
     * empty or consists only of whitespace characters, the Faq_Generator
     * SHALL exclude those items from the returned array while preserving
     * all valid items in their original order.
     *
     * **Validates: Requirements 4.4**
     */
    #[Test]
    #[DataProvider('mixedFaqArrayProvider')]
    public function invalid_items_are_filtered_and_valid_items_preserved(array $inputFaqs, array $expectedOutput): void
    {
        global $afg_test_posts;

        // Set up a published post
        $post = new \WP_Post();
        $post->post_title = 'Test Title';
        $post->post_content = 'Test content';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Configure Prompt_Builder to return a prompt
        $this->prompt_builder
            ->method('build')
            ->willReturn('generated prompt');

        // Configure AI provider to return the mixed FAQ array
        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn($inputFaqs);

        $result = $this->generator->generateFaqs(1);

        // Assert invalid items are excluded and valid items preserved in order
        $this->assertSame(array_values($expectedOutput), $result);
    }
}
