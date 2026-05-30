'use strict';

const gulp              = require('gulp');
const config            = require('./config');
const autoprefix        = require('autoprefixer');
const sortMediaQueries  = require('postcss-sort-media-queries');
const cssnano           = require('cssnano');
const fs                = require('fs');
const gulpif            = require('gulp-if');
const notify            = require('gulp-notify');
const plumber           = require('gulp-plumber');
const postcss           = require('gulp-postcss');
const sourcemap         = require('gulp-sourcemaps');
const sass              = require('sass');
const bulksass          = require('gulp-sass-bulk-import');
const map               = require('lodash.map');
const combineSelectors   = require('postcss-combine-duplicated-selectors');
const discardDuplicates  = require('postcss-discard-duplicates');
const pxtorem           = require('postcss-pxtorem');
const remtopx           = require('postcss-rem-to-pixel');
const rename            = require('gulp-rename');
const through2          = require('through2');

// Custom Sass plugin using native compiler
function nativeSass(options = {}) {
    return through2.obj(function(file, enc, cb) {
        if (file.isNull()) {
            return cb(null, file);
        }

        if (file.isStream()) {
            return cb(new Error('Streaming not supported'));
        }

        try {
            const result = sass.compile(file.path, {
                style: 'compressed',
                loadPaths: ['./assets/scss'],
                ...options
            });

            file.contents = Buffer.from(result.css);
            cb(null, file);
        } catch (error) {
            cb(error);
        }
    });
}

// Post-processing configuration
const postProcessors = [
    sortMediaQueries(),
    autoprefix(),
    // cssnano 7 reads optimization options from `preset`, NOT from the top-level
    // object. Passing `cssnano(config.css.cssnano)` directly silently ignored the
    // entire config (mergeRules/convertValues/etc.), so builds ran stock defaults.
    // That let `mergeRules` hoist scoped alignfull selectors into bare ones and
    // `convertValues` strip the unit off `@property { initial-value: 0px }`.
    cssnano({ preset: ['default', config.css.cssnano] }),
    combineSelectors,
    discardDuplicates,
];

const pxtoremConfig = pxtorem({
    root_value: config.css.basefontsize,
    replace: config.css.remreplace,
    media_query: config.css.remmediaquery,
});

// Task to compile main SCSS file
module.exports.main = function mainTask() {
    return gulp.src('./assets/scss/main.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('main.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile header SCSS file
module.exports.header = function headerTask() {
    return gulp.src('./assets/scss/header.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('header.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile page-header SCSS file
module.exports.pageheader = function pageheaderTask() {
    return gulp.src('./assets/scss/page-header.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('page-header.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile blocks SCSS file
module.exports.blocks = function blocksTaskStyles() {
    return gulp.src('./assets/scss/blocks.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('blocks.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile utilities SCSS file
module.exports.utilities = function utilitiesTask() {
    return gulp.src('./assets/scss/utilities.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('utilities.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile footer SCSS file
module.exports.footer = function footerTask() {
    return gulp.src('./assets/scss/footer.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('footer.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile editor SCSS file
module.exports.editor = function editorTask() {
    const editorPostProcessors = [...postProcessors, remtopx({ rootValue: config.css.basefontsize })];

    return gulp.src('./assets/scss/editor.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('editor.min.css')) // Rename to add .min
        .pipe(postcss(editorPostProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile theme SCSS files
module.exports.themes = function themesTask() {
    // Process every theme stylesheet in a SINGLE stream and `return` it so gulp awaits
    // completion natively (same as the single-file tasks above). The previous
    // `Promise.all(map(..., () => gulp.src(...)...))` returned un-awaited gulp streams,
    // so the task "finished" before writes flushed and theme .min.css files went stale.
    // The themes dir is flat (no partials/subdirs), so a *.scss glob maps 1:1 to outputs;
    // rename({ suffix: '.min' }) keeps each file's own basename. See issue #660.
    return gulp.src('./assets/scss/themes/*.scss')
        .pipe(bulksass())
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename({ suffix: '.min', extname: '.css' }))
        .pipe(gulpif(config.css.sourcemaps, sourcemap.init()))
        .pipe(postcss([
            sortMediaQueries(),
            autoprefix(),
            // Same preset wrapping as the shared `postProcessors` above — pass options
            // under `preset` so cssnano 7 actually reads them (a flat object is silently
            // ignored). Use the shared config.css.cssnano so theme CSS gets the same
            // mergeRules/convertValues protection as the rest of the build. See #660.
            cssnano({ preset: ['default', config.css.cssnano] }),
            combineSelectors,
            discardDuplicates,
            pxtoremConfig,
        ]))
        .pipe(gulpif(config.css.sourcemaps, sourcemap.write('./')))
        .pipe(gulp.dest('./assets/css/themes/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile plugin SCSS files
module.exports.plugins = function pluginsTask() {
    return Promise.all(map(fs.readdirSync('./assets/scss/plugins/'), function (stylesheet) {
        const fileSrc = function () {
            return './assets/scss/plugins/' + stylesheet;
        };

        const pluginPostProcessors = [...postProcessors];
        if (stylesheet !== 'advanced-custom-fields' && stylesheet !== 'kirki') {
            pluginPostProcessors.push(pxtoremConfig);
        }

        return gulp.src(fileSrc())
            .pipe(plumber())
            .pipe(nativeSass())
            .pipe(rename({ basename: stylesheet.replace('.scss', ''), suffix: '.min', extname: '.css' })) // Correctly rename with .css extension
            .pipe(postcss(pluginPostProcessors))
            .pipe(gulp.dest('./assets/css/')) // Output to top-level directory
            .pipe(notify({ message: config.messages.css }));
    }));
};

// Task to compile MAI plugin SCSS file
module.exports.maiplugins = function maipluginsTask() {
    const maipluginsPostProcessors = [...postProcessors, remtopx({ rootValue: config.css.basefontsize })];

    return gulp.src('./assets/scss/plugins.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('plugins.min.css')) // Rename to add .min
        .pipe(postcss(maipluginsPostProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile admin SCSS file
module.exports.admin = function adminTask() {
    return gulp.src('./assets/scss/admin.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('admin.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile desktop SCSS file
module.exports.desktop = function desktopTask() {
    return gulp.src('./assets/scss/desktop.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('desktop.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};

// Task to compile deprecated SCSS file
module.exports.deprecated = function deprecatedTask() {
    return gulp.src('./assets/scss/deprecated.scss')
        .pipe(plumber())
        .pipe(nativeSass())
        .pipe(rename('deprecated.min.css')) // Rename to add .min
        .pipe(postcss(postProcessors))
        .pipe(gulp.dest('./assets/css/'))
        .pipe(notify({ message: config.messages.css }));
};