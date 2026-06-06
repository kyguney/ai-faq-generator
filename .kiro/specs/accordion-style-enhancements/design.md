# Design Document: Accordion Style Enhancements

## Overview

This feature enhances the FAQ Accordion block's styling capabilities by adding background color controls for title and content areas, font style controls (bold, italic, underline, text-transform) for the title heading tag, and expanding the accordion icon selection to use SVG-based icons for better scalability and visual variety.

## Architecture

This feature extends the existing FAQ Accordion block (`wpbits/faq-accordion`) with three groups of enhancements:

1. **Background color controls** — ColorPalette-based pickers for title and content areas
2. **Title font style controls** — Font weight, italic, underline, and text-transform options
3. **SVG icon system** — Replaces Unicode character icons with scalable SVG markup and adds backward compatibility mapping

All changes follow the existing block architecture: attributes defined in `block.json`, editor UI in `InspectorPanel.js`, preview rendering in `edit.js`, and server-side rendering in `render.php`.

## Components and Interfaces

### 1. Block Attributes (block.json)

New attributes added to the existing `attributes` object:

```json
{
  "titleBackgroundColor": { "type": "string", "default": "" },
  "contentBackgroundColor": { "type": "string", "default": "" },
  "titleFontWeight": { "type": "string", "default": "" },
  "titleFontStyle": { "type": "string", "default": "" },
  "titleTextDecoration": { "type": "string", "default": "" },
  "titleTextTransform": { "type": "string", "default": "" },
  "selectedIcon": { "type": "string", "default": "chevron-down" }
}
```

The `selectedIcon` default changes from `"chevron"` to `"chevron-down"` to align with the new SVG naming scheme.

### 2. SVG Icon Registry (new module: `src/utils/iconRegistry.js`)

A centralized registry mapping icon identifiers to SVG components and their metadata.

```javascript
import { chevronDown, chevronRight, plusCircle, arrowDown, arrowRight } from '@wordpress/icons';

/**
 * Legacy-to-new icon identifier mapping.
 * Handles backward compatibility for saved blocks using old Unicode icon names.
 */
export const LEGACY_ICON_MAP = {
  chevron: 'chevron-down',
  plus: 'plus-minus',
  arrow: 'arrow-down',
};

/**
 * Available SVG icon definitions.
 * Each entry contains an SVG component and a human-readable label.
 */
export const ICON_REGISTRY = {
  'chevron-down': { icon: chevronDown, label: 'Chevron Down' },
  'chevron-right': { icon: chevronRight, label: 'Chevron Right' },
  'plus-minus': { icon: null, label: 'Plus / Minus', svg: '<custom SVG>' },
  'arrow-down': { icon: arrowDown, label: 'Arrow Down' },
  'arrow-right': { icon: arrowRight, label: 'Arrow Right' },
  'none': { icon: null, label: 'None' },
};

/**
 * Default icon size in pixels when no custom title font size is set.
 */
export const DEFAULT_ICON_SIZE = 20;

/**
 * Resolves a selectedIcon attribute value to a valid icon identifier.
 * Handles legacy mappings and unrecognized values.
 *
 * @param {string} iconId - The raw selectedIcon attribute value.
 * @returns {string} A valid icon identifier from ICON_REGISTRY.
 */
export function resolveIconId(iconId) {
  if (LEGACY_ICON_MAP[iconId]) {
    return LEGACY_ICON_MAP[iconId];
  }
  if (ICON_REGISTRY[iconId]) {
    return iconId;
  }
  return 'chevron-down'; // fallback for unrecognized values
}

/**
 * Computes the icon pixel size based on the title font size.
 * Scales proportionally; defaults to DEFAULT_ICON_SIZE when no font size is set.
 *
 * @param {number} titleFontSize - The title font size in pixels (0 means default/unset).
 * @returns {number} The icon size in pixels.
 */
export function getIconSize(titleFontSize) {
  if (!titleFontSize || titleFontSize <= 0) {
    return DEFAULT_ICON_SIZE;
  }
  return Math.round(titleFontSize * 1.1);
}
```

