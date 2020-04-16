/* eslint-env jest */
'use strict'

const toCSS = require('./')

test('should export function', () => {
  expect(toCSS).toBeDefined()
  expect(typeof toCSS).toBe('function')
})

test('should convert object to CSS string', () => {
  const obj = {
    '.hello': {
      color: 'red'
    },
    '.foo': {
      background: 'pink'
    }
  }

  expect(toCSS()).toBe('')
  expect(toCSS(obj)).toBe('.hello{color:red}.foo{background:pink}')
})
