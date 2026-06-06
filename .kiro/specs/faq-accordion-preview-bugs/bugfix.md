# Bugfix Requirements Document

## Introduction

The FAQ Accordion block plugin (ai-faq-generator) has three bugs introduced during the partial implementation of the "Visual Preview Mode & Enhanced Styling Controls" feature (GitHub issue #32). These bugs prevent the visual preview toggle from working, cause duplicate icons to appear when changing the icon selection, and prevent frontend styles (title color, font-size, etc.) from applying correctly to the rendered output.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the user toggles "Visual Preview Mode" ON in the sidebar THEN the left editor continues showing input fields (edit mode) instead of switching to the accordion UI preview

1.2 WHEN the user selects a different icon from the Icon Selection dropdown THEN the new icon character appears ON TOP of the old CSS-drawn chevron icon, resulting in two icons being visible simultaneously

1.3 WHEN the user changes title color, font-size, or font-family in the sidebar styling controls THEN the frontend rendered output does not reflect those style changes because CSS stylesheet rules override the inline styles

### Expected Behavior (Correct)

2.1 WHEN the user toggles "Visual Preview Mode" ON in the sidebar THEN the left editor SHALL immediately switch to displaying the accordion UI (visual preview) showing accordion items with proper styling; WHEN toggled OFF the editor SHALL return to showing the input fields (edit mode)

2.2 WHEN the user selects a different icon from the Icon Selection dropdown THEN the system SHALL display ONLY the selected icon character (▾, ▸, +, →, or none) without any CSS-drawn duplicate chevron artifact

2.3 WHEN the user changes title color, font-size, font-family, or padding in the sidebar styling controls THEN the frontend rendered output SHALL apply those inline styles with sufficient specificity to override the default stylesheet rules

### Unchanged Behavior (Regression Prevention)

3.1 WHEN the user is in edit mode (layoutMode = 'edit') THEN the system SHALL CONTINUE TO display editable input fields for question and answer text with move/remove action buttons

3.2 WHEN the icon position is set to 'none' THEN the system SHALL CONTINUE TO hide all icons entirely regardless of which icon is selected

3.3 WHEN no custom styling values are set (defaults) THEN the system SHALL CONTINUE TO render the accordion with the default CSS variable-based styling from style.css

3.4 WHEN the user adds, removes, or reorders FAQ items THEN the system SHALL CONTINUE TO update the items array correctly regardless of current layoutMode

3.5 WHEN the accordion is rendered on the frontend with animation enabled THEN the system SHALL CONTINUE TO animate the open/close transitions using the grid-template-rows technique
