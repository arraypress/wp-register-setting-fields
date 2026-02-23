# Type Checks

Convenience functions for toggle and multi-select fields.

## Toggle / Checkbox

```php
if ( is_setting_on( 'my_plugin', 'debug_mode' ) ) {
    // Debug mode is enabled
}
```

Returns `true` if the toggle/checkbox value is truthy (`'1'`, `true`, `'on'`, etc.).

## Multi-Select / Checkbox Group

```php
if ( is_setting_enabled( 'my_plugin', 'features', 'api' ) ) {
    // 'api' is selected in the features checkbox group
}
```

Checks if a specific value exists in an array field (checkbox_group, select2, select_multiple).
