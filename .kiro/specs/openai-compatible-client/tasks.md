# Implementation Plan: OpenAI Compatible Client

## Overview

Implement the `OpenAIClient` class that communicates with any OpenAI-compatible chat completions endpoint, extend the `Settings` class with a `base_url` field, register the new class in the `Loader`, and validate correctness with property-based tests using PHPUnit data providers.

## Tasks

- [x] 1. Extend Settings class with base_url field
  - [x] 1.1 Add `base_url` to the `DEFAULTS` constant with default value `https://api.openai.com`
    - Modify `admin/class-settings.php`
    - _Requirements: 5.1_
  - [x] 1.2 Add `base_url` sanitization logic in the `sanitize()` method
    - Use `esc_url_raw()` to validate the URL
    - Reject empty or invalid URLs by retaining the previous stored value
    - _Requirements: 5.2_
  - [x] 1.3 Add `base_url` to `get_settings()` and `update_settings()` response arrays
    - Include `base_url` in the REST API response so the frontend can read/write it
    - _Requirements: 5.1, 5.2_

- [x] 2. Create OpenAIClient class
  - [x] 2.1 Create `includes/class-openai-client.php` with constructor
    - Declare `strict_types=1`, namespace `WPBits\AiFaqGenerator\Includes`
    - Implement `AIProviderInterface`
    - Constructor reads `api_key`, `model`, `temperature`, `base_url` from `afg_settings` option merged with defaults
    - Strip trailing slash from `base_url`
    - _Requirements: 5.3, 5.4_
  - [x] 2.2 Implement `getEndpointUrl()` private method
    - Return `base_url` (already trimmed) appended with `/v1/chat/completions`
    - _Requirements: 1.1, 5.4_
  - [x] 2.3 Implement `buildHeaders()` private method
    - Return array with `Authorization: Bearer {api_key}` and `Content-Type: application/json`
    - _Requirements: 1.4_
  - [x] 2.4 Implement `buildRequestBody(string $prompt)` private method
    - Return array with `model`, `messages` (single user message with prompt), and `temperature`
    - _Requirements: 1.2, 1.3_
  - [x] 2.5 Implement `sendRequest(array $body)` private method
    - Call `wp_remote_post` with URL, headers, JSON-encoded body, and 30s timeout
    - Check for `WP_Error` → throw `RuntimeException` with WP_Error message
    - Check HTTP status code → throw `RuntimeException` with code and error message if not 2xx
    - Decode JSON body → throw `RuntimeException` if invalid JSON
    - Return decoded response array
    - _Requirements: 1.5, 3.1, 3.2, 3.3_
  - [x] 2.6 Implement `parseResponse(array $response)` private method
    - Extract `choices[0].message.content` from decoded response
    - Throw `RuntimeException` if path is missing
    - _Requirements: 2.1, 3.4_
  - [x] 2.7 Implement `parseFaqItems(string $content)` private method
    - JSON-decode the content string
    - Validate it is an array where each element has non-empty `question` and `answer` string keys
    - Throw `RuntimeException` if structure is invalid
    - Return validated FAQ array
    - _Requirements: 2.2, 2.3, 3.5_
  - [x] 2.8 Implement `generateFaqs(string $prompt)` public method
    - Orchestrate: buildRequestBody → sendRequest → parseResponse → parseFaqItems
    - _Requirements: 1.1, 2.1, 2.2, 2.3_
  - [x] 2.9 Implement `testConnection()` public method
    - Build minimal request body with `max_tokens=1` and prompt `"Hi"`
    - Wrap in try/catch(\Throwable) → return false on any exception, true on success
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 3. Register OpenAIClient in Loader
  - [x] 3.1 Add class map entry in `includes/class-loader.php`
    - Map `WPBits\AiFaqGenerator\Includes\OpenAIClient` to `includes/class-openai-client.php`
    - _Requirements: 6.1_

- [x] 4. Checkpoint — Verify core implementation
  - Ensure all existing tests still pass with `./vendor/bin/phpunit`
  - Verify the new class file is loadable and implements `AIProviderInterface`
  - Ask the user if questions arise.

- [x] 5. Add test infrastructure for wp_remote_post mocking
  - [x] 5.1 Add WordPress HTTP API function stubs to `tests/bootstrap.php`
    - Add `wp_remote_post` stub with global capture/return mechanism (`$afg_test_wp_remote_post_args`, `$afg_test_wp_remote_post_return`)
    - Add `wp_remote_retrieve_response_code` stub
    - Add `wp_remote_retrieve_body` stub
    - Add `esc_url_raw` stub (basic URL validation)
    - Add `WP_Error` class stub with `get_error_message()` method (if not already present)
    - _Requirements: 1.1, 3.1_
  - [x] 5.2 Add `require_once` for `includes/class-openai-client.php` in bootstrap
    - Ensure the OpenAIClient class is available in tests
    - _Requirements: 6.1_

