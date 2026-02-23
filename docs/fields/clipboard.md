# Clipboard

Read-only value with a copy-to-clipboard button. Useful for webhook URLs, shortcodes, API endpoints, or any string users need to copy.

```php
'webhook_url' => [
    'type'         => 'clipboard',
    'label'        => 'Webhook URL',
    'value'        => home_url( '/webhook/my-plugin/' ),
    'display'      => 'code',                // 'code' or 'input'
    'url'          => true,                  // Format value as a URL
    'button_label' => 'Copy',
    'copied_label' => 'Copied!',
],

'shortcode' => [
    'type'    => 'clipboard',
    'label'   => 'Shortcode',
    'value'   => '[my_plugin id="123"]',
    'display' => 'code',
],
```

## Display Modes

- **code** — renders the value inside a `<code>` element (default)
- **input** — renders as a readonly text input
