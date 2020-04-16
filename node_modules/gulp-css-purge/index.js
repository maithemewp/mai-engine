'use strict';

var through = require('through2');
var cssPurge = require('css-purge');
var gutil = require('gulp-util');
var PluginError = gutil.PluginError;

const PLUGIN_NAME = 'gulp-css-purge';

var gulpCSSPurge = function(options) {

  function purgedStream(modifiedCSS) {

    return through().write(modifiedCSS);
  }

  return through.obj(function(file, encoding, callback){
    
    if (options !== undefined && (options.reduceConfig !== undefined || options.reduceConfig !== null)) {
      delete options.reduceConfig;
    }

    if (file.isNull()) {
      return callback(null, file);
    }

    if (file.isStream()) {
      var fileContents = file.contents ? file.contents.toString() : '';
      cssPurge.purgeCSS(fileContents, options, function(error, results){

        if (error) {
          return callback(new gutil.PluginError(PLUGIN_NAME, error));
        }

        file.contents = file.contents.pipe(purgedStream(results));
        callback(null, file);
      });


    } else if (file.isBuffer()) {

      var fileContents = file.contents ? file.contents.toString() : '';

      //default options
      if (options === null || options === undefined) {
        options = {
          "trim" : true,

          "shorten" : true,

          "format_font_family": true

        };
      }

      try {
        cssPurge.purgeCSS(fileContents, options, function(error, results){

          if (error) {
            return callback(new gutil.PluginError(PLUGIN_NAME, error));
          }
          file.contents = new Buffer(results);
          callback(null, file);
        });
      } catch (error) {
        return callback(new gutil.PluginError(PLUGIN_NAME, error));
      }


    }

  });
};

module.exports = gulpCSSPurge;