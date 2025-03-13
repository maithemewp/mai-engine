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

add_action( 'admin_notices', 'mai_ai_pack_notice' );
/**
 * Displays the AI Pack notice.
 *
 * @since TBD
 *
 * @return void
 */
function mai_ai_pack_notice() {
	// Bail if Mai AI Pack is active.
	if ( class_exists( 'Mai_AI_Pack' ) ) {
		return;
	}

	// Bail if user is not an editor.
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// delete_user_meta( get_current_user_id(), 'mai_ai_pack_notice_dismissed' );

	// Get user ID and dismissed status.
	$user_id   = get_current_user_id();
	$dismissed = (int) get_user_meta( $user_id, 'mai_ai_pack_notice_dismissed', true );

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
		.mai-ai-pack-notice {
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
		}
	</style>
	<div class="notice mai-ai-pack-notice">
		<div style="display:flex;align-items:center;gap:.5rem;">
			<img width="24" height="24" src="<?php echo mai_get_url() . 'assets/svg/mai-logo-icon.svg'; ?>" alt="Mai AI Pack">
			<h2>Enable Intuitive Site Search and Intelligent Suggestions for Your Visitors.</h2>
		</div>
		<p><strong>Turn your website into an AI-Powered Search Engine with the Mai AI Pack.</strong></p>
		<ul>
			<li>✅&nbsp;&nbsp;<strong>AI Search:</strong> Visitors ask natural questions and get instant answers — all from your content.</li>
			<li>✅&nbsp;&nbsp;<strong>Smart Recommendations:</strong> Automatically show related posts that match what visitors want next.</li>
			<li>✅&nbsp;&nbsp;<strong>Boost Engagement:</strong> Keep visitors exploring longer with trending, popular, and ultra-relevant content.</li>
		</ul>
		<p>Upgrade your site with the Mai AI Pack today!</p>
		<p><a target="_blank" rel="noopener noreferrer" href="https://bizbudding.com/mai-ai-pack/?utm_source=engine&utm_medium=mai-ai-pack&utm_campaign=mai-ai-pack" class="button button-primary">Learn More about the Mai AI Pack</a></p>
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
			$('.mai-ai-pack-notice').on('click', '.mai-dismiss', function(e) {
				e.preventDefault();
				const $notice = $(this).closest('.notice');
				$.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', {
					type: 'POST',
					action: 'mai_dismiss_ai_pack_notice',
					nonce: '<?php echo wp_create_nonce( 'mai_dismiss_ai_pack_notice' ); ?>',
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

add_action( 'wp_ajax_mai_dismiss_ai_pack_notice', 'mai_dismiss_ai_pack_notice' );
/**
 * Handles the AJAX request to dismiss the AI Pack notice.
 *
 * @since TBD
 *
 * @return void
 */
function mai_dismiss_ai_pack_notice() {
	check_ajax_referer( 'mai_dismiss_ai_pack_notice', 'nonce' );

	// Get user ID.
	$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : get_current_user_id();

	// Get forever status.
	$forever = isset( $_POST['forever'] ) ? rest_sanitize_boolean( $_POST['forever'] ) : false;

	// Bail if user is not an editor.
	if ( ! user_can( $user_id, 'edit_posts' ) ) {
		wp_send_json_error( 'You do not have permission to dismiss this notice.' );
	}

	// Update user meta.
	update_user_meta( $user_id, 'mai_ai_pack_notice_dismissed', $forever ? true : time() );

	// Send success response.
	wp_send_json_success( [
		'dismissed' => $forever ? true : time(),
	] );

	// Kill execution.
	wp_die();
}
