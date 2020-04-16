#!/usr/bin/env node
'use strict'

const fs = require('fs')
const cssDedoupe = require('./')
const input = process.argv[2]
const output = process.argv[3] || input

const content = fs.readFileSync(input)
fs.writeFileSync(output, cssDedoupe(content.toString()))
