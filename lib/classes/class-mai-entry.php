<?php


class Mai_Entry {

	protected $entry;
	protected $args;
	protected $type;
	protected $context;
	protected $id;
	protected $url;
	protected $breakpoints;

	function __construct( $entry, $args ) {
		$this->entry       = $entry;
		$this->args        = $args;
		$this->type        = $this->args['type'];
		$this->context     = $this->args['context'];
		$this->id          = $this->get_id();
		$this->url         = $this->get_url();
		$this->breakpoints = mai_temp_get_breakpoints();
	}

	function render() {

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

		// Open.
		genesis_markup(
			[
				'open'    => "<{$wrap} %s>",
				'context' => 'entry',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

		// Check if extra wrap is needed.
		$has_inner = in_array( 'image', $this->args['show'] ) && in_array( $this->args['image_position'], array( 'left', 'right' ) );

		// If we have inner wrap.
		if ( $has_inner ) {

			// Image outside inner wrap.
			$this->do_image();

			// Inner open.
			genesis_markup(
				[
					'open'    => '<div %s>',
					'context' => 'entry-inner',
					'echo'    => true,
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);
		}

		// Loop through our elements.
		foreach( $this->args['show'] as $element ) {

			// Skip image is left or right, skip.
			if ( ( 'image' === $element ) && $has_inner ) {
				continue;
			}

			// Output the element if a method exists.
			$method = "do_{$element}";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
		}

		// If we have inner wrap.
		if ( $has_inner ) {

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

	}

	function get_id() {
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

	function get_url() {
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

	function do_image() {

		// Get the image HTML.
		$image = $this->get_image();

		// Bail if no image.
		if ( ! $image ) {
			return;
		}

		// Image.
		genesis_markup(
			[
				'open'    => '<a %s>',
				'close'   => '</a>',
				'content' => $image,
				'context' => 'entry-image-link',
				'echo'    => true,
				'atts'    => [
					'href' => $this->url,
				]
			]
		);
	}

	function get_image() {

		// Get the image ID.
		$image_id = $this->get_image_id();

		// Bail if no image ID.
		if ( ! $image_id ) {
			return;
		}

		/**
		 * Filters the output of 'wp_calculate_image_sizes()'.
		 *
		 * @param string       $sizes         A source size value for use in a 'sizes' attribute.
		 * @param array|string $size          Requested size. Image size or array of width and height values
		 *                                    in pixels (in that order).
		 * @param string|null  $image_src     The URL to the image file or null.
		 * @param array|null   $image_meta    The image meta data as returned by wp_get_attachment_metadata() or null.
		 * @param int          $attachment_id Image attachment ID of the original image or 0.
		 */
		add_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ], 10, 5 );
		$image = wp_get_attachment_image( $image_id, $this->get_image_size() );
		remove_filter( 'wp_calculate_image_sizes', [ $this, 'calculate_image_sizes' ], 10, 5 );

		return $image;
	}

	function calculate_image_sizes( $sizes, $size, $image_src, $image_meta, $attachment_id ) {

		// '(min-width: 768px) 322px, (min-width: 576px) 255px, calc( (100vw - 30px) / 2)'

		/**
		 * TODO:
		 * handle 0/auto columns.
		 */

		$new_sizes = [];

		// $image_sizes = mai_temp_get_image_sizes();
		$container = $this->breakpoints['xl'] . 'px';
		foreach( $this->get_breakpoint_columns() as $size => $count ) {
			// if ( 0 === $columns ) {
				// $new_sizes[] = 	"(min-width: 768px) 322px, (min-width: 576px) 255px, calc( (100vw - 30px) / 2)";
			// }
			switch ( $size ) {
				case 'xs':
					$max_width   = ( $this->breakpoints['sm'] + 1 ) . 'px';
					$new_sizes[] = "(max-width: {$max_width}) calc( {$container} / $count )";
				break;
				case 'sm':
					$min_width   = $this->breakpoints['sm'] . 'px';
					$max_width   = ( $this->breakpoints['md'] + 1 ) . 'px';
					$new_sizes[] = "(min-width: {$min_width}) and (max-width: {$max_width}) calc( {$container} / $count )";
				break;
				case 'md':
					$min_width   = $this->breakpoints['md'] . 'px';
					$max_width   = ( $this->breakpoints['lg'] + 1 ) . 'px';
					$new_sizes[] = "(min-width: {$min_width}) and (max-width: {$max_width}) calc( {$container} / $count )";
				break;
				case 'lg':
					$min_width   = $this->breakpoints['lg'] . 'px';
					$new_sizes[] = "(min-width: {$min_width}) calc( {$container} / $count )";
				break;
			}


		}

		return implode( ',', $new_sizes );
	}

	function get_breakpoint_columns() {

		$columns = [
			'lg' => (int) $this->args['columns'],
		];

		if ( $this->args['columns_responsive'] ) {
			$columns['md'] = (int) $this->args['columns_md'];
			$columns['sm'] = (int) $this->args['columns_sm'];
			$columns['xs'] = (int) $this->args['columns_xs'];
		} else {
			switch ( (int) $this->args['columns'] ) {
				case 6:
					$columns['md'] = 4;
					$columns['sm'] = 3;
					$columns['xs'] = 2;
				break;
				case 5:
					$columns['md'] = 3;
					$columns['sm'] = 2;
					$columns['xs'] = 2;
				break;
				case 4:
					$columns['md'] = 4;
					$columns['sm'] = 2;
					$columns['xs'] = 1;
				break;
				case 3:
					$columns['md'] = 3;
					$columns['sm'] = 1;
					$columns['xs'] = 1;
				break;
				case 2:
					$columns['md'] = 2;
					$columns['sm'] = 2;
					$columns['xs'] = 1;
				break;
				case 1:
					$columns['md'] = 1;
					$columns['sm'] = 1;
					$columns['xs'] = 1;
				break;
				case 0: // Auto.
					$columns['md'] = 0;
					$columns['sm'] = 0;
					$columns['xs'] = 0;
				break;
			}
		}

		return $columns;
	}

	function get_image_id() {

		switch ( $this->type ) {
			case 'post':
				$image_id = get_post_thumbnail_id( $this->id );
				$image_id = $image_id ? $image_id : genesis_get_image_id( 0, $this->id );
			break;
			case 'term':
				$image_id = get_term_meta( $this->id, 'mai_image', true ); // TODO.
			break;
			case 'user':
				$image_id = get_user_meta( $this->id, 'mai_image', true ); // TODO.
			break;
			default:
				$image_id = 0;
		}

		// Get fallback.
		if ( ! $image_id ) {
			// TODO;
			// $image_id = genesis_get_option( 'featured_image_fallback' );
		}

		// Filter.
		$image_id = apply_filters( 'mai_entry_image_id', $image_id, $this->entry, $this->args );

		// Bail if no image ID.
		if ( ! $image_id ) {
			return;
		}

		return $image_id;
	}

	function get_image_size() {
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

	function get_image_size_by_cols() {

		// "image-sm": "one-third  - 400",
		// "image-md": "two-thirds - 800",
		// "image-lg": "one-whole  - 1200",

		$fw_content  = ( 'full-width-content' === genesis_site_layout() ) ? true: false;
		$img_aligned = in_array( $this->args['image_position'], ['left', 'right'] );

		// If singular.
		if ( 'singular' === $this->context ) {

			$image_size = $fw_content ? 'lg' : 'md';
		}
		// Archive or block.
		else {

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
	function do_title() {

		$link = false;

		// Title.
		switch ( $this->type ) {
			case 'post':

				// Not a block.
				if ( 'block' !== $this->context ) {

					// Singular and archive wrap and title text.
					if ( 'singular' === $this->context ) {
						$wrap  = 'h1';
						$title = genesis_entry_header_hidden_on_current_page() ? get_the_title() : '';
					} else {
						$wrap  = 'h2';
						$title = get_the_title();
					}

					// If HTML5 with semantic headings, wrap in H1.
					$wrap  = genesis_get_seo_option( 'semantic_headings' ) ? 'h1' : $wrap;

					// Filter the post title text.
					$title = apply_filters( 'genesis_post_title_text', $title );

					// Wrap in H2 on static homepages if Primary Title H1 is set to title or description.
					if (
						( 'singular' === $this->context )
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
				}
				// Block.
				else {

					$wrap  = 'h3';
					$title = get_the_title( $this->entry );
					$link  = true;
				}
			break;
			case 'term':
				$wrap  = 'h3'; // Only blocks use this function for terms.
				$title = ''; // TODO.
			break;
			case 'user':
				$wrap  = 'h3'; // Only blocks use this function for users.
				$title = ''; // TODO.
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
			$title = genesis_markup(
				[
					'open'    => '<a %s>',
					'close'   => '</a>',
					'content' => $title,
					'context' => 'entry-title-link',
					'echo'    => false,
					'atts'    => [
						'href' => $this->url,
					],
					'params'  => [
						'args'  => $this->args,
						'entry' => $this->entry,
					],
				]
			);
		}

		/**
		 * Entry title wrapping element.
		 *
		 * The wrapping element for the entry title.
		 *
		 * @param  string  $wrap The wrapping element (h1, h2, p, etc.).
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
			$output = apply_filters( 'genesis_post_title_output', $output, $wrap, $title ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title output is left unescaped to accommodate trusted user input. See https://codex.wordpress.org/Function_Reference/the_title#Security_considerations.
		}

		echo $output;

	}

	/**
	 * Display the post excerpt.
	 *
	 * Initially based off of genesis_do_post_content().
	 *
	 * @return  void
	 */
	function do_excerpt() {

		// Excerpt.
		switch ( $this->type ) {
			case 'post':
				$excerpt = get_the_excerpt();
				break;
			case 'term':
				$excerpt = ''; // TODO (intro text).
				break;
			case 'user':
				$excerpt = ''; // TODO (possibly not an option for users).
				break;
			default:
				$excerpt = '';
		}

		// Limit.
		if ( $this->args['content_limit'] > 0 ) {
			// TODO: Add [...] or whatever the read more thing is?
			$excerpt = mai_get_content_limit( $excerpt, $this->args['content_limit'] );
		}

		// Output.
		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'entry-excerpt',
				'content' => $excerpt,
				'echo'    => true,
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
	function do_content() {

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
		if ( $content && ( $this->args['content_limit'] > 0 ) ) {
			// TODO: Add [...] or whatever the read more thing is?
			$content = mai_get_content_limit( $content, $this->args['content_limit'] );
		}

		// Output.
		genesis_markup(
			[
				'open'    => '<div %s>',
				'close'   => '</div>',
				'context' => 'entry-content',
				'content' => $content,
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
	function do_header_meta() {

		// Bail if none.
		if ( ! $this->args['header_meta'] ) {
			return;
		}

		// Run shortcodes.
		$this->args['header_meta'] = do_shortcode( $this->args['header_meta'] );

		genesis_markup(
			[
				'open'    => '<p %s>',
				'close'   => '</p>',
				'content' => genesis_strip_p_tags( $this->args['header_meta'] ),
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
	function do_footer_meta() {

		// Bail if none.
		if ( ! $this->args['footer_meta'] ) {
			return;
		}

		// Run shortcodes.
		$this->args['footer_meta'] = do_shortcode( $this->args['footer_meta'] );

		genesis_markup(
			[
				'open'    => '<p %s>',
				'close'   => '</p>',
				'content' => genesis_strip_p_tags( $this->args['footer_meta'] ),
				'context' => 'entry-meta-after-content',
				'echo'    => true,
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);

	}

	function do_more_link() {

		// Link.
		switch ( $this->type ) {
			case 'post':
				$more_link = get_the_permalink( $this->entry );
			break;
			case 'term':
				$more_link = ''; // TODO.
			break;
			case 'user':
				$more_link = ''; // TODO.
			break;
			default:
				$more_link = '';
		}

		// Bail if no link.
		if ( ! $more_link ) {
			return;
		}

		$more_link_text = $this->args['more_link_text'] ? $this->args['more_link_text'] : __( 'Read More', 'mai-engine' );

		genesis_markup(
			[
				'open'    => '<a %s>',
				'close'   => '</a>',
				'content' => $more_link_text,
				'context' => 'entry-read-more',
				'atts'    => [
					'href' => $more_link,
				],
				'params'  => [
					'args'  => $this->args,
					'entry' => $this->entry,
				],
			]
		);
	}

	function do_after_entry_widget_area() {

		genesis_widget_area(
			'after-entry',
			[
				'before' => '<div class="after-entry widget-area">',
				'after'  => '</div>',
			]
		);
	}

	function do_author_box() {
		echo genesis_get_author_box( 'single' );
	}

	function do_adjacent_entry_nav() {
		genesis_adjacent_entry_nav();
	}

	/**
	 * Backwards compatibility for Genesis hooks.
	 */
	function do_genesis_entry_header() {
		do_action( 'genesis_entry_header' );
	}
	function do_genesis_before_entry_content() {
		do_action( 'genesis_before_entry_content' );
	}
	function do_genesis_entry_content() {
		do_action( 'genesis_entry_content' );
	}
	function do_genesis_after_entry_content() {
		do_action( 'genesis_after_entry_content' );
	}
	function do_genesis_entry_footer() {
		do_action( 'genesis_entry_footer' );
	}

}
