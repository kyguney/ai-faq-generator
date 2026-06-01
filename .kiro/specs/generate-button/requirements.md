# Requirements Document

## Introduction

The Generate Button feature adds a "Generate FAQs" button to the WordPress Gutenberg post editor sidebar. When clicked, the button triggers an AJAX request to the server, which invokes the existing Faq_Generator service to produce FAQ content from the current post. The generated FAQ data is stored as post meta using `register_meta()`. The feature includes loading state feedback during generation and nonce-based security verification for all AJAX requests.

## Glossary

- **Editor_Panel**: A React-based sidebar panel registered in the Gutenberg block editor using the `@wordpress/plugins` PluginDocumentSettingPanel component.
- **Generate_Button**: The UI button element within the Editor_Panel, labeled "Generate FAQs", that initiates the FAQ generation AJAX request.
- **AJAX_Handler**: The server-side PHP handler registered on the `wp_ajax_aifaq_generate_faqs` action hook that processes FAQ generation requests.
- **Editor_Script**: The JavaScript bundle enqueued via the `enqueue_block_editor_assets` hook, providing the sidebar panel and button functionality.
- **FAQ_Meta**: The post meta field (`_aifaq_generated_faqs`) storing the generated FAQ array as a JSON-encoded string, registered via `register_meta()`.
- **Nonce**: A WordPress security token created with `wp_create_nonce('aifaq_generate_faqs')` and verified with `check_ajax_referer()` to prevent CSRF attacks.
- **Loading_State**: A visual indicator (spinner and disabled button) displayed in the Editor_Panel while the AJAX request is in progress.
- **Faq_Generator**: The existing orchestration service (`includes/services/class-faq-generator.php`) that generates FAQ arrays from a post ID.

## Requirements

### Requirement 1: Register Editor Sidebar Panel

**User Story:** As a content editor, I want to see a FAQ generation panel in the post editor sidebar, so that I can generate FAQs without leaving the editor.

#### Acceptance Criteria

1. WHEN the Gutenberg block editor loads for a post or page whose post type supports the `custom-fields` feature, THE Editor_Panel SHALL render in the document sidebar as a registered PluginDocumentSettingPanel with the title "AI FAQ Generator".
2. THE Editor_Panel SHALL contain the Generate_Button displayed as an enabled button with the visible text "Generate FAQs".
3. IF the current post type does not support the `custom-fields` feature, THEN THE Editor_Panel SHALL NOT render in the document sidebar.
4. WHEN the Editor_Panel renders, THE Editor_Panel SHALL register its sidebar script using the `@wordpress/plugins` registerPlugin API so that the panel loads automatically without additional user action.

### Requirement 2: Enqueue Editor Script

**User Story:** As a plugin developer, I want the editor JavaScript to load only in the block editor context, so that it does not affect other admin pages.

#### Acceptance Criteria

1. THE Editor_Script SHALL be enqueued using the `enqueue_block_editor_assets` WordPress action hook with the script handle `aifaq-editor`.
2. WHEN the asset file at `build/index.asset.php` relative to the plugin root exists, THE Editor_Script SHALL register the script located at `build/index.js` relative to the plugin root, using the `dependencies` array and `version` string from the asset file as the script dependencies and version respectively.
3. THE Editor_Script SHALL receive a localized JavaScript object named `aifaqEditor` containing three properties: `ajaxurl` set to the WordPress `admin_url('admin-ajax.php')` value, `nonce` set to a nonce created with the action `aifaq_generate_faqs`, and `postId` set to the value of `get_the_ID()` at the time of enqueueing.
4. IF the asset file at `build/index.asset.php` relative to the plugin root does not exist, THEN THE plugin SHALL skip enqueueing the Editor_Script and SHALL NOT produce a PHP error or warning.
5. WHEN the Editor_Script is enqueued, THE plugin SHALL NOT enqueue the script on any WordPress admin page outside the block editor context.

### Requirement 3: Handle Generate Button Click

**User Story:** As a content editor, I want to click the "Generate FAQs" button and have FAQs generated from my post content, so that I can quickly add FAQ content to my post.

#### Acceptance Criteria

1. WHEN the Generate_Button is clicked, THE Editor_Panel SHALL send an AJAX POST request to the WordPress admin-ajax.php endpoint with the action `aifaq_generate_faqs`, the Nonce value from the `aifaqEditor` localized object using the field name `_ajax_nonce`, and the current post ID obtained from the WordPress data store via `wp.data.select('core/editor').getCurrentPostId()`.
2. WHEN the AJAX request is in progress, THE Generate_Button SHALL enter the Loading_State by displaying a spinner component and disabling the button to prevent duplicate requests.
3. WHEN the AJAX request completes successfully with a response containing `{data: {faqs: [...], count: N}}`, THE Editor_Panel SHALL exit the Loading_State, store the returned FAQ items for display in the editor, and show a success notice indicating the number of FAQ items generated that auto-dismisses after 5 seconds.
4. IF the AJAX request returns an error response containing `{data: {message: "..."}}`, THEN THE Editor_Panel SHALL exit the Loading_State and display an error notice containing the error message from the server response that auto-dismisses after 8 seconds.
5. WHILE the Generate_Button is in the Loading_State, THE Generate_Button SHALL display the text "Generating..." instead of "Generate FAQs".
6. IF the AJAX request fails due to a network error or does not receive a response within 30 seconds, THEN THE Editor_Panel SHALL exit the Loading_State and display an error notice indicating that the server could not be reached.
7. IF the AJAX request returns an error response that does not contain a `data.message` field, THEN THE Editor_Panel SHALL display a generic error notice indicating that FAQ generation failed.

