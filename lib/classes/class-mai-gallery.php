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

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Class Mai_Gallery.
 */
class Mai_Gallery {
	/**
	 * Args.
	 *
	 * @var array $args.
	 */
	protected $args;

	/**
	 * Image size.
	 *
	 * @var string $image_size.
	 */
	protected $image_size;

	/**
	 * Mai_Gallery constructor.
	 *
	 * @since TBD
	 *
	 * @param array $args Gallery args.
	 *
	 * @return void
	 */
	function __construct( $args ) {
		$args = wp_parse_args( $args,
			[
				'preview'                => false,
				'class'                  => '',
				'images'                 => [],
				'image_orientation'      => 'landscape',
				'image_size'             => 'sm',
				'shadow'                 => false,
				'columns'                => 3,
				'columns_responsive'     => '',
				'columns_md'             => '',
				'columns_sm'             => '',
				'columns_xs'             => '',
				'align_columns'          => '',
				'align_columns_vertical' => '',
				'column_gap'             => 'md',
				'row_gap'                => 'md',
				'margin_top'             => '',
				'margin_bottom'          => '',
			]
		);

		// Sanitize.
		$args = [
			'preview'                => mai_sanitize_bool( $args['preview'] ),
			'class'                  => esc_html( $args['class'] ),
			'images'                 => $args['images'] ? array_map( 'absint', (array) $args['images'] ) : [],
			'image_orientation'      => esc_html( $args['image_orientation'] ),
			'image_size'             => esc_html( $args['image_size'] ),
			'shadow'                 => mai_sanitize_bool( $args['shadow'] ),
			'columns'                => absint( $args['columns'] ),
			'columns_responsive'     => mai_sanitize_bool( $args['columns_responsive'] ),
			'columns_md'             => absint( $args['columns_md'] ),
			'columns_sm'             => absint( $args['columns_sm'] ),
			'columns_xs'             => absint( $args['columns_xs'] ),
			'align_columns'          => esc_html( $args['align_columns'] ),
			'align_columns_vertical' => esc_html( $args['align_columns_vertical'] ),
			'column_gap'             => esc_html( $args['column_gap'] ),
			'row_gap'                => esc_html( $args['row_gap'] ),
			'margin_top'             => sanitize_html_class( $args['margin_top'] ),
			'margin_bottom'          => sanitize_html_class( $args['margin_bottom'] ),
		];

		$this->args       = $args;
		$this->image_size = $this->get_image_size();
	}

	/**
	 * Displays gallery.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function render() {
		echo $this->get();
	}

	/**
	 * Gets gallery.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	function get() {
		$html  = '';

		if ( ! $this->args['images'] ) {
			if ( $this->args['preview'] ) {
				$text  = __( 'Add gallery images in the block sidebar settings.', 'mai-engine' );
				$html .= sprintf( '<p style="display:flex;justify-content:center;align-items:center;color:var(--body-color);font-family:var(--body-font-family);font-weight:var(--body-font-weight);font-size:var(--body-font-size);opacity:0.62;"><span class="dashicons dashicons-format-gallery"></span>&nbsp;&nbsp;%s</p>', $text );
			}

			return $html;
		}

		$atts = [
			'class' => 'mai-gallery',
		];

		$atts = mai_get_columns_atts( $atts, $this->args );

		if ( $this->args['class'] ) {
			$atts['class'] = mai_add_classes( $this->args['class'], $atts['class'] );
		}

		if ( $this->args['margin_top'] ) {
			$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-top', $this->args['margin_top'] ), $atts['class'] );
		}

		if ( $this->args['margin_bottom'] ) {
			$atts['class'] = mai_add_classes( sprintf( 'has-%s-margin-bottom', $this->args['margin_bottom'] ), $atts['class'] );
		}

		if ( $this->args['shadow'] ) {
			$atts['class'] = mai_add_classes( 'has-drop-shadow', $atts['class'] );
		}

		$html .= genesis_markup(
			[
				'open'    => '<div %s>',
				'context' => 'mai-gallery',
				'echo'    => false,
				'atts'    => $atts,
				'params'  => [
					'args' => $this->args,
				],
			]
		);

			foreach ( $this->args['images'] as $image_id ) {
				$image = wp_get_attachment_image(
					$image_id,
					$this->image_size,
					false,
					[
						'class' => "mai-gallery-image size-{$this->image_size}",
					]
				);

				if ( ! $image ) {
					continue;
				}

				$caption = wp_get_attachment_caption( $image_id );

				if ( $caption ) {
					$image .= sprintf( '<figcaption>%s</figcaption>', $caption );
				}

				$html .= genesis_markup(
					[
						'open'    => '<figure %s>',
						'close'   => '</figure>',
						'context' => 'mai-gallery-item',
						'content' => $image,
						'echo'    => false,
						'atts'    => [
							'class' => 'mai-gallery-item is-column',
						],
						'params'  => [
							'args' => $this->args,
						],
					]
				);
			}

		$html .= genesis_markup(
			[
				'close'   => '</div>',
				'context' => 'mai-gallery',
				'echo'    => false,
				'params'  => [
					'args' => $this->args,
				],
			]
		);

		return $html;
	}

	/**
	 * Gets the image size.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_image_size() {
		if ( mai_has_image_orientiation( $this->args['image_orientation'] ) ) {
			$image_size = 'sm';
			$image_size = sprintf( '%s-%s', $this->args['image_orientation'], $image_size );

		} else {
			$image_size = $this->args['image_size'];
		}

		// Filter.
		$image_size = apply_filters( 'mai_gallery_image_size', $image_size, $this->args );

		return esc_attr( $image_size );
	}
}
