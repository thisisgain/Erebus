<?php
/**
 * Admin Columns Manager
 *
 * Register custom columns on WordPress post type list tables with full ACF support.
 * Supports sorting, filter dropdowns, click-to-filter, and composite (multi-field) columns.
 *
 * Field types:  text | image | true_false | taxonomy | date | composite | object
 *
 * ── Single-field examples ────────────────────────────────────────────────────
 *
 *   $cols = new Admin_Columns( 'film' );
 *   $cols->add( 'poster',       'Poster',   'image' );
 *   $cols->add( 'subtitle',     'Subtitle', 'text',      [ 'filterable' => true, 'clickable' => true ] );
 *   $cols->add( 'is_featured',  'Featured', 'true_false' );
 *   $cols->add( 'release_date', 'Released', 'date',      [ 'filter_by' => 'year', 'clickable' => true ] );
 *   $cols->add( 'genre',        'Genre',    'taxonomy',  [ 'taxonomy' => 'genre' ] );
 *   $cols->register();
 *
 * ── Composite (multi-field) column ───────────────────────────────────────────
 *
 * Pass an array as the first argument. Each element describes one ACF field:
 *
 *   $cols->add(
 *       [
 *           [ 'key' => 'event_date', 'type' => 'date', 'storage_format' => 'Ymd', 'display_format' => 'j M Y' ],
 *           [ 'key' => 'event_time', 'type' => 'text' ],
 *       ],
 *       'Date & Time',
 *       'composite',
 *       [
 *           'template'     => '{event_date} at {event_time}',
 *           //  — OR —
 *           // 'separator' => ' at ',
 *
 *           // Sort by a single field:
 *           'sort_field'  => 'event_date',
 *           //  — OR sort by multiple fields in order (WP 4.2+):
 *           // 'sort_fields' => [ 'event_date' => 'ASC', 'event_time' => 'ASC' ],
 *
 *           'filter_field' => 'event_date',
 *           'filter_type'  => 'date',
 *           'filter_by'    => 'month',
 *           'sortable'     => true,
 *           'filterable'   => true,
 *       ]
 *   );
 *
 * ── Options reference ────────────────────────────────────────────────────────
 *
 *  All types
 *    sortable   bool  Column header is clickable to sort.         Default: type-specific.
 *    filterable bool  Show a filter dropdown above the table.     Default: type-specific.
 *    clickable  bool  Cell value links to a filtered list view.   Default: false.
 *
 *  text
 *    max_length  int   Truncate after N chars (0 = off). Default: 0.
 *
 *  image
 *    size    string  WP size slug. Default: 'thumbnail'.
 *    width   int     px. Default: 80.   height  int  px. Default: 80.
 *
 *  true_false
 *    true_label / false_label          string  Cell glyph. Defaults: ✔ / —.
 *    filter_true_label / filter_false_label  string  Dropdown labels. Defaults: Yes / No.
 *
 *  taxonomy
 *    taxonomy   string  Required. Slug.
 *    separator  string  Between multiple terms. Default: ', '.
 *    link       bool    Native WP filter link (when clickable is false). Default: true.
 *
 *  date
 *    display_format  string  PHP date(). Default: 'd/m/Y'.
 *    storage_format  string  PHP date() ACF uses in DB. Default: 'Ymd'.
 *    filter_by       string  'year' | 'month'. Default: 'year'.
 *
 *  composite
 *    template     string  Token string e.g. '{event_date} at {event_time}'.
 *    separator    string  Fallback join string when no template set. Default: ' '.
 *    sort_field   string  ACF key of the field that drives the sort.
 *    sort_fields  array   [ acf_key => 'ASC'|'DESC', ... ] for multi-key sort.
 *    filter_field string  ACF key whose values populate the filter widget.
 *    filter_type  string  Widget type: 'text'|'date'|'true_false'|'taxonomy'. Default: 'text'.
 *    filter_by    string  For date filter_type: 'year'|'month'. Default: 'year'.
 *
 *  object  ── for ACF fields that return objects or associative arrays ───────
 *
 *    sub_key      string|callable  Required. Dot-notation path into each returned item,
 *                                  or a callable that receives the item and returns a string.
 *                                  Examples:
 *                                    'post_title'               → $item->post_title / $item['post_title']
 *                                    'author.display_name'      → $item['author']['display_name']
 *                                    fn($p) => $p->post_title   → full control via callable
 *
 *    separator    string    Glue when multiple items are returned. Default: ', '.
 *    max_items    int       Cap the number of displayed items (0 = unlimited). Default: 0.
 *    max_length   int       Truncate each resolved string after N chars (0 = off). Default: 0.
 *    link         bool      Wrap each item in a hyperlink. Default: false.
 *    link_sub_key string    Dot-notation path (or magic value) that resolves to the href.
 *                           Magic values for WP_Post items:
 *                             'permalink' → get_permalink( $item->ID )
 *                             'edit_link' → get_edit_post_link( $item->ID )
 *
 *    Sorting and filtering are intentionally disabled by default for object
 *    columns because ACF stores the raw values (e.g. post IDs) rather than
 *    the resolved display text. Enable with 'sortable' => true if your use
 *    case supports raw-value ordering.
 *
 *  ── object examples ─────────────────────────────────────────────────────────
 *
 *    // Relationship field — titles with edit links, capped at 3
 *    $cols->add( 'related_posts', 'Related', 'object', [
 *        'sub_key'      => 'post_title',
 *        'link'         => true,
 *        'link_sub_key' => 'edit_link',
 *        'max_items'    => 3,
 *    ] );
 *
 *    // ACF link field — show title, link to url
 *    $cols->add( 'cta_link', 'CTA', 'object', [
 *        'sub_key'      => 'title',
 *        'link'         => true,
 *        'link_sub_key' => 'url',
 *    ] );
 *
 *    // User field — display name
 *    $cols->add( 'assigned_user', 'Assigned', 'object', [
 *        'sub_key' => 'display_name',
 *    ] );
 *
 *    // Callable — full control
 *    $cols->add( 'event_ref', 'Event', 'object', [
 *        'sub_key' => fn( WP_Post $p ) => $p->post_title . ' (' . get_the_date( 'd/m/Y', $p ) . ')',
 *    ] );
 *
 *    // Dot-notation into a nested array/object
 *    $cols->add( 'map_location', 'Location', 'object', [
 *        'sub_key' => 'address',           // ACF Google Map field → $value['address']
 *    ] );
 *
 *    // Object sub-field inside a composite column
 *    $cols->add(
 *        [
 *            [ 'key' => 'speaker', 'type' => 'object', 'sub_key' => 'post_title' ],
 *            [ 'key' => 'talk_date', 'type' => 'date', 'storage_format' => 'Ymd', 'display_format' => 'j M Y' ],
 *        ],
 *        'Speaker & Date', 'composite',
 *        [ 'template' => '{speaker} — {talk_date}', 'sort_field' => 'talk_date' ]
 *    );
 *
 * @package Gain
 */

