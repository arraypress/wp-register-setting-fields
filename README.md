# WP Register Setting Fields

A WordPress library for registering settings pages with fields using the WordPress Settings API. Designed to mirror the API of the post fields library for a consistent developer experience.

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
use function ArrayPress\WP\Register\SettingFields\Utilities\register_setting_fields;

register_setting_fields( 'my_plugin_settings', [
    'page_title'  => 'My Plugin Settings',
    'menu_title'  => 'My Plugin',
    'parent_slug' => 'options-general.php', // Add under Settings menu
    'capability'  => 'manage_options',
    'tabs'        => [
        'general' => 'General',
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

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `page_title` | string | 'Settings' | The page title |
| `menu_title` | string | 'Settings' | The menu title |
| `menu_slug` | string | ID | The menu slug |
| `capability` | string | 'manage_options' | Required capability |
| `parent_slug` | string | '' | Parent menu slug (for submenu) |
| `icon` | string | 'dashicons-admin-generic' | Menu icon (top-level only) |
| `position` | int\|null | null | Menu position |
| `option_name` | string | ID | Option name in database |
| `tabs` | array | [] | Tab configuration |
| `sections` | array | [] | Section configuration |
| `fields` | array | [] | Field configuration |
| `show_title` | bool | true | Show page title |
| `show_tabs` | bool | true | Show tab navigation |
| `submit_button` | bool | true | Show submit button |

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

### Basic Text Fields

```php
// Text input
'field_name' => [
    'type'        => 'text', // or 'url', 'email', 'tel', 'password'
    'label'       => 'Field Label',
    'placeholder' => 'Enter value...',
    'maxlength'   => 100,
    'size'        => 'regular', // 'small', 'regular', 'large'
]

// Textarea
'description' => [
    'type' => 'textarea',
    'rows' => 5,
    'cols' => 50,
]

// WYSIWYG Editor
'content' => [
    'type'          => 'wysiwyg',
    'rows'          => 10,
    'media_buttons' => true,
    'teeny'         => false,
]

// Code Editor
'custom_css' => [
    'type'     => 'code',
    'language' => 'css', // 'html', 'css', 'javascript', 'php', 'json'
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
    'type'        => 'select2',
    'multiple'    => true,
    'placeholder' => 'Select tags...',
    'options'     => [...],
    'tags'        => true, // Allow creating new options
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
    'type'  => 'color',
    'alpha' => false, // Enable alpha/opacity
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
    'step' => 900, // 15 minute increments
]

// Datetime
'event_datetime' => [
    'type' => 'datetime',
]
```

### Media Fields

```php
// Image upload
'logo' => [
    'type'         => 'image',
    'preview_size' => 'thumbnail',
]

// File upload
'document' => [
    'type'          => 'file',
    'allowed_types' => 'pdf,doc,docx',
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

```php
// Post selector (static)
'featured_post' => [
    'type'      => 'post',
    'post_type' => 'post',
    'multiple'  => false,
]

// Post selector (AJAX)
'related_posts' => [
    'type'        => 'post_ajax',
    'post_type'   => 'post',
    'multiple'    => true,
    'placeholder' => 'Search posts...',
]

// Taxonomy selector
'category' => [
    'type'     => 'taxonomy',
    'taxonomy' => 'category',
]

// Taxonomy AJAX
'tags' => [
    'type'     => 'taxonomy_ajax',
    'taxonomy' => 'post_tag',
    'multiple' => true,
]

// User selector
'author' => [
    'type'     => 'user',
    'role'     => 'author',
    'multiple' => false,
]

// User AJAX
'team_members' => [
    'type'       => 'user_ajax',
    'role'       => ['editor', 'author'],
    'multiple'   => true,
    'show_email' => true,
]
```

### Complex Fields

```php
// Link
'cta_link' => [
    'type' => 'link',
]

// Dimensions (padding/margin)
'padding' => [
    'type'         => 'dimensions',
    'units'        => ['px', 'em', 'rem', '%'],
    'default_unit' => 'px',
    'sides'        => ['top', 'right', 'bottom', 'left'],
    'linked'       => true, // Link all sides
]
```

### Nested Fields

```php
// Group
'address' => [
    'type'       => 'group',
    'layout'     => 'block', // 'block', 'row', 'table'
    'sub_fields' => [
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

### Content Fields

```php
// Raw HTML
'instructions' => [
    'type'    => 'html',
    'content' => '<div class="notice notice-info"><p>Some helpful instructions here.</p></div>',
]

// Message/Notice
'warning' => [
    'type'         => 'message',
    'message_type' => 'warning', // 'info', 'success', 'warning', 'error'
    'content'      => 'Please backup your data before proceeding.',
]
```

## Conditional Logic

Show/hide fields based on other field values:

```php
'enable_notifications' => [
    'type' => 'toggle',
    'label' => 'Enable Notifications',
],

'notification_email' => [
    'type'      => 'email',
    'label'     => 'Notification Email',
    'show_when' => ['enable_notifications' => 1],
],

'notification_frequency' => [
    'type'      => 'select',
    'label'     => 'Frequency',
    'show_when' => ['enable_notifications' => 1],
    'options'   => [
        'instant' => 'Instant',
        'daily'   => 'Daily',
        'weekly'  => 'Weekly',
    ],
],

'digest_time' => [
    'type'      => 'time',
    'label'     => 'Send Digest At',
    'show_when' => [
        ['field' => 'enable_notifications', 'value' => 1],
        ['field' => 'notification_frequency', 'operator' => 'in', 'value' => ['daily', 'weekly']],
    ],
],
```

### Available Operators

- `=`, `==` - Equals
- `===` - Strict equals
- `!=`, `!==` - Not equals
- `>`, `>=`, `<`, `<=` - Comparisons
- `in` - Value in array
- `not_in` - Value not in array
- `contains` - Array contains value or string contains substring
- `not_contains` - Opposite of contains
- `empty` - Value is empty
- `not_empty` - Value is not empty

## Helper Functions

```php
use function ArrayPress\WP\Register\SettingFields\Utilities\{
    get_setting_field_value,
    get_all_setting_values,
    update_setting_field_value,
    is_setting_enabled,
    is_setting_on
};

// Get a single value
$api_key = get_setting_field_value('my_plugin_settings', 'api_key', 'default');

// Get all values
$all_settings = get_all_setting_values('my_plugin_settings');

// Update a single value
update_setting_field_value('my_plugin_settings', 'api_key', 'new_key');

// Check if feature is enabled in checkbox group
if (is_setting_enabled('my_plugin_settings', 'features', 'feature_a')) {
    // Feature A is enabled
}

// Check toggle/checkbox as boolean
if (is_setting_on('my_plugin_settings', 'enable_feature')) {
    // Feature is on
}
```

## Accessing the Settings Object

```php
use function ArrayPress\WP\Register\SettingFields\Utilities\get_setting_fields;

$settings = get_setting_fields('my_plugin_settings');

// Get a value
$value = $settings->get_value('api_key', 'default');

// Get all values
$values = $settings->get_values();

// Reset to defaults
$settings->reset_to_defaults(); // All fields
$settings->reset_to_defaults('general'); // Just one tab

// Delete all settings
$settings->delete_settings();
```

## Hooks and Filters

### Sanitization

```php
// Before sanitization
add_filter('setting_fields_pre_sanitize_value', function($value, $field, $key) {
    // Modify value before sanitization
    return $value;
}, 10, 3);

// After sanitization
add_filter('setting_fields_sanitize_value', function($sanitized, $value, $field, $key) {
    // Modify sanitized value
    return $sanitized;
}, 10, 4);

// All settings before save
add_filter('setting_fields_sanitize_settings', function($sanitized, $input, $old_value, $id) {
    // Modify all settings before save
    return $sanitized;
}, 10, 4);
```

### Custom Field Rendering

```php
add_filter('setting_fields_render_field', function($rendered, $key, $field, $name, $id, $value) {
    if ($field['type'] === 'my_custom_type') {
        echo '<input type="text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
        return true; // Mark as rendered
    }
    return $rendered;
}, 10, 6);
```

## Requirements

- PHP 8.0+
- WordPress 5.0+

## License

GPL-2.0-or-later
