# Design Document: FAQ Response Parser

## Overview

The FAQ Response Parser (`Faq_Parser`) is a standalone service class that extracts, validates, and normalizes FAQ items from raw AI response content. It replaces the private `parseFaqItems()` method currently embedded in `OpenAIClient`, making the parsing logic reusable, independently testable, and resilient to common AI response quirks (markdown fences, extra keys, whitespace).

The class lives at `includes/services/class-faq-parser.php` under the namespace `WPBits\AiFaqGenerator\Includes\Services` and exposes a single public method `parse(string $content): array` that never throws exceptions — it returns an empty array on any failure.

### Design Rationale

- **Single Responsibility**: Parsing/validation logic is decoupled from HTTP transport concerns in `OpenAIClient`.
- **Defensive Design**: The parser never throws. Callers don't need try/catch blocks or null checks.
- **Reusability**: Any future AI provider (Anthropic, Gemini, local models) can use the same parser.
- **Testability**: Pure function behavior (string in → array out) with no side effects or dependencies.

## Architecture

```mermaid
graph LR
    A[OpenAIClient] -->|raw content string| B[Faq_Parser::parse]
    B -->|strip markdown fences| C[Fence Stripper]
    C -->|clean string| D[JSON Decoder]
    D -->|decoded array| E[Item Validator/Filter]
    E -->|valid items| F["array<int, array{question, answer}>"]
```

The parser operates as a pipeline of transformations:

1. **Fence Stripping** — Remove markdown code fences if present
2. **JSON Decoding** — Decode the string into a PHP array
3. **Item Filtering** — Validate each item, trim values, discard invalid entries
4. **Re-indexing** — Return a zero-based sequential array of clean FAQ items

## Components and Interfaces

### Faq_Parser Class

```php
<?php
declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Includes\Services;

/**
 * Parses raw AI response content into validated FAQ item arrays.
 *
 * @package WPBits\AiFaqGenerator\Includes\Services
 */
class Faq_Parser
{
    /**
     * Parse raw AI response content into FAQ items.
     *
     * @param string $content Raw content from AI response (choices[0].message.content).
     * @return array<int, array{question: string, answer: string}> Validated FAQ items.
     */
    public function parse(string $content): array;
}
```

### Integration with OpenAIClient

The `OpenAIClient::generateFaqs()` method will delegate to `Faq_Parser::parse()` instead of calling the private `parseFaqItems()` method. The private method can then be removed.

```php
// Before (in OpenAIClient):
return $this->parseFaqItems($content);

// After (in OpenAIClient):
$parser = new Faq_Parser();
return $parser->parse($content);
```

### Internal Methods (Private)

| Method | Responsibility |
|--------|---------------|
| `strip_markdown_fences(string $content): string` | Remove wrapping ` ```json ` or ` ``` ` fences |
| `decode_json(string $content): ?array` | JSON decode and validate result is an indexed array |
| `validate_item(mixed $item): ?array` | Validate a single item has non-empty string question/answer |
| `trim_values(array $item): array` | Trim whitespace from question and answer values |

## Data Models

### Input

| Field | Type | Description |
|-------|------|-------------|
| `content` | `string` | Raw string from `choices[0].message.content`. May contain JSON, markdown-wrapped JSON, or invalid content. |

### Output: FAQ Item

| Field | Type | Constraints |
|-------|------|-------------|
| `question` | `string` | Non-empty after trim. No leading/trailing whitespace. |
| `answer` | `string` | Non-empty after trim. No leading/trailing whitespace. |

The output array is always zero-based with sequential integer keys (`array_values` applied after filtering).

### Markdown Fence Pattern

The regex pattern for fence detection:

```
/^\s*```(?:\w*)\s*\n([\s\S]*?)\n\s*```\s*$/
```

This matches:
- Optional leading whitespace
- Opening ` ``` ` with optional language identifier (e.g., `json`)
- Content between fences (captured)
- Closing ` ``` `
- Optional trailing whitespace

If the pattern does not match (malformed/partial fence), the content is used as-is.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Valid FAQ Parsing Round-Trip

*For any* valid JSON array of objects where each object contains non-empty-after-trim `question` and `answer` string values (and possibly additional keys), `parse()` SHALL return an array of the same length, in the same order, where each item contains only the `question` and `answer` keys with their trimmed values matching the originals.

**Validates: Requirements 1.1, 1.2, 1.3, 3.6**

### Property 2: Invalid JSON Returns Empty Array

*For any* string that is not valid JSON, or that decodes to a non-array type (object, string, number, boolean, null), or that is empty/whitespace-only, `parse()` SHALL return an empty array.

