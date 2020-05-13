<?php

namespace MaiSetupWizard\Providers;

use MaiSetupWizard\AbstractServiceProvider;

class Step extends AbstractServiceProvider {
	private $steps = [];

	public function add_hooks() {
		\add_action( 'init', [ $this, 'add_steps' ], 13 );
	}

	public function add_steps() {
		$steps = $this->get_default_steps();

		foreach ( $steps as $step ) {
			$this->add_step( $step );
		}

		\usort( $this->steps, function ( $a, $b ) {
			return strcmp( $a['order'], $b['order'] );
		} );

		return $this->steps;
	}

	private function add_step( $args ) {
		if ( isset( $args['id'] ) ) {
			$args['fields'] = $this->field->get_fields( $args['id'] );
			$args           = \wp_parse_args( $args, $this->get_default_args( $args['id'] ) );
			$this->steps[]  = $args;
		}
	}

	public function get_steps() {
		return $this->steps;
	}

	private function get_default_steps() {
		return \apply_filters( 'mai_setup_wizard_steps', [
			'welcome' => $this->get_welcome_step(),
			'demo'    => $this->get_demo_step(),
			'plugins' => $this->get_plugins_step(),
			'content' => $this->get_content_step(),
			'done'    => $this->get_done_step(),
		] );
	}

	private function get_welcome_step() {
		return [
			'id'              => 'welcome',
			'title'           => __( 'Welcome', 'mai-setup-wizard' ),
			'description'     => __( 'Welcome to the Mai Setup Wizard! Enter your email address in the form below to receive automatic updates, latest news and special offers.', 'mai-setup-wizard' ),
			'order'           => 10,
			'error_message'   => __( 'Please enter a valid email address.', 'mai-setup-wizard' ),
			'success_message' => __( 'Success!', 'mai-setup-wizard' ),
			'continue_text'   => __( 'Yes please, sign me up!', 'mai-setup-wizard' ),
			'loading_text'    => __( 'Sending...', 'mai-setup-wizard' ),
			'skip_text'       => __( 'No thanks', 'mai-setup-wizard' ),
			'fields'          => $this->field->get_fields( 'welcome' ),
		];
	}

	private function get_demo_step() {
		$step  = [];
		$demos = $this->demo->get_demos();

		if ( \is_array( $demos ) && \count( $demos ) > 1 ) {
			$step = [
				'id'              => 'demo',
				'title'           => __( 'Site Style', 'mai-setup-wizard' ),
				'description'     => __( 'Please select your site style below (required).', 'mai-setup-wizard' ),
				'order'           => 20,
				'error_message'   => __( 'Please select a site style to continue.', 'mai-setup-wizard' ),
				'success_message' => __( 'Good choice!', 'mai-setup-wizard' ),
				'continue_text'   => __( 'Continue', 'mai-setup-wizard' ),
				'skip_text'       => false,
				'fields'          => $this->field->get_fields( 'demo' ),
			];
		}

		return $step;
	}

	private function get_plugins_step() {
		return [
			'id'              => 'plugins',
			'title'           => __( 'Recommended Plugins', 'mai-setup-wizard' ),
			'description'     => __( 'The following plugins will be installed and activated.', 'mai-setup-wizard' ),
			'order'           => 30,
			'error_message'   => __( 'Plugins could not be installed.', 'mai-setup-wizard' ),
			'success_message' => __( 'Plugins successfully installed!', 'mai-setup-wizard' ),
			'continue_text'   => __( 'Install Plugins', 'mai-setup-wizard' ),
			'loading_text'    => __( 'Installing plugins...', 'mai-setup-wizard' ),
			'fields'          => $this->field->get_fields( 'plugins' ),
		];
	}

	private function get_content_step() {
		return [
			'id'              => 'content',
			'title'           => __( 'Content', 'mai-setup-wizard' ),
			'description'     => __( 'Select which content you would like to import. Please note that this step can take up to 5 minutes.', 'mai-setup-wizard' ),
			'order'           => 40,
			'error_message'   => __( 'Content could not be installed.', 'mai-setup-wizard' ),
			'success_message' => __( 'Content successfully installed!', 'mai-setup-wizard' ),
			'continue_text'   => __( 'Import Content', 'mai-setup-wizard' ),
			'loading_text'    => __( 'Importing content...', 'mai-setup-wizard' ),
			'fields'          => $this->field->get_fields( 'content' ),
		];
	}

