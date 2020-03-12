'use strict';

const gulp = require( 'gulp' ),
	config = require( '../../config' ),
	plumber = require( 'gulp-plumber' ),
	sourcemap = require( 'gulp-sourcemaps' ),
	sass = require( 'gulp-sass' ),
	normalize = require( 'node-normalize-scss' ).includePaths,
	postcss = require( 'gulp-postcss' ),
	mqpacker = require( 'css-mqpacker' ),
	autoprefix = require( 'autoprefixer' ),
	cssnano = require( 'cssnano' ),
	pxtorem = require( 'postcss-pxtorem' ),
	fs = require( 'fs' ),
	sassVars = require('gulp-sass-vars'),
	notify = require( 'gulp-notify' ),
	map = require( 'lodash.map' ),
	rename = require( 'gulp-rename' ),
	combineSelectors = require( 'postcss-combine-duplicated-selectors' ),
	discardDuplicates = require( 'postcss-discard-duplicates' );

module.exports = function() {

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

		let themeVars = function () {
			return require('../../../config/_default/config.json');
		};

		return gulp.src( fileSrc() )
			.pipe(sassVars(themeVars(), {verbose: false}))
			.pipe( plumber() )
			.pipe( rename( stylesheet + '.min.scss' ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
			} ) )
			.pipe( postcss( [
				mqpacker( {
					sort: true,
				} ),
				autoprefix(),
				cssnano( config.css.cssnano ),
				combineSelectors,
				discardDuplicates,
				pxtorem( {
					root_value: config.css.basefontsize,
					replace: config.css.remreplace,
					media_query: config.css.remmediaquery,
				} )
			] ) )
			.pipe( gulp.dest( './assets/css/plugins/' ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};
