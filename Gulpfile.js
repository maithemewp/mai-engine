process.env.DISABLE_NOTIFIER = true;

const gulp = require('gulp'),
	tasks = require('./bin/tasks');

Object.keys(tasks).forEach(function(taskName) {
	const args = [taskName].concat(tasks[taskName]);

	gulp.task.apply(gulp, args);
});