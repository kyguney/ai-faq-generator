# Implementation Plan

## Overview

Fix three bugs in the FAQ Accordion block: (1) Visual Preview Toggle not switching views due to stale build, (2) Duplicate Icons from CSS border-chevron conflicting with span text characters, and (3) Frontend Styles not applying because inline styles lack `!important` and padding targets the wrong element.

## Tasks

- [ ] 1. Write bug condition exploration tests
  - **Property 1: Bug Condition** - FAQ Accordion Preview Bugs (Toggle, Duplicate Icons, Frontend Styles)
  - **IMPORTANT**: Write these property-based tests BEFORE implementing any fixes
  - **CRITICAL**: These tests MUST FAIL on unfixed code — failure confirms the bugs exist
  - **DO NOT attempt to fix the tests or the code when they fail**
  - **NOTE**: These tests encode the expected behavior — they will validate the fixes when they pass after implementation
  - **GOAL**: Surface counterexamples that demonstrate all three bugs exist
  - **Scoped PBT Approach**: Scope each property to the concrete failing conditions
  - **Test file**: `blocks/faq-accordion/src/__tests__/bug-condition.test.js`
  - **Bug 1 — Preview Toggle**: Render `<Edit>` component with `layoutMode="preview"` and at least one FAQ item. Assert that `.faq-accordion-summary` elements are present (visual preview) and that `.faq-item-editor` elements are NOT present (edit mode). On unfixed code this may fail if the build is stale.
  - **Bug 2 — Duplicate Icons**: Parse the `.faq-accordion-icon` CSS rule from `style.css`. Assert that the rule does NOT contain `border-right` or `border-bottom` properties. Alternatively, render the block frontend HTML and verify that `.faq-accordion-icon` has no CSS border-chevron artifact — only the text character icon.
  - **Bug 3 — Frontend Styles**: Call `render_faq_accordion_block()` (or its JS equivalent for testing) with `titleColor="#ff0000"`, `titleFontSize=20`, `titlePadding=24`. Assert the rendered HTML inline styles contain `!important` declarations. On unfixed code, inline styles lack `!important` and are overridden by CSS.
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests FAIL (this is correct — it proves the bugs exist)
  - Document counterexamples found to understand root causes
  - Mark task complete when tests are written, run, and failures are documented
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 2. Write preservation property tests (BEFORE implementing fixes)
  - **Property 2: Preservation** - Edit Mode, Default Styling, and Item Management Behavior
  - **IMPORTANT**: Follow observation-first methodology
  - **Test file**: `blocks/faq-accordion/src/__tests__/preservation.test.js`
  - **Observe on UNFIXED code**:
    - Render `<Edit>` with `layoutMode="edit"` → verify `<FaqItemEditor>` components render correctly
    - Render with `selectedIcon="none"` or `iconPosition="none"` → verify no icon element appears
    - Render with default attributes (empty colors, 0 font sizes, default padding) → verify no inline styles are injected
    - Verify item add/remove/reorder operations work in edit mode
    - Verify `has-animation` class is present when `enableAnimation=true`
  - **Write property-based tests capturing observed behavior**:
    - For all attribute combinations where `layoutMode="edit"`, editor renders FaqItemEditor components
    - For all attribute combinations where `iconPosition="none"`, no `.faq-accordion-icon` element appears in rendered HTML
    - For all attribute combinations with default styling values, rendered output uses CSS variable-based styling without inline overrides
    - For all valid item arrays, add/remove/reorder operations produce correct results regardless of layoutMode
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 3. Fix Bug 1 — Visual Preview Toggle

  - [ ] 3.1 Rebuild the block and verify layoutMode attribute works
    - Run `npm run build` in the `blocks/faq-accordion/` directory (or project root build script) to recompile `edit.js` into `build/index.js`
    - Verify the compiled output includes the `layoutMode === 'preview'` conditional branch
    - If rebuild alone does not fix: check WordPress block validation — ensure `save()` returns `null` (dynamic block) so no validation mismatch resets the attribute
    - If still broken: add debug logging to confirm `setAttributes({ layoutMode: 'preview' })` fires on toggle change
    - _Bug_Condition: layoutMode == 'preview' AND editor still shows edit mode (stale build)_
    - _Expected_Behavior: When layoutMode='preview', renderVisualPreview() output is shown_
    - _Preservation: layoutMode='edit' continues to render FaqItemEditor components_
    - _Requirements: 2.1, 3.1, 3.4_

  - [ ] 3.2 Verify bug condition exploration test (Bug 1 portion) now passes
    - **Property 1: Expected Behavior** - Preview Toggle Renders Visual Accordion
    - **IMPORTANT**: Re-run the SAME test from task 1 (Bug 1 portion) — do NOT write a new test
    - The test from task 1 encodes the expected behavior for preview mode
    - When this test passes, it confirms the preview toggle bug is fixed
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.1_

  - [ ] 3.3 Verify preservation tests still pass after Bug 1 fix
    - **Property 2: Preservation** - Edit Mode Unaffected
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Confirm edit mode rendering, item management, and default behavior remain unchanged
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [ ] 4. Fix Bug 2 — Duplicate Icons

  - [ ] 4.1 Remove CSS border-chevron from `.faq-accordion-icon` in style.css
    - Open `blocks/faq-accordion/style.css`
    - Locate the `.wp-block-wpbits-faq-accordion .faq-accordion-icon` rule
    - Remove: `width: 0.5em`, `height: 0.5em`, `border-right: 2px solid currentColor`, `border-bottom: 2px solid currentColor`, `transform: rotate(-45deg)`
    - Replace with neutral icon container styles:
      ```css
      .wp-block-wpbits-faq-accordion .faq-accordion-icon {
          flex-shrink: 0;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          margin-right: 0.75em;
          transition: transform 0.2s ease;
      }
      ```
    - Update `.faq-accordion-item[open] > summary .faq-accordion-icon` to use `transform: rotate(180deg)` (for chevrons) or remove if not needed for text characters
    - Keep `.has-no-icon .faq-accordion-icon { display: none; }` unchanged
    - Keep `.has-icon-right .faq-accordion-icon` margin rules unchanged
    - Rebuild the block CSS (`npm run build`)
    - _Bug_Condition: selectedIcon IN ['chevron','chevron-right','plus','arrow'] AND iconPosition != 'none' AND CSS border-chevron is visible_
    - _Expected_Behavior: Only the text character icon from ICONS map is rendered, no CSS border artifact_
    - _Preservation: has-no-icon hides icon entirely; has-icon-right positions icon on right side_
    - _Requirements: 2.2, 3.2_

  - [ ] 4.2 Verify bug condition exploration test (Bug 2 portion) now passes
    - **Property 1: Expected Behavior** - Single Icon Display
    - **IMPORTANT**: Re-run the SAME test from task 1 (Bug 2 portion) — do NOT write a new test
    - When this test passes, it confirms only one icon character renders
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.2_

  - [ ] 4.3 Verify preservation tests still pass after Bug 2 fix
    - **Property 2: Preservation** - Icon None and Default Behavior Unaffected
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Confirm icon-none hiding, animation classes, and default styling remain intact
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [ ] 5. Fix Bug 3 — Frontend Styles Not Applying

  - [ ] 5.1 Add `!important` to render.php inline styles and move padding to summary element
    - Open `blocks/faq-accordion/render.php`
    - **Title styles with !important**: Update the title style builder to append `!important`:
      ```php
      if (!empty($title_color)) {
          $title_styles_arr[] = 'color:' . esc_attr($title_color) . ' !important';
      }
      if (!empty($title_font_size) && $title_font_size > 0) {
          $title_styles_arr[] = 'font-size:' . absint($title_font_size) . 'px !important';
      }
      if (!empty($title_font_family)) {
          $title_styles_arr[] = 'font-family:' . esc_attr($title_font_family) . ' !important';
      }
      ```
    - **Move padding to summary element**: Create a separate `$summary_style` variable for padding since the CSS targets `summary` (not the heading tag inside it):
      ```php
      $summary_styles_arr = [];
      if ($title_padding !== 16) {
          $summary_styles_arr[] = 'padding:' . absint($title_padding) . 'px !important';
      }
      $summary_style = '';
      if (!empty($summary_styles_arr)) {
          $summary_style = ' style="' . implode(';', $summary_styles_arr) . '"';
      }
      ```
    - Remove padding from `$title_styles_arr` (it no longer belongs on the heading tag)
    - **Content styles with !important**:
      ```php
      if (!empty($content_color)) {
          $content_styles_arr[] = 'color:' . esc_attr($content_color) . ' !important';
      }
      if (!empty($content_font_size) && $content_font_size > 0) {
          $content_styles_arr[] = 'font-size:' . absint($content_font_size) . 'px !important';
      }
      if (!empty($content_font_family)) {
          $content_styles_arr[] = 'font-family:' . esc_attr($content_font_family) . ' !important';
      }
      if ($content_padding !== 16) {
          $content_styles_arr[] = 'padding:' . absint($content_padding) . 'px !important';
      }
      ```
    - **Update HTML output**: Apply `$summary_style` on `<summary>` element:
      ```php
      $output .= '<summary' . $summary_style . ' aria-expanded="false" aria-controls="' . esc_attr($panel_id) . '">';
      ```
    - Rebuild the block
    - _Bug_Condition: Custom styling attributes set (non-empty color, fontSize > 0, etc.) AND CSS variable rules override inline styles_
    - _Expected_Behavior: Inline styles with !important override CSS defaults; padding applies on summary element_
    - _Preservation: Default attributes produce no inline styles; CSS variable-based styling works unchanged_
    - _Requirements: 2.3, 3.3_

  - [ ] 5.2 Verify bug condition exploration test (Bug 3 portion) now passes
    - **Property 1: Expected Behavior** - Inline Styles Override CSS Defaults
    - **IMPORTANT**: Re-run the SAME test from task 1 (Bug 3 portion) — do NOT write a new test
    - When this test passes, it confirms inline styles apply correctly
    - **EXPECTED OUTCOME**: Test PASSES (confirms bug is fixed)
    - _Requirements: 2.3_

  - [ ] 5.3 Verify preservation tests still pass after Bug 3 fix
    - **Property 2: Preservation** - Default Styling Unaffected
    - **IMPORTANT**: Re-run the SAME tests from task 2 — do NOT write new tests
    - Confirm default rendering (no custom attributes) still uses CSS variable-based styling without any inline overrides
    - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)

- [ ] 6. Checkpoint — Ensure all tests pass
  - Run the full test suite: `npm test` (or `npx jest --run`)
  - Verify all bug condition exploration tests now PASS (confirming all 3 bugs are fixed)
  - Verify all preservation tests still PASS (confirming no regressions)
  - Run the build: `npm run build` to ensure no compilation errors
  - If any tests fail, investigate and fix before marking complete
  - Ask the user if questions arise


## Task Dependency Graph

```json
{
  "waves": [
    ["1", "2"],
    ["3"],
    ["4"],
    ["5"],
    ["6"]
  ]
}
```

## Notes

- All exploration tests (task 1) and preservation tests (task 2) must be written and run BEFORE any implementation begins
- The build step (`npm run build`) is critical for Bug 1 — the source code logic is correct but the compiled output may be stale
- Bug 2 is a CSS-only fix in `style.css` — no JS changes needed
- Bug 3 requires changes to `render.php` (adding `!important` and moving padding to `<summary>`)
- Test framework: Jest + @testing-library/react for editor tests; PHPUnit or Jest for render.php output testing
