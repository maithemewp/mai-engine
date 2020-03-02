<?php



register_activation_hook( dirname( __DIR__ ) . '/mai-engine.php', 'mai_short_circuit_acf' );
/**
 * Description of expected behavior.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mai_short_circuit_acf() {
	deactivate_plugins( '/advanced-custom-fields/acf.php' );
}

