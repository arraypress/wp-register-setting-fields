# Help Tabs

Add contextual help to the WordPress Help screen on your settings page.

```php
register_setting_fields( 'my_plugin', [
    'help_tabs' => [
        'getting_started' => [
            'title'    => 'Getting Started',
            'content'  => '<p>Welcome to My Plugin. Here is how to get started...</p>',
            'priority' => 10,
        ],
        'api_setup' => [
            'title'    => 'API Setup',
            'content'  => '<p>To connect your API key, go to Settings → API.</p>',
        ],
        'dynamic' => [
            'title'    => 'Dynamic Content',
            'callback' => function ( $screen, $tab ) {
                echo '<p>Rendered at runtime.</p>';
            },
        ],
    ],

    'help_sidebar' => '<p><strong>Links</strong></p><a href="https://example.com/docs">Documentation</a>',
] );
```

## Help Tab Options

| Key        | Type     | Description                                |
|------------|----------|--------------------------------------------|
| `title`    | string   | Tab title in the Help panel                |
| `content`  | string   | HTML content                               |
| `callback` | callable | Render callback (alternative to content)   |
| `priority` | int      | Sort order (default: 10)                   |

The tab IDs are automatically prefixed with the settings ID to avoid collisions.
