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
	  rename            = require( 'gulp-rename' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' );

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

	return gulp.src( './assets/scss/admin.scss' )
		.pipe( plumber() )
		.pipe( rename( 'admin.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
		} ) )
		.pipe( postcss( getPostProcessors() ) )
		.pipe( gulp.dest( './assets/css/admin/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};
