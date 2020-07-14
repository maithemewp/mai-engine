'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  autoprefix        = require( 'autoprefixer' ),
	  bourbon           = require( 'bourbon' ).includePaths,
	  mqpacker          = require( 'css-mqpacker' ),
	  cssnano           = require( 'cssnano' ),
	  fs                = require( 'fs' ),
	  gulpif            = require( 'gulp-if' ),
	  notify            = require( 'gulp-notify' ),
	  plumber           = require( 'gulp-plumber' ),
	  postcss           = require( 'gulp-postcss' ),
	  rename            = require( 'gulp-rename' ),
	  sourcemap         = require( 'gulp-sourcemaps' ),
	  sass              = require( 'gulp-sass' ),
	  bulksass          = require( 'gulp-sass-bulk-import' ),
	  map               = require( 'lodash.map' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' ),
	  pxtorem           = require( 'postcss-pxtorem' );

module.exports = function() {
	return map( fs.readdirSync( './assets/scss/themes/' ), function( stylesheet ) {
		return gulp.src( './assets/scss/themes/' + stylesheet )
			.pipe( bulksass() )
			.pipe( plumber() )
			.pipe( rename( {
				suffix: '.min'
			} ) )
			.pipe( gulpif( config.css.sourcemaps, sourcemap.init() ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
				includePaths: [].concat( bourbon )
			} ) )
			.pipe( postcss( [
				mqpacker( {
					sort: true,
				} ),
				autoprefix(),
				cssnano( {
					discardComments: {
						removeAll: true
					},
					zindex: false,
					reduceIdents: false,
				} ),
				combineSelectors,
				discardDuplicates,
				pxtorem( {
					root_value: config.css.basefontsize,
					replace: config.css.remreplace,
					media_query: config.css.remmediaquery,
				} )
			] ) )
			.pipe( gulpif( config.css.sourcemaps, sourcemap.write( './' ) ) )
			.pipe( gulp.dest( './assets/css/themes/' ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};
