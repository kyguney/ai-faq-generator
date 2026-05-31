# Implementation Plan: AI FAQ Generator Plugin Skeleton

## Overview

This plan implements the foundational skeleton for the AI FAQ Generator WordPress plugin. It follows the same conventions as the existing `sample-plugin`: main entry file with constants, SPL autoloader, admin settings page shell, and wp-scripts build configuration. Tasks are ordered so each step builds on the previous one, ending with wiring everything together.

## Tasks

- [x] 1. Set up project structure and configuration files
  - [x] 1.1 Create `composer.json` with PSR-4 autoloading
    - Create `plugins/ai-faq-generator/composer.json` with package name `wpbits/ai-faq-generator`
    - Map `WPBits\AiFaqGenerator\` namespace to the plugin root directory
    - Require PHP >= 7.4
    - Add `phpunit/phpunit` as a dev dependency
    - Include classmap autoloading for `includes/`
    - _Requirements: 1.6_

  - [x] 1.2 Create `package.json` with wp-scripts configuration
    - Create `plugins/ai-faq-generator/package.json` with name `@wpbits/ai-faq-generator`
    - Define `build` script running `wp-scripts build`
    - Define `dev` script running `wp-scripts start`
    - Define `test:unit` script running `wp-scripts test-unit --env=jsdom`
    - Add `@wordpress/scripts` as a devDependency (version ^30.0.0)
    - Set `main` to `build/index.js`
    - _Requirements: 1.7, 6.1, 6.2, 6.4_

  - [x] 1.3 Create placeholder directories and files
    - Create `plugins/ai-faq-generator/blocks/.gitkeep`
    - Create `plugins/ai-faq-generator/src/index.js` with a style import (`import './styles/main.scss'`) and an export statement
    - Create `plugins/ai-faq-generator/src/styles/main.scss` with minimal `.afg-wrap` scoped styles
    - _Requirements: 1.4, 1.5, 6.3_

- [x] 2. Implement core plugin bootstrap
  - [x] 2.1 Create the main plugin entry file `ai-faq-generator.php`
    - Add WordPress plugin header with all required fields (Plugin Name, Plugin URI, Description, Version, Author, Author URI, Text Domain `ai-faq-generator`, Domain Path, Requires at least `6.0`, Requires PHP `7.4`, License)
    - Declare `strict_types=1`
    - Use namespace `WPBits\AiFaqGenerator`
    - Add ABSPATH check that calls `exit` if not defined
    - Define constants: `AFG_PLUGIN_VERSION` (`1.0.0`), `AFG_PLUGIN_PATH`, `AFG_PLUGIN_URL`, `AFG_PLUGIN_BASENAME`
    - Create `init()` function that requires the loader and calls `$loader->init()`
    - Call `init()` at the end of the file
    - _Requirements: 1.1, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9_

- [x] 3. Implement class autoloader
  - [x] 3.1 Create `includes/class-loader.php`
    - Use namespace `WPBits\AiFaqGenerator\Includes`
    - Declare `strict_types=1`
    - Build internal class map in constructor mapping `WPBits\AiFaqGenerator\Admin\Admin` to `admin/class-admin.php`
    - Register SPL autoload function in `init()` method
    - Conditionally instantiate and initialize Admin class when `is_admin()` returns true
    - Autoload callback uses `require_once` for mapped classes
    - Silently ignore classes not in the map
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 3.2 Write property test for autoloader (Property 1: Autoloader ignores unregistered classes)
    - **Property 1: Autoloader ignores unregistered classes**
    - **Validates: Requirements 4.7**
    - Create a PHPUnit test with a data provider generating random fully-qualified class names
    - Assert that calling the autoload method with unregistered classes produces no side effects (no errors, no file loads)
    - Minimum 100 iterations

- [x] 4. Implement admin settings page
  - [x] 4.1 Create `admin/class-admin.php`
    - Use namespace `WPBits\AiFaqGenerator\Admin`
    - Declare `strict_types=1`
    - Implement `init()` method hooking into `admin_menu` and `admin_init`
    - Implement `add_admin_menu()` registering a top-level menu item with label "AI FAQ Generator", slug `ai-faq-generator`, capability `manage_options`, icon `dashicons-format-chat`, position 30
    - Implement `register_settings()` registering settings group `afg_settings` and section `afg_main_section`
    - Implement `render_admin_page()` with capability check, `.wrap` container, page title "AI FAQ Generator Settings", `settings_fields()`, `do_settings_sections()`, and `submit_button()`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7_

  - [x] 4.2 Write unit tests for Admin class
    - Test that `init()` registers `admin_menu` and `admin_init` hooks
    - Test that menu is registered with correct slug and capability
    - Test that `afg_settings` group is registered
    - Test that render method checks `manage_options` capability
    - _Requirements: 5.3, 5.4, 5.5, 5.6_

- [x] 5. Checkpoint - Verify plugin activation
  - Ensure all tests pass, ask the user if questions arise.
  - Verify the plugin can be activated without fatal errors or warnings
  - Verify hooks are registered correctly after activation
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 6. Build pipeline verification
  - [x] 6.1 Install dependencies and run build
    - Run `composer install` in the plugin directory
    - Run `npm install` in the plugin directory
    - Run `npm run build` and verify `build/index.js` and `build/index.asset.php` are produced
    - _Requirements: 6.1, 6.5_

  - [x] 6.2 Write integration test for build output
    - Assert `build/index.js` exists after build
    - Assert `build/index.asset.php` exists and returns a valid array with `dependencies` and `version` keys
    - _Requirements: 6.5_

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
  - Verify complete file structure matches the design document
  - Confirm plugin activates cleanly in WordPress environment

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property test validates the autoloader's safety with unregistered classes (Property 1)
- Unit tests validate specific hook registrations and settings configuration
- The plugin follows the same conventions as `sample-plugin` (SPL autoload, WordPress Settings API, wp-scripts)

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 1, "tasks": ["2.1"] },
    { "id": 2, "tasks": ["3.1"] },
    { "id": 3, "tasks": ["3.2", "4.1"] },
    { "id": 4, "tasks": ["4.2", "6.1"] },
    { "id": 5, "tasks": ["6.2"] }
  ]
}
```
