'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( '../../config' ),
	  plumber           = require( 'gulp-plumber' ),
	  sourcemap         = require( 'gulp-sourcemaps' ),
	  sass              = require( 'gulp-sass' ),
	  normalize         = require( 'node-normalize-scss' ).includePaths,
	  postcss           = require( 'gulp-postcss' ),
	  bulksass          = require( 'gulp-sass-bulk-import' ),
	  mqpacker          = require( 'css-mqpacker' ),
	  autoprefix        = require( 'autoprefixer' ),
	  cssnano           = require( 'cssnano' ),
	  pxtorem           = require( 'postcss-pxtorem' ),
	  remtopx           = require( 'postcss-rem-to-pixel' ),
	  notify            = require( 'gulp-notify' ),
	  map               = require( 'lodash.map' ),
	  deepmerge         = require( 'deepmerge' ),
	  rename            = require( 'gulp-rename' ),
	  fs                = require( 'fs' ),
	  gulpif            = require( 'gulp-if' ),
	  sassVars          = require( 'gulp-sass-vars' ),
	  combineSelectors  = require( 'postcss-combine-duplicated-selectors' ),
	  discardDuplicates = require( 'postcss-discard-duplicates' );

module.exports = function() {

	const getPostProcessors = function( fileName, themeName ) {
		const postProcessors = [
			mqpacker( {
				sort: true,
			} ),
			autoprefix(),
			cssnano( config.css.cssnano ),
			combineSelectors,
			discardDuplicates,
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

	let themes = function() {
		let themes = fs.readdirSync( './config/' );

		themes.splice( themes.indexOf( '_settings' ), 1 );

		themes.push( '_default' );

		return themes;
	};

	let stylesheets = [];

	themes().forEach( function( theme ) {

		let files = function() {
			return [ 'editor', 'main' ];
		};

		files().forEach( function( file ) {
			stylesheets.push( theme + '+' + file );
		} );
	} );

	return map( stylesheets, function( stylesheet ) {

		let themeName = function() {
			return stylesheet.substring( 0, stylesheet.indexOf( '+' ) );
		};

		let fileName = function() {
			return stylesheet.replace( '.scss', '' ).split( '+' )[ 1 ];
		};

		let outputFileName = function( fileName ) {
			const outputFileName = fileName === 'editor' ? themeName() + '-editor.min.css' : themeName() + '.min.css';

			return outputFileName.replace( '_', '' );
		};

		let themeConf = function() {
			return config.src.base + 'config/' + themeName();
		};

		let themeVars = function() {
			let defaults = require( '../../../config/_default/config.json' );
			let theme    = require( '../../../config/' + themeName() + '/config.json' );

			return deepmerge( defaults, theme );
		};

		let fileSrc = function() {
			return './assets/scss/' + fileName() + '.scss';
		};

		let fileDest = function() {
			return './assets/css/themes/';
		};

		if ( ! fs.existsSync( fileSrc() ) ) {
			return console.log( 'ERROR >> Source file ' + fileSrc() +
				' was not found.' );
		}

		return gulp.src( fileSrc() )
			.pipe( sassVars( themeVars(), { verbose: false } ) )
			.pipe( bulksass() )
			.pipe( plumber() )
			.pipe( rename( outputFileName( fileName() ) ) )
			.pipe( gulpif( config.css.sourcemaps, sourcemap.init() ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
				includePaths: [].concat( normalize ).concat( themeConf() )
			} ) )
			.pipe( postcss( getPostProcessors( fileName(), themeName() ) ) )
			.pipe( gulpif( config.css.sourcemaps, sourcemap.write( './' ) ) )
			.pipe( gulp.dest( fileDest() ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};
