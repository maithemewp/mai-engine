parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"bLaB":[function(require,module,exports) {
var n=9007199254740991,r="[object Arguments]",t="[object Function]",e="[object GeneratorFunction]",u=/^(?:0|[1-9]\d*)$/;function o(n,r,t){switch(t.length){case 0:return n.call(r);case 1:return n.call(r,t[0]);case 2:return n.call(r,t[0],t[1]);case 3:return n.call(r,t[0],t[1],t[2])}return n.apply(r,t)}function c(n,r){for(var t=-1,e=Array(n);++t<n;)e[t]=r(t);return e}function a(n,r){return function(t){return n(r(t))}}var i=Object.prototype,l=i.hasOwnProperty,f=i.toString,v=i.propertyIsEnumerable,p=a(Object.keys,Object),s=Math.max,y=!v.call({valueOf:1},"valueOf");function h(n,r){var t=S(n)||F(n)?c(n.length,String):[],e=t.length,u=!!e;for(var o in n)!r&&!l.call(n,o)||u&&("length"==o||m(o,e))||t.push(o);return t}function g(n,r,t){var e=n[r];l.call(n,r)&&x(e,t)&&(void 0!==t||r in n)||(n[r]=t)}function b(n){if(!w(n))return p(n);var r=[];for(var t in Object(n))l.call(n,t)&&"constructor"!=t&&r.push(t);return r}function d(n,r){return r=s(void 0===r?n.length-1:r,0),function(){for(var t=arguments,e=-1,u=s(t.length-r,0),c=Array(u);++e<u;)c[e]=t[r+e];e=-1;for(var a=Array(r+1);++e<r;)a[e]=t[e];return a[r]=c,o(n,this,a)}}function j(n,r,t,e){t||(t={});for(var u=-1,o=r.length;++u<o;){var c=r[u],a=e?e(t[c],n[c],c,t,n):void 0;g(t,c,void 0===a?n[c]:a)}return t}function O(n){return d(function(r,t){var e=-1,u=t.length,o=u>1?t[u-1]:void 0,c=u>2?t[2]:void 0;for(o=n.length>3&&"function"==typeof o?(u--,o):void 0,c&&A(t[0],t[1],c)&&(o=u<3?void 0:o,u=1),r=Object(r);++e<u;){var a=t[e];a&&n(r,a,e,o)}return r})}function m(r,t){return!!(t=null==t?n:t)&&("number"==typeof r||u.test(r))&&r>-1&&r%1==0&&r<t}function A(n,r,t){if(!M(t))return!1;var e=typeof r;return!!("number"==e?k(t)&&m(r,t.length):"string"==e&&r in t)&&x(t[r],n)}function w(n){var r=n&&n.constructor;return n===("function"==typeof r&&r.prototype||i)}function x(n,r){return n===r||n!=n&&r!=r}function F(n){return E(n)&&l.call(n,"callee")&&(!v.call(n,"callee")||f.call(n)==r)}var S=Array.isArray;function k(n){return null!=n&&I(n.length)&&!G(n)}function E(n){return P(n)&&k(n)}function G(n){var r=M(n)?f.call(n):"";return r==t||r==e}function I(r){return"number"==typeof r&&r>-1&&r%1==0&&r<=n}function M(n){var r=typeof n;return!!n&&("object"==r||"function"==r)}function P(n){return!!n&&"object"==typeof n}var $=O(function(n,r){if(y||w(r)||k(r))j(r,q(r),n);else for(var t in r)l.call(r,t)&&g(n,t,r[t])});function q(n){return k(n)?h(n):b(n)}module.exports=$;
},{}],"HPVF":[function(require,module,exports) {
"use strict";var e=t(require("lodash.assign"));function t(e){return e&&e.__esModule?e:{default:e}}var a=wp.compose.createHigherOrderComponent,n=wp.element.Fragment,r=wp.editor.InspectorControls,l=wp.components,i=l.PanelBody,o=l.SelectControl,c=wp.hooks.addFilter,u=wp.i18n.__,s=["core/cover","core/group"],d=[{label:u("Default"),value:""},{label:u("Narrow"),value:"narrow"},{label:u("Standard"),value:"standard"},{label:u("Wide"),value:"wide"},{label:u("Full"),value:"full-width"}],p=function(t,a){return s.includes(a)?(t.attributes=(0,e.default)(t.attributes,{spacing:{type:"string",default:d[0].value}}),t):t};c("blocks.registerBlockType","mai-container-width/attribute/container-width",p);var w=a(function(e){return function(t){if(!s.includes(t.name))return React.createElement(e,t);var a=t.attributes.spacing;return a&&(t.attributes.className="".concat(a,"-content")),React.createElement(n,null,React.createElement(e,t),React.createElement(r,null,React.createElement(i,{title:u("Container Width"),initialOpen:!0},React.createElement(o,{label:u(""),value:a,options:d,onChange:function(e){t.setAttributes({spacing:e})}}))))}},"withContainerWidthControl");c("editor.BlockEdit","mai-container-width/with-container-width",w);
},{"lodash.assign":"bLaB"}],"X1ux":[function(require,module,exports) {
"use strict";require("./blocks/container-width");
},{"./blocks/container-width":"HPVF"}]},{},["X1ux"], null)
//# sourceMappingURL=/blocks.js.map