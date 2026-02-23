# Quick Start

Register a settings page with a few fields:

```php
register_setting_fields( 'my_plugin', [
    'page_title'  => 'My Plugin Settings',
    'menu_title'  => 'My Plugin',
    'parent_slug' => 'options-general.php',

    'fields' => [
        'site_name' => [
            'type'  => 'text',
            'label' => 'Site Name',
        ],
        'enable_feature' => [
            'type'  => 'toggle',
            'label' => 'Enable Feature',
        ],
        'items_per_page' => [
            'type'  => 'number',
            'label' => 'Items Per Page',
            'min'   => 1,
            'max'   => 100,
            'step'  => 1,
        ],
    ],
] );
```

Retrieve values anywhere in your plugin:

```php
$site_name = get_setting_field_value( 'my_plugin', 'site_name' );

if ( is_setting_on( 'my_plugin', 'enable_feature' ) ) {
    // Feature is enabled
}
```

The library handles menu registration, Settings API plumbing, rendering, sanitization, and saving automatically.
