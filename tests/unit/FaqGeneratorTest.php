<?php
/**
 * Example-based unit tests for the Faq_Generator service.
 *
 * Validates: Requirements 2.3, 2.4, 4.3, 5.3, 5.4, 5.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Generator;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class FaqGeneratorTest extends TestCase
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

    private function createPublishedPost(int $id, string $title = 'Test Title', string $content = 'Test content'): void
    {
        global $afg_test_posts;
        $post = new \WP_Post();
        $post->post_title = $title;
        $post->post_content = $content;
        $post->post_status = 'publish';
        $afg_test_posts[$id] = $post;
    }

    // ─── Constructor accepts dependencies ────────────────────────────────────

    /**
     * Validates: Requirement 6.1, 6.2, 6.3
     * The constructor accepts AIProviderInterface and Prompt_Builder mocks.
     */
    #[Test]
    public function constructor_accepts_ai_provider_and_prompt_builder_mocks(): void
    {
        $ai_provider = $this->createMock(AIProviderInterface::class);
        $prompt_builder = $this->createMock(Prompt_Builder::class);

        $generator = new Faq_Generator($ai_provider, $prompt_builder);

        $this->assertInstanceOf(Faq_Generator::class, $generator);
    }

    // ─── Requirement 2.3: null faq_count passes null to Prompt_Builder ───────

    /**
     * Validates: Requirement 2.3
     * When faq_count is null in settings, null is passed to Prompt_Builder.
     */
    #[Test]
    public function null_faq_count_in_settings_passes_null_to_prompt_builder(): void
    {
        global $afg_test_options;
        $this->createPublishedPost(1);
        $afg_test_options['afg_settings'] = ['faq_count' => null];

        $this->prompt_builder
            ->expects($this->once())
            ->method('build')
            ->with('Test Title', 'Test content', null)
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $this->generator->generateFaqs(1);
    }

    // ─── Requirement 2.4: missing afg_settings uses empty array default ──────

    /**
     * Validates: Requirement 2.4
     * When afg_settings option is missing, empty array default is used and null is passed as faq_count.
     */
    #[Test]
    public function missing_afg_settings_option_uses_empty_array_default(): void
    {
        global $afg_test_options;
        $this->createPublishedPost(1);
        // Do not set afg_settings in options — simulates missing option

        $this->prompt_builder
            ->expects($this->once())
            ->method('build')
            ->with('Test Title', 'Test content', null)
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $this->generator->generateFaqs(1);
    }

    // ─── Requirement 5.3: empty title + empty content proceeds ───────────────

    /**
     * Validates: Requirement 5.3
     * Empty title and empty content proceeds without exception.
     */
    #[Test]
    public function empty_title_and_empty_content_proceeds_without_exception(): void
    {
        $this->createPublishedPost(1, '', '');

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $result = $this->generator->generateFaqs(1);

        $this->assertIsArray($result);
    }

    // ─── Requirement 5.4: non-empty title + empty content proceeds ───────────

    /**
     * Validates: Requirement 5.4
     * Non-empty title with empty content proceeds without exception.
     */
    #[Test]
    public function non_empty_title_and_empty_content_proceeds_without_exception(): void
    {
        $this->createPublishedPost(1, 'My Title', '');

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $result = $this->generator->generateFaqs(1);

        $this->assertIsArray($result);
    }

    // ─── Requirement 5.4: empty title + non-empty content proceeds ───────────

    /**
     * Validates: Requirement 5.4
     * Empty title with non-empty content proceeds without exception.
     */
    #[Test]
    public function empty_title_and_non_empty_content_proceeds_without_exception(): void
    {
        $this->createPublishedPost(1, '', 'Some content here');

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([['question' => 'Q1', 'answer' => 'A1']]);

        $result = $this->generator->generateFaqs(1);

        $this->assertIsArray($result);
    }

    // ─── Requirement 4.3, 5.5: empty AI response returns empty array ─────────

    /**
     * Validates: Requirements 4.3, 5.5
     * When the AI provider returns an empty array, the service returns an empty array.
     */
    #[Test]
    public function empty_ai_response_returns_empty_array(): void
    {
        $this->createPublishedPost(1);

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn([]);

        $result = $this->generator->generateFaqs(1);

        $this->assertSame([], $result);
    }

    // ─── Requirement 4.1, 4.2: valid FAQ array is returned unchanged ─────────

    /**
     * Validates: Requirements 4.1, 4.2
     * A valid FAQ array with non-empty questions and answers is returned unchanged.
     */
    #[Test]
    public function valid_faq_array_is_returned_unchanged(): void
    {
        $this->createPublishedPost(1);

        $faqs = [
            ['question' => 'What is PHP?', 'answer' => 'A programming language.'],
            ['question' => 'What is WordPress?', 'answer' => 'A CMS built with PHP.'],
        ];

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn($faqs);

        $result = $this->generator->generateFaqs(1);

        $this->assertSame($faqs, $result);
    }

    // ─── Requirement 4.4: items with empty question/answer are filtered out ──

    /**
     * Validates: Requirement 4.4
     * FAQ items with empty or whitespace-only question or answer are filtered out.
     */
    #[Test]
    public function items_with_empty_question_or_answer_are_filtered_out(): void
    {
        $this->createPublishedPost(1);

        $faqs = [
            ['question' => 'Valid question?', 'answer' => 'Valid answer.'],
            ['question' => '', 'answer' => 'Answer without question.'],
            ['question' => 'Question without answer?', 'answer' => ''],
            ['question' => '   ', 'answer' => 'Whitespace question.'],
            ['question' => 'Whitespace answer?', 'answer' => "  \t\n  "],
            ['question' => 'Another valid?', 'answer' => 'Yes it is.'],
        ];

        $this->prompt_builder
            ->method('build')
            ->willReturn('prompt');

        $this->ai_provider
            ->method('generateFaqs')
            ->willReturn($faqs);

        $result = $this->generator->generateFaqs(1);

        $expected = [
            ['question' => 'Valid question?', 'answer' => 'Valid answer.'],
            ['question' => 'Another valid?', 'answer' => 'Yes it is.'],
        ];

        $this->assertSame($expected, $result);
    }
}
