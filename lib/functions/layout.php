<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2019 BizBudding
 * @license   GPL-2.0-or-later
 */

// Remove layout filter from Genesis Connect for WooCommerce.
remove_filter( 'genesis_pre_get_option_site_layout', 'genesiswooc_archive_layout' );

add_filter( 'genesis_site_layout', 'mai_site_layout' );
/**
 * Use Mai Engine layout.
 *
 * @since 0.1.0
 *
 * @return string
 */
function mai_site_layout() {
	static $site_layout = null;

	if ( ! is_null( $site_layout ) ) {
		return esc_attr( $site_layout );
	}

	if ( is_singular() || ( is_home() && ! genesis_is_root_page() ) ) {

		$post_id     = is_home() ? get_option( 'page_for_posts' ) : null;
		$site_layout = genesis_get_custom_field( '_genesis_layout', $post_id );

	} elseif ( is_category() || is_tag() || is_tax() ) {

		/**
		 * WP Query object.
		 *
		 * @var WP_Query $wp_query
		 */
		global $wp_query;
		$term        = $wp_query->get_queried_object();
		$site_layout = $term ? get_term_meta( $term->term_id, 'layout', true ) : '';

	} elseif ( is_post_type_archive() && genesis_has_post_type_archive_support() ) {

		$site_layout = genesis_get_cpt_option( 'layout' );

	} elseif ( is_author() ) {

		$site_layout = get_the_author_meta( 'layout', (int) get_query_var( 'author' ) );

	}

	// Maybe use layout via mai customizer settings.
	if ( ! $site_layout ) {
		$args = mai_get_template_args();
		if ( $args && isset( $args['site_layout'] ) && ! empty( $args['site_layout'] ) ) {
			$site_layout = $args['site_layout'];
		}
	}

	// Use Theme Settings layout.
	if ( ! $site_layout ) {
		$site_layout = genesis_get_option( 'site_layout' );
	}

	// Use default layout as a fallback, if necessary.
	if ( ! genesis_get_layout( $site_layout ) ) {
		$site_layout = 'standard-layout';
	}

	return $site_layout;
}
