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

add_action( 'admin_notices', 'mai_maybe_display_admin_notice', 20 );
/**
 * Displays notices from a query args.
 *
 * @since 2.6.0
 *
 * @return void
 */
function mai_maybe_display_admin_notice() {
	$notice = mai_sanitize_get( 'mai_notice' );

	if ( ! $notice ) {
		return;
	}

	$type = mai_sanitize_get( 'mai_type' );
	$type = $type ?: 'success';

	printf( '<div class="notice notice-%s">%s</div>', sanitize_html_class( (string) $type ), wpautop( $notice ) );
}

add_action( 'admin_notices', 'mai_lifetime_notice' );
/**
 * Displays the Mai Lifetime Bundle notice.
 *
 * @since 2.36.1
 * @since 2.40.0 Repurposed from the Mai AI Pack promo to the Lifetime Bundle promo.
 *
 * @return void
 */
function mai_lifetime_notice() {
	// Bail if user is not an editor.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// delete_user_meta( get_current_user_id(), 'mai_lifetime_notice_dismissed' );

	// Get user ID and dismissed status.
	$user_id   = get_current_user_id();
	$dismissed = (int) get_user_meta( $user_id, 'mai_lifetime_notice_dismissed', true );

	// If dismissed is 1 (int), it's permanent dismissal.
	if ( 1 === $dismissed ) {
		return;
	}

	// If dismissed temporarily, check if 24 hours have passed
	if ( $dismissed && ( time() - $dismissed ) < DAY_IN_SECONDS ) {
		return;
	}
	?>
	<style>
		.mai-lifetime-notice {
			position: relative;
			padding: 1rem;
			border-left-color: #bcda83;

			.mai-dismissers {
				position: absolute;
				top: 0;
				right: 0;
				display: flex;
				gap: .5rem;
				padding: 8px;
				background: none;
				color: #787c82;
				border: none;
			}

			.mai-dismiss {
				color: #787c82;
				text-decoration: none;
			}

			.mai-dismiss:hover,
			.mai-dismiss:focus-visible {
				color: #bcda83;
				text-decoration: underline;
			}

			.button {
				color: #181f09;
				background: #bcda83;
				border-color: #bcda83;
			}

			.button:hover,
			.button:focus {
				/* Darken the brand green on hover instead of falling back to WP's blue. */
				color: #181f09;
				background: #a0b96f;
				border-color: #a0b96f;
			}
		}
	</style>
	<div class="notice mai-lifetime-notice">
		<div style="display:flex;align-items:center;gap:.5rem;flex-wrap:nowrap;">
			<img width="24" height="24" src="<?php echo mai_get_url() . 'assets/svg/mai-logo-icon.svg'; ?>" alt="Mai" style="flex-shrink:0">
			<h2>Mai is now faster than ever.</h2>
		</div>
		<p><strong>Mai Engine 2.40 brings major performance upgrades that make your site load faster and scale further.</strong></p>
		<ul>
			<li>✅&nbsp;&nbsp;<strong>Faster pages:</strong> Mai Post Grid caches its query results and serves them instantly, even on busy, frequently-edited sites.</li>
			<li>✅&nbsp;&nbsp;<strong>Lighter server load:</strong> Mai's global CSS and internal caches rebuild only when something changes, not on every request.</li>
			<li>✅&nbsp;&nbsp;<strong>Built to scale:</strong> Stampede protection keeps things fast under heavy traffic.</li>
		</ul>
		<p>Lock in every Mai plugin for life with a Lifetime License, and never pay for an upgrade again.</p>
		<p><a target="_blank" rel="noopener noreferrer" href="https://bizbudding.com/products/mai-theme-lifetime-bundle/?utm_source=engine&utm_medium=lifetime-bundle&utm_campaign=lifetime-bundle" class="button button-primary">Get your Lifetime License</a></p>
		<div class="mai-dismissers">
			<a href="#" class="mai-dismiss mai-dismiss__forever">
				<?php _e( 'Don\'t show again', 'mai-engine' ); ?>
			</a>
			<span>·</span>
			<a href="#" class="mai-dismiss mai-dismiss__later">
				<?php _e( 'Remind me later', 'mai-engine' ); ?>
			</a>
		</div>
	</div>
	<script>
		( function( $ ) {
			$('.mai-lifetime-notice').on('click', '.mai-dismiss', function(e) {
				e.preventDefault();
				const $notice = $(this).closest('.notice');
				$.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					type: 'POST',
					action: 'mai_dismiss_lifetime_notice',
					nonce: '<?php echo wp_create_nonce( 'mai_dismiss_lifetime_notice' ); ?>',
					user_id: '<?php echo get_current_user_id(); ?>',
					forever: $(this).hasClass('mai-dismiss__forever'),
				}).done(function(response) {
					$notice.fadeOut();
				}).fail(function(response) {
					console.log('Error response:', response);
				});
			});
		} )( jQuery );
	</script>
	<?php
}

add_action( 'wp_ajax_mai_dismiss_lifetime_notice', 'mai_dismiss_lifetime_notice' );
/**
 * Handles the AJAX request to dismiss the Mai Lifetime Bundle notice.
 *
 * @since 2.36.1
 * @since 2.40.0 Renamed from mai_dismiss_ai_pack_notice.
 *
 * @return void
 */
function mai_dismiss_lifetime_notice() {
	check_ajax_referer( 'mai_dismiss_lifetime_notice', 'nonce' );

	// Get user ID.
	$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : get_current_user_id();

	// Get forever status.
	$forever = isset( $_POST['forever'] ) ? rest_sanitize_boolean( $_POST['forever'] ) : false;

	// Bail if user is not an editor.
	if ( ! user_can( $user_id, 'edit_posts' ) ) {
		wp_send_json_error( 'You do not have permission to dismiss this notice.' );
	}

	// Update user meta.
	update_user_meta( $user_id, 'mai_lifetime_notice_dismissed', $forever ? true : time() );

	// Send success response.
	wp_send_json_success( [
		'dismissed' => $forever ? true : time(),
	] );

	// Kill execution.
	wp_die();
}
