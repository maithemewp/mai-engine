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
 * Class Mai_Setup_Wizard_Steps
 */
class Mai_Setup_Wizard_Steps extends Mai_Setup_Wizard_Service_Provider {

	/**
	 * @var array
	 */
	private $all_steps = [];

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', [ $this, 'add_steps' ], 13 );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function add_steps() {
		$steps = $this->get_default_steps();

		foreach ( $steps as $step ) {
			$this->add_step( $step );
		}

		usort( $this->all_steps, function ( $a, $b ) {
			return strcmp( $a['order'], $b['order'] );
		} );

		return $this->all_steps;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $args
	 *
	 * @return void
	 */
	private function add_step( $args ) {
		if ( isset( $args['id'] ) ) {
			$args['fields'] = $this->fields->get_fields( $args['id'] );
			$args           = wp_parse_args( $args, $this->get_default_args( $args['id'] ) );
			$this->all_steps[]  = $args;
		}
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_steps() {
		return $this->all_steps;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_default_steps() {
		return apply_filters( 'mai_setup_wizard_steps', [
			'welcome' => $this->get_welcome_step(),
			'demo'    => $this->get_demo_step(),
			'plugins' => $this->get_plugins_step(),
			'content' => $this->get_content_step(),
			'done'    => $this->get_done_step(),
		] );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_welcome_step() {
		return [
			'id'              => 'welcome',
			'title'           => __( 'Welcome', 'mai-engine' ),
			'description'     => __( 'Welcome to the Mai Setup Wizard! Enter your email address in the form below to receive automatic updates, latest news and special offers.', 'mai-engine' ),
			'order'           => 10,
			'error_message'   => __( 'Please enter a valid email address.', 'mai-engine' ),
			'success_message' => __( 'Success!', 'mai-engine' ),
			'continue_text'   => __( 'Yes please, sign me up!', 'mai-engine' ),
			'loading_text'    => __( 'Sending...', 'mai-engine' ),
			'skip_text'       => __( 'No thanks', 'mai-engine' ),
			'fields'          => $this->fields->get_fields( 'welcome' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_demo_step() {
		$step  = [];
		$demos = $this->demos->get_demos();

		if ( is_array( $demos ) && count( $demos ) > 1 ) {
			$step = [
				'id'              => 'demo',
				'title'           => __( 'Site Style', 'mai-engine' ),
				'description'     => __( 'Please select your site style below (required).', 'mai-engine' ),
				'order'           => 20,
				'error_message'   => __( 'Please select a site style to continue.', 'mai-engine' ),
				'success_message' => __( 'Good choice!', 'mai-engine' ),
				'continue_text'   => __( 'Continue', 'mai-engine' ),
				'skip_text'       => false,
				'fields'          => $this->fields->get_fields( 'demo' ),
			];
		}

		return $step;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_plugins_step() {
		$step = [];
		$demo = $this->demos->get_demo( $this->demos->get_chosen_demo() );

		if ( isset( $demo['plugins'] ) && ! empty( $demo['plugins'] ) ) {
			$step = [
				'id'              => 'plugins',
				'title'           => __( 'Recommended Plugins', 'mai-engine' ),
				'description'     => __( 'The following plugins will be installed and activated.', 'mai-engine' ),
				'order'           => 30,
				'error_message'   => __( 'Plugins could not be installed.', 'mai-engine' ),
				'success_message' => __( 'Plugins successfully installed!', 'mai-engine' ),
				'continue_text'   => __( 'Install Plugins', 'mai-engine' ),
				'loading_text'    => __( 'Installing plugins...', 'mai-engine' ),
				'fields'          => $this->fields->get_fields( 'plugins' ),
			];
		}

		return $step;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_content_step() {
		$step = [];
		$demo = $this->demos->get_demo( $this->demos->get_chosen_demo() );

		if ( isset( $demo['content'] ) && ! empty( $demo['content'] ) ) {
			$step = [
				'id'              => 'content',
				'title'           => __( 'Content', 'mai-engine' ),
				'description'     => __( 'Select which content you would like to import. Please note that this step can take up to 5 minutes.', 'mai-engine' ),
				'order'           => 40,
				'error_message'   => __( 'Content could not be installed.', 'mai-engine' ),
				'success_message' => __( 'Content successfully installed!', 'mai-engine' ),
				'continue_text'   => __( 'Import Content', 'mai-engine' ),
				'loading_text'    => __( 'Importing content...', 'mai-engine' ),
				'fields'          => $this->fields->get_fields( 'content' ),
			];
		}

		return $step;
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_done_step() {
		return [
			'id'            => 'done',
			'title'         => __( 'Done', 'mai-engine' ),
			'description'   => __( 'Your theme has been all set up.', 'mai-engine' ),
			'order'         => 50,
			'continue_text' => __( 'View Your Site', 'mai-engine' ),
			'continue_url'  => get_home_url(),
			'skip_text'     => __( 'Edit Your Site', 'mai-engine' ),
			'skip_url'      => get_admin_url(),
			'fields'        => $this->fields->get_fields( 'done' ),
		];
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $id
	 *
	 * @return array
	 */
	private function get_default_args( $id ) {
		$title = ucwords( str_replace( [ '-', '_' ], ' ', $id ) );

		return apply_filters( 'mai_setup_wizard_step_defaults', [
			'title'           => $title,
			'description'     => $title,
			'order'           => 10,
			'error_message'   => false,
			'success_message' => false,
			'continue_text'   => __( 'Continue', 'mai-engine' ),
			'continue_url'    => 'javascript:void(0)',
			'loading_text'    => __( 'Loading...', 'mai-engine' ),
			'skip_text'       => __( 'Skip this step', 'mai-engine' ),
			'skip_url'        => 'javascript:void(0)',
			'ajax_callback'   => "mai_setup_wizard_ajax_step_{$id}",
		] );
	}

	/**
	 * Description of expected behavior.
	 *
	 * @since 1.0.0
	 *
	 * @param $step
	 * @param $steps
	 * @param $counter
	 *
	 * @return void
	 */
	public function render( $step, $steps, $counter ) {
		?>
		<form id="<?php echo $step['id']; ?>" class="step" action="javascript:void(0);">
			<h2><?php echo $step['title']; ?></h2>
			<p><?php echo $step['description']; ?></p>
			<p class="error"><?php echo $step['error_message']; ?></p>
			<p class="success"><?php echo $step['success_message']; ?></p>
			<?php if ( $step['fields'] ): ?>
				<?php
				$items = sprintf(
					' class="items-%s"',
					count( $step['fields'] )
				);
				?>
				<ul<?php echo $items; ?>>
					<?php
					/**
					 * @var Mai_Setup_Wizard_Fields $field
					 */
					foreach ( $step['fields'] as $field ) : ?>
						<li><?php echo $this->fields->render( $field ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div>
				<?php if ( 1 !== $counter && apply_filters( 'mai_setup_wizard_previous', true ) ): ?>
					<a href="javascript:void(0)" id="previous" class="button">
						<?php esc_html_e( 'Previous', 'mai-engine' ); ?>
					</a>
				<?php endif; ?>
				<?php $skip_target = filter_var( $step['skip_url'], FILTER_VALIDATE_URL ) === false ? '' : ' target="_blank"'; ?>
				<?php if ( $step['skip_text'] && $counter < count( $steps ) ): ?>
					<a href="<?php echo $step['skip_url']; ?>" <?php echo $skip_target; ?>id="skip" class="button">
						<?php echo $step['skip_text']; ?>
					</a>
				<?php endif; ?>
				<?php if ( $counter < count( $steps ) ): ?>
					<?php $continue_target = filter_var( $step['skip_url'], FILTER_VALIDATE_URL ) === false ? '' : ' target="_blank"'; ?>
					<button href="<?php echo $step['continue_url']; ?>" <?php echo $continue_target; ?>id="submit" class="button-primary" data-default="<?php echo $step['continue_text']; ?>" data-loading="<?php esc_attr_e( $step['loading_text'] ); ?>">
						<?php echo $step['continue_text']; ?>
						<img src="<?php echo mai_get_url() . 'assets/img/spinner.gif'; ?>" alt="spinner" width="20">
					</button>
				<?php endif; ?>
			</div>
			<br>
			<small>
				<?php
				printf(
					'%s %s %s %s',
					__( 'Step', 'mai-engine' ),
					$counter,
					__( 'of', 'mai-engine' ),
					count( $steps )
				);
				?>
			</small>
		</form>
		<?php
	}
}
