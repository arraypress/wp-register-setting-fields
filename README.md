# Register Setting Fields

A settings page from a list of fields — the menu, the tabs, the saving and
the sanitising included.

## What it does

A settings screen in WordPress is `add_menu_page`, `register_setting`,
`add_settings_section`, `add_settings_field` and a sanitise callback per
field, repeated until it works. Most of it is the same every time, and the
part that varies is just the list of fields.

This takes the list. The menu, the tabs, the form, the nonce, the saving and
the per-type sanitising follow from it.

## Features

* Get a settings page from a list of fields, with no `add_settings_field` calls
* Group fields into tabs and sections, saving one tab without touching others
* Read and write a value by name, without knowing how the option is shaped
* Sanitise per field type, rather than writing a callback for each
* Refuse a value that is missing or malformed, and say which field
* Encrypt a field, so an API key is not stored in the clear
* Export, import and reset from Screen Options
* Show a field only when another is set

## Installation

```bash
composer require arraypress/wp-register-setting-fields
```

## Quick start

```php
register_setting_fields( 'my_plugin', [
	'page_title'  => __( 'My Plugin Settings', 'my-plugin' ),
	'menu_title'  => __( 'My Plugin', 'my-plugin' ),
	'parent_slug' => 'options-general.php',
	'fields'      => [
		'site_name'      => [ 'type' => 'text', 'label' => __( 'Site name', 'my-plugin' ) ],
		'enable_feature' => [ 'type' => 'toggle', 'label' => __( 'Enable feature', 'my-plugin' ) ],
		'items_per_page' => [
			'type'  => 'number',
			'label' => __( 'Items per page', 'my-plugin' ),
			'min'   => 1,
			'max'   => 100,
		],
	],
] );
```

Then reading one, anywhere:

```php
$name = get_setting_field_value( 'my_plugin', 'site_name' );
```

Field types come from `arraypress/wp-field-kit`, so anything it renders can go
in a settings page.

## Validation

Sanitising coerces; it does not refuse. A field that must hold something says
`'required' => true`, and one that must hold a particular shape names a rule
with `validate` — `email`, `url`, `numeric`, `integer`, `slug`, `alphanumeric`,
or a callable returning `true`, a message or a `WP_Error`:

```php
'contact' => [ 'type' => 'text', 'label' => __( 'Contact', 'my-plugin' ), 'required' => true, 'validate' => 'email' ],
```

A value that fails is not stored. The field keeps what it held, every other
field on the tab is saved, and after the redirect the message appears as an
error notice at the top of the page — in place of "Settings saved." — and
again under the field itself.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
