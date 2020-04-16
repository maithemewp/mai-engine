const toCSS = require('./')

const obj = {
  '.hello': {
    color: 'red'
  },
  '.foo': {
    background: 'pink'
  }
}

console.log(toCSS(obj))
