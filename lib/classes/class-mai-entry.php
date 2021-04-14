<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

/**
 * Class Mai_Entry
 */
class Mai_Entry {

	/**
	 * Entry.
	 *
	 * @var WP_Post|WP_Term $entry
	 */
	protected $entry;

	/**
	 * Args.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Type.
	 *
	 * @var $type
	 */
	protected $type;

	/**
	 * Context.
	 *
	 * @var $context
	 */
	protected $context;

	/**
	 * Id.
	 *
	 * @var $id
	 */
	protected $id;

	/**
	 * Url.
	 *
	 * @var $url
	 */
	protected $url;

	/**
	 * Breakpoints.
	 *
	 * @var $breakpoints
	 */
	protected $breakpoints;

	/**
	 * Link.
	 *
	 * @var $link_entry
	 */
	protected $link_entry;

	/**
	 * Image size.
	 *
	 * @var $image_size
	 */
	protected $image_size;

	/**
	 * Image ID.
	 *
	 * @var $image_id
	 */
	protected $image_id;

	/**
	 * Mai_Entry constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post|WP_Term $entry Entry object.
	 * @param array           $args  Entry args.
	 *
	 * @return void
	 */
	public function __construct( $entry, $args ) {
		$this->entry       = $entry;
		$this->args        = $args;
		$this->context     = $this->args['context'];
		$this->type        = isset( $this->args['type'] ) ? $this->args['type'] : 'post';
		$this->id          = $this->get_id();
		$this->url         = $this->get_url();
		$this->breakpoints = mai_get_breakpoints();
		$this->link_entry  = apply_filters( 'mai_link_entry', (bool) ! $this->args['disable_entry_link'], $this->args, $this->entry );
		$this->image_size  = $this->get_image_size();
		$this->image_id    = $this->get_image_id();
	}

