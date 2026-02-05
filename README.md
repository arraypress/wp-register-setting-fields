# WP Register Setting Fields

A WordPress library for registering settings pages with fields using the WordPress Settings API. Designed to mirror the
API of the post fields library for a consistent developer experience.

## Installation

### Via Composer

```bash
composer require arraypress/wp-register-setting-fields
```

### Manual Installation

Include the bootstrap file:

```php
require_once 'path/to/wp-register-setting-fields/setting-fields.php';
```

## Basic Usage

```php
register_setting_fields( 'my_plugin_settings', [
    'page_title'  => 'My Plugin Settings',
    'menu_title'  => 'My Plugin',
    'parent_slug' => 'options-general.php',
    'capability'  => 'manage_options',
    'tabs'        => [
        'general'  => 'General',
        'advanced' => 'Advanced',
    ],
    'fields' => [
        'api_key' => [
            'label'       => 'API Key',
            'type'        => 'password',
            'tab'         => 'general',
            'description' => 'Enter your API key.',
        ],
        'enable_feature' => [
            'label'          => 'Enable Feature',
            'type'           => 'toggle',
            'tab'            => 'general',
            'checkbox_label' => 'Enable the awesome feature',
        ],
        'log_level' => [
            'label'   => 'Log Level',
            'type'    => 'select',
            'tab'     => 'advanced',
            'options' => [
                'debug'   => 'Debug',
                'info'    => 'Info',
                'warning' => 'Warning',
                'error'   => 'Error',
            ],
            'default' => 'info',
        ],
    ],
] );
```

## Configuration Options

### Page Options

| Option          | Type      | Default                   | Description                                  |
|-----------------|-----------|---------------------------|----------------------------------------------|
| `page_title`    | string    | 'Settings'                | The page title                               |
| `menu_title`    | string    | 'Settings'                | The menu title                               |
| `menu_slug`     | string    | ID                        | The menu slug                                |
| `capability`    | string    | 'manage_options'          | Required capability                          |
| `parent_slug`   | string    | ''                        | Parent menu slug (for submenu)               |
| `icon`          | string    | 'dashicons-admin-generic' | Menu icon (top-level only)                   |
| `position`      | int\|null | null                      | Menu position                                |
| `option_name`   | string    | ID                        | Option name in database                      |
| `option_group`  | string    | `{id}_group`              | Option group for Settings API                |
| `tabs`          | array     | []                        | Tab configuration                            |
| `sections`      | array     | []                        | Section configuration                        |
| `fields`        | array     | []                        | Field configuration                          |
| `submit_button` | bool      | true                      | Show submit button                           |
| `logo`          | string    | ''                        | URL to logo image for header                 |
| `header_title`  | string    | ''                        | Custom header title (defaults to page_title) |
| `header_class`  | string    | ''                        | Custom CSS class for header                  |
| `body_class`    | string    | ''                        | Custom body class for the page               |

### Help Screen Options

```php
'help_tabs' => [
    'getting_started' => [
        'title'    => 'Getting Started',
        'content'  => '<p>Welcome to the plugin settings...</p>',
        'priority' => 10,
    ],
    'advanced' => [
        'title'    => 'Advanced Usage',
        'callback' => 'my_help_tab_callback',
    ],
],
'help_sidebar' => '<p><strong>For more information:</strong></p><p><a href="#">Documentation</a></p>',
```

### Tab Configuration

```php
'tabs' => [
    // Simple format
    'general' => 'General Settings',
    
    // Full format with icon
    'advanced' => [
        'label' => 'Advanced',
        'icon'  => 'dashicons-admin-tools',
    ],
],
```

### Section Configuration

```php
'sections' => [
    'api_settings' => [
        'title'       => 'API Configuration',
        'description' => 'Configure your API credentials.',
        'tab'         => 'general',
    ],
],
```

## Field Types

### Common Field Options

All field types support these options:

| Option              | Type     | Description                |
|---------------------|----------|----------------------------|
| `label`             | string   | Field label                |
| `description`       | string   | Help text below field      |
| `tooltip`           | string   | Hover tooltip on info icon |
| `default`           | mixed    | Default value              |
| `tab`               | string   | Tab key                    |
| `section`           | string   | Section key                |
| `required`          | bool     | Mark as required           |
| `class`             | string   | Additional CSS classes     |
| `placeholder`       | string   | Placeholder text           |
| `show_when`         | array    | Conditional logic          |
| `readonly`          | bool     | Make field read-only       |
| `disabled`          | bool     | Disable the field          |
| `data`              | array    | Custom data attributes     |
| `sanitize_callback` | callable | Custom sanitization        |
| `render_callback`   | callable | Custom rendering           |

