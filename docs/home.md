# WordPress Register Setting Fields

A WordPress library for creating settings pages with tabs, sections, branded headers, and 30+ field types. Handles registration, rendering, sanitization, and the Settings API — you just define your fields.

## Features

- **30+ field types** — text, number, select, AJAX search, toggles, color pickers, image/file/gallery, WYSIWYG, code editors, and more
- **Relational fields** — post, page, taxonomy, and user selects with Select2 and AJAX search
- **Complex fields** — repeaters, groups, email editors, license keys, action buttons, sortable lists
- **Tabs & sections** — organize fields into tabbed pages with collapsible sections
- **Branded header** — logo, title, badge, and integrated tab navigation
- **Conditional fields** — show/hide fields based on other field values with 12 operators
- **Encryption** — transparent AES-256-CBC encryption for sensitive fields with constant fallback
- **Email editor** — full email template editor with merge tags, preview, and send test — integrates with `wp-register-emails`
- **License field** — license key activation/deactivation with REST callbacks and status persistence
- **Sanitization** — built-in sanitizers for every field type, extensible via callbacks and filters
- **Hooks** — filters and actions at every stage of the lifecycle
- **Helper functions** — `get_setting_field_value()`, `is_setting_on()`, `is_setting_field_license_active()`, and more

## Installation

```bash
composer require arraypress/wp-register-setting-fields
```

## Requirements

- PHP 8.2+
- WordPress 5.8+

## License

GPL-2.0-or-later
