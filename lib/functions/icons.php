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
 * Gets an icon.
 *
 * @since 0.1.0
 * @since 2.11.0 Added width and height attributes to svg.
 *
 * @param array $args Icon args.
 *
 * @return null|string
 */
function mai_get_icon( $args ) {
	if ( ! class_exists( 'Mai_Icons_Plugin' ) ) {
		if ( ! is_admin() ) {
			return;
		}

		$link = sprintf( '<a target="_blank" href="https://bizbudding.com/mai-theme/plugins/mai-icons/">%s</a>', __( 'Mai Icons', 'mai-engine' ) );
		$text = sprintf( __( '%s plugin required.', 'mai-engine' ), $link );

		return sprintf( '<p>%s</p>', $text );
	}

	$args = shortcode_atts(
		mai_get_icon_default_args(),
		$args,
		'mai_icon'
	);

	$args = array_map(
		'esc_html',
		$args
	);

	$size = mai_get_width_height_attribute( $args['size'] );
	$svg  = mai_get_svg_icon( $args['icon'], $args['style'], [
		'width'  => $size,
		'height' => $size,
	] );

	if ( ! $svg ) {
		return '';
	}

	// Build classes.
	$class = sprintf( 'mai-icon mai-icon-%s', $args['icon'] );

	// Add custom classes.
	if ( ! empty( $args['class'] ) ) {
		$class .= ' ' . esc_attr( $args['class'] );
	}

	// Get it started.
	$atts = [
		'class' => $class,
		'style' => '',
	];

	// Build inline styles.
	$atts['style'] .= sprintf( 'display:%s;', $args['display'] );
	$atts['style'] .= sprintf( 'text-align:%s;', $args['align'] );
	$atts['style'] .= sprintf( '--icon-margin:%s %s %s %s;', mai_get_unit_value( $args['margin_top'] ), mai_get_unit_value( $args['margin_right'] ), mai_get_unit_value( $args['margin_bottom'] ), mai_get_unit_value( $args['margin_left'] ) );
	$atts['style'] .= sprintf( '--icon-padding:%s;', mai_get_unit_value( $args['padding'] ) );

	if ( $args['size'] ) {
		$atts['style'] .= sprintf( '--icon-size:%s;', mai_get_unit_value( $args['size'] ) );
	}

	if ( $args['color_icon'] ) {
		$atts['style'] .= sprintf( '--icon-color:%s;', mai_get_color_css( $args['color_icon'] ) );
	}

	if ( $args['color_background'] ) {
		$atts['style'] .= sprintf( '--icon-background:%s;', mai_get_color_value( $args['color_background'] ) );
	}

	if ( $args['color_shadow'] ) {
		$atts['style'] .= sprintf( '--icon-box-shadow:%s %s %s %s;', mai_get_unit_value( $args['x_offset'] ), mai_get_unit_value( $args['y_offset'] ), mai_get_unit_value( $args['blur'] ), $args['color_shadow'] );
	}

	if ( $args['color_text_shadow'] ) {
		$atts['style'] .= sprintf( '--icon-text-shadow:%s %s %s %s;', mai_get_unit_value( $args['text_shadow_x_offset'] ), mai_get_unit_value( $args['text_shadow_y_offset'] ), mai_get_unit_value( $args['text_shadow_blur'] ), $args['color_text_shadow'] );
	}

	if ( $args['border_width'] && $args['color_border'] ) {
		$atts['style'] .= sprintf( '--icon-border:%s solid %s;', mai_get_unit_value( $args['border_width'] ), mai_get_unit_value( $args['color_border'] ) );
	}

	if ( $args['border_radius'] ) {
		$atts['style'] .= sprintf( '--icon-border-radius:%s;', mai_get_unit_value( $args['border_radius'] ) );
	}

	$tag = 'span';

	if ( $args['link'] && ! is_admin() ) {
		$tag           = 'a';
		$atts['href']  = esc_url( $args['link'] );
		$atts['title'] = esc_attr( $args['link_title'] );

		if ( $args['link_target'] ) {
			$atts['target'] = '_blank';
			$atts['rel']    = 'noopener nofollow';
		}
	}

	$icon = '';

	if ( $args['cart_total'] ) {
		$icon .= genesis_markup(
			[
				'open'    => '<span class="mai-icon-container">',
				'context' => 'mai-icon-container',
				'echo'    => false,
			]
		);
	}

	$icon .= genesis_markup(
		[
			'open'    => "<{$tag} %s>" . '<span class="mai-icon-wrap">',
			'close'   => '</span>' . "</{$tag}>",
			'content' => $svg,
			'context' => 'mai-icon',
			'echo'    => false,
			'atts'    => $atts,
		]
	);

	if ( $args['cart_total'] ) {
		$icon .= mai_get_cart_total();
		$icon .= genesis_markup(
			[
				'close'   => '</span>',
				'context' => 'mai-icon-container',
				'echo'    => false,
			]
		);
	}

	return $icon;
}

/**
 * Gets list of icon shortcode attributes.
 *
 * @since 0.1.0
 * @since 2.11.0 Added filter.
 *
 * @return array
 */
