<?php
/**
 * Unit tests for the Faq_Parser service class.
 *
 * Example-based tests covering:
 * - Malformed markdown fences (Requirement 5.4)
 * - Method signature via reflection (Requirement 7.1)
 * - Constructor without arguments (Requirement 7.2)
 * - Specific edge cases: single item array, all-invalid array, truncated JSON
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;

class FaqParserTest extends TestCase
{
    private Faq_Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Faq_Parser();
    }

    // ─── Malformed Markdown Fences (Requirement 5.4) ────────────────────────────

    /**
     * **Validates: Requirement 5.4**
     *
     * Only opening fence without closing — parser should attempt JSON decode on content as-is.
     */
    #[Test]
    public function parse_handles_only_opening_fence_without_closing(): void
    {
        $input = "```json\n" . '[{"question":"Q","answer":"A"}]';

        $result = $this->parser->parse($input);

        // The regex won't match (no closing fence), so content is used as-is.
        // The raw string starts with "```json\n" which is not valid JSON,
        // so it should return empty array.
        $this->assertSame([], $result);
    }

    /**
     * **Validates: Requirement 5.4**
     *
     * Only closing fence — parser should attempt JSON decode on content as-is.
     */
    #[Test]
    public function parse_handles_only_closing_fence(): void
    {
        $input = '[{"question":"Q","answer":"A"}]' . "\n```";

        $result = $this->parser->parse($input);

        // The regex won't match (no opening fence), so content is used as-is.
        // The raw string ends with "\n```" which makes it invalid JSON,
        // so it should return empty array.
        $this->assertSame([], $result);
    }

    /**
     * **Validates: Requirement 5.4**
     *
     * Mismatched fences (opening with different closing) — parser should attempt
     * JSON decode on content as-is without stripping.
     */
    #[Test]
    public function parse_handles_mismatched_fences(): void
    {
        $input = "```json\n" . '[{"question":"Q","answer":"A"}]' . "\n~~~";

        $result = $this->parser->parse($input);

        // Mismatched fences: opening ``` but closing ~~~.
        // The regex won't match, so content is used as-is (invalid JSON).
        $this->assertSame([], $result);
    }

    // ─── Method Signature via Reflection (Requirement 7.1) ──────────────────────

    /**
     * **Validates: Requirement 7.1**
     *
     * Assert `parse` method exists and is public.
     */
    #[Test]
    public function parse_method_exists_and_is_public(): void
    {
        $reflection = new ReflectionClass(Faq_Parser::class);

        $this->assertTrue($reflection->hasMethod('parse'));

        $method = $reflection->getMethod('parse');
        $this->assertTrue($method->isPublic());
    }

    /**
     * **Validates: Requirement 7.1**
     *
     * Assert `parse` accepts exactly one parameter of type `string`.
     */
    #[Test]
    public function parse_method_accepts_one_string_parameter(): void
    {
        $method = new ReflectionMethod(Faq_Parser::class, 'parse');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('content', $parameters[0]->getName());
        $this->assertNotNull($parameters[0]->getType());
        $this->assertSame('string', $parameters[0]->getType()->getName());
    }

    /**
     * **Validates: Requirement 7.1**
     *
     * Assert return type is `array`.
     */
    #[Test]
    public function parse_method_has_array_return_type(): void
    {
        $method = new ReflectionMethod(Faq_Parser::class, 'parse');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    // ─── Constructor Without Arguments (Requirement 7.2) ────────────────────────

    /**
     * **Validates: Requirement 7.2**
     *
     * Assert Faq_Parser can be instantiated with no constructor arguments.
     */
    #[Test]
    public function can_be_instantiated_without_arguments(): void
    {
        $parser = new Faq_Parser();

        $this->assertInstanceOf(Faq_Parser::class, $parser);
    }

    /**
     * **Validates: Requirement 7.2**
     *
     * Assert the constructor has zero required parameters.
     */
    #[Test]
    public function constructor_has_zero_required_parameters(): void
    {
        $reflection = new ReflectionClass(Faq_Parser::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            // No explicit constructor means zero required parameters.
            $this->assertTrue(true);
            return;
        }

        $requiredParams = array_filter(
            $constructor->getParameters(),
            fn($param) => !$param->isOptional()
        );

        $this->assertCount(0, $requiredParams);
    }

    // ─── Specific Edge Cases ────────────────────────────────────────────────────

    /**
     * Single item array returns one item.
     */
    #[Test]
    public function parse_returns_single_item_from_single_element_array(): void
    {
        $input = '[{"question":"What is PHP?","answer":"A programming language."}]';

        $result = $this->parser->parse($input);

        $this->assertCount(1, $result);
        $this->assertSame('What is PHP?', $result[0]['question']);
        $this->assertSame('A programming language.', $result[0]['answer']);
    }

    /**
     * All-invalid array returns empty array.
     */
    #[Test]
    public function parse_returns_empty_array_for_all_invalid_items(): void
    {
        $input = json_encode([null, 42, 'string', true]);

        $result = $this->parser->parse($input);

        $this->assertSame([], $result);
    }

    /**
     * Truncated JSON returns empty array.
     */
    #[Test]
    public function parse_returns_empty_array_for_truncated_json(): void
    {
        $input = '[{"question":"Q","answer":"A"';

        $result = $this->parser->parse($input);

        $this->assertSame([], $result);
    }
}
