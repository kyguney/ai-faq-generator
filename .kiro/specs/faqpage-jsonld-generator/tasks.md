# Implementation Plan: FAQPage JSON-LD Generator

## Overview

Implement the `JSON_LD_Generator` service class that outputs FAQPage structured data (JSON-LD) in the document `<head>` for singular posts containing generated FAQs. The implementation follows the existing service-class pattern, integrates with the Loader autoloader, and includes comprehensive property-based and unit tests.

## Tasks

- [x] 1. Add WordPress function stubs for testing and create the JSON_LD_Generator service class
  - [x] 1.1 Add `is_singular()` and `get_post_meta()` stubs to the test bootstrap
    - Add a `global $afg_test_is_singular` variable (default `true`) and stub `is_singular()` to return it
    - Add a `global $afg_test_post_meta_values` associative array and stub `get_post_meta()` to look up values by `"{$post_id}_{$meta_key}"` key, returning empty string if not found
    - Add the `require_once` for the new service file at the bottom of `tests/bootstrap.php`
    - _Requirements: 5.3, 1.4_

  - [x] 1.2 Create the `JSON_LD_Generator` service class file
    - Create `includes/services/class-json-ld-generator.php`
    - Namespace: `WPBits\AiFaqGenerator\Includes\Services`
    - Implement `init()` method that calls `add_action('wp_head', [$this, 'output_schema'], 20)`
    - Implement `output_schema()` method with early return if `!is_singular()`
    - Read `_aifaq_generated_faqs` meta via `get_post_meta(get_the_ID(), '_aifaq_generated_faqs', true)`
    - Call `build_schema()` and output inside `<script type="application/ld+json">` tag if valid
    - Implement `build_schema(string $raw_meta): ?array` that calls `parse_faq_items()`, returns null if empty, limits to 25 items, and builds the FAQPage schema structure
    - Implement `parse_faq_items(string $raw_meta): array` that decodes JSON, validates each item has non-empty question and answer strings
    - Implement `build_question_object(array $item): array` that returns schema.org Question structure
    - Implement `prepare_question_text(string $text): string` that decodes HTML entities and strips tags
    - Implement `prepare_answer_text(string $text): string` that decodes HTML entities but preserves markup
    - Implement `escape_script_tags(string $json): string` that replaces `</script` (case-insensitive) with `<\/script`
    - Use `wp_json_encode()` with `JSON_UNESCAPED_UNICODE` flag, return early if it returns false
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 5.4, 6.1, 6.2, 6.3, 6.4_

  - [x] 1.3 Register `JSON_LD_Generator` in the Loader class
    - Add `'WPBits\\AiFaqGenerator\\Includes\\Services\\JSON_LD_Generator' => AFG_PLUGIN_PATH . 'includes/services/class-json-ld-generator.php'` to the `$classes` array in `Loader::__construct()`
    - Add instantiation and `init()` call in `Loader::init()` after the existing service initializations (outside the `is_admin()` block since it runs on the frontend)
    - _Requirements: 5.1, 5.2, 5.4_

- [x] 2. Checkpoint - Verify core implementation
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Write unit tests for hook registration and edge cases
  - [x] 3.1 Create `JsonLdGeneratorTest.php` unit test class
    - Create `tests/unit/JsonLdGeneratorTest.php`
    - Test that `init()` registers `wp_head` action at priority 20 with a public method callback
    - Test that `output_schema()` produces no output when `is_singular()` returns false
    - Test that `output_schema()` produces no output when meta is empty string, `'[]'`, or absent
    - Test that arrays with more than 25 items are truncated to 25 Question objects
    - Test that the callback is a publicly accessible callable (not a closure) for `remove_action()` compatibility
    - Test a valid FAQ array produces correct script tag with FAQPage JSON-LD
    - _Requirements: 1.1, 1.2, 1.4, 5.1, 5.2, 5.4_

