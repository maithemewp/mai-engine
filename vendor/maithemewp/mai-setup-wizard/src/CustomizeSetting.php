<?php

namespace MaiSetupWizard;

class CustomizeSetting extends \WP_Customize_Setting {
	public function import( $value ) {
		$this->update( $value );
	}
}
