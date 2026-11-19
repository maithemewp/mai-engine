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
 * The vendored WXR importer, with a post-processing pass that keeps block attribute escapes.
 *
 * @since 2.42.0
 */
class Mai_Setup_Wizard_Wxr_Importer extends ProteusThemes\WPContentImporter2\Importer {

	/**
	 * Remaps image URLs in imported content before the parent's post-processing runs.
	 *
	 * The parent rewrites the URLs in the stored post_content and passes it to wp_update_post()
	 * unslashed. wp_insert_post() unslashes its input, so every backslash in the content is lost.
	 * Block attributes save `<`, `>`, `&`, `--` and `"` as `\u` escapes, so a value like `<p>`
	 * comes back as the literal text `u003cpu003e`. Doing the URL swap here with a slashed update keeps
	 * the escapes. Deleting the temp meta afterwards makes the parent skip its own unslashed rewrite,
	 * while it still remaps parents, authors and menu items.
	 *
	 * @since 2.42.0
	 *
	 * @return void
	 */
	protected function post_process() {
		if ( ! empty( $this->requires_remapping['post'] ) ) {
			foreach ( array_keys( $this->requires_remapping['post'] ) as $post_id ) {
				if ( empty( get_post_meta( $post_id, '_wxr_import_has_attachment_refs', true ) ) ) {
					continue;
				}

				$post = get_post( $post_id );

				if ( ! $post ) {
					continue;
				}

				$new_content = str_replace( array_keys( $this->url_remap ), $this->url_remap, $post->post_content );

				if ( $new_content !== $post->post_content ) {
					$result = wp_update_post(
						wp_slash(
							[
								'ID'           => $post_id,
								'post_content' => $new_content,
							]
						),
						true
					);

					if ( is_wp_error( $result ) ) {
						$this->logger->warning(
							sprintf(
								/* translators: 1: post title, 2: post ID. */
								__( 'Could not update "%1$s" (post #%2$d) with mapped data', 'mai-engine' ),
								get_the_title( $post_id ),
								$post_id
							)
						);
						$this->logger->debug( $result->get_error_message() );
						continue;
					}
				}

				delete_post_meta( $post_id, '_wxr_import_has_attachment_refs' );
			}
		}

		parent::post_process();
	}
}
