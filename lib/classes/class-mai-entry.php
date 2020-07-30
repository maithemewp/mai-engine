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

		// Get elements without Genesis hooks.
		$elements = [];

		foreach ( (array) $this->args['show'] as $item ) {
			if ( mai_has_string( 'genesis_', $item ) ) {
				continue;
			}

			$elements[] = $item;
		}

		$image_first = isset( $elements[0] ) && ( 'image' === $elements[0] );

		// Has image classes.
		if ( in_array( 'image', $this->args['show'], true ) ) {
			if ( $this->get_image_id() ) {
				$atts['class'] .= ' has-image';

				if ( $image_first && ! mai_is_element_hidden( 'featured_image' ) ) {
					$atts['class'] .= ' has-image-first';
				}
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

		// Inner open.
		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'entry-wrap',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Overlay link.
		if ( ( 'single' !== $this->context ) && ( 'background' === $this->args['image_position'] ) ) {
			printf( '<a href="%s" class="entry-overlay"></a>', $this->url );
		}

		// Loop through our elements.
		foreach ( $this->args['show'] as $element ) {

			// Skip image is left or right, skip.
			if ( ( 'image' === $element ) && $image_first ) {
				continue;
			}

			// Output the element if a method exists.
			$method = "do_{$element}";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}

		// Inner close.
		genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'entry-inner',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

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

		if ( ( 'single' === $this->context ) && mai_is_element_hidden( 'featured_image' ) ) {
			return;
		}

		// Get the image HTML.
		$image = $this->get_image();

		// Bail if no image.
		if ( ! $image ) {
			return;
		}

		// TODO: Is this the best way to handle non-linked featured images?
		// We'll need this later for Mai Favorites when we can disable links in grid.
		$wrap = ( 'single' === $this->context ) || ( 'background' === $this->args['image_position'] ) ? 'figure' : 'a';

		$atts = [
			'class' => 'entry-image-link',
		];

		if ( 'single' === $this->context ) {
			$atts['class'] .= ' entry-image-single';
		}

		if ( ( 'single' !== $this->context ) && ( 'background' !== $this->args['image_position'] ) ) {
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
		$image_id = $this->get_image_id();

		// Bail if no image ID.
		if ( ! $image_id ) {
			return '';
		}

		add_filter( 'max_srcset_image_width', [ $this, 'srcset_max_image_width' ], 10, 2 );
		add_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ], 10, 5 );
		$size  = $this->get_image_size();
		$image = wp_get_attachment_image(
			$image_id,
			$size,
			false,
			[
				'class'   => "entry-image size-{$size}",
				'loading' => 'lazy',
			]
		);
		remove_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ], 10, 5 );
		remove_filter( 'max_srcset_image_width', [ $this, 'srcset_max_image_width' ], 10, 2 );

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
	 * @param int   $size       The max width.
	 * @param array $size_array The array of width and height values.
	 *
	 * @return int
	 */
	public function srcset_max_image_width( $size, $size_array ) {
		$size        = 1600; // Max theme image size.
		$has_sidebar = mai_has_sidebar();
		$single      = [
			'xs' => 1,
			'sm' => 1,
			'md' => 1,
			'lg' => 1,
		];
		$columns     = ( 'single' === $this->context ) ? $single : array_reverse( mai_get_breakpoint_columns( $this->args ), true ); // mobile first.
		$widths      = [];

		foreach ( $columns as $break => $count ) {
			switch ( $break ) {
				case 'xs':
					$max_width = $this->breakpoints['sm'];
					$widths[]  = $count ? floor( $max_width / $count ) : $max_width;
					break;
				case 'sm':
					$min_width = $this->breakpoints['sm'];
					$max_width = $this->breakpoints['md'];
					$widths[]  = $count ? floor( $max_width / $count ) : $max_width;
					break;
				case 'md':
					$min_width = $this->breakpoints['md'];
					$max_width = $this->breakpoints['lg'];
					$container = $has_sidebar ? $max_width * 2 / 3 : $max_width;
					$widths[]  = $count ? floor( $container / $count ) : $container;
					break;
				case 'lg':
					$min_width = $this->breakpoints['lg'];
					$container = $this->breakpoints['xl'];
					$container = $has_sidebar ? $container * 2 / 3 : $container;
					$widths[]  = $count ? floor( $container / $count ) : $container;
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
	 * @param string       $sizes         A source size value for use in a 'sizes' attribute.
	 * @param array|string $size          Requested size. Image size or array of width and height values
	 *                                    in pixels (in that order).
	 * @param string|null  $image_src     The URL to the image file or null.
	 * @param array|null   $image_meta    The image meta data as returned by wp_get_attachment_metadata() or null.
	 * @param int          $attachment_id Image attachment ID of the original image or 0.
	 *
	 * @return string
	 */
	public function calculate_image_sizes( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
		// TODO: handle 0/auto columns.

		$new_sizes   = [];
		$has_sidebar = mai_has_sidebar();
		$single      = [
			'xs' => 1,
			'sm' => 1,
			'md' => 1,
			'lg' => 1,
		];
		$columns     = ( 'single' === $this->context ) ? $single : array_reverse( mai_get_breakpoint_columns( $this->args ), true ); // mobile first.

		foreach ( $columns as $break => $count ) {
			switch ( $break ) {
				case 'xs':
					$max_width   = $this->breakpoints['sm'] - 1;
					$width       = $count ? floor( $max_width / $count ) : $max_width;
					$new_sizes[] = "(max-width:{$max_width}px) {$width}px";
					break;
				case 'sm':
					$min_width   = $this->breakpoints['sm'];
					$max_width   = $this->breakpoints['md'] - 1;
					$width       = $count ? floor( $max_width / $count ) : $max_width;
					$new_sizes[] = "(min-width:{$min_width}px) and (max-width: {$max_width}px) {$width}px";
					break;
				case 'md':
					$min_width   = $this->breakpoints['md'];
					$max_width   = $this->breakpoints['lg'] - 1;
					$container   = $has_sidebar ? $max_width * 2 / 3 : $max_width;
					$width       = $count ? floor( $container / $count ) : $container;
					$new_sizes[] = "(min-width:{$min_width}px) and (max-width: {$max_width}px) {$width}px";
					break;
				case 'lg':
					$min_width   = $this->breakpoints['lg'];
					$container   = $this->breakpoints['xl'];
					$container   = $has_sidebar ? $container * 2 / 3 : $container;
					$width       = $count ? floor( $container / $count ) : $container;
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
				$image_id = get_user_meta( $this->id, 'featured_image', true ); // TODO.
				// $image_id = $image_id ? $image_id : fallback to avatar?      // TODO.
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
			$img_aligned = in_array( $this->args['image_position'], [ 'left', 'right' ], true );

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
	 * Display the post content.
	 *
	 * Initially based off of genesis_do_post_title().
	 *
	 * @return  void
	 */
	public function do_title() {

		if ( ( 'single' === $this->context ) && ( mai_is_element_hidden( 'entry_title' ) || ( mai_has_page_header() && apply_filters( 'mai_entry_title_in_page_header', true ) ) ) ) {
			return;
		}

		$link = false;

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

					// Link it, if necessary.
					if ( ( 'archive' === $this->context ) && apply_filters( 'genesis_link_post_title', true ) ) {
						$link = true;
					}
				} else {
					// Block.
					$wrap  = 'h3';
					$title = get_the_title( $this->entry );
					$link  = true;
				}

				break;
			case 'term':
				$wrap  = 'h3'; // Only blocks use this function for terms.
				$title = $this->entry->name;
				$link  = true;
				break;
			case 'user':
				$wrap  = 'h3'; // Only blocks use this function for users.
				$title = ''; // TODO: Add title.
				$link  = true;
				break;
			default:
				$title = '';
		}

		// Bail if no title.
		if ( ! $title ) {
			return;
		}

		// If linking.
		if ( $link ) {
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
		$wrap = apply_filters( 'mai_entry_title_wrap', $wrap, $this->args );

		// Build the output.
		$output = genesis_markup(
			[
				'open'    => "<{$wrap} %s>",
				'close'   => "</{$wrap}>",
				'content' => $title,
				'context' => 'entry-title',
				'echo'    => false,
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

		// title output is left unescaped to accommodate trusted user input. See https://codex.wordpress.org/Function_Reference/the_title#Security_considerations.
		echo $output;
	}

	/**
	 * Display the post excerpt.
	 *
	 * Initially based off of genesis_do_post_content().
	 *
	 * @return  void
	 */
	public function do_excerpt() {
		$excerpt = '';

		// Excerpt.
		switch ( $this->type ) {
			case 'post':
				if ( 'single' === $this->context ) {
					// Manual excerpts only, on single posts.
					$excerpt = has_excerpt() && ! mai_is_element_hidden( 'entry_excerpt' ) ? get_the_excerpt() : '';
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
	 * @return  void
	 */
	public function do_content() {

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'entry-content',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Single needs the_content() directly, to parse_blocks and other filters.
		if ( 'single' === $this->context ) {
			the_content();

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

			echo $content;
		}

		genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'entry-content',
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
	 */
	public function do_header_meta() {

		// Bail if none.
		if ( ! isset( $this->args['header_meta'] ) || ! $this->args['header_meta'] ) {
			return;
		}

		// Run shortcodes.
		$header_meta = do_shortcode( $this->args['header_meta'] );

		if ( ! $header_meta ) {
			return;
		}

		genesis_markup(
			[
				'open'    => '<p %s>',
				'close'   => '</p>',
				'content' => genesis_strip_p_tags( $header_meta ),
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
	 */
	public function do_footer_meta() {

		// Bail if none.
		if ( ! isset( $this->args['footer_meta'] ) || ! $this->args['footer_meta'] ) {
			return;
		}

		// Run shortcodes.
		$footer_meta = do_shortcode( $this->args['footer_meta'] );

		if ( ! $footer_meta ) {
			return;
		}

		genesis_markup(
			[
				'open'    => '<p %s>',
				'close'   => '</p>',
				'content' => genesis_strip_p_tags( $footer_meta ),
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

		$more_link_text = $this->args['more_link_text'] ? $this->args['more_link_text'] : mai_get_read_more_text();

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
		if ( mai_has_template_part( 'after-entry' ) && ! mai_is_element_hidden( 'after_entry' ) ) {
			mai_render_template_part( 'after-entry' );
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
	 * @return void
	 */
	public function do_author_box() {
		// TODO: Output should be escaped.
		echo genesis_get_author_box( 'single' );
	}

	/**
	 * Render adjacent entry nav.
	 * Can't use genesis_adjacent_entry_nav() because it checks for post_type support.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function do_adjacent_entry_nav() {

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

		$previous_post_text = '<span class="screen-reader-text">' . esc_html__( 'Previous Post:', 'genesis' ) . ' </span><span class="adjacent-post-link">&#xAB; %image %title</span>';

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'pagination-previous',
				'content' => get_previous_post_link( '%link', $previous_post_text ),
				'close'   => '</div>',
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		$next_post_text = '<span class="screen-reader-text">' . esc_html__( 'Next Post:', 'genesis' ) . ' </span><span class="adjacent-post-link">%title %image &#xBB;</span>';

		genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'pagination-next',
				'content' => get_next_post_link( '%link', $next_post_text ),
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
