'use strict'
const css = require('css')
const ToCSS = require('obj-to-css')

module.exports = function cssDedoupe (content = '') {
  const other = []
  const declarations = []
  const parsed = css.parse(content)

  parsed.stylesheet.rules.forEach(current => {
    const value = {}
    const selector = current.selectors.join(',')

    // Ignore non-rule types
    if (current.type !== 'rule') {
      other.push(current)
      return
    }

    current.declarations.forEach(rule => {
      value[rule.property] = rule.value
    })

    declarations.push({ selector, value })
  })

  const newDeclartions = declarations.reduce((acc, current) => {
    if (acc[current.selector]) {
      const newProps = Object.assign({}, acc[current.selector], current.value)
      return Object.assign({}, acc, { [current.selector]: newProps })
    }

    return Object.assign({}, acc, { [current.selector]: current.value })
  }, {})

  parsed.stylesheet.rules = other
  return `${ToCSS(newDeclartions)}${css.stringify(parsed, { compress: true })}`
}
