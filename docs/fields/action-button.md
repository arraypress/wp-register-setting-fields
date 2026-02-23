# Action Button

A button that fires a REST API request to execute a server-side callback and displays the result. Useful for connection tests, cache clearing, data imports, or any on-demand action.

```php
'test_connection' => [
    'type'          => 'action_button',
    'label'         => 'API Connection',
    'button_label'  => 'Test Connection',
    'loading_label' => 'Testing...',
    'icon'          => 'dashicons-update',
    'success_icon'  => 'dashicons-yes-alt',
    'error_icon'    => 'dashicons-warning',
    'button_class'  => '',                   // Extra class (uses 'button button-secondary' by default)
    'confirm'       => '',                   // Browser confirm dialog text (empty = no confirm)

    'action_callback' => function ( $data ) {
        $response = wp_remote_get( 'https://api.example.com/ping' );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Connection successful!',
        ];
    },
],
```

## With Input

Show an inline text input before the button — the value is passed to the callback as `input_value`:

```php
'import_url' => [
    'type'              => 'action_button',
    'label'             => 'Import Data',
    'button_label'      => 'Import',
    'show_input'        => true,
    'input_placeholder' => 'Enter import URL...',
    'input_type'        => 'url',            // 'text', 'url', 'email', etc.

    'action_callback' => function ( $data ) {
        $url = $data['input_value'];
        $result = import_from_url( $url );
        return [
            'success' => $result,
            'message' => $result ? 'Imported successfully.' : 'Import failed.',
        ];
    },
],
```

## Callback Return Values

The callback can return:

- **bool** — `true` shows success, `false` shows error with default messages
- **string** — shown as a success message
- **array** — `['success' => bool, 'message' => string]`
- **WP_Error** — shown as an error

The result message appears inline next to the button with a success/error icon.
