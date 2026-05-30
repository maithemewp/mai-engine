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
			// mergeRules has a bug where it can rewrite
			// `[data-content-align="start"] > .wp-block-cover__inner-container > .wp-block[data-align="full"]`
			// into bare `.wp-block[data-align="full"]` when another rule shares an !important
			// margin declaration, which then over-applies to every alignfull block in the canvas.
			mergeRules: false,
			// convertValues strips units from zero values (`0px` -> `0`). That's safe
			// for normal declarations, but INVALID inside an `@property` descriptor:
			// `@property --x { syntax: "<length>"; initial-value: 0; }` fails to register
			// because a `<length>` initial-value requires an explicit unit (`0px`). A failed
			// registration silently un-registers the property, breaking our CSS-only
			// --scrollbar-width detection. Disable so `initial-value: 0px` survives intact.
			convertValues: false,
		},
	},
};
