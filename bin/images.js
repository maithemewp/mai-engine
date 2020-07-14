'use strict';

const gulp     = require( 'gulp' ),
	  config   = require( './config' ),
	  changed  = require( 'gulp-changed' ),
	  filter   = require( 'gulp-filter' ),
	  imagemin = require( 'gulp-imagemin' ),
	  notify   = require( 'gulp-notify' ),
	  map      = require( 'lodash.map' );

module.exports.img = function() {
	const allButScreenshot = filter( [ '**/*', '!**/screenshot.*' ], { restore: true } );
	const onlyScreenshot   = filter( [ '**/screenshot.*' ] );

	return gulp.src( config.src.images )
		.pipe( changed( config.dest.images ) )
		.pipe( imagemin( {
			verbose: true,
			optimizationLevel: 3,
			progressive: true,
			interlaced: true
		} ) )
		.pipe( allButScreenshot )
		.pipe( gulp.dest( config.dest.images ) )
		.pipe( allButScreenshot.restore )
		.pipe( onlyScreenshot )
		.pipe( gulp.dest( './' ) )
		.pipe( notify( { message: config.messages.images } ) );
};

module.exports.svg = function() {
	return map( config.src.svg, function( style ) {
		const dir = style.split( '/' );

		return gulp.src( style + '/*' )
			.pipe( gulp.dest( config.dest.svg + '/' + dir[ dir.length - 1 ] ) )
			.pipe( notify( { message: config.messages.svg } ) );
	} );
};
