#!/usr/bin/env node
'use strict'

const fs = require('fs')
const input = process.argv[2]
const toCSS = require('./')

fs.readFile(input, (error, data) => {
  if (error) {
    throw error
  }

  try {
    const json = JSON.parse(data.toString())
    const css = toCSS(json)

    console.log(css)
  } catch (error) {
    throw error
  }
})
