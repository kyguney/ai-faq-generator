# Requirements Document

## Introduction

This feature adds a toggle setting to the AI FAQ Generator WordPress plugin that allows administrators to enable or disable the FAQPage JSON-LD schema output. The JSON-LD generator currently hooks into `wp_head` unconditionally. This toggle gives site owners control over whether structured data is rendered in the document head, without needing to deactivate the entire plugin or modify code.

## Glossary

- **Settings_Page**: The React-based admin settings interface rendered at the AI FAQ Generator settings screen, consuming the REST API for reading and saving plugin options.
- **Settings_REST_Endpoint**: The WordPress REST API endpoint at `ai-faq-generator/v1/settings` that handles GET and POST requests for plugin settings.
- **Settings_Store**: The WordPress options table entry keyed as `afg_settings`, storing an associative array of plugin configuration values.
- **JSON_LD_Generator**: The service class that hooks into `wp_head` at priority 20 and outputs FAQPage structured data as a JSON-LD script tag on singular posts containing FAQ meta.
- **Loader**: The class responsible for initializing all plugin services including the JSON_LD_Generator.
- **Enable_Schema_Setting**: A boolean setting stored as the `enable_schema` key within the `afg_settings` options array, defaulting to `true`.

## Requirements

### Requirement 1: Store Enable Schema Setting

**User Story:** As a site administrator, I want the FAQ schema toggle preference to persist across sessions, so that my choice is remembered without needing to reconfigure it.

#### Acceptance Criteria

1. THE Settings_Store SHALL include an `enable_schema` key with a boolean value within the `afg_settings` options array.
2. WHEN the `enable_schema` key is absent from the Settings_Store, THE Settings_REST_Endpoint SHALL return `true` as the `enable_schema` value in GET responses.
3. WHEN a POST request includes `enable_schema` with a non-boolean value, THE Settings_REST_Endpoint SHALL cast the value to boolean using PHP type-casting rules (i.e., `0`, `"0"`, `""`, and `null` resolve to `false`; all other non-empty values resolve to `true`) before persisting it to the Settings_Store.
4. WHEN a POST request includes `enable_schema` set to `null`, THE Settings_REST_Endpoint SHALL persist `false` for the `enable_schema` key in the Settings_Store.

### Requirement 2: Expose Setting via REST API

**User Story:** As a site administrator, I want to read and update the schema toggle through the existing settings REST API, so that the settings page can manage it alongside other plugin options.

#### Acceptance Criteria

1. WHEN a GET request is made to the Settings_REST_Endpoint, THE Settings_REST_Endpoint SHALL include `enable_schema` as a boolean field in the response payload, defaulting to `true` when no value has been explicitly persisted.
2. WHEN a POST request includes `enable_schema` set to `false`, THE Settings_REST_Endpoint SHALL persist `false` for the `enable_schema` key in the plugin settings option and return the updated value as a boolean in the response body.
3. WHEN a POST request includes `enable_schema` set to `true`, THE Settings_REST_Endpoint SHALL persist `true` for the `enable_schema` key in the plugin settings option and return the updated value as a boolean in the response body.
4. WHEN a POST request omits the `enable_schema` field, THE Settings_REST_Endpoint SHALL retain the current stored value of `enable_schema` unchanged.
5. IF a POST request includes `enable_schema` with a non-boolean value (such as a string, integer, null, or array), THEN THE Settings_REST_Endpoint SHALL cast the value to a boolean using PHP's standard boolean casting rules before persisting it.

### Requirement 3: Display Toggle in Settings UI

**User Story:** As a site administrator, I want to see a clearly labeled checkbox on the settings page, so that I can easily enable or disable FAQ schema output.

#### Acceptance Criteria

1. THE Settings_Page SHALL render a checkbox control labeled "Enable FAQ Schema" within the settings form.
2. WHEN the Settings_Page loads and receives the settings response from the Settings_REST_Endpoint, THE Settings_Page SHALL display the checkbox as checked if the `enable_schema` value is `true`, and unchecked if the value is `false`.
3. WHEN the administrator checks or unchecks the checkbox and submits the form, THE Settings_Page SHALL include the `enable_schema` field with a boolean value (`true` for checked, `false` for unchecked) in the POST request body sent to the Settings_REST_Endpoint.
4. IF the Settings_REST_Endpoint returns an error when loading or saving settings, THEN THE Settings_Page SHALL display an error notice describing the failure and SHALL preserve the checkbox in its last-known state.

### Requirement 4: Conditionally Output JSON-LD Schema

**User Story:** As a site administrator, I want JSON-LD schema to only appear on my site when the toggle is enabled, so that I have full control over structured data output.

#### Acceptance Criteria

1. WHILE the `enable_schema` value in the `afg_settings` WordPress option is `true` (boolean), THE JSON_LD_Generator SHALL hook into `wp_head` and output FAQPage structured data on singular posts that contain valid FAQ_Meta.
2. WHILE the `enable_schema` value in the `afg_settings` WordPress option is `false` (boolean), THE JSON_LD_Generator SHALL not hook into `wp_head` and SHALL produce no JSON-LD output on any page.
3. IF the `enable_schema` key is absent from the `afg_settings` WordPress option, THEN THE JSON_LD_Generator SHALL treat the value as `true` and output FAQPage structured data on singular posts that contain valid FAQ_Meta.
4. WHEN the JSON_LD_Generator evaluates the `enable_schema` setting, THE JSON_LD_Generator SHALL read the value from the `afg_settings` option at hook registration time during plugin initialization, not deferred to render time.
