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
 * Return content stripped down and limited content.
 *
 * Strips out tags and shortcodes, limits the output to `$max_char` characters.
 *
 * @since 0.1.0
 *
 * @param string $content  The content to limit.
 * @param int    $limit    The maximum number of characters to return.
 * @param bool   $ellipsis Wether to add an ellipsis or not.
 *
 * @return string
 */
function mai_get_content_limit( $content, $limit, $ellipsis = true ) {

	// Strip tags and shortcodes so the content truncation count is done correctly.
	$content = strip_tags( strip_shortcodes( $content ), apply_filters( 'get_the_content_limit_allowedtags', '<script>,<style>' ) );

	// Remove inline styles / scripts.
	$content = trim( preg_replace( '#<(s(cript|tyle)).*?</\1>#si', '', $content ) );

	// Truncate $content to $limit.
	$content = mai_get_truncated_text( $content, $limit, $ellipsis );

	return $content;
}

/**
 * Return a phrase shortened in length to a maximum number of characters.
 *
 * Result will be truncated at the last white space in the original string. In this function the word separator is a
 * single space. Other white space characters (like newlines and tabs) are ignored.
 *
 * If the first `$max_characters` of the string does not contain a space character, an empty string will be returned.
 *
 * Originally taken `from genesis_truncate_phrase()`.
 *
 * @since 2.4.3
 *
 * @param string $text           A string to be shortened.
 * @param int    $max_characters The maximum number of characters to return.
 * @param bool   $ellipsis       Add an ellipsis to truncated text.
 *
 * @return string Truncated string. Empty string if `$max_characters` is falsy.
 */
function mai_get_truncated_text( $text, $max_characters, $ellipsis = true ) {

	if ( ! $max_characters ) {
		return '';
	}

	$text = trim( $text );

	if ( mb_strlen( $text ) > $max_characters ) {

		// Truncate $text to $max_characters + 1.
		$text = mb_substr( $text, 0, $max_characters + 1 );

		// Truncate to the last space in the truncated string.
		$text_trim = trim( mb_substr( $text, 0, mb_strrpos( $text, ' ' ) ) );

		$text  = empty( $text_trim ) ? $text : $text_trim;
		$text .= $ellipsis ? mai_get_ellipsis() : '';
	}

	return $text;
}

/**
 * Check if a loop supports our loop settings.
 *
 * @since  0.1.0
 *
 * @return bool
 */