	/**
	 * Renders the entry.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function render() {

		// Remove post attributes.
		remove_filter( 'genesis_attr_entry', 'genesis_attributes_entry' );

		// Wrap.
		switch ( $this->type ) {
			case 'post':
				$wrap = 'article';
			break;
			case 'term':
			case 'user':
				$wrap = 'div';
			break;
			default:
				$wrap = 'div';
		}

		$atts = [
			'class' => sprintf( 'entry entry-%s', 'block' === $this->context ? 'grid' : $this->context ),
		];

		// Add entry link class.
		if ( $this->link_entry ) {
			$atts['class'] .= ' has-entry-link';
		} else {
			$atts['class'] .= ' no-entry-link';
		}

		// Get elements without Genesis hooks.
		$elements = [];

		foreach ( (array) $this->args['show'] as $item ) {
			if ( mai_has_string( 'genesis_', $item ) ) {
				continue;
			}

			$elements[] = $item;
		}

		$image_first = ( isset( $elements[0] ) && ( 'image' === $elements[0] ) ) || ( isset( $this->args['image_position'] ) && mai_has_string(
			[
				'left',
				'right',
			],
			$this->args['image_position'] )
		);

		$image_only = ( isset( $elements[0] ) && 'image' === $elements[0] ) && ( 1 === count( $elements ) );

		// Add image classes.
		if ( in_array( 'image', $this->args['show'], true ) ) {
			if ( $this->image_id ) {
				$atts['class'] .= ' has-image';

				if ( $image_first && ! ( ( 'single' === $this->context ) && mai_is_element_hidden( 'featured_image', $this->id ) ) ) {
					$atts['class'] .= ' has-image-first';
				}
			}

			if ( $image_only ) {
				$atts['class'] .= ' has-image-only';
			}
		}

		// Add atts from `genesis_attributes_entry` but only when we need it.
		if ( 'post' === $this->type ) {
			$atts['class']      = mai_add_classes( implode( ' ', get_post_class() ), $atts['class'] );
			$atts['aria-label'] = the_title_attribute(
				[
					'echo' => false,
				]
			);
		}

		// Term classes.
		if ( 'term' === $this->type ) {
			$atts['class'] .= sprintf( ' term-%s type-%s %s-%s', $this->entry->term_id, $this->entry->taxonomy, $this->entry->taxonomy, $this->entry->slug );
		}

		// Remove duplicate classes.
		$atts['class'] = implode( ' ', array_unique( explode( ' ', $atts['class'] ) ) );

		// Open.
		genesis_markup(
			[
				'open'    => "<{$wrap} %s>",
				'context' => 'entry',
				'echo'    => true,
				'atts'    => $atts,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Image outside inner wrap if first element.
		if ( $image_first ) {
			$this->do_image();
		}

		if ( ! $image_only ) {

			// Entry wrap open.
			genesis_markup(
				[
					'open'    => '<div %s>',
					'context' => 'entry-wrap',
					'echo'    => true,
					'atts'    => [
						'class' => sprintf( 'entry-wrap entry-wrap-%s', 'block' === $this->context ? 'grid' : $this->context ),
					],
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);

		}

		// Overlay.
		if ( isset( $this->args['image_position'] ) && ( 'background' === $this->args['image_position'] ) ) {
			$overlay_wrap = 'span';
			$overlay_atts = [
				'class' => 'entry-overlay',
			];

			if ( $this->link_entry ) {
				$overlay_wrap           = 'a';
				$overlay_atts['href']   = $this->url;
				$overlay_atts['class'] .= ' entry-overlay-link';
			}

			genesis_markup(
				[
					'open'    => "<{$overlay_wrap} %s>",
					'close'   => "</{$overlay_wrap}>",
					'context' => 'entry-overlay',
					'echo'    => true,
					'atts'    => $overlay_atts,
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);
		}

		$outside_elements = [];

		if ( ! $image_only && ( 'single' === $this->context ) ) {
			foreach ( $this->args['show'] as $index => $element ) {
				if ( mai_has_string( 'genesis_', $element ) ) {
					$outside_elements = array_slice( $elements, $index, null, false );
				} else {
					$outside_elements[] = $element;
				}
			}
		}

		// Loop through our elements.
		foreach ( $this->args['show'] as $element ) {

			// Skip image is first, skip.
			if ( ( 'image' === $element ) && $image_first ) {
				continue;
			}

			// Skip if an outside element.
			if ( in_array( $element, $outside_elements ) ) {
				continue;
			}

			// Output the element if a method or function exists.
			$method   = "do_{$element}";
			$function = "mai_do_{$element}";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			} elseif ( function_exists( $function ) ) {
				$function( $this->entry, $this->args );
			}
		}

		if ( ! $image_only ) {

			// Entry wrap close.
			genesis_markup(
				[
					'close'   => '</div>',
					'context' => 'entry-wrap',
					'echo'    => true,
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);

			// Loop through our outside elements.
			foreach ( $outside_elements as $element ) {
				// Output the element if a method exists.
				$method = "do_{$element}";
				if ( method_exists( $this, $method ) ) {
					$this->$method();
				}
			}
		}

		// Close.
		genesis_markup(
			[
				'close'   => "</{$wrap}>",
				'context' => 'entry',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Add back post attributes for other entries.
		add_filter( 'genesis_attr_entry', 'genesis_attributes_entry' );
		// if ( 'post' !== $this->type ) {
		// 	add_filter( 'genesis_attr_entry', 'genesis_attributes_entry' );
		// } elseif ( in_array( 'image', $this->args['show'], true ) ) {
		if ( in_array( 'image', $this->args['show'], true ) ) {
			remove_filter( 'post_class', [ $this, 'has_image_class' ] );
		}

	}

	/**
	 * Gets the entry ID.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public function get_id() {
		switch ( $this->type ) {
			case 'post':
				$entry_id = $this->entry->ID;
			break;
			case 'term':
				$entry_id = $this->entry->term_id;
			break;
			case 'user':
				$entry_id = $this->entry->ID;
			break;
			default:
				$entry_id = 0;
		}

		return $entry_id;
	}

	/**
	 * Gets the entry URL.
	 *
	 * @since 0.1.0
	 *
	 * @return false|string|WP_Error
	 */
	public function get_url() {
		switch ( $this->type ) {
			case 'post':
				$url = get_permalink( $this->id );
			break;
			case 'term':
				$url = get_term_link( $this->id );
			break;
			case 'user':
				$url = get_author_posts_url( $this->id );
			break;
			default:
				$url = '';
		}

		return $url;
	}

