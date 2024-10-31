'use strict';

module.exports = function i18nTask() {
	return require( 'child_process' ).exec( 'composer i18n' );
};
