'use strict';

const gulp = require( 'gulp' ),
	config = require( '../../config' ),
	plumber = require( 'gulp-plumber' ),
	sourcemap = require( 'gulp-sourcemaps' ),
	sass = require( 'gulp-sass' ),
	normalize = require( 'node-normalize-scss' ).includePaths,
	postcss = require( 'gulp-postcss' ),
	bulksass = require( 'gulp-sass-bulk-import' ),
	mqpacker = require( 'css-mqpacker' ),
	autoprefix = require( 'autoprefixer' ),
	cssnano = require( 'cssnano' ),
	pxtorem = require( 'postcss-pxtorem' ),
	remtopx = require( 'postcss-rem-to-pixel' ),
	notify = require( 'gulp-notify' ),
	map = require( 'lodash.map' ),
	rename = require( 'gulp-rename' ),
	fs = require( 'fs' ),
	gulpif = require( 'gulp-if' ),
	sassVars = require( 'gulp-sass-vars' ),
	path = require( 'path' );

module.exports = function() {

	const getPostProcessors = function( fileName ) {
		const postProcessors = [
			mqpacker( {
				sort: true,
			} ),
			autoprefix(),
			cssnano( config.css.cssnano )
		];

		if ( fileName !== 'editor' ) {
			postProcessors.push( pxtorem( {
				root_value: config.css.basefontsize,
				replace: config.css.remreplace,
				media_query: config.css.remmediaquery,
			} ) );
		}

		if ( fileName === 'editor' ) {
			postProcessors.push( remtopx( {
				rootValue: config.css.basefontsize
			} ) );
		}

		return postProcessors;

	};

	const themes = function() {
		return fs.readdirSync( './config/' );
	};

	const stylesheets = [];

	themes().forEach( function( theme ) {

		let files = function() {
			return fs.readdirSync( './assets/scss/' ).filter( function( file ) {
				if ( file.includes( '.scss' ) ) {
					return file;
				}
			} );
		};

		files().forEach( function( file ) {
			stylesheets.push( theme + '-' + file );
		} );
	} );

	return map( stylesheets, function( stylesheet ) {

		let themeName = function() {
			return stylesheet.substring( 0, stylesheet.indexOf( '-' ) );
		};

		let fileName = function() {
			return stylesheet.replace( '.scss', '' ).split( '-' )[ 1 ];
		};

		let themeConf = function() {
			return config.src.base + 'config/' + themeName() + '/';
		};

		let themeVars = function() {
			return require( '../../../config/' + themeName() + '/config.json' );
		};

		let fileSrc = function() {
			return './assets/scss/' + fileName() + '.scss';
		};

		let fileDest = function() {
			return './assets/css/' + themeName() + '/';
		};

		if ( ! fs.existsSync( fileSrc() ) ) {
			return console.log( 'ERROR >> Source file ' + fileSrc() +
				' was not found.' );
		}

		return gulp.src( fileSrc() )
			.pipe( sassVars( themeVars(), { verbose: false } ) )
			.pipe( bulksass() )
			.pipe( plumber() )
			.pipe( rename( fileName() + '.css' ) )
			.pipe( gulpif( true, sourcemap.init() ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
				includePaths: [].concat( normalize ).concat( themeConf() )
			} ) )
			.pipe( postcss( getPostProcessors( fileName() ) ) )
			.pipe( gulpif( true, sourcemap.write( './' ) ) )
			.pipe( gulp.dest( fileDest() ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};