- [x] 4. Write property-based tests for correctness properties
  - [x] 4.1 Write property test for schema structure invariant
    - **Property 1: Schema Structure Invariant**
    - **Validates: Requirements 1.1, 2.1, 2.2, 2.3, 2.4, 2.5**
    - Create `tests/unit/JsonLdSchemaStructurePropertyTest.php`
    - Generate 100+ random valid FAQ arrays (1–25 items with non-empty question/answer)
    - Assert root object has `@context` = `"https://schema.org"`, `@type` = `"FAQPage"`, `mainEntity` array
    - Assert each mainEntity element has `@type` = `"Question"`, `name` string, `acceptedAnswer` object with `@type` = `"Answer"` and `text` string

  - [x] 4.2 Write property test for invalid item filtering
    - **Property 2: Invalid Item Filtering**
    - **Validates: Requirements 2.6, 3.1, 3.3, 3.4**
    - Create `tests/unit/JsonLdFilteringPropertyTest.php`
    - Generate 100+ arrays mixing valid and invalid items (missing keys, empty strings, whitespace-only, non-string values)
    - Assert `mainEntity` length equals count of valid items
    - Assert no invalid item appears in output

  - [x] 4.3 Write property test for order preservation
    - **Property 3: Order Preservation**
    - **Validates: Requirements 3.2**
    - Create `tests/unit/JsonLdOrderPropertyTest.php`
    - Generate 100+ random FAQ arrays with distinct question values
    - Assert Question objects appear in same relative order as input valid items

  - [x] 4.4 Write property test for invalid input producing no output
    - **Property 4: Invalid Input Produces No Output**
    - **Validates: Requirements 1.2, 1.3**
    - Create `tests/unit/JsonLdInvalidInputPropertyTest.php`
    - Generate 100+ invalid meta strings: malformed JSON, non-array JSON, arrays of non-objects, empty strings, whitespace-only
    - Assert output_schema produces no script tag (empty output buffer)

  - [x] 4.5 Write property test for script tag escaping
    - **Property 5: Script Tag Escaping**
    - **Validates: Requirements 4.2**
    - Create `tests/unit/JsonLdScriptEscapingPropertyTest.php`
    - Generate 100+ FAQ items containing `</script` in various cases (mixed upper/lower)
    - Assert final output never contains literal `</script` (case-insensitive)

  - [x] 4.6 Write property test for unicode preservation
    - **Property 6: Unicode Preservation**
    - **Validates: Requirements 4.1**
    - Create `tests/unit/JsonLdUnicodePropertyTest.php`
    - Generate 100+ FAQ items with Unicode characters (CJK, Cyrillic, Arabic, emoji)
    - Assert characters appear unescaped in output (no `\uXXXX` sequences)

  - [x] 4.7 Write property test for JSON validity with special characters
    - **Property 7: JSON Validity with Special Characters**
    - **Validates: Requirements 6.2**
    - Create `tests/unit/JsonLdJsonValidityPropertyTest.php`
    - Generate 100+ FAQ items containing double quotes, backslashes, control characters
    - Extract JSON content from script tag, un-escape `<\/script` → `</script`
    - Assert `json_decode()` succeeds (valid JSON per RFC 8259)

  - [x] 4.8 Write property test for HTML entity decoding
    - **Property 8: HTML Entity Decoding**
    - **Validates: Requirements 6.1**
    - Create `tests/unit/JsonLdEntityDecodingPropertyTest.php`
    - Generate 100+ FAQ items with HTML entities (`&amp;`, `&#60;`, `&#x3C;`, etc.)
    - Assert JSON-LD output contains decoded Unicode characters, not raw entity strings

  - [x] 4.9 Write property test for HTML handling in questions and answers
    - **Property 9: HTML Handling in Questions and Answers**
    - **Validates: Requirements 6.3, 6.4**
    - Create `tests/unit/JsonLdHtmlHandlingPropertyTest.php`
    - Generate 100+ FAQ items with HTML tags in both questions and answers
    - Assert `name` field contains no HTML tags (all stripped)
    - Assert `text` field preserves HTML markup from answer

- [x] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Integration wiring and final verification
  - [x] 6.1 Verify Loader integration end-to-end
    - Add a test to `JsonLdGeneratorTest.php` (or existing `LoaderPropertyTest.php`) verifying that after `Loader::init()`, a `wp_head` action is registered at priority 20 with a callable referencing `JSON_LD_Generator::output_schema`
    - Ensure the class map entry in Loader resolves correctly during autoload
    - _Requirements: 5.1, 5.2, 5.4_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples, edge cases, and WordPress integration
- The implementation uses PHP following the existing service-class pattern in the plugin
- All tests use the existing PHPUnit 11 + DataProvider pattern (100+ random iterations per property)
- WordPress function stubs in `tests/bootstrap.php` follow the established pattern of global variables controlling stub behavior

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["1.3"] },
    { "id": 3, "tasks": ["3.1"] },
    { "id": 4, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7", "4.8", "4.9"] },
    { "id": 5, "tasks": ["6.1"] }
  ]
}
```
