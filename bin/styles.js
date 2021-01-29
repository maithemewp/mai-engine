'use strict';

const gulp              = require( 'gulp' ),
	  config            = require( './config' ),
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
	  pxtorem           = require( 'postcss-pxtorem' ),
	  remtopx           = require( 'postcss-rem-to-pixel' );

const postProcessors = [
	mqpacker( {
		sort: true,
	} ),
	autoprefix(),
	cssnano( config.css.cssnano ),
	combineSelectors,
	discardDuplicates,
];

const pxtoremConfig = pxtorem( {
	root_value: config.css.basefontsize,
	replace: config.css.remreplace,
	media_query: config.css.remmediaquery,
} );

module.exports.main = function() {
	return gulp.src( './assets/scss/main.scss' )
		.pipe( plumber() )
		.pipe( rename( 'main.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.header = function() {
	return gulp.src( './assets/scss/header.scss' )
		.pipe( plumber() )
		.pipe( rename( 'header.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.blocks = function() {
	return gulp.src( './assets/scss/blocks.scss' )
		.pipe( plumber() )
		.pipe( rename( 'blocks.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.utilities = function() {
	return gulp.src( './assets/scss/utilities.scss' )
		.pipe( plumber() )
		.pipe( rename( 'utilities.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.footer = function() {
	return gulp.src( './assets/scss/footer.scss' )
		.pipe( plumber() )
		.pipe( rename( 'footer.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.editor = function() {
	postProcessors.push( remtopx( {
		rootValue: config.css.basefontsize
	} ) );

	return gulp.src( './assets/scss/editor.scss' )
		.pipe( plumber() )
		.pipe( rename( 'editor.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.themes = function() {
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
				pxtoremConfig
			] ) )
			.pipe( gulpif( config.css.sourcemaps, sourcemap.write( './' ) ) )
			.pipe( gulp.dest( './assets/css/themes/' ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};

module.exports.plugins = function() {
	return map( fs.readdirSync( './assets/scss/plugins/' ), function( stylesheet ) {
		let fileSrc = function() {
			return './assets/scss/plugins/' + stylesheet;
		};

		if ( stylesheet !== 'advanced-custom-fields' && stylesheet !== 'kirki' ) {
			postProcessors.push( pxtoremConfig );
		}

		return gulp.src( fileSrc() )
			.pipe( plumber() )
			.pipe( rename( {
				suffix: '.min'
			} ) )
			.pipe( sass.sync( {
				outputStyle: 'compressed',
				includePaths: [].concat( bourbon )
			} ) )
			.pipe( postcss( postProcessors ) )
			.pipe( gulp.dest( './assets/css/' ) )
			.pipe( notify( { message: config.messages.css } ) );
	} );
};

module.exports.admin = function() {
	return gulp.src( './assets/scss/admin.scss' )
		.pipe( plumber() )
		.pipe( rename( 'admin.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.desktop = function() {
	return gulp.src( './assets/scss/desktop.scss' )
		.pipe( plumber() )
		.pipe( rename( 'desktop.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.desktop = function() {
	return gulp.src( './assets/scss/columns.scss' )
		.pipe( plumber() )
		.pipe( rename( 'columns.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
			includePaths: [].concat( bourbon )
		} ) )
		// .pipe( postcss( postProcessors ) ) // This was changing 100% in max-width to just 1.
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};

module.exports.deprecated = function() {
	return gulp.src( './assets/scss/deprecated.scss' )
		.pipe( plumber() )
		.pipe( rename( 'deprecated.min.scss' ) )
		.pipe( sass.sync( {
			outputStyle: 'compressed',
		} ) )
		.pipe( postcss( postProcessors ) )
		.pipe( gulp.dest( './assets/css/' ) )
		.pipe( notify( { message: config.messages.css } ) );
};
