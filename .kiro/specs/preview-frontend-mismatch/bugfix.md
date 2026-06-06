# Bugfix Requirements Document

## Introduction

The Visual Preview Mode in the FAQ Accordion block editor does not match the frontend rendered output. The frontend uses native `<details>/<summary>` elements styled by `style.css`, while the editor preview uses `<div>` elements with custom classes (`faq-accordion-summary`, `faq-accordion-content`) that don't match the CSS selectors in `style.css`. This causes the preview to appear as plain unstyled text without borders, backgrounds, spacing, or proper accordion structure.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the user enables Visual Preview Mode in the editor THEN the system renders FAQ items using `<div class="faq-accordion-item">` instead of `<details class="faq-accordion-item">`, causing `style.css` border, border-radius, and margin rules not to apply

1.2 WHEN the user enables Visual Preview Mode in the editor THEN the system renders the clickable header using `<div class="faq-accordion-summary">` instead of `<summary>`, causing `style.css` background-color (#f9f9f9), padding, font-weight, and flex layout rules not to apply

1.3 WHEN the user enables Visual Preview Mode in the editor THEN the system does not apply the accordion icon positioning styles from `style.css` (e.g., `.has-icon-right` flex-direction, `.has-no-icon` display:none) because the HTML structure doesn't match the expected `details > summary > .faq-accordion-icon` selector hierarchy

1.4 WHEN the user enables Visual Preview Mode and an item is open THEN the system does not apply the open-state styles (accent border color, icon rotation via `[open]` attribute) because divs don't support the native `[open]` attribute

1.5 WHEN the user enables Visual Preview Mode THEN the content panel lacks the top border separator (`border-top: 1px solid #eee`) from `style.css` because the `.faq-accordion-content` selector requires it to be nested inside `details.faq-accordion-item`

### Expected Behavior (Correct)

2.1 WHEN the user enables Visual Preview Mode in the editor THEN the system SHALL render FAQ items with the same visual appearance as the frontend, including borders (1px solid #ddd), border-radius (4px), and proper margin-bottom spacing between items

2.2 WHEN the user enables Visual Preview Mode in the editor THEN the system SHALL render clickable headers with the same visual appearance as the frontend, including background-color (#f9f9f9), proper padding, font-weight (600), and flexbox layout with gap

2.3 WHEN the user enables Visual Preview Mode in the editor THEN the system SHALL apply icon positioning consistent with the frontend: left-aligned by default, right-aligned with flex-direction row-reverse when `iconPosition` is "right", and hidden when `iconPosition` is "none"

2.4 WHEN the user enables Visual Preview Mode and an item is in the open state THEN the system SHALL display the open-state visual feedback matching the frontend: accent border color (#0073aa) on the item and rotated icon (180deg)

2.5 WHEN the user enables Visual Preview Mode THEN the system SHALL render the content panel with a top border separator (1px solid #eee) matching the frontend appearance

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the user is in the classic Edit Mode (layoutMode = "edit") THEN the system SHALL CONTINUE TO render the FaqItemEditor input fields with text inputs and action buttons as before

3.2 WHEN the FAQ block is rendered on the frontend via render.php THEN the system SHALL CONTINUE TO use native `<details>/<summary>` elements with the same `style.css` styling as before

3.3 WHEN the user interacts with the Visual Preview Mode (clicks to expand/collapse items) THEN the system SHALL CONTINUE TO toggle item open/closed state via the `_open` attribute on items

3.4 WHEN the user applies custom styling attributes (titleColor, titleFontSize, contentColor, etc.) THEN the system SHALL CONTINUE TO apply those inline styles in the Visual Preview Mode

3.5 WHEN the user configures openFirstItem to true THEN the system SHALL CONTINUE TO show the first FAQ item in the expanded state in both preview and frontend

---

## Bug Condition (Structured Pseudocode)

```pascal
FUNCTION isBugCondition(X)
  INPUT: X of type BlockRenderContext
  OUTPUT: boolean
  
  // Returns true when the block is in Visual Preview Mode in the editor
  RETURN X.layoutMode = "preview" AND X.context = "editor"
END FUNCTION
```

```pascal
// Property: Fix Checking - Visual Preview matches frontend appearance
FOR ALL X WHERE isBugCondition(X) DO
  previewOutput ← renderVisualPreview'(X)
  ASSERT visualAppearance(previewOutput) ≈ visualAppearance(renderFrontend(X))
  // Specifically: borders, backgrounds, icon positioning, open-state styling, content separators
END FOR
```

```pascal
// Property: Preservation Checking
FOR ALL X WHERE NOT isBugCondition(X) DO
  ASSERT F(X) = F'(X)
  // Edit mode rendering and frontend rendering remain unchanged
END FOR
```
