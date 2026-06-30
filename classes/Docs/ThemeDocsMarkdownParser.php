<?php

namespace Origin\Docs;

/**
 * Theme Docs — Markdown Parser
 *
 * Lightweight, self-contained Markdown-to-HTML converter.
 * Handles headings, lists, code blocks, blockquotes, tables,
 * inline formatting, links, and images.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ThemeDocsMarkdownParser {

	/** Stores fenced code blocks while processing block-level elements */
	private array $code_placeholders = [];

	/** Stores inline code spans while processing inline elements */
	private array $inline_code_placeholders = [];

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	public static function parse( string $markdown ): string {
		return ( new self() )->convert( $markdown );
	}

	// -------------------------------------------------------------------------
	// Core conversion pipeline
	// -------------------------------------------------------------------------

	private function convert( string $text ): string {
		$text = $this->normalize_line_endings( $text );
		$text = $this->extract_fenced_code_blocks( $text );
		$text = $this->extract_indented_code_blocks( $text );
		$text = $this->process_block_elements( $text );
		$text = $this->restore_placeholders( $text );

		return trim( $text );
	}

	// -------------------------------------------------------------------------
	// Normalization
	// -------------------------------------------------------------------------

	private function normalize_line_endings( string $text ): string {
		return str_replace( [ "\r\n", "\r" ], "\n", $text );
	}

	// -------------------------------------------------------------------------
	// Code blocks (extracted early to protect content from inline parsing)
	// -------------------------------------------------------------------------

	private function extract_fenced_code_blocks( string $text ): string {
		return preg_replace_callback(
			'/^(`{3,}|~{3,})(\w*)\n(.*?)\n\1\s*$/ms',
			function ( $m ) {
				$lang    = $m[2] ? ' class="language-' . esc_attr( $m[2] ) . '"' : '';
				$code    = esc_html( $m[3] );
				$ph      = $this->store_placeholder( "<pre><code{$lang}>{$code}</code></pre>", 'CODE' );
				return "\n\n{$ph}\n\n";
			},
			$text
		);
	}

	private function extract_indented_code_blocks( string $text ): string {
		return preg_replace_callback(
			'/(?:^|\n\n)((?:(?:    |\t)[^\n]*\n?)+)/',
			function ( $m ) {
				$code = preg_replace( '/^(?:    |\t)/m', '', $m[1] );
				$code = esc_html( rtrim( $code ) );
				$ph   = $this->store_placeholder( "<pre><code>{$code}</code></pre>", 'CODE' );
				return "\n\n{$ph}\n\n";
			},
			$text
		);
	}

	// -------------------------------------------------------------------------
	// Block-level processing
	// -------------------------------------------------------------------------

	private function process_block_elements( string $text ): string {
		$text = $this->process_horizontal_rules( $text );
		$text = $this->process_tables( $text );
		$text = $this->process_blockquotes( $text );
		$text = $this->process_lists( $text );
		$text = $this->process_headings( $text );
		$text = $this->process_paragraphs( $text );

		return $text;
	}

	private function process_horizontal_rules( string $text ): string {
		return preg_replace( '/^[ \t]*(?:[-*_][ \t]*){3,}$/m', '<hr>', $text );
	}

	private function process_headings( string $text ): string {
		// ATX headings: # Heading
		$text = preg_replace_callback(
			'/^(#{1,6})[ \t]+(.+?)[ \t]*#*\s*$/m',
			function ( $m ) {
				$level = strlen( $m[1] );
				$inner = $this->parse_inline( trim( $m[2] ) );
				$id    = $this->heading_id( $m[2] );
				return "<h{$level} id=\"{$id}\">{$inner}</h{$level}>";
			},
			$text
		);

		// Setext headings: underlined with === or ---
		$text = preg_replace_callback(
			'/^(.+)\n(=+|-+)\s*$/m',
			function ( $m ) {
				$level = ( $m[2][0] === '=' ) ? 1 : 2;
				$inner = $this->parse_inline( trim( $m[1] ) );
				$id    = $this->heading_id( $m[1] );
				return "<h{$level} id=\"{$id}\">{$inner}</h{$level}>";
			},
			$text
		);

		return $text;
	}

	private function process_blockquotes( string $text ): string {
		return preg_replace_callback(
			'/(?:^>[ \t]?.+\n?)+/m',
			function ( $m ) {
				$inner = preg_replace( '/^>[ \t]?/m', '', $m[0] );
				$inner = $this->convert( $inner );
				return "<blockquote>\n{$inner}\n</blockquote>";
			},
			$text
		);
	}

	/**
	 * Handles nested ordered and unordered lists.
	 */
	private function process_lists( string $text ): string {
		// Unordered list items: -, *, +
		$text = $this->build_list_html( $text, 'ul', '/^([ \t]*)[-*+] (.+)$/m' );

		// Ordered list items: 1. 2. etc.
		$text = $this->build_list_html( $text, 'ol', '/^([ \t]*)\d+\. (.+)$/m' );

		return $text;
	}

	private function build_list_html( string $text, string $tag, string $pattern ): string {
		return preg_replace_callback(
			// Match a block of consecutive list items (same list type)
			str_replace( '(.+)$', '(.+(?:\n(?:[ \t]*.+))*)', $pattern ),
			function ( $m ) use ( $tag, $pattern ) {
				$lines = explode( "\n", $m[0] );
				$html  = "<{$tag}>\n";
				foreach ( $lines as $line ) {
					if ( preg_match( $pattern, $line, $lm ) ) {
						$html .= '<li>' . $this->parse_inline( trim( $lm[2] ) ) . "</li>\n";
					}
				}
				$html .= "</{$tag}>";
				return $html;
			},
			$text
		);
	}

	/**
	 * Basic GFM-style pipe tables.
	 *
	 * | Col A | Col B |
	 * |-------|-------|
	 * | val   | val   |
	 */
	private function process_tables( string $text ): string {
		return preg_replace_callback(
			'/^(\|.+\|[ \t]*\n)([ \t]*\|[ \t]*[-:]+[-| \t:]*\n)((?:\|.+\|\n?)*)/m',
			function ( $m ) {
				// Header row
				$headers = $this->split_table_row( $m[1] );
				// Alignment row
				$aligns  = $this->parse_table_alignments( $m[2] );
				// Body rows
				$rows    = array_filter( explode( "\n", trim( $m[3] ) ) );

				$html  = "<table>\n<thead>\n<tr>";
				foreach ( $headers as $i => $cell ) {
					$align = $aligns[ $i ] ?? '';
					$attr  = $align ? " style=\"text-align:{$align}\"" : '';
					$html .= "<th{$attr}>" . $this->parse_inline( $cell ) . "</th>";
				}
				$html .= "</tr>\n</thead>\n<tbody>\n";

				foreach ( $rows as $row ) {
					$cells = $this->split_table_row( $row );
					$html .= '<tr>';
					foreach ( $cells as $i => $cell ) {
						$align = $aligns[ $i ] ?? '';
						$attr  = $align ? " style=\"text-align:{$align}\"" : '';
						$html .= "<td{$attr}>" . $this->parse_inline( $cell ) . "</td>";
					}
					$html .= "</tr>\n";
				}

				$html .= "</tbody>\n</table>";
				return $html;
			},
			$text
		);
	}

	private function split_table_row( string $row ): array {
		$row  = trim( $row, " \t|" );
		$cols = explode( '|', $row );
		return array_map( 'trim', $cols );
	}

	private function parse_table_alignments( string $separator_row ): array {
		$cols   = $this->split_table_row( $separator_row );
		$aligns = [];
		foreach ( $cols as $col ) {
			$col = trim( $col );
			if ( str_starts_with( $col, ':' ) && str_ends_with( $col, ':' ) ) {
				$aligns[] = 'center';
			} elseif ( str_ends_with( $col, ':' ) ) {
				$aligns[] = 'right';
			} elseif ( str_starts_with( $col, ':' ) ) {
				$aligns[] = 'left';
			} else {
				$aligns[] = '';
			}
		}
		return $aligns;
	}

	private function process_paragraphs( string $text ): string {
		$blocks = preg_split( '/\n{2,}/', $text );

		$out = [];
		foreach ( $blocks as $block ) {
			$block = trim( $block );
			if ( $block === '' ) {
				continue;
			}

			// Skip blocks that are already wrapped in block-level HTML or are placeholders
			$is_block_html   = preg_match( '/^<(?:h[1-6]|ul|ol|blockquote|pre|table|hr|div|figure)[\s>\/]/i', $block );
			$is_placeholder  = preg_match( '/^\x02[A-Z]+\d+\x03$/', $block );

			if ( $is_block_html || $is_placeholder ) {
				$out[] = $block;
			} else {
				// Convert single newlines within a paragraph to <br> for hard wraps
				$inner = preg_replace( '/(?<!\n)\n(?!\n)/', " \n", $block );
				$out[] = '<p>' . $this->parse_inline( $inner ) . '</p>';
			}
		}

		return implode( "\n", $out );
	}

	// -------------------------------------------------------------------------
	// Inline parsing
	// -------------------------------------------------------------------------

	private function parse_inline( string $text ): string {
		$text = $this->extract_inline_code( $text );
		$text = $this->parse_images( $text );
		$text = $this->parse_links( $text );
		$text = $this->parse_bold_italic( $text );
		$text = $this->parse_strikethrough( $text );
		$text = $this->parse_hard_breaks( $text );
		$text = $this->restore_inline_placeholders( $text );

		return $text;
	}

	private function extract_inline_code( string $text ): string {
		return preg_replace_callback(
			'/(`+)(.+?)\1/s',
			function ( $m ) {
				$code = esc_html( $m[2] );
				$ph   = $this->store_placeholder( "<code>{$code}</code>", 'ICODE' );
				return $ph;
			},
			$text
		);
	}

	private function parse_images( string $text ): string {
		// ![alt](url "optional title")
		return preg_replace_callback(
			'/!\[([^\]]*)\]\(([^\s)]+)(?:\s+"([^"]*)")?\)/',
			function ( $m ) {
				$alt   = esc_attr( $m[1] );
				$src   = esc_url( $m[2] );
				$title = isset( $m[3] ) ? ' title="' . esc_attr( $m[3] ) . '"' : '';
				return "<img src=\"{$src}\" alt=\"{$alt}\"{$title}>";
			},
			$text
		);
	}

	private function parse_links( string $text ): string {
		// [text](url "optional title")
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^\s)]+)(?:\s+"([^"]*)")?\)/',
			function ( $m ) {
				$inner = $m[1];
				$href  = esc_url( $m[2] );
				$title = isset( $m[3] ) ? ' title="' . esc_attr( $m[3] ) . '"' : '';
				// Open external links in new tab
				$ext   = ( strpos( $m[2], 'http' ) === 0 ) ? ' target="_blank" rel="noopener noreferrer"' : '';
				return "<a href=\"{$href}\"{$title}{$ext}>{$inner}</a>";
			},
			$text
		);

		// Bare URLs: <https://example.com>
		$text = preg_replace_callback(
			'/<(https?:[^>]+)>/',
			function ( $m ) {
				$href = esc_url( $m[1] );
				return "<a href=\"{$href}\" target=\"_blank\" rel=\"noopener noreferrer\">{$href}</a>";
			},
			$text
		);

		return $text;
	}

	private function parse_bold_italic( string $text ): string {
		// Bold + Italic: ***text*** or ___text___
		$text = preg_replace( '/\*{3}(.+?)\*{3}/', '<strong><em>$1</em></strong>', $text );
		$text = preg_replace( '/_{3}(.+?)_{3}/',   '<strong><em>$1</em></strong>', $text );
		// Bold: **text** or __text__
		$text = preg_replace( '/\*{2}(.+?)\*{2}/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/_{2}(.+?)_{2}/',   '<strong>$1</strong>', $text );
		// Italic: *text* or _text_
		$text = preg_replace( '/\*(.+?)\*/', '<em>$1</em>', $text );
		$text = preg_replace( '/(?<!\w)_(.+?)_(?!\w)/', '<em>$1</em>', $text );

		return $text;
	}

	private function parse_strikethrough( string $text ): string {
		return preg_replace( '/~~(.+?)~~/', '<del>$1</del>', $text );
	}

	private function parse_hard_breaks( string $text ): string {
		// Two spaces + newline = <br>
		return preg_replace( '/  \n/', "<br>\n", $text );
	}

	// -------------------------------------------------------------------------
	// Placeholder helpers
	// -------------------------------------------------------------------------

	private function store_placeholder( string $html, string $type ): string {
		$index = count( $this->code_placeholders );
		$ph    = "\x02{$type}{$index}\x03";
		$this->code_placeholders[ $ph ] = $html;
		return $ph;
	}

	private function restore_placeholders( string $text ): string {
		return str_replace(
			array_keys( $this->code_placeholders ),
			array_values( $this->code_placeholders ),
			$text
		);
	}

	private function restore_inline_placeholders( string $text ): string {
		// Inline code placeholders are stored in code_placeholders too
		return $this->restore_placeholders( $text );
	}

	// -------------------------------------------------------------------------
	// Heading ID helper (slug-style, WP-friendly)
	// -------------------------------------------------------------------------

	private function heading_id( string $text ): string {
		$text = strtolower( $text );
		$text = preg_replace( '/[^\w\s-]/', '', $text );
		$text = preg_replace( '/[\s_]+/', '-', $text );
		$text = trim( $text, '-' );
		return $text;
	}
}