### 3. Title Style Builder (new utility: `src/utils/buildTitleStyles.js`)

Pure function that constructs the inline style object for the title heading tag based on font styling attributes.

```javascript
/**
 * Builds an inline style object for the title heading tag.
 *
 * @param {Object} attributes - Block attributes containing title font styling values.
 * @returns {Object} A CSS style object with only non-empty/non-default properties.
 */
export function buildTitleHeadingStyle(attributes) {
  const {
    titleFontWeight = '',
    titleFontStyle = '',
    titleTextDecoration = '',
    titleTextTransform = '',
  } = attributes;

  const style = {};

  if (titleFontWeight && titleFontWeight !== '') {
    style.fontWeight = titleFontWeight;
  }
  if (titleFontStyle === 'italic') {
    style.fontStyle = 'italic';
  }
  if (titleTextDecoration === 'underline') {
    style.textDecoration = 'underline';
  }
  if (titleTextTransform && titleTextTransform !== '' && titleTextTransform !== 'none') {
    style.textTransform = titleTextTransform;
  }

  return style;
}
```

### 4. Background Style Builders (integrated into existing style logic)

The `getTitleStyle()` and `getContentStyle()` functions in `edit.js` are extended:

```javascript
const getTitleStyle = () => {
  const style = {};
  if (titleColor) style.color = titleColor;
  if (titleBackgroundColor) style.backgroundColor = titleBackgroundColor;
  if (titleFontSize && titleFontSize > 0) style.fontSize = `${titleFontSize}px`;
  if (titleFontFamily) style.fontFamily = titleFontFamily;
  if (titlePadding !== undefined) style.padding = `${titlePadding}px`;
  return style;
};

const getContentStyle = () => {
  const style = {};
  if (contentColor) style.color = contentColor;
  if (contentBackgroundColor) style.backgroundColor = contentBackgroundColor;
  if (contentFontSize && contentFontSize > 0) style.fontSize = `${contentFontSize}px`;
  if (contentFontFamily) style.fontFamily = contentFontFamily;
  if (contentPadding !== undefined) style.padding = `${contentPadding}px`;
  return style;
};
```

### 5. InspectorPanel Enhancements

The `InspectorPanel.js` component receives these additions:

**Title Styling section** (existing panel, new controls added):
- `ColorPalette` from `@wordpress/components` for title background color
- `SelectControl` for font weight (options: Default, Normal/400, Medium/500, Semi-Bold/600, Bold/700, Extra-Bold/800)
- `ToggleControl` for italic
- `ToggleControl` for underline
- `SelectControl` for text-transform (options: None, Uppercase, Lowercase, Capitalize)

**Content Styling section** (existing panel, new control added):
- `ColorPalette` from `@wordpress/components` for content background color

**Icon Selection section** (existing panel, updated):
- Replace the `SelectControl` dropdown with a visual icon picker using `ButtonGroup` + `Button` components
- Each button shows the SVG icon preview with its label below

```javascript
import { ColorPalette, SelectControl, ToggleControl, ButtonGroup, Button } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import { ICON_REGISTRY, resolveIconId } from '../utils/iconRegistry';
```

### 6. Render Template Updates (render.php)

**New attributes extracted:**
```php
$title_bg_color       = $attributes['titleBackgroundColor'] ?? '';
$content_bg_color     = $attributes['contentBackgroundColor'] ?? '';
$title_font_weight    = $attributes['titleFontWeight'] ?? '';
$title_font_style_val = $attributes['titleFontStyle'] ?? '';
$title_text_decoration = $attributes['titleTextDecoration'] ?? '';
$title_text_transform = $attributes['titleTextTransform'] ?? '';
```

**Background color integration:**
- `$title_bg_color` applied to the `<summary>` element's inline style
- `$content_bg_color` applied to the `.faq-accordion-content` div's inline style

