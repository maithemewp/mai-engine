

![gulp and CSS-PURGE](https://raw.githubusercontent.com/rbtech/css-purge/master/assets/images/gulp-css-purge.png)


# Gulp CSS-PURGE

Purges duplicate CSS rules and more. Based on [css-purge](https://www.npmjs.org/package/css-purge).


## You have an issue?

This is a simple [gulp](https://github.com/gulpjs/gulp) plugin, which means it's a thin wrapper around `css-purge`. If you are having CSS issues, please contact [css-purge](https://github.com/rbtech/css-purge/issues). Please only create a new issue if it looks like you're having a problem with the gulp plugin.


## Install
```
npm install gulp-css-purge --save-dev
```

### Options

Visit the [CSS-PURGE website](http://rbtech.github.io/css-purge)


## Example 1 - Multiple CSS files merged into single CSS file - [Full Working Example](https://github.com/rbtech/gulp-css-purge-example)
    var gulp = require('gulp'),
        concat = require('gulp-concat'),
        purge = require('gulp-css-purge');

    gulp.task('default', function() {
      return gulp
            .src(['./**/*.css']) //input css
            .pipe(concat('main.css')) //merge into single css file - remove if you want to process output into separate files
            .pipe(purge({
                trim : true,
                shorten : true,
                verbose : true
            }))
            .pipe(gulp.dest('build/css')) //output folder
    });

## Example 2 - SASS
    var gulp = require('gulp'),
        sass = require('gulp-ruby-sass'),
        purge = require('gulp-css-purge'),
        minify = require('gulp-minify-css');

    gulp.task('default', function() {
      gulp.src(['./**/*.sass'])
        .pipe(sass())
        .pipe(purge())
        .pipe(gulp.dest('./public'));
    })



License
-----

(The MIT License)

Copyright (c) 2017 [Red Blueprint Technologies](http://redblueprint.com)

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