	/**
	 * Render the image.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_image() {
		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'featured_image', $this->id ) ) {
			return;
		}

		// Get the image HTML.
		$image = $this->get_image();

		// Bail if no image.
		if ( ! $image ) {
			return;
		}

		$link_image = $this->link_entry && ( 'background' !== $this->args['image_position'] );
		$wrap       = $link_image ? 'a' : 'figure';
		$atts       = [
			'class' => 'entry-image-link',
		];

		if ( 'single' === $this->context ) {
			$atts['class'] .= ' entry-image-single';
		}

		if ( $link_image ) {
			$atts['href']        = $this->url;
			$atts['aria-hidden'] = 'true';
			$atts['tabindex']    = '-1';
		}

		// This filter overrides href.
		remove_filter( 'genesis_attr_entry-image-link', 'genesis_attributes_entry_image_link' );

		// Image.
		genesis_markup(
			[
				'open'    => "<{$wrap} %s>",
				'close'   => "</{$wrap}>",
				'content' => $image,
				'context' => 'entry-image-link',
				'echo'    => true,
				'atts'    => $atts,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		add_filter( 'genesis_attr_entry-image-link', 'genesis_attributes_entry_image_link' );
	}

	/**
	 * Gets the image HTML.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_image() {

		// Get the image ID.
		$image_id = $this->image_id;

		// Bail if no image ID.
		if ( ! $image_id ) {
			return '';
		}

		add_filter( 'max_srcset_image_width', [ $this, 'srcset_max_image_width' ], 10, 2 );
		add_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ], 10, 5 );

		if ( 'single' === $this->context ) {
			$filter = function() {
				return false;
			};

			add_filter( 'wp_lazy_loading_enabled', $filter );
		}

		$image = wp_get_attachment_image(
			$image_id,
			$this->image_size,
			false,
			[
				'class' => "entry-image size-{$this->image_size}",
			]
		);

		if ( 'single' === $this->context ) {
			remove_filter( 'wp_lazy_loading_enabled', $filter );
		}

		remove_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ] );
		remove_filter( 'max_srcset_image_width', [ $this, 'srcset_max_image_width' ] );

		if ( 'single' === $this->context ) {
			$caption = wp_get_attachment_caption( $image_id );
			if ( $caption ) {
				$image .= sprintf( '<figcaption>%s</figcaption>', $caption );
			}
		}

		return $image;
	}

	/**
	 * Modify the max image width to use in srcset based on the breakpoint and amount of columns.
	 * This allows srcset to never show an image larger than it'll ever be displayed via the theme settings.
	 *
	 * @since 0.3.3
	 *
	 * @return int
	 */
	public function srcset_max_image_width() {
		$size        = 1600; // Max theme image size.
		$has_sidebar = mai_has_sidebar();
		$is_single   = 'single' === $this->context;
		$img_aligned = ! $is_single && mai_has_string( ['left', 'right'], $this->args['image_position'] );
		$img_widths  = [
			'fourth' => 4,
			'third'  => 3,
			'half'   => 2,
		];
		$single_cols = [
			'xs'     => 1,
			'sm'     => 1,
			'md'     => 1,
			'lg'     => 1,
		];
		$image_cols = [
			'xs'     => ! $is_single && $img_aligned && ! $this->args['image_stack'] ? $img_widths[ $this->args['image_width'] ] : 1,
			'sm'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
			'md'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
			'lg'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
		];

		$columns = $is_single ? $single_cols : array_reverse( mai_get_breakpoint_columns( $this->args ), true ); // Mobile first.
		$columns = $this->get_image_breakpoint_columns( $columns );
		$widths  = [];

		foreach ( $columns as $break => $count ) {
			switch ( $break ) {
				case 'xs':
					$max_width = $this->breakpoints['sm'];
					$width     = $max_width / $columns['xs'];
					$width     = $width / $image_cols['xs'];
					$widths[]  = floor( $width );
				break;
				case 'sm':
					$max_width = $this->breakpoints['md'];
					$width     = $max_width / $columns['sm'];
					$width     = $width / $image_cols['sm'];
					$widths[]  = floor( $width );
				break;
				case 'md':
					$max_width = $this->breakpoints['lg'];
					$width     = $has_sidebar ? $max_width * 2 / 3 : $max_width;
					$width     = $width / $columns['md'];
					$width     = $width / $image_cols['md'];
					$widths[]  = floor( $width );
				break;
				case 'lg':
					$max_width = $this->breakpoints['xl'];
					$width     = $has_sidebar ? $max_width * 2 / 3 : $max_width;
					$width     = $width / $columns['lg'];
					$width     = $width / $image_cols['lg'];
					$widths[]  = floor( $width );
				break;
			}
		}

		if ( $widths ) {
			$size = absint( max( $widths ) );
		}

		return $size;
	}

