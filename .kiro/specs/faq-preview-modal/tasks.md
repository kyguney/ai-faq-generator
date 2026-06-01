# Implementation Plan: FAQ Preview Modal

## Overview

This plan implements the FAQ Preview Modal feature for the AI FAQ Generator WordPress plugin. The modal opens after successful FAQ generation, allowing users to review, edit, remove, and regenerate FAQs before inserting them as WordPress blocks. Implementation uses React with `@wordpress/components` and integrates with the existing EditorPanel and AJAX generation flow.

## Tasks

- [x] 1. Set up test infrastructure and mocks
  - [x] 1.1 Install fast-check and extend mock files
    - Add `fast-check` as a devDependency in `package.json`
    - Extend `src/editor/__mocks__/@wordpress/components.js` to export `Modal`, `TextControl`, and `TextareaControl` mock components
    - Create `src/editor/__mocks__/@wordpress/blocks.js` mock exporting `createBlock(name, attributes)` that returns `{ name, attributes }`
    - Add `@wordpress/blocks` mapping to `jest.config.js` moduleNameMapper
    - _Requirements: 8.1, 8.3, 8.4_

- [x] 2. Implement PreviewModal component
  - [x] 2.1 Create the PreviewModal component with FAQ list rendering
    - Create `src/editor/PreviewModal.js`
    - Import `Modal`, `Button`, `TextControl`, `TextareaControl`, `Spinner` from `@wordpress/components`
    - Import `useState` from `@wordpress/element`, `dispatch` from `@wordpress/data`, `createBlock` from `@wordpress/blocks`
    - Accept props: `faqs`, `postId`, `onClose`, `onInsertSuccess`
    - Initialize `localFaqs` state from `faqs` prop
    - Render Modal with title "Preview Generated FAQs" and FAQ count display
    - Render each FAQ item with 1-based index, TextControl for question (labeled), TextareaControl for answer (labeled)
    - Render remove button per item with `aria-label` containing the 1-based index
    - Show empty state message when list is empty
    - _Requirements: 1.2, 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 4.1, 8.1, 8.2, 8.3, 8.4, 8.7_

  - [x] 2.2 Implement inline editing and removal logic
    - Implement `handleQuestionChange(index, value)` — updates `localFaqs[index].question`
    - Implement `handleAnswerChange(index, value)` — updates `localFaqs[index].answer`
    - Implement `handleRemove(index)` — filters item at index, remaining items re-index automatically
    - Add visual indicator for empty fields (invalid state styling)
    - Disable "Insert" button when `localFaqs` is empty
    - _Requirements: 3.3, 3.4, 3.5, 4.2, 4.3, 4.4_

  - [x] 2.3 Implement regeneration logic
    - Implement `handleRegenerate()` using the same AJAX pattern as EditorPanel (POST to `aifaq_generate_faqs`)
    - Add `isRegenerating` state and `error` state
    - While loading: show Spinner, disable Regenerate/Insert buttons, set inputs to non-interactive
    - On success: replace `localFaqs` with new data, clear error
    - On error: show inline error message, retain existing list, re-enable buttons
    - On timeout (30s via AbortController): show inline timeout error, re-enable buttons
    - Dismiss previous error on successful regeneration
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [x] 2.4 Implement block insertion logic
    - Implement `faqsToBlocks(faqs)` — converts each FAQ to a `core/heading` (level 3) + `core/paragraph` block pair
    - Implement `handleInsert()` — calls `faqsToBlocks(localFaqs)`, dispatches `insertBlocks(blocks)` via `dispatch('core/block-editor')`, calls `onInsertSuccess(localFaqs)`
    - Handle insertion errors: keep modal open, show inline error, list unchanged
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 7.3_

  - [x] 2.5 Write property test: FAQ list rendering invariant
    - **Property 1: FAQ list rendering invariant**
    - **Validates: Requirements 1.3, 2.1, 2.2, 2.3**

  - [x] 2.6 Write property test: Controlled input state synchronization
    - **Property 2: Controlled input state synchronization**
    - **Validates: Requirements 3.3, 3.4**

  - [x] 2.7 Write property test: Removal reduces list and re-indexes
    - **Property 3: Removal reduces list and re-indexes**
    - **Validates: Requirements 4.2, 4.3, 2.4**

  - [x] 2.8 Write property test: Block conversion correctness
    - **Property 4: Block conversion correctness**
    - **Validates: Requirements 6.2, 6.3**