**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

### Property 3: Invalid Items Filtered, Valid Items Preserved in Order

*For any* JSON array containing a mix of valid FAQ items and invalid entries (missing keys, non-string values, whitespace-only values, scalar entries, nested arrays), `parse()` SHALL return only the valid items in their original relative order, with sequential zero-based integer keys.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.7**

### Property 4: Whitespace Trimming Preserves Internal Content

*For any* valid FAQ item whose `question` or `answer` value contains leading/trailing whitespace, `parse()` SHALL return the item with all leading and trailing whitespace removed, while preserving all internal whitespace (whitespace between non-whitespace characters) unchanged.

**Validates: Requirements 4.1, 4.2, 4.3**

### Property 5: Markdown Fence Stripping Enables Parsing

*For any* valid FAQ JSON string, wrapping it in markdown code fences (with or without a language identifier, with or without surrounding whitespace) SHALL produce the same parse result as the unwrapped JSON string.

**Validates: Requirements 5.1, 5.2, 5.3**

### Property 6: Return Type Invariant

*For any* input string whatsoever (valid JSON, invalid JSON, binary data, empty string, extremely long strings), `parse()` SHALL never throw an exception and SHALL always return a value of type `array<int, array{question: string, answer: string}>` with zero-based sequential integer keys.

**Validates: Requirements 6.1, 6.2, 6.4**

## Error Handling

The `Faq_Parser` follows a **no-throw** design philosophy:

| Failure Scenario | Behavior |
|-----------------|----------|
| Invalid JSON string | Return `[]` |
| JSON decodes to non-array | Return `[]` |
| All items invalid | Return `[]` |
| Regex error in fence stripping | Catch exception, proceed with raw content |
| `json_decode` returns null | Return `[]` |
| Unexpected internal error | Catch `\Throwable`, return `[]` |

No exceptions propagate out of `parse()`. The method wraps its entire body in a try/catch for `\Throwable` as a safety net, returning an empty array if anything unexpected occurs.

### Error Logging (Optional)

The parser does not log errors by default. If debugging is needed, the caller (e.g., `OpenAIClient`) can check for an empty result and log at its own discretion.

## Testing Strategy

### Property-Based Testing

The project uses PHPUnit 11 with `DataProvider` attributes to implement property-based testing via randomized data providers generating 100+ test cases per property.

**Library**: PHPUnit 11 with `#[DataProvider]` and `#[Test]` attributes
**Iterations**: Minimum 110 random cases per property test (matching existing project convention)
**Tag format**: `Feature: faq-response-parser, Property {number}: {property_text}`

Each correctness property maps to a single test class:

| Property | Test Class |
|----------|-----------|
| Property 1: Valid FAQ Parsing Round-Trip | `FaqParserValidParsingPropertyTest` |
| Property 2: Invalid JSON Returns Empty Array | `FaqParserInvalidJsonPropertyTest` |
| Property 3: Invalid Items Filtered | `FaqParserFilteringPropertyTest` |
| Property 4: Whitespace Trimming | `FaqParserTrimmingPropertyTest` |
| Property 5: Markdown Fence Stripping | `FaqParserFenceStrippingPropertyTest` |
| Property 6: Return Type Invariant | `FaqParserReturnTypePropertyTest` |

### Unit Tests (Example-Based)

Complement property tests with specific examples for:

- Malformed markdown fences (only opening fence, mismatched fences) — Requirement 5.4
- Method signature verification via reflection — Requirement 7.1
- Constructor without arguments — Requirement 7.2
- Specific edge cases: single item array, all-invalid array, truncated JSON

### Test Data Generation Strategy

Generators for property tests will produce:

- **Valid FAQ items**: Random non-empty strings for question/answer, optional extra keys with random values
- **Invalid items**: Missing keys, null values, integer values, empty strings, whitespace-only strings, scalar entries, nested arrays
- **Mixed arrays**: Random combination of valid and invalid items
- **Whitespace variants**: Spaces, tabs, `\n`, `\r\n` as leading/trailing padding
- **Fence wrapping**: ` ```json\n...\n``` `, ` ```\n...\n``` `, with random surrounding whitespace
- **Invalid JSON**: Random strings, truncated JSON (valid JSON cut at random positions), HTML, XML
- **Non-array JSON**: Objects `{}`, strings `"hello"`, numbers `42`, booleans `true`, null

### Running Tests

```bash
cd plugins/ai-faq-generator
./vendor/bin/phpunit --testsuite unit --filter FaqParser
```
