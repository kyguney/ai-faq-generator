# Requirements Document

## Introduction

This feature enhances the FAQ Accordion block's styling capabilities by adding background color controls for title and content areas, font style controls (bold, italic, underline, text-transform) for the title heading tag, and expanding the accordion icon selection to use SVG-based icons for better scalability and visual variety.

## Glossary

- **Block_Editor**: The WordPress Gutenberg editor interface where users configure the FAQ Accordion block.
- **Inspector_Panel**: The sidebar settings panel in the Block_Editor that contains styling controls.
- **FAQ_Accordion_Block**: The `wpbits/faq-accordion` dynamic block that renders FAQ items in a collapsible accordion format.
- **Title_Area**: The clickable `<summary>` element of each accordion item that contains the heading tag.
- **Content_Area**: The expandable `<div>` element of each accordion item that contains the answer text.
- **ColorPalette_Component**: The WordPress `ColorPalette` component that renders a theme-aware color picker with predefined palette swatches and a custom color option.
- **SVG_Icon**: A Scalable Vector Graphics element used as the accordion toggle indicator, sourced from `@wordpress/icons` or custom SVG markup.
- **Font_Style_Controls**: A set of toggle/select controls for font-weight, font-style, text-decoration, and text-transform CSS properties.
- **Render_Template**: The `render.php` file responsible for server-side HTML output of the FAQ_Accordion_Block.

## Requirements

### Requirement 1: Title Background Color Control

**User Story:** As a site editor, I want to set a background color for the accordion title area, so that I can visually distinguish the title from the surrounding content.

#### Acceptance Criteria

1. WHEN a user opens the "Title Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a ColorPalette_Component labeled "Title Background Color."
2. WHEN a user selects a color from the ColorPalette_Component for title background, THE FAQ_Accordion_Block SHALL store the selected value in the `titleBackgroundColor` attribute as a valid CSS color string.
3. WHEN the `titleBackgroundColor` attribute contains a non-empty value, THE FAQ_Accordion_Block SHALL apply that color as the `background-color` inline style on the Title_Area in both the Block_Editor preview and the Render_Template output.
4. WHEN the `titleBackgroundColor` attribute is empty or cleared, THE FAQ_Accordion_Block SHALL render the Title_Area without an explicit background-color inline style.
5. THE FAQ_Accordion_Block SHALL default the `titleBackgroundColor` attribute to an empty string.

### Requirement 2: Content Background Color Control

**User Story:** As a site editor, I want to set a background color for the accordion content area, so that I can create visual contrast between the content and the page background.

#### Acceptance Criteria

1. WHEN a user opens the "Content Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a ColorPalette_Component labeled "Content Background Color."
2. WHEN a user selects a color from the ColorPalette_Component for content background, THE FAQ_Accordion_Block SHALL store the selected value in the `contentBackgroundColor` attribute as a valid CSS color string.
3. WHEN the `contentBackgroundColor` attribute contains a non-empty value, THE FAQ_Accordion_Block SHALL apply that color as the `background-color` inline style on the Content_Area in both the Block_Editor preview and the Render_Template output.
4. WHEN the `contentBackgroundColor` attribute is empty or cleared, THE FAQ_Accordion_Block SHALL render the Content_Area without an explicit background-color inline style.
5. THE FAQ_Accordion_Block SHALL default the `contentBackgroundColor` attribute to an empty string.

### Requirement 3: Title Font Weight Control

**User Story:** As a site editor, I want to control the font weight of the accordion title, so that I can emphasize or de-emphasize the heading text.

#### Acceptance Criteria

1. WHEN a user opens the "Title Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a SelectControl for "Font Weight" with options: Default, Normal (400), Medium (500), Semi-Bold (600), Bold (700), and Extra-Bold (800).
2. WHEN a user selects a font weight value, THE FAQ_Accordion_Block SHALL store the selected value in the `titleFontWeight` attribute as a string.
3. WHEN the `titleFontWeight` attribute contains a non-empty value other than the default, THE FAQ_Accordion_Block SHALL apply that value as the `font-weight` CSS property on the title heading tag in both the Block_Editor preview and the Render_Template output.
4. THE FAQ_Accordion_Block SHALL default the `titleFontWeight` attribute to an empty string.

### Requirement 4: Title Font Style (Italic) Control

**User Story:** As a site editor, I want to toggle italic styling on the accordion title, so that I can match the typographic style of my site design.

#### Acceptance Criteria

1. WHEN a user opens the "Title Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a ToggleControl labeled "Italic."
2. WHEN a user enables the italic toggle, THE FAQ_Accordion_Block SHALL store `"italic"` in the `titleFontStyle` attribute.
3. WHEN a user disables the italic toggle, THE FAQ_Accordion_Block SHALL store an empty string in the `titleFontStyle` attribute.
4. WHEN the `titleFontStyle` attribute equals `"italic"`, THE FAQ_Accordion_Block SHALL apply `font-style: italic` on the title heading tag in both the Block_Editor preview and the Render_Template output.
5. THE FAQ_Accordion_Block SHALL default the `titleFontStyle` attribute to an empty string.

