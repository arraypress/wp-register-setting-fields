# Conditional Fields

Show/hide fields based on other field values. Fields with dependencies start hidden and are shown/hidden by JavaScript based on the current values of their dependent fields. All conditions use AND logic — every condition must be met for the field to show.

## Simple Key → Value

The most common format. Pass one or more field/value pairs — all must match.

```php
// Single condition
'notification_email' => [
    'type'    => 'email',
    'label'   => 'Notification Email',
    'depends' => [
        'enable_notifications' => 1,
    ],
],

// Multiple conditions (AND logic)
'discount_percent' => [
    'type'  => 'number',
    'label' => 'Discount %',
    'depends' => [
        'enable_discounts' => 1,
        'discount_type'    => 'percentage',
    ],
],
```

## Value Match with Array (IN)

When the value is an array, the field shows if the current value matches any of the provided values.

```php
'priority_note' => [
    'type'    => 'message',
    'content' => 'Priority support is included with your plan.',
    'depends' => [
        'license_type' => [ 'business', 'enterprise' ],
    ],
],
```

## Single Condition with Operator

For comparisons beyond simple equality, use the explicit format with an operator.

```php
'free_shipping_notice' => [
    'type'    => 'message',
    'content' => 'Free shipping applies.',
    'depends' => [
        'field'    => 'order_total',
        'value'    => 50,
        'operator' => '>=',
    ],
],
```

## Multiple Conditions with Operators (AND)

Pass an array of condition arrays — all must be met.

```php
'refund_message' => [
    'type'  => 'textarea',
    'label' => 'Refund Message',
    'depends' => [
        [
            'field'    => 'enable_refunds',
            'value'    => 1,
            'operator' => '=',
        ],
        [
            'field'    => 'order_total',
            'value'    => 100,
            'operator' => '>=',
        ],
        [
            'field'    => 'order_status',
            'value'    => [ 'completed', 'processing' ],
            'operator' => 'in',
        ],
    ],
],
```

## Empty / Not Empty

```php
'setup_prompt' => [
    'type'    => 'message',
    'content' => 'Please configure your API key above.',
    'depends' => [
        'field'    => 'api_key',
        'value'    => '',
        'operator' => 'empty',
    ],
],
```

## Available Operators

| Operator       | Description                                                        |
|----------------|--------------------------------------------------------------------|
| `=` / `==`     | Loose equality                                                     |
| `===`          | Strict equality                                                    |
| `!=` / `!==`   | Not equal                                                          |
| `>`            | Greater than                                                       |
| `>=`           | Greater than or equal                                              |
| `<`            | Less than                                                          |
| `<=`           | Less than or equal                                                 |
| `in`           | Value matches any in array                                         |
| `not_in`       | Value matches none in array                                        |
| `contains`     | Array contains value, or string contains substring                 |
| `not_contains` | Array does not contain value, or string does not contain substring |
| `empty`        | Value is empty, blank, or zero                                     |
| `not_empty`    | Value is not empty                                                 |

## Section Auto-Hide

When all fields inside a section are hidden by conditional logic, the entire section container (title, description, and table) is automatically hidden by JavaScript.
