'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  plumber           = require( 'gulp-plumber' ),
	  sourcemap         = require( 'gulp-sourcemaps' ),
	  sass              = require( 'gulp-sass' ),
	  bourbon           = require( 'bourbon' ).includePaths,
	  normalize         = require( 'node-normalize-scss' ).includePaths,
	  postcss           = require( 'gulp-postcss' ),
	  bulksass          = require( 'gulp-sass-bulk-import' ),
	  mqpacker          = require( 'css-mqpacker' ),
	  autoprefix        = require( 'autoprefixer' ),
	  cssnano           = require( 'cssnano' ),
	  pxtorem           = require( 'postcss-pxtorem' ),
	  notify            = require( 'gulp-notify' ),
	  map               = require( 'lodash.map' ),
	  rename            = require( 'gulp-rename' ),
	  fs                = require( 'fs' ),
	  gulpif            = require( 'gulp-if' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' );

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
				includePaths: [].concat( bourbon ).concat( normalize )
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
