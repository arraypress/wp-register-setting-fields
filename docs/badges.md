# Badges

Display upgrade or tier indicators on individual fields or entire sections. Badges render as small inline pills next to
labels and section titles. When a badge is visible, the associated field is automatically disabled — creating a locked
preview state for premium features.

## Field Badge

Add a `badge` key to any field config:

```php
'advanced_sync' => [
    'type'  => 'toggle',
    'label' => 'Advanced Sync',
    'badge' => 'Pro',
],
```

The badge appears inline after the field label, between the required asterisk and the tooltip icon. When the badge is
visible, the field is automatically disabled — no need to set `disabled` separately.

### Full Config

```php
'webhook_mode' => [
    'type'  => 'select',
    'label' => 'Webhook Mode',
    'badge' => [
        'text'     => 'Business',
        'url'      => 'https://example.com/upgrade',
        'class'    => 'my-badge--gold',
        'icon'     => 'lock',
        'disabled' => fn() => is_setting_field_license_active( 'my_plugin', 'license' ),
    ],
],
```

When `url` is set, the badge renders as a link that opens in a new tab — useful for pointing users to an upgrade page.

## Section Badge

Add a `badge` to a section definition. When the badge is visible, all fields in the section are automatically disabled:

```php
'sections' => [
    'advanced' => [
        'title'       => 'Advanced Settings',
        'description' => 'These features require a Pro license.',
        'tab'         => 'general',
        'badge'       => [
            'text'     => 'Pro',
            'url'      => 'https://example.com/upgrade',
            'disabled' => fn() => is_setting_field_license_active( 'my_plugin', 'license' ),
        ],
    ],
],
```

The section title and badge stay visible and clickable — that is the point of an upgrade link — while the fields under them are disabled.

## A locked field keeps its value

A disabled control sends nothing, and a save path that iterates its field list rather than the submission reads "nothing" as "cleared". So a locked field is **skipped on save entirely**.

Without that, an install whose licence lapsed would have its premium settings wiped by the next unrelated save, and get them back as blanks when the licence returned. The same applies to a field carrying `'disabled' => true` directly.

## The condition reads backwards on purpose

`disabled` **hides** the badge. It answers "does this install already have the feature?" — true means yes, so there is nothing to sell and nothing to lock:

```php
'disabled' => fn() => is_setting_field_license_active( 'my_plugin', 'license' ),
```

A callable is accepted so the answer can be a licence check made at render time rather than at registration.

## Badge Options

| Key        | Type           | Required | Default | Description                                 |
|------------|----------------|----------|---------|---------------------------------------------|
| `text`     | string         | Yes      | —       | Badge label                                 |
| `url`      | string         | No       | `''`    | Links badge to upgrade page (opens new tab) |
| `class`    | string         | No       | `''`    | Additional CSS class for styling            |
| `icon`     | string         | No       | `''`    | Dashicon **suffix** — `lock`, not `dashicons-lock` |
| `disabled` | bool\|callable | No       | `false` | When truthy, hides badge and unlocks field  |

If `badge` is a string (e.g., `'Pro'`), it's treated as `['text' => 'Pro']`.

## The `disabled` Key

The `disabled` key on a badge controls both the badge visibility and the field's disabled state in a single declaration.
This avoids the need to conditionally build the field array:

- When `disabled` is **falsy or absent** — badge is visible, field is disabled
- When `disabled` is **truthy** — badge is hidden, field is editable
- When `disabled` is a **callable** — it's called at render time and the return value is used as a bool

```php
'badge' => [
    'text'     => 'Pro',
    'disabled' => fn() => has_pro_license(),
],
```

This is equivalent to writing:

```php
'badge'    => has_pro_license() ? '' : 'Pro',
'disabled' => ! has_pro_license(),
```

But the `disabled` key keeps the config static and declarative — the array never needs to be built conditionally.

## Built-in Color Variants

Use the `class` key to apply a color variant:

| Class                            | Colors       |
|----------------------------------|--------------|
| *(default — no class)*           | Neutral grey |
| `setting-fields-badge--pro`      | Amber/orange |
| `setting-fields-badge--premium`  | Purple       |
| `setting-fields-badge--business` | Green        |
| `setting-fields-badge--gold`     | Gold/yellow  |

```php
'badge' => [
    'text'  => 'Premium',
    'class' => 'setting-fields-badge--premium',
    'url'   => 'https://example.com/upgrade',
],
```

Linked badges change to a solid color on hover.

## Disabled Cascade

When a section has an active badge, all child fields inherit the disabled state automatically. You don't need to set
`disabled` on each field individually. The section's form table is dimmed with reduced opacity while the section header
remains fully interactive.

## Example: License-Gated Features

A common pattern for freemium plugins — fields are locked with a badge until the user activates a license:

```php
$license_check = fn() => is_setting_field_license_active( 'my_plugin', 'license' );

'fields' => [
    'basic_feature' => [
        'type'  => 'toggle',
        'label' => 'Basic Feature',
    ],
    'advanced_sync' => [
        'type'  => 'toggle',
        'label' => 'Advanced Sync',
        'badge' => [
            'text'     => 'Pro',
            'url'      => 'https://example.com/upgrade',
            'class'    => 'setting-fields-badge--pro',
            'icon'     => 'lock',
            'disabled' => $license_check,
        ],
    ],
    'webhook_mode' => [
        'type'    => 'select',
        'label'   => 'Webhook Mode',
        'options' => [ 'basic' => 'Basic', 'advanced' => 'Advanced' ],
        'badge'   => [
            'text'     => 'Pro',
            'url'      => 'https://example.com/upgrade',
            'class'    => 'setting-fields-badge--pro',
            'icon'     => 'lock',
            'disabled' => $license_check,
        ],
    ],
],
```

When the license is active, badges disappear and fields become editable — all from a static config array.