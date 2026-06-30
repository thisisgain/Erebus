<?php

namespace Origin\ACF\Forge;

use Origin\ACF\Forge\FieldGroup;
use Origin\ACF\Forge\BlockBuilder;

/**
 * Registry - Manages registration of field groups and blocks
 */
class FieldRegistry {
    protected static array $fieldGroups = [];
    protected static array $blocks = [];
    protected static bool $initialized = false;
    
    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        
        add_action('acf/init', [self::class, 'registerAll']);
        self::$initialized = true;
    }
    
    public static function addFieldGroup(FieldGroup $group): void {
        // If acf/init has already fired (e.g. the field file was loaded on a
        // later hook such as init:10), register straight away — deferring to
        // acf/init would silently do nothing. Otherwise queue for acf/init.
        if (did_action('acf/init')) {
            $group->register();
            return;
        }

        self::$fieldGroups[] = $group;
        self::init();
    }

    public static function addBlock(BlockBuilder $block): void {
        if (did_action('acf/init')) {
            $block->register();
            return;
        }

        self::$blocks[] = $block;
        self::init();
    }
    
    public static function registerAll(): void {
        foreach (self::$fieldGroups as $group) {
            $group->register();
        }
        
        foreach (self::$blocks as $block) {
            $block->register();
        }
    }
}