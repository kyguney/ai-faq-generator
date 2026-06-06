# Requirements Document

## Introduction

The block-insert-state feature introduces a state machine to the AI FAQ Generator sidebar panel that tracks whether FAQ blocks have been inserted into the post content. After insertion, the sidebar transitions from showing "X FAQs generated" to a "block inserted" state, preventing duplicate blocks on regeneration and providing contextual actions (Edit Block, Clear & Start Over). This resolves the current bug where regenerating and inserting after an initial insert creates duplicate FAQ accordion blocks.

## Glossary

- **Editor_Panel**: The AI FAQ Generator sidebar panel component rendered in the WordPress block editor document settings area (`EditorPanel.js`).
- **FAQ_Block**: A `wpbits/faq-accordion` block instance in the post content containing FAQ items as structured data.
- **Post_Meta**: The `_aifaq_generated_faqs` post meta field storing generated FAQ JSON before block insertion.
- **Sidebar_State**: The current UI state of the Editor_Panel, one of: `empty`, `has_faqs`, or `block_inserted`.
- **Block_Detection**: The process of checking whether the post content contains an existing FAQ_Block on page load.
- **Regenerate_Action**: The action of generating new FAQs via the AI service and either adding them to meta or replacing an existing FAQ_Block depending on the current Sidebar_State.
- **Clear_Action**: The action of resetting the Editor_Panel to the `empty` state and removing the FAQ_Block reference.

## Requirements

### Requirement 1: Sidebar State Machine

**User Story:** As a content editor, I want the sidebar to reflect whether FAQs have been inserted as blocks, so that I understand the current state of my FAQ content and can take appropriate actions.

#### Acceptance Criteria

1. THE Editor_Panel SHALL maintain a Sidebar_State with exactly three possible values: `empty`, `has_faqs`, and `block_inserted`, where the initial state on load is determined by evaluating Post_Meta and post content as described in criteria 2–4
2. WHEN the Editor_Panel loads and Post_Meta contains valid FAQ JSON and no FAQ_Block exists in the post content, THE Editor_Panel SHALL display the `has_faqs` state showing the number of parsed FAQ items (e.g., "N FAQs generated") and a Generate button to regenerate FAQs
3. WHEN the Editor_Panel loads and the post content contains at least one FAQ_Block, THE Editor_Panel SHALL display the `block_inserted` state showing a confirmation message indicating FAQs have been inserted and a Generate button to regenerate FAQs, regardless of Post_Meta content
4. WHEN the Editor_Panel loads and Post_Meta is empty and no FAQ_Block exists in the post content, THE Editor_Panel SHALL display the `empty` state with only the Generate button
5. WHEN a FAQ_Block is inserted into the post content while the Editor_Panel is mounted, THE Editor_Panel SHALL transition from the current state to the `block_inserted` state without requiring a page reload

### Requirement 2: Post-Insert State Transition

**User Story:** As a content editor, I want the sidebar to update after I insert FAQ blocks, so that I know the insertion succeeded and I see relevant post-insert actions.

#### Acceptance Criteria

1. WHEN a FAQ_Block is successfully inserted into the post content via `dispatch('core/block-editor').insertBlocks(blocks)`, THE Editor_Panel SHALL transition the Sidebar_State to `block_inserted` within 500 milliseconds of the insertion call completing without error
2. WHEN a FAQ_Block is successfully inserted into the post content, THE Editor_Panel SHALL set the `_aifaq_generated_faqs` Post_Meta value to an empty string (`""`)
3. WHILE the Sidebar_State is `block_inserted`, THE Editor_Panel SHALL display a success indicator with the text "1 FAQ Block inserted"
4. WHILE the Sidebar_State is `block_inserted`, THE Editor_Panel SHALL display an "Edit Block" button and a "Clear & Start Over" button
5. WHILE the Sidebar_State is `block_inserted`, THE Editor_Panel SHALL hide the "Generate FAQs" button and the FAQ count text
6. WHEN the user clicks the "Clear & Start Over" button, THE Editor_Panel SHALL transition the Sidebar_State back to the initial state, clearing the success indicator and restoring the "Generate FAQs" button
7. IF the `dispatch('core/block-editor').insertBlocks(blocks)` call throws an error, THEN THE Editor_Panel SHALL remain in the current state without transitioning to `block_inserted` and SHALL display an error message indicating the insertion failed