### Basic Text Fields

```php
// Text input
'field_name' => [
    'type'         => 'text', // or 'url', 'email', 'tel', 'password'
    'label'        => 'Field Label',
    'placeholder'  => 'Enter value...',
    'maxlength'    => 100,
    'minlength'    => 5,
    'pattern'      => '[A-Za-z]+',
    'autocomplete' => 'off',
    'size'         => 'regular', // 'small', 'regular', 'large'
]

// Textarea
'description' => [
    'type'      => 'textarea',
    'rows'      => 5,
    'cols'      => 50,
    'maxlength' => 500,
]

// WYSIWYG Editor
'content' => [
    'type'            => 'wysiwyg',
    'rows'            => 10,
    'media_buttons'   => true,
    'teeny'           => false,
    'quicktags'       => true,
    'wpautop'         => true,
    'drag_drop_upload' => false,
]

// Code Editor
'custom_css' => [
    'type'     => 'code',
    'language' => 'css', // 'html', 'css', 'javascript', 'php', 'json', 'sql', 'markdown'
    'rows'     => 15,
]
```

### Number Fields

```php
// Number input
'quantity' => [
    'type'   => 'number',
    'min'    => 0,
    'max'    => 100,
    'step'   => 1,
    'suffix' => 'items',
]

// Range slider
'volume' => [
    'type'       => 'range',
    'min'        => 0,
    'max'        => 100,
    'step'       => 5,
    'show_value' => true,
    'suffix'     => '%',
]
```

### Choice Fields

```php
// Select dropdown
'country' => [
    'type'        => 'select',
    'placeholder' => 'Select a country...',
    'options'     => [
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
    ],
]

// Select with optgroups
'category' => [
    'type'    => 'select',
    'options' => [
        'Fruits' => [
            'apple'  => 'Apple',
            'banana' => 'Banana',
        ],
        'Vegetables' => [
            'carrot' => 'Carrot',
            'celery' => 'Celery',
        ],
    ],
]

// Select2 (enhanced select)
'tags' => [
    'type'           => 'select2',
    'multiple'       => true,
    'placeholder'    => 'Select tags...',
    'options'        => [...],
    'tags'           => true,  // Allow creating new options
    'allow_clear'    => true,
    'max_selections' => 5,
]

// Checkbox
'agree_terms' => [
    'type'           => 'checkbox',
    'checkbox_label' => 'I agree to the terms and conditions',
]

// Toggle switch
'enable_feature' => [
    'type'           => 'toggle',
    'checkbox_label' => 'Enable this feature',
]

// Checkbox group
'features' => [
    'type'    => 'checkbox_group',
    'layout'  => 'horizontal', // or 'vertical'
    'options' => [
        'feature_a' => 'Feature A',
        'feature_b' => 'Feature B',
        'feature_c' => 'Feature C',
    ],
]

// Radio buttons
'plan' => [
    'type'    => 'radio',
    'layout'  => 'vertical',
    'options' => [
        'free'    => 'Free',
        'basic'   => 'Basic',
        'premium' => 'Premium',
    ],
]

// Button group
'alignment' => [
    'type'    => 'button_group',
    'options' => [
        'left'   => 'Left',
        'center' => 'Center',
        'right'  => 'Right',
    ],
]
```

### Date/Time Fields

```php
// Color picker
'brand_color' => [
    'type'     => 'color',
    'alpha'    => false,
    'palettes' => ['#000000', '#ffffff', '#dd3333'],
]

// Date
'start_date' => [
    'type' => 'date',
    'min'  => '2024-01-01',
    'max'  => '2025-12-31',
]

// Time
'opening_time' => [
    'type' => 'time',
    'min'  => '09:00',
    'max'  => '17:00',
    'step' => 900, // 15 minute increments
]

// Datetime
'event_datetime' => [
    'type' => 'datetime',
    'min'  => '2024-01-01T00:00',
    'max'  => '2025-12-31T23:59',
]
```

### Media Fields

```php
// Image upload
'logo' => [
    'type'          => 'image',
    'preview_size'  => 'thumbnail',
    'library'       => 'image', // Filter media library
    'return_format' => 'id',
]

// File upload
'document' => [
    'type'          => 'file',
    'allowed_types' => 'pdf,doc,docx',
    'library'       => 'all',
]

// Gallery
'photos' => [
    'type'         => 'gallery',
    'preview_size' => 'thumbnail',
    'min'          => 1,
    'max'          => 10,
]

// oEmbed preview
'video_url' => [
    'type' => 'oembed',
]
```

