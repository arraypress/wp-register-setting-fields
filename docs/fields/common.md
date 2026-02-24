# Common Field Options

All field types share these common options:

```php
'field_key' => [
    'type'              => 'text',           // Field type (required)
    'label'             => 'Field Label',    // Auto-generated from key if omitted
    'default'           => '',               // Default value
    'description'       => '',               // Help text below the field
    'tooltip'           => '',               // Info icon tooltip next to the label
    'placeholder'       => '',
    'required'          => false,
    'disabled'          => false,
    'readonly'          => false,
    'class'             => '',               // CSS class on the input element
    'tab'               => '',               // Tab key (defaults to first tab)
    'section'           => '',               // Section key
    'badge'             => '',               // Upgrade badge (see Badges)
    'depends'           => [],               // Conditional display (see Conditional Fields)
    'sanitize_callback' => null,             // Custom sanitization function
    'render_callback'   => null,             // Custom render function
    'data'              => [],               // Custom data-* attributes on the input
    'encrypted'         => false,            // Enable encryption (see Encryption)
],
```

## Badge

Display an inline pill next to the field label. Useful for marking fields as "Pro", "Premium", or any upgrade tier. Combine with `disabled` to create a locked preview state:

```php
'advanced_sync' => [
    'type'     => 'toggle',
    'label'    => 'Advanced Sync',
    'badge'    => 'Pro',
    'disabled' => true,
],
```

Pass a string for a simple label, or an array for full control over URL, icon, and color. See [Badges](badges.md) for all options.

## Tooltip

When `tooltip` is set, an info icon appears next to the label with hover text:

```php
'api_key' => [
    'type'    => 'text',
    'label'   => 'API Key',
    'tooltip' => 'Find this in your account dashboard under Settings → API.',
],
```

## Custom Rendering

Override the default renderer for any field:

```php
'custom_field' => [
    'type'            => 'text',
    'label'           => 'Custom',
    'render_callback' => function ( $field, $name, $id, $value ) {
        printf( '<input type="text" name="%s" value="%s" />', esc_attr( $name ), esc_attr( $value ) );
        echo '<p>Custom markup here</p>';
    },
],
```

## Custom Type

For fully custom fields, use `type => 'custom'` with a `callback`:

```php
'my_widget' => [
    'type'     => 'custom',
    'label'    => 'Widget',
    'callback' => function ( $field, $name, $id, $value ) {
        // Render anything
    },
],
```
