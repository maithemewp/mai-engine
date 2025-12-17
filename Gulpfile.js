process.env.DISABLE_NOTIFIER = true;

const gulp = require('gulp');
const tasks = require('./bin/tasks');

// Modern Gulp 5 task registration
Object.keys(tasks).forEach(function(taskName) {
	gulp.task(taskName, tasks[taskName]);
});