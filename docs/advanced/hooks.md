# Hooks & Filters

## Sanitization Filters

```php
// Before any field is sanitized
add_filter( 'setting_fields_pre_sanitize_value', function ( $value, $field, $key ) {
    return $value;
}, 10, 3 );

// After a field is sanitized (per-field)
add_filter( 'setting_fields_sanitize_value', function ( $sanitized, $value, $field, $key ) {
    return $sanitized;
}, 10, 4 );

// After all fields are sanitized (full array)
add_filter( 'setting_fields_sanitize_settings', function ( $sanitized, $input, $old_value, $id ) {
    return $sanitized;
}, 10, 4 );
```

## Field Rendering Filter

```php
// Custom field rendering (return true to skip default renderer)
add_filter( 'setting_fields_render_field', function ( $rendered, $key, $field, $name, $id, $value ) {
    if ( $key === 'my_special_field' ) {
        echo '<div>Custom output</div>';
        return true;
    }
    return false;
}, 10, 6 );
```

## REST API Permission

```php
// Override the default capability check for REST endpoints
add_filter( 'setting_fields_rest_capability', function ( $capability ) {
    return 'edit_posts';
} );
```
