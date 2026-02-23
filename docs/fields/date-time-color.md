# Date, Time & Color

## Date

```php
'start_date' => [
    'type'  => 'date',
    'label' => 'Start Date',
    'min'   => '2024-01-01',
    'max'   => '2030-12-31',
],
```

Uses the native HTML5 `<input type="date">`. Sanitized to `Y-m-d` format.

## Time

```php
'opening_time' => [
    'type'  => 'time',
    'label' => 'Opening Time',
    'min'   => '08:00',
    'max'   => '22:00',
    'step'  => 900,                          // 15-minute increments (in seconds)
],
```

Uses native `<input type="time">`. Sanitized to `HH:MM` or `HH:MM:SS` format.

## Datetime

```php
'event_start' => [
    'type'  => 'datetime',
    'label' => 'Event Start',
    'min'   => '2024-01-01T00:00',
    'max'   => '2030-12-31T23:59',
],
```

Uses native `<input type="datetime-local">`. Sanitized to `Y-m-d\TH:i` format.

## Color Picker

```php
'brand_color' => [
    'type'     => 'color',
    'label'    => 'Brand Color',
    'default'  => '#3498db',
    'alpha'    => false,                     // Enable alpha (transparency) support
    'palettes' => [                          // Custom color swatches
        '#1abc9c', '#3498db', '#9b59b6',
        '#e74c3c', '#f39c12', '#2c3e50',
    ],
],
```

Uses the WordPress `wp-color-picker` library. Sanitized with `sanitize_hex_color()`.
