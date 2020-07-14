'use strict';

const browserSync = require( 'browser-sync' ).create( 'SIM01' ),
	  config      = require( './config' );

module.exports = function() {
	if ( config.server ) {
		browserSync.init( config.server );
	}
};