### Requirement 3: Edit Block Navigation

**User Story:** As a content editor, I want to navigate directly to the inserted FAQ block, so that I can edit its content without scrolling through the post.

#### Acceptance Criteria

1. WHEN the user clicks the "Edit Block" button, THE Editor_Panel SHALL select the FAQ_Block in the block editor using the stored clientId reference, causing the editor canvas to scroll the block into the visible viewport
2. IF the user clicks the "Edit Block" button and the FAQ_Block referenced by the stored clientId no longer exists in the post content, THEN THE Editor_Panel SHALL transition the Sidebar_State to `empty`, remove the stored clientId reference, and display a snackbar notice indicating that the block was removed
3. WHILE the Sidebar_State is `empty` (no FAQ_Block has been inserted or the previously inserted block was removed), THE Editor_Panel SHALL hide the "Edit Block" button

### Requirement 4: Clear and Start Over

**User Story:** As a content editor, I want to reset the FAQ state and start fresh, so that I can generate entirely new FAQs without leftover state.

#### Acceptance Criteria

1. WHILE the Editor_Panel has a non-empty `_aifaq_generated_faqs` Post_Meta value, THE Editor_Panel SHALL display a "Clear & Start Over" button below the FAQ count indicator
2. WHEN the user clicks the "Clear & Start Over" button, THE Editor_Panel SHALL clear the `_aifaq_generated_faqs` Post_Meta value to an empty string in the editor entity state without triggering an automatic post save
3. WHEN the user clicks the "Clear & Start Over" button, THE Editor_Panel SHALL hide the FAQ count indicator and the "Clear & Start Over" button, displaying only the "Generate FAQs" button
4. WHEN the user clicks the "Clear & Start Over" button, THE Editor_Panel SHALL NOT remove any existing `wpbits/faq-accordion` blocks from the post content
5. WHEN the user clicks the "Clear & Start Over" button, THE Editor_Panel SHALL reset any local component state (generated FAQ data, modal open state) to initial values

### Requirement 5: Regenerate Behavior Based on State

**User Story:** As a content editor, I want regeneration to replace the existing FAQ block when one is already inserted, so that I avoid duplicate blocks in my post.

#### Acceptance Criteria

1. WHILE the Sidebar_State is `has_faqs`, WHEN the user triggers the Regenerate_Action, THE Editor_Panel SHALL call the `aifaq_generate_faqs` AJAX endpoint with a 30-second timeout, display a loading indicator on the Generate button, and upon receiving a successful response, open the PreviewModal populated with the newly generated FAQ items
2. WHILE the Sidebar_State is `block_inserted`, WHEN the user triggers the Regenerate_Action, THE Editor_Panel SHALL call the `aifaq_generate_faqs` AJAX endpoint with a 30-second timeout, display a loading indicator on the Generate button, and upon receiving a successful response, replace the `items` attribute of the existing FAQ_Block in the post content without opening the PreviewModal
3. WHILE the Sidebar_State is `block_inserted`, WHEN the Regenerate_Action completes successfully, THE Editor_Panel SHALL remain in the `block_inserted` state and remove the loading indicator from the Generate button
4. IF the Regenerate_Action fails due to a network error, server error, or request timeout (30 seconds elapsed), THEN THE Editor_Panel SHALL display an error notice for 8 seconds, remove the loading indicator from the Generate button, and retain the current Sidebar_State without modifying the FAQ_Block
5. WHILE the Sidebar_State is `block_inserted`, IF the existing FAQ_Block is no longer present in the post content when the Regenerate_Action completes successfully, THEN THE Editor_Panel SHALL insert a new FAQ_Block with the generated items and transition the Sidebar_State to `block_inserted`

### Requirement 6: Block Detection on Load

**User Story:** As a content editor, I want the sidebar to detect existing FAQ blocks when I open a post, so that the sidebar state is always accurate even if I navigate away and return.

#### Acceptance Criteria

