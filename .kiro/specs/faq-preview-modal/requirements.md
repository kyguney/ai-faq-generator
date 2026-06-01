# Requirements Document

## Introduction

The FAQ Preview Modal feature adds a modal dialog to the WordPress block editor that opens after FAQ generation succeeds. The modal displays generated FAQs in an editable list, allowing users to review, edit, remove, or regenerate FAQs before inserting them as blocks into the post content. This replaces the current "X FAQs generated" label with a full interactive preview workflow.

## Glossary

- **Preview_Modal**: The React-based modal dialog component that displays generated FAQs for review and editing before insertion into post content.
- **FAQ_Item**: A single FAQ entry consisting of a question string and an answer string, displayed as an editable row within the Preview_Modal.
- **FAQ_List**: The ordered collection of FAQ_Item objects displayed within the Preview_Modal.
- **Editor_Panel**: The existing PluginDocumentSettingPanel sidebar component that contains the "Generate FAQs" button.
- **Block_Inserter**: The mechanism that converts FAQ_Item objects into WordPress blocks and appends them to the post content.
- **FAQ_Generator_Service**: The server-side AJAX handler (`aifaq_generate_faqs`) that generates FAQ data from post content via AI.

## Requirements

### Requirement 1: Modal Display After Generation

**User Story:** As a content editor, I want to see a preview modal after FAQ generation completes, so that I can review the generated FAQs before they are inserted into my post.

#### Acceptance Criteria

1. WHEN the FAQ_Generator_Service returns a successful response, THE Preview_Modal SHALL open automatically and display the generated FAQ_List.
2. WHEN the Preview_Modal is open, THE Preview_Modal SHALL display a title header with the text "Preview Generated FAQs".
3. WHEN the Preview_Modal is open, THE Preview_Modal SHALL display the total count of FAQ_Item entries in the FAQ_List.
4. WHEN the user clicks the close button or presses the Escape key, THE Preview_Modal SHALL close without modifying post content or post meta.
5. IF the FAQ_Generator_Service returns an error response, THEN THE Editor_Panel SHALL display a dismissible error notice for 8 seconds indicating the failure reason, and THE Preview_Modal SHALL remain closed.
6. IF the FAQ_Generator_Service request fails due to a network error or timeout, THEN THE Editor_Panel SHALL display a dismissible error notice for 8 seconds indicating the connection failure, and THE Preview_Modal SHALL remain closed.

### Requirement 2: FAQ List Display

**User Story:** As a content editor, I want to see all generated FAQs in a structured list, so that I can quickly scan and evaluate the generated content.

#### Acceptance Criteria

1. THE Preview_Modal SHALL display each FAQ_Item as a distinct visual row containing the question text labeled "Question" and the answer text labeled "Answer", visually separated from adjacent FAQ_Items.
2. WHEN the FAQ_List contains one or more FAQ_Item entries, THE Preview_Modal SHALL display each FAQ_Item in the order received from the FAQ_Generator_Service.
3. THE Preview_Modal SHALL display a 1-based index number for each FAQ_Item to indicate its position in the FAQ_List.
4. WHEN a FAQ_Item is removed from the FAQ_List, THE Preview_Modal SHALL re-number the remaining FAQ_Items sequentially starting from 1.
5. WHEN the FAQ_List is empty (all items removed), THE Preview_Modal SHALL display an empty state message indicating no FAQs are available.
6. WHEN the FAQ_List contains more items than can fit in the visible modal area, THE Preview_Modal SHALL allow the user to scroll through the full FAQ_List.

### Requirement 3: Inline Editing

**User Story:** As a content editor, I want to edit the question and answer text of each FAQ directly in the modal, so that I can refine the AI-generated content before insertion.

#### Acceptance Criteria

1. THE Preview_Modal SHALL render each FAQ_Item question as an editable text input field using the WordPress TextControl component.
2. THE Preview_Modal SHALL render each FAQ_Item answer as an editable textarea field using the WordPress TextareaControl component.
3. WHEN the user modifies the text in a question field, THE Preview_Modal SHALL update the corresponding FAQ_Item question value in the FAQ_List state immediately on each keystroke.
4. WHEN the user modifies the text in an answer field, THE Preview_Modal SHALL update the corresponding FAQ_Item answer value in the FAQ_List state immediately on each keystroke.
5. IF the user clears a question or answer field to an empty string, THEN THE Preview_Modal SHALL visually indicate the empty field as invalid but SHALL NOT prevent other actions (insert, regenerate, remove).

### Requirement 4: Remove Individual FAQ

**User Story:** As a content editor, I want to remove individual FAQs from the list, so that I can exclude irrelevant or low-quality items before insertion.

#### Acceptance Criteria

1. THE Preview_Modal SHALL display a remove button for each FAQ_Item in the FAQ_List, with an accessible ARIA label that identifies the associated FAQ_Item by its index number.
2. WHEN the user clicks the remove button for a FAQ_Item, THE Preview_Modal SHALL remove that FAQ_Item from the FAQ_List without a confirmation prompt.
3. WHEN a FAQ_Item is removed, THE Preview_Modal SHALL re-number the index positions of all remaining FAQ_Items sequentially starting from 1 and update the displayed FAQ count to reflect the remaining items.
4. WHEN the last FAQ_Item is removed from the FAQ_List, THE Preview_Modal SHALL display the empty state message and disable the insert button.

