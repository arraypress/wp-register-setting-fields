# WordPress Register Setting Fields

A WordPress library for creating tabbed settings pages. Registration, the menu, the Settings API wiring and export/import live here; every field is rendered, sanitized and made accessible by [wp-field-kit](https://github.com/arraypress/wp-field-kit), which the term, post, user and list-table libraries share.

## Features

- **50+ field types** — text, number, select, searchable and creatable selects, AJAX search, toggles, colour pickers, image/file/gallery, WYSIWYG, code editors, repeaters (stacked or as a table), groups, email panels, licence keys, action buttons, sortable lists
- **Core's own header** — the shape `options-privacy.php` uses, so it matches the admin and follows the user's colour scheme, with an optional logo and badge
- **Tabs & sections** — saving one tab leaves the others alone, which is the bug a tabbed settings page has if nobody thinks about it
- **Conditional fields** — show or hide based on other fields; a hidden field is deleted on save rather than keeping a stale value
- **Encryption** — AES-256-GCM for any field type, keyed from the site's own salts, with a constant fallback
- **Every write sanitized** — `update_option()` runs the registered callback before it compares, so a value written by a cron job, an importer or another plugin passes through its own field type exactly as a submitted one does
- **Accessible by construction** — labels, descriptions, required state and grouping are the renderer's job, so no field type can forget them
- **Helper functions** — `get_setting_field_value()`, `is_setting_on()`, `is_setting_field_license_active()`, and more

## Installation

```bash
composer require arraypress/wp-register-setting-fields
```

## Requirements

- PHP 8.3+
- WordPress 5.8+

## License

GPL-2.0-or-later
