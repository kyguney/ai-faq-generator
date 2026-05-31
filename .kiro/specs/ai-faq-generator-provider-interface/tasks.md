# Implementation Plan: AI Provider Interface

## Overview

Implement the `AIProviderInterface` PHP interface for the AI FAQ Generator plugin, register it with the existing autoloader, and verify the contract through property-based and unit tests. This establishes the pluggable provider architecture that future provider implementations (OpenAI, OpenRouter, etc.) will build upon.

## Tasks

- [x] 1. Create the AIProviderInterface file
  - [x] 1.1 Create `includes/interfaces/class-ai-provider-interface.php`
    - Add `declare(strict_types=1)` at the top
    - Use namespace `WPBits\AiFaqGenerator\Includes\Interfaces`
    - Declare the interface with `generateFaqs(string $prompt): array` and `testConnection(): bool` methods
    - Include full PHPDoc blocks on the interface and both methods as specified in the design
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3_

- [x] 2. Register interface in the Loader class map
  - [x] 2.1 Update `includes/class-loader.php` to add the interface to the `$classes` array
    - Add entry: `'WPBits\\AiFaqGenerator\\Includes\\Interfaces\\AIProviderInterface' => AFG_PLUGIN_PATH . 'includes/interfaces/class-ai-provider-interface.php'`
    - _Requirements: 5.1, 5.2_

- [x] 3. Checkpoint - Verify interface loads correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Write tests for the interface contract
  - [x] 4.1 Write property test for interface contract enforcement
    - **Property 1: Interface contract enforcement**
    - Generate 100+ random class stubs via data provider and verify via reflection that implementing AIProviderInterface requires both `generateFaqs` and `testConnection` with correct signatures
    - **Validates: Requirements 1.1, 1.2, 4.1**

  - [x] 4.2 Write property test for FAQ output structure invariant
    - **Property 2: FAQ output structure invariant**
    - Create a mock provider implementing AIProviderInterface, generate 100+ random valid prompt strings, call `generateFaqs`, and verify every returned element has `question` (string) and `answer` (string) keys
    - **Validates: Requirements 4.2**

  - [x] 4.3 Write property test for testConnection failure safety
    - **Property 3: testConnection never throws on failure**
    - Create a mock provider that simulates 100+ random failure conditions and verify `testConnection` returns `false` without throwing exceptions
    - **Validates: Requirements 4.5**

  - [x] 4.4 Write unit tests for interface structure
    - Verify file exists at `includes/interfaces/class-ai-provider-interface.php`
    - Verify namespace is correct via reflection
    - Verify PHPDoc blocks exist on interface and methods
    - Verify `declare(strict_types=1)` is present in the file
    - Verify Loader class map contains the interface entry
    - _Requirements: 1.3, 1.4, 2.1, 2.2, 2.3, 3.3, 5.1_

- [x] 5. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- The interface file follows the same coding conventions as existing classes (strict_types, namespace, PHPDoc)
- Property tests use PHPUnit 11 with `#[DataProvider]` attributes, matching the existing `LoaderPropertyTest` pattern
- Future provider implementations (OpenAI, OpenRouter, etc.) will be separate specs that build on this interface