### Requirement 4: AJAX Handler Security

**User Story:** As a plugin developer, I want the AJAX handler to verify the request authenticity, so that unauthorized requests are rejected.

#### Acceptance Criteria

1. WHEN the AJAX_Handler receives a request, THE AJAX_Handler SHALL verify the Nonce by calling `check_ajax_referer()` with the action name `aifaq_generate_faqs` and the nonce field name `_ajax_nonce` as the first security check before any other processing.
2. IF the Nonce verification fails, THEN THE AJAX_Handler SHALL return a JSON error response using `wp_send_json_error()` with HTTP status 403 and a message indicating the security check failed.
3. WHEN the Nonce verification succeeds, THE AJAX_Handler SHALL read the `post_id` parameter from the request and verify that the current user has the `edit_post` capability for that post ID using `current_user_can( 'edit_post', $post_id )`.
4. IF the `post_id` parameter is missing from the request or is not a positive integer, THEN THE AJAX_Handler SHALL return a JSON error response using `wp_send_json_error()` with HTTP status 400 and a message indicating the post ID is invalid.
5. IF the current user does not have the `edit_post` capability for the requested post ID, THEN THE AJAX_Handler SHALL return a JSON error response using `wp_send_json_error()` with HTTP status 403 and a message indicating insufficient permissions.

### Requirement 5: AJAX Handler FAQ Generation

**User Story:** As a plugin developer, I want the AJAX handler to invoke the Faq_Generator service and return the results, so that the editor receives generated FAQ data.

#### Acceptance Criteria

1. WHEN the AJAX_Handler receives a request with a valid nonce and the requesting user has the `edit_post` capability for the given post, THE AJAX_Handler SHALL extract the post_id parameter from the POST data, cast it to an integer, and pass it to the Faq_Generator `generateFaqs()` method.
2. WHEN the Faq_Generator returns a FAQ array, THE AJAX_Handler SHALL return a `wp_send_json_success` response containing the FAQ array under a `faqs` key and the count of generated items under a `count` key.
3. IF the Faq_Generator throws a RuntimeException, THEN THE AJAX_Handler SHALL return a `wp_send_json_error` response with HTTP status 500 and a message containing the exception's getMessage() value.
4. IF the Faq_Generator throws an InvalidArgumentException, THEN THE AJAX_Handler SHALL return a `wp_send_json_error` response with HTTP status 400 and a message containing the exception's getMessage() value.
5. IF the post_id parameter is missing or empty in the POST data, THEN THE AJAX_Handler SHALL return a `wp_send_json_error` response with HTTP status 400 and a message indicating the post ID is required.
6. THE AJAX_Handler SHALL instantiate the Faq_Generator with an OpenAIClient instance as the AIProviderInterface dependency and a Prompt_Builder instance as the prompt builder dependency.

### Requirement 6: Store FAQ Data as Post Meta

**User Story:** As a content editor, I want generated FAQs to be saved to the post, so that the data persists and can be used by other plugin features.

#### Acceptance Criteria

1. THE plugin SHALL register the `_aifaq_generated_faqs` post meta key using `register_meta()` with `object_type` set to `post`, `type` set to `string`, `single` set to `true`, `show_in_rest` set to `true`, a sanitize callback, and an `auth_callback` that checks the `edit_post` capability for the given post.
2. WHEN the sanitize callback receives a value, THE plugin SHALL verify the value is a valid JSON string representing an array of objects each containing "question" and "answer" string keys, and SHALL return the value unchanged if valid or return an empty string if the JSON is malformed or does not match the expected structure.
3. WHEN the Faq_Generator returns a FAQ array successfully, THE AJAX_Handler SHALL store the FAQ array as a JSON-encoded string in the `_aifaq_generated_faqs` post meta field using `update_post_meta()`, overwriting any previously stored value.
4. IF the `update_post_meta()` call returns false, THEN THE AJAX_Handler SHALL return a JSON error response with HTTP status 500 and a message indicating the FAQ data could not be saved.
5. WHEN the Editor_Panel loads, THE Editor_Panel SHALL read the existing FAQ_Meta value from the post meta via the REST API, parse the JSON string, and display the count of FAQ items as a text label below the Generate_Button (e.g., "3 FAQs generated").
6. IF the Editor_Panel reads a FAQ_Meta value that is empty or not valid JSON, THEN THE Editor_Panel SHALL display no FAQ count indicator and treat the state as having no previously generated FAQs.

### Requirement 7: Display Loading State

**User Story:** As a content editor, I want to see visual feedback while FAQs are being generated, so that I know the process is working and can wait for it to complete.

#### Acceptance Criteria

1. WHILE the AJAX request is in progress, THE Editor_Panel SHALL display a WordPress Spinner component immediately after the Generate_Button and SHALL set the Generate_Button `isBusy` prop to `true` to activate the built-in loading indicator.
2. WHILE the AJAX request is in progress, THE Generate_Button SHALL have its `disabled` attribute set to `true`, preventing additional clicks.
3. WHEN the AJAX request completes with either a success or error response, THE Editor_Panel SHALL remove the Spinner component, set the Generate_Button `isBusy` prop to `false`, set the Generate_Button `disabled` attribute to `false`, and restore the Generate_Button text to "Generate FAQs".
4. WHILE the AJAX request is in progress, THE Generate_Button SHALL display the text "Generating..." to indicate active processing.
