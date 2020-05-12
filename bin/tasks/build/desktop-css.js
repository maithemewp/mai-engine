'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  plumber           = require( 'gulp-plumber' ),
	  sass              = require( 'gulp-sass' ),
	  postcss           = require( 'gulp-postcss' ),
	  mqpacker          = require( 'css-mqpacker' ),
	  autoprefix        = require( 'autoprefixer' ),
	  cssnano           = require( 'cssnano' ),
	  notify            = require( 'gulp-notify' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' ),
	  through           = require( 'through2' ),
	  rework            = require( 'rework' ),
	  split             = require( 'rework-split-media' ),
	  reworkMoveMedia   = require( 'rework-move-media' ),
	  stringify         = require( 'css-stringify' ),
	  cleanUpString     = require( 'clean-up-string' ),
	  dirname           = require( 'path' ).dirname,
	  pathjoin          = require( 'path' ).join;

const extractMediaQueries = function() {
	return through.obj( function( file, enc, callback ) {
		let stream         = this;
		let reworkData     = rework( file.contents.toString() ).use( reworkMoveMedia() );
		let stylesheets    = split( reworkData );
		let stylesheetKeys = Object.keys( stylesheets );

		stylesheetKeys.forEach( function( key ) {
			let clone      = file.clone( { contents: false } );
			let query      = key.split( 'width' )[ 0 ];
			let size       = key.split( 'width' )[ 1 ];
			let name       = cleanUpString( query + '-width-' + size );
			let contents   = stringify( stylesheets[ key ] );
			clone.contents = new Buffer( contents );

			if ( name ) {
				clone.path = pathjoin( dirname( file.path ), name + '.css' );
			} else {
				clone.path = file.path;
			}

			stream.push( clone );
		} );

		callback();
	} );
};

module.exports = function() {

	const getPostProcessors = function() {
		return [
			mqpacker( {
				sort: true,
			} ),
			autoprefix(),
			cssnano( config.css.cssnano ),
			combineSelectors,
			discardDuplicates,
		];
	};

	return gulp.src( './assets/css/themes/default.min.css' )
		.pipe( plumber() )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
		} ) )
		.pipe( extractMediaQueries() )
		.pipe( postcss( getPostProcessors() ) )
		.pipe( gulp.dest( './assets/css/desktop/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};
