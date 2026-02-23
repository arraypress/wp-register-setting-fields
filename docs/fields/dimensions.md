# Dimensions

Four-sided numeric input (top, right, bottom, left) with unit selector. Useful for padding, margin, border-radius, and similar CSS properties.

```php
'padding' => [
    'type'         => 'dimensions',
    'label'        => 'Padding',
    'units'        => [ 'px', 'em', 'rem', '%' ],
    'default_unit' => 'px',
    'sides'        => [ 'top', 'right', 'bottom', 'left' ],  // Which sides to show
    'linked'       => false,                 // Link all values together
    'step'         => 1,
    'min'          => 0,
    'max'          => 200,
],
```

## Behavior

When `units` has multiple entries, a dropdown selector is rendered. With a single unit, a static label is shown. When `linked` is `true`, a link icon button appears — clicking it syncs all four inputs to the same value.

## Saved Data Shape

```php
[
    'padding' => [
        'top'    => 10,
        'right'  => 20,
        'bottom' => 10,
        'left'   => 20,
        'unit'   => 'px',
    ],
]
```
