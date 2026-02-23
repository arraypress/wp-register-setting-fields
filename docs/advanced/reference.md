# Reference Summary

## All Field Types

### Text & Input

| Type       | Description              | Sanitizer                    |
|------------|--------------------------|------------------------------|
| `text`     | Standard text input      | `sanitize_text_field`        |
| `email`    | Email input              | `sanitize_email`             |
| `url`      | URL input                | `esc_url_raw`                |
| `tel`      | Phone input              | Strips non-phone chars       |
| `password` | Password input           | `sanitize_text_field`        |
| `number`   | Numeric input            | `intval` / `floatval`        |
| `range`    | Range slider             | `intval` / `floatval`        |
| `textarea` | Multi-line text          | `sanitize_textarea_field`    |
| `wysiwyg`  | Rich text editor         | `wp_kses_post`               |
| `code`     | Code editor (CodeMirror) | Raw (no sanitization)        |
| `hidden`   | Hidden input             | `sanitize_text_field`        |

### Choice & Selection

| Type              | Description                | Saves                 |
|-------------------|----------------------------|-----------------------|
| `select`          | Dropdown                   | String                |
| `select2`         | Enhanced select (single default) | String or Array   |
| `select_multiple` | Alias for select2          | Array                 |
| `checkbox`        | Single checkbox            | Boolean               |
| `toggle`          | Switch                     | Boolean               |
| `checkbox_group`  | Multiple checkboxes        | Array                 |
| `radio`           | Radio buttons              | String                |
| `button_group`    | Segmented control          | String                |

### Relational (AJAX + Select2)

| Type       | Description     | Saves          |
|------------|-----------------|----------------|
| `post`     | Post select     | Int or Array   |
| `page`     | Page select     | Int or Array   |
| `taxonomy` | Term select     | Int or Array   |
| `user`     | User select     | Int or Array   |
| `ajax`     | Custom callback | String or Array |

### Media

| Type      | Description           | Saves              |
|-----------|-----------------------|--------------------|
| `image`   | Image picker          | Int (attachment ID) |
| `file`    | File picker           | Int (attachment ID) |
| `gallery` | Multi-image picker    | Array of Ints      |
| `oembed`  | URL with embed preview | String (URL)      |

### Date, Time & Color

| Type       | Description    | Format          |
|------------|----------------|-----------------|
| `date`     | Date picker    | `Y-m-d`         |
| `time`     | Time picker    | `HH:MM(:SS)`    |
| `datetime` | Datetime picker | `Y-m-d\TH:i`  |
| `color`    | Color picker   | Hex string      |

### Complex

| Type            | Description                       | Saves          |
|-----------------|-----------------------------------|----------------|
| `group`         | Nested field group                | Array          |
| `repeater`      | Repeatable rows                   | Array of Arrays |
| `email_editor`  | Email template editor             | Array          |
| `license`       | License key with activation       | Array          |
| `action_button` | REST action button                | —              |
| `clipboard`     | Copy-to-clipboard display         | —              |
| `sortable`      | Drag-and-drop reorderable list    | Array          |
| `link`          | URL + text + target               | Array          |
| `dimensions`    | Four-sided input with unit        | Array          |

### Layout (No Data)

| Type        | Description                        |
|-------------|------------------------------------|
| `separator` | Visual divider with optional label |
| `heading`   | Section heading                    |
| `message`   | Admin notice                       |
| `html`      | Raw HTML output                    |
| `custom`    | Custom render callback             |

## Helper Functions

| Function                              | Description                              |
|---------------------------------------|------------------------------------------|
| `register_setting_fields()`           | Register a settings page                 |
| `get_setting_fields()`                | Get a registered instance                |
| `get_setting_field_value()`           | Get a field value (with decryption)      |
| `update_setting_field_value()`        | Update a field value                     |
| `delete_setting_field_value()`        | Delete a field value                     |
| `get_all_setting_values()`            | Get all values for a settings page       |
| `is_setting_on()`                     | Check if toggle/checkbox is enabled      |
| `is_setting_enabled()`               | Check if value exists in multi-select    |
| `get_setting_field_page_id()`         | Get page/post ID from a field            |
| `get_setting_field_page_url()`        | Get page URL from a field                |
| `is_setting_field_page()`             | Check if viewing the stored page         |
| `get_setting_field_license()`         | Get full license data                    |
| `get_setting_field_license_key()`     | Get license key string                   |
| `get_setting_field_license_status()`  | Get license status                       |
| `is_setting_field_license_active()`   | Check if license is active               |
| `update_setting_field_license_status()` | Update license status programmatically |
| `get_setting_fields_email()`          | Get email editor settings for wp-register-emails |
| `get_setting_fields_email_recipient()` | Get email recipient                     |
| `is_setting_fields_email_enabled()`   | Check if email is enabled                |

## Configuration Options

| Option           | Type   | Default | Description                                    |
|------------------|--------|---------|------------------------------------------------|
| `page_title`     | string | `'Settings'` | Page `<title>` tag                       |
| `menu_title`     | string | `'Settings'` | Sidebar menu label                       |
| `menu_slug`      | string | Settings ID  | URL slug                                 |
| `capability`     | string | `'manage_options'` | Required capability                |
| `parent_slug`    | string | `''`         | Parent menu slug (empty = top-level)     |
| `icon`           | string | `'dashicons-admin-generic'` | Top-level menu icon       |
| `position`       | int    | `null`       | Top-level menu position                  |
| `body_class`     | string | `''`         | Extra body class                         |
| `option_name`    | string | Settings ID  | `wp_options` key                         |
| `option_group`   | string | ID + `_group` | Settings API group                      |
| `submit_button`  | bool   | `true`       | Show submit button                       |
| `reset_button`   | bool   | `false`      | Show reset-to-defaults button            |
| `export_import`  | bool   | `false`      | Show export/import panel                 |
| `logo`           | string | `''`         | Header logo URL                          |
| `header_title`   | string | `''`         | Header title override                    |
| `header_badge`   | mixed  | `''`         | Header badge (string, array, or callable)|
| `tabs`           | array  | `[]`         | Tab definitions                          |
| `sections`       | array  | `[]`         | Section definitions                      |
| `fields`         | array  | `[]`         | Field definitions                        |
| `help_tabs`      | array  | `[]`         | Help screen tabs                         |
| `help_sidebar`   | string | `''`         | Help screen sidebar HTML                 |
| `encryption`     | array  | Auto         | Encryption settings                      |

## REST Endpoints

| Method | Route                              | Used By            |
|--------|------------------------------------|--------------------|
| GET    | `/setting-fields/v1/ajax`          | `ajax`, `post`, `page`, `taxonomy`, `user` |
| POST   | `/setting-fields/v1/action`        | `action_button`    |
| POST   | `/setting-fields/v1/license`       | `license`          |
| POST   | `/setting-fields/v1/email/preview` | `email_editor`     |
| POST   | `/setting-fields/v1/email/send-test` | `email_editor`   |
| POST   | `/setting-fields/v1/reset`         | `reset_button`     |
| GET    | `/setting-fields/v1/export`        | `export_import`    |
| POST   | `/setting-fields/v1/import`        | `export_import`    |
