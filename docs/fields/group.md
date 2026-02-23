# Group

Group related fields together in a single row. Supports block, row, and table layouts with optional collapsible card wrapper.

```php
'address' => [
    'type'              => 'group',
    'label'             => 'Address',
    'layout'            => 'block',          // 'block', 'row', 'table'
    'collapsible'       => false,
    'collapsed'         => false,            // Start collapsed
    'title'             => 'Address',        // Collapsible card title
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
],
```

## Layouts

- **block** — each sub-field on its own line with label above
- **row** — sub-fields displayed side-by-side
- **table** — sub-fields in a `form-table` layout (label/value columns)

## Collapsible

When `collapsible` is `true`, the group renders inside a card with a toggle button. Set `collapsed` to `true` to start closed.

## Nested Types

Sub-fields support all field types including encrypted fields, relational fields, and AJAX selects. Nested field paths use dot notation internally (e.g. `address.street`) for REST API routing.

## Saved Data Shape

```php
[
    'address' => [
        'street' => '123 Main St',
        'city'   => 'Springfield',
        'zip'    => '62701',
    ],
]
```
