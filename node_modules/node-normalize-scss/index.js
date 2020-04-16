var path = require('path');

var normalizeEntryPoint = require.resolve('node-normalize-scss');
var normalizeDir = path.dirname(normalizeEntryPoint);

function includePaths() {
  return normalizeDir;
}

module.exports = {

  includePaths: includePaths(),

  with: function() {
    var paths  = Array.prototype.slice.call(arguments);
    var result = [].concat.apply(includePaths(), paths);
    return result;
  }

};
