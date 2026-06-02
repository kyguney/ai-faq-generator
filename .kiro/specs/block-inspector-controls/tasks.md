# Implementation Plan: Block Inspector Controls

## Overview

Add a settings panel to the FAQ Accordion block's inspector sidebar with four controls (Title Tag, Open First Item, Icon Position, Enable Animation). Implementation proceeds from attribute schema registration, through the React inspector panel component and editor integration, to PHP server-side rendering and CSS styling. Each phase builds incrementally on the previous one and includes tests to validate correctness properties.

## Tasks

- [x] 1. Register block attributes and create InspectorPanel component
  - [x] 1.1 Add new attributes to block.json
    - Add `titleTag` (string, default `"h3"`), `openFirstItem` (boolean, default `false`), `iconPosition` (string, default `"left"`), and `enableAnimation` (boolean, default `false`) to the `attributes` object in `blocks/faq-accordion/block.json`
    - _Requirements: 6.3, 6.4_

  - [x] 1.2 Create InspectorPanel component
    - Create `blocks/faq-accordion/src/components/InspectorPanel.js`
    - Import `PanelBody`, `SelectControl`, `ToggleControl` from `@wordpress/components`
    - Render a `PanelBody` with title "Settings" containing:
      - `SelectControl` labeled "Title Tag" with options `H2`, `H3`, `H4` (values `h2`, `h3`, `h4`)
      - `ToggleControl` labeled "Open first item" bound to `openFirstItem`
      - `SelectControl` labeled "Icon Position" with options `Left`, `Right`, `None` (values `left`, `right`, `none`)
      - `ToggleControl` labeled "Enable animation" bound to `enableAnimation`
    - Each control calls `setAttributes` with the correct attribute key and value on change
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 2.1, 2.2, 3.1, 3.2, 3.3, 4.1, 4.2, 5.1, 5.2_

  - [x] 1.3 Create getBlockClasses utility function
    - Add a `getBlockClasses(attributes)` function (can be in `edit.js` or a separate utils file)
    - Returns icon-position class: `has-icon-left` (default/fallback), `has-icon-right`, or `has-no-icon`
    - Appends `has-animation` when `enableAnimation` is `true`
    - Returns space-joined string
    - _Requirements: 4.4, 5.7_

- [x] 2. Integrate InspectorPanel into edit.js
  - [x] 2.1 Wire InspectorControls and CSS classes in edit.js
    - Import `InspectorControls` from `@wordpress/block-editor`
    - Import `InspectorPanel` from `./components/InspectorPanel`
    - Import or define `getBlockClasses`
    - Destructure `titleTag`, `openFirstItem`, `iconPosition`, `enableAnimation` from `attributes`
    - Compute `className` via `getBlockClasses(attributes)` and pass to `useBlockProps({ className })`
    - Render `<InspectorControls><InspectorPanel attributes={attributes} setAttributes={setAttributes} /></InspectorControls>` before block content
    - _Requirements: 1.1, 1.2, 2.4, 4.4, 5.7_

  - [x] 2.2 Reflect openFirstItem state in editor preview
    - When `openFirstItem` is `true` and items array has at least one item, visually indicate first item is expanded in the editor preview
    - Pass `openFirstItem` to `FaqItemEditor` for index 0 or handle expansion state in the items loop
    - _Requirements: 3.5, 3.6_

