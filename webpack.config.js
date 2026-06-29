/**
 * webpack.config.js
 *
 * Compiles:
 *   1. Global assets  → assets/scss/main.scss + assets/js/main.js → public/css|js/
 *   2. Block assets   → blocks/{name}/assets/  → blocks/{name}/build/
 *
 * In production:
 *   - Content hashes added to filenames  (e.g. main.abc123.css)
 *   - manifest.json written to public/   (maps logical name → hashed filename)
 *   - Assets minified
 *
 * In development:
 *   - No hashes, no minification, source maps enabled
 *
 * ── IMPORTANT: ENTRY NAME SCOPING ────────────────────────────────────────────
 * Each Webpack config in the exported array is fully isolated — entry key names
 * do NOT need to be globally unique across configs. The 'editor-style' key in a
 * block config and the 'editor-style' key in the global config are resolved
 * relative to their own `context` / `output.path` and never conflict.
 *
 * The previous error "Can't resolve 'blocks/hero/assets/editor.scss'" was caused by
 * the globalScssConfig emitting a stub JS file with an 'editor-style' entry that
 * Webpack tried to resolve as a JS module in the wrong context. That config has
 * been removed — global SCSS is now imported directly from within its JS entry
 * files (main.js → main.scss, editor.js → editor.scss), which is the standard
 * Webpack pattern and avoids any cross-config resolution ambiguity.
 */

