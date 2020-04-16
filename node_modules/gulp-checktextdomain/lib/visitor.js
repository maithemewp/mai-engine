/**
 * Generic helper for visiting every node
 */
function visit(node, cb) {
  if (node.kind) cb(node);
  for(var k in node) {
    var inner = node[k];
    if (inner) {
      if (inner.kind) {
        visit(inner, cb);
      } else if (Array.isArray(inner)) {
        inner.forEach(function(child) {
          visit(child, cb);
        });
      }
    }
  }
}
/**
 * Reads a PHP source and retrieves a list calls
 */
module.exports = function(ast, fn) {
  var extract = [];
  // we extract here each matching call
  visit(
    ast,
    function(node) {
      if (node.kind === 'call') {
        if (node.what && node.what.kind === 'identifier') {
          if (fn.indexOf(node.what.name) > -1) {
            extract.push(node);
          }
        }
        // @todo handle functions alias with `use` keywords
      }
    }
  );
  return extract;
};
