# FAQ Accordion Preview Bugs — Bugfix Design

## Overview

Three bugs were introduced during the partial implementation of the "Visual Preview Mode & Enhanced Styling Controls" feature for the FAQ Accordion block. The bugs affect:
1. The Visual Preview Mode toggle not switching the editor view
2. Duplicate icons appearing (CSS-drawn chevron + span-based icon character)
3. Frontend inline styles being overridden by CSS variable-based stylesheet rules

The fix strategy is minimal and targeted: correct the toggle logic in edit.js, remove the CSS chevron conflict in `style.css`, and add `!important` declarations to inline styles in `render.php` where they conflict with the stylesheet's CSS-variable defaults.

## Glossary

- **Bug_Condition (C)**: The set of conditions under which any of the three bugs manifest — preview toggle is enabled, a non-default icon is selected, or custom styling attributes are set
- **Property (P)**: The correct behavior — preview renders, only one icon displays, inline styles apply
- **Preservation**: Existing edit-mode editing, default styling, animation, and item management must remain unchanged
- **layoutMode**: Block attribute (`"edit"` | `"preview"`) that determines which editor view renders
- **selectedIcon**: Block attribute determining which icon character to display (`"chevron"`, `"chevron-right"`, `"plus"`, `"arrow"`, `"none"`)
- **ICONS object**: Map in `edit.js` that converts `selectedIcon` values to unicode characters
- **CSS chevron**: The `.faq-accordion-icon` class in `style.css` that draws a chevron via `border-right`/`border-bottom` + rotation
- **Inline styles**: The `style="..."` attributes rendered by `render.php` for custom title/content styling

## Bug Details

### Bug Condition

The bugs manifest in three distinct scenarios that share a common root: incomplete migration from CSS-only rendering to a hybrid system (CSS + JS icon characters + inline styles).

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type { layoutMode, selectedIcon, iconPosition, titleColor, titleFontSize, titleFontFamily, titlePadding, contentColor, contentFontSize, contentFontFamily, contentPadding }
  OUTPUT: boolean
  
  bug1 := input.layoutMode == 'preview'
          AND editorStillShowsEditMode(input)
  
  bug2 := input.selectedIcon IN ['chevron', 'chevron-right', 'plus', 'arrow']
          AND input.iconPosition != 'none'
          AND cssChevronBorderIsVisible(input)
          AND spanIconCharIsVisible(input)
  
  bug3 := (input.titleColor != '' OR input.titleFontSize > 0 OR input.titleFontFamily != '' OR input.titlePadding != 16
           OR input.contentColor != '' OR input.contentFontSize > 0 OR input.contentFontFamily != '' OR input.contentPadding != 16)
          AND cssVariableRulesOverrideInlineStyles(input)
  
  RETURN bug1 OR bug2 OR bug3
END FUNCTION
```

### Examples

- **Bug 1**: User sets layoutMode to `"preview"` via the toggle → editor still renders `<FaqItemEditor>` components because the toggle's `onChange` callback may not be firing correctly or the condition check fails.
- **Bug 2**: User selects "Plus / Minus ±" icon → the `<span class="faq-accordion-icon">+</span>` renders the "+" text BUT the CSS `.faq-accordion-icon` class also draws a rotated border-chevron beneath/on top of it, producing two overlapping icons.
- **Bug 3**: User sets titleColor to `#ff0000` → `render.php` outputs `style="color:#ff0000"` on the heading, but the stylesheet rule `.wp-block-wpbits-faq-accordion .faq-accordion-item summary` sets `font-size:1em` and padding via CSS variables with higher specificity, overriding the inline values that lack `!important`.
- **Edge Case**: When defaults are used (no custom styling, layoutMode = 'edit', icon = 'chevron'), all rendering works correctly — this is the preservation baseline.

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Edit mode (layoutMode = 'edit') must continue displaying editable input fields with move/remove action buttons
- Adding, removing, reordering FAQ items must work identically in both modes
- Icon position 'none' must continue to hide all icons entirely
- Default CSS variable-based styling must continue working when no custom values are set
- Animation (grid-template-rows technique) must continue functioning on the frontend
- Mouse/keyboard interactions in edit mode must remain intact

