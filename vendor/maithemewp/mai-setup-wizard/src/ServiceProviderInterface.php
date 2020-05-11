<?php

namespace MaiSetupWizard;

interface ServiceProviderInterface {

	public function register( \Pimple\Container $container );

	public function add_hooks();
}
