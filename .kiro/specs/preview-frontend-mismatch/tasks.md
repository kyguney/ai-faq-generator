# Implementation Plan

## Overview

Fix the Visual Preview Mode in the FAQ Accordion block editor so it uses `<details>/<summary>` elements (matching the frontend's HTML structure) instead of plain `<div>` elements. This allows `style.css` to apply naturally in the editor preview, giving it the same look as the frontend.

## Tasks

- [x] 1. Write bug condition exploration test
  - **Property 1: Bug Condition** - Visual Preview uses div elements instead of details/summary
  - **IMPORTANT**: Write this property-based test BEFORE implementing the fix
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the preview HTML structure doesn't match style.css selectors
  - **Scoped PBT Approach**: Generate random FAQ item arrays (1-10 items with arbitrary question/answer text) and random attribute combinations (iconPosition ∈ {left, right, none}, openFirstItem ∈ {true, false}, enableAnimation ∈ {true, false}, selectedIcon ∈ {chevron, chevron-right, plus, arrow, none})
  - **Bug Condition**: `isBugCondition(X)` where `X.layoutMode = "preview"` AND `X.context = "editor"` — the `renderVisualPreview()` function renders `<div>` elements that don't match `style.css` selectors targeting `<details>/<summary>`
  - **Test file**: Create `blocks/faq-accordion/src/__tests__/edit-preview-bug-condition.test.js`
  - **Test assertions** (expected behavior that will validate the fix):
    - For all generated FAQ items, preview output contains `<details>` elements with class `faq-accordion-item`
    - For all generated FAQ items, preview output contains `<summary>` elements (not `<div class="faq-accordion-summary">`)
    - Open items have the native `open` attribute on `<details>` (not `is-open` class)
    - Content panels have `<div class="faq-accordion-content__inner">` inner wrapper
    - Icon elements inside `<summary>` have no inline positioning/transform styles (let style.css handle them)
  - Run test on UNFIXED code using: `npx wp-scripts test-unit-js --env=jsdom --testPathPattern="edit-preview-bug-condition"`
  - **EXPECTED OUTCOME**: Test FAILS (this is correct - it proves the bug exists because divs are rendered instead of details/summary)
  - Document counterexamples found (e.g., "renders `<div class='faq-accordion-item'>` instead of `<details class='faq-accordion-item'>`")
  - Mark task complete when test is written, run, and failure is documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 2. Write preservation property tests (BEFORE implementing fix)
  - **Property 2: Preservation** - Classic Edit Mode and Inline Styles Unchanged
  - **IMPORTANT**: Follow observation-first methodology
  - **Test file**: Create `blocks/faq-accordion/src/__tests__/edit-preview-preservation.test.js`
  - **Observation phase** (run on UNFIXED code):
    - Observe: When `layoutMode = "edit"`, the Edit component renders `FaqItemEditor` components with input fields and action buttons
    - Observe: When `layoutMode = "edit"`, no `<details>` or `<summary>` elements are present (it uses input fields)
    - Observe: Custom attributes (titleColor, titleFontSize, contentColor, contentPadding, etc.) generate inline styles in preview
    - Observe: `openFirstItem = true` makes the first item have open/expanded state
    - Observe: Toggle click on an item flips its `_open` attribute
  - **Property-based tests** (generate random attribute combinations):
    - Generate random `titleColor` (hex strings), `titleFontSize` (0-72), `contentColor` (hex strings), `contentPadding` (0-100), `itemSpacing` (0-50)
    - For all generated attribute sets with `layoutMode = "edit"`: assert output contains FaqItemEditor elements, no `faq-accordion-item` class in output
    - For all generated attribute sets with `layoutMode = "preview"` and non-empty titleColor: assert inline `color` style is applied to title
    - For all generated attribute sets with `layoutMode = "preview"` and titleFontSize > 0: assert inline `fontSize` style is applied
    - For all generated attribute sets with `layoutMode = "preview"` and openFirstItem=true: assert first item is in open state
  - Verify tests pass on UNFIXED code
  - Run tests using: `npx wp-scripts test-unit-js --env=jsdom --testPathPattern="edit-preview-preservation"`
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 3. Fix for Visual Preview using div elements instead of details/summary

  - [x] 3.1 Implement the fix in `renderVisualPreview()` in `edit.js`
    - Replace `<div className="faq-accordion-item ...">` with `<details className="faq-accordion-item" open={isOpen}>`
    - Replace `<div className="faq-accordion-summary" ...>` with `<summary>` element
    - Remove `role="button"` and `tabIndex` (native `<summary>` is interactive)
    - Use native `open` attribute controlled by React state instead of `is-open` class
    - Add `onClick` with `e.preventDefault()` on `<summary>` to prevent native toggle and keep React state in control
    - Always render `<div className="faq-accordion-content">` (don't conditionally hide with `{isOpen && ...}`) — let `<details>` handle visibility
    - Add inner `<div className="faq-accordion-content__inner">` wrapper inside content div
    - Remove inline icon styles (`marginRight`, `order`, `transition`, `transform`) — let `style.css` handle positioning and rotation
    - Keep the `getTitleStyle()` inline style on `<summary>` for custom padding override
    - Move `TitleTag` inline style (color, fontSize, fontFamily) inside `<summary>`
    - Apply `getContentStyle()` on `.faq-accordion-content` div
    - Apply `getItemStyle()` on `<details>` element
    - Remove the extra `<div className="wp-block-wpbits-faq-accordion">` wrapper (blockProps already provides this)
    - _Bug_Condition: isBugCondition(input) where input.layoutMode = "preview" AND input.context = "editor"_
    - _Expected_Behavior: renderVisualPreview() produces `<details>/<summary>` elements matching style.css selectors_
    - _Preservation: Classic edit mode, inline styles, toggle interaction, openFirstItem unchanged_
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.3, 3.4, 3.5_

  - [x] 3.2 Verify bug condition exploration test now passes
    - **Property 1: Expected Behavior** - Visual Preview uses details/summary matching frontend
    - **IMPORTANT**: Re-run the SAME test from task 1 - do NOT write a new test
    - The test from task 1 encodes the expected behavior (details/summary structure)
    - When this test passes, it confirms the expected behavior is satisfied
    - Run: `npx wp-scripts test-unit-js --env=jsdom --testPathPattern="edit-preview-bug-condition"`
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed — preview now uses details/summary)
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 3.3 Verify preservation tests still pass
    - **Property 2: Preservation** - Classic Edit Mode and Inline Styles Unchanged
    - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
    - Run: `npx wp-scripts test-unit-js --env=jsdom --testPathPattern="edit-preview-preservation"`
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions in edit mode or inline styles)
    - Confirm all tests still pass after fix (no regressions)

  - [x] 3.4 Rebuild the block and verify build succeeds
    - Run: `npm run build` in the plugin directory
    - Confirm no build errors
    - Verify `build/style-index.css` and `build/index.js` are generated

- [x] 4. Checkpoint - Ensure all tests pass
  - Run full test suite: `npx wp-scripts test-unit-js --env=jsdom`
  - Ensure all tests pass, ask the user if questions arise.

## Task Dependency Graph

```json
{
  "waves": [
    ["1", "2"],
    ["3.1"],
    ["3.2", "3.3", "3.4"],
    ["4"]
  ]
}
```

## Notes

- Test framework: `@wordpress/scripts` with Jest + jsdom + `@testing-library/react`
- Property-based testing library: `fast-check` (already in devDependencies)
- Plugin directory: `plugins/ai-faq-generator`
- Test command: `npx wp-scripts test-unit-js --env=jsdom`
- Build command: `npm run build`
- The exploration test (task 1) is expected to FAIL on unfixed code — this confirms the bug exists
- The preservation test (task 2) is expected to PASS on unfixed code — this captures baseline behavior
- After the fix (task 3), both tests should PASS
