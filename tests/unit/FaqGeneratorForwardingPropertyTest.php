<?php
/**
 * Property-based tests for Faq_Generator data forwarding.
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

class FaqGeneratorForwardingPropertyTest extends TestCase
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
     * Generate a random string of given length using various character sets.
     */
    private static function randomString(int $minLength, int $maxLength): string
    {
        $length = mt_rand($minLength, $maxLength);
        $chars = '';

        // Mix of ASCII printable, special chars, and unicode
        $charSets = [
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            ' !@#$%^&*()_+-=[]{}|;:\'",.<>?/\\~`',
            "\t\n\r",
        ];

        // Unicode characters
        $unicodeChars = ['é', 'ñ', 'ü', 'ö', 'ä', 'ß', '中', '文', '日', '本', '語', 'العربية', '한국어', 'ελληνικά', 'кириллица', '🎉', '🚀', '💡', '✅', '❌'];

        for ($i = 0; $i < $length; $i++) {
            $setChoice = mt_rand(0, 3);
            if ($setChoice === 3) {
                // Pick a unicode character
                $chars .= $unicodeChars[mt_rand(0, count($unicodeChars) - 1)];
            } else {
                $set = $charSets[$setChoice];
                $chars .= $set[mt_rand(0, strlen($set) - 1)];
            }
        }

        return $chars;
    }

    // ─── Property 4: Post data forwarding to Prompt_Builder ─────────────────
    // Feature: generate-faq-service, Property 4: Post data forwarding to Prompt_Builder
    // Validates: Requirements 1.2, 2.1

    /**
     * Data provider generating 100+ published posts with random title and content strings.
     *
     * @return array<string, array{string, string}>
     */
    public static function postDataForwardingProvider(): array
    {
        $cases = [];

        // Edge cases
        $cases['empty_title_empty_content'] = ['', ''];
        $cases['empty_title_with_content'] = ['', 'Some content here'];
        $cases['with_title_empty_content'] = ['A Title', ''];

        // Random title/content pairs
        for ($i = 0; $i < 105; $i++) {
            $title = self::randomString(0, 100);
            $content = self::randomString(0, 500);
            $cases["random_post_iter_{$i}"] = [$title, $content];
        }

        return $cases;
    }

    /**
     * Property 4: Post data forwarding to Prompt_Builder
     *
     * For any published post with arbitrary title and content strings,
     * the Faq_Generator SHALL pass the exact post_title as the first argument
     * and the exact post_content as the second argument to Prompt_Builder::build().
     *
     * **Validates: Requirements 1.2, 2.1**
     */
    #[Test]
    #[DataProvider('postDataForwardingProvider')]
    public function post_data_is_forwarded_to_prompt_builder(string $title, string $content): void
    {
        global $afg_test_posts;

        // Set up a published post with the random title and content
        $post = new \WP_Post();
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Assert Prompt_Builder::build() receives exact post_title and post_content
        $this->prompt_builder
            ->expects($this->once())
            ->method('build')
            ->with(
                $this->identicalTo($title),
                $this->identicalTo($content),
                $this->anything()
            )
            ->willReturn('generated prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q', 'answer' => 'A']]);

        $this->generator->generateFaqs(1);
    }

    // ─── Property 5: Settings faq_count forwarding ───────────────────────────
    // Feature: generate-faq-service, Property 5: Settings faq_count forwarding
    // Validates: Requirements 2.2

    /**
     * Data provider generating 100+ non-null faq_count values.
     *
     * @return array<string, array{mixed, int}>
     */
    public static function faqCountForwardingProvider(): array
    {
        $cases = [];

        // Edge cases
        $cases['faq_count_1'] = [1, 1];
        $cases['faq_count_20'] = [20, 20];
        $cases['faq_count_string_5'] = ['5', 5];
        $cases['faq_count_string_10'] = ['10', 10];
        $cases['faq_count_float_3'] = [3.7, 3];
        $cases['faq_count_zero'] = [0, 0];
        $cases['faq_count_negative'] = [-1, -1];
        $cases['faq_count_large'] = [1000, 1000];

        // Random positive integers
        for ($i = 0; $i < 50; $i++) {
            $value = mt_rand(1, 100);
            $cases["random_int_{$i}"] = [$value, $value];
        }

        // Random string representations of integers
        for ($i = 0; $i < 25; $i++) {
            $value = (string) mt_rand(1, 50);
            $cases["random_string_int_{$i}"] = [$value, (int) $value];
        }

        // Random negative integers
        for ($i = 0; $i < 25; $i++) {
            $value = mt_rand(-100, -1);
            $cases["random_negative_{$i}"] = [$value, $value];
        }

        return $cases;
    }

    /**
     * Property 5: Settings faq_count forwarding
     *
     * For any non-null faq_count value stored in the afg_settings option,
     * the Faq_Generator SHALL cast it to an integer and pass that integer
     * as the third argument to Prompt_Builder::build().
     *
     * **Validates: Requirements 2.2**
     */
    #[Test]
    #[DataProvider('faqCountForwardingProvider')]
    public function faq_count_is_cast_to_int_and_forwarded(mixed $rawValue, int $expectedInt): void
    {
        global $afg_test_posts, $afg_test_options;

        // Set up a published post
        $post = new \WP_Post();
        $post->post_title = 'Test Title';
        $post->post_content = 'Test content';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Set up settings with the faq_count value
        $afg_test_options['afg_settings'] = ['faq_count' => $rawValue];

        // Assert Prompt_Builder::build() receives the integer-cast value as third argument
        $this->prompt_builder
            ->expects($this->once())
            ->method('build')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->identicalTo($expectedInt)
            )
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q', 'answer' => 'A']]);

        $this->generator->generateFaqs(1);
    }

    // ─── Property 6: Prompt forwarding to AI provider ────────────────────────
    // Feature: generate-faq-service, Property 6: Prompt forwarding to AI provider
    // Validates: Requirements 3.1

    /**
     * Data provider generating 100+ random prompt strings returned by Prompt_Builder.
     *
     * Generates varying lengths, special characters, unicode, and multiline strings.
     *
     * @return array<string, array{string}>
     */
    public static function promptForwardingProvider(): array
    {
        $cases = [];

        // Short prompts (1-10 chars)
        for ($i = 0; $i < 20; $i++) {
            $prompt = self::randomString(1, 10);
            $cases["short_prompt_iter_{$i}"] = [$prompt];
        }

        // Medium prompts (50-200 chars)
        for ($i = 0; $i < 25; $i++) {
            $prompt = self::randomString(50, 200);
            $cases["medium_prompt_iter_{$i}"] = [$prompt];
        }

        // Long prompts (500-2000 chars)
        for ($i = 0; $i < 20; $i++) {
            $prompt = self::randomString(500, 2000);
            $cases["long_prompt_iter_{$i}"] = [$prompt];
        }

        // Prompts with special characters only
        $specialChars = '!@#$%^&*()_+-=[]{}|;:\'",.<>?/\\~`';
        for ($i = 0; $i < 10; $i++) {
            $length = mt_rand(10, 100);
            $prompt = '';
            for ($j = 0; $j < $length; $j++) {
                $prompt .= $specialChars[mt_rand(0, strlen($specialChars) - 1)];
            }
            $cases["special_chars_iter_{$i}"] = [$prompt];
        }

        // Prompts with unicode characters
        $unicodeChars = ['é', 'ñ', 'ü', 'ö', 'ä', 'ß', '中', '文', '日', '本', '語', '한국어', '🎉', '🚀', '💡'];
        for ($i = 0; $i < 10; $i++) {
            $length = mt_rand(10, 50);
            $prompt = '';
            for ($j = 0; $j < $length; $j++) {
                $prompt .= $unicodeChars[mt_rand(0, count($unicodeChars) - 1)];
            }
            $cases["unicode_prompt_iter_{$i}"] = [$prompt];
        }

        // Multiline prompts
        for ($i = 0; $i < 10; $i++) {
            $lines = mt_rand(2, 10);
            $prompt = '';
            for ($l = 0; $l < $lines; $l++) {
                $prompt .= self::randomString(10, 80) . "\n";
            }
            $cases["multiline_prompt_iter_{$i}"] = [$prompt];
        }

        // Prompts with leading/trailing whitespace
        for ($i = 0; $i < 10; $i++) {
            $whitespace = [' ', "\t", "\n", "\r", "  ", "\t\t"];
            $leading = $whitespace[mt_rand(0, count($whitespace) - 1)];
            $trailing = $whitespace[mt_rand(0, count($whitespace) - 1)];
            $prompt = $leading . self::randomString(20, 100) . $trailing;
            $cases["whitespace_padded_iter_{$i}"] = [$prompt];
        }

        // Empty-ish prompts (single char, single space, tab)
        $cases['single_char'] = ['a'];
        $cases['single_space'] = [' '];
        $cases['single_tab'] = ["\t"];
        $cases['single_newline'] = ["\n"];
        $cases['empty_string'] = [''];

        return $cases;
    }

    /**
     * Property 6: Prompt forwarding to AI provider
     *
     * For any prompt string returned by Prompt_Builder::build(),
     * the Faq_Generator SHALL pass that exact string to
     * AIProviderInterface::generateFaqs() without modification.
     *
     * **Validates: Requirements 3.1**
     */
    #[Test]
    #[DataProvider('promptForwardingProvider')]
    public function prompt_is_forwarded_to_ai_provider_without_modification(string $prompt): void
    {
        global $afg_test_posts;

        // Set up a published post
        $post = new \WP_Post();
        $post->post_title = 'Test Post Title';
        $post->post_content = 'Test post content for FAQ generation.';
        $post->post_status = 'publish';
        $afg_test_posts[1] = $post;

        // Configure Prompt_Builder to return the random prompt string
        $this->prompt_builder
            ->method('build')
            ->willReturn($prompt);

        // Assert AIProviderInterface::generateFaqs() receives the EXACT prompt string
        $this->ai_provider
            ->expects($this->once())
            ->method('generateFaqs')
            ->with($this->identicalTo($prompt))
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $this->generator->generateFaqs(1);
    }
}
