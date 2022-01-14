'use strict';

module.exports = {
	'build:admin-css': [ require( './styles' ).admin ],
	'build:blocks-css': [ require( './styles' ).blocks ],
	'build:columns-css': [ require( './styles' ).columns ],
	'build:deprecated-css': [ require( './styles' ).deprecated ],
	'build:desktop-css': [ require( './styles' ).desktop ],
	'build:editor-css': [ require( './styles' ).editor ],
	'build:footer-css': [ require( './styles' ).footer ],
	'build:header-css': [ require( './styles' ).header ],
	'build:page-header-css': [ require( './styles' ).pageheader ],
	'build:main-css': [ require( './styles' ).main ],
	'build:maiplugins-css': [ require( './styles' ).maiplugins ],
	'build:plugin-css': [ require( './styles' ).plugins ],
	'build:theme-css': [ require( './styles' ).themes ],
	'build:utilities-css': [ require( './styles' ).utilities ],

	'build:css': [ [ 'build:admin-css', 'build:blocks-css', 'build:columns-css', 'build:deprecated-css', 'build:desktop-css', 'build:editor-css', 'build:footer-css', 'build:header-css', 'build:page-header-css', 'build:main-css', 'build:maiplugins-css', 'build:plugin-css', 'build:theme-css', 'build:utilities-css' ] ],

	'build:blocks': [ require( './scripts' ).blocks ],
	'build:scripts': [ require( './scripts' ).js ],

	'build:js': [ [ 'build:scripts', 'build:blocks' ] ],

	'build:images': [ require( './images' ).img ],
	'build:svg': [ require( './images' ).svg ],

	'build:img': [ [ 'build:images', 'build:svg' ] ],

	'build:i18n': [ require( './i18n' ) ],

	'build': [ [ 'build:css', 'build:js', 'build:img', 'build:i18n' ] ],

	'create:theme': [ require( './create' ) ],

	'create': [ [ 'create:theme', 'build:css' ] ],

	'watch': [ require( './watch' ) ],
	'default': [ [ 'build', 'watch' ] ],
};