namespace Origin\WordPress;
use WP_Query;
use DateTime;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminColumns {

	// -------------------------------------------------------------------------
	// Properties
	// -------------------------------------------------------------------------

	/** @var string Post type slug this instance manages. */
	private string $post_type;

	/**
	 * Column definitions keyed by column ID ('acf_col_<key>' or 'acf_col_<key1>__<key2>').
	 *
	 * @var array<string, array{
	 *   acf_key: string|list<string|array>,
	 *   fields:  list<array{key:string,type:string,options:array}>,
	 *   label:   string,
	 *   type:    string,
	 *   options: array,
	 *   position: int|null
	 * }>
	 */
	private array $columns = [];

	/** @var string[] */
	private array $supported_types = [ 'text', 'image', 'true_false', 'taxonomy', 'date', 'composite', 'object' ];

	/** @var array<string, array> Per-type defaults; user options are merged on top. */
	private array $defaults = [
		'text'       => [
			'max_length' => 0,
			'sortable'   => true,
			'filterable' => false,
			'clickable'  => false,
		],
		'image'      => [
			'size'       => 'thumbnail',
			'width'      => 80,
			'height'     => 80,
			'sortable'   => false,
			'filterable' => false,
			'clickable'  => false,
		],
		'true_false' => [
			'true_label'         => "\xE2\x9C\x94",   // ✔
			'false_label'        => "\xE2\x80\x94",   // —
			'filter_true_label'  => 'Yes',
			'filter_false_label' => 'No',
			'sortable'           => true,
			'filterable'         => true,
			'clickable'          => true,
		],
		'taxonomy'   => [
			'taxonomy'   => '',
			'separator'  => ', ',
			'link'       => true,
			'sortable'   => false,
			'filterable' => true,
			'clickable'  => true,
		],
		'date'       => [
			'display_format' => 'd/m/Y',
			'storage_format' => 'Ymd',
			'filter_by'      => 'year',
			'sortable'       => true,
			'filterable'     => true,
			'clickable'      => false,
		],
		'composite'  => [
			'template'     => '',
			'separator'    => ' ',
			'sort_field'   => '',
			'sort_fields'  => [],
			'filter_field' => '',
			'filter_type'  => 'text',
			'filter_by'    => 'year',
			'sortable'     => true,
			'filterable'   => false,
			'clickable'    => false,
		],
		'object'     => [
			'sub_key'      => '',      // Required: dot-notation string or callable
			'separator'    => ', ',
			'max_items'    => 0,       // 0 = no cap
			'max_length'   => 0,       // 0 = no truncation per item
			'link'         => false,
			'link_sub_key' => '',      // Magic: 'permalink' | 'edit_link' | dot-notation path
			'sortable'     => false,
			'filterable'   => false,
			'clickable'    => false,
		],
	];

	// -------------------------------------------------------------------------
	// Constructor
	// -------------------------------------------------------------------------

	/** @param string $post_type  Slug e.g. 'post', 'page', 'film'. */
	public function __construct( string $post_type ) {
		$this->post_type = sanitize_key( $post_type );
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Register a column.
	 *
	 * @param string|list<string|array{key:string,type?:string}> $acf_key
	 *   Single ACF field name, OR an array of field definitions for a composite column.
	 *   Composite format: [ 'key' => 'my_field', 'type' => 'date', ...display options ]
	 *   Simple shorthand: 'my_field'  (type defaults to 'text')
	 *
	 * @param string   $label     Column header label.
	 * @param string   $type      text|image|true_false|taxonomy|date|composite.
	 *                            Auto-set to 'composite' when $acf_key is an array.
	 * @param array    $options   Option overrides — see file header.
	 * @param int|null $position  Zero-based insertion index. Null = append.
	 *
	 * @return static  Fluent.
	 * @throws InvalidArgumentException On unsupported type.
	 */
	public function add(
		string|array $acf_key,
		string $label,
		string $type = 'text',
		array $options = [],
		?int $position = null
	): static {

		// Auto-detect composite when an array of fields is supplied.
		if ( is_array( $acf_key ) ) {
			$type = 'composite';
		}

		if ( ! in_array( $type, $this->supported_types, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Admin_Columns: unsupported type "%s". Supported: %s.',
					$type,
					implode( ', ', $this->supported_types )
				)
			);
		}

		$column_id = $this->make_column_id( $acf_key );
		$fields    = ( 'composite' === $type ) ? $this->normalize_composite_fields( (array) $acf_key ) : [];

		$this->columns[ $column_id ] = [
			'acf_key'  => $acf_key,
			'fields'   => $fields,
			'label'    => $label,
			'type'     => $type,
			'options'  => array_merge( $this->defaults[ $type ] ?? [], $options ),
			'position' => $position,
		];

		return $this;
	}

	/**
	 * Remove a previously registered column.
	 *
	 * @param string|array $acf_key  Same value passed to add().
	 * @return static
	 */
	public function remove( string|array $acf_key ): static {
		unset( $this->columns[ $this->make_column_id( $acf_key ) ] );
		return $this;
	}

	/**
	 * Attach all WordPress hooks. Call once after all add() calls.
	 */
	public function register(): void {
		if ( empty( $this->columns ) ) {
			return;
		}

		$hook = ( 'post' === $this->post_type ) ? 'post' : $this->post_type;

		add_filter( "manage_{$hook}_posts_columns",         [ $this, '_filter_columns'   ] );
		add_action( "manage_{$hook}_posts_custom_column",   [ $this, '_render_column'    ], 10, 2 );
		add_filter( "manage_edit-{$hook}_sortable_columns", [ $this, '_sortable_columns' ] );
		add_action( 'restrict_manage_posts',                [ $this, '_render_filters'   ], 10, 1 );
		add_action( 'pre_get_posts',                        [ $this, '_handle_query'     ] );
	}

	// -------------------------------------------------------------------------
	// WordPress hooks  (public for WP callbacks; treat as internal)
	// -------------------------------------------------------------------------

	/** @internal */
	public function _filter_columns( array $existing ): array {
		foreach ( $this->columns as $column_id => $config ) {
			if ( null === $config['position'] ) {
				$existing[ $column_id ] = $config['label'];
			} else {
				$existing = $this->array_insert_at( $existing, $column_id, $config['label'], $config['position'] );
			}
		}
		return $existing;
	}

	/** @internal */
	public function _sortable_columns( array $sortable ): array {
		foreach ( $this->columns as $column_id => $config ) {
			if ( ! empty( $config['options']['sortable'] ) ) {
				$sortable[ $column_id ] = [ $column_id, false ]; // false = first click ASC
			}
		}
		return $sortable;
	}

	/** @internal */
	public function _render_column( string $column_id, int $post_id ): void {
		if ( ! isset( $this->columns[ $column_id ] ) ) {
			return;
		}

		$config = $this->columns[ $column_id ];
		$opts   = $config['options'];

		switch ( $config['type'] ) {
			case 'composite':
				$this->render_composite( $config['fields'], $opts, $column_id, $post_id );
				break;
			default:
				$value = $this->get_field_value( $config['acf_key'], $post_id );
				switch ( $config['type'] ) {
					case 'text':       $this->render_text(       $value, $opts, $column_id ); break;
					case 'image':      $this->render_image(      $value, $opts             ); break;
					case 'true_false': $this->render_true_false( $value, $opts, $column_id ); break;
					case 'taxonomy':   $this->render_taxonomy(   $value, $post_id, $opts, $column_id ); break;
					case 'date':       $this->render_date(       $value, $opts, $column_id ); break;
					case 'object':     $this->render_object(     $value, $opts             ); break;
				}
				break;
		}
	}

	/** @internal */
	public function _render_filters( string $post_type ): void {
		if ( $post_type !== $this->post_type ) {
			return;
		}

		foreach ( $this->columns as $column_id => $config ) {
			if ( empty( $config['options']['filterable'] ) ) {
				continue;
			}

			$var     = $this->filter_var( $column_id );
			$current = sanitize_text_field( $_GET[ $var ] ?? '' );

			if ( 'composite' === $config['type'] ) {
				$this->render_composite_filter( $column_id, $config, $var, $current );
			} else {
				$this->dispatch_filter_renderer( $config['type'], $column_id, $config, $var, $current );
			}
		}
	}

	/** @internal */
	public function _handle_query( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== $this->post_type ) {
			return;
		}

		$this->apply_sorting( $query );
		$this->apply_filtering( $query );
	}

	// -------------------------------------------------------------------------
	// Sorting
	// -------------------------------------------------------------------------

	private function apply_sorting( WP_Query $query ): void {
		$orderby = $query->get( 'orderby' );

		if ( empty( $orderby ) || ! isset( $this->columns[ $orderby ] ) ) {
			return;
		}

		$config = $this->columns[ $orderby ];
		if ( empty( $config['options']['sortable'] ) ) {
			return;
		}

		if ( 'composite' === $config['type'] ) {
			$this->apply_composite_sort( $query, $config );
		} else {
			$query->set( 'meta_key', $config['acf_key'] );
			$query->set( 'orderby', ( 'true_false' === $config['type'] ) ? 'meta_value_num' : 'meta_value' );
		}
	}

	/**
	 * Sort a composite column.
	 *
	 * When 'sort_fields' (array) is set: uses named meta_query clauses for multi-key ordering.
	 * When 'sort_field' (string) is set or defaults to the first field: single meta_key sort.
	 */
	private function apply_composite_sort( WP_Query $query, array $config ): void {
		$opts   = $config['options'];
		$fields = $config['fields'];

		// ── Multi-key sort ──────────────────────────────────────────────────
		// sort_fields => [ 'event_date' => 'ASC', 'event_time' => 'ASC' ]
		if ( ! empty( $opts['sort_fields'] ) && is_array( $opts['sort_fields'] ) ) {
			$existing_meta = (array) ( $query->get( 'meta_query' ) ?: [] );
			$named_clauses = [];
			$orderby       = [];

			foreach ( $opts['sort_fields'] as $field_key => $direction ) {
				$clause_key                  = 'acf_sort_' . sanitize_key( $field_key );
				$named_clauses[ $clause_key ] = [ 'key' => $field_key, 'compare' => 'EXISTS' ];
				$orderby[ $clause_key ]       = ( strtoupper( (string) $direction ) === 'DESC' ) ? 'DESC' : 'ASC';
			}

			$query->set( 'meta_query', array_merge( $existing_meta, $named_clauses ) );
			$query->set( 'orderby', $orderby );
			return;
		}

		// ── Single-key sort ─────────────────────────────────────────────────
		$sort_field = (string) ( $opts['sort_field'] ?? '' );
		if ( ! $sort_field && ! empty( $fields ) ) {
			$sort_field = $fields[0]['key'];
		}
		if ( ! $sort_field ) {
			return;
		}

		$query->set( 'meta_key', $sort_field );

		// Detect field type to pick numeric vs string orderby.
		$field_type = '';
		foreach ( $fields as $fd ) {
			if ( $fd['key'] === $sort_field ) {
				$field_type = $fd['type'];
				break;
			}
		}
		$query->set( 'orderby', ( 'true_false' === $field_type ) ? 'meta_value_num' : 'meta_value' );
	}

	// -------------------------------------------------------------------------
	// Filtering
	// -------------------------------------------------------------------------

	private function apply_filtering( WP_Query $query ): void {
		$meta_query = (array) ( $query->get( 'meta_query' ) ?: [] );
		$tax_query  = (array) ( $query->get( 'tax_query' )  ?: [] );
		$dirty      = false;

		foreach ( $this->columns as $column_id => $config ) {
			if ( empty( $config['options']['filterable'] ) && empty( $config['options']['clickable'] ) ) {
				continue;
			}

			$var   = $this->filter_var( $column_id );
			$value = sanitize_text_field( $_GET[ $var ] ?? '' );

			if ( $value === '' || $value === '_all' ) {
				continue;
			}

			if ( 'composite' === $config['type'] ) {
				$this->apply_composite_filter( $config, $value, $meta_query, $tax_query, $dirty );
			} else {
				$this->apply_single_filter( $config['type'], $config['acf_key'], $config['options'], $value, $meta_query, $tax_query, $dirty );
			}
		}

		if ( $dirty ) {
			if ( ! empty( $meta_query ) ) {
				$query->set( 'meta_query', $meta_query );
			}
			if ( ! empty( $tax_query ) ) {
				$query->set( 'tax_query', $tax_query );
			}
		}
	}

	/**
	 * Resolve a composite column's filter_field / filter_type and delegate to the
	 * same logic used by single-field columns.
	 */
	private function apply_composite_filter(
		array $config,
		string $value,
		array &$meta_query,
		array &$tax_query,
		bool &$dirty
	): void {
		$opts         = $config['options'];
		$fields       = $config['fields'];
		$filter_field = (string) ( $opts['filter_field'] ?? '' );
		$filter_type  = (string) ( $opts['filter_type']  ?? 'text' );

		if ( ! $filter_field && ! empty( $fields ) ) {
			$filter_field = $fields[0]['key'];
		}

		if ( ! $filter_field ) {
			return;
		}

		$this->apply_single_filter( $filter_type, $filter_field, $opts, $value, $meta_query, $tax_query, $dirty );
	}

	/**
	 * Build and append meta_query / tax_query clauses for a single-field filter.
	 *
	 * @param string $type     Field type driving the filter logic.
	 * @param string $acf_key  Meta key to query against.
	 * @param array  $opts     Column / composite options (must include filter_by, taxonomy etc.).
	 * @param string $value    Active filter value from the URL param.
	 */
	private function apply_single_filter(
		string $type,
		string $acf_key,
		array $opts,
		string $value,
		array &$meta_query,
		array &$tax_query,
		bool &$dirty
	): void {

		switch ( $type ) {

			case 'text':
				$meta_query[] = [ 'key' => $acf_key, 'value' => $value, 'compare' => '=' ];
				$dirty        = true;
				break;

			case 'true_false':
				$meta_query[] = [ 'key' => $acf_key, 'value' => $value, 'compare' => '=', 'type' => 'NUMERIC' ];
				$dirty        = true;
				break;

			case 'taxonomy':
				$taxonomy = $opts['taxonomy'] ?? '';
				if ( $taxonomy ) {
					$tax_query[] = [ 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $value ];
					$dirty       = true;
				}
				break;

			case 'date':
				// Synthetic config so build_date_filter_clause gets what it needs.
				$clause = $this->build_date_filter_clause(
					[ 'acf_key' => $acf_key, 'options' => $opts ],
					$value
				);
				if ( $clause ) {
					$meta_query[] = $clause;
					$dirty        = true;
				}
				break;
		}
	}

	/**
	 * Build a BETWEEN meta_query clause for a date filter value.
	 * Ymd strings sort lexicographically in chronological order — CHAR is correct.
	 */
	private function build_date_filter_clause( array $config, string $value ): ?array {
		$filter_by = $config['options']['filter_by'] ?? 'year';
		$key       = $config['acf_key'];

		switch ( $filter_by ) {

			case 'year':
				if ( ! preg_match( '/^\d{4}$/', $value ) ) {
					return null;
				}
				return [
					'key'     => $key,
					'value'   => [ $value . '0101', $value . '1231' ],
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				];

			case 'month':
				if ( ! preg_match( '/^\d{6}$/', $value ) ) {
					return null;
				}
				$year     = (int) substr( $value, 0, 4 );
				$month    = (int) substr( $value, 4, 2 );
				$last_day = (int) ( new DateTime( "{$year}-{$month}-01" ) )->format( 't' );
				return [
					'key'     => $key,
					'value'   => [ $value . '01', $value . sprintf( '%02d', $last_day ) ],
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				];

			default:
				return null;
		}
	}

	// -------------------------------------------------------------------------
	// Cell renderers (single-field)
	// -------------------------------------------------------------------------

	private function render_text( mixed $value, array $opts, string $column_id ): void {
		if ( (string) $value === '' ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$text = wp_strip_all_tags( (string) $value );
		if ( ! empty( $opts['max_length'] ) && mb_strlen( $text ) > (int) $opts['max_length'] ) {
			$text = mb_substr( $text, 0, (int) $opts['max_length'] ) . "\xE2\x80\xA6";
		}

		if ( ! empty( $opts['clickable'] ) ) {
			printf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $this->filter_url( $column_id, (string) $value ) ),
				esc_attr( 'Filter by: ' . $text ),
				esc_html( $text )
			);
		} else {
			echo esc_html( $text );
		}
	}

	private function render_image( mixed $value, array $opts ): void {
		if ( empty( $value ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$size   = $opts['size']   ?? 'thumbnail';
		$width  = (int) ( $opts['width']  ?? 80 );
		$height = (int) ( $opts['height'] ?? 80 );
		$style  = 'object-fit:cover;border-radius:3px;';

		if ( is_array( $value ) && ! empty( $value['ID'] ) ) {
			$id = (int) $value['ID'];
		} elseif ( is_numeric( $value ) ) {
			$id = (int) $value;
		} elseif ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			printf(
				'<img src="%s" width="%d" height="%d" style="%s" loading="lazy" alt="" />',
				esc_url( $value ), $width, $height, esc_attr( $style )
			);
			return;
		} else {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		echo wp_get_attachment_image( $id, $size, false, [ 'width' => $width, 'height' => $height, 'style' => $style ] )
			?: '<span aria-hidden="true">&#8212;</span>';
	}

	private function render_true_false( mixed $value, array $opts, string $column_id ): void {
		$is_true = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		$inner   = sprintf(
			'<span style="color:%s;font-size:1.2em;" aria-label="%s">%s</span>',
			esc_attr( $is_true ? '#1e8e3e' : '#999' ),
			esc_attr( $is_true ? 'Yes' : 'No' ),
			esc_html( $is_true ? $opts['true_label'] : $opts['false_label'] )
		);

		if ( ! empty( $opts['clickable'] ) ) {
			printf(
				'<a href="%s" title="%s" style="text-decoration:none;">%s</a>',
				esc_url( $this->filter_url( $column_id, $is_true ? '1' : '0' ) ),
				esc_attr( 'Filter by: ' . ( $is_true ? 'Yes' : 'No' ) ),
				$inner
			);
		} else {
			echo $inner;
		}
	}

	private function render_taxonomy( mixed $value, int $post_id, array $opts, string $column_id ): void {
		$taxonomy  = $opts['taxonomy']  ?? '';
		$separator = $opts['separator'] ?? ', ';
		$clickable = ! empty( $opts['clickable'] );
		$link      = $opts['link'] ?? true;

		$terms = [];
		if ( ! empty( $value ) ) {
			foreach ( (array) $value as $item ) {
				if ( $item instanceof WP_Term ) {
					$terms[] = $item;
				} elseif ( is_numeric( $item ) && $taxonomy ) {
					$term = get_term( (int) $item, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$terms[] = $term;
					}
				}
			}
		}

		if ( empty( $terms ) && $taxonomy ) {
			$fetched = get_the_terms( $post_id, $taxonomy );
			if ( ! is_wp_error( $fetched ) && ! empty( $fetched ) ) {
				$terms = $fetched;
			}
		}

		if ( empty( $terms ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$parts = [];
		foreach ( $terms as $term ) {
			if ( ! ( $term instanceof WP_Term ) ) {
				continue;
			}
			if ( $clickable ) {
				$parts[] = sprintf( '<a href="%s">%s</a>', esc_url( $this->filter_url( $column_id, $term->slug ) ), esc_html( $term->name ) );
			} elseif ( $link ) {
				$url     = add_query_arg( [ 'post_type' => $this->post_type, $taxonomy => $term->slug ], admin_url( 'edit.php' ) );
				$parts[] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $term->name ) );
			} else {
				$parts[] = esc_html( $term->name );
			}
		}

		echo implode( esc_html( $separator ), $parts );
	}

	private function render_date( mixed $value, array $opts, string $column_id ): void {
		if ( empty( $value ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$date = DateTime::createFromFormat( $opts['storage_format'] ?? 'Ymd', (string) $value );
		if ( ! $date ) {
			echo esc_html( $value );
			return;
		}

		$formatted = esc_html( $date->format( $opts['display_format'] ?? 'd/m/Y' ) );

		if ( ! empty( $opts['clickable'] ) ) {
			$filter_val = ( ( $opts['filter_by'] ?? 'year' ) === 'month' ) ? $date->format( 'Ym' ) : $date->format( 'Y' );
			printf(
				'<a href="%s" title="%s">%s</a>',
				esc_url( $this->filter_url( $column_id, $filter_val ) ),
				esc_attr( 'Filter by this ' . ( $opts['filter_by'] ?? 'year' ) ),
				$formatted
			);
		} else {
			echo $formatted;
		}
	}

	// -------------------------------------------------------------------------
	// Object cell renderer
	// -------------------------------------------------------------------------

	/**
	 * Render an ACF field that returns an object, an associative array, or a
	 * collection thereof (relationship, post object, user, link, map, group…).
	 *
	 * ACF can return:
	 *   • A single object    (WP_Post / WP_User / stdClass)
	 *   • A single assoc array  (link field → ['url','title','target'])
	 *   • An indexed array of objects  (relationship / post object multi)
	 *   • An indexed array of assoc arrays  (repeater rows)
	 *
	 * Detection heuristic: if the first key of an array is a string, it is
	 * treated as a single associative item.  A numeric-first-key array is
	 * treated as a collection.
	 */
	private function render_object( mixed $value, array $opts ): void {
		if ( empty( $value ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$sub_key      = $opts['sub_key']      ?? '';
		$link         = ! empty( $opts['link'] );
		$link_sub_key = (string) ( $opts['link_sub_key'] ?? '' );
		$separator    = (string) ( $opts['separator']    ?? ', ' );
		$max_items    = (int)   ( $opts['max_items']    ?? 0 );
		$max_length   = (int)   ( $opts['max_length']   ?? 0 );

		if ( ! $sub_key ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		$items = $this->normalize_object_value( $value );

		if ( empty( $items ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		if ( $max_items > 0 ) {
			$total    = count( $items );
			$items    = array_slice( $items, 0, $max_items );
			$overflow = $total - count( $items );
		} else {
			$overflow = 0;
		}

		$parts = [];
		foreach ( $items as $item ) {
			$text = $this->resolve_sub_key( $item, $sub_key );

			if ( $text === '' ) {
				continue;
			}

			if ( $max_length > 0 && mb_strlen( $text ) > $max_length ) {
				$text = mb_substr( $text, 0, $max_length ) . "\xE2\x80\xA6";
			}

			if ( $link && $link_sub_key ) {
				$href = $this->resolve_link_url( $item, $link_sub_key );
				$parts[] = $href
					? sprintf( '<a href="%s">%s</a>', esc_url( $href ), esc_html( $text ) )
					: esc_html( $text );
			} else {
				$parts[] = esc_html( $text );
			}
		}

		if ( empty( $parts ) ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		echo implode( esc_html( $separator ), $parts );

		if ( $overflow > 0 ) {
			printf( ' <span style="color:#999;">+%d more</span>', $overflow );
		}
	}

	/**
	 * Normalise an ACF object-type value into a flat indexed array of items.
	 *
	 * Single object / scalar → wrapped in array.
	 * Associative array (string first-key, e.g. a link field) → treated as
	 *   one item, wrapped in array.
	 * Numerically-indexed array (relationship / repeater) → returned as-is.
	 *
	 * @return list<mixed>
	 */
	private function normalize_object_value( mixed $value ): array {
		if ( is_object( $value ) ) {
			return [ $value ];
		}

		if ( ! is_array( $value ) ) {
			return [ $value ];
		}

		if ( empty( $value ) ) {
			return [];
		}

		// Associative array = single item (link field, map field, group field…)
		// Numerically indexed = collection (relationship, repeater…)
		$first_key = array_key_first( $value );

		return is_string( $first_key ) ? [ $value ] : array_values( $value );
	}

	/**
	 * Extract a display string from an item using a dot-notation path or callable.
	 *
	 * Dot-notation traverses nested objects and arrays:
	 *   'post_title'           → $item->post_title  |  $item['post_title']
	 *   'author.display_name'  → $item['author']['display_name']
	 *
	 * @param mixed           $item     The item to extract from.
	 * @param string|callable $sub_key  Path string or callable( $item ): string.
	 * @return string  Empty string when path cannot be resolved.
	 */
	private function resolve_sub_key( mixed $item, string|callable $sub_key ): string {
		if ( is_callable( $sub_key ) ) {
			return wp_strip_all_tags( (string) $sub_key( $item ) );
		}

		$segments = explode( '.', (string) $sub_key );
		$cursor   = $item;

		foreach ( $segments as $segment ) {
			if ( is_object( $cursor ) ) {
				$cursor = $cursor->$segment ?? null;
			} elseif ( is_array( $cursor ) ) {
				$cursor = $cursor[ $segment ] ?? null;
			} else {
				return '';
			}

			if ( $cursor === null ) {
				return '';
			}
		}

		if ( is_array( $cursor ) || is_object( $cursor ) ) {
			return '';  // Path resolved to a nested structure — not a displayable scalar.
		}

		return wp_strip_all_tags( (string) $cursor );
	}

	/**
	 * Resolve a hyperlink URL from an item.
	 *
	 * Supports two magic values for WP_Post items:
	 *   'permalink'  → get_permalink( $item->ID )
	 *   'edit_link'  → get_edit_post_link( $item->ID )
	 *
	 * Any other string is treated as a dot-notation path into the item
	 * (e.g. 'url' for an ACF link field).
	 *
	 * @param mixed  $item         The current item (WP_Post, array, etc.).
	 * @param string $link_sub_key Magic value or dot-notation path.
	 * @return string  Empty string when unresolvable.
	 */
	private function resolve_link_url( mixed $item, string $link_sub_key ): string {
		if ( $link_sub_key === 'permalink' && $item instanceof WP_Post ) {
			return (string) get_permalink( $item->ID );
		}

		if ( $link_sub_key === 'edit_link' && $item instanceof WP_Post ) {
			return (string) get_edit_post_link( $item->ID );
		}

		// Treat as a dot-notation path — works for link fields, custom URL properties, etc.
		return $this->resolve_sub_key( $item, $link_sub_key );
	}

	// -------------------------------------------------------------------------
	// Composite cell renderer
	// -------------------------------------------------------------------------

	/**
	 * Render a composite (multi-field) cell.
	 *
	 * Each sub-field is formatted to a plain string via format_field_value(),
	 * then assembled using either a {token} template or a separator.
	 * Images are skipped in composite context (they don't format to a string).
	 */
	private function render_composite( array $fields, array $opts, string $column_id, int $post_id ): void {
		// Collect plain-text formatted values keyed by ACF field key.
		$parts = [];
		foreach ( $fields as $field_def ) {
			$raw = $this->get_field_value( $field_def['key'], $post_id );
			$formatted = $this->format_field_value( $raw, $field_def['type'], $field_def['options'] );
			$parts[ $field_def['key'] ] = $formatted;
		}

		$template  = (string) ( $opts['template']  ?? '' );
		$separator = (string) ( $opts['separator'] ?? ' ' );

		if ( $template !== '' ) {
			// Replace {field_key} tokens. Escape the template itself, then
			// insert pre-escaped part values so there's no double-escaping.
			$output = esc_html( $template );
			foreach ( $parts as $key => $formatted ) {
				$output = str_replace( '{' . $key . '}', esc_html( $formatted ), $output );
			}
			// If every part is empty the template just contains its literal text — show a dash.
			$all_empty = count( array_filter( $parts ) ) === 0;
		} else {
			$non_empty = array_filter( $parts );
			$all_empty = empty( $non_empty );
			$output    = esc_html( implode( $separator, $non_empty ) );
		}

		if ( $all_empty ) {
			echo '<span aria-hidden="true">&#8212;</span>';
			return;
		}

		// Wrap in a click-to-filter link if requested.
		// The filter targets filter_field (or the first non-empty field).
		if ( ! empty( $opts['clickable'] ) ) {
			$filter_field = (string) ( $opts['filter_field'] ?? '' );
			if ( ! $filter_field && ! empty( $fields ) ) {
				$filter_field = $fields[0]['key'];
			}

			$filter_value = $parts[ $filter_field ] ?? '';

			// For date filter_type, use the period key (YYYY or YYYYMM).
			if ( $filter_field && $filter_value !== '' && $opts['filter_type'] === 'date' ) {
				$raw  = $this->get_field_value( $filter_field, $post_id );
				$date = DateTime::createFromFormat( $opts['storage_format'] ?? 'Ymd', (string) $raw );
				if ( $date ) {
					$filter_value = ( ( $opts['filter_by'] ?? 'year' ) === 'month' ) ? $date->format( 'Ym' ) : $date->format( 'Y' );
				}
			}

			if ( $filter_field && $filter_value !== '' ) {
				printf(
					'<a href="%s" title="%s">%s</a>',
					esc_url( $this->filter_url( $column_id, $filter_value ) ),
					esc_attr( 'Filter by this value' ),
					$output   // already escaped above
				);
				return;
			}
		}

		echo $output;
	}

	// -------------------------------------------------------------------------
	// Plain-text formatter (used by composite renderer)
	// -------------------------------------------------------------------------

	/**
	 * Format a single field value to a plain string with no HTML.
	 * Used by render_composite() to prepare parts before template/separator join.
	 *
	 * @param mixed  $value  Raw ACF field value.
	 * @param string $type   Field type.
	 * @param array  $opts   Display options for this sub-field.
	 * @return string  Empty string when value is empty or unformattable.
	 */
	private function format_field_value( mixed $value, string $type, array $opts ): string {
		if ( $value === null || $value === false || $value === '' ) {
			return '';
		}

		$type_defaults = $this->defaults[ $type ] ?? [];
		$opts          = array_merge( $type_defaults, $opts );

		switch ( $type ) {

			case 'date':
				$date = DateTime::createFromFormat( $opts['storage_format'] ?? 'Ymd', (string) $value );
				return $date ? $date->format( $opts['display_format'] ?? 'd/m/Y' ) : (string) $value;

			case 'true_false':
				return filter_var( $value, FILTER_VALIDATE_BOOLEAN )
					? (string) ( $opts['true_label']  ?? 'Yes' )
					: (string) ( $opts['false_label'] ?? 'No' );

			case 'taxonomy':
				// Return comma-separated term names.
				$names = [];
				foreach ( (array) $value as $item ) {
					if ( $item instanceof WP_Term ) {
						$names[] = $item->name;
					} elseif ( is_numeric( $item ) ) {
						$term = get_term( (int) $item );
						if ( $term && ! is_wp_error( $term ) ) {
							$names[] = $term->name;
						}
					} elseif ( is_string( $item ) ) {
						$names[] = $item;
					}
				}
				return implode( $opts['separator'] ?? ', ', $names );

			case 'image':
				// Images don't produce a meaningful inline string — skip.
				return '';

			case 'object':
				$sub_key   = $opts['sub_key']   ?? '';
				$separator = $opts['separator'] ?? ', ';
				$max_items = (int) ( $opts['max_items'] ?? 0 );
				$max_len   = (int) ( $opts['max_length'] ?? 0 );

				if ( ! $sub_key ) {
					return '';
				}

				$items = $this->normalize_object_value( $value );
				if ( empty( $items ) ) {
					return '';
				}

				if ( $max_items > 0 ) {
					$items = array_slice( $items, 0, $max_items );
				}

				$parts = [];
				foreach ( $items as $item ) {
					$text = $this->resolve_sub_key( $item, $sub_key );
					if ( $text === '' ) {
						continue;
					}
					if ( $max_len > 0 && mb_strlen( $text ) > $max_len ) {
						$text = mb_substr( $text, 0, $max_len ) . "\xE2\x80\xA6";
					}
					$parts[] = $text;
				}

				return implode( $separator, $parts );

			case 'text':
			default:
				$text = wp_strip_all_tags( (string) $value );
				$max  = (int) ( $opts['max_length'] ?? 0 );
				if ( $max > 0 && mb_strlen( $text ) > $max ) {
					$text = mb_substr( $text, 0, $max ) . "\xE2\x80\xA6";
				}
				return $text;
		}
	}

	// -------------------------------------------------------------------------
	// Filter dropdown renderers
	// -------------------------------------------------------------------------

	/**
	 * Route a filter render call for composite columns by resolving filter_field / filter_type
	 * and forwarding to the appropriate single-type renderer.
	 */
	private function render_composite_filter( string $column_id, array $config, string $var, string $current ): void {
		$opts         = $config['options'];
		$fields       = $config['fields'];
		$filter_field = (string) ( $opts['filter_field'] ?? '' );
		$filter_type  = (string) ( $opts['filter_type']  ?? 'text' );

		if ( ! $filter_field && ! empty( $fields ) ) {
			$filter_field = $fields[0]['key'];
		}

		if ( ! $filter_field ) {
			return;
		}

		// Build a synthetic single-field config for the chosen filter_field.
		$synthetic = [
			'acf_key' => $filter_field,
			'label'   => $config['label'],
			'options' => array_merge( $this->defaults[ $filter_type ] ?? [], $opts ),
		];

		$this->dispatch_filter_renderer( $filter_type, $column_id, $synthetic, $var, $current );
	}

	/**
	 * Call the correct filter dropdown renderer for a given field type.
	 */
	private function dispatch_filter_renderer( string $type, string $column_id, array $config, string $var, string $current ): void {
		switch ( $type ) {
			case 'text':       $this->render_text_filter(       $column_id, $config, $var, $current ); break;
			case 'true_false': $this->render_true_false_filter( $config, $var, $current             ); break;
			case 'taxonomy':   $this->render_taxonomy_filter(   $config, $var, $current             ); break;
			case 'date':       $this->render_date_filter(       $config, $var, $current             ); break;
		}
	}

	private function render_text_filter( string $column_id, array $config, string $var, string $current ): void {
		$values = $this->get_unique_meta_values( $config['acf_key'] );
		if ( empty( $values ) ) {
			return;
		}

		$max = (int) ( $config['options']['max_length'] ?? 0 );

		echo '<select name="' . esc_attr( $var ) . '" id="' . esc_attr( $var ) . '">';
		printf( '<option value="_all">&#8212; %s &#8212;</option>', esc_html( $config['label'] ) );
		foreach ( $values as $v ) {
			$label = ( $max > 0 && mb_strlen( $v ) > $max ) ? mb_substr( $v, 0, $max ) . "\xE2\x80\xA6" : $v;
			printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $current, $v, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	private function render_true_false_filter( array $config, string $var, string $current ): void {
		$t = $config['options']['filter_true_label']  ?? 'Yes';
		$f = $config['options']['filter_false_label'] ?? 'No';

		echo '<select name="' . esc_attr( $var ) . '" id="' . esc_attr( $var ) . '">';
		printf( '<option value="_all">&#8212; %s &#8212;</option>', esc_html( $config['label'] ) );
		printf( '<option value="1"%s>%s</option>', selected( $current, '1', false ), esc_html( $t ) );
		printf( '<option value="0"%s>%s</option>', selected( $current, '0', false ), esc_html( $f ) );
		echo '</select>';
	}

	private function render_taxonomy_filter( array $config, string $var, string $current ): void {
		$taxonomy = $config['options']['taxonomy'] ?? '';
		if ( ! $taxonomy ) {
			return;
		}

		$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		echo '<select name="' . esc_attr( $var ) . '" id="' . esc_attr( $var ) . '">';
		printf( '<option value="_all">&#8212; %s &#8212;</option>', esc_html( $config['label'] ) );
		foreach ( $terms as $term ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $term->slug ), selected( $current, $term->slug, false ), esc_html( $term->name ) );
		}
		echo '</select>';
	}

	private function render_date_filter( array $config, string $var, string $current ): void {
		$filter_by   = $config['options']['filter_by']      ?? 'year';
		$storage_fmt = $config['options']['storage_format'] ?? 'Ymd';

		$raw_values = $this->get_unique_meta_values( $config['acf_key'] );
		if ( empty( $raw_values ) ) {
			return;
		}

		$options = [];
		foreach ( $raw_values as $raw ) {
			$date = DateTime::createFromFormat( $storage_fmt, $raw );
			if ( ! $date ) {
				continue;
			}
			$key             = ( 'month' === $filter_by ) ? $date->format( 'Ym' ) : $date->format( 'Y' );
			$options[ $key ] = ( 'month' === $filter_by ) ? $date->format( 'F Y' ) : $key;
		}

		if ( empty( $options ) ) {
			return;
		}

		krsort( $options );

		echo '<select name="' . esc_attr( $var ) . '" id="' . esc_attr( $var ) . '">';
		printf( '<option value="_all">&#8212; %s &#8212;</option>', esc_html( $config['label'] ) );
		foreach ( $options as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $key ), selected( $current, (string) $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Retrieve an ACF field value, with raw post-meta fallback if ACF is inactive.
	 */
	private function get_field_value( string $key, int $post_id ): mixed {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $key, $post_id );
		}
		return get_post_meta( $post_id, $key, true );
	}

	/**
	 * Distinct non-empty meta values for $meta_key across all non-trashed posts of this type.
	 *
	 * @return string[]
	 */
	private function get_unique_meta_values( string $meta_key ): array {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				 FROM   {$wpdb->postmeta} pm
				 JOIN   {$wpdb->posts}    p  ON p.ID = pm.post_id
				 WHERE  pm.meta_key    = %s
				 AND    p.post_type    = %s
				 AND    p.post_status NOT IN ('trash', 'auto-draft')
				 AND    pm.meta_value != ''
				 ORDER  BY pm.meta_value ASC",
				$meta_key,
				$this->post_type
			)
		);
	}

	/**
	 * Compute a stable column ID from a single ACF key or a composite field array.
	 *
	 * @param string|array $acf_key
	 */
	private function make_column_id( string|array $acf_key ): string {
		if ( is_string( $acf_key ) ) {
			return 'acf_col_' . sanitize_key( $acf_key );
		}

		$parts = array_map(
			fn( $f ) => sanitize_key( is_array( $f ) ? ( $f['key'] ?? '' ) : (string) $f ),
			$acf_key
		);

		return 'acf_col_' . implode( '__', array_filter( $parts ) );
	}

	/**
	 * Normalise a composite fields array into a consistent internal format.
	 *
	 * Accepts each element as either:
	 *   'my_field'                              → type defaults to 'text'
	 *   [ 'key' => 'my_field', 'type' => '...', ...display options ]
	 *
	 * @param  array $raw_fields  Elements from the $acf_key array passed to add().
	 * @return list<array{key:string,type:string,options:array}>
	 */
	private function normalize_composite_fields( array $raw_fields ): array {
		$normalized = [];

		foreach ( $raw_fields as $item ) {
			if ( is_string( $item ) ) {
				$normalized[] = [ 'key' => $item, 'type' => 'text', 'options' => [] ];
			} elseif ( is_array( $item ) && ! empty( $item['key'] ) ) {
				$type         = $item['type'] ?? 'text';
				$options      = array_diff_key( $item, array_flip( [ 'key', 'type' ] ) );
				$normalized[] = [ 'key' => $item['key'], 'type' => $type, 'options' => $options ];
			}
		}

		return $normalized;
	}

	/**
	 * The GET parameter name for a column's active filter value.
	 * Prefixed 'acf_f_' to avoid clashing with WP core params.
	 */
	private function filter_var( string $column_id ): string {
		return 'acf_f_' . $column_id;
	}

	/**
	 * Build an admin list-table URL with a specific filter applied.
	 */
	private function filter_url( string $column_id, string $value ): string {
		$args = [ $this->filter_var( $column_id ) => $value ];
		if ( 'post' !== $this->post_type ) {
			$args['post_type'] = $this->post_type;
		}
		return add_query_arg( $args, admin_url( 'edit.php' ) );
	}

	/**
	 * Insert a key/value pair into an associative array at a zero-based position.
	 */
	private function array_insert_at( array $array, string $key, mixed $value, int $position ): array {
		$position = max( 0, min( $position, count( $array ) ) );
		return array_merge(
			array_slice( $array, 0, $position, true ),
			[ $key => $value ],
			array_slice( $array, $position, null, true )
		);
	}
}