# Admin Columns

A PHP utility class for registering custom columns on WordPress post type list tables, with first-class ACF support. Handles display, sorting, filter dropdowns, and click-to-filter for seven field types — including composite (multi-field) columns and structured object fields.

---

## Contents

- [Requirements](#requirements)
- [Setup](#setup)
- [Quick start](#quick-start)
- [API reference](#api-reference)
  - [`__construct()`](#__construct)
  - [`add()`](#add)
  - [`remove()`](#remove)
  - [`register()`](#register)
- [Field types](#field-types)
  - [text](#text)
  - [image](#image)
  - [true\_false](#true_false)
  - [taxonomy](#taxonomy)
  - [date](#date)
  - [object](#object)
  - [composite](#composite)
- [Shared options](#shared-options)
- [Sorting](#sorting)
- [Filtering](#filtering)
- [Click-to-filter](#click-to-filter)
- [Default values reference](#default-values-reference)
- [ACF return format compatibility](#acf-return-format-compatibility)
- [Notes and gotchas](#notes-and-gotchas)

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0+ |
| WordPress | 5.0+ (4.2+ for composite multi-key sort) |
| Advanced Custom Fields | 5.x / 6.x (optional — falls back to `get_post_meta`) |

---

## Setup

Drop `class-admin-columns.php` into your theme or plugin and include it before use.

```php
require_once get_template_directory() . '/inc/class-admin-columns.php';
```

Instantiate and register on or after `admin_init` so that `get_current_screen()` is available.

```php
add_action( 'admin_init', function () {
    $cols = new Admin_Columns( 'film' );
    $cols->add( 'release_date', 'Released', 'date' );
    $cols->register();
} );
```

---

## Quick start

```php
$cols = new Admin_Columns( 'film' );

$cols->add( 'poster',        'Poster',    'image' );
$cols->add( 'subtitle',      'Subtitle',  'text',       [ 'filterable' => true, 'clickable' => true ] );
$cols->add( 'is_featured',   'Featured',  'true_false' );
$cols->add( 'release_date',  'Released',  'date',       [ 'filter_by' => 'year', 'clickable' => true ] );
$cols->add( 'genre',         'Genre',     'taxonomy',   [ 'taxonomy' => 'genre' ] );
$cols->add( 'director',      'Director',  'object',     [ 'sub_key' => 'post_title', 'link' => true, 'link_sub_key' => 'permalink' ] );

$cols->register();
```

Each call to `add()` is fluent — you can chain them:

```php
( new Admin_Columns( 'event' ) )
    ->add( 'hero_image',   'Hero',     'image' )
    ->add( 'event_date',   'Date',     'date' )
    ->add( 'ticket_price', 'Price',    'text',  [ 'sortable' => true ] )
    ->register();
```

---

## API reference

### `__construct()`

```php
new Admin_Columns( string $post_type )
```

| Parameter | Type | Description |
|---|---|---|
| `$post_type` | `string` | Post type slug — e.g. `'post'`, `'page'`, `'film'`. |

---

### `add()`

```php
->add(
    string|array $acf_key,
    string       $label,
    string       $type     = 'text',
    array        $options  = [],
    ?int         $position = null
): static
```

| Parameter | Type | Description |
|---|---|---|
| `$acf_key` | `string\|array` | ACF field name for single-field columns, **or** an array of field definitions for a [composite](#composite) column. When an array is passed, `$type` is automatically set to `'composite'`. |
| `$label` | `string` | Column header label shown in the list table. |
| `$type` | `string` | One of: `text`, `image`, `true_false`, `taxonomy`, `date`, `object`, `composite`. Defaults to `'text'`. |
| `$options` | `array` | Type-specific options — see [Field types](#field-types). User values are merged on top of per-type defaults. |
| `$position` | `int\|null` | Zero-based index at which to insert the column. `null` appends after existing columns. |

Throws `InvalidArgumentException` if an unsupported type is passed.

---

### `remove()`

```php
->remove( string|array $acf_key ): static
```

Removes a previously registered column. Pass the same `$acf_key` value that was used in `add()`.

---

### `register()`

```php
->register(): void
```

Attaches all WordPress hooks. **Call once**, after all `add()` and `remove()` calls. Safe to call with zero registered columns (no-op).

Registers the following hooks internally:

| WordPress hook | Purpose |
|---|---|
| `manage_{post_type}_posts_columns` | Injects column headers |
| `manage_{post_type}_posts_custom_column` | Renders cell content |
| `manage_edit-{post_type}_sortable_columns` | Declares sortable columns |
| `restrict_manage_posts` | Outputs filter dropdowns |
| `pre_get_posts` | Applies active sort and filter to the query |

---

## Field types

### `text`

Renders a plain text or textarea value. Strips HTML tags before output.

```php
$cols->add( 'subtitle', 'Subtitle', 'text', [
    'max_length' => 50,
    'filterable' => true,
    'clickable'  => true,
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `max_length` | `int` | `0` | Truncate display text after N characters and append `…`. `0` = no truncation. |
| `sortable` | `bool` | `true` | Column header is clickable to sort. |
| `filterable` | `bool` | `false` | Show a dropdown of unique values above the list table. |
| `clickable` | `bool` | `false` | Wrap cell value in a link that filters to that value. |

> **Filter behaviour:** The dropdown is populated by querying all distinct non-empty meta values for this field across all published posts of this type.

---

### `image`

Renders an ACF image field as a small thumbnail. Handles all three ACF image return formats: **array** (default), **ID**, and **URL**.

```php
$cols->add( 'hero_image', 'Hero', 'image', [
    'size'   => 'thumbnail',
    'width'  => 60,
    'height' => 80,
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `size` | `string` | `'thumbnail'` | WordPress image size slug. |
| `width` | `int` | `80` | Display width in pixels. |
| `height` | `int` | `80` | Display height in pixels. |
| `sortable` | `bool` | `false` | — |
| `filterable` | `bool` | `false` | — |
| `clickable` | `bool` | `false` | — |

---

### `true_false`

Renders an ACF true/false field as a coloured glyph (green ✔ / grey —). Sorting, filtering, and click-to-filter are all enabled by default.

```php
$cols->add( 'is_featured', 'Featured', 'true_false', [
    'true_label'         => '✔',
    'false_label'        => '—',
    'filter_true_label'  => 'Featured only',
    'filter_false_label' => 'Not featured',
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `true_label` | `string` | `'✔'` | Glyph shown when the value is true. |
| `false_label` | `string` | `'—'` | Glyph shown when the value is false. |
| `filter_true_label` | `string` | `'Yes'` | Label for the "true" option in the filter dropdown. |
| `filter_false_label` | `string` | `'No'` | Label for the "false" option in the filter dropdown. |
| `sortable` | `bool` | `true` | Numeric sort (`0` / `1`). |
| `filterable` | `bool` | `true` | Yes / No dropdown. |
| `clickable` | `bool` | `true` | Click glyph to filter by that state. |

---

### `taxonomy`

Renders taxonomy terms associated with a post. Accepts the value returned by an ACF taxonomy field (term objects or IDs) and falls back to `get_the_terms()` if ACF returns nothing.

```php
$cols->add( 'genre', 'Genre', 'taxonomy', [
    'taxonomy'  => 'genre',
    'separator' => ' / ',
    'link'      => true,
    'clickable' => true,
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `taxonomy` | `string` | `''` | **Required.** Taxonomy slug (e.g. `'genre'`, `'category'`). |
| `separator` | `string` | `', '` | Separator between multiple terms. |
| `link` | `bool` | `true` | When `clickable` is `false`, wrap each term in a native WordPress taxonomy filter URL. |
| `sortable` | `bool` | `false` | Taxonomy sort is not supported. |
| `filterable` | `bool` | `true` | Dropdown of all non-empty terms in the taxonomy. |
| `clickable` | `bool` | `true` | Each term links to a custom filtered list. |

> **`link` vs `clickable`:** When `clickable` is `true`, terms link to the class's own filter URL (using the custom `acf_f_*` query var). When `clickable` is `false` and `link` is `true`, terms link to WordPress's native taxonomy filter URL (e.g. `?post_type=film&genre=action`).

---

### `date`

Renders an ACF date picker field. Dates are stored as `Ymd` by default — this format sorts correctly as a CHAR string, so sorting requires no special casting.

```php
$cols->add( 'release_date', 'Released', 'date', [
    'display_format' => 'j M Y',
    'storage_format' => 'Ymd',
    'filter_by'      => 'year',
    'sortable'       => true,
    'filterable'     => true,
    'clickable'      => true,
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `display_format` | `string` | `'d/m/Y'` | PHP `date()` format string used for display. |
| `storage_format` | `string` | `'Ymd'` | PHP `date()` format string that matches what ACF stores in the database. Change this if your field uses `Y-m-d` or another format. |
| `filter_by` | `string` | `'year'` | Granularity of the filter dropdown: `'year'` or `'month'`. |
| `sortable` | `bool` | `true` | Sorts chronologically via `meta_value` CHAR comparison. |
| `filterable` | `bool` | `true` | Dropdown of unique years or year-months, newest first. |
| `clickable` | `bool` | `false` | Click a date value to filter by its year or month (per `filter_by`). |

---

### `object`

Renders any ACF field that returns structured data rather than a plain string. This covers:

| ACF field type | What ACF returns |
|---|---|
| Relationship | `WP_Post[]` |
| Post Object | `WP_Post` or `WP_Post[]` |
| User | `WP_User` or `WP_User[]` |
| Link | `['url', 'title', 'target']` |
| Google Map | `['address', 'lat', 'lng', ...]` |
| Group | Associative array of sub-fields |
| Repeater | Numerically-indexed array of row arrays |

```php
$cols->add( 'related_posts', 'Related', 'object', [
    'sub_key'      => 'post_title',
    'link'         => true,
    'link_sub_key' => 'edit_link',
    'max_items'    => 3,
    'separator'    => ' · ',
] );
```

| Option | Type | Default | Description |
|---|---|---|---|
| `sub_key` | `string\|callable` | `''` | **Required.** Dot-notation path into each item, or a callable that receives the item and returns a string. See [sub\_key resolution](#sub_key-resolution) below. |
| `separator` | `string` | `', '` | Glue between multiple resolved values. |
| `max_items` | `int` | `0` | Cap the number of items shown. `0` = no limit. Items beyond the cap show a muted `+N more` indicator. |
| `max_length` | `int` | `0` | Truncate each resolved string after N characters. `0` = no truncation. |
| `link` | `bool` | `false` | Wrap each item's text in a hyperlink. |
| `link_sub_key` | `string` | `''` | Dot-notation path to the URL, or a magic value. See [link\_sub\_key](#link_sub_key) below. |
| `sortable` | `bool` | `false` | Disabled by default — ACF stores raw values (e.g. post IDs) not display text. |
| `filterable` | `bool` | `false` | Disabled by default. |
| `clickable` | `bool` | `false` | — |

#### `sub_key` resolution

The `sub_key` is resolved against each item independently.

**Dot-notation string** — traverses nested objects and arrays using `.` as a separator:

```php
// WP_Post relationship field
'sub_key' => 'post_title'              // → $item->post_title

// Nested array (e.g. ACF group or custom structure)
'sub_key' => 'author.display_name'    // → $item['author']['display_name']

// ACF link field
'sub_key' => 'title'                  // → $item['title']

// ACF Google Map field
'sub_key' => 'address'                // → $item['address']
```

**Callable** — receives the raw item and returns a string. Use this when the display value requires logic:

```php
'sub_key' => fn( WP_Post $post ) => $post->post_title . ' (' . get_the_date( 'd/m/Y', $post ) . ')',

'sub_key' => fn( array $row ) => implode( ' — ', array_filter( [
    $row['first_name'] ?? '',
    $row['last_name']  ?? '',
] ) ),
```

If the resolved path returns a nested array or object (rather than a scalar), an empty string is returned and the item is skipped.

#### `link_sub_key`

When `link => true`, `link_sub_key` determines the `href` for each item.

**Magic values** (for `WP_Post` items only):

| Value | Resolves to |
|---|---|
| `'permalink'` | `get_permalink( $item->ID )` |
| `'edit_link'` | `get_edit_post_link( $item->ID )` |

**Dot-notation** — same resolution logic as `sub_key`. Use this for link fields or any item that carries a URL property:

```php
// ACF link field — the item IS the array, so 'url' reads $item['url']
'link_sub_key' => 'url'

// Custom nested URL
'link_sub_key' => 'social.twitter_url'
```

#### Object examples

```php
// Relationship — titles linked to front-end, max 3
$cols->add( 'related_posts', 'Related', 'object', [
    'sub_key'      => 'post_title',
    'link'         => true,
    'link_sub_key' => 'permalink',
    'max_items'    => 3,
] );

// Relationship — titles linked to edit screen
$cols->add( 'related_posts', 'Related', 'object', [
    'sub_key'      => 'post_title',
    'link'         => true,
    'link_sub_key' => 'edit_link',
] );

// Post Object (single) — title only
$cols->add( 'featured_post', 'Feature', 'object', [
    'sub_key' => 'post_title',
] );

// User field
$cols->add( 'assigned_to', 'Assigned', 'object', [
    'sub_key' => 'display_name',
] );

// ACF link field
$cols->add( 'cta_link', 'CTA', 'object', [
    'sub_key'      => 'title',
    'link'         => true,
    'link_sub_key' => 'url',
] );

// Google Map field — address only
$cols->add( 'venue_location', 'Venue', 'object', [
    'sub_key' => 'address',
] );

// Callable — full control
$cols->add( 'event_ref', 'Event', 'object', [
    'sub_key' => fn( WP_Post $p ) => $p->post_title . ' — ' . get_the_date( 'd/m/Y', $p ),
] );
```

---

### `composite`

Combines multiple ACF fields into a single column cell. Pass an **array** as the first argument to `add()` — the type is auto-detected as `'composite'`.

Each element of the array describes one sub-field:

```php
// Full definition
[ 'key' => 'event_date', 'type' => 'date', 'storage_format' => 'Ymd', 'display_format' => 'j M Y' ]

// Shorthand — type defaults to 'text'
'event_time'
```

Any display option for the sub-field's type can be included inline alongside `key` and `type`.

#### Display: template vs separator

```php
// Token template — full control over layout
$cols->add(
    [
        [ 'key' => 'event_date', 'type' => 'date', 'display_format' => 'j M Y' ],
        [ 'key' => 'event_time', 'type' => 'text' ],
    ],
    'Date & Time', 'composite',
    [ 'template' => '{event_date} at {event_time}' ]
);

// Separator — joins all non-empty parts
$cols->add(
    [ 'first_name', 'last_name' ],
    'Name', 'composite',
    [ 'separator' => ' ' ]
);
```

#### Composite options

| Option | Type | Default | Description |
|---|---|---|---|
| `template` | `string` | `''` | Token template. Use `{acf_key}` placeholders. Takes precedence over `separator`. |
| `separator` | `string` | `' '` | Fallback join string when `template` is empty. Empty sub-field values are excluded. |
| `sort_field` | `string` | `''` | ACF key of the single sub-field that drives the sort. Defaults to the first sub-field. |
| `sort_fields` | `array` | `[]` | Multi-key sort: `[ 'event_date' => 'ASC', 'event_time' => 'ASC' ]`. Uses named `meta_query` clauses (WP 4.2+). Overrides `sort_field` when set. |
| `filter_field` | `string` | `''` | ACF key of the sub-field whose values populate the filter widget. Defaults to the first sub-field. |
| `filter_type` | `string` | `'text'` | Type of filter widget: `'text'`, `'date'`, `'true_false'`, or `'taxonomy'`. |
| `filter_by` | `string` | `'year'` | For `filter_type => 'date'`: `'year'` or `'month'`. |
| `sortable` | `bool` | `true` | |
| `filterable` | `bool` | `false` | |
| `clickable` | `bool` | `false` | Wraps the entire rendered cell in a filter link targeting `filter_field`. |

#### Composite sorting

**Single-field sort** — set `sort_field` to the ACF key you want to order by:

```php
'sort_field' => 'event_date'
// Sets meta_key = event_date, orderby = meta_value
```

**Multi-key sort** — set `sort_fields` for a true two-level sort (requires WP 4.2+). Most useful for date + time columns:

```php
'sort_fields' => [ 'event_date' => 'ASC', 'event_time' => 'ASC' ]
// Sorts by date first; breaks ties by time
```

#### Sub-field types supported in composite

| Type | Formatted as |
|---|---|
| `text` | Stripped plain text, optionally truncated |
| `date` | Formatted via `display_format` |
| `true_false` | `true_label` or `false_label` text |
| `taxonomy` | Comma-separated term names |
| `object` | Comma-separated resolved `sub_key` values |
| `image` | *(skipped — images don't produce inline text)* |

#### Composite examples

```php
// Date + time with multi-key sort
$cols->add(
    [
        [ 'key' => 'event_date', 'type' => 'date', 'storage_format' => 'Ymd', 'display_format' => 'j M Y' ],
        [ 'key' => 'event_time', 'type' => 'text' ],
    ],
    'Date & Time', 'composite',
    [
        'template'     => '{event_date} at {event_time}',
        'sort_fields'  => [ 'event_date' => 'ASC', 'event_time' => 'ASC' ],
        'filter_field' => 'event_date',
        'filter_type'  => 'date',
        'filter_by'    => 'month',
        'filterable'   => true,
    ]
);

// Speaker (relationship) + date
$cols->add(
    [
        [ 'key' => 'speaker',   'type' => 'object', 'sub_key' => 'post_title' ],
        [ 'key' => 'talk_date', 'type' => 'date',   'storage_format' => 'Ymd', 'display_format' => 'j M Y' ],
    ],
    'Speaker & Date', 'composite',
    [
        'template'   => '{speaker} — {talk_date}',
        'sort_field' => 'talk_date',
    ]
);

// First name + last name
$cols->add(
    [ 'first_name', 'last_name' ],
    'Name', 'composite',
    [ 'separator' => ' ', 'sortable' => false ]
);
```

---

## Shared options

These options are available on every field type:

| Option | Type | Description |
|---|---|---|
| `sortable` | `bool` | Makes the column header a clickable sort link. First click = ascending, second = descending. |
| `filterable` | `bool` | Renders a `<select>` dropdown above the list table. Submitting the form filters posts to matching values. The active filter persists in the URL. |
| `clickable` | `bool` | Wraps the cell value in a link. Clicking it applies a filter for that exact value without needing the dropdown. Works independently of `filterable`. |

---

## Sorting

Clicking a sortable column header sets `orderby` in the URL. The class intercepts this in `pre_get_posts` and translates it to the correct WP_Query arguments.

| Field type | WP `orderby` strategy |
|---|---|
| `text` | `meta_value` (CHAR, lexicographic) |
| `date` | `meta_value` (CHAR — Ymd strings sort chronologically) |
| `true_false` | `meta_value_num` (numeric `0` / `1`) |
| `composite` (single field) | Inherits from the resolved sub-field type |
| `composite` (multi-field) | Named `meta_query` clauses + `orderby` array |
| `image`, `taxonomy`, `object` | Not sortable by default |

---

## Filtering

When `filterable => true`, a `<select>` appears in the admin bar above the list table. Options are populated from existing data:

| Field type | Dropdown populated from |
|---|---|
| `text` | Unique `meta_value` values in `wp_postmeta` (direct DB query) |
| `true_false` | Static Yes / No options |
| `taxonomy` | All non-empty terms via `get_terms()` |
| `date` (year) | Unique years extracted from stored values, newest first |
| `date` (month) | Unique year-month combinations, newest first |
| `composite` | Delegates to the `filter_field` / `filter_type` combination |

Selecting an option and clicking **Filter** applies a `meta_query` (or `tax_query` for taxonomy) to `WP_Query` via `pre_get_posts`.

---

## Click-to-filter

When `clickable => true`, each cell value becomes a link. Clicking it adds a filter query var to the URL and immediately shows only matching posts — without needing the top-bar dropdown.

The URL param is namespaced as `acf_f_{column_id}` to avoid conflicts with WordPress core parameters.

`clickable` and `filterable` are independent — you can have:

| Configuration | Result |
|---|---|
| `'clickable' => true, 'filterable' => false` | Inline cell links only, no dropdown |
| `'clickable' => false, 'filterable' => true` | Dropdown only, no cell links |
| `'clickable' => true, 'filterable' => true` | Both — dropdown + clickable cells |

---

## Default values reference

| Type | `sortable` | `filterable` | `clickable` | Notes |
|---|---|---|---|---|
| `text` | ✔ | ✗ | ✗ | `filterable` requires a DB scan — opt in explicitly |
| `image` | ✗ | ✗ | ✗ | Display only |
| `true_false` | ✔ | ✔ | ✔ | All three on by default |
| `taxonomy` | ✗ | ✔ | ✔ | `link` also `true` by default |
| `date` | ✔ | ✔ | ✗ | |
| `object` | ✗ | ✗ | ✗ | Raw meta values (e.g. IDs) not suitable for sort/filter |
| `composite` | ✔ | ✗ | ✗ | Requires `sort_field` or `sort_fields` to be useful |

---

## ACF return format compatibility

The class handles ACF's three image return formats (array, ID, URL) and normalises taxonomy and object fields automatically. For reference:

| ACF field | Recommended return format | Notes |
|---|---|---|
| Image | Array (default) | ID and URL also supported |
| Taxonomy | Any | Term objects and IDs are both handled |
| Relationship | Post objects | Post IDs also work |
| Post Object | Post object | — |
| User | User array or object | — |
| Link | Array | — |
| Date Picker | `Ymd` | Set `storage_format` if you've changed the ACF field setting |
| True / False | — | ACF stores `1` / `0` in the DB regardless |

If ACF is not active, all field values fall back to `get_post_meta( $post_id, $key, true )`.

---

## Notes and gotchas

**`register()` must be called after `add()`**
All column definitions must be complete before `register()` hooks into WordPress. Calling `add()` after `register()` has no effect.

**Use on `admin_init` or later**
The class uses `get_current_screen()` to scope `pre_get_posts` to the correct list table. This function is not available before `admin_init`.

**`filterable` on `text` runs a database query**
Each page load of the list table runs `SELECT DISTINCT meta_value ...` to populate the dropdown. For post types with many rows or high-cardinality text fields, consider whether a dropdown is the right UI choice.

**`object` type and sorting**
ACF stores relationship and post object field values as raw post IDs in `wp_postmeta`. Sorting with `sortable => true` will sort numerically by those IDs, not by the resolved display text. Sort by a different column (e.g. a date field) for meaningful ordering.

**Composite `template` and HTML**
The template string and all resolved sub-field values are passed through `esc_html()`. The template cannot contain raw HTML — it is treated as plain text.

**`true_false` glyph labels in filter dropdowns**
The `true_label` and `false_label` options control the cell glyph (e.g. `✔` / `—`). These are intentionally **not** used in the filter dropdown — use `filter_true_label` and `filter_false_label` for human-readable dropdown options.

**Multiple instances per post type**
Create one `Admin_Columns` instance per post type. Instantiating multiple instances for the same post type and calling `register()` on each is supported but may produce unexpected ordering — consolidate all columns into a single instance where possible.
