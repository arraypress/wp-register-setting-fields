# Sanitization

Every field type has a built-in sanitizer that runs automatically on save. You can override per-field or globally via filters.

## Per-Field Override

```php
'slug' => [
    'type'              => 'text',
    'label'             => 'URL Slug',
    'sanitize_callback' => function ( $value, $field, $key ) {
        return sanitize_title( $value );
    },
],
```

## Built-in Sanitizers

| Type                               | Sanitizer                                         |
|------------------------------------|----------------------------------------------------|
| `text`, `tel`, `password`, `hidden` | `sanitize_text_field`                              |
| `textarea`                          | `sanitize_textarea_field`                          |
| `email`                             | `sanitize_email`                                   |
| `url`                               | `esc_url_raw`                                      |
| `number`, `range`                   | `intval` or `floatval` (based on step), with min/max clamping |
| `checkbox`, `toggle`                | `filter_var( ..., FILTER_VALIDATE_BOOLEAN )`       |
| `select`, `radio`, `button_group`   | Validates value exists in options                  |
| `select2`, `select_multiple`, `checkbox_group` | Array of validated option values        |
| `wysiwyg`                           | `wp_kses_post`                                     |
| `code`                              | Raw (no sanitization)                              |
| `color`                             | `sanitize_hex_color`                               |
| `date`                              | Validates `Y-m-d` format                           |
| `time`                              | Validates `HH:MM(:SS)` format                      |
| `datetime`                          | Validates `Y-m-d\TH:i` format                     |
| `image`, `file`                     | Validates attachment ID is valid                   |
| `gallery`                           | Array of validated attachment IDs                  |
| `link`                              | Array with `url` (esc_url_raw), `text`, `target`   |
| `post`, `page`                      | Validates post ID exists with correct post type    |
| `taxonomy`                          | Validates term ID exists in taxonomy               |
| `user`                              | Validates user ID exists                           |
| `ajax`                              | `sanitize_text_field` or array of sanitized strings |
| `dimensions`                        | Array with numeric sides and sanitized unit        |
| `repeater`                          | Each row's sub-fields sanitized individually       |
| `group`                             | Each sub-field sanitized individually              |
| `sortable`                          | Array of `sanitize_text_field` values              |
| `email_editor`                      | Array with sanitized subject, title, subtitle, message |
| `license`                           | Array with sanitized key, validated status, expiry |
