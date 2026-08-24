# Encryption

Transparent AES-256-GCM encryption for sensitive field values — API keys, tokens, secrets. Encrypted values are stored with an `fkenc:` prefix and decrypted on read.

GCM authenticates as well as encrypts, so a tampered value fails to decrypt rather than decrypting to something else.

## Enable Per-Field

Mark the field and nothing else:

```php
'api_secret' => [
    'type'      => 'password',
    'label'     => 'API Secret',
    'encrypted' => true,
],
```

There is no global switch to set. A field that asks to be encrypted is encrypted.

## The Key

The key is derived from the site's own salts — `LOGGED_IN_KEY`, `LOGGED_IN_SALT`, `AUTH_KEY` and `SECURE_AUTH_KEY`, hashed together with SHA-256.

That has a consequence worth stating plainly:

- A value encrypted on one site **cannot be read on another**.
- **Rotating your salts makes existing values unreadable.**

Both are the correct trade for a credential — it is why a leaked database alone is not enough to use one — but it means an encrypted field is not something to put in a migration and expect to survive. It is also why an encrypted value is left out of an export entirely (see [Reset & Export/Import](../reset-export-import.md)).

## Failure Behaviour

Two cases are decided deliberately rather than left to chance:

- **Encryption fails** (no OpenSSL, no salts): the write is **dropped**. Storing the plaintext instead is the one outcome worse than not saving.
- **A field is marked encrypted after it already held something**: the existing plaintext still reads. Marking a populated field encrypted does not lose it; the next save encrypts it.

## Reading Values

`get_setting_field_value()` and `$settings->get_value()` return the decrypted value. `$settings->get_values()` returns the option **as stored**, so encrypted entries are ciphertext there.

```php
$secret = get_setting_field_value( 'my_plugin', 'api_secret' );  // decrypted
$raw    = get_setting_fields( 'my_plugin' )->get_values();       // 'fkenc:…'
```

## Constant Fallback

For maximum security, define the value as a PHP constant instead of storing it at all:

```php
// wp-config.php
define( 'MY_PLUGIN_API_SECRET', 'sk_live_abc123' );
```

A field has to opt in, either by naming the constant:

```php
'api_secret' => [
    'type'     => 'password',
    'label'    => 'API Secret',
    'constant' => 'MY_PLUGIN_API_SECRET',
],
```

…or by asking for one to be derived from its key:

```php
'api_secret' => [
    'type'         => 'password',
    'label'        => 'API Secret',
    'use_constant' => true,
],
```

The derived name is `constant_prefix` + the field key, uppercased — so `MY_PLUGIN_API_SECRET` for the option `my_plugin`. Set `constant_prefix` in the page config to change it.

Opting in is required on purpose. Deriving a name for every field would let an unrelated constant that happens to match a field key silently take one over.

When the constant is defined, the field reads from it and **a write to that field is dropped** rather than stored and shadowed — so the database never holds a stale copy of something the constant is now supplying.

## Requirements

OpenSSL, with `aes-256-gcm` among `openssl_get_cipher_methods()`, and salts defined. Without all three the encrypting layer reports itself unavailable and values are stored as they are.

## Where It Lives

Encryption and the constant fallback are `wp-field-kit` decorators (`EncryptedContext`, `ConstantContext`), not features of this library. Both work the same way on post meta and term meta.