### Requirement 5: Title Text Decoration (Underline) Control

**User Story:** As a site editor, I want to toggle underline decoration on the accordion title, so that I can draw attention to the heading text.

#### Acceptance Criteria

1. WHEN a user opens the "Title Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a ToggleControl labeled "Underline."
2. WHEN a user enables the underline toggle, THE FAQ_Accordion_Block SHALL store `"underline"` in the `titleTextDecoration` attribute.
3. WHEN a user disables the underline toggle, THE FAQ_Accordion_Block SHALL store an empty string in the `titleTextDecoration` attribute.
4. WHEN the `titleTextDecoration` attribute equals `"underline"`, THE FAQ_Accordion_Block SHALL apply `text-decoration: underline` on the title heading tag in both the Block_Editor preview and the Render_Template output.
5. THE FAQ_Accordion_Block SHALL default the `titleTextDecoration` attribute to an empty string.

### Requirement 6: Title Text Transform Control

**User Story:** As a site editor, I want to control the text capitalization of the accordion title, so that I can enforce consistent heading styles across the page.

#### Acceptance Criteria

1. WHEN a user opens the "Title Styling" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a SelectControl labeled "Text Transform" with options: None, Uppercase, Lowercase, and Capitalize.
2. WHEN a user selects a text-transform value, THE FAQ_Accordion_Block SHALL store the selected value in the `titleTextTransform` attribute as a string.
3. WHEN the `titleTextTransform` attribute contains a non-empty value other than "none," THE FAQ_Accordion_Block SHALL apply that value as the `text-transform` CSS property on the title heading tag in both the Block_Editor preview and the Render_Template output.
4. WHEN the `titleTextTransform` attribute is empty or equals "none," THE FAQ_Accordion_Block SHALL render the title heading tag without an explicit text-transform inline style.
5. THE FAQ_Accordion_Block SHALL default the `titleTextTransform` attribute to an empty string.

### Requirement 7: SVG-Based Accordion Icon Set

**User Story:** As a site editor, I want a wider variety of SVG-based accordion icons to choose from, so that the toggle indicators match my site's visual style and scale cleanly at any size.

#### Acceptance Criteria

1. THE FAQ_Accordion_Block SHALL replace the existing Unicode character icon set with SVG-based icons sourced from `@wordpress/icons` or custom inline SVG markup.
2. THE FAQ_Accordion_Block SHALL provide a minimum of six icon options: chevron-down, chevron-right, plus-minus, arrow-down, arrow-right, and none.
3. WHEN a user opens the "Icon Selection" section in the Inspector_Panel, THE FAQ_Accordion_Block SHALL display a visual icon picker showing each available SVG_Icon with a label.
4. WHEN a user selects an SVG_Icon, THE FAQ_Accordion_Block SHALL store the icon identifier in the `selectedIcon` attribute as a string.
5. WHEN the `selectedIcon` attribute contains a valid icon identifier other than "none," THE FAQ_Accordion_Block SHALL render the corresponding SVG markup as the accordion toggle indicator in both the Block_Editor preview and the Render_Template output.
6. WHEN the `selectedIcon` attribute equals "none," THE FAQ_Accordion_Block SHALL render no icon element in the accordion items.
7. THE FAQ_Accordion_Block SHALL render SVG icons at a size that scales proportionally with the title font size, defaulting to 20x20 pixels when no custom title font size is set.
8. THE FAQ_Accordion_Block SHALL default the `selectedIcon` attribute to `"chevron-down"`.

### Requirement 8: Backward Compatibility for Icon Attribute

**User Story:** As a site editor with existing FAQ blocks, I want my current icon selections to continue working after the update, so that published content is not disrupted.

#### Acceptance Criteria

1. WHEN the FAQ_Accordion_Block encounters a saved `selectedIcon` attribute value of `"chevron"`, THE FAQ_Accordion_Block SHALL map the value to the new `"chevron-down"` SVG_Icon for rendering.
2. WHEN the FAQ_Accordion_Block encounters a saved `selectedIcon` attribute value of `"plus"`, THE FAQ_Accordion_Block SHALL map the value to the new `"plus-minus"` SVG_Icon for rendering.
3. WHEN the FAQ_Accordion_Block encounters a saved `selectedIcon` attribute value of `"arrow"`, THE FAQ_Accordion_Block SHALL map the value to the new `"arrow-down"` SVG_Icon for rendering.
4. IF the FAQ_Accordion_Block encounters an unrecognized `selectedIcon` attribute value, THEN THE FAQ_Accordion_Block SHALL fall back to the `"chevron-down"` SVG_Icon.
