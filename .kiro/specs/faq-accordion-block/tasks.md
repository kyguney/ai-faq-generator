# Implementation Plan: FAQ Accordion Block

## Overview

Implement a custom Gutenberg block (`wpbits/faq-accordion`) that allows content editors to create and manage FAQ items inline in the block editor, with server-side PHP rendering using native `<details>`/`<summary>` elements for an accessible accordion on the frontend. The block is dynamic (save returns null), uses `@wordpress/scripts` for build tooling, and follows the existing plugin architecture.

## Tasks

- [x] 1. Set up block directory structure and metadata
  - [x] 1.1 Create `blocks/faq-accordion/block.json` with block metadata
    - Define block name `wpbits/faq-accordion`, title, category `widgets`, icon `editor-help`
    - Define `items` attribute schema (array of objects with `question` and `answer` string properties)
    - Set `editorScript`, `editorStyle`, and `style` file references
    - Set `supports.html` to false and `supports.align` to `["wide", "full"]`
    - Set `apiVersion` to 3 and `textdomain` to `ai-faq-generator`
    - _Requirements: 1.3, 2.1, 2.2_

  - [x] 1.2 Create `blocks/faq-accordion/src/index.js` with block registration
    - Import `registerBlockType` from `@wordpress/blocks`
    - Import block metadata from `../block.json`
    - Import `Edit` component from `./edit`
    - Import `save` component from `./save`
    - Call `registerBlockType` with metadata and `edit`/`save` components
    - _Requirements: 1.1, 1.2_

  - [x] 1.3 Create `blocks/faq-accordion/src/save.js` returning null
    - Export default function that returns `null` for dynamic block rendering
    - _Requirements: 7.4_

- [x] 2. Implement PHP block registration and render callback
  - [x] 2.1 Create `blocks/faq-accordion/render.php` with the render callback function
    - Implement `render_faq_accordion_block(array $attributes): string`
    - Return empty string if `items` is not an array or is empty
    - Skip items that are not arrays, or have empty/missing `question` or `answer`
    - Sanitize question and answer values with `wp_kses_post()`
    - Generate HTML using `<details>` and `<summary>` elements
    - Add `aria-expanded="false"` and `aria-controls` with unique panel IDs on each `<summary>`
    - Wrap each answer in a `<div>` with matching `id` attribute
    - Wrap all items in a container `<div class="wp-block-wpbits-faq-accordion">`
    - _Requirements: 5.1, 5.2, 5.5, 5.6, 5.7, 6.2, 6.4, 6.5, 7.1, 7.2, 7.3, 7.5_

  - [x] 2.2 Create `blocks/faq-accordion/class-faq-accordion-block.php` for block registration
    - Define `register_faq_accordion_block()` function in the plugin namespace
    - Call `register_block_type()` with the block directory path and `render_callback`
    - Log error via `error_log()` if registration fails
    - Hook registration to `init` action
    - _Requirements: 1.1, 1.5_

  - [x] 2.3 Wire block registration into the plugin Loader class
    - Add require for `blocks/faq-accordion/class-faq-accordion-block.php` in `includes/class-loader.php`
    - Add `init` action hook for `register_faq_accordion_block` in the Loader's `init()` method
    - _Requirements: 1.1, 1.4_

