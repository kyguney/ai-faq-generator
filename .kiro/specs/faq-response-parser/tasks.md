# Implementation Plan: FAQ Response Parser

## Overview

Extract the FAQ parsing logic from `OpenAIClient::parseFaqItems()` into a standalone `Faq_Parser` service class at `includes/services/class-faq-parser.php`. The new class exposes a single `parse(string $content): array` method that never throws, handles markdown fence stripping, JSON decoding, item validation/filtering, whitespace trimming, and re-indexing. After the class is implemented and tested, `OpenAIClient` is updated to delegate to `Faq_Parser`.

## Tasks

- [x] 1. Create the Faq_Parser service class
  - [x] 1.1 Create `includes/services/class-faq-parser.php` with class skeleton
    - Declare `strict_types=1` and namespace `WPBits\AiFaqGenerator\Includes\Services`
    - Define `class Faq_Parser` with public `parse(string $content): array` method
    - Add private helper method stubs: `strip_markdown_fences`, `decode_json`, `validate_item`, `trim_values`
    - Wrap entire `parse()` body in try/catch for `\Throwable`, returning `[]` on any uncaught error
    - _Requirements: 6.1, 6.2, 7.1, 7.2_

  - [x] 1.2 Implement `strip_markdown_fences(string $content): string`
    - Use regex `/^\s*```(?:\w*)\s*\n([\s\S]*?)\n\s*```\s*$/` to detect and strip fences
    - If pattern does not match, return content as-is (handles malformed/partial fences)
    - Catch any regex exception and return raw content
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 1.3 Implement `decode_json(string $content): ?array`
    - Call `json_decode($content, true)` on the fence-stripped content
    - Return `null` if result is not an indexed array (`array_is_list` check)
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 1.4 Implement `validate_item(mixed $item): ?array` and `trim_values(array $item): array`
    - `validate_item`: Check item is an array with string `question` and `answer` keys that are non-empty after trim; return `null` if invalid
    - `trim_values`: Trim leading/trailing whitespace from `question` and `answer`, return array with only those two keys
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.6, 3.7, 4.1, 4.2, 4.3_

  - [x] 1.5 Wire the pipeline in `parse()` method
    - Call `strip_markdown_fences` → `decode_json` → filter/map items with `validate_item` and `trim_values` → `array_values` for re-indexing
    - Return `[]` if `decode_json` returns null or all items are invalid
    - _Requirements: 1.1, 1.2, 1.3, 3.5, 6.4_

- [x] 2. Property-based tests for Faq_Parser
  - [x] 2.1 Write property test: Valid FAQ Parsing Round-Trip
    - **Property 1: Valid FAQ Parsing Round-Trip**
    - **Validates: Requirements 1.1, 1.2, 1.3, 3.6**
    - Create `tests/unit/FaqParserValidParsingPropertyTest.php`
    - Generate 110+ random valid JSON arrays with question/answer objects (with optional extra keys)
    - Assert output length matches input valid item count, order preserved, only question/answer keys retained, values trimmed

  - [x] 2.2 Write property test: Invalid JSON Returns Empty Array
    - **Property 2: Invalid JSON Returns Empty Array**
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
    - Create `tests/unit/FaqParserInvalidJsonPropertyTest.php`
    - Generate 110+ random invalid JSON strings (random text, truncated JSON, non-array JSON types, empty/whitespace strings)
    - Assert `parse()` returns `[]` for every case

  - [x] 2.3 Write property test: Invalid Items Filtered, Valid Items Preserved
    - **Property 3: Invalid Items Filtered, Valid Items Preserved in Order**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.7**
    - Create `tests/unit/FaqParserFilteringPropertyTest.php`
    - Generate 110+ random arrays mixing valid FAQ items with invalid entries (missing keys, non-string values, scalars, nested arrays)
    - Assert only valid items returned in original relative order with zero-based keys

  - [x] 2.4 Write property test: Whitespace Trimming Preserves Internal Content
    - **Property 4: Whitespace Trimming Preserves Internal Content**
    - **Validates: Requirements 4.1, 4.2, 4.3**
    - Create `tests/unit/FaqParserTrimmingPropertyTest.php`
    - Generate 110+ random FAQ items with random leading/trailing whitespace (spaces, tabs, newlines, carriage returns)
    - Assert leading/trailing whitespace removed, internal whitespace preserved unchanged

  - [x] 2.5 Write property test: Markdown Fence Stripping Enables Parsing
    - **Property 5: Markdown Fence Stripping Enables Parsing**
    - **Validates: Requirements 5.1, 5.2, 5.3**
    - Create `tests/unit/FaqParserFenceStrippingPropertyTest.php`
    - Generate 110+ random valid FAQ JSON strings, wrap each in markdown fences (with/without language identifier, with/without surrounding whitespace)
    - Assert fenced input produces same result as unwrapped input

  - [x] 2.6 Write property test: Return Type Invariant
    - **Property 6: Return Type Invariant**
    - **Validates: Requirements 6.1, 6.2, 6.4**
    - Create `tests/unit/FaqParserReturnTypePropertyTest.php`
    - Generate 110+ random strings (valid JSON, invalid JSON, binary-like data, empty, extremely long)
    - Assert `parse()` never throws, always returns array with zero-based sequential int keys, each element has exactly `question` and `answer` string keys

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Integrate Faq_Parser into OpenAIClient
  - [x] 4.1 Update `OpenAIClient::generateFaqs()` to use Faq_Parser
    - Replace `return $this->parseFaqItems($content)` with `$parser = new Faq_Parser(); return $parser->parse($content);`
    - Add `use WPBits\AiFaqGenerator\Includes\Services\Faq_Parser;` import
    - Remove the private `parseFaqItems()` method from `OpenAIClient`
    - _Requirements: 7.1, 7.2_

  - [x] 4.2 Write unit tests for Faq_Parser integration edge cases
    - Create `tests/unit/FaqParserTest.php` with example-based tests
    - Test malformed markdown fences (only opening fence, mismatched fences) — Requirement 5.4
    - Test method signature via reflection — Requirement 7.1
    - Test constructor without arguments — Requirement 7.2
    - Test specific edge cases: single item array, all-invalid array, truncated JSON
    - _Requirements: 5.4, 7.1, 7.2_

- [x] 5. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (110+ random cases each, matching project convention)
- Unit tests validate specific examples and edge cases
- The parser follows snake_case method naming consistent with existing services (e.g., `Prompt_Builder`)
- PHP 8.x with `declare(strict_types=1)` throughout
- PHPUnit 11 with `#[DataProvider]` and `#[Test]` attributes

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3", "1.4"] },
    { "id": 2, "tasks": ["1.5"] },
    { "id": 3, "tasks": ["2.1", "2.2", "2.3", "2.4", "2.5", "2.6"] },
    { "id": 4, "tasks": ["4.1"] },
    { "id": 5, "tasks": ["4.2"] }
  ]
}
```
