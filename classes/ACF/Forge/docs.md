# Forge ACF Builder Library Documentation

A modern, object-oriented PHP library for creating Advanced Custom Fields field groups and blocks in WordPress with a fluent, expressive API.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Field Groups](#field-groups)
- [Fields](#fields)
- [Blocks](#blocks)
- [Registry](#registry)
- [Advanced Usage](#advanced-usage)
- [API Reference](#api-reference)

---

## Installation

1. Place the library file in your theme or plugin directory
2. Include or autoload the library:

```php
require_once get_template_directory() . '/includes/acf-builder.php';
```

3. Use the namespace in your files:

```php
use Origin\ACF\Forge\FieldGroup;
use Origin\ACF\Forge\Fields;
use Origin\ACF\Forge\BlockBuilder;
use Origin\ACF\Forge\Registry;
```

---

## Quick Start

### Creating a Simple Field Group

```php
use Origin\ACF\Forge\FieldGroup;
use Origin\ACF\Forge\Fields;
use Origin\ACF\Forge\Registry;

$group = FieldGroup::make('Page Settings')
    ->location('post_type', '==', 'page')
    ->addFields([
        Fields::text('subtitle')->label('Page Subtitle'),
        Fields::wysiwyg('intro_text')->label('Introduction'),
        Fields::image('header_image')->returnFormat('array')
    ]);

Registry::addFieldGroup($group);
```

### Creating a Block

```php
$block = BlockBuilder::make('hero', 'Hero Section')
    ->description('A hero section with image and text')
    ->category('layout')
    ->icon('cover-image')
    ->renderTemplate(get_template_directory() . '/blocks/hero.php');

Registry::addBlock($block);
```

---

## Field Groups

### Creating Field Groups

Use `FieldGroup::make()` to create a new field group:

```php
$group = FieldGroup::make('Contact Information')
    ->key('group_contact_info') // Optional: custom key
    ->location('post_type', '==', 'page')
    ->position('side')
    ->style('seamless');
```

### Location Rules

Define where the field group appears using the `location()` method:

```php
// Single location rule
$group->location('post_type', '==', 'page');

// Multiple location rules (OR logic)
$group->location('post_type', '==', 'page')
      ->location('post_type', '==', 'post');

// Common location parameters
$group->location('post_type', '==', 'post');
$group->location('page_template', '==', 'template-custom.php');
$group->location('post_template', '==', 'template-full-width.php');
$group->location('taxonomy', '==', 'category');
$group->location('user_role', '==', 'administrator');
```

### Field Group Configuration

```php
$group = FieldGroup::make('Settings')
    ->position('side') // normal, side, acf_after_title
    ->style('seamless') // default, seamless
    ->menuOrder(10)
    ->hideOnScreen([
        'permalink',
        'the_content',
        'excerpt',
        'discussion',
        'comments',
        'revisions',
        'slug',
        'author',
        'format',
        'page_attributes',
        'featured_image',
        'categories',
        'tags',
        'send-trackbacks'
    ]);
```

### Adding Fields

Add fields individually or in bulk:

```php
// Add single field
$group->addField(
    Fields::text('phone_number')
);

// Add multiple fields
$group->addFields([
    Fields::text('email'),
    Fields::text('phone'),
    Fields::textarea('address')
]);
```

### Registering Field Groups

```php
// Automatic registration via Registry
Registry::addFieldGroup($group);

// Manual registration
$group->register();
```

---

## Fields

### Field Helper Methods

The `Fields` class provides quick access to all ACF field types:

#### Text Fields
```php
Fields::text('field_name')
Fields::textarea('description')
Fields::wysiwyg('content')
Fields::email('email_address')
Fields::url('website')
Fields::password('secure_field')
```

#### Number Fields
```php
Fields::number('quantity')
    ->min(0)
    ->max(100)
    ->defaultValue(1);

Fields::range('opacity')
    ->min(0)
    ->max(1)
    ->set('step', 0.1);
```

#### Choice Fields
```php
Fields::select('country', [
    'us' => 'United States',
    'uk' => 'United Kingdom',
    'ca' => 'Canada'
])->allowNull();

Fields::checkbox('services', [
    'design' => 'Design',
    'development' => 'Development',
    'marketing' => 'Marketing'
]);

Fields::radio('size', [
    'small' => 'Small',
    'medium' => 'Medium',
    'large' => 'Large'
])->defaultValue('medium');

Fields::trueFalse('is_featured')
    ->defaultValue(false);
```

#### Media Fields
```php
Fields::image('featured_image')
    ->returnFormat('array') // id, url, array
    ->set('preview_size', 'medium');

Fields::file('pdf_download')
    ->mimeTypes('pdf,doc,docx');

Fields::gallery('image_gallery')
    ->min(1)
    ->max(10);
```

#### Date & Time Fields
```php
Fields::datePicker('event_date')
    ->displayFormat('d/m/Y')
    ->returnFormat('Y-m-d');

Fields::dateTimePicker('event_start');

Fields::timePicker('opening_time');
```

#### Relational Fields
```php
Fields::postObject('related_post')
    ->set('post_type', ['post', 'page'])
    ->allowNull();

Fields::relationship('related_posts')
    ->set('post_type', ['post'])
    ->set('filters', ['search', 'taxonomy'])
    ->min(1)
    ->max(3);

Fields::taxonomy('categories')
    ->set('taxonomy', 'category')
    ->set('field_type', 'checkbox')
    ->returnFormat('id');

Fields::user('author')
    ->set('role', ['author', 'editor']);

Fields::link('cta_link');
```

#### Layout Fields
```php
// Repeater
Fields::repeater('team_members', [
    Fields::text('name'),
    Fields::email('email'),
    Fields::image('photo')
])->min(1)->max(10)->layout('block');

// Group
Fields::group('contact_info', [
    Fields::text('phone'),
    Fields::email('email'),
    Fields::url('website')
])->layout('block');
```

#### Other Fields
```php
Fields::colorPicker('brand_color')
    ->defaultValue('#000000');
```

### Common Field Methods

All fields support these methods:

```php
Fields::text('field_name')
    ->label('Custom Label')
    ->instructions('Helper text for this field')
    ->required(true)
    ->defaultValue('Default text')
    ->placeholder('Enter text...')
    ->wrapper([
        'width' => '50',
        'class' => 'custom-class',
        'id' => 'custom-id'
    ]);
```

### Text Field Specific Methods

```php
Fields::text('title')
    ->prepend('$')
    ->append('.00')
    ->maxlength(100);
```

### Conditional Logic

Show/hide fields based on other field values:

```php
Fields::text('other_specify')
    ->conditional('field_choice', '==', 'other');

// Multiple conditions
Fields::text('advanced_option')
    ->conditional('field_enabled', '==', '1')
    ->conditional('field_type', '==', 'advanced');
```

### Custom Field Settings

For settings not covered by helper methods:

```php
Fields::text('custom_field')
    ->set('character_limit', 100)
    ->set('new_lines', 'br')
    ->set('custom_setting', 'value');
```

---

## Blocks

### Creating Blocks

```php
$block = BlockBuilder::make('testimonial', 'Testimonial Block')
    ->description('Display customer testimonials')
    ->category('custom') // common, formatting, layout, widgets, embed, custom
    ->icon('format-quote') // Dashicon name
    ->keywords(['testimonial', 'quote', 'review'])
    ->mode('preview'); // auto, preview, edit
```

### Block Supports

Configure what features the block supports:

```php
$block->supports([
    'align' => true, // or ['left', 'center', 'right', 'wide', 'full']
    'mode' => true,
    'multiple' => true,
    'jsx' => true,
    'anchor' => true,
    'customClassName' => true,
    'align_text' => true,
    'align_content' => true,
    'full_height' => true
]);
```

### Rendering Blocks

#### Using a Template File

```php
$block->renderTemplate(get_template_directory() . '/blocks/hero.php');
```

Template file (`blocks/hero.php`):
```php
<?php
$title = get_field('title');
$subtitle = get_field('subtitle');
$image = get_field('image');
?>

<div class="hero-block">
    <?php if ($image): ?>
        <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
    <?php endif; ?>
    
    <h2><?php echo esc_html($title); ?></h2>
    <p><?php echo esc_html($subtitle); ?></p>
</div>
```

#### Using a Callback Function

```php
$block->renderCallback(function($block, $content = '', $is_preview = false) {
    $title = get_field('title');
    
    echo '<div class="custom-block">';
    echo '<h3>' . esc_html($title) . '</h3>';
    echo '</div>';
});
```

### Enqueuing Assets

```php
$block->enqueueStyle('hero-block', get_template_directory_uri() . '/blocks/hero.css');
$block->enqueueScript('hero-block', get_template_directory_uri() . '/blocks/hero.js');
```

### Block Configuration

```php
$block = BlockBuilder::make('pricing', 'Pricing Table')
    ->description('Display pricing information')
    ->category('layout')
    ->icon('money-alt')
    ->keywords(['pricing', 'price', 'table'])
    ->mode('preview')
    ->supports([
        'align' => ['wide', 'full'],
        'anchor' => true
    ])
    ->renderTemplate(get_template_directory() . '/blocks/pricing.php');

Registry::addBlock($block);
```

---

## Registry

The Registry class manages all field groups and blocks, handling WordPress hooks automatically.

### Registering Components

```php
// Register field group
Registry::addFieldGroup($fieldGroup);

// Register block
Registry::addBlock($block);

// Registry automatically hooks into 'acf/init'
```

### Manual Initialization

The Registry initializes automatically, but you can force initialization:

```php
Registry::init();
```

---

## Advanced Usage

### Complex Repeater Fields

```php
$group = FieldGroup::make('Content Sections')
    ->location('post_type', '==', 'page')
    ->addField(
        Fields::repeater('sections', [
            Fields::text('section_title'),
            Fields::wysiwyg('section_content'),
            Fields::repeater('items', [
                Fields::image('item_image'),
                Fields::text('item_title'),
                Fields::textarea('item_description')
            ])->layout('block')
        ])->min(1)->layout('block')
    );
```

### Nested Groups

```php
$group = FieldGroup::make('Advanced Settings')
    ->location('post_type', '==', 'page')
    ->addField(
        Fields::group('seo', [
            Fields::text('meta_title'),
            Fields::textarea('meta_description'),
            Fields::group('social', [
                Fields::text('og_title'),
                Fields::image('og_image'),
                Fields::textarea('og_description')
            ])
        ])->layout('block')
    );
```

### Conditional Field Groups

```php
$group = FieldGroup::make('Video Settings')
    ->location('post_type', '==', 'post')
    ->location('post_format', '==', 'video')
    ->addFields([
        Fields::url('video_url')->required(),
        Fields::select('video_provider', [
            'youtube' => 'YouTube',
            'vimeo' => 'Vimeo',
            'self' => 'Self Hosted'
        ]),
        Fields::file('video_file')
            ->conditional('field_video_provider', '==', 'self')
            ->mimeTypes('mp4,mov,avi')
    ]);
```

### Options Pages

```php
// First create an options page (standard ACF code)
if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title' => 'Theme Settings',
        'menu_title' => 'Theme Settings',
        'menu_slug' => 'theme-settings',
        'capability' => 'edit_posts',
        'redirect' => false
    ]);
}

// Then add fields to it
$group = FieldGroup::make('Theme Options')
    ->location('options_page', '==', 'theme-settings')
    ->addFields([
        Fields::text('site_phone'),
        Fields::email('contact_email'),
        Fields::textarea('footer_text')
    ]);

Registry::addFieldGroup($group);
```

### Custom Block with Fields

```php
// Create the block
$block = BlockBuilder::make('call-to-action', 'Call to Action')
    ->category('custom')
    ->icon('megaphone')
    ->renderTemplate(get_template_directory() . '/blocks/cta.php');

Registry::addBlock($block);

// Create fields for the block
$ctaFields = FieldGroup::make('CTA Block Fields')
    ->location('block', '==', 'acf/call-to-action')
    ->addFields([
        Fields::text('heading')->required(),
        Fields::textarea('description'),
        Fields::link('button'),
        Fields::colorPicker('background_color'),
        Fields::select('layout', [
            'left' => 'Left Aligned',
            'center' => 'Center Aligned',
            'right' => 'Right Aligned'
        ])
    ]);

Registry::addFieldGroup($ctaFields);
```

### Working with Field Keys

By default, field keys are auto-generated. You can customize them:

```php
Fields::text('title')
    ->key('field_custom_title_key');

FieldGroup::make('Custom Group')
    ->key('group_custom_key');
```

---

## API Reference

### FieldBuilder Methods

| Method | Description | Example |
|--------|-------------|---------|
| `key(string)` | Set custom field key | `->key('field_custom')` |
| `label(string)` | Set field label | `->label('Email Address')` |
| `instructions(string)` | Add helper text | `->instructions('Enter email')` |
| `required(bool)` | Make field required | `->required()` |
| `defaultValue(mixed)` | Set default value | `->defaultValue('Default')` |
| `placeholder(string)` | Set placeholder text | `->placeholder('Enter...')` |
| `conditional(string, string, mixed)` | Add conditional logic | `->conditional('field', '==', 'value')` |
| `wrapper(array)` | Set wrapper attributes | `->wrapper(['width' => '50'])` |
| `choices(array)` | Set choices (select/radio/checkbox) | `->choices(['a' => 'Option A'])` |
| `multiple(bool)` | Allow multiple selections | `->multiple()` |
| `allowNull(bool)` | Allow null value | `->allowNull()` |
| `min(int/float)` | Set minimum value | `->min(0)` |
| `max(int/float)` | Set maximum value | `->max(100)` |
| `prepend(string)` | Add text before input | `->prepend('$')` |
| `append(string)` | Add text after input | `->append('.00')` |
| `returnFormat(string)` | Set return format | `->returnFormat('array')` |
| `mimeTypes(string)` | Restrict file types | `->mimeTypes('pdf,doc')` |
| `subFields(array)` | Add sub-fields (repeater/group) | `->subFields([...])` |
| `layout(string)` | Set layout style | `->layout('block')` |
| `set(string, mixed)` | Set any custom property | `->set('key', 'value')` |

### FieldGroup Methods

| Method | Description | Example |
|--------|-------------|---------|
| `key(string)` | Set custom group key | `->key('group_custom')` |
| `addField(FieldBuilder)` | Add single field | `->addField($field)` |
| `addFields(array)` | Add multiple fields | `->addFields([...])` |
| `location(string, string, string)` | Add location rule | `->location('post_type', '==', 'page')` |
| `position(string)` | Set position | `->position('side')` |
| `style(string)` | Set style | `->style('seamless')` |
| `menuOrder(int)` | Set menu order | `->menuOrder(10)` |
| `hideOnScreen(array)` | Hide screen elements | `->hideOnScreen(['editor'])` |
| `register()` | Register the group | `->register()` |

### BlockBuilder Methods

| Method | Description | Example |
|--------|-------------|---------|
| `description(string)` | Set block description | `->description('Hero section')` |
| `category(string)` | Set block category | `->category('layout')` |
| `icon(string)` | Set block icon | `->icon('star-filled')` |
| `keywords(array)` | Set search keywords | `->keywords(['hero', 'banner'])` |
| `mode(string)` | Set default mode | `->mode('preview')` |
| `supports(array)` | Configure block supports | `->supports(['align' => true])` |
| `renderCallback(callable)` | Set render callback | `->renderCallback(function() {})` |
| `renderTemplate(string)` | Set template path | `->renderTemplate('/path/to/template.php')` |
| `enqueueStyle(string, string)` | Enqueue CSS | `->enqueueStyle('handle', 'url')` |
| `enqueueScript(string, string)` | Enqueue JS | `->enqueueScript('handle', 'url')` |
| `register()` | Register the block | `->register()` |

### Fields Helper Methods

All available field types via the `Fields` class:

- `text(string)` - Text input
- `textarea(string)` - Textarea
- `wysiwyg(string)` - WYSIWYG editor
- `image(string)` - Image upload
- `file(string)` - File upload
- `gallery(string)` - Gallery
- `select(string, array)` - Select dropdown
- `checkbox(string, array)` - Checkbox
- `radio(string, array)` - Radio buttons
- `trueFalse(string)` - True/False toggle
- `number(string)` - Number input
- `range(string)` - Range slider
- `email(string)` - Email input
- `url(string)` - URL input
- `password(string)` - Password input
- `datePicker(string)` - Date picker
- `dateTimePicker(string)` - Date & time picker
- `timePicker(string)` - Time picker
- `colorPicker(string)` - Color picker
- `repeater(string, array)` - Repeater field
- `group(string, array)` - Group field
- `relationship(string)` - Post relationship
- `postObject(string)` - Post object
- `taxonomy(string)` - Taxonomy selector
- `user(string)` - User selector
- `link(string)` - Link picker

---

## Complete Examples

### Example 1: Product Custom Post Type

```php
// Register product field group
$productGroup = FieldGroup::make('Product Details')
    ->location('post_type', '==', 'product')
    ->addFields([
        Fields::number('price')
            ->label('Price')
            ->prepend('$')
            ->required()
            ->min(0),
        
        Fields::text('sku')
            ->label('SKU')
            ->required(),
        
        Fields::select('availability', [
            'in_stock' => 'In Stock',
            'out_of_stock' => 'Out of Stock',
            'pre_order' => 'Pre-Order'
        ])->defaultValue('in_stock'),
        
        Fields::repeater('features', [
            Fields::text('feature_name'),
            Fields::textarea('feature_description')
        ])->min(1)->layout('block'),
        
        Fields::gallery('product_images')
            ->min(1)
            ->max(10)
    ]);

Registry::addFieldGroup($productGroup);
```

### Example 2: Homepage Builder

```php
// Create homepage sections block
$heroBlock = BlockBuilder::make('hero-section', 'Hero Section')
    ->category('layout')
    ->icon('cover-image')
    ->renderTemplate(get_template_directory() . '/blocks/hero.php');

Registry::addBlock($heroBlock);

// Hero block fields
$heroFields = FieldGroup::make('Hero Section Fields')
    ->location('block', '==', 'acf/hero-section')
    ->addFields([
        Fields::text('headline')->required(),
        Fields::textarea('subheadline'),
        Fields::image('background_image')->returnFormat('array'),
        Fields::link('cta_button'),
        Fields::select('text_alignment', [
            'left' => 'Left',
            'center' => 'Center',
            'right' => 'Right'
        ])->defaultValue('center')
    ]);

Registry::addFieldGroup($heroFields);
```

### Example 3: Team Members

```php
$teamGroup = FieldGroup::make('Team Member Info')
    ->location('post_type', '==', 'team_member')
    ->addFields([
        Fields::text('job_title')->required(),
        Fields::image('headshot')->returnFormat('array'),
        Fields::group('social_links', [
            Fields::url('linkedin'),
            Fields::url('twitter'),
            Fields::email('email')
        ])->layout('table'),
        Fields::wysiwyg('bio'),
        Fields::repeater('skills', [
            Fields::text('skill_name'),
            Fields::range('proficiency')->min(0)->max(100)
        ])->layout('block')
    ]);

Registry::addFieldGroup($teamGroup);
```

---

## Best Practices

1. **Use the Registry**: Always register via `Registry::addFieldGroup()` and `Registry::addBlock()` for automatic hook management

2. **Field Keys**: Let the library auto-generate keys unless you need specific keys for data migration

3. **Return Formats**: Always specify return formats for image/file fields:
   ```php
   Fields::image('photo')->returnFormat('array')
   ```

4. **Required Fields**: Mark fields as required in the builder rather than validating later:
   ```php
   Fields::text('email')->required()
   ```

5. **Organize Code**: Keep field definitions in separate files:
   ```
   /includes
     /acf
       /field-groups
         product-fields.php
         team-fields.php
       /blocks
         hero-block.php
         testimonial-block.php
   ```

6. **Template Organization**: Store block templates in a dedicated directory:
   ```
   /blocks
     hero.php
     testimonial.php
     cta.php
   ```

---

## Troubleshooting

**Fields not appearing**: Ensure ACF Pro is installed and activated.

**Blocks not showing**: ACF Blocks require ACF Pro 5.8+. Verify your version.

**Location rules not working**: Double-check your location parameter names match ACF's expected values.

**Template not rendering**: Verify the template path is correct and the file exists:
```php
// Use absolute paths
get_template_directory() . '/blocks/hero.php'
```

**Conditional logic not working**: Ensure you're using the correct field key in conditional statements.

---

## License

This library is provided as-is for use in WordPress projects with Advanced Custom Fields Pro.