function mai_get_icon_default_args() {
	$defaults = [
		'style'                => 'light',
		'icon'                 => 'heart',
		'icon_brand'           => 'wordpress-simple',
		'display'              => 'block',
		'align'                => 'center',
		'size'                 => '40',
		'link'                 => '',
		'link_title'           => '',
		'link_target'          => '',
		'cart_total'           => false,
		'class'                => '',
		'color_icon'           => 'currentColor',
		'color_background'     => '',
		'color_border'         => '',
		'color_shadow'         => '',
		'color_text_shadow'    => '',
		'margin_top'           => 0,
		'margin_right'         => 0,
		'margin_left'          => 0,
		'margin_bottom'        => 0,
		'padding'              => 0,
		'border_width'         => 0,
		'border_radius'        => 'var(--border-radius)',
		'x_offset'             => 0,
		'y_offset'             => 0,
		'blur'                 => 0,
		'text_shadow_x_offset' => 0,
		'text_shadow_y_offset' => 0,
		'text_shadow_blur'     => 0,
	];

	$defaults = apply_filters( 'mai_icon_defaults', $defaults );

	return $defaults;
}

/**
 * Gets an svg from assets.
 *
 * @since 0.2.0
 *
 * @param string $name  SVG name.
 * @param string $class SVG class name.
 *
 * @return string
 */
function mai_get_svg( $name, $class = '' ) {
	$svg = mai_get_svg_file( $name );

	if ( ! $svg ) {
		return '';
	}

	if ( $class ) {
		$dom  = mai_get_dom_document( $svg );
		$svgs = $dom->getElementsByTagName( 'svg' );

		/**
		 * DOM Element.
		 *
		 * @var DOMElement $first_svg First dom element.
		 */
		$first_svg = isset( $svgs[0] ) ? $svgs[0] : null;

		if ( $first_svg ) {
			$classes = mai_add_classes( $class, $first_svg->getAttribute( 'class' ) );
			$first_svg->setAttribute( 'class', $classes );
			$svg = $dom->saveHTML();
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return trim( $svg );
}

/**
 * Returns an SVG string.
 *
 * @since 0.1.0
 * @since 2.4.0 Added check for dom element.
 * @since 2.11.0 Added default width and height attributes.
 *
 * @param string $name  SVG name.
 * @param string $style SVG style.
 * @param array  $atts  SVG HTML attributes.
 *
 * @return string
 */
function mai_get_svg_icon( $name, $style = 'light', $atts = [] ) {
	$svg = mai_get_svg_icon_file( $name, $style );

	if ( ! $svg ) {
		return '';
	}

	if ( ! isset( $atts['width'] ) ) {
		$atts['width'] = '24';
	}

	if ( ! isset( $atts['height'] ) ) {
		$atts['height'] = '24';
	}

	if ( $atts ) {
		$dom  = mai_get_dom_document( $svg );
		$svgs = $dom->getElementsByTagName( 'svg' );

		foreach ( $atts as $att => $value ) {

			/**
			 * DOM Element.
			 *
			 * @var DOMElement $first_svg First dom element.
			 */
			$first_svg = isset( $svgs[0] ) ? $svgs[0] : null;

			if ( $first_svg ) {
				$first_svg->setAttribute( $att, $value );
			}
		}

		$svg = $dom->saveHTML();
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return trim( $svg );
}

/**
 * Gets an svg file. Cached so the same file is never fetched twice on the same page.
 *
 * @since 2.11.0
 *
 * @param string $name The svg file name.
 *
 * @return string
 */
function mai_get_svg_file( $name ) {
	static $files = null;

	if ( is_array( $files ) && isset( $files[ $name ] ) ) {
		return $files[ $name ];
	}

	$file = mai_get_dir() . "assets/svg/$name.svg";

	if ( ! file_exists( $file ) ) {
		$files[ $name ] = '';
		return $files[ $name ];
	}

	$svg            = file_get_contents( $file );
	$files[ $name ] = trim( $svg );

	return $files[ $name ];
}

/**
 * Gets an svg icon file. Cached so the same file is never fetched twice on the same page.
 *
 * @since 2.11.0
 *
 * @param string $name  The svg file name.
 * @param string $style The svg style.
 *
 * @return string
 */
function mai_get_svg_icon_file( $name, $style = 'light' ) {
	static $files = null;

	if ( is_array( $files ) && isset( $files[ $style ][ $name ] ) ) {
		return $files[ $style ][ $name ];
	}

	// Dir requires Mai Icons.
	$dir = mai_get_icons_dir();

	if ( ! $dir ) {
		$files[ $style ][ $name ] = '';
		return $files[ $style ][ $name ];
	}

	$file = $dir . "/svgs/$style/$name.svg";

	if ( ! file_exists( $file ) ) {
		$files[ $style ][ $name ] = '';
		return $files[ $style ][ $name ];
	}

	$svg                      = file_get_contents( $file );
	$files[ $style ][ $name ] = trim( $svg );

	return $files[ $style ][ $name ];
}

/**
 * Gets an svg icon url.
 *
 * @since 0.1.0
 *
 * @param string $name  SVG name.
 * @param string $style SVG style.
 *
 * @return string
 */
function mai_get_svg_icon_url( $name, $style = 'light' ) {
	$url = mai_get_icons_url();
	$url = $url ? $url . "/svgs/$style/$name.svg" : '';

	return $url;
}

/**
 * Gets icons directory path.
 *
 * @since 2.14.0
 *
 * @uses Mai Icons plugin.
 *
 * @return string
 */
function mai_get_icons_dir() {
	return function_exists( 'mai_icons_get_dir' ) ? mai_icons_get_dir() : false;
}

/**
 * Gets icons directory url.
 *
 * @since 2.14.0
 *
 * @uses Mai Icons plugin.
 *
 * @return string
 */
function mai_get_icons_url() {
	return function_exists( 'mai_icons_get_url' ) ? mai_icons_get_url() : false;
}