- [x] 3. Checkpoint - Verify block registration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement editor components
  - [x] 4.1 Create `blocks/faq-accordion/src/components/FaqItemEditor.js`
    - Accept props: `item`, `index`, `onUpdate`, `onRemove`, `onMove`, `isFirst`, `isLast`
    - Render a text input for question (maxLength 500) and textarea for answer (maxLength 5000)
    - Render move-up button (disabled/hidden when `isFirst`), move-down button (disabled/hidden when `isLast`), and remove button
    - Call `onUpdate(index, field, value)` on input change
    - Call `onRemove(index)` on remove button click
    - Call `onMove(index, direction)` on move button click (direction: -1 for up, 1 for down)
    - _Requirements: 3.2, 3.4, 3.5, 3.6, 4.3_

  - [x] 4.2 Create `blocks/faq-accordion/src/components/AddItemButton.js`
    - Accept props: `onClick`, `disabled`, `itemCount`
    - Render "Add FAQ Item" button
    - When `itemCount` is 0, show placeholder message indicating no FAQ items added
    - When `disabled` is true (50 items reached), disable button and show limit message
    - _Requirements: 3.3, 3.7, 3.8, 4.4_

  - [x] 4.3 Create `blocks/faq-accordion/src/edit.js` with the main Edit component
    - Import `useBlockProps` from `@wordpress/block-editor`
    - Import `FaqItemEditor` and `AddItemButton` components
    - Define `MAX_ITEMS = 50` constant
    - Implement `addItem` function: append `{ question: '', answer: '' }` to items
    - Implement `updateItem(index, field, value)`: update specific item field via `setAttributes`
    - Implement `removeItem(index)`: remove item at index via `setAttributes`
    - Implement `moveItem(index, direction)`: swap item with neighbor at `index + direction`
    - Render items list with `FaqItemEditor` for each item and `AddItemButton` at the end
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3_

- [x] 5. Add editor styles
  - [x] 5.1 Create `blocks/faq-accordion/src/editor.scss` with editor-only styles
    - Style the FAQ item editor rows with clear visual separation
    - Style question input and answer textarea fields
    - Style move and remove action buttons
    - Style the add item button and placeholder state
    - Style the disabled/limit state
    - _Requirements: 3.2, 3.6, 3.7_

  - [x] 5.2 Create `blocks/faq-accordion/style.css` with frontend accordion styles
    - Style the `.wp-block-wpbits-faq-accordion` container
    - Style `.faq-accordion-item` details elements
    - Style `summary` elements as clickable headers
    - Style `.faq-accordion-content` panels
    - Ensure collapsed/expanded states are visually clear
    - _Requirements: 5.1, 5.2_

- [x] 6. Configure build tooling
  - [x] 6.1 Update `webpack.config.js` to add the block entry point
    - Add entry for `blocks/faq-accordion/src/index.js` so `@wordpress/scripts` compiles the block assets into `blocks/faq-accordion/build/`
    - Ensure editor styles (SCSS) are processed and output alongside the script
    - _Requirements: 1.4_

- [x] 7. Checkpoint - Verify editor functionality
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Write PHP property tests for render callback
  - [x] 8.1 Write property test for render output order and structure
    - **Property 5: Render Output Preserves Item Order and Structure**
    - Generate random arrays of valid FAQ items, verify rendered HTML contains items in same order with question in summary and answer in content div
    - Use PHPUnit data provider with 110+ generated iterations
    - **Validates: Requirements 4.2, 5.2**

  - [x] 8.2 Write property test for skipping invalid items
    - **Property 6: Invalid Items Are Skipped in Render**
    - Generate arrays with mix of valid and invalid items (missing keys, empty values, non-array entries), verify only valid items appear in output
    - Use PHPUnit data provider with 110+ generated iterations
    - **Validates: Requirements 2.5, 5.7, 7.5**

  - [x] 8.3 Write property test for HTML sanitization
    - **Property 7: Render Output Is Sanitized**
    - Generate FAQ items containing HTML markup including dangerous tags, verify output contains sanitized content via `wp_kses_post` and never raw unsanitized input
    - Use PHPUnit data provider with 110+ generated iterations
    - **Validates: Requirements 7.3**

  - [x] 8.4 Write property test for accessibility attributes
    - **Property 8: Accessibility Attributes Are Correct on Initial Render**
    - Generate valid FAQ arrays, verify every summary has `aria-expanded="false"` and `aria-controls` referencing a unique ID matching the content panel's `id`
    - Use PHPUnit data provider with 110+ generated iterations
    - **Validates: Requirements 5.1, 6.2, 6.5**

