# Message

Inline WordPress admin notice. Renders full-width (no label column). Supports HTML via `wp_kses_post()`.

```php
'warning' => [
    'type'         => 'message',
    'message_type' => 'warning',             // 'info', 'success', 'warning', 'error'
    'content'      => 'This feature requires PHP 8.0 or higher.',
    'inline'       => true,                  // Add 'inline' class to the notice
],
```

The `content` key can also be written as `message`.