### Relational Fields

All relational fields use Select2 with AJAX search for optimal performance.

```php
// Post selector
'featured_post' => [
    'type'        => 'post',
    'post_type'   => 'post', // or ['post', 'page'] for multiple
    'multiple'    => false,
    'placeholder' => 'Search posts...',
]

// Page selector
'landing_page' => [
    'type'        => 'page',
    'multiple'    => false,
    'placeholder' => 'Search pages...',
]

// Taxonomy selector
'category' => [
    'type'     => 'taxonomy',
    'taxonomy' => 'category',
    'multiple' => true,
]

// User selector
'author' => [
    'type'       => 'user',
    'role'       => 'author', // or ['editor', 'author']
    'multiple'   => false,
    'show_email' => true,
]

// Custom AJAX selector
'custom_data' => [
    'type'          => 'ajax',
    'multiple'      => true,
    'placeholder'   => 'Search...',
    'ajax_callback' => function( $search, $ids = null ) {
        // $search: search term (string)
        // $ids: array of IDs for hydration (when loading existing values)
        
        // Return array of ['value' => ..., 'label' => ...]
        return [
            ['value' => '1', 'label' => 'Option 1'],
            ['value' => '2', 'label' => 'Option 2'],
        ];
    },
]
```

### Complex Fields

```php
// Link
'cta_link' => [
    'type' => 'link',
]
// Returns: ['url' => '...', 'text' => '...', 'target' => '_self|_blank']

// Dimensions (padding/margin)
'padding' => [
    'type'         => 'dimensions',
    'units'        => ['px', 'em', 'rem', '%'],
    'default_unit' => 'px',
    'sides'        => ['top', 'right', 'bottom', 'left'],
    'linked'       => true,
    'min'          => 0,
    'max'          => 100,
    'step'         => 1,
]

// Sortable list with enable/disable toggle
'column_order' => [
    'type'    => 'sortable',
    'options' => [
        'title'  => 'Title',
        'date'   => 'Date',
        'author' => 'Author',
        'status' => 'Status',
    ],
]
// Returns array of enabled items in order: ['title', 'date']
```

### Nested Fields

```php
// Group
'address' => [
    'type'              => 'group',
    'layout'            => 'block', // 'block', 'row', 'table'
    'collapsible'       => true,
    'collapsed'         => false,
    'title'             => 'Address Details',
    'group_description' => 'Enter the full address.',
    'sub_fields'        => [
        'street' => [
            'type'  => 'text',
            'label' => 'Street',
        ],
        'city' => [
            'type'  => 'text',
            'label' => 'City',
        ],
        'zip' => [
            'type'  => 'text',
            'label' => 'ZIP Code',
        ],
    ],
]

// Repeater
'social_links' => [
    'type'         => 'repeater',
    'layout'       => 'table', // 'table', 'block', 'row'
    'button_label' => 'Add Social Link',
    'min'          => 0,
    'max'          => 10,
    'sortable'     => true,
    'collapsed'    => false,
    'max_width'    => '800px',
    'sub_fields'   => [
        'platform' => [
            'type'    => 'select',
            'label'   => 'Platform',
            'options' => [
                'facebook'  => 'Facebook',
                'twitter'   => 'Twitter',
                'instagram' => 'Instagram',
            ],
        ],
        'url' => [
            'type'  => 'url',
            'label' => 'URL',
        ],
    ],
]
```

### Content/Layout Fields

```php
// Raw HTML
'instructions' => [
    'type'    => 'html',
    'content' => '<div class="notice notice-info"><p>Instructions here.</p></div>',
]

// Message/Notice
'warning' => [
    'type'         => 'message',
    'message_type' => 'warning', // 'info', 'success', 'warning', 'error'
    'content'      => 'Please backup your data before proceeding.',
    'inline'       => true,
]

// Separator with optional title
'divider' => [
    'type'        => 'separator',
    'title'       => 'Advanced Options',
    'description' => 'Configure advanced settings below.',
]

// Heading
'section_heading' => [
    'type'        => 'heading',
    'title'       => 'API Configuration',
    'description' => 'Configure your API connection settings.',
    'level'       => 'h3', // 'h2', 'h3', 'h4', 'h5', 'h6'
]

// Custom field with callback
'my_custom' => [
    'type'     => 'custom',
    'callback' => function( $field, $name, $id, $value ) {
        echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
    },
]
```

