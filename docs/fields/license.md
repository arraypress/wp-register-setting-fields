# License

License key field with activation/deactivation via REST API, status badge, expiry display, and optional renewal URL.

```php
'license' => [
    'type'               => 'license',
    'label'              => 'License Key',
    'placeholder'        => 'Enter your license key...',
    'activate_label'     => 'Activate',
    'deactivate_label'   => 'Deactivate',
    'activate_loading'   => 'Activating...',
    'deactivate_loading' => 'Deactivating...',
    'url'                => 'https://example.com/account/',  // Renewal link
    'url_label'          => 'Renew License',

    // Server-side callback for activate/deactivate
    'callback' => function ( $data ) {
        $key    = $data['key'];
        $action = $data['action'];           // 'activate' or 'deactivate'

        $result = call_license_api( $key, $action );

        return [
            'success'   => $result->success,
            'message'   => $result->message,
            'status'    => $result->status,   // 'active', 'inactive', 'expired', 'invalid'
            'expiry'    => $result->expiry,   // e.g. '2027-01-15'
            'url'       => '',                // Optional: override renewal URL
            'url_label' => '',                // Optional: override renewal label
        ];
    },
],
```

## Behavior

The field renders a password input with an activate/deactivate button. When active, the key input becomes readonly. The status badge updates automatically based on the callback response, and the status/expiry are persisted to the stored value.

## Status Values

| Status     | Badge Color | Input State |
|------------|-------------|-------------|
| `inactive` | Gray        | Editable    |
| `active`   | Green       | Readonly    |
| `expired`  | Red         | Editable    |
| `invalid`  | Red         | Editable    |

The renewal URL link appears automatically when the status is `expired` or `invalid`.

## Saved Data Shape

```php
[
    'license' => [
        'key'    => 'XXXX-XXXX-XXXX-XXXX',
        'status' => 'active',
        'expiry' => '2027-01-15',
    ],
]
```

## Helper Functions

```php
// Check if license is active
if ( is_setting_field_license_active( 'my_plugin', 'license' ) ) {
    // Pro features
}

// Get the license key string
$key = get_setting_field_license_key( 'my_plugin', 'license' );

// Update status from a webhook or cron
update_setting_field_license_status( 'my_plugin', 'license', 'expired', '2025-01-15' );
```

See [License Helpers](helpers/license-helpers.md) for the full API.
