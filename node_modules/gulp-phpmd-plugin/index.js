var gutil = require('gulp-util')
var through = require('through2')
var exec = require('child_process').exec

/**
 * Builds shell command for PHPMD according to specified options.
 */
var buildCommand = function(file, options) {
  options = options || {}
  var command = (options.bin || 'phpmd') + ' ' + file.path

  if (options.hasOwnProperty('format')) {
    command += ' ' + options.format + ' '
  }

  if (options.hasOwnProperty('ruleset')) {
    command += ' ' + options.ruleset + ' '
  }

  if (options.hasOwnProperty('minimumpriority')) {
    command += ' --minimumpriority="' + options.minimumpriority + '"'
  }

  if (options.hasOwnProperty('strict')) {
    command += ' --strict'
  }

  return command
}

var phpmdPlugin = function(options) {
  return through.obj(function(file, enc, callback) {
    var stream = this

    if (file.isNull()) {
      stream.push(file)
      callback()

      return
    }

    if (file.isStream()) {
      stream.emit('error', new gutil.PluginError('gulp-phpmd', 'Streams are not supported'))
      callback()

      return
    }

    // Run PHPMD
    var phpmd = exec(buildCommand(file, options), function(error, stdout, stderr) {

      var report = {
        error: false,
        output: ''
      }

      if (error) {
        // Something went wrong. Attach report to the file to allow
        // reporters do their job.
        report.error = error
        report.output = stdout
      }

      file.phpmdReport = report
      stream.push(file)
      callback()
    })
  })
}

// Attach reporters loader to the plugin.
phpmdPlugin.reporter = require('./reporters')

module.exports = phpmdPlugin