- [x] 9. Write JavaScript property tests for editor logic
  - [x] 9.1 Write property test for FAQ item attribute round-trip
    - **Property 1: FAQ Item Attribute Round-Trip**
    - Use `fast-check` to generate valid FAQ item arrays, serialize as block attributes, restore, and verify all values preserved
    - Run with `{ numRuns: 100 }` minimum
    - **Validates: Requirements 2.4**

  - [x] 9.2 Write property test for adding items
    - **Property 2: Adding an Item Grows the List**
    - Use `fast-check` to generate arrays of 0-49 items, verify adding an item increases length by 1 with empty question/answer appended
    - Run with `{ numRuns: 100 }` minimum
    - **Validates: Requirements 3.3, 4.1**

  - [x] 9.3 Write property test for removing items
    - **Property 3: Removing an Item Shrinks the List**
    - Use `fast-check` to generate non-empty arrays and valid indices, verify removing decreases length by 1 with other items in relative order
    - Run with `{ numRuns: 100 }` minimum
    - **Validates: Requirements 3.5**

  - [x] 9.4 Write property test for reordering items
    - **Property 4: Reordering Preserves All Items**
    - Use `fast-check` to generate arrays with 2+ items and valid indices, verify move swaps only the target and neighbor while preserving all items
    - Run with `{ numRuns: 100 }` minimum
    - **Validates: Requirements 4.3**

- [x] 10. Write unit tests for editor components
  - [x] 10.1 Write unit tests for the Edit component
    - Test that editor renders all FAQ items from attributes
    - Test that add button appears and is functional
    - Test placeholder state when items array is empty
    - Test that add button is disabled at 50 items
    - _Requirements: 3.2, 3.3, 3.7, 3.8_

  - [x] 10.2 Write unit tests for FaqItemEditor component
    - Test question input and answer textarea render with correct values
    - Test onChange handlers call onUpdate with correct arguments
    - Test remove button calls onRemove
    - Test move buttons call onMove with correct direction
    - Test move-up disabled when isFirst, move-down disabled when isLast
    - _Requirements: 3.4, 3.5, 3.6, 4.3_

  - [x] 10.3 Write unit tests for AddItemButton component
    - Test button renders and calls onClick
    - Test placeholder message when itemCount is 0
    - Test disabled state and limit message when disabled is true
    - _Requirements: 3.3, 3.7, 3.8, 4.4_

  - [x] 10.4 Write unit test for save component
    - Test that save() returns null
    - _Requirements: 7.4_

- [x] 11. Write PHP unit tests for block registration
  - [x] 11.1 Write unit tests for block registration
    - Test `register_faq_accordion_block()` calls `register_block_type()` with correct path
    - Test error logging when registration fails
    - Test block is hooked to `init` action
    - _Requirements: 1.1, 1.5_

  - [x] 11.2 Write unit tests for render callback edge cases
    - Test empty items returns empty string
    - Test non-array items value returns empty string
    - Test render always returns a string type
    - _Requirements: 5.6, 7.2, 7.5_

- [x] 12. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- PHP property tests use PHPUnit data providers with 110+ iterations (matching existing project convention)
- JavaScript property tests use `fast-check` (already in devDependencies) with 100+ runs
- The block uses `@wordpress/scripts` for compilation; webpack config needs an additional entry point
- Frontend accordion uses native `<details>`/`<summary>` elements — no custom JS needed on frontend

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.3", "2.1"] },
    { "id": 1, "tasks": ["1.2", "2.2", "4.1", "4.2", "5.2"] },
    { "id": 2, "tasks": ["2.3", "4.3", "5.1", "6.1"] },
    { "id": 3, "tasks": ["8.1", "8.2", "8.3", "8.4", "9.1", "9.2", "9.3", "9.4"] },
    { "id": 4, "tasks": ["10.1", "10.2", "10.3", "10.4", "11.1", "11.2"] }
  ]
}
```