### Email Editor Field

A complete email template editor with merge tag support.

```php
'welcome_email' => [
    'type'             => 'email_editor',
    'label'            => 'Welcome Email',
    'collapsible'      => true,
    'collapsed'        => true,
    'title'            => 'Welcome Email',
    'card_description' => 'Sent when a new user registers.',
    'show_enable'      => true,
    'default_enabled'  => true,
    'default_subject'  => 'Welcome to {site_name}!',
    'default_body'     => '<p>Hi {user_name},</p><p>Welcome aboard!</p>',
    'rows'             => 15,
    'show_preview'     => true,
    'show_send_test'   => true,
    'merge_tags'       => [
        '{site_name}' => [
            'label'       => 'Site Name',
            'description' => 'Your website name',
        ],
        '{user_name}' => [
            'label'       => 'User Name',
            'description' => 'The user\'s display name',
        ],
        '{user_email}' => 'User Email', // Simple format
    ],
    'preview_callback' => function( $data ) {
        // Process merge tags and return HTML
        $body = str_replace( '{site_name}', get_bloginfo( 'name' ), $data['body'] );
        return [
            'subject' => $data['subject'],
            'body'    => wpautop( $body ),
        ];
    },
    'send_callback' => function( $data ) {
        // Send the test email
        $sent = wp_mail( $data['to'], $data['subject'], $data['body'], ['Content-Type: text/html'] );
        return $sent ? true : ['success' => false, 'message' => 'Failed to send.'];
    },
]
// Returns: ['enabled' => true, 'subject' => '...', 'body' => '...']
```

## Conditional Logic

Show/hide fields based on other field values:

```php
'enable_notifications' => [
    'type'  => 'toggle',
    'label' => 'Enable Notifications',
],

// Simple format - show when field equals value
'notification_email' => [
    'type'      => 'email',
    'label'     => 'Notification Email',
    'show_when' => ['enable_notifications' => 1],
],

// Multiple conditions (all must be true)
'digest_time' => [
    'type'      => 'time',
    'label'     => 'Send Digest At',
    'show_when' => [
        'enable_notifications'     => 1,
        'notification_frequency'   => 'daily',
    ],
],

// Advanced format with operators
'advanced_field' => [
    'type'      => 'text',
    'label'     => 'Advanced Field',
    'show_when' => [
        ['field' => 'enable_notifications', 'value' => 1, 'operator' => '='],
        ['field' => 'notification_frequency', 'value' => ['daily', 'weekly'], 'operator' => 'in'],
    ],
],
```

### Available Operators

| Operator             | Description                                       |
|----------------------|---------------------------------------------------|
| `=`, `==`            | Equals                                            |
| `===`                | Strict equals                                     |
| `!=`, `!==`          | Not equals                                        |
| `>`, `>=`, `<`, `<=` | Numeric comparisons                               |
| `in`                 | Value in array                                    |
| `not_in`             | Value not in array                                |
| `contains`           | Array contains value or string contains substring |
| `not_contains`       | Opposite of contains                              |
| `empty`              | Value is empty                                    |
| `not_empty`          | Value is not empty                                |

## Encryption

Securely store sensitive values like API keys with automatic encryption.

```php
register_setting_fields( 'my_settings', [
    'fields' => [
        'api_key' => [
            'type'      => 'password',
            'label'     => 'API Key',
            'encrypted' => true, // Stored encrypted in database
        ],
        'secret_key' => [
            'type'      => 'password',
            'label'     => 'Secret Key',
            'encrypted' => true,
            'constant'  => 'MY_PLUGIN_SECRET_KEY', // Optional constant override
        ],
    ],
    
    // Optional encryption configuration
    'encryption' => [
        'enabled' => true,        // Auto-detected if any field has 'encrypted' => true
        'key'     => null,        // Custom key (defaults to WordPress salts)
        'prefix'  => 'myplugin',  // Prefix for encrypted values
    ],
] );
```

### Constant Override

Define sensitive values in `wp-config.php` instead of the database:

```php
// wp-config.php
define( 'MY_SETTINGS_API_KEY', 'sk-your-actual-key' );
define( 'MY_PLUGIN_SECRET_KEY', 'your-secret' );
```

When a constant is defined:

- The database value is ignored
- The field displays as read-only
- The constant value is used when retrieved

### Custom Encryption Key

For additional security, define a custom encryption key:

```php
// wp-config.php
define( 'WP_ENCRYPTION_KEY', 'your-secure-random-key-here' );
```

