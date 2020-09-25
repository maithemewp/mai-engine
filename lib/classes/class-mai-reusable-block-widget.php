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
 * Class Mai_Reusable_Block_Widget
 */
class Mai_Reusable_Block_Widget extends WP_Widget {

	/**
	 * Register the widget.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'mai_reusable_block_widget',
			esc_html__( 'Mai Reusable Block', 'mai-engine' ),
			[
				'classname'   => 'mai-reusable-block-widget',
				'description' => esc_html__( 'Display an existing reusable block in a widget area.', 'mai-engine' ),
			]
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		if ( isset( $instance['block'] ) && ! empty( $instance['block'] ) ) {
			$content = mai_get_post_content( $instance['block'] );
			if ( ! $content ) {
				return;
			}
			echo $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			}
			echo $content;
			echo $args['after_widget'];
		}
	}

	/**
	 * Outputs the options form on admin.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance The widget options.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title          = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$block_selected = ! empty( $instance['block'] ) ? $instance['block'] : '';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_attr__( 'Title:', 'mai-engine' ) );
		printf( '<input class="widefat" id="%s" name="%s" type="text" value="%s">', esc_attr( $this->get_field_id( 'title' ) ), esc_attr( $this->get_field_name( 'title' ) ), esc_attr( $title ) );
		echo '</p>';

		$blocks = new WP_Query(
			[
				'post_type'              => 'wp_block',
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( $blocks->have_posts() ) {
			echo '<p>';
			printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'block' ) ), esc_attr__( 'Select from saved Reusable Blocks: ', 'mai-engine' ) );
			printf( '<select class="widefat" id="%s" name="%s">', esc_attr( $this->get_field_id( 'block' ) ), esc_attr( $this->get_field_name( 'block' ) ) );
			printf( '<option values="">%s</option>', esc_html__( 'Select Reusable Block', 'mai-engine' ) );

			while ( $blocks->have_posts() ) {
				$blocks->the_post();
				$selected = ( get_the_ID() === (int) $block_selected ) ? 'selected="selected"' : '';
				printf( '<option value="%s" %s>%s</option>', get_the_ID(), $selected, get_the_title() );
			}
			echo '</select>';
			echo '</p>';

		} else {
			printf( '<p>%s</p>', esc_attr__( 'No saved reusable blocks yet.', 'mai-engine' ) );
		}

		wp_reset_postdata();

		echo '<p style="font-size: 11px; line-height: 13px;">';
		printf( '<a href="%s">%s</a>', '#', esc_html__( 'Edit the currently saved block.', 'mai-engine' ) );
		echo '</p>';
	}

	/**
	 * Processing widget options on save.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = [];
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['block'] = ( ! empty( $new_instance['block'] ) ) ? wp_strip_all_tags( $new_instance['block'] ) : '';

		return $instance;
	}
}
