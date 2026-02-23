# Post, Taxonomy & User

Shortcut field types that automatically configure Select2 with AJAX search and built-in REST endpoints. No callbacks needed.

## Post Select

```php
'related_post' => [
    'type'        => 'post',
    'label'       => 'Related Post',
    'placeholder' => 'Search posts...',
    'post_type'   => 'post',                 // String or array of post types
    'multiple'    => false,
],

// Multi-select with multiple post types
'linked_content' => [
    'type'        => 'post',
    'label'       => 'Linked Content',
    'multiple'    => true,
    'post_type'   => [ 'post', 'page', 'product' ],
],
```

## Page Select

```php
'checkout_page' => [
    'type'        => 'page',
    'label'       => 'Checkout Page',
    'placeholder' => 'Search pages...',
    'multiple'    => false,
],
```

The `page` type is a convenience wrapper for `post` with `post_type` defaulted to `'page'`.

## Taxonomy Select

```php
'category' => [
    'type'        => 'taxonomy',
    'label'       => 'Category',
    'placeholder' => 'Search categories...',
    'taxonomy'    => 'category',             // Any registered taxonomy
    'multiple'    => false,
],
```

## User Select

```php
'author' => [
    'type'        => 'user',
    'label'       => 'Author',
    'placeholder' => 'Search users...',
    'role'        => 'editor',               // String or array of roles, empty = all
    'multiple'    => false,
    'show_email'  => false,                  // Append email to display name
],
```

## Sanitization

All relational types validate that the saved IDs actually exist. Post types are validated against `post_type`, taxonomy terms against `taxonomy`, and users via `get_user_by()`. Invalid IDs are silently dropped.
