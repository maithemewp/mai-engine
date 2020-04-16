var gutil = require('gulp-util');
var through = require('through2');
var chalk = require('chalk');

/**
 * Returns "fail" reporter.
 *
 * The "fail" reporter rises an error on files stream if PHP Mess Detector fails
 * for at least one file.
 *
 * @returns {Function}
 */
module.exports = function() {
  return through.obj(function(file, enc, callback) {
    var report = file.phpmdReport || {};

    if (report.error) {
      var errorMessage = 'PHP Mess Detector failed' +
        ' on ' + chalk.magenta(file.path);

      this.emit('error', new gutil.PluginError('gulp-phpmd', errorMessage));
      callback();

      return;
    }

    this.push(file);
    callback();
  });
}
