/**
 * WPGulp Configuration File
 *
 * 1. Edit the variables as per your project requirements.
 * 2. In paths you can add <<glob or array of globs>>.
 *
 * @package WPGulp
 */

module.exports = {

	// Project options.
	projectURL: 'mai.local', // Local project URL of your already running WordPress site. Could be something like wpgulp.local or localhost:3000 depending upon your local WordPress setup.
	productURL: './', // Theme/Plugin URL. Leave it like it is, since our gulpfile.js lives in the root folder.
	browserAutoOpen: false,
	injectChanges: true,

	// Style options.
	styleSrc: './assets/scss/*.scss', // Path to main .scss file.
	themeStyleSrc: './assets/scss/themes/*.scss', // Path to theme .scss files.
	styleDestination: './assets/css/', // Path to place the compiled CSS file. Default set to root folder.
	themeStyleDestination: './assets/css/themes/', // Path to place the compiled CSS file. Default set to root folder.
	themeDir: './assets/scss/themes/',
	outputStyle: 'compressed', // Available options → 'compact' or 'compressed' or 'nested' or 'expanded'
	errLogToConsole: true,
	precision: 10,

	// JS Vendor options.
	jsVendorSrc: './assets/js/*.js', // Path to JS vendor folder.
	jsVendorDestination: './assets/js/min/', // Path to place the compiled JS vendors file.
	jsVendorFile: 'vendor', // Compiled JS vendors file name. Default set to vendors i.e. vendors.js.

	// JS Custom options.
	jsCustomSrc: './assets/js/*.js', // Path to JS custom scripts folder.
	jsCustomDestination: './assets/js/min/', // Path to place the compiled JS custom scripts file.
	jsCustomFile: 'global', // Compiled JS custom file name. Default set to custom i.e. custom.js.

	// Images options.
	imgSrc: './assets/img/raw/**/*', // Source folder of images which should be optimized and watched. You can also specify types e.g. raw/**.{png,jpg,gif} in the glob.
	imgDST: './assets/img/', // Destination folder of optimized images. Must be different from the imagesSrc folder.

	// Watch files paths.
	watchStyles: './assets/scss/**/*.scss', // Path to all *.scss files inside css folder and inside them.
	watchJsVendor: './assets/js/vendor/*.js', // Path to all vendor JS files.
	watchJsCustom: './assets/js/custom/*.js', // Path to all custom JS files.
	watchPhp: './**/*.php', // Path to all PHP files.

	// Translation options.
	textDomain: 'mai-engine', // Your textdomain here.
	translationFile: 'mai-engine.pot', // Name of the translation file.
	translationDestination: './assets/lang', // Where to save the translation files.
	packageName: 'mai-engine', // Package name.
	bugReport: 'https://maithemewp.com/contact/', // Where can users report bugs.
	lastTranslator: 'Lee Anthony <lee@bizbudding.com>', // Last translator Email ID.
	team: 'Mike Hemberger <mike@bizbudding.com>', // Team's Email ID.

	// Browsers you care about for autoprefixing. Browserlist https://github.com/ai/browserslist
	// The following list is set as per WordPress requirements. Though, Feel free to change.
	BROWSERS_LIST: [
		'last 2 version',
		'> 1%',
		'ie >= 11',
		'last 1 Android versions',
		'last 1 ChromeAndroid versions',
		'last 2 Chrome versions',
		'last 2 Firefox versions',
		'last 2 Safari versions',
		'last 2 iOS versions',
		'last 2 Edge versions',
		'last 2 Opera versions'
	]
};
