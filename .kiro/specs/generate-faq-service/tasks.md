# Implementation Plan: FAQ Generator Service

## Overview

Implement the `Faq_Generator` orchestration service that coordinates the FAQ generation pipeline: validate post ID → fetch post → read settings → build prompt → call AI provider → filter empty items → return FAQ array. The service uses dependency injection for testability and follows the existing plugin architecture patterns.

## Tasks

- [x] 1. Create the Faq_Generator service class
  - [x] 1.1 Create `includes/services/class-faq-generator.php` with the Faq_Generator class
    - Declare `strict_types=1` and namespace `WPBits\AiFaqGenerator\Includes\Services`
    - Import `WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface`
    - Define private properties `$ai_provider` (AIProviderInterface) and `$prompt_builder` (Prompt_Builder)
    - Implement constructor accepting AIProviderInterface as first param and Prompt_Builder as second param
    - Store injected dependencies as private properties
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 1.2 Implement the `generateFaqs(int $post_id): array` method
    - Validate `$post_id > 0`, throw `InvalidArgumentException` with message "Invalid post ID: {post_id}" if not
    - Call `get_post($post_id)`, throw `RuntimeException` with message "Post not found: {post_id}" if null
    - Check `post_status === 'publish'`, throw `RuntimeException` with message "Post is not published: {post_id}" if not
    - Read `get_option('afg_settings', [])` and extract `faq_count` (cast to int if non-null, pass null otherwise)
    - Call `$this->prompt_builder->build($post->post_title, $post->post_content, $faq_count)`
    - Call `$this->ai_provider->generateFaqs($prompt)` — let exceptions propagate
    - Filter out items where question or answer is empty or whitespace-only
    - Return re-indexed filtered array via `array_values()`
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 3.1, 3.4, 4.1, 4.2, 4.3, 4.4, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 1.3 Register the Faq_Generator class in the test bootstrap
    - Add `require_once` for `includes/services/class-faq-generator.php` in `tests/bootstrap.php`
    - Add a `get_post` stub (if not already present) that uses a global `$afg_test_posts` array to return mock WP_Post objects or null
    - Add a `WP_Post` class stub (if not already present) with public properties: `post_title`, `post_content`, `post_status`
    - _Requirements: 6.1, 6.2_

- [x] 2. Write example-based unit tests
  - [x] 2.1 Create `tests/unit/FaqGeneratorTest.php` with example-based tests
    - Test constructor accepts AIProviderInterface and Prompt_Builder mocks
    - Test null `faq_count` in settings passes null to Prompt_Builder
    - Test missing `afg_settings` option uses empty array default
    - Test empty title + empty content proceeds without exception
    - Test non-empty title + empty content proceeds without exception
    - Test empty title + non-empty content proceeds without exception
    - Test empty AI response returns empty array
    - Test valid FAQ array is returned unchanged
    - Test items with empty question/answer are filtered out
    - _Requirements: 2.3, 2.4, 4.3, 5.3, 5.4, 5.5_

- [x] 3. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Write property-based tests for input validation
  - [x] 4.1 Write property test for invalid post ID rejection
    - **Property 1: Invalid post ID rejection**
    - Use a data provider generating 100+ integers ≤ 0 (negative, zero, PHP_INT_MIN)
    - Assert `InvalidArgumentException` is thrown without calling `get_post()` or any dependency
    - **Validates: Requirements 1.4**

  - [x] 4.2 Write property test for non-existent post exception
    - **Property 2: Non-existent post throws with ID in message**
    - Use a data provider generating 100+ positive integers where `get_post()` returns null
    - Assert `RuntimeException` is thrown and message contains the string representation of the post ID
    - **Validates: Requirements 1.3, 5.2**

  - [x] 4.3 Write property test for non-published post rejection
    - **Property 3: Non-published post rejection**
    - Use a data provider generating 100+ posts with random non-"publish" statuses (draft, pending, trash, private, future, custom strings)
    - Assert `RuntimeException` is thrown without invoking Prompt_Builder or AI provider
    - **Validates: Requirements 1.5**

- [x] 5. Write property-based tests for data forwarding
  - [x] 5.1 Write property test for post data forwarding to Prompt_Builder
    - **Property 4: Post data forwarding to Prompt_Builder**
    - Use a data provider generating 100+ published posts with random title and content strings
    - Assert Prompt_Builder::build() receives exact `post_title` as first arg and exact `post_content` as second arg
    - **Validates: Requirements 1.2, 2.1**

  - [x] 5.2 Write property test for settings faq_count forwarding
    - **Property 5: Settings faq_count forwarding**
    - Use a data provider generating 100+ non-null integer faq_count values
    - Assert Prompt_Builder::build() receives the integer-cast value as third argument
    - **Validates: Requirements 2.2**

  - [x] 5.3 Write property test for prompt forwarding to AI provider
    - **Property 6: Prompt forwarding to AI provider**
    - Use a data provider generating 100+ random prompt strings returned by Prompt_Builder
    - Assert AIProviderInterface::generateFaqs() receives that exact string without modification
    - **Validates: Requirements 3.1**

- [x] 6. Write property-based tests for error handling and output
  - [x] 6.1 Write property test for AI provider exception propagation
    - **Property 7: AI provider exception propagation**
    - Use a data provider generating 100+ RuntimeExceptions with random messages
    - Assert the exception propagates to the caller with the original message unchanged
    - **Validates: Requirements 3.4, 5.1**

  - [x] 6.2 Write property test for valid FAQ passthrough
    - **Property 8: Valid FAQ passthrough**
    - Use a data provider generating 100+ FAQ arrays where all items have non-empty, non-whitespace question and answer
    - Assert the returned array is identical to the input array
    - **Validates: Requirements 4.1, 4.2**

  - [x] 6.3 Write property test for empty/whitespace item filtering
    - **Property 9: Empty/whitespace item filtering**
    - Use a data provider generating 100+ FAQ arrays containing a mix of valid and invalid items (empty/whitespace question or answer)
    - Assert invalid items are excluded and valid items are preserved in original order
    - **Validates: Requirements 4.4**

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The `get_post` and `WP_Post` stubs in the bootstrap enable testing without WordPress loaded
- All property tests use PHPUnit data providers with 100+ random iterations per property

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2"] },
    { "id": 2, "tasks": ["1.3"] },
    { "id": 3, "tasks": ["2.1"] },
    { "id": 4, "tasks": ["4.1", "4.2", "4.3"] },
    { "id": 5, "tasks": ["5.1", "5.2", "5.3"] },
    { "id": 6, "tasks": ["6.1", "6.2", "6.3"] }
  ]
}
```
