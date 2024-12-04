'use strict';

const gulp       = require('gulp');
const config     = require('./config');
const plumber    = require('gulp-plumber');
const uglify     = require('gulp-uglify-es').default;
const fs         = require('fs');
const notify     = require('gulp-notify');
const map        = require('lodash.map');
const exec       = require('child_process').exec;
const rename     = require('gulp-rename');

// Named function for processing blocks
module.exports.blocks = function blocksTaskScripts() {
    return new Promise((resolve, reject) => {
        exec('npm run blocks', (err, stdout, stderr) => {
            if (err) {
                console.error(stderr);
                reject(err);
                return;
            }
            console.log(stdout);
            resolve();
        });
    });
};

// Named function for processing JavaScript files
module.exports.js = function jsTask() {
    const dir = './assets/js/';
    const files = fs.readdirSync(dir).filter(function(file) {
        return file.indexOf('.js') > -1;
    });

    // Return a promise that resolves when all tasks are complete
    return Promise.all(map(files, function(file) {
        return gulp.src(dir + file)
            .pipe(plumber())
            .pipe(rename({ basename: file.replace('.js', ''), suffix: '.min' })) // Rename to add .min before .js
            .pipe(uglify())
            .pipe(gulp.dest(config.dest.js))
            .pipe(notify({ message: config.messages.js }));
    }));
};