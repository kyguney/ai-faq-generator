# Implementation Plan: Prompt Builder

## Overview

Implement the `Prompt_Builder` service class that constructs deterministic prompt strings for AI-powered FAQ generation. The class sanitizes inputs (HTML stripping, whitespace trimming), truncates content to 2000 characters, validates/clamps FAQ count to [1, 20], and assembles a structured prompt instructing the AI to return a JSON array of question/answer pairs.

## Tasks

- [x] 1. Create Prompt_Builder class and register in Loader
  - [x] 1.1 Create the Prompt_Builder service class file
    - Create `includes/services/class-prompt-builder.php`
    - Define the class in namespace `WPBits\AiFaqGenerator\Includes\Services`
    - Add constants: `CONTENT_LIMIT = 2000`, `DEFAULT_FAQ_COUNT = 5`, `MIN_FAQ_COUNT = 1`, `MAX_FAQ_COUNT = 20`
    - Implement `build(string $post_title, string $post_content, ?int $faq_count = null): string` public method
    - Implement private helper methods: `sanitize_input()`, `truncate_content()`, `resolve_faq_count()`, `assemble_prompt()`
    - `sanitize_input()` must use `wp_strip_all_tags()` and `trim()` to strip HTML and whitespace
    - `truncate_content()` must cut at exactly 2000 characters using `substr()`
    - `resolve_faq_count()` must clamp to [1, 20] and default to 5 when null
    - `assemble_prompt()` must include JSON array instruction, question/answer key instruction, raw-JSON-only instruction, FAQ count, and post context (title/content) when non-empty
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 4.1, 4.2, 4.3, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4_

  - [x] 1.2 Register Prompt_Builder in the Loader class autoload map
    - Add entry to `$this->classes` array in `includes/class-loader.php`
    - Map `'WPBits\\AiFaqGenerator\\Includes\\Services\\Prompt_Builder'` to `AFG_PLUGIN_PATH . 'includes/services/class-prompt-builder.php'`
    - _Requirements: 1.1_

  - [x] 1.3 Add wp_strip_all_tags stub to test bootstrap
    - Add `wp_strip_all_tags` function stub in `tests/bootstrap.php`
    - Stub must use `strip_tags()` and `trim()` to mimic WordPress behavior
    - Add require_once for the new Prompt_Builder class file
    - _Requirements: 1.2, 1.3_

- [x] 2. Implement unit tests for static prompt instructions
  - [x] 2.1 Write unit tests for prompt format and instructions
    - Create `tests/unit/PromptBuilderTest.php`
    - Test that output contains JSON array instruction (Requirement 2.1)
    - Test that output contains question/answer key instruction (Requirement 2.2)
    - Test that output contains raw-JSON-only instruction (Requirement 2.3)
    - Test that null FAQ count defaults to 5 in the prompt (Requirement 3.2)
    - Test that empty title omits title section from prompt (Requirement 4.1)
    - Test that empty content omits content section from prompt (Requirement 4.2)
    - Test that both empty produces valid prompt with instructions only (Requirement 4.3)
    - Test that HTML-only content (no text) is treated as empty (Requirement 4.4)
    - Test that whitespace-only input is treated as empty (Requirement 4.5)
    - _Requirements: 2.1, 2.2, 2.3, 3.2, 4.1, 4.2, 4.3, 4.4, 4.5_

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement property-based tests
  - [x] 4.1 Write property test for HTML tag stripping
    - Create `tests/unit/PromptBuilderHtmlStrippingPropertyTest.php`
    - **Property 1: HTML Tag Stripping**
    - Use DataProvider to generate 100+ random strings wrapped in random HTML tags
    - Assert that no HTML tags appear in the output prompt string
    - **Validates: Requirements 1.2, 1.3**

  - [x] 4.2 Write property test for content truncation invariant
    - Create `tests/unit/PromptBuilderTruncationPropertyTest.php`
    - **Property 2: Content Truncation Invariant**
    - Use DataProvider to generate 100+ random strings of varying lengths (0 to 5000 chars)
    - Assert that content portion in prompt has length equal to `min(stripped_length, 2000)`
    - **Validates: Requirements 1.4, 1.5**

  - [x] 4.3 Write property test for FAQ count clamping
    - Create `tests/unit/PromptBuilderFaqCountPropertyTest.php`
    - **Property 3: FAQ Count Clamping**
    - Use DataProvider to generate 100+ random integers from -100 to 100, plus null
    - Assert that the FAQ count in the prompt equals `clamp(faq_count, 1, 20)` or 5 when null
    - **Validates: Requirements 3.1, 3.2, 3.3**

  - [x] 4.4 Write property test for empty-after-sanitization treatment
    - Create `tests/unit/PromptBuilderEmptyInputPropertyTest.php`
    - **Property 4: Empty-After-Sanitization Treatment**
    - Use DataProvider to generate 100+ whitespace-only strings, HTML-only strings, and mixed
    - Assert that inputs resolving to empty after sanitization are treated as absent
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

  - [x] 4.5 Write property test for deterministic output
    - Create `tests/unit/PromptBuilderDeterminismPropertyTest.php`
    - **Property 5: Deterministic Output**
    - Use DataProvider to generate 100+ random title/content/count combinations
    - Call `build()` twice with same arguments and assert byte-for-byte identical output
    - **Validates: Requirements 5.1, 5.4**

  - [x] 4.6 Write property test for non-empty output
    - Create `tests/unit/PromptBuilderNonEmptyPropertyTest.php`
    - **Property 6: Non-Empty Output**
    - Use DataProvider to generate 100+ random valid inputs including edge cases
    - Assert that the return value is always a non-empty string
    - **Validates: Requirements 5.2**

  - [x] 4.7 Write property test for content inclusion
    - Create `tests/unit/PromptBuilderInclusionPropertyTest.php`
    - **Property 7: Content Inclusion**
    - Use DataProvider to generate 100+ random non-empty title/content pairs
    - Assert that the prompt contains both the sanitized title and sanitized content
    - **Validates: Requirements 1.1**

- [x] 5. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties using PHPUnit DataProvider with 100+ iterations
- Unit tests validate specific examples and edge cases
- The existing test bootstrap pattern is followed for WordPress function stubs
- The `Prompt_Builder` class is stateless with no constructor dependencies, making it trivially testable

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["2.1"] },
    { "id": 3, "tasks": ["4.1", "4.2", "4.3", "4.4", "4.5", "4.6", "4.7"] }
  ]
}
```
