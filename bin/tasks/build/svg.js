'use strict';

const gulp = require( 'gulp' ),
	config = require( '../../config' ),
	map = require( 'lodash.map' ),
	notify = require( 'gulp-notify' );

module.exports = function() {

	return map( config.src.svg, function( style ) {
		const dir = style.split( '/' );

		return gulp.src( style + '/*' )
			.pipe( gulp.dest( config.dest.svg + '/' + dir[ dir.length - 1 ] ) )
			.pipe( notify( { message: config.messages.svg } ) );
	} );
};
