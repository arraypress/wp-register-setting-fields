# Tabs

Organize fields into tabbed pages. Tabs appear in the branded header as navigation links.

```php
register_setting_fields( 'my_plugin', [
    'tabs' => [
        'general'  => 'General',                         // Simple string format
        'advanced' => [ 'label' => 'Advanced' ],         // Array format
        'emails'   => [
            'label' => 'Emails',
            'icon'  => 'dashicons-email',                // Optional dashicon
        ],
    ],

    'fields' => [
        'site_name' => [
            'type'  => 'text',
            'label' => 'Site Name',
            'tab'   => 'general',
        ],
        'debug_mode' => [
            'type'  => 'toggle',
            'label' => 'Debug Mode',
            'tab'   => 'advanced',
        ],
    ],
] );
```

The first tab is active by default. The current tab is tracked via the `?tab=` query parameter. Fields without a `tab` key are assigned to the first tab automatically.

## Tab Options

| Key     | Type   | Description                             |
|---------|--------|-----------------------------------------|
| `label` | string | Tab display text                        |
| `icon`  | string | Dashicon class (e.g. `dashicons-email`) |

## Responsive

On small screens, the tab bar collapses into a dropdown toggle showing the current tab name with an expand/collapse button.
