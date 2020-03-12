'use strict';

const gulp = require('gulp'),
	config = require('../../config'),
	plumber = require('gulp-plumber'),
	sourcemap = require('gulp-sourcemaps'),
	sass = require('gulp-sass'),
	normalize = require('node-normalize-scss').includePaths,
	postcss = require('gulp-postcss'),
	mqpacker = require('css-mqpacker'),
	autoprefix = require('autoprefixer'),
	cssnano = require('cssnano'),
	pxtorem = require('postcss-pxtorem'),
	remtopx = require('postcss-rem-to-pixel'),
	notify = require('gulp-notify'),
	map = require('lodash.map'),
	rename = require('gulp-rename'),
	combineSelectors = require('postcss-combine-duplicated-selectors'),
	discardDuplicates = require('postcss-discard-duplicates');

module.exports = function () {

	const getPostProcessors = function (fileName, themeName) {
		const postProcessors = [
			mqpacker({
				sort: true,
			}),
			autoprefix(),
			cssnano(config.css.cssnano),
			combineSelectors,
			discardDuplicates,
		];

		if (fileName !== 'editor') {
			postProcessors.push(pxtorem({
				root_value: config.css.basefontsize,
				replace: config.css.remreplace,
				media_query: config.css.remmediaquery,
			}));
		}

		return postProcessors;
	};

	return map(stylesheets, function (stylesheet) {

		return gulp.src(fileSrc())
			.pipe(sassVars(themeVars(), {verbose: false}))
			.pipe(bulksass())
			.pipe(plumber())
			.pipe(rename(outputFileName(fileName())))
			.pipe(gulpif(config.css.sourcemaps, sourcemap.init()))
			.pipe(sass.sync({
				outputStyle: 'compressed',
				includePaths: [].concat(normalize).concat(themeConf())
			}))
			.pipe(postcss(getPostProcessors(fileName(), themeName())))
			.pipe(gulpif(config.css.sourcemaps, sourcemap.write('./')))
			.pipe(gulp.dest(fileDest()))
			.pipe(notify({message: config.messages.css}));
	});
};
