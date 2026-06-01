# Implementation Plan: Generate Button

## Overview

This plan implements the "Generate FAQs" button feature for the Gutenberg editor sidebar. The implementation follows the design's three-component architecture: a PHP AJAX handler, a React editor panel, and post meta registration. Tasks are ordered to build foundational pieces first (meta registration, AJAX handler) then the editor UI, and finally wire everything together.

## Tasks

- [x] 1. Register FAQ post meta and update Loader
  - [x] 1.1 Add `Ajax_Generate_Faqs` to the Loader autoload map and register FAQ post meta
    - Add `WPBits\AiFaqGenerator\Includes\Ajax_Generate_Faqs` entry to the `$classes` array in `includes/class-loader.php`
    - Add `register_faq_meta` method to `Loader` class hooked on `init` action
    - Implement `register_meta('post', '_aifaq_generated_faqs', ...)` with `type` string, `single` true, `show_in_rest` true
    - Implement `sanitize_faq_meta` callback: validate JSON is array of `{question, answer}` objects, return value if valid or empty string if invalid
    - Implement `auth_callback` that checks `edit_post` capability
    - Initialize `Ajax_Generate_Faqs` in `Loader::init()` (outside the `is_admin()` check since AJAX requests may not pass `is_admin()` early — or inside if appropriate for wp_ajax hooks)
    - _Requirements: 6.1, 6.2_

  - [x] 1.2 Write property test for FAQ meta sanitization (Property 3)
    - **Property 3: FAQ meta sanitization round-trip**
    - Generate random valid JSON strings (arrays of `{question, answer}` objects) and verify sanitize callback returns them unchanged
    - Generate random invalid JSON strings and non-conforming structures and verify sanitize callback returns empty string
    - Use PHPUnit data providers with 100+ iterations
    - **Validates: Requirements 6.2**

- [x] 2. Implement AJAX handler class
  - [x] 2.1 Create `includes/class-ajax-generate-faqs.php` with security checks
    - Create file with namespace `WPBits\AiFaqGenerator\Includes`
    - Implement `init()` method that registers `wp_ajax_aifaq_generate_faqs` action hook
    - Implement `handle()` method with nonce verification via `check_ajax_referer('aifaq_generate_faqs', '_ajax_nonce')`
    - On nonce failure: `wp_send_json_error(['message' => 'Security check failed.'], 403)`
    - Read `post_id` from `$_POST`, validate it is a positive integer; on failure: `wp_send_json_error(['message' => 'Post ID is required and must be a positive integer.'], 400)`
    - Check `current_user_can('edit_post', $post_id)`; on failure: `wp_send_json_error(['message' => 'You do not have permission to edit this post.'], 403)`
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 2.2 Add FAQ generation and meta storage to the AJAX handler
    - Instantiate `Faq_Generator` with `OpenAIClient` and `Prompt_Builder` dependencies
    - Call `$faq_generator->generateFaqs((int) $post_id)`
    - Catch `\InvalidArgumentException` → `wp_send_json_error(['message' => $e->getMessage()], 400)`
    - Catch `\RuntimeException` → `wp_send_json_error(['message' => $e->getMessage()], 500)`
    - On success: call `update_post_meta($post_id, '_aifaq_generated_faqs', wp_json_encode($faqs))`
    - If `update_post_meta` returns false: `wp_send_json_error(['message' => 'FAQ data could not be saved.'], 500)`
    - Return `wp_send_json_success(['faqs' => $faqs, 'count' => count($faqs)])`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.3, 6.4_

  - [x] 2.3 Write property test for invalid post_id rejection (Property 1)
    - **Property 1: Invalid post_id rejection**
    - Generate random invalid post_id values (zero, negative integers, non-numeric strings, null, empty strings)
    - Verify AJAX handler returns JSON error with HTTP status 400 for each
    - Use PHPUnit data providers with 100+ iterations
    - **Validates: Requirements 4.4, 5.5**

  - [x] 2.4 Write property test for successful response structure (Property 2)
    - **Property 2: Successful response structure invariant**
    - Generate random valid FAQ arrays (arrays of `{question, answer}` objects with non-empty strings)
    - Mock `Faq_Generator` to return the generated array
    - Verify response contains `faqs` key with exact array and `count` key equal to array length
    - Use PHPUnit data providers with 100+ iterations
    - **Validates: Requirements 5.2**

  - [x] 2.5 Write unit tests for AJAX handler
    - Test nonce verification failure returns 403
    - Test missing post_id returns 400
    - Test user without `edit_post` capability returns 403
    - Test successful generation returns correct response structure
    - Test `RuntimeException` from service returns 500
    - Test `InvalidArgumentException` from service returns 400
    - Test `update_post_meta` failure returns 500
    - _Requirements: 4.1–4.5, 5.1–5.6, 6.3, 6.4_

