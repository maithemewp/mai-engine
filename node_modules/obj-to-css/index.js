'use strict'

module.exports = function toCSS (obj = {}) {
  if (!obj || typeof obj !== 'object') {
    throw new Error('Invalid object provided')
  }

  const selectors = Object.keys(obj)
  return selectors
    .map(selector => {
      const definition = obj[selector]
      const rules = Object.keys(definition).map(rule => `${rule}:${definition[rule]}`).join(';')
      return `${selector}{${rules}}`
    })
    .join('')
}
