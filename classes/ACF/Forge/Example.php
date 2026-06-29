<?php

// Create a field group
$heroGroup = FieldGroup::make('Hero Section')
    ->location('post_type', '==', 'page')
    ->location('page_template', '==', 'template-home.php')
    ->addFields([
        Fields::text('hero_title')
            ->label('Hero Title')
            ->required(),
        Fields::textarea('hero_subtitle')
            ->label('Hero Subtitle'),
        Fields::image('hero_background')
            ->label('Background Image')
            ->returnFormat('array'),
        Fields::select('hero_layout', [
            'left' => 'Left Aligned',
            'center' => 'Center Aligned',
            'right' => 'Right Aligned'
        ])->defaultValue('center')
    ]);

Registry::addFieldGroup($heroGroup);

// Create a block
$testimonialBlock = BlockBuilder::make('testimonial', 'Testimonial')
    ->description('Display a customer testimonial')
    ->category('custom')
    ->icon('format-quote')
    ->keywords(['testimonial', 'quote', 'review'])
    ->renderTemplate(get_template_directory() . '/blocks/testimonial.php');

Registry::addBlock($testimonialBlock);

