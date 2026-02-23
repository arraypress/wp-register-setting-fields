# License Helpers

Utility functions for reading and updating license field data.

## Get Full License Data

```php
$license = get_setting_field_license( 'my_plugin', 'license' );
// Returns: [ 'key' => 'XXXX-...', 'status' => 'active', 'expiry' => '2027-01-15' ]
```

## Get License Key

```php
$key = get_setting_field_license_key( 'my_plugin', 'license' );
// Returns: 'XXXX-XXXX-XXXX-XXXX'
```

## Get License Status

```php
$status = get_setting_field_license_status( 'my_plugin', 'license' );
// Returns: 'active', 'inactive', 'expired', or 'invalid'
```

## Check Active

```php
if ( is_setting_field_license_active( 'my_plugin', 'license' ) ) {
    // License is active — enable pro features
}
```

## Update Status Programmatically

Update the license status from a cron job, webhook, or remote license check without going through the settings page:

```php
update_setting_field_license_status( 'my_plugin', 'license', 'expired', '2025-01-15' );
```

Parameters: `$settings_id`, `$field_key`, `$status`, `$expiry` (optional). Allowed statuses: `inactive`, `active`, `expired`, `invalid`.