- [x] 3. Checkpoint - Ensure PHP tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Enqueue editor script in Admin class
  - [x] 4.1 Add `enqueue_editor_assets` method to the Admin class
    - Add `enqueue_block_editor_assets` hook in `Admin::init()` calling `enqueue_editor_assets()`
    - In `enqueue_editor_assets()`: check if `build/index.asset.php` exists, bail silently if not
    - Register script handle `aifaq-editor` with path `build/index.js`, dependencies and version from asset file
    - Localize script with `aifaqEditor` object containing `ajaxurl` (admin_url('admin-ajax.php')), `nonce` (wp_create_nonce('aifaq_generate_faqs')), and `postId` (get_the_ID())
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 4.2 Write unit tests for editor script enqueueing
    - Test script is registered with correct handle and dependencies when asset file exists
    - Test script is not registered when asset file does not exist
    - Test localized object contains correct keys and values
    - _Requirements: 2.1–2.5_

- [x] 5. Implement React editor panel
  - [x] 5.1 Create `src/editor/index.js` with registerPlugin
    - Create `src/editor/` directory
    - Create `src/editor/index.js` that imports `registerPlugin` from `@wordpress/plugins`
    - Import `EditorPanel` component
    - Call `registerPlugin('aifaq-editor-panel', { render: EditorPanel })`
    - _Requirements: 1.4_

  - [x] 5.2 Create `src/editor/EditorPanel.js` component
    - Import `PluginDocumentSettingPanel` from `@wordpress/editor`
    - Import `Button`, `Spinner` from `@wordpress/components`
    - Import `useEntityProp` from `@wordpress/core-data`
    - Import `useSelect` from `@wordpress/data`
    - Import `useState` from `@wordpress/element`
    - Import `dispatch` from `@wordpress/data` for `core/notices`
    - Implement component with `PluginDocumentSettingPanel` title "AI FAQ Generator"
    - Use `useSelect` to get current post type and check `custom-fields` support; return `null` if not supported
    - Use `useEntityProp` to read `_aifaq_generated_faqs` meta value
    - Render "Generate FAQs" button with `isBusy` and `disabled` props tied to `isLoading` state
    - Display "Generating..." text while loading
    - Display Spinner component while loading
    - On click: send AJAX POST to `aifaqEditor.ajaxurl` with action, nonce, and post_id from `wp.data.select('core/editor').getCurrentPostId()`
    - Set 30-second timeout on the request
    - On success: show success notice with count (auto-dismiss 5s), update local state
    - On error with `data.message`: show error notice (auto-dismiss 8s)
    - On error without `data.message`: show generic "FAQ generation failed." notice (auto-dismiss 8s)
    - On network error/timeout: show "Could not reach the server. Please try again." notice (auto-dismiss 8s)
    - Always restore button state on completion
    - Display FAQ count label below button when meta has valid data (e.g., "3 FAQs generated")
    - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 6.5, 6.6, 7.1, 7.2, 7.3, 7.4_

  - [x] 5.3 Import editor module from `src/index.js`
    - Add `import './editor';` to `src/index.js` to register the panel when the bundle loads
    - _Requirements: 1.4, 2.1_

  - [x] 5.4 Write unit tests for EditorPanel component
    - Test panel renders with title "AI FAQ Generator" and button text "Generate FAQs"
    - Test button click triggers AJAX with correct action, nonce, and post_id
    - Test loading state: spinner visible, button disabled, text "Generating..."
    - Test success response updates FAQ count display and dispatches success notice
    - Test error response with message dispatches error notice
    - Test error response without message dispatches generic error notice
    - Test network timeout dispatches server unreachable notice
    - Test existing meta displays FAQ count on initial load
    - Test panel does not render when post type lacks `custom-fields` support
    - _Requirements: 1.1–1.4, 3.1–3.7, 6.5, 6.6, 7.1–7.4_

- [x] 6. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The AJAX handler is initialized in the Loader (not gated by `is_admin()`) because WordPress AJAX requests route through `admin-ajax.php` which sets `is_admin()` to true, but it's safer to register early
- The editor panel uses `useEntityProp` for meta access via REST API, which requires `show_in_rest: true` on the meta registration

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "2.1", "4.1"] },
    { "id": 2, "tasks": ["2.2", "4.2"] },
    { "id": 3, "tasks": ["2.3", "2.4", "2.5", "5.1"] },
    { "id": 4, "tasks": ["5.2", "5.3"] },
    { "id": 5, "tasks": ["5.4"] }
  ]
}
```