**Title heading font styles:**
All four title font properties (`font-weight`, `font-style`, `text-decoration`, `text-transform`) are applied to the heading tag's inline style, only when non-empty and non-default.

**SVG icon rendering:**
- A PHP function `get_svg_icon_markup($icon_id, $size)` returns the SVG string for a given icon identifier
- Legacy icon mapping handled by a `$legacy_icon_map` associative array mirroring the JS `LEGACY_ICON_MAP`
- Unrecognized values fall back to `'chevron-down'`
- Icon size computed from `$title_font_size` using the same proportional logic as the JS utility

```php
/**
 * Map of legacy icon identifiers to their new SVG counterparts.
 */
$legacy_icon_map = [
    'chevron'       => 'chevron-down',
    'plus'          => 'plus-minus',
    'arrow'         => 'arrow-down',
    'chevron-right' => 'chevron-right', // unchanged but explicit
];

/**
 * Resolve an icon identifier, handling legacy values and fallback.
 */
function resolve_icon_id(string $icon_id, array $legacy_map): string {
    if (isset($legacy_map[$icon_id])) {
        return $legacy_map[$icon_id];
    }
    $valid_icons = ['chevron-down', 'chevron-right', 'plus-minus', 'arrow-down', 'arrow-right', 'none'];
    return in_array($icon_id, $valid_icons, true) ? $icon_id : 'chevron-down';
}
```

## Data Models

### New Block Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `titleBackgroundColor` | string | `""` | CSS color string for title area background |
| `contentBackgroundColor` | string | `""` | CSS color string for content area background |
| `titleFontWeight` | string | `""` | CSS font-weight value (400, 500, 600, 700, 800) |
| `titleFontStyle` | string | `""` | CSS font-style value ("italic" or "") |
| `titleTextDecoration` | string | `""` | CSS text-decoration value ("underline" or "") |
| `titleTextTransform` | string | `""` | CSS text-transform value (uppercase, lowercase, capitalize, none, "") |

### Modified Attributes

| Attribute | Old Default | New Default | Reason |
|-----------|-------------|-------------|--------|
| `selectedIcon` | `"chevron"` | `"chevron-down"` | Aligns with new SVG naming convention |

### Icon Identifier Values

| New Identifier | Legacy Equivalent | Source |
|----------------|-------------------|--------|
| `chevron-down` | `chevron` | `@wordpress/icons` chevronDown |
| `chevron-right` | `chevron-right` | `@wordpress/icons` chevronRight |
| `plus-minus` | `plus` | Custom inline SVG |
| `arrow-down` | `arrow` | `@wordpress/icons` arrowDown |
| `arrow-right` | (new) | `@wordpress/icons` arrowRight |
| `none` | `none` | No render |

## Interfaces

### resolveIconId(iconId: string): string

Resolves a raw `selectedIcon` attribute value through legacy mapping and validation. Returns a guaranteed-valid icon identifier.

**Input:** Any string (potentially a legacy or unrecognized value)
**Output:** A valid icon identifier from `ICON_REGISTRY`, defaulting to `'chevron-down'`

### getIconSize(titleFontSize: number): number

Computes the pixel size for SVG icons based on the title font size.

**Input:** Title font size in pixels (0 = unset/default)
**Output:** Icon dimensions in pixels (minimum: `DEFAULT_ICON_SIZE` = 20)

### buildTitleHeadingStyle(attributes: Object): Object

Constructs a React inline style object for the title heading tag from font styling attributes.

**Input:** Block attributes object
**Output:** CSS style object with only active (non-empty, non-default) properties

### get_svg_icon_markup(string $icon_id, int $size): string (PHP)

Returns the SVG markup string for a resolved icon identifier at the given pixel size.

**Input:** A valid icon identifier and pixel dimensions
**Output:** SVG element string with `width` and `height` attributes set, or empty string for `'none'`

## Error Handling

