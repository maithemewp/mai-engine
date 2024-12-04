'use strict';

const gulp = require('gulp');
const config = require('./config');
const tasks = require('./tasks'); // Import tasks from tasks.js

module.exports = function watchTask() {
    gulp.watch(config.src.scss, gulp.series('build:css'));
    // Add other watch tasks as needed
};