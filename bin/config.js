'use strict';

module.exports = {
	messages: {
		css: 'Stylesheet compiled and saved: <%= file.relative %>',
		i18n: 'Translation file generated.',
		images: 'Image files compressed and copied: <%= file.relative %>',
		js: 'JavaScript task complete: <%= file.relative %>',
		potomo: 'PO files converted to MO files.'
	},
	src: {
		base: './',
		php: [ '**/*.php', '!vendor/**' ],
		images: './assets/img/**/*',
		scss: [ './assets/scss/**/*.scss' ],
		css: [ './assets/css/**/*.css', '!./assets/css/min/*.css', '!node_modules/**' ],
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
	css: {
		basefontsize: 16,
		sourcemaps: false,
		remmediaquery: false,
		remreplace: true,
		cssnano: {
			discardComments: {
				removeAll: true
			},
			discardUnused: {
				fontFace: false
			},
			zindex: false,
			reduceIdents: false,
		},
	},
};
