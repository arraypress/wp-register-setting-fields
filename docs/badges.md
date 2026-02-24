# Badges

Display upgrade or tier indicators on individual fields or entire sections. Badges render as small inline pills next to labels and section titles. Combine with `disabled` to create a locked preview state for premium features.

## Field Badge

Add a `badge` key to any field config:

```php
'advanced_sync' => [
    'type'     => 'toggle',
    'label'    => 'Advanced Sync',
    'badge'    => 'Pro',
    'disabled' => true,
],
```

The badge appears inline after the field label, between the required asterisk and the tooltip icon.

### Full Config

```php
'webhook_mode' => [
    'type'  => 'select',
    'label' => 'Webhook Mode',
    'badge' => [
        'text'  => 'Business',
        'url'   => 'https://example.com/upgrade',
        'class' => 'setting-fields-badge--gold',
        'icon'  => 'dashicons-lock',
    ],
    'disabled' => true,
],
```

When `url` is set, the badge renders as a link that opens in a new tab — useful for pointing users to an upgrade page.

## Section Badge

Add a `badge` to a section definition. When combined with `disabled`, all fields in the section are automatically disabled:

```php
'sections' => [
    'advanced' => [
        'title'       => 'Advanced Settings',
        'description' => 'These features require a Pro license.',
        'tab'         => 'general',
        'badge'       => [
            'text' => 'Pro',
            'url'  => 'https://example.com/upgrade',
        ],
        'disabled' => true,
    ],
],
```

The section title and badge remain fully visible and clickable (for upgrade links) even when the section's fields are dimmed and disabled.

## Badge Options

| Key     | Type   | Required | Default | Description                                    |
|---------|--------|----------|---------|------------------------------------------------|
| `text`  | string | Yes      | —       | Badge label                                    |
| `url`   | string | No       | `''`    | Links badge to upgrade page (opens new tab)    |
| `class` | string | No       | `''`    | Additional CSS class for styling               |
| `icon`  | string | No       | `''`    | Dashicon class (e.g., `dashicons-lock`)        |

If `badge` is a string (e.g., `'Pro'`), it's treated as `['text' => 'Pro']`.

## Built-in Color Variants

Use the `class` key to apply a color variant:

| Class                            | Colors         |
|----------------------------------|----------------|
| *(default — no class)*           | Neutral grey   |
| `setting-fields-badge--pro`      | Amber/orange   |
| `setting-fields-badge--premium`  | Purple         |
| `setting-fields-badge--business` | Green          |
| `setting-fields-badge--gold`     | Gold/yellow    |

```php
'badge' => [
    'text'  => 'Premium',
    'class' => 'setting-fields-badge--premium',
    'url'   => 'https://example.com/upgrade',
],
```

Linked badges change to a solid color on hover.

## Disabled Cascade

When a section has `'disabled' => true`, all child fields inherit the disabled state automatically. You don't need to set `disabled` on each field individually. The section's form table is dimmed with reduced opacity while the section header remains fully interactive.

## Dynamic Badges

You can conditionally add badges based on license status or feature flags:

```php
$is_pro = is_setting_field_license_active( 'my_plugin', 'license' );

'advanced_sync' => [
    'type'     => 'toggle',
    'label'    => 'Advanced Sync',
    'badge'    => $is_pro ? '' : [
        'text' => 'Pro',
        'url'  => 'https://example.com/upgrade',
    ],
    'disabled' => ! $is_pro,
],
```

When the license is active, the badge disappears and the field becomes editable.
