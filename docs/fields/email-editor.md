# Email Editor

Full email template editor with subject line, WYSIWYG body, merge tag insertion, preview, and send test functionality. Supports two modes: integrated with `wp-register-emails` or standalone.

## Integrated Mode (Recommended)

Connect to an email template registered with `wp-register-emails`. Merge tags, defaults, preview, and sending are handled automatically:

```php
'email_purchase' => [
    'type'           => 'email_editor',
    'label'          => 'Purchase Receipt',
    'email_group'    => 'shop',              // Email group/prefix
    'email_template' => 'purchase_receipt',  // Template name

    // Display options
    'show_enable'    => true,                // Enable/disable toggle
    'show_recipient' => false,               // Recipient email field
    'show_title'     => true,                // Email header title field
    'show_subtitle'  => true,                // Email header subtitle field
    'show_preview'   => true,                // Preview button
    'show_send_test' => true,                // Send test button
    'rows'           => 15,                  // Editor rows

    // Collapsible card wrapper
    'collapsible'       => true,
    'collapsed'         => true,
    'title'             => 'Purchase Receipt Email',
    'card_description'  => 'Sent when a customer completes a purchase.',
],
```

## Standalone Mode

Provide merge tags and callbacks directly:

```php
'notification_email' => [
    'type'              => 'email_editor',
    'label'             => 'Notification',
    'show_enable'       => true,
    'show_recipient'    => true,
    'default_recipient' => get_option( 'admin_email' ),
    'default_subject'   => 'New Order: {order_id}',
    'default_body'      => '<p>A new order has been placed.</p>',
    'default_enabled'   => true,

    'merge_tags' => [
        '{order_id}' => [
            'label'       => 'Order ID',
            'description' => 'The unique order identifier',
        ],
        '{customer_name}' => [
            'label'       => 'Customer Name',
            'description' => 'Full name of the customer',
        ],
    ],

    // Optional callbacks (falls back to wp_mail if not provided)
    'preview_callback' => function ( $data ) {
        return '<html>...' . $data['message'] . '...</html>';
    },
    'send_callback' => function ( $data ) {
        return wp_mail( $data['to'], $data['subject'], $data['message'] );
    },
],
```

## Merge Tags

Merge tags appear in a searchable modal when the user clicks the tag insertion button (available next to subject, title, subtitle, and in the editor toolbar). Tags are inserted at the cursor position.

## Collapsible Card

When `collapsible` is `true`, the editor wraps in a card with a Configure button and optional enable toggle in the header bar. The body expands/collapses on click.

## Saved Data Shape

```php
[
    'email_purchase' => [
        'enabled'   => true,
        'recipient' => 'admin@example.com',  // Only if show_recipient
        'subject'   => 'Your Order #{order_id}',
        'title'     => 'Order Confirmation',
        'subtitle'  => 'Thank you for your purchase',
        'message'   => '<p>Your order details...</p>',
    ],
]
```

## wp-register-emails Integration

When `email_group` and `email_template` are set and the `wp-register-emails` library is installed, the editor:

- Loads merge tags from the template registration via `get_email_template_tags()`
- Loads default subject/title/subtitle/message from the template's `get_settings()`
- Routes preview through `get_email_preview_html()`
- Routes send test through `send_email_template()`

Use the helper function to bridge settings back to the email library:

```php
// In your email template registration
register_email_template( 'shop', 'purchase_receipt', [
    'settings_callback' => fn() => get_setting_fields_email( 'my_plugin', 'email_purchase' ),
] );
```
