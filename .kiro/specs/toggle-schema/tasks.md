# Implementation Plan: Toggle Schema

## Overview

Add an `enable_schema` boolean setting to the AI FAQ Generator plugin that controls whether FAQPage JSON-LD structured data is output in the page head. The implementation modifies the Settings class (defaults, sanitization, REST response), the Loader (conditional initialization), and the React SettingsPage (checkbox control). Tests validate sanitization properties, conditional initialization, and UI behavior.

## Tasks

- [x] 1. Add `enable_schema` to Settings class
  - [x] 1.1 Add `enable_schema` to DEFAULTS and sanitize method
    - Add `'enable_schema' => true` to the `DEFAULTS` constant in `admin/class-settings.php`
    - Add sanitization logic in `sanitize()` using `array_key_exists` to detect `null` values, casting to `(bool)` when present, preserving current value when omitted
    - _Requirements: 1.1, 1.3, 1.4, 2.4, 2.5_

  - [x] 1.2 Expose `enable_schema` in REST GET and POST responses
    - In `get_settings()`, include `'enable_schema' => (bool) $settings['enable_schema']` in the response array
    - In `update_settings()`, include `'enable_schema' => (bool) $sanitized['enable_schema']` in the response `settings` array
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 1.3 Write property test: Sanitizer always produces boolean `enable_schema`
    - **Property 1: Sanitizer always produces boolean `enable_schema`**
    - Create `tests/unit/EnableSchemaTypePropertyTest.php`
    - Generate 100+ random inputs (strings, integers, floats, null, arrays, booleans) and verify the sanitized output always has `enable_schema` as PHP `bool` type
    - **Validates: Requirements 1.1, 2.1**

  - [x] 1.4 Write property test: Boolean casting matches PHP rules
    - **Property 2: Boolean casting matches PHP rules**
    - Create `tests/unit/EnableSchemaCastingPropertyTest.php`
    - Generate 100+ random values and verify `sanitize()` output for `enable_schema` equals `(bool) $input_value`
    - **Validates: Requirements 1.3, 1.4, 2.5**

  - [x] 1.5 Write property test: Omitted field preserves current stored value
    - **Property 3: Omitted field preserves current stored value**
    - Create `tests/unit/EnableSchemaPreservationPropertyTest.php`
    - Generate 100+ random stored boolean states and verify that input arrays omitting `enable_schema` produce output with the previously stored value intact
    - **Validates: Requirements 2.4**

  - [x] 1.6 Write unit tests for REST GET/POST with `enable_schema`
    - Test GET returns `enable_schema: true` when key is absent from stored option
    - Test POST with `enable_schema: false` persists and returns `false`
    - Test POST with `enable_schema: true` persists and returns `true`
    - Test POST omitting `enable_schema` retains current stored value
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 2. Checkpoint - Verify Settings backend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Conditionally initialize JSON_LD_Generator in Loader
  - [x] 3.1 Modify Loader to conditionally init JSON_LD_Generator
    - In `includes/class-loader.php`, before calling `$json_ld_generator->init()`, read `afg_settings` via `get_option` and check `enable_schema`
    - If `enable_schema` is explicitly `false`, skip `JSON_LD_Generator::init()` call
    - If key is absent or `true`, proceed with initialization (backward compatible)
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 3.2 Write property test: Conditional initialization correctness
    - **Property 4: Conditional initialization correctness**
    - Create `tests/unit/EnableSchemaInitPropertyTest.php`
    - Generate 100+ random option states (true, false, absent, truthy/falsy values) and verify JSON_LD_Generator hook is registered if and only if `enable_schema` is `true` or absent
    - **Validates: Requirements 4.1, 4.2, 4.3**

- [x] 4. Add checkbox control to React SettingsPage
  - [x] 4.1 Add CheckboxControl for `enable_schema` to the settings form
    - Import `CheckboxControl` from `@wordpress/components` in `src/settings/SettingsPage.js`
    - Add `enable_schema: true` to the default state
    - Render a `CheckboxControl` labeled "Enable FAQ Schema" with help text, bound to `settings.enable_schema`
    - Include `enable_schema` in the POST payload within `handleSubmit`
    - _Requirements: 3.1, 3.2, 3.3_

  - [x] 4.2 Write Jest tests for the checkbox UI
    - Create `src/settings/__tests__/SettingsPage.test.js` (or extend existing test file)
    - Test checkbox renders with label "Enable FAQ Schema"
    - Test checkbox reflects `enable_schema` state from API response (checked when true, unchecked when false)
    - Test form submission includes `enable_schema` in POST payload
    - Test error notice displayed on API failure with checkbox state preserved
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 4.3 Write fast-check property test for checkbox state reflection
    - For any boolean value returned by the API, verify the checkbox `checked` attribute matches that value
    - Use `fc.assert(property, { numRuns: 100 })`
    - _Requirements: 3.2_

- [x] 5. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- PHP property tests use PHPUnit DataProvider pattern with 100+ iterations
- JS property tests use fast-check with 100+ runs
- The JSON_LD_Generator class itself requires no modifications — conditional logic lives in the Loader

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "3.1"] },
    { "id": 2, "tasks": ["1.3", "1.4", "1.5", "1.6", "3.2", "4.1"] },
    { "id": 3, "tasks": ["4.2", "4.3"] }
  ]
}
```
