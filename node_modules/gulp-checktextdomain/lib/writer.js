var fs = require('fs');

module.exports = function(filename, contents, replace) {
  // order replace
  replace.sort(function(a, b) {
    if (a[0].loc.start.offset < b[0].loc.start.offset) {
      return -1;
    } else if (a[0].loc.start.offset > b[0].loc.start.offset) {
      return 1;
    }
    return 0;
  });
  // start to extract parts
  var buffer = '';
  var lastOffset = 0;
  replace.forEach(function(item) {
    buffer += contents.substring(lastOffset, item[0].loc.start.offset);
    buffer += item[1];
    lastOffset = item[0].loc.end.offset;
  });
  if (lastOffset < contents.length) {
    buffer += contents.substring(lastOffset);
  }
  fs.writeFileSync(filename, buffer);
};
