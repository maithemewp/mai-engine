# gulp-checktextdomain

[![Build Status](https://travis-ci.org/felixzapata/gulp-checktextdomain.png)](https://travis-ci.org/felixzapata/gulp-checktextdomain)

[![Package Quality](http://npm.packagequality.com/badge/gulp-checktextdomain.png)](http://npm.packagequality.com/badge/gulp-checktextdomain.png)

A Gulp plugin to checks gettext function calls for missing or incorrect text domain.

Inspired by [grunt-checktextdomain](https://github.com/stephenharris/grunt-checktextdomain).


## Getting Started

```shell
npm install gulp-checktextdomain --save-dev
```


## The "checktextdomain" task

### Important: Before you start

For the task to run you need to specify:

1. **Text domain(s)** - a string or array of valid text domain(s)
2. **Keywords** - gettext functions, along with a specification indicating where to look for the text domain


#### Keyword specifications
This task extends the original [keyword specification](http://www.gnu.org/software/gettext/manual/html_node/xgettext-Invocation.html) to indicate where to look for the text domain. The default specification is of the form

``` 
    [function name]:[argument-specifier],[argument-specifier],...
```
where an argument specificier, `[argument-specifier]`, is of the form

 - `[number]` - indicating that this argument is a translatable string
 - `[number]c` - indicating that this argument is a context specifier


For example:

 - `gettext` - the translated string is the first argument of `gettext()`
 - `ngettext:1,2` -  the translated strings are arguments 1 and 2 of of `ngettext()`
 - `pgettext:1c,2` -  argument 1 is a context specifier and the translated string is argument 2 of `pgettext()`


This task requires an additional argument specifier (in fact this is the only required one): `[number]d` - indicating that the argument is a domain specifier. For example:

 - `__:1,2d` - the translated string is the first argument of `__()` and the domain is the second argument
 - `_n:1,2,4d` -  the translated strings are arguments 1 and 2 of `_n()` and the fourth is the domain specifier.
 - `_nx:1,2,3c,5d` -  the translated strings are arguments 1 and 2 of `_nx()`, the third is a context specifier and the fifth is the domain specifier.


#### Example keyword specifications (WordPress)

```
keywords: [
	'__:1,2d',
	'_e:1,2d',
	'_x:1,2c,3d',
	'esc_html__:1,2d',
	'esc_html_e:1,2d',
	'esc_html_x:1,2c,3d',
	'esc_attr__:1,2d', 
	'esc_attr_e:1,2d', 
	'esc_attr_x:1,2c,3d', 
	'_ex:1,2c,3d',
	'_n:1,2,4d', 
	'_nx:1,2,4c,5d',
	'_n_noop:1,2,3d',
	'_nx_noop:1,2,3c,4d'
];
```

### Options

#### text_domain
Type: `String`|`Array`

Must be provided. A text domain (or an array of text domains) indicating the domains to check against.

#### keywords
Type: `Array`

An array of keyword specifications to look for. See above section for details & examples.

#### report_missing
Type: `Bool`
Default value: `true`

Whether to report use of keywords without a domain being passed.

#### report_success
Type: `Bool`
Default value: `false`

Whether to report a "no problem" message when a file passes validation.

#### report_variable_domain
Type: `Bool`
Default value: `true`

Whether to report use of keywords with a variable being used as the domain.

#### correct_domain
Type: `Bool`
Default value: `false`

Whether to automatically correct incorrect domains. Please note that this does **not** add in missing domains, and can **only** be used when one text domain is supplied. This will also correct instances where a variable, rather than string is used as a text doman, **unless** you set `report_variable_domain` to `false`.

#### create_report_file
Type: `Bool`
Default value: `false`

Create a hidden `.[target].json` file with reported errors.

#### force

Type: `Bool`
Default value: `false`

Set force to true to report text domain errors but not fail the task

### Usage Examples

This is a typical set-up for WordPress development. The only thing specific to WordPress here is the keywords list.

```js
var gulp = require('gulp');
var checktextdomain = require('gulp-checktextdomain');

gulp.task('checktextdomain', function() {
	return gulp
	.src('**/*.php')
	.pipe(checktextdomain({
		text_domain: 'my-domain', //Specify allowed domain(s)
		keywords: [ //List keyword specifications
			'__:1,2d',
			'_e:1,2d',
			'_x:1,2c,3d',
			'esc_html__:1,2d',
			'esc_html_e:1,2d',
			'esc_html_x:1,2c,3d',
			'esc_attr__:1,2d',
			'esc_attr_e:1,2d',
			'esc_attr_x:1,2c,3d',
			'_ex:1,2c,3d',
			'_n:1,2,4d',
			'_nx:1,2,4c,5d',
			'_n_noop:1,2,3d',
			'_nx_noop:1,2,3c,4d'
		],
	}));
});
```


## Release History

Read the [full changelog](CHANGELOG.md).

## License

ISC © [Félix Zapata](http://github.com/felixzapata)
