process.env.DISABLE_NOTIFIER = true;

const gulp = require( 'gulp' );
const toolkit = require( './bin' );
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
			scss: './assets/scss/**/*.scss',
			css: [ '**/*.css', '!node_modules/**' ],
			js: [ './assets/js/**/*.js', '!node_modules/**' ],
			json: [ '**/*.json', '!node_modules/**' ],
			i18n: './assets/lang/'
		},
		dest: {
			i18npo: './assets/lang/',
			i18nmo: './assets/lang/',
			images: './assets/img/',
			js: './assets/js/min/'
		},
		js: {
			'global': [
				'./assets/js/filters.js',
				'./assets/js/header.js',
				'./assets/js/menu.js',
				'./assets/js/scroll.js',
				'./assets/js/toggle.js'
			]
		},
		css: {
			basefontsize: 10,
			remmediaquery: false,
			remreplace: true,
			cssnano: {
				discardComments: {
					removeAll: true
				},
				zindex: false
			},
		},
		server: {
			notify: false,
			proxy: 'https://mai.test',
			host: 'mai.test',
			open: 'external',
			port: '8000',
			files: [
				'./assets/css/*'
			],
			https: {
				'key': '/Users/seothemes/.config/valet/Certificates/mai.test.key',
				'cert': '/Users/seothemes/.config/valet/Certificates/mai.test.crt'
			}
		}
	}
);

toolkit.extendTasks( gulp, {} );
