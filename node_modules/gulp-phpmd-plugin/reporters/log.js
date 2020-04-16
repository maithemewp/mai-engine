var gutil = require('gulp-util'),
    through = require('through2'),
    chalk = require('chalk');

/**
 * Returns "log" reporter.
 *
 * The "log" reporter, according to its name, logs to the console all problems
 * that PHP Mess Detector found.
 *
 * @returns {Function}
 */
module.exports = function() {
    return through.obj(function(file, enc, callback) {
        var report = file.phpmdReport || {};
        if (report.error) {
            var message = 'PHP Mess Detector found a ' + chalk.yellow('problem')
                + ' in ' + chalk.magenta(file.path) + '\n'
                + 'Message:\n    '
                + report.error + '\n    '
                + report.output.replace(/\n/g, '\n    ');
            gutil.log(message);
        }

        this.push(file);
        callback();
    });
}
