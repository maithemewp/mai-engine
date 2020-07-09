'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  bourbon           = require( 'bourbon' ).includePaths,
	  plumber           = require( 'gulp-plumber' ),
	  sass              = require( 'gulp-sass' ),
	  postcss           = require( 'gulp-postcss' ),
	  mqpacker          = require( 'css-mqpacker' ),
	  autoprefix        = require( 'autoprefixer' ),
	  cssnano           = require( 'cssnano' ),
	  notify            = require( 'gulp-notify' ),
	  remtopx           = require( 'postcss-rem-to-pixel' ),
	  rename            = require( 'gulp-rename' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' );

module.exports = function() {
	return gulp.src( './assets/scss/editor.scss' )
		.pipe( plumber() )
		.pipe( rename( 'editor.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( [
			mqpacker( {
				sort: true,
			} ),
			autoprefix(),
			cssnano( config.css.cssnano ),
			combineSelectors,
			discardDuplicates,
			remtopx( {
				rootValue: config.css.basefontsize
			} )
		] ) )
		.pipe( gulp.dest( './assets/css/editor/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};