- [x] 3. Checkpoint - Verify core component
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Integrate PreviewModal with EditorPanel
  - [x] 4.1 Modify EditorPanel to manage modal state and render PreviewModal
    - Add `isModalOpen` state (boolean, default false) and `generatedFaqs` state (array, default [])
    - In `handleGenerate` success branch: set `generatedFaqs` to the new FAQ array and `isModalOpen` to true
    - Remove the existing success notice from `handleGenerate` (modal replaces it)
    - Conditionally render `<PreviewModal>` when `isModalOpen === true`
    - Pass props: `faqs={generatedFaqs}`, `postId={postId}`, `onClose={() => setIsModalOpen(false)}`
    - Implement `onInsertSuccess` callback: update post meta via `setMeta`, close modal, show success notice with FAQ count
    - Import PreviewModal at top of EditorPanel.js
    - _Requirements: 1.1, 1.4, 1.5, 1.6, 6.4, 6.5, 7.1, 7.2, 7.4_

  - [x] 4.2 Write unit tests for EditorPanel modal integration
    - Test: successful generation opens the modal with FAQ data
    - Test: error response does not open the modal
    - Test: network timeout does not open the modal
    - _Requirements: 1.1, 1.5, 1.6_

  - [x] 4.3 Write property test: Local state isolation
    - **Property 5: Local state isolation**
    - **Validates: Requirements 7.1, 7.2, 1.4**

  - [x] 4.4 Write property test: Insertion uses final edited state
    - **Property 6: Insertion uses final edited state**
    - **Validates: Requirements 7.3**

  - [x] 4.5 Write property test: Post meta persistence after insertion
    - **Property 7: Post meta persistence after insertion**
    - **Validates: Requirements 7.4**

- [x] 5. Add styles and accessibility
  - [x] 5.1 Create modal styles and ensure accessibility compliance
    - Create `src/editor/preview-modal.scss`
    - Use only WordPress admin CSS custom properties for colors (e.g., `--wp-admin-theme-color`)
    - Use spacing values that are multiples of 8px
    - Style FAQ items with visual separation, empty field invalid indicator, inline error messages
    - Import `preview-modal.scss` in PreviewModal.js
    - Ensure all interactive elements are reachable via Tab in DOM order
    - Ensure buttons are activatable via Enter and Space keys (native behavior via Button component)
    - _Requirements: 8.5, 8.6_

  - [x] 5.2 Write property test: Accessible names for all interactive elements
    - **Property 8: Accessible names for all interactive elements**
    - **Validates: Requirements 4.1, 8.7**

  - [x] 5.3 Write unit tests for PreviewModal
    - Test: renders correct title, FAQ count, and all FAQ items
    - Test: edit question/answer updates local state
    - Test: remove button removes item and re-indexes
    - Test: empty state shown when all items removed
    - Test: insert button disabled when list is empty
    - Test: regenerate triggers AJAX and replaces list on success
    - Test: regenerate error shows inline message, retains list
    - Test: insert converts FAQs to blocks and calls onInsertSuccess
    - Test: close button calls onClose without side effects
    - Test: loading state during regeneration disables inputs and buttons
    - _Requirements: 1.2, 1.3, 2.1, 2.5, 3.3, 4.2, 4.4, 5.5, 5.6, 6.2, 6.6, 7.2_

- [x] 6. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- All WordPress package imports are externals provided by `@wordpress/scripts` — no new runtime dependencies needed
- `fast-check` is the only new devDependency required
- The existing `@wordpress/components` mock needs extension (Modal, TextControl, TextareaControl)
- A new `@wordpress/blocks` mock is needed for `createBlock`

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["2.2", "2.3", "2.5"] },
    { "id": 3, "tasks": ["2.4", "2.6", "2.7"] },
    { "id": 4, "tasks": ["2.8", "4.1"] },
    { "id": 5, "tasks": ["4.2", "4.3", "4.4", "4.5", "5.1"] },
    { "id": 6, "tasks": ["5.2", "5.3"] }
  ]
}
```
