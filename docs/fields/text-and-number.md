# Text & Number Fields

## Text Fields

```php
'name' => [
    'type'         => 'text',
    'label'        => 'Name',
    'placeholder'  => 'Enter name...',
    'required'     => true,
    'size'         => 'regular',             // 'small', 'regular', 'large'
    'maxlength'    => 100,
    'minlength'    => 2,
    'pattern'      => '[A-Za-z]+',           // HTML5 pattern attribute
    'autocomplete' => 'name',
],

'email' => [
    'type'        => 'email',
    'label'       => 'Email Address',
    'placeholder' => 'user@example.com',
],

'website' => [
    'type'        => 'url',
    'label'       => 'Website',
    'placeholder' => 'https://',
],

'phone' => [
    'type'  => 'tel',
    'label' => 'Phone Number',
],

'api_key' => [
    'type'  => 'password',
    'label' => 'API Key',
],
```

## Number

```php
'quantity' => [
    'type'   => 'number',
    'label'  => 'Quantity',
    'min'    => 0,
    'max'    => 100,
    'step'   => 1,
    'suffix' => 'items',                     // Text after the input
],
```

When `step` contains a decimal (e.g. `0.01`), the value is sanitized as a float. Otherwise it's sanitized as an integer.

## Range Slider

```php
'opacity' => [
    'type'       => 'range',
    'label'      => 'Opacity',
    'min'        => 0,
    'max'        => 100,
    'step'       => 5,
    'show_value' => true,                    // Show current value next to slider
    'suffix'     => '%',
],
```

## Textarea

```php
'description' => [
    'type'        => 'textarea',
    'label'       => 'Description',
    'rows'        => 5,
    'cols'        => 50,
    'maxlength'   => 500,
    'placeholder' => 'Enter description...',
],
```

## WYSIWYG Editor

```php
'content' => [
    'type'            => 'wysiwyg',
    'label'           => 'Content',
    'rows'            => 10,
    'media_buttons'   => true,
    'teeny'           => false,
    'quicktags'       => true,
    'wpautop'         => true,
    'default_editor'  => '',                 // 'tinymce' or 'html'
    'drag_drop_upload' => false,
],
```

Sanitized with `wp_kses_post()`.

## Code Editor

```php
'custom_css' => [
    'type'     => 'code',
    'label'    => 'Custom CSS',
    'language' => 'css',                     // html, css, javascript, json, php, xml, sql, markdown
    'rows'     => 10,
],
```

Uses the WordPress built-in CodeMirror editor. The value is stored raw (not sanitized) to preserve code formatting.
