'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  plumber           = require( 'gulp-plumber' ),
	  sourcemap         = require( 'gulp-sourcemaps' ),
	  sass              = require( 'gulp-sass' ),
	  bourbon           = require( 'bourbon' ).includePaths,
	  normalize         = require( 'node-normalize-scss' ).includePaths,
	  postcss           = require( 'gulp-postcss' ),
	  mqpacker          = require( 'css-mqpacker' ),
	  autoprefix        = require( 'autoprefixer' ),
	  cssnano           = require( 'cssnano' ),
	  pxtorem           = require( 'postcss-pxtorem' ),
	  fs                = require( 'fs' ),
	  sassVars          = require( 'gulp-sass-vars' ),
	  notify            = require( 'gulp-notify' ),
	  map               = require( 'lodash.map' ),
	  rename            = require( 'gulp-rename' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' );

module.exports = function() {

	const getPostProcessors = function( fileName ) {
		const postProcessors = [
			mqpacker( {
				sort: true,
			} ),
			autoprefix(),
			cssnano( config.css.cssnano ),
			combineSelectors,
			discardDuplicates,
		];

		if ( fileName !== 'advanced-custom-fields' && fileName !== 'kirki' ) {
			postProcessors.push( pxtorem( {
				root_value: config.css.basefontsize,
				replace: config.css.remreplace,
				media_query: config.css.remmediaquery,
			} ) );
		}

		return postProcessors;
	};

	let plugins = function() {
		return fs.readdirSync( './assets/scss/plugins/' );
	};

	let stylesheets = [];

	plugins().forEach( function( plugin ) {
		stylesheets.push( plugin );
	} );

	return map( stylesheets, function( stylesheet ) {

		let fileSrc = function() {
			return './assets/scss/plugins/' + stylesheet + '/__index.scss';
		};

		return gulp.src( fileSrc() )
			.pipe( plumber() )
			.pipe( rename( stylesheet + '.min.scss' ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
				includePaths: [].concat( bourbon )
			} ) )
			.pipe( postcss( getPostProcessors( stylesheet ) ) )
			.pipe( gulp.dest( './assets/css/plugins/' ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};
