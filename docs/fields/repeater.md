# Repeater

Dynamic repeatable rows of sub-fields. Users can add, remove, and reorder rows.

```php
'team_members' => [
    'type'         => 'repeater',
    'label'        => 'Team Members',
    'layout'       => 'table',               // 'table', 'block', 'row'
    'min'          => 0,                     // Minimum rows
    'max'          => 10,                    // Maximum rows (0 = unlimited)
    'sortable'     => true,                  // Enable drag-and-drop reorder
    'collapsed'    => false,                 // Start rows collapsed (block/row only)
    'button_label' => 'Add Member',
    'max_width'    => '',                    // e.g. '600px', '80%'
    'sub_fields'   => [
        'name' => [
            'type'  => 'text',
            'label' => 'Name',
        ],
        'email' => [
            'type'  => 'email',
            'label' => 'Email',
        ],
        'role' => [
            'type'    => 'select',
            'label'   => 'Role',
            'options' => [
                'admin'  => 'Admin',
                'editor' => 'Editor',
                'viewer' => 'Viewer',
            ],
        ],
    ],
],
```

## Layouts

- **table** — compact table with column headers, one row per entry
- **block** — each row as a card with sub-fields stacked vertically
- **row** — each row as a card with sub-fields side-by-side

In block/row layouts, rows can be collapsed when `collapsed` is `true`, showing a "Row N" title bar with a toggle.

## Max Width

Use `max_width` to constrain the repeater width. Accepts any CSS value (`'600px'`, `'80%'`, `'50em'`). A plain number is treated as pixels.

## Saved Data Shape

```php
[
    'team_members' => [
        [ 'name' => 'John', 'email' => 'john@example.com', 'role' => 'admin' ],
        [ 'name' => 'Jane', 'email' => 'jane@example.com', 'role' => 'editor' ],
    ],
]
```

Each sub-field is sanitized individually using the same sanitizers as top-level fields. Encrypted sub-fields are supported.
