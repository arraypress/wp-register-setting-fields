# Email Helpers

Bridge functions for integrating `email_editor` fields with the `wp-register-emails` library.

## Get Email Settings

Returns the stored email editor values in the format expected by `register_email_template`'s `settings_callback`:

```php
// In your email template registration
register_email_template( 'shop', 'purchase_receipt', [
    'settings_callback' => fn() => get_setting_fields_email( 'my_plugin', 'email_purchase' ),
] );
```

Returns an array with `enabled`, `recipient`, `subject`, `title`, `subtitle`, and `message` keys. The `message` value has `wpautop()` applied automatically.

## Get Recipient

```php
$to = get_setting_fields_email_recipient( 'my_plugin', 'email_sale_notification' );
// Returns the stored recipient email, or falls back to admin email
```

## Check Enabled

```php
if ( is_setting_fields_email_enabled( 'my_plugin', 'email_purchase' ) ) {
    // Email is enabled — proceed with sending
}
```

Returns `true` if the email is enabled, or `true` by default if no stored value exists.
