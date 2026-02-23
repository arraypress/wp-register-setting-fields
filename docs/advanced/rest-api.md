# REST API

The library automatically registers REST API routes when fields require them. Routes are registered under the `setting-fields/v1` namespace.

## Endpoints

| Method | Route                              | Used By            | Description                  |
|--------|------------------------------------|--------------------|------------------------------|
| GET    | `/setting-fields/v1/ajax`          | `ajax`, `post`, `page`, `taxonomy`, `user` | Search and hydration |
| POST   | `/setting-fields/v1/action`        | `action_button`    | Execute server-side callback |
| POST   | `/setting-fields/v1/license`       | `license`          | Activate/deactivate license  |
| POST   | `/setting-fields/v1/email/preview` | `email_editor`     | Generate email preview HTML  |
| POST   | `/setting-fields/v1/email/send-test` | `email_editor`   | Send test email              |
| POST   | `/setting-fields/v1/reset`         | `reset_button`     | Reset fields to defaults     |
| GET    | `/setting-fields/v1/export`        | `export_import`    | Export settings as JSON      |
| POST   | `/setting-fields/v1/import`        | `export_import`    | Import settings from JSON    |

## Authentication

All endpoints require the `manage_options` capability by default. Override with:

```php
add_filter( 'setting_fields_rest_capability', function () {
    return 'edit_posts';
} );
```

## Reset Endpoint

```
POST /setting-fields/v1/reset
```

| Parameter     | Type   | Required | Description                                    |
|---------------|--------|----------|------------------------------------------------|
| `settings_id` | string | Yes      | The registered settings ID                     |
| `tab`         | string | No       | Tab key to scope the reset (empty = all fields)|

Returns the number of fields reset. Fields with no stored data (layout types, action buttons, clipboard) are skipped.

## Export Endpoint

```
GET /setting-fields/v1/export?settings_id=my_plugin
```

Returns a JSON object containing all exportable field values with metadata. Encrypted fields are returned decrypted. Layout fields, constant-sourced fields, and non-data fields are excluded.

## Import Endpoint

```
POST /setting-fields/v1/import
Content-Type: application/json
```

```json
{
    "settings_id": "my_plugin",
    "data": {
        "settings_id": "my_plugin",
        "version": 1,
        "exported_at": "2025-06-15 10:30:00",
        "data": {
            "site_name": "My Site",
            "cache_ttl": 3600
        }
    }
}
```

The `settings_id` in the payload is validated against the target. Each value is sanitized through the standard field sanitizer before saving. Values are merged with existing options.

## Nested Field Paths

For fields inside groups, the REST API uses dot notation for `field_key` parameters (e.g. `stripe.secret_key`). Dot-separated keys are sanitized segment by segment.

## Auto-Registration

REST routes are only registered if the settings page contains field types that need them (`ajax`, `post`, `page`, `taxonomy`, `user`, `email_editor`, `action_button`, `license`) or has `reset_button` or `export_import` enabled. Sub-fields inside groups and repeaters are checked too.