function mai_has_custom_loop() {
	if ( mai_is_type_archive() ) {
		$name     = mai_get_archive_args_name();
		$defaults = mai_get_config( 'settings' )['content-archives']['enable'];
		$types    = mai_get_option( 'archive-settings', $defaults );

	} elseif ( mai_is_type_single() ) {
		$name     = mai_get_singular_args_name();
		$defaults = mai_get_config( 'settings' )['single-content']['enable'];
		$types    = mai_get_option( 'single-settings', $defaults );
	}

	if ( isset( $name, $types ) ) {

		if ( in_array( $name, $types, true ) ) {
			return true;
		}

		// All core WP post types and taxonomies use our custom loop.
		$post_types = get_post_types(
			[
				'_builtin' => true,
				'public'   => true,
			]
		);
		$taxonomies = get_taxonomies(
			[
				'_builtin' => true,
				'public'   => true,
			]
		);

		if ( isset( $post_types[ $name ] ) || isset( $taxonomies[ $name ] ) ) {
			return true;
		}

		// Check taxonomy post type.
		if ( taxonomy_exists( $name ) ) {
			$post_type = mai_get_taxonomy_post_type( $name );

			if ( $post_type && in_array( $post_type, $types, true ) ) {
				return true;
			}
		}

		$other_types = [ 'author', 'date', 'search' ];

		if ( in_array( $name, $other_types, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Get the settings args for a content type.
 *
 * @since  0.1.0
 *
 * @return array
 */
function mai_get_template_args() {
	static $args = null;

	if ( ! is_null( $args ) ) {
		return $args;
	}

	$name     = '';
	$context  = '';
	$settings = '';
	$default  = '';

	if ( mai_is_type_archive() ) {
		$name     = mai_get_archive_args_name();
		$context  = 'archive';
		$settings = 'content-archives';
	} elseif ( mai_is_type_single() ) {
		$name     = mai_get_singular_args_name();
		$context  = 'single';
		$settings = 'single-content';
	}

	if ( $settings ) {
		$default = mai_get_config( 'settings' )[ $settings ]['enable'];
	}

	// Get taxonomy's post type as fallback.
	if ( taxonomy_exists( $name ) && ! in_array( $name, mai_get_option( $context . '-settings', $default ), true ) ) {
		$post_type = mai_get_taxonomy_post_type( $name );

		if ( $post_type ) {
			$name = $post_type;
		}
	}

	// Get fallback for archives. This happens on category/tag/etc archives when they don't have custom loop settings.
	if ( mai_is_type_archive() && ! in_array( $name, mai_get_option( 'archive-settings', mai_get_config( 'settings' )['content-archives']['enable'] ), true ) ) {
		$name = 'post';
	}

	// Bail if no data.
	if ( ! ( $name && $context ) ) {
		return [];
	}

	// Get defaults.
	$config = [];

	if ( 'archive' === $context ) {
		$config = mai_get_content_archive_settings( $name );

	} elseif ( 'single' === $context ) {
		$config = mai_get_single_content_settings( $name );
	}

	$defaults = [ 'context' => $context ] + wp_list_pluck( $config, 'default', 'settings' );

	foreach ( $defaults as $key => $value ) {
		if ( is_string( $value ) && mai_has_string( 'mai_', $value ) && is_callable( $value ) ) {
			$defaults[ $key ] = call_user_func_array( $value, [ 'name' => $name ] );
		}
	}

	// Get args.
	$options   = mai_get_option( $settings, [] );
	$args      = isset( $options[ $name ] ) ? $options[ $name ] : [];
	$skip_empy = [ 'header_meta', 'footer_meta', 'column_gap', 'row_gap' ];

	// Remove settings with empty string, since that means use the default.
	foreach ( $args as $key => $value ) {

		// Skip some keys, empty means empty.
		if ( in_array( $key, $skip_empy, true ) ) {
			continue;
		}

		if ( is_null( $value ) || '' === $value ) {
			unset( $args[ $key ] );
		}
	}

	// Parse args.
	$args = wp_parse_args( $args, $defaults );

	// Remove settings with empty string, again.
	foreach ( $args as $key => $value ) {

		// Skip header and footer meta, empty means empty.
		if ( in_array( $key, $skip_empy, true ) ) {
			continue;
		}

		if ( is_null( $value ) || '' === $value ) {
			unset( $args[ $key ] );
		}
	}

	// Allow devs to filter.
	$args = apply_filters( 'mai_template_args', $args, $context, $name );

	// Sanitize.
	return mai_get_sanitized_entry_args( $args, $settings, $name );
}

/**
 * Returns a single template arg.
 *
 * @since 2.0.0
 *
 * @param string $name    Name of arg to get.
 * @param mixed  $default Default value.
 *
 * @return mixed
 */
function mai_get_template_arg( $name, $default = null ) {
	$args = mai_get_template_args();

	return isset( $args[ $name ] ) ? $args[ $name ] : $default;
}

/**
 * Description of expected behavior.
 *
 * @since 0.1.0
 * @since 2.4.2 Added $name param.
 *
 * @param array  $args    Entry args.
 * @param string $context The args context.
 * @param string $name    The content type name.
 *
 * @return mixed
 */
function mai_get_sanitized_entry_args( $args, $context, $name = 'post' ) {
	$settings = [];

	if ( 'archive' === $context ) {
		$settings = mai_get_content_archive_settings( $name );
	} elseif ( 'single' === $context ) {
		$settings = mai_get_single_content_settings( $name );
	}

	// Bail if no settings.
	if ( ! $settings ) {
		return $args;
	}

	// Make sure sanitize key is set.
	if ( ! isset( $settings['sanitize'] ) ) {
		return $args;
	}

	// Get sanitize array.
	$sanitize = wp_list_pluck( $settings, 'sanitize', 'name' );

	// Sanitize.
	foreach ( $args as $key => $value ) {
		// Skip if not set.
		if ( ! isset( $sanitize[ $key ] ) ) {
			continue;
		}
		$function = $sanitize[ $key ];
		if ( is_array( $value ) ) {
			$escaped = [];
			foreach ( $value as $key => $val ) {
				$escaped[ $key ] = $function( $val );
			}
			$args[ $key ] = $escaped;
		} else {
			$args[ $key ] = $function( $value );
		}
	}

	return $args;
}

/**
 * Gets the archive content type name to be used in the main args function/helpers.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_get_archive_args_name() {
	static $name = null;

	if ( is_null( $name ) ) {
		if ( is_home() ) {
			$name = 'post';

		} elseif ( is_category() ) {
			$name = 'category';

		} elseif ( is_tag() ) {
			$name = 'post_tag';

		} elseif ( is_tax() ) {
			$name = get_query_var( 'taxonomy' );

			if ( ! $name ) {
				$object = get_queried_object();

				if ( $object ) {
					$name = $object->taxonomy;
				}
			}
		} elseif ( is_post_type_archive() ) {
			$name = get_query_var( 'post_type' );

			if ( ! $name ) {
				$object = get_queried_object();

				if ( $object ) {
					$name = $object->name;
				}
			}
		} elseif ( is_search() ) {
			$name = 'search';

		} elseif ( is_author() ) {
			$name = 'author';

		} elseif ( is_date() ) {
			$name = 'date';

		} else {
			$name = 'post';
		}
	}

	return apply_filters( 'mai_archive_args_name', $name );
}

/**
 * Gets the singular content type name to be used in the main args function/helpers.
 *
 * @since 0.1.0
 *
 * @return false|mixed|string
 */
function mai_get_singular_args_name() {
	$name = is_404() ? '404-page' : mai_get_post_type();

	return apply_filters( 'mai_single_args_name', $name );
}

/**
 * Gets the header meta default content.
 *
 * @since 0.1.0
 *
 * @param string $name Post type name.
 *
 * @return string
 */
function mai_get_header_meta_default( $name = 'post' ) {
	$post_type = get_post_type_object( $name );

	if ( $post_type && $post_type->hierarchical ) {
		return '';
	}

	return '[post_date] [post_author_posts_link before="by "]';
}

/**
 * Gets the footer meta default content.
 *
 * @since 0.1.0
 *
 * @param string $name Taxonomy name.
 *
 * @return string
 */
function mai_get_footer_meta_default( $name ) {
	$taxonomies = get_object_taxonomies( $name, 'objects' );

	if ( $taxonomies ) {
		// Get only public taxonomies.
		$taxonomies = wp_list_filter( $taxonomies, [ 'public' => true ] );
		// Remove taxonomies we don't want.
		unset( $taxonomies['post_format'] );
		unset( $taxonomies['product_shipping_class'] );
		unset( $taxonomies['yst_prominent_words'] );
	}

	// Bail if none.
	if ( ! $taxonomies ) {
		return '';
	}

	$default = '';

	foreach ( $taxonomies as $tax_name => $taxonomy ) {
		$default .= '[post_terms taxonomy="' . $tax_name . '" before="' . $taxonomy->labels->singular_name . ': "]';
	}

	return $default;
}

/**
 * Gets the "Show" choices for archive content.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_archive_show_choices() {
	static $choices = null;

	if ( is_null( $choices ) ) {
		$choices = [
			'image'                        => esc_html__( 'Image', 'mai-engine' ),
			'genesis_entry_header'         => 'genesis_entry_header',
			'title'                        => esc_html__( 'Title', 'mai-engine' ),
			'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
			'genesis_before_entry_content' => 'genesis_before_entry_content',
			'excerpt'                      => esc_html__( 'Excerpt', 'mai-engine' ),
			'content'                      => esc_html__( 'Content', 'mai-engine' ),
			'custom_content'               => esc_html__( 'Custom Content', 'mai-engine' ),
			'genesis_entry_content'        => 'genesis_entry_content',
			'more_link'                    => esc_html__( 'Read More link', 'mai-engine' ),
			'genesis_after_entry_content'  => 'genesis_after_entry_content',
			'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
			'genesis_entry_footer'         => 'genesis_entry_footer',
		];
	}

	return $choices;
}

/**
 * Gets the "Show" choices for single content.
 *
 * @since 0.1.0
 *
 * @return array
 */
function mai_get_single_show_choices() {
	static $choices = null;

	if ( is_null( $choices ) ) {
		$choices = [
			'image'                        => esc_html__( 'Image', 'mai-engine' ),
			'genesis_entry_header'         => 'genesis_entry_header',
			'title'                        => esc_html__( 'Title', 'mai-engine' ),
			'header_meta'                  => esc_html__( 'Header Meta', 'mai-engine' ),
			'genesis_before_entry_content' => 'genesis_before_entry_content',
			'excerpt'                      => esc_html__( 'Manual Excerpts', 'mai-engine' ),
			'content'                      => esc_html__( 'Content', 'mai-engine' ),
			'custom_content'               => esc_html__( 'Custom Content', 'mai-engine' ),
			'genesis_entry_content'        => 'genesis_entry_content',
			'genesis_after_entry_content'  => 'genesis_after_entry_content',
			'footer_meta'                  => esc_html__( 'Footer Meta', 'mai-engine' ),
			'genesis_entry_footer'         => 'genesis_entry_footer',
			'author_box'                   => esc_html__( 'Author Box', 'mai-engine' ),
			'after_entry'                  => esc_html__( 'After Entry Template Part', 'mai-engine' ),
			'adjacent_entry_nav'           => esc_html__( 'Previous/Next Entry Nav', 'mai-engine' ),
		];
	}

	return $choices;
}