- [x] 6. Property-based test — Request structure invariant (Property 1)
  - [x] 6.1 Create `tests/unit/OpenAIClientRequestPropertyTest.php`
    - **Property 1: Request structure invariant**
    - **Validates: Requirements 1.2, 1.3, 1.4, 1.5**
    - Generate 100+ random configurations (model strings, temperatures in [0.0, 2.0], api_key strings, prompt strings)
    - Mock `wp_remote_post` to capture arguments and return a valid response
    - For each iteration verify:
      - Body contains `model`, `messages`, and `temperature` fields
      - `messages` is a single-element array with `role`=`"user"` and `content`=prompt
      - Headers contain `Authorization`=`"Bearer {api_key}"` and `Content-Type`=`"application/json"`
      - Timeout is 30
    - Use `#[DataProvider('configProvider')]` attribute

- [x] 7. Property-based test — Response parsing correctness (Property 2)
  - [x] 7.1 Create `tests/unit/OpenAIClientResponsePropertyTest.php`
    - **Property 2: Response parsing correctness**
    - **Validates: Requirements 2.1, 2.2, 2.3**
    - Generate 100+ random FAQ arrays (varying count 1–20, random question/answer strings)
    - Wrap each in a valid API response format: `{"choices":[{"message":{"content":"[...]"}}]}`
    - Mock `wp_remote_post` to return the wrapped response with 200 status
    - Verify parsed output matches the input FAQ array structure exactly
    - Use `#[DataProvider('faqResponseProvider')]` attribute

- [x] 8. Property-based test — Error handling completeness (Property 3)
  - [x] 8.1 Create `tests/unit/OpenAIClientErrorPropertyTest.php`
    - **Property 3: Error handling completeness**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
    - Generate 100+ error conditions across categories:
      - `WP_Error` objects with random error messages
      - Non-2xx HTTP status codes (400, 401, 403, 404, 429, 500, 502, 503)
      - Invalid JSON response bodies (random non-JSON strings)
      - Valid JSON but missing `choices[0].message.content` path
      - Valid response structure but invalid FAQ content (not array, missing keys, empty values)
    - Verify `RuntimeException` is thrown with a descriptive message for each case
    - Use `#[DataProvider('errorConditionProvider')]` attribute

- [x] 9. Property-based test — testConnection never throws (Property 4)
  - [x] 9.1 Create `tests/unit/OpenAIClientTestConnectionPropertyTest.php`
    - **Property 4: testConnection never throws**
    - **Validates: Requirements 4.2, 4.3**
    - Generate 100+ failure conditions (WP_Error, non-2xx codes, invalid JSON, exceptions)
    - Verify `testConnection()` returns `false` without throwing for each failure
    - Also test success case: mock returns valid 200 response → returns `true`
    - Use `#[DataProvider('failureConditionProvider')]` attribute

- [x] 10. Property-based test — URL construction (Property 5)
  - [x] 10.1 Create `tests/unit/OpenAIClientUrlPropertyTest.php`
    - **Property 5: URL construction**
    - **Validates: Requirements 1.1, 5.4**
    - Generate 100+ base_url variants:
      - With and without trailing slash
      - Various domains (localhost, custom domains, IP addresses)
      - Various ports (8080, 11434, 443)
      - Various path prefixes (`/api`, `/v1/proxy`)
    - Mock `wp_remote_post` to capture the URL argument
    - Verify endpoint URL equals `rtrim(base_url, '/') . '/v1/chat/completions'`
    - Use `#[DataProvider('baseUrlProvider')]` attribute

- [x] 11. Final checkpoint — Ensure all tests pass
  - Run `./vendor/bin/phpunit` and verify all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Task Dependency Graph

```json
{
  "waves": [
    {
      "wave": 1,
      "tasks": ["1"],
      "description": "Extend Settings with base_url field"
    },
    {
      "wave": 2,
      "tasks": ["2"],
      "description": "Create OpenAIClient class (depends on Settings having base_url)"
    },
    {
      "wave": 3,
      "tasks": ["3"],
      "description": "Register OpenAIClient in Loader"
    },
    {
      "wave": 4,
      "tasks": ["4"],
      "description": "Checkpoint — verify core implementation"
    },
    {
      "wave": 5,
      "tasks": ["5"],
      "description": "Add test infrastructure for wp_remote_post mocking"
    },
    {
      "wave": 6,
      "tasks": ["6", "7", "8", "9", "10"],
      "description": "Property-based tests (all independent, depend on test infrastructure)"
    },
    {
      "wave": 7,
      "tasks": ["11"],
      "description": "Final checkpoint — ensure all tests pass"
    }
  ]
}
```

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests validate universal correctness properties from the design document
- The test bootstrap already provides `get_option`/`update_option` stubs; only HTTP API stubs need to be added
- All PHP files must have `declare(strict_types=1)` and proper namespaces
- Tests use PHPUnit 11 `#[DataProvider('...')]` attributes (not `@dataProvider` annotations)
