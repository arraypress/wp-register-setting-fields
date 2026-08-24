# Reset & Export/Import

These three live in the **Screen Options** panel, which opens next to Help. They are a screen's occasional controls, not something to read past on every visit — and Screen Options is a panel WordPress already provides rather than a row of buttons across the page title.

Each is a form posting to `admin-post.php` with a nonce, not a link. They change state, and a link that changes state can be followed by a prefetch or a crawler.

## Reset

Restores every setting on the tab being viewed to its default.

```php
register_setting_fields( 'my_plugin', [
    'reset_button' => true,
    'tabs'         => [
        'general'  => 'General',
        'advanced' => 'Advanced',
    ],
    'fields'       => [
        'site_name' => [
            'type'    => 'text',
            'label'   => 'Site Name',
            'default' => 'My Site',
            'tab'     => 'general',
        ],
        'cache_ttl' => [
            'type'    => 'number',
            'label'   => 'Cache TTL',
            'default' => 3600,
            'tab'     => 'advanced',
        ],
    ],
] );
```

When viewing the General tab, resetting restores only `site_name` to `'My Site'`. The Advanced tab's `cache_ttl` is untouched.

A reset removes the stored keys rather than writing defaults over them: a field with no stored value renders its configured default, so removing the key *is* the reset. A confirmation is shown first.

## Export/Import

Adds an Export/Import panel below the settings form. Export downloads a JSON file; import reads one back.

```php
register_setting_fields( 'my_plugin', [
    'export_import' => true,
    'fields'        => [ /* ... */ ],
] );
```

### Export

Clicking **Export Settings** downloads a JSON file named `{settings_id}-settings-{date}.json`:

```json
{
    "id": "my_plugin",
    "version": 1,
    "values": {
        "site_name": "My Site",
        "cache_ttl": 3600
    }
}
```

**Encrypted fields are left out entirely.** The key is derived from the site's own salts, so an encrypted value cannot be read anywhere else — exporting it would put a credential in a file for no benefit at all. See [Encryption](advanced/encryption.md).

### Import

Core's own import form — a `.wp-upload-form` with a visible label, the upload limit beside it and a plain file input, the same shape `wp_import_upload_form()` produces on `import.php`.

On upload:

1. The `id` in the file is checked against this settings page, and a file from another page is refused by name.
2. The values are handed to `update_option()`, which means they pass through this page's registered sanitize callback like every other write — each value sanitized by its own field type.
3. A key that is not a field on this page is dropped there rather than becoming part of the option. An uploaded file is untrusted input, and this is where that is enforced.

### Both Together

```php
register_setting_fields( 'my_plugin', [
    'reset_button'  => true,
    'export_import' => true,
    'fields'        => [ /* ... */ ],
] );
```

### Not REST

These are forms posting to `admin-post.php`, not REST routes. An earlier version registered `/reset`, `/export` and `/import` endpoints; a state change driven from a page that already has a form and a nonce does not need one.
