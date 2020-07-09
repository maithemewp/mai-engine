'use strict';

const gulp    = require( 'gulp' ),
	  config  = require( '../../config' ),
	  plumber = require( 'gulp-plumber' ),
	  uglify  = require( 'gulp-uglify-es' ).default,
	  rename  = require( 'gulp-rename' ),
	  fs      = require( 'fs' ),
	  notify  = require( 'gulp-notify' ),
	  map     = require( 'lodash.map' );

module.exports = function() {
	const dir   = './assets/js/';
	const files = fs.readdirSync( dir ).filter( function( file ) {
		if ( file.indexOf( '.js' ) > + 1 ) return file;
	} );

	return map( files, function( file ) {
		return gulp.src( dir + file )
			.pipe( plumber() )
			.pipe( rename( {
				suffix: '.min'
			} ) )
			.pipe( uglify() )
			.pipe( gulp.dest( config.dest.js ) )
			.pipe( notify( { message: config.messages.js } ) );
	} );
};
