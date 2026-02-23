# Link

Composite field with URL, link text, and "open in new tab" checkbox.

```php
'cta_link' => [
    'type'  => 'link',
    'label' => 'Call to Action',
],
```

## Saved Data Shape

```php
[
    'cta_link' => [
        'url'    => 'https://example.com',
        'text'   => 'Learn More',
        'target' => '_blank',                // '_blank' or '_self'
    ],
]
```
