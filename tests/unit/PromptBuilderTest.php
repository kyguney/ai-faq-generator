<?php
/**
 * Unit tests for the Prompt_Builder service.
 *
 * Validates: Requirements 2.1, 2.2, 2.3, 3.2, 4.1, 4.2, 4.3, 4.4, 4.5
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WPBits\AiFaqGenerator\Includes\Services\Prompt_Builder;

class PromptBuilderTest extends TestCase
{
    private Prompt_Builder $builder;

    protected function setUp(): void
    {
        $this->builder = new Prompt_Builder();
    }

    // ─── Requirement 2.1: JSON array instruction ─────────────────────────────

    /**
     * Validates: Requirement 2.1
     * The prompt must instruct the AI to return a JSON array as the top-level structure.
     */
    #[Test]
    public function output_contains_json_array_instruction(): void
    {
        $result = $this->builder->build('Test Title', 'Test content');

        $this->assertStringContainsString('JSON array', $result);
        $this->assertStringContainsString('top-level structure', $result);
    }

    // ─── Requirement 2.2: question/answer key instruction ────────────────────

    /**
     * Validates: Requirement 2.2
     * The prompt must instruct the AI that each element has "question" and "answer" keys.
     */
    #[Test]
    public function output_contains_question_answer_key_instruction(): void
    {
        $result = $this->builder->build('Test Title', 'Test content');

        $this->assertStringContainsString('"question"', $result);
        $this->assertStringContainsString('"answer"', $result);
    }

    // ─── Requirement 2.3: raw-JSON-only instruction ──────────────────────────

    /**
     * Validates: Requirement 2.3
     * The prompt must instruct the AI to return only raw JSON without prose or code fences.
     */
    #[Test]
    public function output_contains_raw_json_only_instruction(): void
    {
        $result = $this->builder->build('Test Title', 'Test content');

        $this->assertStringContainsString('raw JSON', $result);
        $this->assertStringContainsString('without surrounding prose', $result);
        $this->assertStringContainsString('markdown code fences', $result);
    }

    // ─── Requirement 3.2: null FAQ count defaults to 5 ──────────────────────

    /**
     * Validates: Requirement 3.2
     * When no FAQ count is provided, the prompt uses the default of 5.
     */
    #[Test]
    public function null_faq_count_defaults_to_five_in_prompt(): void
    {
        $result = $this->builder->build('Test Title', 'Test content', null);

        $this->assertStringContainsString('5', $result);
        $this->assertStringContainsString('Generate exactly 5', $result);
    }

    // ─── Requirement 4.1: empty title omits title section ────────────────────

    /**
     * Validates: Requirement 4.1
     * When the title is empty, the prompt does not include a title section.
     */
    #[Test]
    public function empty_title_omits_title_section_from_prompt(): void
    {
        $result = $this->builder->build('', 'Some content here');

        $this->assertStringNotContainsString('Title:', $result);
        $this->assertStringContainsString('Content: Some content here', $result);
    }

    // ─── Requirement 4.2: empty content omits content section ────────────────

    /**
     * Validates: Requirement 4.2
     * When the content is empty, the prompt does not include a content section.
     */
    #[Test]
    public function empty_content_omits_content_section_from_prompt(): void
    {
        $result = $this->builder->build('My Title', '');

        $this->assertStringNotContainsString('Content:', $result);
        $this->assertStringContainsString('Title: My Title', $result);
    }

    // ─── Requirement 4.3: both empty produces valid prompt ───────────────────

    /**
     * Validates: Requirement 4.3
     * When both title and content are empty, the prompt contains only instructions.
     */
    #[Test]
    public function both_empty_produces_valid_prompt_with_instructions_only(): void
    {
        $result = $this->builder->build('', '');

        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('Title:', $result);
        $this->assertStringNotContainsString('Content:', $result);
        // Should still contain the JSON format instructions
        $this->assertStringContainsString('JSON array', $result);
        $this->assertStringContainsString('Generate exactly', $result);
    }

    // ─── Requirement 4.4: HTML-only content treated as empty ─────────────────

    /**
     * Validates: Requirement 4.4
     * When content contains only HTML tags with no text, it is treated as empty.
     */
    #[Test]
    public function html_only_content_is_treated_as_empty(): void
    {
        $result = $this->builder->build('My Title', '<div><span></span></div>');

        $this->assertStringNotContainsString('Content:', $result);
        $this->assertStringContainsString('Title: My Title', $result);
    }

    // ─── Requirement 4.5: whitespace-only input treated as empty ─────────────

    /**
     * Validates: Requirement 4.5
     * When title or content contains only whitespace, it is treated as empty.
     */
    #[Test]
    public function whitespace_only_input_is_treated_as_empty(): void
    {
        $result = $this->builder->build('   ', "  \t\n  ");

        $this->assertStringNotContainsString('Title:', $result);
        $this->assertStringNotContainsString('Content:', $result);
        // Should still produce a valid prompt with instructions
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('JSON array', $result);
    }
}
