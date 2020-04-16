var test = require('tape')
var fileSort = require('./')

test('file-sort', function(t) {
  var files = [
      'a/world'
    , 'a/lib/index.js'
    , 'b/package.json'
    , 'b/lib/3/index.js'
    , 'b/lib/2/README.js'
    , 'a/hello'
    , 'b/lib/2/index.js'
    , 'a/lib/README.md'
    , 'b/lib/3/README.js'
    , 'c'
  ]

  t.deepEqual(fileSort(files), [
      'a/hello'
    , 'a/world'
    , 'a/lib/index.js'
    , 'a/lib/README.md'
    , 'b/package.json'
    , 'b/lib/2/index.js'
    , 'b/lib/2/README.js'
    , 'b/lib/3/index.js'
    , 'b/lib/3/README.js'
    , 'c'
  ])
  t.end()
})