1. WHEN the Editor_Panel mounts in the block editor, THE Editor_Panel SHALL scan all top-level and nested blocks in the post content for any block with the name `wpbits/faq-accordion` using `select('core/block-editor').getBlocks()` with recursive inner-block traversal
2. WHEN Block_Detection finds one or more FAQ_Block instances, THE Editor_Panel SHALL set the Sidebar_State to `block_inserted` using the first FAQ_Block in document order as the reference
3. WHEN Block_Detection finds no FAQ_Block and Post_Meta `_aifaq_generated_faqs` is either absent, an empty string, or not a valid JSON array with at least one element, THE Editor_Panel SHALL set the Sidebar_State to `empty`
4. WHEN Block_Detection finds no FAQ_Block and Post_Meta `_aifaq_generated_faqs` contains a valid JSON array with one or more elements, THE Editor_Panel SHALL set the Sidebar_State to `has_faqs`
5. WHEN the block list in the editor changes after initial mount (block added, removed, or reordered), THE Editor_Panel SHALL re-run Block_Detection and update the Sidebar_State accordingly within 500 milliseconds of the change

### Requirement 7: Regenerate Button in Block-Inserted State

**User Story:** As a content editor, I want a Regenerate button available in the block-inserted state, so that I can update my FAQ content with fresh AI-generated answers without manual editing.

#### Acceptance Criteria

1. WHILE the Sidebar_State is `block_inserted`, THE Editor_Panel SHALL display a "Regenerate" button positioned after the "Edit Block" button and before the "Clear & Start Over" button in the button row
2. WHEN the user clicks the "Regenerate" button in the `block_inserted` state, THE Editor_Panel SHALL send an AJAX POST request to the `aifaq_generate_faqs` endpoint with the current post ID and nonce, and SHALL set the "Regenerate" button's `isBusy` prop to `true` to activate the built-in loading indicator
3. WHILE the Regenerate_Action is in progress, THE Editor_Panel SHALL disable the "Regenerate", "Edit Block", and "Clear & Start Over" buttons to prevent concurrent actions
4. WHEN the `aifaq_generate_faqs` endpoint returns a successful response containing a FAQ array, THE Editor_Panel SHALL update the existing FAQ_Block's `items` attribute with the newly generated FAQ items, re-enable all buttons, set the "Regenerate" button's `isBusy` prop to `false`, and display a success notice indicating the number of FAQs regenerated that auto-dismisses after 5 seconds
5. IF the `aifaq_generate_faqs` endpoint returns an error response, THEN THE Editor_Panel SHALL re-enable all buttons, set the "Regenerate" button's `isBusy` prop to `false`, and display an error notice containing the server-provided error message that auto-dismisses after 8 seconds
6. IF the AJAX request fails due to a network error or does not receive a response within 30 seconds, THEN THE Editor_Panel SHALL abort the request, re-enable all buttons, set the "Regenerate" button's `isBusy` prop to `false`, and display an error notice indicating the server could not be reached that auto-dismisses after 8 seconds

### Requirement 8: Manually Added Block Detection

**User Story:** As a content editor, I want the sidebar to recognize FAQ blocks I added manually (not through generation), so that I can still use Regenerate to populate them with AI content.

#### Acceptance Criteria

1. WHEN the Block_Editor loads or the editor content changes such that a `wpbits/faq-accordion` block is present in the post and no prior Generate flow produced it in the current session, THE Editor_Panel SHALL set the Sidebar_State to `block_inserted`, store a reference to the first detected `wpbits/faq-accordion` block as the active FAQ_Block, and display the "Regenerate" button in place of the "Generate FAQs" button
2. IF multiple `wpbits/faq-accordion` blocks exist in the post content, THEN THE Editor_Panel SHALL use the first `wpbits/faq-accordion` block in document order as the active FAQ_Block reference
3. WHILE the Sidebar_State is `block_inserted`, WHEN the user clicks the "Regenerate" button, THE Editor_Panel SHALL send a generation request to the `aifaq_generate_faqs` endpoint using the current post ID, and upon receiving a successful response, replace the `items` attribute of the active FAQ_Block with the newly generated FAQ items
4. IF the active FAQ_Block is removed from the post content while the Sidebar_State is `block_inserted`, THEN THE Editor_Panel SHALL reset the Sidebar_State to `empty` and restore the "Generate FAQs" button