### Requirement 5: Regenerate FAQs

**User Story:** As a content editor, I want to regenerate FAQs from within the modal, so that I can get a fresh set of suggestions without closing the modal.

#### Acceptance Criteria

1. THE Preview_Modal SHALL display a "Regenerate" button.
2. WHEN the user clicks the "Regenerate" button, THE Preview_Modal SHALL discard any local edits to the current FAQ_List and trigger a new request to the FAQ_Generator_Service using the current post ID.
3. WHILE the FAQ_Generator_Service request is in progress, THE Preview_Modal SHALL display a loading indicator, disable the "Regenerate" and "Insert" buttons, and set all FAQ_Item input fields to a non-interactive state.
4. IF the FAQ_Generator_Service request does not return a response within 30 seconds, THEN THE Preview_Modal SHALL cancel the request, display an inline error notice indicating a timeout occurred, and re-enable the "Regenerate" and "Insert" buttons.
5. WHEN the FAQ_Generator_Service returns a successful response after regeneration, THE Preview_Modal SHALL replace the current FAQ_List with the newly generated FAQ_List and update the displayed FAQ count.
6. IF the FAQ_Generator_Service returns an error during regeneration, THEN THE Preview_Modal SHALL display an inline error notice, retain the existing FAQ_List, and re-enable the "Regenerate" and "Insert" buttons.
7. WHEN the user clicks the "Regenerate" button and a new FAQ_List is successfully loaded, THE Preview_Modal SHALL dismiss any previously displayed error notice.

### Requirement 6: Insert FAQs Into Post Content

**User Story:** As a content editor, I want to insert the reviewed FAQs into my post as blocks, so that the FAQ content becomes part of my published post.

#### Acceptance Criteria

1. THE Preview_Modal SHALL display an "Insert" button.
2. WHEN the user clicks the "Insert" button, THE Block_Inserter SHALL convert each FAQ_Item in the FAQ_List into a WordPress heading block at level 3 (for the question) followed by a paragraph block (for the answer).
3. WHEN the user clicks the "Insert" button, THE Block_Inserter SHALL append the generated blocks to the end of the current post content in FAQ_List order.
4. WHEN the Block_Inserter completes insertion, THE Preview_Modal SHALL close automatically.
5. WHEN the Block_Inserter completes insertion, THE Editor_Panel SHALL display a success notice indicating the number of FAQs inserted, visible for 5 seconds before auto-dismissing.
6. WHILE the FAQ_List is empty, THE Preview_Modal SHALL disable the "Insert" button.
7. IF the Block_Inserter fails to insert blocks into the post content, THEN THE Preview_Modal SHALL remain open and display an inline error notice indicating the insertion failed, and the FAQ_List SHALL remain unchanged.

### Requirement 7: State Management

**User Story:** As a content editor, I want the modal to maintain consistent state during my editing session, so that my changes are preserved until I explicitly insert or dismiss.

#### Acceptance Criteria

1. THE Preview_Modal SHALL maintain the FAQ_List state locally within the component until the user clicks "Insert" or closes the modal, without persisting intermediate edits to post meta or post content.
2. WHEN the Preview_Modal closes without insertion (close button or Escape key), THE Preview_Modal SHALL discard all local edits without modifying post meta or post content.
3. WHEN the user clicks "Insert", THE Block_Inserter SHALL use the current local FAQ_List state (including any edits and removals) for block generation.
4. WHEN the Block_Inserter completes insertion successfully, THE Preview_Modal SHALL overwrite the `_aifaq_generated_faqs` post meta with the final FAQ_List state that was used for block generation.
5. IF the post meta update fails after block insertion, THEN THE Preview_Modal SHALL display an inline error notice indicating that FAQ metadata could not be saved, while preserving the already-inserted blocks in the post content.

### Requirement 8: WordPress Design Compliance

**User Story:** As a content editor, I want the modal to look and behave like native WordPress UI, so that the experience is consistent with the rest of the editor.

#### Acceptance Criteria

1. THE Preview_Modal SHALL use the `Modal` component from the `@wordpress/components` package.
2. THE Preview_Modal SHALL use `Button` components from `@wordpress/components` for all interactive actions.
3. THE Preview_Modal SHALL use `TextControl` from `@wordpress/components` for question input fields.
4. THE Preview_Modal SHALL use `TextareaControl` from `@wordpress/components` for answer input fields.
5. THE Preview_Modal SHALL apply only WordPress admin CSS custom properties (e.g., `--wp-admin-theme-color`) for colors and use spacing values that are multiples of 8px, without introducing custom color values or overriding WordPress admin theme variables.
6. THE Preview_Modal SHALL support keyboard navigation such that all interactive elements (buttons, text inputs, textareas) are reachable via the Tab key in DOM order, buttons are activatable via Enter and Space keys, and focus is trapped within the modal while it is open.
7. THE Preview_Modal SHALL provide an accessible name via `aria-label` or associated `<label>` element for every interactive element, including: the modal itself (via its title prop), each question TextControl, each answer TextareaControl, each remove button (indicating which FAQ_Item it removes), the "Regenerate" button, and the "Insert" button.