	/**
	 * Show sizes image attribute with appropriate values based on registered theme image sizes and breakpoints/columns.
	 *
	 * @since 0.1.0
	 *
	 * @todo  Handle 0/auto columns.
	 *
	 * @return string
	 */
	public function calculate_image_sizes() {
		$new_sizes   = [];
		$has_sidebar = mai_has_sidebar();
		$is_single   = 'single' === $this->context;
		$img_aligned = ! $is_single && mai_has_string( ['left', 'right'], $this->args['image_position'] );
		$img_widths  = [
			'fourth' => 4,
			'third'  => 3,
			'half'   => 2,
		];
		$single_cols = [
			'xs'     => 1,
			'sm'     => 1,
			'md'     => 1,
			'lg'     => 1,
		];
		$image_cols = [
			'xs'     => ! $is_single && $img_aligned && ! $this->args['image_stack'] ? $img_widths[ $this->args['image_width'] ] : 1,
			'sm'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
			'md'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
			'lg'     => ! $is_single && $img_aligned ? $img_widths[ $this->args['image_width'] ] : 1,
		];

		$columns = $is_single ? $single_cols : array_reverse( mai_get_breakpoint_columns( $this->args ), true ); // Mobile first.
		$columns = $this->get_image_breakpoint_columns( $columns );

		// TODO: Add retina support?

		foreach ( $columns as $break => $count ) {
			switch ( $break ) {
				case 'xs':
					$max_width   = $this->breakpoints['sm'] - 1;
					$width       = $max_width / $columns['xs'];
					$width       = $width / $image_cols['xs'];
					$new_sizes[] = "(max-width:{$max_width}px) {$width}px";
				break;
				case 'sm':
					$min_width   = $this->breakpoints['sm'];
					$max_width   = $this->breakpoints['md'] - 1;
					$width       = $max_width / $columns['sm'];
					$width       = $width / $image_cols['sm'];
					$new_sizes[] = "(min-width:{$min_width}px) and (max-width: {$max_width}px) {$width}px";
				break;
				case 'md':
					$min_width   = $this->breakpoints['md'];
					$max_width   = $this->breakpoints['lg'] - 1;
					$width       = $has_sidebar ? $max_width * 2 / 3 : $max_width;
					$width       = $width / $columns['md'];
					$width       = $width / $image_cols['md'];
					$new_sizes[] = "(min-width:{$min_width}px) and (max-width: {$max_width}px) {$width}px";
				break;
				case 'lg':
					$min_width   = $this->breakpoints['lg'];
					$width       = $this->breakpoints['xl'];
					$width       = $has_sidebar ? $width * 2 / 3 : $width;
					$width       = $width / $columns['lg'];
					$width       = $width / $image_cols['lg'];
					$new_sizes[] = "(min-width:{$min_width}px) {$width}px";
				break;
			}
		}

		return implode( ', ', $new_sizes );
	}

