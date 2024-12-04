'use strict';

const gulp = require('gulp');
const styles = require('./styles'); // Import style tasks
const scripts = require('./scripts'); // Import script tasks
const images = require('./images'); // Import image tasks
const i18n = require('./i18n'); // Import translation task
const create = require('./create'); // Import theme creation task
const watch = require('./watch'); // Import watch task

// Array of CSS tasks
const cssTasks = [
    styles.admin,
    styles.blocks,
    styles.deprecated,
    styles.desktop,
    styles.editor,
    styles.footer,
    styles.header,
    styles.pageheader,
    styles.main,
    styles.maiplugins,
    styles.plugins,
    styles.themes,
    styles.utilities,
];

// Gulp task exports
exports['build:admin-css'] = styles.admin;
exports['build:blocks-css'] = styles.blocks;
exports['build:deprecated-css'] = styles.deprecated;
exports['build:desktop-css'] = styles.desktop;
exports['build:editor-css'] = styles.editor;
exports['build:footer-css'] = styles.footer;
exports['build:header-css'] = styles.header;
exports['build:page-header-css'] = styles.pageheader;
exports['build:main-css'] = styles.main;
exports['build:maiplugins-css'] = styles.maiplugins;
exports['build:plugin-css'] = styles.plugins;
exports['build:theme-css'] = styles.themes;
exports['build:utilities-css'] = styles.utilities;

// Grouped CSS Task
exports['build:css'] = gulp.parallel(...cssTasks);

// JS Tasks
exports['build:blocks'] = scripts.blocks;
exports['build:scripts'] = scripts.js;
exports['build:js'] = gulp.parallel(scripts.js, scripts.blocks);

// Image Tasks
exports['build:images'] = images.img;
exports['build:svg'] = images.svg;
exports['build:img'] = gulp.parallel(images.img, images.svg);

// I18n Task
exports['build:i18n'] = i18n;

// Full Build Task
exports['build'] = gulp.series(
    gulp.parallel(...cssTasks),
    gulp.parallel(scripts.js, scripts.blocks),
    gulp.parallel(images.img, images.svg),
    i18n
);

// Theme Creation Task
exports['create:theme'] = create;
exports['create'] = gulp.series(create, gulp.parallel(...cssTasks));

// Watch Task
exports['watch'] = watch;

// Default Task
exports['default'] = gulp.series(
    gulp.parallel(...cssTasks),
    gulp.parallel(scripts.js, scripts.blocks),
    gulp.parallel(images.img, images.svg),
    i18n,
    watch
);