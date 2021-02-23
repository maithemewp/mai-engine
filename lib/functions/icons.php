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
 *
 * @param array $args Icon args.
 *
 * @return null|string
 */
function mai_get_icon( $args ) {
	$args = shortcode_atts(
		mai_get_icon_default_args(),
		$args,
		'mai_icon'
	);

	$args = array_map(
		'esc_html',
		$args
	);

	$svg = mai_get_svg_icon( $args['icon'], $args['style'] );

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
		$atts['style'] .= sprintf( '--icon-color:%s;', $args['color_icon'] );
	}

	if ( $args['color_background'] ) {
		$atts['style'] .= sprintf( '--icon-background:%s;', mai_get_color( $args['color_background'] ) );
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
		$radius        = explode( ' ', trim( $args['border_radius'] ) );
		$radius        = array_map( 'mai_get_unit_value', $radius );
		$radius        = array_filter( $radius );
		$atts['style'] .= sprintf( '--icon-border-radius:%s;', implode( ' ', $radius ) );
	}

	$tag = 'span';

	if ( $args['link'] && ! is_admin() ) {
		$tag          = 'a';
		$atts['href'] = esc_url( $args['link'] );

		if ( $args['link_target'] ) {
			$atts['target'] = '_blank';
			$atts['rel']    = 'noopener nofollow';
		}
	}

	$icon = genesis_markup(
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
	}

	return $icon;
}

/**
 * Gets list of icon shortcode attributes.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_icon_default_args() {
	return [
		'style'                => 'light',
		'icon'                 => 'bolt',
		'icon_brand'           => 'wordpress-simple',
		'display'              => 'block',
		'align'                => 'center',
		'size'                 => '40',
		'link'                 => '',
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
		'border_radius'        => '50%',
		'x_offset'             => 0,
		'y_offset'             => 0,
		'blur'                 => 0,
		'text_shadow_x_offset' => 0,
		'text_shadow_y_offset' => 0,
		'text_shadow_blur'     => 0,
	];
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
	return $svg;
}

/**
 * Returns an SVG string.
 *
 * @since 2.4.0 Added check for dom element.
 * @since 0.1.0
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
	return $svg;
}

/**
 * Gets an svg file. Cached so the same file is never fetched twice on the same page.
 *
 * @since TBD
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
	$svg            = mai_convert_svg_xmlns( $svg );
	$files[ $name ] = $svg;

	return $files[ $name ];
}

/**
 * Gets an svg icon file. Cached so the same file is never fetched twice on the same page.
 *
 * @since TBD
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

	$file = mai_get_dir() . "assets/icons/svgs/$style/$name.svg";

	if ( ! file_exists( $file ) ) {
		$files[ $style ][ $name ] = '';
		return $files[ $style ][ $name ];
	}

	$svg                      = file_get_contents( $file );
	$svg                      = mai_convert_svg_xmlns( $svg );
	$files[ $style ][ $name ] = $svg;

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
	return mai_get_url() . "assets/icons/svgs/$style/$name.svg";
}

/**
 * Converts an svg xmlns attribute to https if the site uses https.
 *
 * @since 2.6.0
 *
 * @access private
 *
 * @param string $svg The svg HTML.
 *
 * @return string
 */
function mai_convert_svg_xmlns( $svg ) {
	if ( ! mai_is_https() ) {
		return $svg;
	}

	$dom   = mai_get_dom_document( $svg );
	$first = mai_get_dom_first_child( $dom );

	if ( $first ) {
		$xmlns = $first->attributes->getNamedItem( 'xmlns' );
		$xmlns = $xmlns->value;

		if ( $xmlns ) {
			$xmlns = str_replace( 'http:', 'https:', $xmlns );
			$first->setAttribute( 'xmlns', $xmlns );
			$svg = $dom->saveHTML();
		}
	}

	return $svg;
}
