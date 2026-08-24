# Header

The page header is WordPress's own. `options-privacy.php` and `site-health.php` render the same shape, and core styles it from a rule in `wp-admin/css/edit.css` — a dependency of the `wp-admin` bundle, so it is present on every admin page.

Reusing core's classes means the header matches core exactly, follows the user's colour scheme, and costs almost nothing in CSS.

```php
register_setting_fields( 'my_plugin', [
    'header_title' => 'My Plugin',
    'logo'         => plugin_dir_url( __FILE__ ) . 'assets/logo.svg',
    'badge'        => 'v2.1.0',

    'tabs' => [
        'general'  => 'General',
        'advanced' => 'Advanced',
    ],
    // ...
] );
```

If no `header_title` is set, `page_title` is used.

Core's title section is a centred flex row, so the logo and the badge sit beside the heading without any positioning of their own. The logo renders with empty `alt` text on purpose: the heading beside it already says the same thing, and a screen reader announcing the plugin's name twice is worse than not describing a decorative image.

## Badge Formats

```php
// String
'badge' => 'v2.1.0',

// Array, to add a class of your own
'badge' => [
    'text'  => 'Pro',
    'class' => 'my-badge-class',
],

// Callable, for full control
'badge' => function () {
    return '<span class="custom-badge">Beta</span>';
},
```

A callable's return value goes through `wp_kses_post()`. A callable returning raw markup is a callable returning whatever a filter put into it.

## Tabs

Tabs render as core's `.privacy-settings-tab` links. The active one carries `aria-current="true"`, which is what conveys the selection — the class is only how it is drawn.

One thing does not carry over from core: `.privacy-settings-tabs-wrapper` is `grid-template-columns: 1fr 1fr`, hardcoded to the two tabs that screen has, and Site Health declares its own at four. Neither generalises, so the wrapper is the kit's own. It is the one place this costs any CSS.

## Notices

The header ends in `<hr class="wp-header-end">`. That is not decoration: `common.js` looks for it and moves admin notices to sit directly after it. Without one, notices are appended after the first heading on the page, which on a tabbed screen means somewhere arbitrary.

## Where It Lives

The header is `wp-field-kit`'s `Support\PageHeader`, so the reports library and anything else built on the kit renders the same one.
