# Requirements Document

## Introduction

This feature adds a settings panel to the FAQ Accordion block's inspector sidebar in the WordPress block editor. The panel provides controls for customizing the block's appearance and behavior: selecting the HTML heading tag for questions, toggling whether the first item starts open, choosing the position of expand/collapse icons, and enabling or disabling expand/collapse animations. Settings are stored as block attributes, reflected in real-time in the editor preview, and applied via CSS classes on the server-rendered frontend.

## Glossary

- **Inspector_Panel**: The WordPress block editor sidebar panel rendered via the `InspectorControls` component from `@wordpress/block-editor`, displaying block-specific settings.
- **FAQ_Block**: The `wpbits/faq-accordion` block that displays FAQ items in an accordion format using native `<details>`/`<summary>` elements.
- **Block_Attributes**: The data structure defined in `block.json` that stores block settings and persists them in the post content.
- **Title_Tag**: The HTML heading element (`h2`, `h3`, or `h4`) used to wrap each FAQ question in the rendered output.
- **Icon_Position**: The placement of the expand/collapse chevron indicator relative to the question text, with possible values of `left`, `right`, or `none`.
- **Editor_Preview**: The visual representation of the FAQ block in the WordPress block editor that reflects current attribute values in real-time.

## Requirements

### Requirement 1: Inspector Panel Registration

**User Story:** As a content editor, I want to see a settings panel in the block sidebar when the FAQ Accordion block is selected, so that I can configure block appearance without editing code.

#### Acceptance Criteria

1. WHEN the FAQ_Block is selected in the editor, THE Inspector_Panel SHALL display a `PanelBody` component with the title "Settings" in the block sidebar.
2. THE Inspector_Panel SHALL render using the `InspectorControls` component from `@wordpress/block-editor`.
3. THE Inspector_Panel SHALL contain a Title_Tag selection control with the label "Title Tag" and options for `H2`, `H3`, and `H4`.
4. THE Inspector_Panel SHALL contain an "Open first item" toggle control.
5. THE Inspector_Panel SHALL contain an Icon_Position selection control with the label "Icon Position" and options `Left`, `Right`, and `None`.
6. THE Inspector_Panel SHALL contain an "Enable animation" toggle control.

### Requirement 2: Title Tag Selector

**User Story:** As a content editor, I want to choose the heading level for FAQ questions, so that I can maintain proper document outline hierarchy.

#### Acceptance Criteria

1. THE Inspector_Panel SHALL display a `SelectControl` labelled "Title Tag" with exactly three options: `H2`, `H3`, and `H4`.
2. WHEN a Title_Tag option is selected, THE FAQ_Block SHALL store the selected value as a lowercase string (`h2`, `h3`, or `h4`) in the `titleTag` block attribute.
3. WHEN a new FAQ_Block is inserted, THE FAQ_Block SHALL set the `titleTag` attribute to `h3` as the default value.
4. WHEN the `titleTag` attribute changes, THE Editor_Preview SHALL update all question elements to use the selected heading level without requiring a page reload or manual save.
5. WHEN the block is rendered on the frontend, THE FAQ_Block SHALL render each question as the heading element specified by the `titleTag` attribute placed inside the `<summary>` element (e.g., `<summary><h3>Question text</h3></summary>`).
6. IF the `titleTag` attribute is missing or contains a value other than `h2`, `h3`, or `h4`, THEN THE FAQ_Block SHALL fall back to rendering questions with an `h3` element.

### Requirement 3: Open First Item Toggle

**User Story:** As a content editor, I want to control whether the first FAQ item starts expanded, so that I can guide users to see the first answer immediately.

#### Acceptance Criteria

1. THE Inspector_Panel SHALL display a `ToggleControl` labeled "Open first item" within the block's sidebar settings for controlling initial expansion state.
2. WHEN the toggle is activated, THE FAQ_Block SHALL store `true` in the `openFirstItem` block attribute.
3. WHEN the toggle is deactivated, THE FAQ_Block SHALL store `false` in the `openFirstItem` block attribute.
4. THE FAQ_Block SHALL use `false` as the default value for the `openFirstItem` attribute when a new block is inserted.
5. WHILE `openFirstItem` is `true` and the block contains at least one FAQ item, THE Editor_Preview SHALL render the first FAQ item with its answer content visible.
6. WHILE `openFirstItem` is `false` or the block contains zero FAQ items, THE Editor_Preview SHALL render all FAQ items with their answer content hidden.
7. WHEN `openFirstItem` is `true` and the block contains at least one FAQ item and the block is rendered on the frontend, THE FAQ_Block SHALL add the `open` attribute to the first `<details>` element only.
8. IF `openFirstItem` is `false` or the block contains zero FAQ items, THEN THE FAQ_Block SHALL NOT add the `open` attribute to any `<details>` element on the frontend.

### Requirement 4: Icon Position Selector

**User Story:** As a content editor, I want to choose where the expand/collapse icon appears or hide it entirely, so that I can match the accordion style to the site's design.

#### Acceptance Criteria

