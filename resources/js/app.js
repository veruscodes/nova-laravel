import Nova from './nova.js'

window.Vue = require('vue')
window.LaravelNova = require('./mixins/packages')
window.LaravelNovaUtil = require('./util')
window.createNovaApp = config => new Nova(config)