	private function get_done_step() {
		return [
			'id'            => 'done',
			'title'         => __( 'Done', 'mai-setup-wizard' ),
			'description'   => __( 'Your theme has been all set up.', 'mai-setup-wizard' ),
			'order'         => 50,
			'continue_text' => __( 'View Your Site', 'mai-setup-wizard' ),
			'continue_url'  => \get_home_url(),
			'skip_text'     => __( 'Edit Your Site', 'mai-setup-wizard' ),
			'skip_url'      => \get_admin_url(),
			'fields'        => $this->field->get_fields( 'done' ),
		];
	}

	private function get_default_args( $id ) {
		$title = \ucwords( \str_replace( [ '-', '_' ], ' ', $id ) );

		return apply_filters( 'mai_setup_wizard_step_defaults', [
			'title'           => $title,
			'description'     => $title,
			'order'           => 10,
			'error_message'   => false,
			'success_message' => false,
			'continue_text'   => __( 'Continue', 'mai-setup-wizard' ),
			'continue_url'    => 'javascript:void(0)',
			'loading_text'    => __( 'Loading...', 'mai-setup-wizard' ),
			'skip_text'       => __( 'Skip this step', 'mai-setup-wizard' ),
			'skip_url'        => 'javascript:void(0)',
			'ajax_callback'   => "mai_setup_wizard_ajax_step_{$id}",
		] );
	}

	public function render( $step, $steps, $counter ) {
		?>
		<form id="<?php echo $step['id']; ?>" class="step" action="javascript:void(0);">
			<h2><?php echo $step['title']; ?></h2>
			<p><?php echo $step['description']; ?></p>
			<p class="error"><?php echo $step['error_message']; ?></p>
			<p class="success"><?php echo $step['success_message']; ?></p>
			<?php if ( $step['fields'] ): ?>
				<?php
				$items = \sprintf(
					' class="items-%s"',
					\count( $step['fields'] )
				);
				?>
				<ul<?php echo $items; ?>>
					<?php
					/**
					 * @var Field $field
					 */
					foreach ( $step['fields'] as $field ) : ?>
						<li><?php echo $this->field->render( $field ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div>
				<?php if ( 1 !== $counter && apply_filters( 'mai_setup_wizard_previous', true ) ): ?>
					<a href="javascript:void(0)" id="previous" class="button">
						<?php esc_html_e( 'Previous', 'mai-setup-wizard' ); ?>
					</a>
				<?php endif; ?>
				<?php $skip_target = filter_var( $step['skip_url'], FILTER_VALIDATE_URL ) === false ? '' : ' target="_blank"'; ?>
				<?php if ( $step['skip_text'] && $counter < count( $steps ) ): ?>
					<a href="<?php echo $step['skip_url']; ?>" <?php echo $skip_target; ?>id="skip" class="button">
						<?php echo $step['skip_text']; ?>
					</a>
				<?php endif; ?>
				<?php if ( $counter < \count( $steps ) ): ?>
					<?php $continue_target = filter_var( $step['skip_url'], FILTER_VALIDATE_URL ) === false ? '' : ' target="_blank"'; ?>
					<button href="<?php echo $step['continue_url']; ?>" <?php echo $continue_target; ?>id="submit" class="button-primary" data-default="<?php echo $step['continue_text']; ?>" data-loading="<?php esc_attr_e( $step['loading_text'] ); ?>">
						<?php echo $step['continue_text']; ?>
						<img src="https://www.cupraofficial.com/etc.clientlibs/seatComponents/components/login-component/clientlibs/resources/images/spinner.gif" alt="spinner" width="20">
					</button>
				<?php endif; ?>
			</div>
			<br>
			<small>
				<?php
				\printf(
					'%s %s %s %s',
					__( 'Step', 'mai-setup-wizard' ),
					$counter,
					__( 'of', 'mai-setup-wizard' ),
					\count( $steps )
				);
				?>
			</small>
		</form>
		<?php
	}
}
