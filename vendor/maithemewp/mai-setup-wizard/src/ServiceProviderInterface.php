<?php

namespace MaiSetupWizard;

interface ServiceProviderInterface {

	public function register( $container );

	public function add_hooks();
}
