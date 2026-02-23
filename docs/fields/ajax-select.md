# AJAX Select

Searchable dropdown powered by Select2 and a server-side callback. Supports single select, multi-select, and free-text tags.

The callback handles both searching (user types) and hydration (reloading saved values):

```php
function ( string $search = '', ?array $ids = null ): array
```

- When `$search` is provided: return matching results
- When `$ids` is provided: return labels for those specific IDs
- Return format: `[ { value, label }, ... ]`

## Single Select

```php
'customer_id' => [
    'type'        => 'ajax',
    'label'       => 'Customer',
    'placeholder' => 'Search customers...',
    'multiple'    => false,
    'allow_clear' => true,
    'minimum_input_length' => 2,             // Min chars before searching
    'ajax_callback' => function ( string $search = '', ?array $ids = null ): array {
        $args = [ 'number' => 20 ];
        if ( $ids ) {
            $args['include'] = array_map( 'absint', $ids );
        } else {
            $args['search'] = '*' . $search . '*';
        }
        $results = [];
        foreach ( get_users( $args ) as $user ) {
            $results[] = [
                'value' => $user->ID,
                'label' => $user->display_name,
            ];
        }
        return $results;
    },
],
```

## Multi-Select

```php
'related_posts' => [
    'type'        => 'ajax',
    'label'       => 'Related Posts',
    'placeholder' => 'Search posts...',
    'multiple'    => true,
    'ajax_callback' => function ( string $search = '', ?array $ids = null ): array {
        $args = [ 'post_type' => 'post', 'posts_per_page' => 20 ];
        if ( $ids ) {
            $args['post__in'] = array_map( 'absint', $ids );
        } else {
            $args['s'] = $search;
        }
        $results = [];
        foreach ( get_posts( $args ) as $post ) {
            $results[] = [
                'value' => $post->ID,
                'label' => $post->post_title,
            ];
        }
        return $results;
    },
],
```

The library automatically registers a REST API endpoint, generates nonces, initializes Select2, and handles hydration on page load. You only provide the callback.

**Saved data:** Single select saves a string. Multi-select saves an array of strings.
