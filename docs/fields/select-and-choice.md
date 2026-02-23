# Select & Choice Fields

## Select

```php
'status' => [
    'type'        => 'select',
    'label'       => 'Status',
    'placeholder' => 'Select status...',
    'options'     => [
        'draft'     => 'Draft',
        'published' => 'Published',
        'archived'  => 'Archived',
    ],
],
```

### Optgroups

```php
'country' => [
    'type'    => 'select',
    'label'   => 'Country',
    'options' => [
        'North America' => [
            'us' => 'United States',
            'ca' => 'Canada',
        ],
        'Europe' => [
            'gb' => 'United Kingdom',
            'de' => 'Germany',
        ],
    ],
],
```

## Select2 (Enhanced Multi-Select)

```php
'categories' => [
    'type'           => 'select2',
    'label'          => 'Categories',
    'multiple'       => false,               // Default: false; set true for multi-select
    'placeholder'    => 'Select categories...',
    'allow_clear'    => true,
    'max_selections' => 5,
    'tags'           => false,               // Allow free-text entries
    'options'        => [
        'electronics' => 'Electronics',
        'clothing'    => 'Clothing',
        'home'        => 'Home & Garden',
    ],
],
```

`select_multiple` is an alias for `select2`.

## Toggle (Switch)

```php
'enabled' => [
    'type'           => 'toggle',
    'label'          => 'Enable Feature',
    'checkbox_label' => 'Turn this on',      // Text next to the switch
],
```

Renders as a styled switch. Sanitized to boolean (`true`/`false`).

## Checkbox

```php
'agree' => [
    'type'           => 'checkbox',
    'label'          => 'Terms',
    'checkbox_label' => 'I agree to the terms',
],
```

## Checkbox Group

```php
'features' => [
    'type'    => 'checkbox_group',
    'label'   => 'Features',
    'layout'  => 'vertical',                 // 'vertical' or 'horizontal'
    'options' => [
        'api'       => 'API Access',
        'webhooks'  => 'Webhooks',
        'analytics' => 'Analytics',
    ],
],
```

Saves an array of selected values.

## Radio Buttons

```php
'shipping' => [
    'type'    => 'radio',
    'label'   => 'Shipping Method',
    'layout'  => 'vertical',                 // 'vertical' or 'horizontal'
    'options' => [
        'standard'  => 'Standard (5-7 days)',
        'express'   => 'Express (2-3 days)',
        'overnight' => 'Overnight',
    ],
],
```

## Button Group

Styled radio buttons that look like a segmented control:

```php
'view_mode' => [
    'type'    => 'button_group',
    'label'   => 'View Mode',
    'options' => [
        'grid' => 'Grid',
        'list' => 'List',
        'map'  => 'Map',
    ],
],
```