	/**
	 * Gets the image ID.
	 *
	 * @since 0.1.0
	 *
	 * @return bool|int|mixed|null|string
	 */
	public function get_image_id() {
		switch ( $this->type ) {
			case 'post':
				$image_id = get_post_thumbnail_id( $this->id );
				if ( ! $image_id && ( 'single' !== $this->context ) ) {
					$image_id = genesis_get_image_id( 0, $this->id );
				}
			break;
			case 'term':
				$key = 'featured_image';

				// We need to check each term because a grid archive can show multiple taxonomies.
				if ( class_exists( 'WooCommerce' ) && 'block' === $this->context && 'term' === $this->type ) {
					$term = get_term( $this->id );
					if ( $term && 'product_cat' === $term->taxonomy ) {
						$key = 'thumbnail_id';
					}
				}
				$image_id = get_term_meta( $this->id, $key, true );
			break;
			case 'user':
				$image_id = get_user_meta( $this->id, 'featured_image', true );
				// TODO: $image_id = $image_id ? $image_id : fallback to avatar?
			break;
			default:
				$image_id = 0;
		}

		// TODO: Get fallback.

		// Filter.
		$image_id = apply_filters( 'mai_entry_image_id', (int) $image_id, $this->entry, $this->args );

		// Bail if no image ID.
		if ( ! $image_id ) {
			return false;
		}

		return (int) $image_id;
	}

	/**
	 * Gets the image size.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_image_size() {
		switch ( $this->args['image_orientation'] ) {
			case 'landscape':
			case 'portrait':
			case 'square':
				$image_size = $this->get_image_size_by_cols();
				$image_size = sprintf( '%s-%s', $this->args['image_orientation'], $image_size );
			break;
			default:
				$image_size = $this->args['image_size'];
		}

		return $image_size;
	}

	/**
	 * Gets the image size by columns.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_image_size_by_cols() {
		$fw_content = ( 'full-width-content' === genesis_site_layout() ) ? true : false;

		// If single.
		if ( 'single' === $this->context ) {
			$image_size = $fw_content ? 'lg' : 'md';
		} else {
			$img_aligned = mai_has_string( ['left', 'right'], $this->args['image_position'] );

			// Archive or block.
			switch ( $this->args['columns'] ) {
				case 1:
					if ( $fw_content ) {
						if ( $img_aligned ) {
							$image_size = 'md';
						} else {
							$image_size = 'lg';
						}
					} else {
						if ( $img_aligned ) {
							$image_size = 'sm';
						} else {
							$image_size = 'md';
						}
					}
				break;
				case 2:
					if ( $fw_content ) {
						if ( $img_aligned ) {
							$image_size = 'sm';
						} else {
							$image_size = 'md';
						}
					} else {
						$image_size = 'sm';
					}
				break;
				default:
					$image_size = 'sm';
			}
		}

		return $image_size;
	}

	/**
	 * Gets a reasonable column count when a breakpoint has a 0 (Auto) value.
	 *
	 * @param array $columns The existing columns.
	 *
	 * @return array
	 */
	public function get_image_breakpoint_columns( $columns ) {
		$image_sizes = mai_get_available_image_sizes();
		$fallback    = isset( $image_sizes[ $this->image_size ]['width'] ) ? absint( $this->breakpoints['xl'] / $image_sizes[ $this->image_size ]['width'] ) : 1;

		foreach ( $columns as $break => $count ) {
			if ( 0 !== $count ) {
				continue;
			}

			$columns[ $break ] = $fallback;
		}

		return $columns;
	}

