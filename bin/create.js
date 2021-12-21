'use strict';

const fs           = require( 'fs' ),
	  argv         = require( 'yargs' ).argv,
	  readline     = require( 'readline' ),
	  childProcess = require( 'child_process' );

const styleCss = function( theme ) {
	return `/**
 * Theme Name:       ${theme.name}
 * Theme URI:        https://bizbudding.com/mai-theme
 * Description:      ${theme.name} child theme for the Genesis Framework.
 * Author:           BizBudding
 * Author URI:       https://bizbudding.com/
 * Version:          2.0.0
 * Text Domain:      ${theme.slug}
 * Template:         genesis
 * Template Version: ${theme.templateVersion}
 * License:          GPL-2.0-or-later
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.html
 */
`;
};

const functionsPhp = function( theme ) {
	return `<?php
/**
 * ${theme.name} theme.
 *
 * @package   ${theme.namespace}
 * @link      https://bizbudding.com/
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

require_once __DIR__ . '/vendor/autoload.php';

/**********************************
 * Add your customizations below! *
 **********************************/
`;
};

const composerJson = function( theme ) {
	return `{
    "name": "maithemewp/${theme.slug}",
    "authors": [
        {
            "name": "BizBudding",
            "email": "team@bizbudding.com"
        }
    ],
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/maithemewp/mai-installer"
        }
    ],
    "require": {
        "afragen/wp-dependency-installer": "^4.2"
        "maithemewp/mai-installer": "dev-master",
    },
    "config": {
        "preferred-install": {
            "*": "dist"
        },
        "sort-order": true
    },
    "minimum-stability": "dev"
}`;
};

const gitIgnore = function() {
	return `*.lock
LICENSE
vendor/**/*.md
vendor/**/*.json
*test*
!vendor
`;
};

const configPhp = function() {
	return `<?php
/**
 * Mai Engine.
 *
 * @package   BizBudding\\MaiEngine
 * @link      https://bizbudding.com
 * @author    BizBudding
 * @copyright Copyright © 2020 BizBudding
 * @license   GPL-2.0-or-later
 */

return [];
`;
};

const themeScss = function() {
	return ``;
};

const genesisVersion = function() {
	let version = '3.3.5';

	readline.createInterface( {
		input: fs.createReadStream( '../../themes/genesis/style.css' ),
		terminal: false
	} ).on( 'line', function( line ) {
		if ( line.includes( 'Version: ' ) ) {
			version = line.replace( 'Version: ', '' );
		}
	} );

	return version;
};

module.exports = function() {
	const ansiColors = {
		red: '\x1b[31m',
		green: '\x1b[32m',
		blue: '\x1b[36m',
	};

	if ( ! ( 'name' in argv ) ) {
		return console.log( ansiColors.red, 'Error: --name argument required.' );
	}

	let composerInstall = false;

	if ( 'composer' in argv ) {
		composerInstall = argv.composer;
	}

	const capitalized = argv.name.charAt( 0 ).toUpperCase() + argv.name.slice( 1 );

	const theme = {
		name: `Mai ${capitalized}`,
		namespace: `BizBudding\\Mai${capitalized}`,
		slug: `mai-${argv.name}`,
		dir: `../../themes/mai-${argv.name}`,
		config: `./config/${argv.name}.php`,
		scss: `./assets/scss/themes/${argv.name}.scss`,
		templateVersion: genesisVersion(),
	};

	if ( ! fs.existsSync( theme.config ) ) {
		fs.writeFileSync( theme.config, configPhp() );
		console.log( ansiColors.green, 'Success: Created theme PHP config file.' );
	}

	if ( ! fs.existsSync( theme.scss ) ) {
		fs.writeFileSync( theme.scss, themeScss() );
		console.log( ansiColors.green, 'Success: Created theme SCSS file.' );
	}

	if ( fs.existsSync( theme.dir ) ) {
		return console.log( ansiColors.red, `Error: "${theme.dir}" directory already exists.` );
	}

	if ( ! fs.existsSync( theme.dir ) ) {
		fs.mkdirSync( theme.dir );
		fs.writeFileSync( theme.dir + '/style.css', styleCss( theme ) );
		fs.writeFileSync( theme.dir + '/functions.php', functionsPhp( theme ) );
		fs.writeFileSync( theme.dir + '/composer.json', composerJson( theme ) );
		fs.writeFileSync( theme.dir + '/.gitignore', gitIgnore() );
		fs.copyFileSync( './assets/img/screenshot.png', theme.dir + '/screenshot.png' );

		console.log( ansiColors.green, 'Success: Created theme directory and files.' );

		if ( composerInstall ) {
			console.log( ansiColors.blue, 'Running composer install...' );

			process.chdir( theme.dir );
			childProcess.exec( `composer install` );

			console.log( ansiColors.green, 'Success: Installed composer packages.' );
		}

		console.log( ansiColors.green, `Success: Finished creating ${theme.name} theme.` );
	}
};