**Scope:**
All inputs where the bug conditions do NOT hold should be completely unaffected:
- layoutMode = 'edit' rendering path
- Default icon selection ('chevron') with no span text (CSS-only chevron was the original design)
- Default styling values (empty colors, 0 font sizes, default padding)
- Frontend rendering when no custom attributes are set

## Hypothesized Root Cause

Based on code analysis, the root causes are:

1. **Bug 1 — Preview Toggle Not Working**: The `edit.js` code checks `layoutMode === 'preview'` and the `InspectorPanel.js` correctly calls `setAttributes({ layoutMode: value ? 'preview' : 'edit' })`. The code appears structurally correct. The most likely cause is a **stale build** — the compiled `build/index.js` doesn't reflect the current source. The `layoutMode` attribute was added to `block.json` but the block may not have been rebuilt after the toggle logic was added to `edit.js`. Alternatively, if the build IS current, there may be a WordPress block validation issue where the stored attribute doesn't match the registered default, causing re-serialization to reset it.

2. **Bug 2 — Duplicate Icons**: The CSS in `style.css` defines `.faq-accordion-icon` with `border-right: 2px solid currentColor` and `border-bottom: 2px solid currentColor` plus `transform: rotate(-45deg)` to draw a CSS chevron. The `render.php` and `edit.js` now ALSO place a text character (▾, ▸, +, →) inside a `<span class="faq-accordion-icon">`. The result is BOTH the CSS-border chevron AND the text character render simultaneously. The CSS `::before` pseudo-element is `display: none`, but the border-based chevron on the span itself is still active.

3. **Bug 3 — Frontend Styles Not Applying**: The `render.php` generates inline styles like `style="color:#ff0000;font-size:20px"` on the heading element. However, the compiled stylesheet has rules like `.wp-block-wpbits-faq-accordion .faq-accordion-item summary` which sets `font-size:1em`, `padding:var(--wp--custom--faq-accordion--header-padding,1em 1.25em)`, and `font-weight:600`. These class-based selectors have equal or higher specificity than the element's inline style in some browsers, and the CSS variable `padding` declaration specifically overrides the inline `padding` because the inline style is on the `<h3>` child element, not on the `<summary>` element that carries the CSS rule.

## Correctness Properties

Property 1: Bug Condition - Visual Preview Toggle Rendering

_For any_ block state where `layoutMode === 'preview'` and the block has at least one FAQ item, the editor SHALL render the visual accordion preview (with `renderVisualPreview()`) and SHALL NOT render the classic editor input fields.

**Validates: Requirements 2.1**

Property 2: Bug Condition - Single Icon Display

_For any_ block state where `selectedIcon` is one of `['chevron', 'chevron-right', 'plus', 'arrow']` and `iconPosition` is not `'none'`, the rendered output SHALL display exactly ONE icon indicator (the text character from the ICONS map) without any CSS-drawn border-chevron artifact.

**Validates: Requirements 2.2**

Property 3: Bug Condition - Inline Style Application

_For any_ block state where custom styling attributes are set (non-empty titleColor, titleFontSize > 0, non-empty titleFontFamily, or titlePadding ≠ 16), the frontend rendered output SHALL visually apply those styles, overriding any conflicting stylesheet defaults.

**Validates: Requirements 2.3**

Property 4: Preservation - Edit Mode and Default Behavior

_For any_ block state where `layoutMode === 'edit'`, the editor SHALL render the classic editor with editable input fields. For any block state where all styling attributes are at their defaults and `selectedIcon === 'chevron'`, the frontend SHALL render with the default CSS variable-based styling.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

## Fix Implementation

### Changes Required

#### Bug 1 Fix — Visual Preview Toggle

**File**: `blocks/faq-accordion/src/edit.js`

**Root Cause Confirmation**: The source code logic is correct. The fix requires **rebuilding the block** so the compiled `build/index.js` reflects the current source. If the build is already current, add a `console.log` to verify the attribute is being set and check for block validation errors in the browser console.

