# Implementation Plan: Settings Storage

## Overview

Implement a React-based settings page for the AI FAQ Generator plugin. The backend uses a PHP `Settings` class as a REST controller with input sanitization. The frontend is a separate Webpack entry point using `@wordpress/components`. Tasks follow dependency order: PHP backend first, then Loader integration, Admin modifications, and finally the React frontend.

## Tasks

- [x] 1. Create the Settings PHP class (REST controller + sanitizer)
  - [x] 1.1 Create `admin/class-settings.php` with constants and sanitize logic
    - Define `OPTION_KEY`, `REST_NAMESPACE`, `REST_ROUTE`, `DEFAULTS`, `ALLOWED_PROVIDERS` constants
    - Implement `sanitize(array $input): array` — validate provider against allowed list, clamp temperature to [0.0, 2.0], clamp faq_count to [1, 20], apply `sanitize_text_field()` to model and api_key, reject empty/whitespace model
    - Implement `mask_api_key(string $key): string` — show first 3 + last 4 chars, mask middle with `****`
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 4.4_

  - [x] 1.2 Add REST route registration and endpoint handlers
    - Implement `init()` to hook `register_routes` on `rest_api_init`
    - Implement `register_routes()` with GET and POST methods on `ai-faq-generator/v1/settings`
    - Implement `permission_check()` verifying `manage_options` capability
    - Implement `get_settings()` — merge defaults with stored option, mask api_key, add `has_api_key` boolean
    - Implement `update_settings()` — sanitize input, merge with existing (preserve api_key if not sent), persist via `update_option()`, return masked response
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.1, 4.2, 4.4, 4.5_

  - [x] 1.3 Write property tests for the sanitizer
    - **Property 2: Invalid provider rejection** — For any string not in ALLOWED_PROVIDERS, sanitizer retains previous provider
    - **Property 3: Numeric field clamping** — For any numeric input, temperature is clamped to [0.0, 2.0] and faq_count to [1, 20]
    - **Property 4: Whitespace model rejection** — For any whitespace-only string, sanitizer retains previous model
    - **Property 5: API key masking** — For any key of length ≥ 7, masked output shows only first 3 + last 4 chars
    - **Property 6: Text field sanitization** — For any string with HTML/script tags, sanitizer strips them
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 4.4**

  - [x] 1.4 Write unit tests for Settings REST handlers
    - Test GET returns defaults when no option exists
    - Test GET returns merged settings with masked api_key
    - Test POST persists sanitized values
    - Test permission_check rejects users without manage_options
    - _Requirements: 1.1, 1.2, 1.4, 4.1, 4.2_

- [x] 2. Integrate Settings class into the Loader
  - [x] 2.1 Modify `includes/class-loader.php` to register and initialize Settings
    - Add `WPBits\AiFaqGenerator\Admin\Settings` to the `$classes` autoload map
    - Instantiate `Settings` and call `$settings->init()` inside the `init()` method (within `is_admin()` block)
    - _Requirements: 1.1, 1.2_

- [x] 3. Checkpoint — Verify PHP backend
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Modify Admin class for settings page assets
  - [x] 4.1 Update `render_settings_page()` to output the React mount point
    - Replace the existing form HTML with a `<div id="afg-settings-root"></div>` wrapper
    - Keep the `<div class="wrap">` and `<h1>` heading
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

  - [x] 4.2 Add `enqueue_settings_assets()` method to Admin class
    - Hook into `admin_enqueue_scripts`
    - Check `$hook_suffix === 'ai-faq_page_ai-faq-generator-settings'` — bail early if not
    - Enqueue `build/settings.js` (with `build/settings.asset.php` dependencies) and `build/settings.css`
    - Use `wp_localize_script()` to pass `afgSettings` object with `restUrl` (rest_url + namespace) and `nonce` (wp_create_nonce('wp_rest'))
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 4.3 Write unit tests for Admin enqueue logic
    - Test assets are enqueued when hook_suffix matches settings page
    - Test assets are NOT enqueued for other admin pages
    - _Requirements: 6.1, 6.2_

- [x] 5. Create the React settings entry point and component
  - [x] 5.1 Create `src/settings/index.js` entry point
    - Import `render` from `@wordpress/element`
    - Import `SettingsPage` component
    - Render `<SettingsPage />` into `#afg-settings-root`
    - _Requirements: 5.1_

  - [x] 5.2 Create `src/settings/SettingsPage.js` component
    - Use `useState` for settings object, `isSaving`, and `notice` state
    - On mount (`useEffect`), call `apiFetch` GET to load current settings
    - Render `SelectControl` for provider (options: OpenAI, OpenRouter, Ollama, DeepSeek, LocalAI, LM Studio)
    - Render `TextControl` type="password" for API key (placeholder when `has_api_key` is true)
    - Render `TextControl` for model
    - Render `RangeControl` for temperature (min 0, max 2, step 0.1)
    - Render `RangeControl` for faq_count (min 1, max 20, step 1)
    - On submit, POST settings via `apiFetch`, show success/error `Notice`
    - Disable submit `Button` and show `Spinner` while saving
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 4.3, 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 6. Configure Webpack build for the settings entry point
  - [x] 6.1 Update `package.json` or add `webpack.config.js` for multi-entry build
    - Add a custom `webpack.config.js` that extends `@wordpress/scripts` default config
    - Define two entry points: `index` (existing `src/index.js`) and `settings` (`src/settings/index.js`)
    - This produces `build/settings.js`, `build/settings.css`, and `build/settings.asset.php`
    - _Requirements: 6.1_

- [x] 7. Final checkpoint — Full integration verification
  - Ensure all tests pass, ask the user if questions arise.
  - Verify `npm run build` produces both `build/index.js` and `build/settings.js` bundles

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "1.4"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["4.1", "4.2", "4.3", "6.1"] },
    { "id": 3, "tasks": ["5.1", "5.2"] }
  ]
}
```

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Property tests use PHPUnit `DataProvider` pattern with 100+ generated inputs (matching existing project convention)
- The React app depends on the REST endpoint being functional (tasks 1–3 must complete before task 5 can be tested end-to-end)
- The existing `register_settings()` and old form in `render_settings_page()` will be replaced — the React app + REST API replaces the native WordPress Settings API approach