## Helper Functions

### Registration

```php
// Register a settings page
$settings = register_setting_fields( 'my_plugin_settings', $config );

// Get a registered settings instance
$settings = get_setting_fields( 'my_plugin_settings' );
```

### Getting Values

```php
// Get a single value (with decryption and constant fallback)
$api_key = get_setting_field_value( 'my_plugin_settings', 'api_key', 'default' );

// Get all values
$all_settings = get_all_setting_values( 'my_plugin_settings' );
```

### Updating Values

```php
// Update a single value
update_setting_field_value( 'my_plugin_settings', 'api_key', 'new_key' );

// Delete a single value (resets to default)
delete_setting_field_value( 'my_plugin_settings', 'api_key' );
```

### Type-Specific Helpers

```php
// Check if toggle/checkbox is enabled
if ( is_setting_on( 'my_plugin_settings', 'enable_feature' ) ) {
    // Feature is enabled
}

// Check if value exists in checkbox_group or multi-select
if ( is_setting_enabled( 'my_plugin_settings', 'features', 'feature_a' ) ) {
    // Feature A is selected
}
```

### Page Field Helpers

```php
// Get page ID from a page selector field
$cart_page_id = get_setting_field_page_id( 'my_settings', 'cart_page' );

// Get page URL with fallback
$cart_url = get_setting_field_page_url( 'my_settings', 'cart_page', home_url( '/cart/' ) );

// Check if currently viewing the configured page
if ( is_setting_field_page( 'my_settings', 'cart_page' ) ) {
    // On the cart page
}
```

### Direct Instance Access

```php
$settings = get_setting_fields( 'my_plugin_settings' );

// Get a value
$value = $settings->get_value( 'api_key', 'default' );

// Get all values
$values = $settings->get_values();

// Get field configuration
$field = $settings->get_field( 'api_key' );

// Reset to defaults
$settings->reset_to_defaults();        // All fields
$settings->reset_to_defaults( 'general' ); // Just one tab

// Delete all settings
$settings->delete_settings();
```

## Hooks and Filters

### Sanitization

```php
// Before sanitization
add_filter( 'setting_fields_pre_sanitize_value', function( $value, $field, $key ) {
    // Modify value before sanitization
    return $value;
}, 10, 3 );

// After sanitization
add_filter( 'setting_fields_sanitize_value', function( $sanitized, $value, $field, $key ) {
    // Modify sanitized value
    return $sanitized;
}, 10, 4 );

// All settings before save
add_filter( 'setting_fields_sanitize_settings', function( $sanitized, $input, $old_value, $id ) {
    // Modify all settings before save
    return $sanitized;
}, 10, 4 );
```

### Custom Field Rendering

```php
add_filter( 'setting_fields_render_field', function( $rendered, $key, $field, $name, $id, $value ) {
    if ( $field['type'] === 'my_custom_type' ) {
        echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
        return true; // Mark as rendered
    }
    return $rendered;
}, 10, 6 );
```

## Plugin Integration Example

Create a thin wrapper for your plugin:

```php
<?php
namespace MyPlugin;

const SETTINGS_ID = 'my_plugin_settings';

/**
 * Get a setting value with filtering.
 */
function get_setting( string $key, $default = null ) {
    $value = get_setting_field_value( SETTINGS_ID, $key, $default );
    
    return apply_filters( 'my_plugin_get_setting', $value, $key, $default );
}

/**
 * Update a setting value with action hook.
 */
function update_setting( string $key, $value ): bool {
    $value   = apply_filters( 'my_plugin_update_setting_value', $value, $key );
    $updated = update_setting_field_value( SETTINGS_ID, $key, $value );
    
    if ( $updated ) {
        do_action( 'my_plugin_setting_updated', $key, $value );
    }
    
    return $updated;
}

/**
 * Check if a feature is enabled.
 */
function is_feature_enabled( string $key ): bool {
    return is_setting_on( SETTINGS_ID, $key );
}

/**
 * Get cart page URL.
 */
function get_cart_url( array $args = [] ): string {
    $url = get_setting_field_page_url( SETTINGS_ID, 'cart_page', home_url( '/cart/' ) );
    $url = apply_filters( 'my_plugin_cart_url', $url, $args );
    
    return ! empty( $args ) ? add_query_arg( $args, $url ) : $url;
}

/**
 * Check if on cart page.
 */
function is_cart_page(): bool {
    return is_setting_field_page( SETTINGS_ID, 'cart_page' );
}
```

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

GPL-2.0-or-later