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
 * Class Mai_Cli_Generate_Command
 */
class Mai_Cli_Generate_Command {

	/**
	 * Default function to run.
	 *
	 * Remember to use URL parameter when on multisite. E.g:
	 *
	 * `wp mai generate --url=demo.bizbudding.com/example`
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Passed arguments.
	 *
	 * @return void
	 */
	public function __invoke( $args ) {
		self::create_home();
		self::set_front_page();
	}

	/**
	 * Returns page data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $theme Theme name.
	 * @param string $demo  Demo name.
	 *
	 * @return array
	 */
	private static function get_pages( $theme, $demo ) {
		return [
			'home'     => [
				'post_title'   => 'Home - ' . $theme . $demo,
				'post_content' => '<!-- wp:group {"align":"full","verticalSpacingTop":"lg","verticalSpacingBottom":"lg"} -->
<div class="wp-block-group alignfull"><div class="wp-block-group__inner-container"><!-- wp:heading {"align":"center","level":1} -->
<h1 class="has-text-align-center">Welcome to ' . $theme . $demo . '</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"align":"center"} -->
<div class="wp-block-buttons aligncenter"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link">Purchase This Theme</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:group -->',
				'post_type'    => 'page',
				'post_status'  => 'publish',
			],
			'blog'     => [
				'post_title'   => 'Blog',
				'post_content' => '',
				'post_type'    => 'page',
				'post_status'  => 'publish',
			],
			'about'    => [
				'post_title'   => 'About',
				'post_content' => '
<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. </p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. </p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus.</p>
<!-- /wp:paragraph -->',
				'post_type'    => 'page',
				'post_status'  => 'publish',
			],
			'features' => [
				'post_title'   => 'Features',
				'post_content' => '<!-- wp:paragraph -->
<p>Here\'s some cool things about this theme:</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul><li>Feature one</li><li>Feature two</li><li>Feature three</li><li>Feature four</li><li>Feature five</li></ul>
<!-- /wp:list -->',
				'post_type'    => 'page',
				'post_status'  => 'publish',
			],
			'contact'  => [
				'post_title'   => 'Contact',
				'post_content' => '<!-- wp:paragraph -->
<p>Please fill out the form below and I will reply shortly.</p>
<!-- /wp:paragraph -->
<!-- wp:html -->
<form>
<label>Your Name</label>
<input>
<br>
<label>Email Address</label>
<input>
<br>
<label>Your Message</label>
<textarea rows="6"></textarea>
<br>
</form>
<!-- /wp:html -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link">Submit Form</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
				'post_type'    => 'page',
				'post_status'  => 'publish',
			],
		];
	}

	/**
	 * Creates pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function create_home() {
		$base  = explode( '-', basename( home_url() ) );
		$theme = ucwords( $base[0] );
		$demo  = 2 === count( $base ) ? ' ' . ucwords( end( $base ) ) : '';
		$pages = self::get_pages( $theme, $demo );

		foreach ( $pages as $slug => $page ) {
			if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
				WP_CLI::log( ucwords( $slug ) . __( ' page already exists and has been skipped.', 'mai-engine' ) );

				continue;
			}

			$page['post_name'] = $slug;

			wp_insert_post( $page );

			WP_CLI::log( sprintf(
				'%s %s %s',
				__( 'Created', 'mai-engine' ),
				$slug,
				__( 'page.', 'mai-engine' )
			) );
		}
	}

	/**
	 * Updates front page settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private static function set_front_page() {
		$home = get_page_by_path( 'home', OBJECT, 'page' );
		$blog = get_page_by_path( 'blog', OBJECT, 'page' );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
		update_option( 'page_for_posts', $blog->ID );

		WP_CLI::log( __( 'Updated options.', 'mai-engine' ) );
	}
}
