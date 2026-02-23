# WordPress Setting Fields Library

A WordPress library for creating settings pages with tabs, sections, branded headers, and 30+ field types. Handles
registration, rendering, sanitization, and the Settings API — you just define your fields.

## Installation

```bash
composer require arraypress/wp-register-setting-fields
```

## Quick Start

```php
register_setting_fields( 'my_plugin', [
    'page_title'  => 'My Plugin Settings',
    'menu_title'  => 'My Plugin',
    'parent_slug' => 'options-general.php',
    'fields'      => [
        'site_name'      => [ 'type' => 'text', 'label' => 'Site Name' ],
        'enable_feature' => [ 'type' => 'toggle', 'label' => 'Enable Feature' ],
        'items_per_page' => [ 'type' => 'number', 'label' => 'Items Per Page', 'min' => 1, 'max' => 100 ],
    ],
] );

$name = get_setting_field_value( 'my_plugin', 'site_name' );

if ( is_setting_on( 'my_plugin', 'enable_feature' ) ) {
    // Feature is enabled
}
```

## Documentation

Full documentation is available at **[https://arraypress.github.io/wp-register-setting-fields](https://arraypress.github.io/wp-register-setting-fields)**

## Requirements

- PHP 7.4+
- WordPress 5.8+

## License

GPL-2.0-or-later