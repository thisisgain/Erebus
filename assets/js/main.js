/**
 * src/js/main.js
 * Global front-end JavaScript entry point.
 * Import the main stylesheet so Webpack includes it in the manifest.
 */

// SCSS import — Webpack extracts this into public/css/main.[hash].css via MiniCssExtractPlugin.
// This is how the CSS gets its own manifest entry without a separate CSS-only config.
import '../scss/main.scss';

// ── MODULES ──────────────────────────────────────────────────────────────────
// Import lightweight vanilla JS modules for front-end interactivity.
// Heavy interactions should use the WordPress Interactivity API instead
// (configured per-block via supports.interactivity in block.json).

import './modules/navigation';
import './modules/scroll-observer';

// ── INIT ─────────────────────────────────────────────────────────────────────

document.addEventListener( 'DOMContentLoaded', () => {
    // console.log( '[Origin] Scripts loaded.' );
} );


