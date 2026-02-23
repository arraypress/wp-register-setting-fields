# Value Access

Core helper functions for reading, writing, and deleting setting values. Use these instead of `get_option()` directly — they handle decryption, constant fallback, and type resolution automatically.

## Register & Retrieve Instance

```php
// Register a settings page (returns SettingFields instance)
$settings = register_setting_fields( 'my_plugin', [...] );

// Get a registered instance later
$settings = get_setting_fields( 'my_plugin' );
```

## Get a Single Value

```php
$api_key = get_setting_field_value( 'my_plugin', 'api_key' );
$mode    = get_setting_field_value( 'my_plugin', 'mode', 'production' );  // With default
```

Handles decryption for encrypted fields and checks for constant overrides automatically.

## Get All Values

```php
$all = get_all_setting_values( 'my_plugin' );
// Returns: [ 'api_key' => '...', 'mode' => 'production', ... ]
```

## Update a Single Value

```php
update_setting_field_value( 'my_plugin', 'mode', 'staging' );
```

> **Note:** This bypasses encryption. For encrypted fields, use the settings form.

## Delete a Single Value

```php
delete_setting_field_value( 'my_plugin', 'deprecated_field' );
```

Removes the key from the option array. The next `get_setting_field_value()` call will return the field's default.