- [x] 3. Checkpoint - Verify editor integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Update PHP render function for new attributes
  - [x] 4.1 Add validation helper functions to render.php
    - Add `get_validated_title_tag(array $attributes): string` — returns validated tag or `h3` fallback
    - Add `get_validated_icon_position(array $attributes): string` — returns validated position or `left` fallback
    - Add `get_validated_boolean(array $attributes, string $key): bool` — strict `=== true` check
    - _Requirements: 2.6, 4.8, 6.5, 7.5, 7.6, 7.7_

  - [x] 4.2 Update render_faq_accordion_block to use new attributes
    - Call validation helpers to get `$title_tag`, `$icon_position`, `$open_first_item`, `$enable_animation`
    - Build CSS class string: base class + icon-position class + optional animation class
    - Update wrapper `<div>` to include computed CSS classes
    - Wrap question text in `<$title_tag>` element inside `<summary>` (e.g., `<summary><h3>Question</h3></summary>`)
    - Add `open` attribute to first `<details>` element when `$open_first_item` is `true`
    - _Requirements: 2.5, 3.7, 3.8, 4.5, 4.6, 4.7, 5.4, 7.1, 7.2, 7.3, 7.4_

  - [x] 4.3 Write property tests for PHP validation logic (JS simulation)
    - **Property 1: Title tag renders correct heading inside summary**
    - **Property 2: Invalid title tag falls back to h3**
    - **Validates: Requirements 2.5, 2.6, 7.1, 7.5**

  - [x] 4.4 Write property tests for open first item and icon position
    - **Property 3: Open first item applies open attribute exclusively to first details**
    - **Property 4: Icon position maps to exactly one correct CSS class**
    - **Property 5: Invalid icon position falls back to has-icon-left**
    - **Validates: Requirements 3.7, 3.8, 4.5, 4.6, 4.7, 4.8, 7.2, 7.3, 7.6**

  - [x] 4.5 Write property tests for animation and boolean validation
    - **Property 6: Animation class presence matches boolean attribute**
    - **Property 7: Non-boolean attribute values treated as false**
    - **Validates: Requirements 5.4, 6.5, 7.4, 7.7**

- [x] 5. Add CSS rules for icon position and animation
  - [x] 5.1 Add icon position CSS rules to style.css
    - Add `.has-icon-left` rules (default, existing `::before` chevron behavior — may be a no-op if current styles already cover this)
    - Add `.has-icon-right` rule: `flex-direction: row-reverse` and `justify-content: space-between` on summary
    - Add `.has-no-icon` rule: `display: none` on `summary::before`
    - _Requirements: 4.5, 4.6, 4.7_

  - [x] 5.2 Add animation CSS rules to style.css
    - Add `.has-animation .faq-accordion-content` rule: `display: grid; grid-template-rows: 0fr; transition: grid-template-rows 300ms ease; overflow: hidden; padding: 0`
    - Add `.has-animation .faq-accordion-item[open] .faq-accordion-content` rule: `grid-template-rows: 1fr; padding: 1em 1.25em 1.25em`
    - Add `@media (prefers-reduced-motion: reduce)` rule to disable transition (`transition: none`)
    - _Requirements: 5.4, 5.5, 5.6_

- [x] 6. Checkpoint - Verify full integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Unit and integration tests
  - [x] 7.1 Write unit tests for InspectorPanel component
    - Verify all four controls render with correct labels
    - Verify `SelectControl` for Title Tag has options H2, H3, H4
    - Verify `SelectControl` for Icon Position has options Left, Right, None
    - Verify each control calls `setAttributes` with correct key/value on change
    - _Requirements: 1.3, 1.4, 1.5, 1.6, 2.1, 4.1_

  - [x] 7.2 Write unit tests for getBlockClasses utility
    - Test all icon position values produce correct class
    - Test `enableAnimation: true` adds `has-animation`
    - Test `enableAnimation: false` does not add `has-animation`
    - Test invalid `iconPosition` defaults to `has-icon-left`
    - _Requirements: 4.4, 4.8, 5.7_

  - [x] 7.3 Write PHPUnit tests for render.php
    - Test rendering with each valid `titleTag` value produces correct heading in output
    - Test `openFirstItem: true` adds `open` attribute to first details only
    - Test `openFirstItem: false` adds no `open` attribute
    - Test each `iconPosition` value produces correct CSS class on wrapper
    - Test `enableAnimation: true` adds `has-animation` class
    - Test backward compatibility: rendering without new attributes uses defaults
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

- [x] 8. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design (Properties 1–7)
- Unit tests validate specific examples and edge cases
- The design uses JavaScript (React) and PHP — no language selection needed
- CSS animation uses `grid-template-rows` technique for smooth height transitions on `<details>` elements
- All CSS variants use classes on the block wrapper, compatible with both `useBlockProps` in editor and `render.php` on frontend

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["2.1", "2.2"] },
    { "id": 3, "tasks": ["4.1", "5.1", "5.2"] },
    { "id": 4, "tasks": ["4.2"] },
    { "id": 5, "tasks": ["4.3", "4.4", "4.5", "7.1", "7.2"] },
    { "id": 6, "tasks": ["7.3"] }
  ]
}
```
