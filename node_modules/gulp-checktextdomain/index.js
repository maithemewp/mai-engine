/*
 * gulp-checktextdomain
 * https://github.com/felixzapata/gulp-checktextdomain
 *
 * Copyright (c) 2016 Félix Zapata
 * Licensed under the ISC license.
 */

'use strict';

var fs = require('fs-extra');
var path = require('path');
var chalk = require('chalk');
var R = require('ramda');
var through = require('through2');
var table = require('text-table');
var PluginError = require('plugin-error');
var Parser = require('php-parser');
var visitor = require('./lib/visitor');
var writer = require('./lib/writer');

var PLUGIN_NAME = 'gulp-checktextdomain';


function fileExists(filePath) {
  try {
    return fs.statSync(filePath).isFile();
  } catch (err) {
    return false;
  }
}

function gulpCheckTextDomain(customOptions, cb) {

  var defaultOptions = {
    keywords: false,
    text_domain: false,
    report_missing: true,
    report_success: false,
    report_variable_domain: true,
    correct_domain: false,
    create_report_file: false,
    force: false
  };


  var options = customOptions ? R.merge(defaultOptions, customOptions) : defaultOptions;
  var errors = [];
  var functions = []; //Array of gettext functions
  var func_domain = {}; //Map of gettext function => ordinal number of domain argument
  var patt = new RegExp('([0-9]+)d', 'i');	//Check for domain identifier in keyword specification

  function bufferContents(file, enc, cb) {

    /* jshint validthis: true */

    // ignore empty files
    if (file.isNull()) {
      cb(null, file);
      return;
    }

    if (file.isStream()) {
      this.emit('error', new PluginError(PLUGIN_NAME, 'Streaming not supported'));
      cb();
      return;
    }

    if (options.text_domain === false) {
      this.emit('error', new PluginError(PLUGIN_NAME, 'Text domain not provided.'));
      cb();
      return;
    }

    //Cast text_domain as an array to support multiple text domains
    options.text_domain = (options.text_domain instanceof Array) ? options.text_domain : [options.text_domain];

    //correct_domain can only be used if one domain is specified:
    options.correct_domain = options.correct_domain && (options.text_domain.length === 1);

    if (options.keywords === false) {
      this.emit('error', new PluginError(PLUGIN_NAME, 'No keywords specified.'));
      cb();
      return;
    }

    options.keywords.forEach(function(keyword) {

      //parts[0] is keyword name, e.g. __ or _x
      var parts = keyword.split(':');
      var name = parts[0];
      var argument = 0;

      //keyword argument identifiers
      if (parts.length > 1) {
        var args = parts[1];
        var arg_parts = args.split(',');

        for (var j = 0; j < arg_parts.length; j++) {

          //check for domain identifier
          if (patt.test(arg_parts[j])) {
            argument = parseInt(patt.exec(arg_parts[j]), 10);
            break;
          }
        }

        //No domain identifier found, assume it is #ags + 1
        argument = argument ? argument : arg_parts.length + 1;

        //keyword has no argument identifiers -- assume text domain is 2nd argument
      } else {
        argument = 2;
      }

      func_domain[name] = argument;
      functions.push(name);
    });

    var all_errors = {};
    var error_num = 0;


    // read file, if it exists
    var filename = path.basename(file.path);
    var dirname = path.dirname(file.path);

    if (!fileExists(file.path)) {
      this.emit('error', new PluginError(PLUGIN_NAME, 'Source file "' + file.path + '" not found.'));
      cb();
      return;
    }

    var reader = new Parser({
      parser: {
        // does not extract docs
        extractDoc: false,
        // do not fail if PHP syntax is broken
        suppressErrors: true
      },
      ast: {
        // got line and col on every node
        withPositions: true
      },
      lexer: {
        // accept <? ...
        short_tags: true,
        // accept <%= (old php4 syntax)
        asp_tags: true
      }
    });

    // get every gettext call
    var corrections = [];
    var ast = reader.parseCode(file.contents.toString(), file.path);
    var calls = visitor(ast, functions);
    calls.forEach(function(call) {
      var domainOffset = func_domain[call.what.name] - 1;
      if (call.arguments.length > domainOffset) {
        var arg = call.arguments[domainOffset];
        // bad domain type
        var domName = -1;
        // check argument type
        if (arg.kind === 'string') {
          // check the domain
          domName = arg.value;
        } else if (arg.kind === 'constref') {
          // check a constant name
          domName = arg.name.name;
        }
        // bad domain contents
        if (options.text_domain.indexOf(domName) === -1) {
          errors.push({
            name: call.what.name,
            line: call.loc.start.line,
            domain: domName,
            argument: domainOffset
          });
          if (options.correct_domain) {
            // try to correct it
            corrections.push([
              arg, '\'' + options.text_domain[0] + '\''
            ]);
          }
        }
      } else if (options.report_missing) {
        // argument not found
        errors.push({
          name: call.what.name,
          line: call.loc.start.line,
          domain: false,
          argument: 0
        });
      }
    });

    //Output errors
    if (errors.length > 0) {

      console.log('\n' + chalk.bold.underline(dirname + path.sep + filename));

      var rows = [], error_line, func, message;
      for (var i = 0, len = errors.length; i < len; i++) {

        error_line = chalk.yellow('[L' + errors[i].line + ']');
        func = chalk.cyan(errors[i].name);

        if (!errors[i].domain) {
          message = chalk.red('Missing text domain');

        } else if (errors[i].domain === -1) {
          message = chalk.red('Variable used in domain argument');

        } else {
          message = chalk.red('Incorrect text domain used ("' + errors[i].domain + '")');
        }

        rows.push([error_line, func, message]);
        error_num++;
      }

      console.log(table(rows));

      if (corrections.length > 0) {
        writer(file.path, file.contents.toString(), corrections);
        console.log(chalk.bold(dirname + path.sep + filename + ' corrected.'));
      }
    }

    all_errors[file.path] = errors;

    //Reset errors
    errors = [];


    if (options.create_report_file) {
      fs.writeFileSync('.' + path.basename(file.path, path.extname(file.path)) + '.json', JSON.stringify(all_errors));
    }

    if (error_num > 0 && !options.force) {
      console.log(error_num + ' problem' + (error_num === 1 ? '' : 's'));
    } else if (error_num > 0) {
      console.log("\n" + chalk.red.bold('✖ ' + error_num + ' problem' + (error_num === 1 ? '' : 's')));
    } else if (options.report_success) {
      console.log("\n" + chalk.green.bold('✔ No problems') + "\n");
    }

    this.push(file);

    cb();


  }

  return through.obj(bufferContents, cb);

}

// Exporting the plugin main function
module.exports = gulpCheckTextDomain;
