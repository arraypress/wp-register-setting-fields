# Branded Header

Display a modern header with your logo, title, version badge, and integrated tab navigation. Rendered outside the `.wrap` container to match patterns used by EDD, WooCommerce, and other popular plugins.

```php
register_setting_fields( 'my_plugin', [
    'logo'         => plugin_dir_url( __FILE__ ) . 'assets/logo.svg',
    'header_title' => 'My Plugin',
    'header_badge' => 'v2.1.0',

    'tabs' => [
        'general'  => 'General',
        'advanced' => 'Advanced',
    ],
    // ...
] );
```

If no `header_title` is set, `page_title` is used as the fallback.

## Badge Formats

The `header_badge` option supports three formats:

```php
// String — rendered with default styling
'header_badge' => 'v2.1.0',

// Array — with custom CSS class
'header_badge' => [
    'text'  => 'Pro',
    'class' => 'my-badge-class',
],

// Callable — full control over output
'header_badge' => function () {
    return '<span class="custom-badge">Beta</span>';
},
```

## Without Tabs

If no tabs are defined but `logo` or `header_title` is set, the header renders the branding row without tab navigation.

## Without Header

If no logo, title, or tabs are provided, the header is skipped entirely and a standard `<hr class="wp-header-end">` is rendered instead.
