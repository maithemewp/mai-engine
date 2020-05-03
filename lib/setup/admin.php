<?php

add_action( 'admin_menu', 'mai_setup_wizard_submenu_page', 100 );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_setup_wizard_submenu_page() {
	add_submenu_page(
		mai_get_handle(),
		__( 'Setup Wizard', 'mai-engine' ),
		__( 'Setup Wizard', 'mai-engine' ),
		'manage_options',
		'mai-setup',
		'mai_render_setup_wizard_page'
	);
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_render_setup_wizard_page() {
	?>
	<div class="mai-setup-wizard">
		<div class="wrap">
			<form class="mai-setup-form">
				<?php do_action( 'mai_before_setup_wizard_steps' ); ?>

				<?php $steps = mai_setup_wizard_steps(); ?>

				<?php foreach ( $steps as $step ) : ?>

					<fieldset class="mai-step mai-step-<?php echo $step->id; ?>">
						<h2><?php echo $step->title; ?></h2>
						<p><?php echo $step->description; ?></p>
						<p class="error-message"><?php echo $step->error_message; ?></p>
						<p class="success-message"><?php echo $step->success_message; ?></p>
						<ul class="mai-step-fields">
							<?php foreach ( $step->fields as $field ) : ?>
								<li class="mai-step-field"><?php echo mai_build_step_field( $field ); ?></li>
							<?php endforeach; ?>
						</ul>
					</fieldset>

				<?php endforeach; ?>

				<?php do_action( 'mai_after_setup_wizard_steps' ); ?>

				<br>
				<p class="mai-continue-wrap">
					<a href="javascript:void(0)" class="mai-continue button button-primary button-hero">
						Continue
						<img class="mai-spinner" src="https://www.cupraofficial.com/etc.clientlibs/seatComponents/components/login-component/clientlibs/resources/images/spinner.gif" alt="spinner" width="20"></a>
					<br>
					<a href="javascript:void(0)" class="mai-skip">Skip this step</a>
				</p>
			</form>
			<br>
			<a href="<?php echo admin_url(); ?>">Return to dashboard</a>
		</div>
	</div>

	<?php
}

/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @param $field
 *
 * @return string
 */
function mai_build_step_field( $field ) {
	$type  = isset( $field['type'] ) ? $field['type'] : 'text';
	$label = isset( $field['label'] ) ? $field['label'] : '';
	$image = isset( $field['image'] ) ? $field['image'] : [];
	$id    = isset( $field['id'] ) ? $field['id'] : $field['name'];
	$skip  = [ 'element', 'label', 'image', 'checked' ];
	$html  = '';

	if ( $label ) {
		$html .= sprintf( '<label for="%s" class="mai-%s-label">', $id, $type );
	}

	$html .= '<' . $field['element'];

	foreach ( $field as $attr => $value ) {
		if ( ! in_array( $attr, $skip, true ) ) {
			$html .= " $attr=\"$value\"";
		}
	}

	if ( isset( $field['checked'] ) && $field['checked'] ) {
		$html .= ' checked';
	}

	$html .= '>';

	if ( ! empty( $image ) ) {
		$html .= '<img';

		foreach ( $image as $attr => $value ) {
			$html .= " $attr=\"$value\"";
		}

		$html .= '>';
	}

	if ( $label ) {
		$html .= $label;
		$html .= '</label>';
	}

	return $html;
}
