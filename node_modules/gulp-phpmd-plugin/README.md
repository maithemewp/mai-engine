# gulp-phpmd

This is a fork of [gulp-phpmd](https://github.com/kid-icarus/gulp-phpmd) created because the original wasn't functioning.

> A gulp plugin for running [PHP Mess Detector](https://github.com/phpmd/phpmd).

Derivative work of Dmitriy S. Simushev's [gulp-phpcs](https://github.com/JustBlackBird/gulp-phpcs)
##Requirements
 - [PHP Mess Detector](https://github.com/phpmd/phpmd)

##Installation
```shell
npm install gulp-phpmd-plugin --save-dev
```

## Usage

```js
var gulp = require('gulp');
var phpmd = require('gulp-phpmd');

gulp.task('default', function () {
    return gulp.src(['src/**/*.php', '!src/vendor/**/*.*'])
        // Validate code using PHP Mess Detector
        .pipe(phpmd({
            bin: 'src/vendor/bin/phpmd',
            format: 'text',
        }))
        // Log all problems that was found
        .pipe(phpmd.reporter('log'))
        // Fail if there is an error
        .pipe(phpmd.reporter('fail'))
});
```


## API

### phpmd(options)

#### options.bin

Type: `String`

Default: `'phpmd'`

PHP Mess Detector executable.

#### options.ruleset

Type: `String`

The format of the report, for multiple formats just use a comma separated
string.

#### options.ruleset

Type: `String`

The ruleset to check against

#### options.minimumpriority

Type: `String`

pass --mininumpriority to phpmd

#### options.strict

Type: `String`

pass --strict to phpmd

### phpmd.reporter(name)

Loads one of the reporters that shipped with the plugin (see below).

#### name

Type: `String`

The name of the reporter that should be loaded.


## Reporters
The plugin only passes files through PHPMD. To process the results of
the check one should use a reporter. Reporters are plugins too, so one can pipe
a files stream to them. Several reporters can be used on a stream, just like
any other plugins.

These reporters are shipped with the plugin:

1. Fail reporter - fails if a problem was found. Use `phpmd.reporter('fail')`
to load it.

2. Log reporter - outputs all problems to the console. Use
`phpmd.reporter('log')` to load it.


## License

[MIT](http://opensource.org/licenses/MIT) © Ryan Kois
