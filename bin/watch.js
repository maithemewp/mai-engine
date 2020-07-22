'use strict';

const gulp   = require( 'gulp' ),
	  config = require( './config' );

module.exports = function() {
	gulp.watch( config.src.scss, [ 'build:css' ] );
};
