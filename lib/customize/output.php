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

add_filter( 'kirki_inline_styles_id', 'mai_kirki_inline_styles_id' );
/**
 * Changes kirki inline styles element id.
 *
 * @since 2.21.0
 *
 * @return string
 */
function mai_kirki_inline_styles_id( $id ) {
	return 'mai-inline-styles';
}

add_action( 'after_switch_theme',       'mai_flush_customizer_transients' );
add_action( 'customize_save_after',     'mai_flush_customizer_transients' );
add_action( 'update_option_mai-engine', 'mai_flush_customizer_transients' );
/**
 * Deletes kirki transients when switching themes, when the Customizer is saved, or when mai-engine option is updated.
 *
 * @since 2.12.0
 * @since 2.21.0 Added updated_option hook.
 *
 * @return void
 */
function mai_flush_customizer_transients() {
	$transients = [
		'mai_dynamic_css',
		'mai_dynamic_fonts',
	];
	foreach ( $transients as $transient ) {
		delete_transient( $transient );
	}
}

add_action( 'save_post', 'mai_save_post_flush_customizer_transients', 999, 3 );
/**
 * Flush transients when saving/updating posts.
 *
 * @since 2.21.0
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 *
 * @return void
 */
function mai_save_post_flush_customizer_transients( $post_id, $post, $update ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	mai_flush_customizer_transients();
}

add_filter( 'kirki_global_styles', 'mai_add_kirki_css' );
/**
 * Outputs kirki css.
 *
 * @since 2.12.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_kirki_css( $css ) {
	/**
	 * Check if this filter ran already.
	 * loop_controls() method in Kirki calls this more than once.
	 */
	static $has_run = false;

	if ( $has_run ) {
		return $css;
	}

	$has_run   = true;
	$transient = 'mai_dynamic_css';
	$admin     = is_admin();
	$ajax      = wp_doing_ajax();
	$preview   = is_customize_preview();

	if ( ! ( $admin || $ajax || $preview ) && $cached_css = get_transient( $transient ) ) {
		return $cached_css;
	}

	// Make sure :root is set before adding to it below.
	if ( ! isset( $css['global'][':root'] ) ) {
		$css['global'][':root'] = [];
	}

	$css = mai_add_breakpoint_custom_properties( $css );
	$css = mai_add_title_area_custom_properties( $css );
	$css = mai_add_fonts_custom_properties( $css );
	$css = mai_add_colors_css( $css );
	$css = mai_add_button_text_colors( $css );
	$css = mai_add_extra_custom_properties( $css );

	if ( ! ( $admin || $ajax || $preview ) ) {
		set_transient( $transient, $css, HOUR_IN_SECONDS );
	}

	return $css;
}


add_filter( 'kirki_enqueue_google_fonts', 'mai_add_kirki_fonts', 99 );
/**
 * Loads additional fonts and variants.
 *
 * @since 2.12.0
 *
 * @param array $fonts All fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_kirki_fonts( $fonts ) {
	/**
	 * Check if this filter ran already.
	 */
	static $has_run = false;

	if ( $has_run ) {
		return $fonts;
	}

	$has_run = true;

	if ( ! $fonts ) {
		return $fonts;
	}

	$transient = 'mai_dynamic_fonts';
	$admin     = is_admin();
	$ajax      = wp_doing_ajax();
	$preview   = is_customize_preview();

	if ( ! ( $admin || $ajax || $preview ) && $cached_fonts = get_transient( $transient ) ) {
		return $cached_fonts;
	}

	$fonts = mai_add_font_variants( $fonts );
	$fonts = mai_add_additional_fonts( $fonts );

	// Remove any duplicates.
	foreach ( $fonts as $font_family => $font_variants ) {
		$fonts[ $font_family ] = array_unique( $fonts[ $font_family ] );
	}

	if ( ! ( $admin || $ajax || $preview ) ) {
		set_transient( $transient, $fonts, HOUR_IN_SECONDS );
	}

	return $fonts;
}

add_filter( 'kirki_global_styles', 'mai_add_kirki_page_header_css' );
/**
 * Outputs kirki page header css.
 * This can't be cached because it can be different per content type.
 *
 * @since 2.13.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_kirki_page_header_css( $css ) {
	/**
	 * Check if this filter ran already.
	 * loop_controls() method in Kirki calls this more than once.
	 */
	static $has_run = false;

	if ( $has_run ) {
		return $css;
	}

	$has_run = true;

	return array_merge( $css, mai_add_page_header_content_type_css( $css ) );
}

