# path-sort [![stable](http://hughsk.github.io/stability-badges/dist/stable.svg)](http://github.com/hughsk/stability-badges) #

Sort a list of file/directory paths, such that something like this:

``` javascript
[
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
```

Becomes something like this:

``` javascript
[
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
]
```

## Installation ##

``` bash
npm install path-sort
```

## Usage ##

### `require('path-sort')(files[, sep])` ###

Takes an array of `filenames` with an optional delimiter (`sep`), returning a
sorted copy.

### `require('path-sort').standalone([sep])` ###

Returns a `Array.prototype.sort`-friendly method. It's a little slower but
easier to use in some cases.

``` javascript
var sorter = require('path-sort').standalone('/')

array = array.sort(sorter)
```
