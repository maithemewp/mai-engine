<div align="center">
	<img width="30%" src="media/funnel.png" alt="">
</div>
<h1 align="center">css-dedoupe</h1>
<div align="center">
  <strong>Remove duplicate properties and declarations from your CSS</strong>
</div>
<br>
<div align="center">
  <a href="https://npmjs.org/package/css-dedoupe">
    <img src="https://img.shields.io/npm/v/css-dedoupe.svg?style=flat-square" alt="Package version" />
  </a>
  <a href="https://npmjs.org/package/css-dedoupe">
  <img src="https://img.shields.io/npm/dm/css-dedoupe.svg?style=flat-square" alt="Downloads" />
  </a>
  <a href="https://github.com/feross/standard">
    <img src="https://img.shields.io/badge/code%20style-standard-brightgreen.svg?style=flat-square" alt="Standard" />
  </a>
  <a href="https://travis-ci.org/tiaanduplessis/css-dedoupe">
    <img src="https://img.shields.io/travis/tiaanduplessis/css-dedoupe.svg?style=flat-square" alt="Travis Build" />
  </a>
  <a href="https://github.com/RichardLitt/standard-readme)">
    <img src="https://img.shields.io/badge/standard--readme-OK-green.svg?style=flat-square" alt="Standard Readme" />
  </a>
  <a href="https://badge.fury.io/gh/tiaanduplessis%2Fcss-dedoupe">
    <img src="https://badge.fury.io/gh/tiaanduplessis%2Fcss-dedoupe.svg?style=flat-square" alt="GitHub version" />
  </a>
  <a href="https://dependencyci.com/github/tiaanduplessis/css-dedoupe">
    <img src="https://dependencyci.com/github/tiaanduplessis/css-dedoupe/badge?style=flat-square" alt="Dependency CI" />
  </a>
  <a href="https://github.com/tiaanduplessis/css-dedoupe/blob/master/LICENSE">
    <img src="https://img.shields.io/npm/l/css-dedoupe.svg?style=flat-square" alt="License" />
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
  <a href="https://github.com/tiaanduplessis/css-dedoupe/watchers">
    <img src="https://img.shields.io/github/watchers/tiaanduplessis/css-dedoupe.svg?style=social" alt="Github Watch Badge" />
  </a>
  <a href="https://github.com/tiaanduplessis/css-dedoupe/stargazers">
    <img src="https://img.shields.io/github/stars/tiaanduplessis/css-dedoupe.svg?style=social" alt="Github Star Badge" />
  </a>
  <a href="https://twitter.com/intent/tweet?text=Check%20out%20css-dedoupe!%20https://github.com/tiaanduplessis/css-dedoupe%20%F0%9F%91%8D">
    <img src="https://img.shields.io/twitter/url/https/github.com/tiaanduplessis/css-dedoupe.svg?style=social" alt="Tweet" />
  </a>
</div>
<br>
<div align="center">
  Built with ❤︎ by <a href="tiaan.beer">Tiaan</a> and <a href="https://github.com/tiaanduplessis/css-dedoupe/graphs/contributors">contributors</a>
</div>

<h2>Table of Contents</h2>
<details>
  <summary>Table of Contents</summary>
  <li><a href="#about">About</a></li>
  <li><a href="#install">Install</a></li>
  <li><a href="#usage">Usage</a></li>
  <li><a href="#cli">CLI</a></li>
	<li><a href="#issues">Issues</a></li>
  <li><a href="#contribute">Contribute</a></li>
  <li><a href="#license">License</a></li>
</details>

## About

This is a basic module that walks a AST built with [reworkcss](github.com/reworkcss/css) and removes duplicate CSS properties associated with a specific selector. Only keeping the most recent.

## Install

```sh
$ npm install --save css-dedoupe
# OR
$ yarn add css-dedoupe
```

## Usage

```js
const cssDedoupe = require('css-dedoupe')

const cssStr = '.float-right {float: right;}.float-right {float: right;}'
console.log(cssDedoupe(cssStr)) // '.float-right{float:right}'

```

## CLI

```sh
$ css-dedoupe input.css output.css
```

Or if you would like to modify the input file directly:

```sh
$ css-dedoupe inputAndOutput.css
```

## Issues

- Currently only supports to level declartions e.g. does not dedoupe declartions in media queries.
- The module makes no attempt to format the css after dedouping. Use modules like [csscomb](https://github.com/csscomb/csscomb.js) for this.

## Contribute

Contributions are welcome. Please open up an issue or create PR if you would like to help out.

Note: If editing the README, please conform to the [standard-readme](https://github.com/RichardLitt/standard-readme) specification.

## License

Licensed under the MIT License.

<div>Icons made by <a href="http://www.flaticon.com/authors/madebyoliver" title="Madebyoliver">Madebyoliver</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a> is licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0" target="_blank">CC 3.0 BY</a></div>