1. THE Inspector_Panel SHALL display a `SelectControl` labeled "Icon Position" with options `Left`, `Right`, and `None` for Icon_Position selection.
2. WHEN an Icon_Position option is selected, THE FAQ_Block SHALL store the selected value (`left`, `right`, or `none`) in the `iconPosition` block attribute.
3. THE FAQ_Block SHALL use `left` as the default value for the `iconPosition` attribute when a new block is inserted.
4. WHEN the `iconPosition` attribute changes, THE Editor_Preview SHALL apply the corresponding CSS class (`has-icon-left`, `has-icon-right`, or `has-no-icon`) to the block wrapper and update the icon placement visually without requiring a page reload.
5. WHEN `iconPosition` is `none`, THE FAQ_Block SHALL apply a CSS class `has-no-icon` to the outer `.wp-block-wpbits-faq-accordion` wrapper div on the frontend and hide the chevron `::before` pseudo-element on `summary`.
6. WHEN `iconPosition` is `right`, THE FAQ_Block SHALL apply a CSS class `has-icon-right` to the outer `.wp-block-wpbits-faq-accordion` wrapper div on the frontend and render the chevron indicator after the question text using `summary::after` instead of `summary::before`.
7. WHEN `iconPosition` is `left`, THE FAQ_Block SHALL apply a CSS class `has-icon-left` to the outer `.wp-block-wpbits-faq-accordion` wrapper div on the frontend and render the chevron indicator before the question text using `summary::before`.
8. IF the `iconPosition` attribute contains a value other than `left`, `right`, or `none`, THEN THE FAQ_Block SHALL fall back to `left` behavior and apply the `has-icon-left` CSS class.

### Requirement 5: Animation Toggle

**User Story:** As a content editor, I want to enable or disable expand/collapse animations, so that I can provide a smoother interaction or respect reduced-motion preferences.

#### Acceptance Criteria

1. THE Inspector_Panel SHALL display a `ToggleControl` labeled "Enable animation" for controlling expand/collapse transitions.
2. WHEN the toggle is activated, THE FAQ_Block SHALL store `true` in the `enableAnimation` block attribute. WHEN the toggle is deactivated, THE FAQ_Block SHALL store `false` in the `enableAnimation` block attribute.
3. THE FAQ_Block SHALL use `false` as the default value for the `enableAnimation` attribute when a new block is inserted.
4. WHEN `enableAnimation` is `true` and the block is rendered on the frontend, THE FAQ_Block SHALL apply a CSS class `has-animation` to the block wrapper. WHEN `enableAnimation` is `false` and the block is rendered on the frontend, THE FAQ_Block SHALL NOT apply the `has-animation` CSS class to the block wrapper.
5. WHILE `enableAnimation` is `true` and the user has not set `prefers-reduced-motion: reduce`, THE FAQ_Block SHALL apply a CSS transition with a duration of 300ms to the content panel expand/collapse behavior.
6. WHILE `enableAnimation` is `true` and the user has set `prefers-reduced-motion: reduce`, THE FAQ_Block SHALL disable CSS transitions and expand/collapse content with 0ms duration.
7. WHEN the `enableAnimation` attribute changes, THE Editor_Preview SHALL update the block wrapper to add or remove the `has-animation` class to reflect the current value in real-time.

### Requirement 6: Attribute Persistence

**User Story:** As a content editor, I want my block settings to persist when I save the post, so that I do not lose my configuration.

#### Acceptance Criteria

1. WHEN the post is saved, THE FAQ_Block SHALL persist all inspector control attribute values (`titleTag`, `openFirstItem`, `iconPosition`, `enableAnimation`) in the serialized block comment delimiter alongside the existing `items` attribute.
2. WHEN the post is reopened in the editor, THE FAQ_Block SHALL restore all persisted attribute values and pre-populate the corresponding Inspector_Panel controls with the saved values.
3. THE FAQ_Block SHALL define the following attributes with their types and default values in `block.json`: `titleTag` (type: string, default: `"h3"`), `openFirstItem` (type: boolean, default: `false`), `iconPosition` (type: string, default: `"left"`), `enableAnimation` (type: boolean, default: `false`).
4. WHEN an existing block is loaded that does not contain the new attributes in its serialized content, THE FAQ_Block SHALL apply the default values defined in `block.json` so that the block renders and functions without editor errors.
5. IF a persisted attribute value does not match its defined type (e.g., a non-string value for `titleTag` or a non-boolean value for `openFirstItem`), THEN THE FAQ_Block SHALL fall back to the default value defined in `block.json` for that attribute.

### Requirement 7: Frontend Rendering with Settings

**User Story:** As a site visitor, I want the FAQ accordion to render according to the editor's configured settings, so that I see the intended design and behavior.

#### Acceptance Criteria

1. WHEN the block is rendered on the frontend, THE FAQ_Block SHALL wrap each question text inside the `<summary>` element with the HTML heading tag specified by the `titleTag` attribute (one of `h2`, `h3`, or `h4`).
2. WHEN the block is rendered on the frontend with `openFirstItem` set to `true`, THE FAQ_Block SHALL add the `open` attribute to the first `<details>` element in the accordion.
3. WHEN the block is rendered on the frontend, THE FAQ_Block SHALL add exactly one icon-position CSS class to the block wrapper element: `has-icon-left` when `iconPosition` is `left`, `has-icon-right` when `iconPosition` is `right`, or `has-no-icon` when `iconPosition` is `none`.
4. WHEN the block is rendered on the frontend with `enableAnimation` set to `true`, THE FAQ_Block SHALL add the CSS class `has-animation` to the block wrapper element.
5. IF the `titleTag` attribute is missing or not one of `h2`, `h3`, `h4`, THEN THE FAQ_Block SHALL fall back to `h3`.
6. IF the `iconPosition` attribute is missing or not one of `left`, `right`, `none`, THEN THE FAQ_Block SHALL fall back to `left`.
7. IF the `openFirstItem` or `enableAnimation` attribute is missing or not a boolean, THEN THE FAQ_Block SHALL treat it as `false`.
