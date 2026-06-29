<?php

namespace Origin\Docs;

use Origin\Docs\ThemeDocsMarkdownParser;

/**
 * Theme Docs

 * Registers a WordPress admin documentation section that reads Markdown files
 * from /docs/ in the active theme and renders them with a tabbed sidebar UI.
 *
 * Usage: require_once get_template_directory() . '/inc/theme-docs/class-theme-docs.php';
 *        ThemeDocs::init();
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ThemeDocs {

	/** Absolute path to the /docs/ directory in the active theme */
	private string $docs_dir;

	/** Public URL to the /docs/ directory */
	private string $docs_url;

	/** Admin page hook suffix (used to scope asset enqueueing) */
	private string $page_hook = '';

	/** Nonce action used for AJAX requests */
	private const NONCE_ACTION = 'theme_docs_load';

	/** Admin menu slug */
	private const MENU_SLUG = 'theme-docs';

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	public static function init(): void {
		$instance = new self();
		$instance->register_hooks();
	}

	public function __construct() {
		$this->docs_dir = get_template_directory() . '/docs';
		$this->docs_url = get_template_directory_uri() . '/docs';
	}

	private function register_hooks(): void {
		add_action( 'admin_menu',            [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_theme_docs_load_doc', [ $this, 'ajax_load_doc' ] );
	}

	// -------------------------------------------------------------------------
	// Admin menu
	// -------------------------------------------------------------------------

	public function register_menu(): void {
		$this->page_hook = add_menu_page(
			__( 'Documentation', 'theme-docs' ),
			__( 'Documentation', 'theme-docs' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'dashicons-book-alt',
			99
		);
	}

	// -------------------------------------------------------------------------
	// Asset enqueueing
	// -------------------------------------------------------------------------

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		// Enqueue WordPress's built-in code highlighting (optional, for code blocks)
		wp_enqueue_style( 'wp-codemirror' );

		// Inline styles — output on this page only
		wp_add_inline_style(
			'wp-codemirror',
			$this->get_admin_styles()
		);
	}

	// -------------------------------------------------------------------------
	// Page rendering
	// -------------------------------------------------------------------------

	public function render_page(): void {
		$docs = $this->get_docs();

		if ( empty( $docs ) ) {
			$this->render_empty_state();
			return;
		}

		$first  = $docs[0];
		$nonce  = wp_create_nonce( self::NONCE_ACTION );
		$ajaxurl = admin_url( 'admin-ajax.php' );
		?>
		<div class="theme-docs-wrap" id="theme-docs-wrap">

			<nav class="theme-docs-sidebar" id="theme-docs-sidebar" aria-label="Documentation sections">
				<div class="theme-docs-sidebar__header">
					<span class="dashicons dashicons-book-alt"></span>
					<span class="theme-docs-sidebar__title">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
						<small><?php esc_html_e( 'Documentation', 'theme-docs' ); ?></small>
					</span>
				</div>
				<ul class="theme-docs-nav" role="tablist">
					<?php foreach ( $docs as $i => $doc ) : ?>
						<li class="theme-docs-nav__item" role="presentation">
							<button
								class="theme-docs-nav__btn<?php echo $i === 0 ? ' is-active' : ''; ?>"
								role="tab"
								data-file="<?php echo esc_attr( $doc['file'] ); ?>"
								data-label="<?php echo esc_attr( $doc['label'] ); ?>"
								aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
								tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>"
							>
								<?php if ( ! empty( $doc['icon'] ) ) : ?>
									<span class="dashicons dashicons-<?php echo esc_attr( $doc['icon'] ); ?>"></span>
								<?php endif; ?>
								<?php echo esc_html( $doc['label'] ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<main class="theme-docs-content" id="theme-docs-content" role="tabpanel">
				<div class="theme-docs-content__inner">
					<div class="theme-docs-loading" id="theme-docs-loading" aria-live="polite">
						<span class="spinner is-active"></span>
						<span><?php esc_html_e( 'Loading…', 'theme-docs' ); ?></span>
					</div>
					<article class="theme-docs-article" id="theme-docs-article" aria-label="Documentation content">
						<?php /* Content injected via JS */ ?>
					</article>
				</div>
			</main>

		</div>

		<script>
		( function() {
			const AJAX_URL  = <?php echo wp_json_encode( $ajaxurl ); ?>;
			const NONCE     = <?php echo wp_json_encode( $nonce ); ?>;
			const FIRST_DOC = <?php echo wp_json_encode( $first['file'] ); ?>;

			const wrap     = document.getElementById( 'theme-docs-wrap' );
			const loading  = document.getElementById( 'theme-docs-loading' );
			const article  = document.getElementById( 'theme-docs-article' );
			const navBtns  = wrap.querySelectorAll( '.theme-docs-nav__btn' );

			let currentFile = null;
			let cache = {};

			// -----------------------------------------------------------------
			// Load a doc via AJAX
			// -----------------------------------------------------------------
			function loadDoc( file, label ) {
				if ( currentFile === file ) return;
				currentFile = file;

				article.style.opacity = '0';
				loading.hidden = false;

				if ( cache[ file ] ) {
					renderDoc( cache[ file ], label );
					return;
				}

				const data = new FormData();
				data.append( 'action', 'theme_docs_load_doc' );
				data.append( 'nonce',  NONCE );
				data.append( 'file',   file );

				fetch( AJAX_URL, { method: 'POST', body: data } )
					.then( r => r.json() )
					.then( response => {
						if ( response.success ) {
							cache[ file ] = response.data.html;
							renderDoc( response.data.html, label );
						} else {
							renderError( response.data || 'Could not load document.' );
						}
					} )
					.catch( err => renderError( 'Network error: ' + err.message ) );
			}

			function renderDoc( html, label ) {
				article.innerHTML = html;
				loading.hidden = true;              
				document.title = label + ' — Docs';
				article.style.transition = 'opacity 0.2s ease';
				article.style.opacity = '1';

				// Scroll content pane back to top on tab switch
				document.getElementById( 'theme-docs-content' ).scrollTop = 0;

				// Build a sticky in-page TOC from headings if any h2/h3 exist
				buildToc( article );

				// Highlight code blocks with Prism if available
				if ( window.Prism ) {
					Prism.highlightAllUnder( article );
				}
			}

			function renderError( msg ) {
				article.innerHTML = '<div class="theme-docs-error"><span class="dashicons dashicons-warning"></span>' + msg + '</div>';
				loading.hidden = true;
				article.style.opacity = '1';
			}

			// -----------------------------------------------------------------
			// In-page TOC (appended at top of article)
			// -----------------------------------------------------------------
			function buildToc( container ) {
				const headings = container.querySelectorAll( 'h2, h3' );
				if ( headings.length < 3 ) return;

				const toc = document.createElement( 'nav' );
				toc.className = 'theme-docs-toc';
				toc.setAttribute( 'aria-label', 'On this page' );

				const label = document.createElement( 'p' );
				label.className = 'theme-docs-toc__label';
				label.textContent = 'On this page';
				toc.appendChild( label );

				const ul = document.createElement( 'ul' );

				headings.forEach( h => {
					const li  = document.createElement( 'li' );
					const a   = document.createElement( 'a' );
					a.href    = '#' + h.id;
					a.textContent = h.textContent;
					li.className = 'toc-' + h.tagName.toLowerCase();
					li.appendChild( a );
					ul.appendChild( li );
				} );

				toc.appendChild( ul );
				container.prepend( toc );
			}

			// -----------------------------------------------------------------
			// Tab navigation
			// -----------------------------------------------------------------
			navBtns.forEach( btn => {
				btn.addEventListener( 'click', () => {
					navBtns.forEach( b => {
						b.classList.remove( 'is-active' );
						b.setAttribute( 'aria-selected', 'false' );
						b.setAttribute( 'tabindex', '-1' );
					} );
					btn.classList.add( 'is-active' );
					btn.setAttribute( 'aria-selected', 'true' );
					btn.setAttribute( 'tabindex', '0' );

					loadDoc( btn.dataset.file, btn.dataset.label );
				} );
			} );

			// Keyboard navigation (arrow keys for tablist)
			wrap.querySelector( '.theme-docs-nav' ).addEventListener( 'keydown', e => {
				const btns  = [ ...navBtns ];
				const index = btns.indexOf( document.activeElement );
				if ( index === -1 ) return;
				if ( e.key === 'ArrowDown' || e.key === 'ArrowRight' ) {
					btns[ ( index + 1 ) % btns.length ].focus();
					e.preventDefault();
				}
				if ( e.key === 'ArrowUp' || e.key === 'ArrowLeft' ) {
					btns[ ( index - 1 + btns.length ) % btns.length ].focus();
					e.preventDefault();
				}
			} );

			// -----------------------------------------------------------------
			// Initial load
			// -----------------------------------------------------------------
			loadDoc( FIRST_DOC, <?php echo wp_json_encode( $first['label'] ); ?> );

		} )();
		</script>
		<?php
	}

	private function render_empty_state(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Theme Docs', 'theme-docs' ); ?></h1>
			<div class="theme-docs-empty">
				<span class="dashicons dashicons-media-document"></span>
				<h2><?php esc_html_e( 'No documentation found', 'theme-docs' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: path to docs directory */
						esc_html__( 'Add Markdown (.md) files to %s to get started.', 'theme-docs' ),
						'<code>' . esc_html( str_replace( ABSPATH, '/', $this->docs_dir ) ) . '</code>'
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// AJAX handler
	// -------------------------------------------------------------------------

	public function ajax_load_doc(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.', 403 );
		}

		$raw_file = sanitize_file_name( wp_unslash( $_POST['file'] ?? '' ) );

		if ( empty( $raw_file ) ) {
			wp_send_json_error( 'No file specified.' );
		}

		// Security: ensure the resolved path stays inside the docs directory
		$path = realpath( $this->docs_dir . '/' . $raw_file . '.md' );

		if ( ! $path || strpos( $path, realpath( $this->docs_dir ) ) !== 0 ) {
			wp_send_json_error( 'Invalid file path.' );
		}

		if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
			wp_send_json_error( 'File not found.' );
		}

		$markdown = file_get_contents( $path );
		$html     = ThemeDocsMarkdownParser::parse( $markdown );

		// Rewrite relative asset references to absolute URLs
		// e.g. assets/screenshot.png → https://…/docs/assets/screenshot.png
		$docs_url = esc_url( $this->docs_url );
		$html     = preg_replace(
			'/\b(src|href)="assets\//i',
			'$1="' . $docs_url . '/assets/',
			$html
		);

		wp_send_json_success( [ 'html' => $html ] );
	}

	// -------------------------------------------------------------------------
	// Docs discovery
	// -------------------------------------------------------------------------

	/**
	 * Scans the /docs/ directory for Markdown files.
	 *
	 * Ordering: files are sorted alphabetically — prefix with numbers
	 * (e.g. 01-getting-started.md) to control order.
	 *
	 * Optionally, add a _docs.json index file to the docs directory to
	 * customise labels and add Dashicon names, e.g.:
	 * [
	 *   { "file": "getting-started", "label": "Getting Started", "icon": "lightbulb" }
	 * ]
	 *
	 * @return array<int, array{ id: string, file: string, label: string, icon: string }>
	 */
	private function get_docs(): array {
		if ( ! is_dir( $this->docs_dir ) ) {
			return [];
		}

		// Load optional JSON index for custom labels / icons
		$index_path = $this->docs_dir . '/_docs.json';
		$index      = [];
		if ( file_exists( $index_path ) ) {
			$json = json_decode( file_get_contents( $index_path ), true );
			if ( is_array( $json ) ) {
				foreach ( $json as $entry ) {
					if ( isset( $entry['file'] ) ) {
						$index[ $entry['file'] ] = $entry;
					}
				}
			}
		}

		$files = glob( $this->docs_dir . '/*.md' );
		if ( ! $files ) {
			return [];
		}

		sort( $files );

		$docs = [];
		foreach ( $files as $file_path ) {
			$basename = basename( $file_path, '.md' );

			if ( isset( $index[ $basename ] ) ) {
				$entry = $index[ $basename ];
				$docs[] = [
					'id'    => sanitize_title( $basename ),
					'file'  => $basename,
					'label' => $entry['label'] ?? $this->file_to_label( $basename ),
					'icon'  => $entry['icon']  ?? '',
				];
			} else {
				$docs[] = [
					'id'    => sanitize_title( $basename ),
					'file'  => $basename,
					'label' => $this->file_to_label( $basename ),
					'icon'  => '',
				];
			}
		}

		return $docs;
	}

	/**
	 * Converts a filename into a human-readable label.
	 * "01-getting-started" → "Getting Started"
	 */
	private function file_to_label( string $basename ): string {
		// Strip leading numeric prefix: "01-" or "01_"
		$label = preg_replace( '/^\d+[-_]/', '', $basename );
		$label = str_replace( [ '-', '_' ], ' ', $label );
		return ucwords( $label );
	}

	// -------------------------------------------------------------------------
	// Admin styles (injected inline on the docs page only)
	// -------------------------------------------------------------------------

	private function get_admin_styles(): string {
		return <<<'CSS'
/* ============================================================
   Theme Docs — Admin UI
   ============================================================ */

/* Layout ---------------------------------------------------- */
#wpbody-content .theme-docs-wrap {
	display: flex;
	height: calc(100vh - 32px);     /* subtract WP admin bar */
	margin: 0 -20px -10px;          /* bleed to WP content edges */
	overflow: hidden;
	background: #f0f0f1;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, sans-serif;
}

/* Sidebar --------------------------------------------------- */
.theme-docs-sidebar {
	width: 260px;
	min-width: 220px;
	max-width: 300px;
	background: #1d2327;
	display: flex;
	flex-direction: column;
	overflow-y: auto;
	overflow-x: hidden;
	flex-shrink: 0;
	border-right: 1px solid #101517;
}

.theme-docs-sidebar__header {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 20px 18px 16px;
	border-bottom: 1px solid rgba(255,255,255,.08);
	color: #f0f0f1;
}

.theme-docs-sidebar__header .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
	color: #72aee6;
	flex-shrink: 0;
}

.theme-docs-sidebar__title {
	font-size: 13px;
	font-weight: 600;
	line-height: 1.2;
	color: #f0f0f1;
}

.theme-docs-sidebar__title small {
	display: block;
	font-size: 11px;
	font-weight: 400;
	color: #8c8f94;
	text-transform: uppercase;
	letter-spacing: .06em;
}

/* Nav list -------------------------------------------------- */
.theme-docs-nav {
	list-style: none;
	margin: 8px 0;
	padding: 0;
	flex: 1;
}

.theme-docs-nav__item {
	margin: 0;
	padding: 0;
}

.theme-docs-nav__btn {
	display: flex;
	align-items: center;
	gap: 8px;
	width: 100%;
	padding: 9px 18px;
	background: none;
	border: none;
	border-left: 3px solid transparent;
	color: #c3c4c7;
	font-size: 13px;
	font-family: inherit;
	text-align: left;
	cursor: pointer;
	transition: background .15s, color .15s, border-color .15s;
	line-height: 1.4;
}

.theme-docs-nav__btn .dashicons {
	font-size: 15px;
	width: 15px;
	height: 15px;
	flex-shrink: 0;
	color: #8c8f94;
}

.theme-docs-nav__btn:hover {
	background: rgba(255,255,255,.06);
	color: #f0f0f1;
}

.theme-docs-nav__btn.is-active {
	background: rgba(114,174,230,.12);
	border-left-color: #72aee6;
	color: #72aee6;
	font-weight: 600;
}

.theme-docs-nav__btn.is-active .dashicons {
	color: #72aee6;
}

.theme-docs-nav__btn:focus-visible {
	outline: 2px solid #72aee6;
	outline-offset: -2px;
}

/* Content area ---------------------------------------------- */
.theme-docs-content {
	flex: 1;
	overflow-y: auto;
	overflow-x: hidden;
	background: #fff;
	position: relative;
}

.theme-docs-content__inner {
	position: relative;
	max-width: 860px;
	padding: 40px 48px;
	margin: 0 auto;
}

/* Loading state --------------------------------------------- */
.theme-docs-loading {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 40px 0;
	color: #8c8f94;
	font-size: 13px;
}

.theme-docs-loading[hidden] {
    display: none;
}

.theme-docs-loading .spinner {
	float: none;
	margin: 0;
}

/* ============================================================
   Rendered Markdown typography
   ============================================================ */

.theme-docs-article {
	opacity: 0;
	color: #1d2327;
	line-height: 1.7;
	font-size: 14px;
}

.theme-docs-article h1,
.theme-docs-article h2,
.theme-docs-article h3,
.theme-docs-article h4,
.theme-docs-article h5,
.theme-docs-article h6 {
	margin-top: 2em;
	margin-bottom: .5em;
	line-height: 1.25;
	color: #1d2327;
	font-weight: 600;
}

.theme-docs-article h1 { font-size: 28px; margin-top: 0; border-bottom: 1px solid #e2e4e7; padding-bottom: .4em; }
.theme-docs-article h2 { font-size: 20px; border-bottom: 1px solid #e2e4e7; padding-bottom: .3em; }
.theme-docs-article h3 { font-size: 16px; }
.theme-docs-article h4 { font-size: 14px; }

.theme-docs-article p { margin: 0 0 1.2em; }

.theme-docs-article a { color: #2271b1; text-decoration: none; }
.theme-docs-article a:hover { text-decoration: underline; }

.theme-docs-article strong { font-weight: 600; }

.theme-docs-article code {
	background: #f0f0f1;
	color: #b53939;
	padding: 2px 5px;
	border-radius: 3px;
	font-size: .88em;
	font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
}

.theme-docs-article pre {
	background: #1d2327;
	border-radius: 6px;
	overflow-x: auto;
	padding: 20px 24px;
	margin: 0 0 1.5em;
	line-height: 1.6;
}

.theme-docs-article pre code {
	background: none;
	color: #e2e4e7;
	padding: 0;
	border-radius: 0;
	font-size: 13px;
}

.theme-docs-article blockquote {
	border-left: 4px solid #72aee6;
	margin: 0 0 1.2em;
	padding: 12px 20px;
	background: #f6f8fa;
	color: #50575e;
	border-radius: 0 4px 4px 0;
}

.theme-docs-article blockquote p:last-child { margin-bottom: 0; }

.theme-docs-article ul,
.theme-docs-article ol {
	margin: 0 0 1.2em 1.5em;
	padding: 0;
}

.theme-docs-article li { margin-bottom: .35em; }

.theme-docs-article hr {
	border: none;
	border-top: 1px solid #e2e4e7;
	margin: 2em 0;
}

.theme-docs-article img {
	max-width: 100%;
	height: auto;
	border-radius: 4px;
	box-shadow: 0 1px 4px rgba(0,0,0,.12);
	margin: .5em 0;
}

.theme-docs-article table {
	width: 100%;
	border-collapse: collapse;
	margin: 0 0 1.5em;
	font-size: 13px;
}

.theme-docs-article th,
.theme-docs-article td {
	text-align: left;
	padding: 8px 14px;
	border: 1px solid #e2e4e7;
}

.theme-docs-article th {
	background: #f6f8fa;
	font-weight: 600;
}

.theme-docs-article tr:nth-child(even) td { background: #fafafa; }

.theme-docs-article del { color: #8c8f94; }

/* In-page TOC ----------------------------------------------- */
.theme-docs-toc {
	background: #f6f8fa;
	border: 1px solid #e2e4e7;
	border-radius: 6px;
	padding: 16px 20px;
	margin-bottom: 2em;
	font-size: 13px;
}

.theme-docs-toc__label {
	margin: 0 0 8px;
	font-weight: 600;
	font-size: 11px;
	text-transform: uppercase;
	letter-spacing: .06em;
	color: #8c8f94;
}

.theme-docs-toc ul {
	list-style: none;
	margin: 0;
	padding: 0;
}

.theme-docs-toc li { margin-bottom: 4px; }
.theme-docs-toc .toc-h3 { padding-left: 16px; }

.theme-docs-toc a {
	color: #2271b1;
	text-decoration: none;
}

.theme-docs-toc a:hover { text-decoration: underline; }

/* Error state ----------------------------------------------- */
.theme-docs-error {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px 20px;
	background: #fcf0f1;
	border-left: 4px solid #d63638;
	border-radius: 0 4px 4px 0;
	color: #d63638;
	font-size: 13px;
}

/* Empty state ----------------------------------------------- */
.theme-docs-empty {
	text-align: center;
	padding: 60px 20px;
	color: #8c8f94;
}

.theme-docs-empty .dashicons {
	font-size: 48px;
	width: 48px;
	height: 48px;
	color: #c3c4c7;
	margin-bottom: 16px;
}

.theme-docs-empty h2 { color: #3c434a; }
.theme-docs-empty code {
	background: #f0f0f1;
	padding: 2px 6px;
	border-radius: 3px;
	font-size: 12px;
}

/* Responsive tweaks ----------------------------------------- */
@media (max-width: 960px) {
	.theme-docs-sidebar { width: 220px; }
	.theme-docs-content__inner { padding: 24px; }
}

@media (max-width: 700px) {
	#wpbody-content .theme-docs-wrap { flex-direction: column; height: auto; }
	.theme-docs-sidebar { width: 100%; max-width: none; max-height: 50vh; }
	.theme-docs-content { max-height: none; }
}
CSS;
	}
}
