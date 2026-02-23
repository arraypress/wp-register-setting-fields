# Media Fields

## Image

Single image picker via the WordPress media library. Stores the attachment ID.

```php
'logo' => [
    'type'          => 'image',
    'label'         => 'Logo',
    'preview_size'  => 'thumbnail',          // WordPress image size for preview
    'library'       => 'image',              // Media library filter
    'return_format' => 'id',                 // Always returns attachment ID
],
```

Renders a preview thumbnail with Select, Change, and Remove buttons.

## File

Single file picker via the WordPress media library. Stores the attachment ID.

```php
'download' => [
    'type'          => 'file',
    'label'         => 'Download File',
    'library'       => 'all',                // 'all', 'image', 'video', 'audio'
    'allowed_types' => 'pdf,zip,doc',        // Comma-separated MIME extensions
],
```

Renders a file icon with the filename as a link, plus Change and Remove buttons.

## Gallery

Multiple image picker via the WordPress media library. Stores an array of attachment IDs. Supports drag-and-drop reordering.

```php
'photos' => [
    'type'         => 'gallery',
    'label'        => 'Photos',
    'preview_size' => 'thumbnail',           // WordPress image size for previews
    'min'          => 0,                     // Minimum images required
    'max'          => 20,                    // Maximum images allowed (0 = unlimited)
],
```

Renders a grid of thumbnails with remove buttons on each image and an "Add Images" button. Sanitized to ensure all IDs are valid image attachments.

## oEmbed

URL input with automatic embed preview. Useful for YouTube, Vimeo, Twitter, and other oEmbed-supported URLs.

```php
'video' => [
    'type'  => 'oembed',
    'label' => 'Video URL',
],
```

The preview updates when the URL changes, using `wp_oembed_get()` to generate the embed HTML.
