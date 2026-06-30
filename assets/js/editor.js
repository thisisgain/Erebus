/**
 * src/js/editor.js
 * Editor-only JavaScript.
 * Loaded via wp_enqueue_script in enqueue_block_editor_assets.
 *
 * Use this for:
 *  - Block filters (addFilter, unregisterBlockStyle, etc.)
 *  - Custom sidebar plugin panels
 *  - Block variations registered in JS (prefer block.json where possible)
 *  - Disabling specific block features globally
 */

import '../scss/editor.scss';

import { addFilter }  from '@wordpress/hooks';
import { select }     from '@wordpress/data';
import domReady       from '@wordpress/dom-ready';

// ── EXAMPLE: Remove the core/embed Twitter variation ─────────────────────────
// (Uncomment if needed)
// import { unregisterBlockVariation } from '@wordpress/blocks';
// domReady( () => {
//     unregisterBlockVariation( 'core/embed', 'twitter' );
// } );

// ── EXAMPLE: Add a custom block attribute to all blocks ──────────────────────
// addFilter(
//     'blocks.registerBlockType',
//     'modern-wp/add-custom-attr',
//     ( settings ) => {
//         settings.attributes = {
//             ...settings.attributes,
//             customTracking: { type: 'string', default: '' },
//         };
//         return settings;
//     }
// );

domReady( () => {
    console.log( '[Origin] Editor scripts loaded.' );
} );