/**
 * Outputs breakpoint custom property.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_breakpoint_custom_properties( $css ) {
	$props       = [];
	$breakpoints = mai_get_breakpoints();

	foreach ( $breakpoints as $name => $size ) {
		$props[ '--breakpoint-' . $name ] = $size . 'px';
	}

	// Add breakpoints to beginning of array cause that's how Mike likes to see them.
	if ( $props ) {
		$css['global'][':root'] = array_merge( $props, $css['global'][':root'] );
	}

	return $css;
}

/**
 * Outputs title area custom properties.
 *
 * @since 2.8.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_title_area_custom_properties( $css ) {
	$css['global'][':root']['--header-shrink-offset'] = mai_get_unit_value( mai_get_header_shrink_offset() );

	return $css;
}

/**
 * Adds typography settings custom properties to Kirki output.
 *
 * @since 2.0.0
 * @since 2.21.0 Full refactor.
 *
 * @param array $css Kirki CSS output array.
 *
 * @return array
 */
function mai_add_fonts_custom_properties( $css ) {
	$elements = [
		'body'    => mai_get_font_weights( 'body' ),
		'heading' => mai_get_font_weights( 'heading' ),
	];

	foreach ( $elements as $element => $weights ) {
		$family   = mai_get_font_family( $element );
		$variants = mai_get_font_variants( $element );

		if ( $family ) {
			$css['global'][':root'][ sprintf( '--%s-font-family', $element ) ] = $family;
		}

		$css['global'][':root'][ sprintf( '--%s-font-weight', $element ) ] = mai_isset( $weights, 'default', '400' );

		if ( $weights['light'] ) {
			$css['global'][':root'][ sprintf( '--%s-font-weight-light', $element ) ] = $weights['light'];
		}

		if ( $weights['bold'] ) {
			$css['global'][':root'][ sprintf( '--%s-font-weight-bold', $element ) ]  = $weights['bold'];
		}
		// Fallback since we always need --body-font-weight-bold declared.
		elseif ( 'body' === $element ) {
			$css['global'][':root'][ sprintf( '--%s-font-weight-bold', $element ) ]  = 'bold';
		}

		if ( isset( $variants['default'] ) && mai_has_string( 'italic', $variants['default'] ) ) {
			$css['global'][':root'][ sprintf( '--%s-font-style', $element ) ] = 'italic';
		}
	}

	return $css;
}

/**
 * Outputs named (non-element) color css.
 *
 * @since 2.2.1 Added important rules for button hover state.
 * @since 2.0.0 Added.
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_colors_css( $css ) {
	$colors = mai_get_colors();
	$shades = [ 'primary', 'secondary', 'link' ];

	if ( $colors ) {
		foreach ( $colors as $name => $color ) {
			if ( ! $color ) {
				continue;
			}

			$shade                                        = in_array( $name, $shades );
			$property                                     = 'var(--color-' . $name . ') !important';
			$css['global'][':root'][ '--color-' . $name ] = $color;

			if ( $shade ) {
				$light = mai_get_color_variant( $colors[ $name ], 'light', 8 );
				$dark  = mai_get_color_variant( $colors[ $name ], 'dark', 10 );

				if ( $light ) {
					$css['global'][':root'][ '--color-' . $name . '-light' ] = $light;
				}

				if ( $dark ) {
					$css['global'][':root'][ '--color-' . $name . '-dark' ] = $dark;
				}
			}

			$css['global'][ '.has-' . $name . '-color' ]['color']                       = 'var(--color-' . $name . ') !important';
			$css['global'][ '.has-' . $name . '-color' ]['--body-color']                = 'var(--color-' . $name . ')';
			$css['global'][ '.has-' . $name . '-color' ]['--heading-color']             = 'var(--color-' . $name . ')';
			$css['global'][ '.has-' . $name . '-color' ]['--caption-color']             = 'var(--color-' . $name . ')';
			$css['global'][ '.has-' . $name . '-color' ]['--cite-color']                = 'var(--color-' . $name . ')';
			$css['global'][ '.has-' . $name . '-background-color' ]['background-color'] = 'var(--color-' . $name . ') !important';
		}
	}

	return $css;
}

/**
 * Outputs contrast button text custom property.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS.
 *
 * @return array
 */
function mai_add_button_text_colors( $css ) {
	$buttons = [
		'primary'   => '',
		'secondary' => 'secondary-',
	];

	foreach ( $buttons as $button => $suffix ) {
		$color   = mai_get_color_value( $button );
		$text    = mai_is_light_color( $color ) ? mai_get_color_variant( $color, 'dark', 60 ) : mai_get_color_value( 'white' );
		$white   = mai_get_color_value( 'white' );
		$heading = mai_get_color_value( 'heading' );
		$text    = $white === $color ? $heading : $text;

		$css['global'][':root'][ '--button-' . $suffix . 'color' ] = mai_get_color_css( $text );
	}

	return $css;
}

/**
 * Adds any other custom properties defined in config to output.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS array.
 *
 * @return mixed
 */
