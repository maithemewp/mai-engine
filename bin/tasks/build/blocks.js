'use strict';

module.exports = function() {
	return require( 'child_process' ).exec( 'npm run blocks' );
};