const path                  = require('path');
const glob                  = require('glob');
const MiniCssExtractPlugin  = require('mini-css-extract-plugin');
const CssMinimizerPlugin    = require('css-minimizer-webpack-plugin');
const TerserPlugin          = require('terser-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

const isProd = process.env.NODE_ENV === 'production';

// ── SHARED LOADERS ───────────────────────────────────────────────────────────

const scssRule = {
  test: /\.scss$/,
  use: [
    MiniCssExtractPlugin.loader,
    { loader: 'css-loader', options: { sourceMap: !isProd } },
    {
      loader: 'postcss-loader',
      options: {
        postcssOptions: { plugins: [['autoprefixer']] },
        sourceMap: !isProd,
      },
    },
    { loader: 'sass-loader', options: { api: 'modern-compiler', sourceMap: !isProd } },
  ],
};

const jsRule = {
  test: /\.jsx?$/,
  exclude: /node_modules/,
  use: {
    loader: 'babel-loader',
    options: {
      presets: [
        ['@babel/preset-env', { targets: '> 0.5%, last 2 versions, not dead' }],
        // runtime: 'automatic' means you don't need `import React from 'react'`
        // in every file — Babel injects the JSX transform automatically.
        ['@babel/preset-react', { runtime: 'automatic' }],
      ],
    },
  },
};

// ── FILENAME HELPERS ─────────────────────────────────────────────────────────
// Production: content hash for cache busting. Development: plain names.

const jsFilename  = isProd ? 'js/[name].[contenthash:8].js'  : 'js/[name].js';
const cssFilename = isProd ? 'css/[name].[contenthash:8].css' : 'css/[name].css';

// ── 1. GLOBAL CONFIG ─────────────────────────────────────────────────────────
// Handles all theme-level (non-block) JS and CSS.
//
// Entry points and what they produce:
//   main   → assets/js/main.js   (imports assets/scss/main.scss)
//            → public/js/main.[hash].js
//            → public/css/main.[hash].css   (extracted from the SCSS import)
//
//   editor → assets/js/editor.js (imports assets/scss/editor.scss)
//            → public/js/editor.[hash].js
//            → public/css/editor.[hash].css
//
// Both JS and CSS filenames are recorded in public/manifest.json.
// PHP reads the manifest in inc/assets.php to enqueue the correct hashed paths.

const globalConfig = {
  name:    'global',
  mode:    isProd ? 'production' : 'development',
  devtool: isProd ? false : 'source-map',
  context: path.resolve(__dirname),

  entry: {
    // main.js must `import '../scss/main.scss'` to produce the CSS bundle.
    main:           './assets/js/main.js',
    // editor.js must `import '../scss/editor.scss'` to produce the editor CSS.
    editor:         './assets/js/editor.js',
    // Block-specific overrides compiled as standalone CSS files.
    'core-accordion': './assets/js/blocks/core-accordion.js',
  },

  output: {
    path:     path.resolve(__dirname, 'public'),
    filename: jsFilename,
    clean:    false, // Don't wipe public/ — manifest tracks what's current.
  },

  module: { rules: [scssRule, jsRule] },

  resolve: {
    extensions: ['.js', '.jsx'],
  },

  plugins: [
    new MiniCssExtractPlugin({ filename: cssFilename }),

    // Writes public/manifest.json mapping entry names → hashed filenames.
    // Example (production):
    //   { "css/main.css": "css/main.a1b2c3d4.css", "js/main.js": "js/main.a1b2c3d4.js" }
    // Example (development):
    //   { "css/main.css": "css/main.css", "js/main.js": "js/main.js" }
    new WebpackManifestPlugin({
      fileName:   'manifest.json',
      publicPath: '',
      // Strip source maps from the manifest — they're not enqueued.
      filter: (file) => !file.name.endsWith('.map'),
    }),
  ],

  optimization: isProd
    ? {
        minimizer: [
          new TerserPlugin({ extractComments: false }),
          new CssMinimizerPlugin(),
        ],
      }
    : {},
};

// ── 2. BLOCK CONFIGS ─────────────────────────────────────────────────────────
// Auto-discovers every block under blocks/ that has a src/index.js.
// Each block gets its own isolated Webpack config with its own output path,
// so entry key names ('index', 'style', 'editor-style') are scoped per-block
// and never conflict with each other or with the global config.
//
// Output per block:
//   blocks/{name}/build/index.js          ← editorScript in block.json
//   blocks/{name}/build/style.css         ← style in block.json
//   blocks/{name}/build/editor-style.css  ← editorStyle in block.json
//
// WordPress automatically enqueues these when the block appears on a page.
// No manifest needed — block.json handles all asset registration.

// Discover block source files using absolute paths to avoid any resolution
// ambiguity. path.resolve() ensures Webpack gets an unambiguous entry path
// regardless of the cwd when the build is invoked.
const blockIndexFiles  = glob.sync(path.resolve(__dirname, 'blocks/*/src/index.js'));
const blockStyleFiles  = glob.sync(path.resolve(__dirname, 'blocks/*/src/style.scss'));
const blockEditorFiles = glob.sync(path.resolve(__dirname, 'blocks/*/src/editor.scss'));
const blockViewFiles   = glob.sync(path.resolve(__dirname, 'blocks/*/src/view.js'));

// Build lookup maps: blockName → absolute file path
const styleByBlock  = Object.fromEntries(
  blockStyleFiles.map((f) => [path.basename(path.dirname(path.dirname(f))), f])
);
const editorByBlock = Object.fromEntries(
  blockEditorFiles.map((f) => [path.basename(path.dirname(path.dirname(f))), f])
);
const viewByBlock = Object.fromEntries(
  blockViewFiles.map((f) => [path.basename(path.dirname(path.dirname(f))), f])
);

const blockConfigs = blockIndexFiles.map((indexFile) => {
  // Derive the block name from the directory structure:
  //   /abs/path/blocks/hero/src/index.js → "hero"
  const blockName = path.basename(path.dirname(path.dirname(indexFile)));

  // Build the entry object for this block.
  // Only include style/editor-style entries if the source files exist —
  // this prevents "Module not found" errors for blocks that have no SCSS yet.
  const entry = { index: indexFile };

  if (styleByBlock[blockName]) {
    entry.style = styleByBlock[blockName];
  }

  if (editorByBlock[blockName]) {
    // Named 'editor-style' to match the block.json editorStyle → build/editor-style.css
    entry['editor-style'] = editorByBlock[blockName];
  }

  if (viewByBlock[blockName]) {
    entry.view = viewByBlock[blockName];
  }

  return {
    name:    `block-${blockName}`,
    mode:    isProd ? 'production' : 'development',
    devtool: isProd ? false : 'source-map',

    // Set context to the block's own directory so any relative imports
    // inside block source files resolve correctly.
    context: path.resolve(__dirname, `blocks/${blockName}`),

    entry,

    output: {
      path:     path.resolve(__dirname, `blocks/${blockName}/build`),
      filename: '[name].js',
      clean:    true,
    },

    module: { rules: [scssRule, jsRule] },

    resolve: {
      extensions: ['.js', '.jsx'],
    },

    plugins: [
      // Extracts SCSS into separate CSS files.
      // '[name].css' → style.css, editor-style.css (matching entry key names).
      new MiniCssExtractPlugin({ filename: '[name].css' }),
      // Externalises all @wordpress/* packages and react/react-dom as wp.* globals,
      // and writes build/index.asset.php listing the WP script handles WordPress
      // needs to enqueue before this block's script runs.
      new DependencyExtractionWebpackPlugin(),
    ],

    optimization: isProd
      ? {
          minimizer: [
            new TerserPlugin({ extractComments: false }),
            new CssMinimizerPlugin(),
          ],
        }
      : {},
  };
});

// ── EXPORTS ──────────────────────────────────────────────────────────────────
// Webpack runs all configs in parallel. The global config and each block config
// are fully isolated — they share no state, entry namespaces, or output paths.
module.exports = [globalConfig, ...blockConfigs];
