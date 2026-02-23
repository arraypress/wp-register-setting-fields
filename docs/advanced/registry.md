# Registry

Singleton registry for managing multiple settings pages:

```php
use ArrayPress\RegisterSettingFields\Registry;

// Get the registry instance
$registry = Registry::instance();

// Get a specific settings page
$settings = $registry->get( 'my_plugin' );

// Check if a settings page exists
if ( $registry->has( 'my_plugin' ) ) {
    // ...
}

// Get all registered settings pages
$all = $registry->all();

// Unregister a settings page
$registry->unregister( 'my_plugin' );
```

Each call to `register_setting_fields()` automatically registers the instance with the registry.
