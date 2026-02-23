# Sortable

Drag-and-drop reorderable list with enable/disable toggles per item.

```php
'column_order' => [
    'type'    => 'sortable',
    'label'   => 'Column Order',
    'options' => [
        'title'    => 'Title',
        'author'   => 'Author',
        'date'     => 'Date',
        'category' => 'Category',
        'status'   => 'Status',
    ],
],
```

## Behavior

Each item has a drag handle and a visibility toggle (eye icon). Disabled items are grayed out and their hidden inputs are removed from the form, so only enabled items appear in the saved array.

The saved order combines both position and selection — items appear in the array in their drag-and-drop order, and only enabled items are included.

## Saved Data Shape

```php
// If user reordered and disabled 'category':
[ 'title', 'date', 'author', 'status' ]
```
