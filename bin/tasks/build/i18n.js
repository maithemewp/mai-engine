'use strict';

module.exports = function() {
	return require( 'child_process' ).exec( 'composer i18n' );
};
