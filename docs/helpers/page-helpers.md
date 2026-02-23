# Page Helpers

Convenience functions for `post` and `page` field types.

## Get Page ID

```php
$checkout_id = get_setting_field_page_id( 'my_plugin', 'checkout_page' );
// Returns: 42 (or 0 if not set)
```

## Get Page URL

```php
$checkout_url = get_setting_field_page_url( 'my_plugin', 'checkout_page' );
// Returns: 'https://example.com/checkout/'

// With fallback
$url = get_setting_field_page_url( 'my_plugin', 'checkout_page', 'https://example.com/shop/' );
```

Falls back to `home_url('/')` if no page is set and no fallback is provided.

## Check Current Page

```php
if ( is_setting_field_page( 'my_plugin', 'checkout_page' ) ) {
    // Currently viewing the checkout page
}
```

Returns `true` if the current request is for the page stored in the field. Only works on singular pages.