function mai_add_extra_custom_properties( $css ) {
	$extra = mai_get_global_styles( 'extra' );

	foreach ( $extra as $property => $value ) {
		$css['global'][':root'][ '--' . esc_attr( $property ) ] = esc_attr( $value );
	}

	return $css;
}

/**
 * Loads font family variants.
 *
 * @since 2.21.0
 *
 * @param array $fonts All fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_font_variants( $fonts ) {
	$elements = [
		'body'    => mai_get_font_weights( 'body' ),
		'heading' => mai_get_font_weights( 'heading' ),
	];

	foreach ( $elements as $element => $weights ) {
		$family = mai_get_font_family( $element );

		if ( isset( $fonts[ $family ] ) ) {
			$variants = array_values( array_filter( array_values( mai_get_font_variants( $element ) ) ) );

			foreach ( $variants as $variant ) {
				// Typecast to array so individual and 'add' values still work.
				$fonts[ $family ] = array_unique( array_merge( $fonts[ $family ], (array) $variant ) );
			}
		}
	}

	return $fonts;
}

/**
 * Loads any other Google font families defined in the config.
 *
 * @since 2.0.0
 *
 * @param array $fonts All Google Fonts to be enqueued.
 *
 * @return mixed
 */
function mai_add_additional_fonts( $fonts ) {
	$fonts_config = mai_get_global_styles( 'fonts' );

	unset( $fonts_config['body'] );
	unset( $fonts_config['heading'] );

	if ( ! $fonts_config ) {
		return $fonts;
	}

	$google_fonts = mai_get_kirki_google_fonts();

	if ( $fonts_config ) {
		foreach ( $fonts_config as $element => $args ) {
			$font_family = mai_get_default_font_family( $element );

			// Skip if not a Google Font.
			if ( ! ( isset( $google_fonts[ $font_family ] ) && isset( $google_fonts[ $font_family ]['variants'] ) ) ) {
				continue;
			}

			$font_weights = mai_get_default_font_weights( $element );
			$available    = $google_fonts[ $font_family ]['variants'];

			// Skip if none available.
			if ( ! $available ) {
				continue;
			}

			foreach ( $font_weights as $font_weight ) {
				$font_weight = mai_get_font_variant_for_kirki( $font_weight );

				// Skip if config weight is not a variant in this family.
				if ( ! in_array( $font_weight, $available, true ) ) {
					continue;
				}

				$fonts[ $font_family ][] = (string) $font_weight;
			}
		}
	}

	return $fonts;
}

/**
 * Adds page header styles to kirki output.
 *
 * @since 2.0.0
 *
 * @param array $css Kirki CSS output.
 *
 * @return array
 */
function mai_add_page_header_content_type_css( $css ) {
	$types = array_merge( mai_get_page_header_types( 'archive' ), mai_get_page_header_types( 'single' ) );

	if ( empty( $types ) ) {
		return $css;
	}

	$config     = mai_get_config( 'settings' )['page-header'];
	$background = (string) mai_get_template_arg( 'page-header-background-color', mai_get_option( 'page-header-background-color', mai_get_color_value( $config['background-color'] ) ) );
	$opacity    = (string) mai_get_page_header_overlay_opacity();

	if ( $background ) {
		$css['global'][':root']['--page-header-background'] = $background;
	}

	if ( '' !== $opacity ) {
		$css['global'][':root']['--page-header-overlay-opacity'] = $opacity;
	}

	$spacing = mai_get_option( 'page-header-spacing', $config['spacing'] );
	$top     = isset( $spacing['top'] ) && '' !== $spacing['top'] ? $spacing['top'] : $config['spacing']['top'];
	$bottom  = isset( $spacing['bottom'] ) && '' !== $spacing['bottom'] ? $spacing['bottom'] : $config['spacing']['bottom'];

	$css['global'][':root']['--page-header-padding-top']    = mai_get_unit_value( $top );
	$css['global'][':root']['--page-header-padding-bottom'] = mai_get_unit_value( $bottom );

	$content_width = mai_get_option( 'page-header-content-width', $config['content-width'] );

	if ( $content_width ) {
		$css['global'][':root']['--page-header-inner-max-width'] = sprintf( 'var(--breakpoint-%s)', esc_attr( $content_width ));
	}

	$content_align = mai_get_option( 'page-header-content-align', $config['content-align'] );

	if ( $content_align ) {
		$css['global'][':root']['--page-header-justify-content'] = mai_get_flex_align( esc_attr( $content_align ) );
	}

	$text_align = mai_get_option( 'page-header-text-align', $config['text-align'] );

	if ( $text_align ) {
		$css['global'][':root']['--page-header-text-align'] = mai_get_align_text( esc_attr( $text_align ) );
	}

	return $css;
}
