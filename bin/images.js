'use strict';

const gulp = require('gulp');
const config = require('./config');
const notify = require('gulp-notify');
const map = require('lodash.map');

// Named function for image processing
module.exports.img = async function imgTask() {
    // Dynamic imports for ES Modules
    const changed = (await import('gulp-changed')).default;
    const filter = (await import('gulp-filter')).default;
    const imagemin = (await import('gulp-imagemin')).default;

    const allButScreenshot = filter(['**/*', '!**/screenshot.*'], { restore: true });
    const onlyScreenshot = filter(['**/screenshot.*']);

    return gulp.src(config.src.images)
        .pipe(changed(config.dest.images))
        .pipe(imagemin({
            verbose: true,
            optimizationLevel: 3,
            progressive: true,
            interlaced: true
        }))
        .pipe(allButScreenshot)
        .pipe(gulp.dest(config.dest.images))
        .pipe(allButScreenshot.restore)
        .pipe(onlyScreenshot)
        .pipe(gulp.dest('./'))
        .pipe(notify({ message: config.messages.images }));
};

// Named function for SVG processing
module.exports.svg = function svgTask() {
    return Promise.all(map(config.src.svg, function (style) {
        const dir = style.split('/');

        return gulp.src(style + '/*')
            .pipe(gulp.dest(config.dest.svg + '/' + dir[dir.length - 1]))
            .pipe(notify({ message: config.messages.svg }));
    }));
};