	/**
	 * Display the post content.
	 *
	 * Initially based off of genesis_do_post_title().
	 *
	 * @return  void
	 */
	public function do_title() {
		if ( ( 'single' === $this->context ) && ( mai_is_element_hidden( 'entry_title', $this->id ) || ( mai_has_page_header() && apply_filters( 'mai_entry_title_in_page_header', true, $this->args, $this->entry ) ) ) ) {
			return;
		}

		// Title.
		switch ( $this->type ) {
			case 'post':
				// Not a block.
				if ( 'block' !== $this->context ) {
					// Singular and archive wrap and title text.
					if ( 'single' === $this->context ) {
						$wrap = 'h1';
					} else {
						$wrap = 'h2';
					}

					$title = get_the_title();

					// If HTML5 with semantic headings, wrap in H1.
					$wrap = genesis_get_seo_option( 'semantic_headings' ) ? 'h1' : $wrap;

					// Filter the post title text.
					$title = apply_filters( 'genesis_post_title_text', $title );

					// Wrap in H2 on static homepages if Primary Title H1 is set to title or description.
					if (
						( 'single' === $this->context )
						&& is_front_page()
						&& ! is_home()
						&& genesis_seo_active()
						&& 'neither' !== genesis_get_seo_option( 'home_h1_on' )
					) {
						$wrap = 'h2';
					}

					/**
					 * Entry title wrapping element.
					 *
					 * The wrapping element for the entry title.
					 *
					 * @param string $wrap The wrapping element (h1, h2, p, etc.).
					 */
					$wrap = apply_filters( 'genesis_entry_title_wrap', $wrap );

				} else {
					// Block.
					$wrap  = 'h3';
					$title = get_the_title( $this->entry );
				}

			break;
			case 'term':
				$wrap  = 'h3'; // Only blocks use this function for terms.
				$title = $this->entry->name;
			break;
			case 'user':
				$wrap  = 'h3'; // Only blocks use this function for users.
				$title = ''; // TODO: Add title.
			break;
			default:
				$title = '';
		}

		// Bail if no title.
		if ( ! $title ) {
			return;
		}

		// If linking.
		if ( $this->link_entry ) {
			// This filter overrides href.
			remove_filter( 'genesis_attr_entry-title-link', 'genesis_attributes_entry_title_link' );
			$title = genesis_markup(
				[
					'open'    => '<a %s>',
					'close'   => '</a>',
					'content' => $title,
					'context' => 'entry-title-link',
					'echo'    => false,
					'atts'    => [
						'href' => $this->url,
						'rel'  => 'bookmark',
					],
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);
			add_filter( 'genesis_attr_entry-title-link', 'genesis_attributes_entry_title_link' );
		}

		/**
		 * Entry title wrapping element.
		 *
		 * The wrapping element for the entry title.
		 *
		 * @param  string $wrap The wrapping element (h1, h2, p, etc.).
		 */
		$wrap = apply_filters( 'mai_entry_title_wrap', $wrap, $this->args, $this->entry );

		$atts = [
			'class' => 'entry-title',
		];

		if ( 'single' === $this->context ) {
			$atts['class'] .= ' entry-title-single';
		}

		// Build the output.
		$output = genesis_markup(
			[
				'open'    => "<{$wrap} %s>",
				'close'   => "</{$wrap}>",
				'content' => $title,
				'context' => 'entry-title',
				'echo'    => false,
				'atts'    => $atts,
				'params'  => [
					'wrap'  => $wrap,
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Add genesis filter.
		if ( 'post' === $this->type ) {
			$output = apply_filters( 'genesis_post_title_output', $output, $wrap, $title ) . "\n";
		}

		if ( ! $output ) {
			return;
		}

		// Title output is left unescaped to accommodate trusted user input.
		// See https://codex.wordpress.org/Function_Reference/the_title#Security_considerations.
		echo $output;
	}

	/**
	 * Display the post excerpt.
	 *
	 * Initially based off of genesis_do_post_content().
	 *
	 * @since 0.1.0
	 *
	 * @return  void
	 */
	public function do_excerpt() {
		switch ( $this->type ) {
			case 'post':
				if ( 'single' === $this->context ) {
					// Manual excerpts only, on single posts.
					$excerpt = has_excerpt() && ! mai_is_element_hidden( 'entry_excerpt', $this->id ) ? get_the_excerpt() : '';
				} else {
					$excerpt = get_the_excerpt();
				}
			break;
			case 'term':
				$excerpt = get_term_meta( $this->id, 'intro_text', true );
			break;
			case 'user':
				$excerpt = ''; // TODO (possibly not an option for users).
			break;
			default:
				$excerpt = '';
		}

		// Limit.
		if ( $excerpt && isset( $this->args['content_limit'] ) && $this->args['content_limit'] > 0 ) {
			$excerpt = mai_get_content_limit( $excerpt, $this->args['content_limit'] );
		}

		if ( ! $excerpt ) {
			return;
		}

		// Output.
		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'entry-excerpt',
				'content' => wpautop( $excerpt ),
				'echo'    => true,
				'atts'    => [
					'class' => ( 'single' === $this->context ) ? 'entry-excerpt entry-excerpt-single' : 'entry-excerpt',
				],
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	/**
	 * Display the post content.
	 *
	 * Initially based off of genesis_do_post_content().
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_content() {
		$open = genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'entry-content',
				'echo'    => false,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		$close = genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'entry-content',
				'echo'    => false,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Single needs the_content() directly, to parse_blocks and other filters.
		if ( 'single' === $this->context ) {
			echo $open;
			the_content();
			$this->do_post_content_nav();
			echo $close;

		} else {

			// Content.
			switch ( $this->type ) {
				case 'post':
					$content = strip_shortcodes( get_the_content( null, false, $this->entry ) );
				break;
				case 'term':
					$content = term_description( $this->id );
				break;
				case 'user':
					$content = get_the_author_meta( 'description', $this->id );
				break;
				default:
					$content = '';
			}

			// Limit.
			if ( $content && isset( $this->args['content_limit'] ) && ( $this->args['content_limit'] > 0 ) ) {
				$content = mai_get_content_limit( $content, $this->args['content_limit'] );
			}

			if ( ! $content ) {
				return;
			}

			echo $open;
			echo apply_filters( 'mai_entry_content', $content, $this->args, $this->entry );
			echo $close;
		}

	}

	/**
	 * Displays page links for paginated posts (i.e. includes the <!--nextpage--> Quicktag one or more times).
	 *
	 * @since 2.11.0
	 *
	 * @return void
	 */
	public function do_post_content_nav() {
		wp_link_pages(
			[
				'before'      => genesis_markup(
					[
						'open'    => '<div %s>',
						'context' => 'entry-pagination',
						'echo'    => false,
					]
				) . __( 'Pages:', 'mai-engine' ),
				'after'       => genesis_markup(
					[
						'close'   => '</div>',
						'context' => 'entry-pagination',
						'echo'    => false,
					]
				),
				'link_before' => '<span class="screen-reader-text">' . __( 'Page ', 'mai-engine' ) . '</span>',
			]
		);
	}

	/**
	 * Display the custom content.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public function do_custom_content() {
		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'custom_content', $this->id ) ) {
			return;
		}

		if ( ! ( isset( $this->args['custom_content'] ) && $this->args['custom_content'] ) ) {
			return;
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'content' => mai_get_processed_content( $this->args['custom_content'] ),
				'context' => 'entry-custom-content',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}


	/**
	 * Display the header meta.
	 *
	 * Initially based off genesis_post_info().
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_header_meta() {
		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'header_meta', $this->id ) ) {
			return;
		}

		// Bail if none.
		if ( ! isset( $this->args['header_meta'] ) || ! $this->args['header_meta'] ) {
			return;
		}

		$header_meta = wp_kses_post( $this->args['header_meta'] );
		$header_meta = do_shortcode( $this->args['header_meta'] );

		if ( ! $header_meta ) {
			return;
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'content' => $header_meta,
				'context' => 'entry-meta-before-content',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	/**
	 * Display the footer meta.
	 *
	 * Initially based off genesis_post_meta().
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_footer_meta() {
		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'footer_meta', $this->id ) ) {
			return;
		}

		// Bail if none.
		if ( ! isset( $this->args['footer_meta'] ) || ! $this->args['footer_meta'] ) {
			return;
		}

		$footer_meta = wp_kses_post( $this->args['header_meta'] );
		$footer_meta = do_shortcode( $this->args['footer_meta'] );

		if ( ! $footer_meta ) {
			return;
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'content' => $footer_meta,
				'context' => 'entry-meta-after-content',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	/**
	 * Render the more link.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_more_link() {
		if ( ! $this->link_entry ) {
			return;
		}

		// Link.
		switch ( $this->type ) {
			case 'post':
				$href = get_the_permalink( $this->entry );
			break;
			case 'term':
				$href = get_term_link( $this->entry );
			break;
			case 'user':
				$href = ''; // TODO.
			break;
			default:
				$href = '';
		}

		// Bail if no link.
		if ( ! $href ) {
			return;
		}

		$more_link_text = isset( $this->args['more_link_text'] ) && $this->args['more_link_text'] ? $this->args['more_link_text'] : mai_get_read_more_text();
		$more_link_text = $more_link_text;
		$more_link_text = do_shortcode( $more_link_text );

		// Screen reader text title.
		switch ( $this->type ) {
			case 'post':
				// Not a block.
				if ( 'block' !== $this->context ) {
					$title = get_the_title();
				} else {
					$title = get_the_title( $this->entry );
				}
			break;
			case 'term':
				$title = $this->entry->name;
			break;
			case 'user':
				$title = ''; // TODO: Add title.
			break;
			default:
				$title = '';
		}

		$more_link_text .= $title ? sprintf( '<span class="screen-reader-text">%s</span>', $title ) : '';

		// The link HTML.
		$more_link = genesis_markup(
			[
				'open'    => '<a %s>',
				'close'   => '</a>',
				'content' => $more_link_text,
				'context' => 'entry-more-link',
				'echo'    => false,
				'atts'    => [
					'href'   => $href,
					'target' => is_admin() ? '_blank' : false,
					'class'  => 'entry-more-link ' . ( is_admin() ? 'wp-block-button__link has-small-font-size' : 'button button-small button-secondary' ),
				],
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// The wrap with the link.
		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'content' => $more_link,
				'context' => 'entry-more',
				'atts'    => [
					'class' => 'entry-more' . ( is_admin() ? ' wp-block-button is-style-secondary' : '' ),
				],
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	/**
	 * Render the after entry template part.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_after_entry() {
		if ( mai_has_template_part( 'after-entry' ) && ! mai_is_element_hidden( 'after_entry', $this->id ) ) {
			mai_render_template_part( 'after-entry', '<div class="after-entry template-part">', '</div>' );
		}

		// Deprecated for < 2.0.0.
		if ( ! is_active_sidebar( 'after-entry' ) ) {
			genesis_widget_area(
				'after-entry',
				[
					'before' => '<div class="after-entry widget-area">',
					'after'  => '</div>',
				]
			);
		}
	}

	/**
	 * Render the author box.
	 *
	 * @since 0.1.0
	 *
	 * @todo  Output should be escaped.
	 *
	 * @return void
	 */
	public function do_author_box() {
		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'author_box', $this->id ) ) {
			return;
		}

		echo genesis_get_author_box( 'single' );
	}

	/**
	 * Render adjacent entry nav.
	 *
	 * Can't use genesis_adjacent_entry_nav() because it checks for post_type support.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_adjacent_entry_nav() {
		$taxonomy       = apply_filters( 'mai_adjacent_entry_nav_taxonomy', '', $this->entry, $this->args );
		$prev_post_text = '<span class="screen-reader-text">' . esc_html__( 'Previous Post:', 'genesis' ) . ' </span><span class="adjacent-post-link">&#xAB; %image %title</span>';
		$next_post_text = '<span class="screen-reader-text">' . esc_html__( 'Next Post:', 'genesis' ) . ' </span><span class="adjacent-post-link">%title %image &#xBB;</span>';

		if ( $taxonomy ) {
			$prev_post_link = get_previous_post_link( '%link', $prev_post_text, true, '', $taxonomy );
			$next_post_link = get_next_post_link( '%link', $prev_post_text, true, '', $taxonomy );
		} else {
			$prev_post_link = get_previous_post_link( '%link', $prev_post_text );
			$next_post_link = get_next_post_link( '%link', $next_post_text );
		}

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'adjacent-entry-pagination',
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'pagination-previous',
				'content' => $prev_post_link,
				'close'   => '</div>',
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);


		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'pagination-next',
				'content' => $next_post_link,
				'close'   => '</div>',
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'adjacent-entry-pagination',
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_genesis_entry_header() {
		do_action( 'genesis_entry_header' );
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_genesis_before_entry_content() {
		do_action( 'genesis_before_entry_content' );
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_genesis_entry_content() {
		do_action( 'genesis_entry_content' );
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_genesis_after_entry_content() {
		do_action( 'genesis_after_entry_content' );
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_genesis_entry_footer() {
		do_action( 'genesis_entry_footer' );
	}
}
