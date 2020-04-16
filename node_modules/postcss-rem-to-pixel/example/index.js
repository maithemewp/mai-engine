'use strict';
/* eslint-disable */

var fs = require('fs');
var postcss = require('postcss');
var remToPx = require('..');
var css = fs.readFileSync('main.css', 'utf8');
var options = {
    replace: false
};
var processedCss = postcss(remToPx(options)).process(css).css;

fs.writeFile('main-px.css', processedCss, function (err) {
  if (err) {
    throw err;
  }
  console.log('px file written.');
});

