# Sections

Group fields within a tab into titled sections with optional descriptions. Each section wraps its fields in a separate `form-table` with a heading.

```php
register_setting_fields( 'my_plugin', [
    'tabs' => [
        'general' => 'General',
    ],

    'sections' => [
        'branding' => [
            'title'       => 'Branding',
            'description' => 'Configure your brand identity.',
            'tab'         => 'general',
        ],
        'behavior' => [
            'title' => 'Behavior',
            'tab'   => 'general',
        ],
    ],

    'fields' => [
        'logo' => [
            'type'    => 'image',
            'label'   => 'Logo',
            'tab'     => 'general',
            'section' => 'branding',
        ],
        'brand_color' => [
            'type'    => 'color',
            'label'   => 'Brand Color',
            'tab'     => 'general',
            'section' => 'branding',
        ],
        'auto_save' => [
            'type'    => 'toggle',
            'label'   => 'Auto Save',
            'tab'     => 'general',
            'section' => 'behavior',
        ],
    ],
] );
```

## Section Options

| Key           | Type   | Description                         |
|---------------|--------|-------------------------------------|
| `title`       | string | Section heading                     |
| `description` | string | Text below the heading              |
| `tab`         | string | Tab this section belongs to         |

Fields without a `section` key are rendered after all sectioned fields in a default table. When all fields inside a section are hidden (via conditional logic), the entire section container is automatically hidden by JavaScript.
