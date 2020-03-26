process.env.DISABLE_NOTIFIER = true;

const gulp        = require( 'gulp' );
const toolkit     = require( './bin' );
const packageJson = require( './package' );

toolkit.extendConfig(
	{
		theme: {
			name: packageJson.name,
			textdomain: packageJson.name,
			version: packageJson.version
		},
		lintfiles: {
			phpcs: './phpcs.xml',
			phpmd: './phpmd.xml',
			eslint: './.eslintrc',
			stylelint: './.stylelintrc',
			scss: './.stylelintscssrc',
			csscomb: './.csscomb.json',
		},
		bump: {
			files: [
				'./package.json',
				'./composer.json'
			]
		},
		messages: {
			css: 'Stylesheet compiled and saved: <%= file.relative %>',
			i18n: 'Translation file generated.',
			images: 'Image files compressed and copied: <%= file.relative %>',
			js: 'JavaScript task complete: <%= file.relative %>',
			potomo: 'PO files converted to MO files.',
			styleguide: 'Styleguide task complete.'
		},
		src: {
			base: './',
			php: [ '**/*.php', '!vendor/**' ],
			images: './assets/img/**/*',
			scss: [ './assets/scss/**/*.scss', './config/**/*.scss' ],
			css: [ './assets/css/**/*.css', '!./assets/css/min/*.css', '!node_modules/**' ],
			js: [ './assets/js/**/*.js', '!node_modules/**' ],
			json: [ '**/*.json', '!node_modules/**' ],
			i18n: './assets/lang/',
			svg: [
				'./node_modules/@fortawesome/fontawesome-free/svgs/regular',
				'./node_modules/@fortawesome/fontawesome-free/svgs/solid'
			]
		},
		dest: {
			i18npo: './assets/lang/',
			i18nmo: './assets/lang/',
			images: './assets/img/',
			js: './assets/js/min/',
			svg: './assets/svg/'
		},
		js: {
			'admin': [
				'./assets/js/admin.js',
			],
			'customizer': [
				'./assets/js/customizer.js',
			],
			'global': [
				'./assets/js/global.js',
			],
			'header': [
				'./assets/js/header.js',
			],
			'menus': [
				'./assets/js/menus.js',
			]
		},
		css: {
			basefontsize: 10,
			sourcemaps: false,
			remmediaquery: false,
			remreplace: true,
			cssnano: {
				discardComments: {
					removeAll: true
				},
				zindex: false
			},
		},
	}
);

toolkit.extendTasks( gulp, {} );