**Specific Changes**:
1. **Rebuild the block**: Run `npm run build` (or the project's webpack build) to compile the latest `edit.js` into `build/index.js`
2. **Verify attribute persistence**: Confirm `layoutMode` attribute is registered in `block.json` (already done) and the `save()` function returns `null` (dynamic block, already done), so no block validation mismatch occurs

If rebuild alone doesn't fix:
3. **Add explicit attribute extraction**: Ensure `layoutMode` is destructured from `attributes` (already done in source)
4. **Check for editor iframe scoping**: In newer WordPress versions, the block editor may render in an iframe; ensure the style.css is loaded in both contexts

#### Bug 2 Fix — Duplicate Icons

**File**: `blocks/faq-accordion/style.css`

**Function**: `.faq-accordion-icon` CSS class

**Specific Changes**:
1. **Remove CSS border-based chevron**: The `.faq-accordion-icon` selector currently sets `border-right: 2px solid currentColor`, `border-bottom: 2px solid currentColor`, `width: 0.5em`, `height: 0.5em`, and `transform: rotate(-45deg)`. These create a CSS-drawn chevron that conflicts with the span text content.
2. **Replace with neutral icon container styles**: Change `.faq-accordion-icon` to be a simple flex-aligned inline element without borders or transforms:
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
3. **Update open-state transform**: Change `.faq-accordion-item[open] > summary .faq-accordion-icon` to use a simpler transform (e.g., `rotate(180deg)` for chevron rotation) or remove it since icons like `+` and `→` don't benefit from rotation.
4. **Keep the `has-no-icon` and `has-icon-right` rules** as they are — they still apply correctly to the container span.

#### Bug 3 Fix — Frontend Styles Not Applying

**File**: `blocks/faq-accordion/render.php`

**Function**: `render_faq_accordion_block()`

**Specific Changes**:
1. **Move inline styles to the `<summary>` element**: Currently, `$title_style` is applied to the `<h3>` (or other title tag) INSIDE `<summary>`, but the CSS targets `summary` directly with padding and font-size. Move the `padding` style to the `<summary>` element and keep `color`/`font-size`/`font-family` on the title tag.
2. **Add `!important` to inline style declarations** that conflict with the stylesheet's CSS variable fallbacks:
   ```php
   if (!empty($title_color)) {
       $title_styles_arr[] = 'color:' . esc_attr($title_color) . ' !important';
   }
   if (!empty($title_font_size) && $title_font_size > 0) {
       $title_styles_arr[] = 'font-size:' . absint($title_font_size) . 'px !important';
   }
   ```
3. **Separate summary-level styles from title-level styles**: Create a `$summary_style` for padding (which the CSS applies to `summary`) and keep typography styles on the title tag:
   ```php
   $summary_styles_arr = [];
   if ($title_padding !== 16) {
       $summary_styles_arr[] = 'padding:' . absint($title_padding) . 'px !important';
   }
   ```
4. **Apply `!important` to content styles too**: The `.faq-accordion-content` rule sets `padding:var(...)` which overrides inline content padding:
   ```php
   if ($content_padding !== 16) {
       $content_styles_arr[] = 'padding:' . absint($content_padding) . 'px !important';
   }
   ```
5. **Update the HTML output** to apply `$summary_style` on `<summary>` and `$title_style` (color/font) on the heading tag.

**File**: `blocks/faq-accordion/style.css`

**Additional Changes** (alternative to `!important`):
- Lower the specificity of default styles by using CSS custom properties that the inline styles can override:
  ```css
  .wp-block-wpbits-faq-accordion .faq-accordion-item summary {
      padding: var(--faq-title-padding, var(--wp--custom--faq-accordion--header-padding, 1em 1.25em));
      font-size: var(--faq-title-font-size, 1em);
  }
  ```
  Then in `render.php`, set these custom properties as inline styles on the wrapper instead.

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate each bug on unfixed code, then verify the fixes work correctly and preserve existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bugs BEFORE implementing the fix. Confirm or refute the root cause analysis. If we refute, we will need to re-hypothesize.

**Test Plan**: Write unit tests using Jest + @testing-library/react that simulate the block editor conditions. Run these tests on the UNFIXED code to observe failures.

**Test Cases**:
1. **Preview Toggle Test**: Render `<Edit>` with `layoutMode="preview"` and assert `renderVisualPreview` output is present (will fail if build is stale or condition is broken)
2. **Duplicate Icon Test**: Render the frontend HTML from `render.php` with `selectedIcon="plus"` and inspect the `.faq-accordion-icon` element — assert it has NO `border-right` or `border-bottom` computed styles (will fail on unfixed code)
3. **Inline Style Override Test**: Render with `titleColor="#ff0000"` and `titleFontSize=20`, then check computed color/font-size on the title element (will fail when CSS overrides inline)
4. **Edge Case — None icon**: Render with `selectedIcon="none"` and verify no icon element is present at all

**Expected Counterexamples**:
- Bug 1: Editor renders `<FaqItemEditor>` components even when `layoutMode="preview"`
- Bug 2: `.faq-accordion-icon` span shows both border-chevron and text character
- Bug 3: Title element computed font-size is `1em` (from CSS) instead of `20px` (from inline)

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed function produces the expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  IF input.layoutMode == 'preview' THEN
    rendered := Edit(input)
    ASSERT rendered CONTAINS '.faq-accordion-summary'
    ASSERT rendered NOT CONTAINS '.faq-item-editor'
  END IF
  
  IF input.selectedIcon != 'none' AND input.iconPosition != 'none' THEN
    html := render_faq_accordion_block(input)
    iconElement := querySelector('.faq-accordion-icon', html)
    ASSERT computedStyle(iconElement).borderRight == 'none'
    ASSERT iconElement.textContent == ICONS[input.selectedIcon]
  END IF
  
  IF input.titleFontSize > 0 THEN
    html := render_faq_accordion_block(input)
    titleElement := querySelector('summary > *:last-child', html)
    ASSERT computedStyle(titleElement).fontSize == input.titleFontSize + 'px'
  END IF
END FOR
```

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed function produces the same result as the original function.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT render_faq_accordion_block_original(input) == render_faq_accordion_block_fixed(input)
  ASSERT Edit_original(input).html == Edit_fixed(input).html
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many combinations of attribute values automatically
- It catches regressions in edge cases (e.g., empty items, boundary padding values)
- It provides strong guarantees that default behavior is unchanged

**Test Plan**: Observe behavior on UNFIXED code first for default-attribute rendering, then write property-based tests capturing that behavior.

**Test Cases**:
1. **Edit Mode Preservation**: Verify that with `layoutMode='edit'`, all item editing operations (add, remove, reorder, update) continue to work identically after the fix
2. **Default Styling Preservation**: Verify that when no custom styling attributes are set, the rendered HTML from `render.php` is byte-for-byte identical before and after the fix
3. **Animation Preservation**: Verify that `has-animation` class and grid-template-rows CSS technique remain unchanged
4. **Icon None Preservation**: Verify that `selectedIcon='none'` or `iconPosition='none'` still hides icons completely

### Unit Tests

- Test `edit.js` conditional rendering: assert `renderVisualPreview()` output when `layoutMode='preview'`
- Test `edit.js` conditional rendering: assert `renderClassicEditor()` output when `layoutMode='edit'`
- Test `render.php` output with various `selectedIcon` values — verify single icon character in HTML
- Test `render.php` inline style output with custom attributes — verify `!important` declarations present
- Test `getBlockClasses` utility continues to produce correct class strings

### Property-Based Tests

- Generate random attribute combinations (`selectedIcon` × `iconPosition` × `layoutMode`) and verify editor renders correct view
- Generate random styling attribute values and verify `render.php` inline styles always include `!important` when non-default
- Generate random FAQ item arrays and verify the fix doesn't alter item rendering logic

### Integration Tests

- Full build + render cycle: build the block, load in WordPress editor, toggle preview mode, verify DOM output
- Frontend render test: call `render_faq_accordion_block()` with various attribute combinations, parse HTML, verify structure
- CSS specificity test: load `style-index.css` + rendered HTML in a DOM, verify inline styles win over stylesheet rules