| Scenario | Handling |
|----------|----------|
| Unrecognized `selectedIcon` value | Falls back to `'chevron-down'` via `resolveIconId()` |
| Legacy icon identifier stored in DB | Mapped at render time (both JS and PHP), no migration needed |
| Empty/undefined font styling attributes | Style property omitted from inline styles (no error state) |
| Invalid color string in `titleBackgroundColor` / `contentBackgroundColor` | Passed through as-is; browsers ignore invalid CSS color values gracefully |
| `titleFontSize` is 0 or negative | Icon renders at default 20×20 size |

## Testing Strategy

### Unit Tests (Example-Based)
- **Inspector panel rendering**: Verify each new control (ColorPalette, SelectControl, ToggleControl) renders with correct labels and options
- **Default attribute values**: Confirm block.json declares all new attributes with correct defaults
- **Toggle behaviors**: Verify italic and underline toggles store "italic"/"underline" on enable and empty string on disable
- **Icon registry completeness**: Verify at least six icon options exist in the registry
- **SVG output format**: Verify rendered icons contain valid SVG markup (not Unicode chars)

### Property-Based Tests (fast-check)
- **Background color application** (Property 1): Generate random CSS color strings, verify style presence/absence
- **Title font styling** (Property 2): Generate random combinations of font styling attributes, verify the style builder produces correct output
- **SVG icon rendering** (Property 3): For each valid icon ID, verify SVG output presence
- **Icon proportional sizing** (Property 4): Generate random font sizes, verify icon dimensions match formula
- **Legacy icon mapping** (Property 5): Verify all legacy identifiers map correctly
- **Unrecognized icon fallback** (Property 6): Generate arbitrary strings, verify fallback to chevron-down

### Edge Case Coverage
- Empty/cleared color attributes produce no inline background-color style
- `selectedIcon` set to "none" produces zero icon elements
- `titleTextTransform` set to "none" omits text-transform from styles

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Background color attribute application

*For any* non-empty CSS color string stored in `titleBackgroundColor` or `contentBackgroundColor`, the rendered HTML output (both editor preview and PHP template) SHALL include that exact color value as the `background-color` inline style on the corresponding area element (Title_Area or Content_Area respectively), and when the attribute is empty, no `background-color` property SHALL appear in the inline style.

**Validates: Requirements 1.3, 1.4, 2.3, 2.4**

### Property 2: Title font styling application

*For any* combination of title font styling attributes (`titleFontWeight`, `titleFontStyle`, `titleTextDecoration`, `titleTextTransform`), the `buildTitleHeadingStyle` function SHALL produce a style object containing exactly those CSS properties whose attribute values are non-empty and non-default (i.e., not empty string and not "none" for text-transform), and SHALL omit all properties whose attribute values are empty or default.

**Validates: Requirements 3.3, 4.4, 5.4, 6.3, 6.4**

### Property 3: SVG icon rendering for valid identifiers

*For any* valid icon identifier in the registry (other than "none"), the rendered accordion output SHALL contain SVG markup corresponding to that icon, and when the identifier is "none", no icon element SHALL be present in the output.

**Validates: Requirements 7.5, 7.6**

### Property 4: SVG icon proportional sizing

*For any* title font size value, the rendered SVG icon dimensions SHALL equal `Math.round(titleFontSize * 1.1)` when the font size is greater than 0, and SHALL equal 20 pixels when the font size is 0 or unset.

**Validates: Requirements 7.7**

### Property 5: Legacy icon migration mapping

*For any* legacy icon identifier (`"chevron"` → `"chevron-down"`, `"plus"` → `"plus-minus"`, `"arrow"` → `"arrow-down"`), the `resolveIconId` function SHALL return the corresponding new identifier, and the rendered output SHALL display the correct new SVG icon.

**Validates: Requirements 8.1, 8.2, 8.3**

### Property 6: Unrecognized icon fallback

*For any* string that is not a recognized icon identifier (neither a current valid identifier nor a legacy identifier), the `resolveIconId` function SHALL return `"chevron-down"` as the fallback, ensuring the block always renders a valid icon or no icon.

**Validates: Requirements 8.4**
