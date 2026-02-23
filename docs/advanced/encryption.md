# Encryption

Transparent AES-256-CBC encryption for sensitive field values (API keys, tokens, secrets). Encrypted values are stored in the database with a `$ENC$` prefix and decrypted automatically on retrieval.

## Enable Per-Field

Mark individual fields as encrypted:

```php
'api_secret' => [
    'type'      => 'password',
    'label'     => 'API Secret',
    'encrypted' => true,
],
```

Encryption is auto-enabled when any field has `encrypted => true`. No global config needed.

## Global Configuration

Optionally customize the encryption settings:

```php
register_setting_fields( 'my_plugin', [
    'encryption' => [
        'enabled' => true,                   // Force enable (auto-detected by default)
        'key'     => 'my-custom-key',        // Custom key (defaults to WP salts)
        'prefix'  => 'myapp',                // Identifier prefix (defaults to settings ID)
    ],
    // ...
] );
```

## Encryption Key

The encryption key is resolved in this order:

1. Custom `key` in the encryption config
2. `WP_ENCRYPTION_KEY` constant (define in `wp-config.php`)
3. WordPress authentication salts (`AUTH_KEY`, `SECURE_AUTH_KEY`, etc.)

All keys are hashed with SHA-256 before use.

## Constant Fallback

For maximum security, define sensitive values as PHP constants in `wp-config.php` instead of storing them in the database:

```php
// wp-config.php
define( 'MY_PLUGIN_API_SECRET', 'sk_live_abc123' );
```

The constant name is auto-generated from the option name and field key: `{OPTION_NAME}_{FIELD_KEY}` (uppercased). Or set it explicitly:

```php
'api_secret' => [
    'type'      => 'password',
    'label'     => 'API Secret',
    'encrypted' => true,
    'constant'  => 'MY_CUSTOM_CONSTANT_NAME',
],
```

When a constant is defined, the field becomes readonly/disabled in the admin, the constant value is used instead of the database value, and the database value is stored as empty.

## Admin UI

Encrypted fields show a lock icon with status text:

- **"Stored encrypted"** — value is in the database, encrypted
- **"Defined in constant: `MY_PLUGIN_API_SECRET`"** — value comes from a PHP constant

## Key Rotation

Re-encrypt all fields with a new key:

```php
$settings = get_setting_fields( 'my_plugin' );
$settings->rotate_encryption_key( 'new-encryption-key' );
```

## Value Info

Get detailed information about a field's value source:

```php
$info = $settings->get_value_info( 'api_secret' );
// Returns: [
//     'value'         => 'decrypted-value',
//     'source'        => 'database',    // 'database', 'constant', 'default'
//     'is_encrypted'  => true,
//     'constant_name' => 'MY_PLUGIN_API_SECRET',
// ]
```

## Requirements

OpenSSL PHP extension must be enabled (`extension_loaded('openssl')`).
