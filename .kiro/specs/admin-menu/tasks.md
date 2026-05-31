# Implementation Plan: Admin Menu

## Overview

Update the existing Admin class to correct the menu title from "AI FAQ Generator" to "AI FAQ" and add a default "Settings" submenu item. Add the `add_submenu_page` stub to the test bootstrap and update unit tests to cover the new submenu registration and corrected menu title.

## Tasks

- [x] 1. Update Admin class and test bootstrap
  - [x] 1.1 Update `add_admin_menu()` in `admin/class-admin.php` to correct menu title and add submenu
    - Change the `menu_title` parameter in `add_menu_page()` from `'AI FAQ Generator'` to `'AI FAQ'`
    - Add `add_submenu_page()` call to register the default "Settings" submenu item with parent slug `ai-faq-generator`, page title `AI FAQ Generator Settings`, menu title `Settings`, capability `manage_options`, slug `ai-faq-generator`, and the same `render_admin_page` callback
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 4.1_

  - [x] 1.2 Add `add_submenu_page` stub to `tests/bootstrap.php`
    - Add a global `$afg_test_submenu_pages` array tracker (initialized to `[]`)
    - Add a stub function `add_submenu_page()` that records parent_slug, page_title, menu_title, capability, menu_slug, callback, and position into the global tracker
    - Return the menu_slug from the stub
    - _Requirements: 4.1_

- [x] 2. Update unit tests
  - [x] 2.1 Update existing menu title assertion in `AdminTest.php`
    - Change the assertion in `add_admin_menu_registers_menu_with_correct_slug_and_capability` from expecting `'AI FAQ Generator'` to `'AI FAQ'` for the `menu_title`
    - Add assertion for `icon_url` equals `'dashicons-format-chat'`
    - _Requirements: 1.1, 1.2_

  - [x] 2.2 Add submenu registration test to `AdminTest.php`
    - Reset `$afg_test_submenu_pages` in `setUp()`
    - Add test method `add_admin_menu_registers_settings_submenu` that calls `add_admin_menu()` and asserts:
      - `$afg_test_submenu_pages` has exactly 1 entry
      - `parent_slug` is `'ai-faq-generator'`
      - `page_title` is `'AI FAQ Generator Settings'`
      - `menu_title` is `'Settings'`
      - `capability` is `'manage_options'`
      - `menu_slug` is `'ai-faq-generator'`
    - _Requirements: 4.1_

- [x] 3. Checkpoint - Verify all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- No property-based tests are included because this feature consists of fixed-parameter WordPress API calls with no varying inputs or data transformations
- The Loader class requires no changes — it already instantiates Admin and calls `init()` in admin context
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["2.1", "2.2"] }
  ]
}
```
