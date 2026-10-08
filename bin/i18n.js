'use strict';

module.exports = function i18nTask() {
	return require( 'child_process' ).exec( 'composer i18n', ( error, stdout, stderr ) => {
		// Gulp only reports the exit code, so print what composer said when it fails.
		if ( error ) {
			process.stderr.write( `${ stdout }${ stderr }` );
		}
	} );
};
