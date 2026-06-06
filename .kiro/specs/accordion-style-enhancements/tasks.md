# Implementation Plan: Accordion Style Enhancements

## Overview

Implement background color controls, title font style controls, and an SVG-based icon system for the FAQ Accordion block. The work is organized into utility modules first, then block attribute registration, editor UI updates, server-side rendering, and integration wiring.

## Tasks

- [x] 1. Create utility modules for icon registry and title style builder
  - [x] 1.1 Create `src/utils/iconRegistry.js` with SVG icon registry, legacy mapping, resolveIconId, and getIconSize functions
    - Export `LEGACY_ICON_MAP`, `ICON_REGISTRY`, `DEFAULT_ICON_SIZE`, `resolveIconId()`, and `getIconSize()`
    - Import icons from `@wordpress/icons` (chevronDown, chevronRight, arrowDown, arrowRight)
    - Include custom SVG for plus-minus icon
    - Include "none" entry that renders no icon
    - _Requirements: 7.1, 7.2, 8.1, 8.2, 8.3, 8.4_

  - [x] 1.2 Create `src/utils/buildTitleStyles.js` with the `buildTitleHeadingStyle` function
    - Accept attributes object, return CSS style object
    - Include fontWeight, fontStyle, textDecoration, textTransform only when non-empty and non-default
    - _Requirements: 3.3, 4.4, 5.4, 6.3, 6.4_

  - [x] 1.3 Write property tests for `iconRegistry.js`
    - **Property 5: Legacy icon migration mapping**
    - **Property 6: Unrecognized icon fallback**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4**

  - [x] 1.4 Write property tests for `buildTitleStyles.js`
    - **Property 2: Title font styling application**
    - **Validates: Requirements 3.3, 4.4, 5.4, 6.3, 6.4**

  - [x] 1.5 Write property test for icon proportional sizing
    - **Property 4: SVG icon proportional sizing**
    - **Validates: Requirements 7.7**

- [x] 2. Register new block attributes in block.json
  - [x] 2.1 Add new attributes to `block.json`
    - Add `titleBackgroundColor` (string, default: "")
    - Add `contentBackgroundColor` (string, default: "")
    - Add `titleFontWeight` (string, default: "")
    - Add `titleFontStyle` (string, default: "")
    - Add `titleTextDecoration` (string, default: "")
    - Add `titleTextTransform` (string, default: "")
    - Change `selectedIcon` default from `"chevron"` to `"chevron-down"`
    - _Requirements: 1.5, 2.5, 3.4, 4.5, 5.5, 6.5, 7.8_

- [x] 3. Update InspectorPanel with new styling controls
  - [x] 3.1 Add background color controls and font style controls to `InspectorPanel.js`
    - Import `ColorPalette`, `ButtonGroup`, `Button` from `@wordpress/components`
    - Import `Icon` from `@wordpress/icons`
    - Import `ICON_REGISTRY`, `resolveIconId` from `../utils/iconRegistry`
    - Add ColorPalette labeled "Title Background Color" in Title Styling panel
    - Add SelectControl for font weight (Default, Normal/400, Medium/500, Semi-Bold/600, Bold/700, Extra-Bold/800)
    - Add ToggleControl for Italic
    - Add ToggleControl for Underline
    - Add SelectControl for Text Transform (None, Uppercase, Lowercase, Capitalize)
    - Add ColorPalette labeled "Content Background Color" in Content Styling panel
    - Replace Icon Selection dropdown with visual ButtonGroup picker showing SVG previews
    - _Requirements: 1.1, 1.2, 2.1, 2.2, 3.1, 3.2, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 6.1, 6.2, 7.3, 7.4_

  - [x] 3.2 Write unit tests for new InspectorPanel controls
    - Verify ColorPalette components render with correct labels
    - Verify font style controls store correct attribute values
    - Verify visual icon picker renders all SVG options
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.3_

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Update editor preview rendering in edit.js
  - [x] 5.1 Integrate background colors and title font styles into `edit.js`
    - Import `buildTitleHeadingStyle` from `./utils/buildTitleStyles`
    - Import `resolveIconId`, `getIconSize`, `ICON_REGISTRY` from `./utils/iconRegistry`
    - Import `Icon` from `@wordpress/icons`
    - Add `titleBackgroundColor` and `contentBackgroundColor` to destructured attributes
    - Add `titleFontWeight`, `titleFontStyle`, `titleTextDecoration`, `titleTextTransform` to destructured attributes
    - Update `getTitleStyle()` to include `backgroundColor` from `titleBackgroundColor`
    - Update `getContentStyle()` to include `backgroundColor` from `contentBackgroundColor`
    - Apply `buildTitleHeadingStyle()` output to the title heading tag's style
    - Replace Unicode ICONS map with SVG rendering using `resolveIconId` and `ICON_REGISTRY`
    - Render SVG icons at proportional size via `getIconSize(titleFontSize)`
    - Handle "none" icon by rendering no icon element
    - _Requirements: 1.3, 1.4, 2.3, 2.4, 3.3, 4.4, 5.4, 6.3, 6.4, 7.5, 7.6, 7.7_

  - [x] 5.2 Write property test for background color application in editor
    - **Property 1: Background color attribute application**
    - **Validates: Requirements 1.3, 1.4, 2.3, 2.4**

  - [x] 5.3 Write property test for SVG icon rendering in editor
    - **Property 3: SVG icon rendering for valid identifiers**
    - **Validates: Requirements 7.5, 7.6**

- [x] 6. Update server-side rendering in render.php
  - [x] 6.1 Add background colors, font styles, and SVG icon rendering to `render.php`
    - Extract new attributes: `titleBackgroundColor`, `contentBackgroundColor`, `titleFontWeight`, `titleFontStyle`, `titleTextDecoration`, `titleTextTransform`
    - Add `$title_bg_color` to `$summary_styles_arr` when non-empty
    - Add `$content_bg_color` to `$content_styles_arr` when non-empty
    - Add font-weight, font-style, text-decoration, text-transform to title heading tag inline styles (only when non-empty/non-default)
    - Create `$legacy_icon_map` array mapping old identifiers to new ones
    - Create `resolve_icon_id()` helper function with fallback to `'chevron-down'`
    - Create `get_svg_icon_markup()` function returning SVG strings for each icon identifier
    - Compute icon size from `$title_font_size` using proportional formula (Math.round equivalent)
    - Replace `$icon_chars` Unicode mapping with SVG icon rendering
    - Handle "none" icon by rendering no icon element
    - _Requirements: 1.3, 1.4, 2.3, 2.4, 3.3, 4.4, 5.4, 6.3, 6.4, 7.5, 7.6, 7.7, 8.1, 8.2, 8.3, 8.4_

  - [x] 6.2 Write unit tests for render.php SVG icon output and style application
    - Test that legacy icon values map correctly
    - Test that SVG markup is present for valid icons
    - Test that "none" produces no icon element
    - _Requirements: 7.5, 7.6, 8.1, 8.2, 8.3, 8.4_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The implementation language is JavaScript (React) for editor-side code and PHP for server-side rendering
- All SVG icons sourced from `@wordpress/icons` except plus-minus which uses custom inline SVG
- Legacy icon identifiers are mapped at render time — no database migration needed

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "2.1"] },
    { "id": 1, "tasks": ["1.3", "1.4", "1.5", "3.1"] },
    { "id": 2, "tasks": ["3.2", "5.1"] },
    { "id": 3, "tasks": ["5.2", "5.3", "6.1"] },
    { "id": 4, "tasks": ["6.2"] }
  ]
}
```
