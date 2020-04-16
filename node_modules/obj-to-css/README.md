<h1 align="center">obj-to-css</h1>
<div align="center">
  <strong>Convert JS object to CSS declarations</strong>
</div>
<br>
<div align="center">
  <a href="https://npmjs.org/package/obj-to-css">
    <img src="https://img.shields.io/npm/v/obj-to-css.svg?style=flat-square" alt="Package version" />
  </a>
  <a href="https://npmjs.org/package/obj-to-css">
  <img src="https://img.shields.io/npm/dm/obj-to-css.svg?style=flat-square" alt="Downloads" />
  </a>
  <a href="https://github.com/feross/standard">
    <img src="https://img.shields.io/badge/code%20style-standard-brightgreen.svg?style=flat-square" alt="Standard" />
  </a>
  <a href="https://travis-ci.org/tiaanduplessis/obj-to-css">
    <img src="https://img.shields.io/travis/tiaanduplessis/obj-to-css.svg?style=flat-square" alt="Travis Build" />
  </a>
  <a href="https://github.com/RichardLitt/standard-readme)">
    <img src="https://img.shields.io/badge/standard--readme-OK-green.svg?style=flat-square" alt="Standard Readme" />
  </a>
  <a href="https://badge.fury.io/gh/tiaanduplessis%2Fobj-to-css">
    <img src="https://badge.fury.io/gh/tiaanduplessis%2Fobj-to-css.svg?style=flat-square" alt="GitHub version" />
  </a>
  <a href="https://dependencyci.com/github/tiaanduplessis/obj-to-css">
    <img src="https://dependencyci.com/github/tiaanduplessis/obj-to-css/badge?style=flat-square" alt="Dependency CI" />
  </a>
  <a href="https://github.com/tiaanduplessis/obj-to-css/blob/master/LICENSE">
    <img src="https://img.shields.io/npm/l/obj-to-css.svg?style=flat-square" alt="License" />
  </a>
  <a href="http://makeapullrequest.com">
    <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat-square" alt="PRs" />
  </a>
  <a href="https://www.paypal.me/tiaanduplessis/1">
    <img src="https://img.shields.io/badge/$-support-green.svg?style=flat-square" alt="Donate" />
  </a>
</div>
<br>
<div align="center">
  <a href="https://github.com/tiaanduplessis/obj-to-css/watchers">
    <img src="https://img.shields.io/github/watchers/tiaanduplessis/obj-to-css.svg?style=social" alt="Github Watch Badge" />
  </a>
  <a href="https://github.com/tiaanduplessis/obj-to-css/stargazers">
    <img src="https://img.shields.io/github/stars/tiaanduplessis/obj-to-css.svg?style=social" alt="Github Star Badge" />
  </a>
  <a href="https://twitter.com/intent/tweet?text=Check%20out%20obj-to-css!%20https://github.com/tiaanduplessis/obj-to-css%20%F0%9F%91%8D">
    <img src="https://img.shields.io/twitter/url/https/github.com/tiaanduplessis/obj-to-css.svg?style=social" alt="Tweet" />
  </a>
</div>
<br>
<div align="center">
  Built with ❤︎ by <a href="tiaan.beer">Tiaan</a> and <a href="https://github.com/tiaanduplessis/obj-to-css/graphs/contributors">contributors</a>
</div>

<h2>Table of Contents</h2>
<details>
  <summary>Table of Contents</summary>
  <li><a href="#about">About</a></li>
  <li><a href="#install">Install</a></li>
  <li><a href="#usage">Usage</a></li>
  <li><a href="#cli">CLI</a></li>
  <li><a href="#contribute">Contribute</a></li>
  <li><a href="#license">License</a></li>
</details>

## Install

```sh
$ npm install --save obj-to-css
# OR
$ yarn add obj-to-css
```

## Usage

```js

const ToCss = require('obj-to-css')

const obj = {
  '.hello': {
    color: 'red'
  },
  '.foo': {
    background: 'pink'
  }
}

console.log(toCSS(obj)) // .hello{color:red}.foo{background:pink}

```

## CLI

```sh
$ obj-to-css foo.json
# .hello{color:red}.foo{background:pink}
```

## Contribute

Contributions are welcome. Please open up an issue or create PR if you would like to help out.

Note: If editing the README, please conform to the [standard-readme](https://github.com/RichardLitt/standard-readme) specification.

## License

Licensed under the MIT License.
