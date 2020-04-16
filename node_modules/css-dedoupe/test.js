/* eslint-env jest */
'use strict'

const dedoupe = require('./')

test('should export function', () => {
  expect(dedoupe).toBeDefined()
  expect(typeof dedoupe).toBe('function')
})

test('should remove duplicate', () => {
  expect(dedoupe('.float-right {float: right;}.float-right {float: right;}')).toBe(
    '.float-right{float:right}'
  )
})
