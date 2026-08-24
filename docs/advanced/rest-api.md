# REST API

The interactive field types talk to `wp-field-kit`'s own endpoints. This library registers no routes of its own, and the field set boots the controllers itself — a field emits an endpoint URL whether or not anyone remembered to register the route, and a button posting to a 404 looks exactly like a button that does nothing.

## Endpoints

| Method | Route            | Used By                                              | Description                          |
|--------|------------------|------------------------------------------------------|--------------------------------------|
| GET    | `/search`        | `ajax`, `post`, `page`, `taxonomy`, `user`           | Search a registered source           |
| GET    | `/labels`        | the same types                                       | Resolve stored ids back to labels    |
| POST   | `/action`        | `action_button`, `license`, `email_editor`            | Run a registered handler             |

## The Namespace Is Derived, Not Fixed

The namespace is `{prefix}/v1`, where the prefix comes from the root segment of the kit's own namespace. On a plain Composer install that is `field-kit/v1`. In a Strauss-prefixed build it becomes `{your-prefix}-field-kit/v1`.

This is not cosmetic. `WP_REST_Server::register_route()` merges same-path registrations onto one handler list and dispatches the first whose methods match, so two plugins shipping the same library under one namespace means whichever registered first answers the other's requests — under its own capability check, against its own registry. Deriving the namespace from the one thing Strauss rewrites is what keeps them apart.

The practical consequence: **do not hardcode the route in your own JavaScript.** Read it from the field's own markup, which carries the resolved URL.

## Authentication

There is no single blanket capability. Each registered search source and each registered action declares its own, checked per request:

- A callback-backed search source defaults to `edit_posts`; set `search_capability` on the field to change it.
- An action defaults to `manage_options`; set `action_capability` on the field to change it.

```php
'export_users' => [
    'type'               => 'action_button',
    'label'              => 'Export users',
    'action_callback'    => 'my_plugin_export_users',
    'action_capability'  => 'list_users',
],

'pick_a_thing' => [
    'type'              => 'ajax',
    'label'             => 'Thing',
    'search_callback'   => 'my_plugin_search_things',
    'search_capability' => 'edit_posts',
],
```

The built-in relational sources (post, page, user, taxonomy) carry the capability appropriate to what they search rather than taking one from config.

## Reset, Export and Import Are Not REST

They post to `admin-post.php` as forms with a nonce. They change state, and a link that changes state can be followed by a prefetch or a crawler. See [Reset & Export/Import](../reset-export-import.md).
