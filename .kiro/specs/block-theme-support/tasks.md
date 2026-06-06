# Implementation Plan: Block Theme Support

## Overview

Integrate the FAQ Accordion block with WordPress's native block supports system by extending `block.json` supports, migrating hardcoded CSS to custom properties with fallbacks, and updating `render.php` to use `get_block_wrapper_attributes()`. This enables theme-controlled styling through `theme.json` while maintaining full backward compatibility.

## Tasks

- [x] 1. Extend block.json supports declaration
  - [x] 1.1 Add color, typography, spacing, and border supports to block.json
    - Add `color` support with `text: true`, `background: true`, `link: true`
    - Add `typography` support with `fontSize: true`, `lineHeight: true`
    - Add `spacing` support with `padding: true`, `margin: true`
    - Add `border` support with `color: true`, `style: true`, `width: true`, `radius: true`
    - Preserve existing supports: `html: false`, `align: ["wide", "full"]`, `multiple: false`
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 7.3_

  - [x] 1.2 Write unit test for block.json supports structure
    - Verify all color sub-properties are enabled
    - Verify all typography sub-properties are enabled
    - Verify all spacing sub-properties are enabled
    - Verify all border sub-properties are enabled
    - Verify existing supports are preserved unchanged
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 7.3_

- [x] 2. Migrate CSS to custom properties
  - [x] 2.1 Replace hardcoded color values with CSS custom properties in style.css
    - Replace `#ddd` border color with `var(--wp--custom--faq-accordion--border-color, #ddd)`
    - Replace `#f9f9f9` header background with `var(--wp--custom--faq-accordion--header-background, #f9f9f9)`
    - Replace `#0073aa` accent color with `var(--wp--custom--faq-accordion--accent-color, #0073aa)`
    - Replace `#f0f0f0` hover background with `var(--wp--custom--faq-accordion--hover-background, #f0f0f0)`
    - Replace `#eee` separator color with `var(--wp--custom--faq-accordion--separator-color, #eee)`
    - Ensure all instances including the animation section are updated
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 7.1, 7.2_

  - [x] 2.2 Replace hardcoded spacing and border-radius values with CSS custom properties in style.css
    - Replace `1em 1.25em` summary padding with `var(--wp--custom--faq-accordion--header-padding, 1em 1.25em)`
    - Replace `1em 1.25em 1.25em` content padding with `var(--wp--custom--faq-accordion--content-padding, 1em 1.25em 1.25em)`
    - Replace `4px` border-radius with `var(--wp--custom--faq-accordion--border-radius, 4px)`
    - Update animation section padding references to use the same custom properties
    - _Requirements: 5.6, 5.8, 7.1, 7.2_

  - [x] 2.3 Write unit test for CSS custom property migration
    - Verify no hardcoded color values (`#ddd`, `#f9f9f9`, `#0073aa`, `#f0f0f0`, `#eee`) remain in style.css
    - Verify each custom property has the correct fallback matching the original value
    - _Requirements: 5.8, 7.2_

- [x] 3. Update render.php to use get_block_wrapper_attributes()
  - [x] 3.1 Refactor render_faq_accordion_block() to use get_block_wrapper_attributes()
    - Replace the manual `<div class="' . esc_attr($classes) . '">` with `get_block_wrapper_attributes()`
    - Pass icon-position and animation classes as the `class` key in the extra attributes array
    - Ensure the function signature accepts `$attributes`, `$content`, and `$block` parameters as required by WordPress dynamic block render callbacks
    - _Requirements: 1.3, 1.4, 2.3, 3.3, 3.4, 4.3, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

  - [x] 3.2 Write PHPUnit test for render.php wrapper attributes
    - Verify rendered output includes `get_block_wrapper_attributes()` generated attributes
    - Verify custom classes (icon position, animation) are present in the wrapper
    - Verify supports-generated styles are included when block attributes contain style values
    - Verify the block renders correctly with empty/default attributes (backward compatibility)
    - _Requirements: 7.1, 7.4, 7.5_

- [x] 4. Checkpoint - Verify build and tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Integration verification
  - [x] 5.1 Verify editor integration works with useBlockProps
    - Confirm `edit.js` already uses `useBlockProps()` (no changes needed, just verification)
    - Verify that custom classes from `getBlockClasses()` are still passed to `useBlockProps`
    - Run `npm run build` to ensure the block compiles without errors
    - _Requirements: 1.2, 2.2, 3.2, 4.2, 6.7_

  - [x] 5.2 Write unit test for editor component rendering
    - Verify `useBlockProps` is called with custom className
    - Verify icon-position and animation classes are preserved alongside supports classes
    - _Requirements: 1.2, 2.2, 7.3_

- [x] 6. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The design explicitly states PBT is not applicable for this feature (declarative config + CSS migration)
- No JavaScript structural changes are needed — WordPress handles editor panels automatically via supports declaration
- `edit.js` already uses `useBlockProps()`, which will automatically apply supports-generated styles
- The block uses dynamic rendering (`save()` returns `null`), so no block validation migration is needed
- Fallback values in CSS custom properties guarantee backward compatibility with classic themes
- Test commands: `npm run test:unit` (JS), `composer test` (PHP), `npm run build` (build verification)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "2.1"] },
    { "id": 2, "tasks": ["2.2", "3.1"] },
    { "id": 3, "tasks": ["2.3", "3.2"] },
    { "id": 4, "tasks": ["5.1"] },
    { "id": 5, "tasks": ["5.2"] }
  ]
}
```
