parcelRequire=function(e,r,t,n){var i,o="function"==typeof parcelRequire&&parcelRequire,u="function"==typeof require&&require;function f(t,n){if(!r[t]){if(!e[t]){var i="function"==typeof parcelRequire&&parcelRequire;if(!n&&i)return i(t,!0);if(o)return o(t,!0);if(u&&"string"==typeof t)return u(t);var c=new Error("Cannot find module '"+t+"'");throw c.code="MODULE_NOT_FOUND",c}p.resolve=function(r){return e[t][1][r]||r},p.cache={};var l=r[t]=new f.Module(t);e[t][0].call(l.exports,p,l,l.exports,this)}return r[t].exports;function p(e){return f(p.resolve(e))}}f.isParcelRequire=!0,f.Module=function(e){this.id=e,this.bundle=f,this.exports={}},f.modules=e,f.cache=r,f.parent=o,f.register=function(r,t){e[r]=[function(e,r){r.exports=t},{}]};for(var c=0;c<t.length;c++)try{f(t[c])}catch(e){i||(i=e)}if(t.length){var l=f(t[t.length-1]);"object"==typeof exports&&"undefined"!=typeof module?module.exports=l:"function"==typeof define&&define.amd?define(function(){return l}):n&&(this[n]=l)}if(parcelRequire=f,i)throw i;return f}({"PF6Q":[function(require,module,exports) {
var n=9007199254740991,r="[object Arguments]",t="[object Function]",e="[object GeneratorFunction]",u=/^(?:0|[1-9]\d*)$/;function o(n,r,t){switch(t.length){case 0:return n.call(r);case 1:return n.call(r,t[0]);case 2:return n.call(r,t[0],t[1]);case 3:return n.call(r,t[0],t[1],t[2])}return n.apply(r,t)}function c(n,r){for(var t=-1,e=Array(n);++t<n;)e[t]=r(t);return e}function a(n,r){return function(t){return n(r(t))}}var i=Object.prototype,l=i.hasOwnProperty,f=i.toString,v=i.propertyIsEnumerable,p=a(Object.keys,Object),s=Math.max,y=!v.call({valueOf:1},"valueOf");function h(n,r){var t=S(n)||F(n)?c(n.length,String):[],e=t.length,u=!!e;for(var o in n)!r&&!l.call(n,o)||u&&("length"==o||m(o,e))||t.push(o);return t}function g(n,r,t){var e=n[r];l.call(n,r)&&x(e,t)&&(void 0!==t||r in n)||(n[r]=t)}function b(n){if(!w(n))return p(n);var r=[];for(var t in Object(n))l.call(n,t)&&"constructor"!=t&&r.push(t);return r}function d(n,r){return r=s(void 0===r?n.length-1:r,0),function(){for(var t=arguments,e=-1,u=s(t.length-r,0),c=Array(u);++e<u;)c[e]=t[r+e];e=-1;for(var a=Array(r+1);++e<r;)a[e]=t[e];return a[r]=c,o(n,this,a)}}function j(n,r,t,e){t||(t={});for(var u=-1,o=r.length;++u<o;){var c=r[u],a=e?e(t[c],n[c],c,t,n):void 0;g(t,c,void 0===a?n[c]:a)}return t}function O(n){return d(function(r,t){var e=-1,u=t.length,o=u>1?t[u-1]:void 0,c=u>2?t[2]:void 0;for(o=n.length>3&&"function"==typeof o?(u--,o):void 0,c&&A(t[0],t[1],c)&&(o=u<3?void 0:o,u=1),r=Object(r);++e<u;){var a=t[e];a&&n(r,a,e,o)}return r})}function m(r,t){return!!(t=null==t?n:t)&&("number"==typeof r||u.test(r))&&r>-1&&r%1==0&&r<t}function A(n,r,t){if(!M(t))return!1;var e=typeof r;return!!("number"==e?k(t)&&m(r,t.length):"string"==e&&r in t)&&x(t[r],n)}function w(n){var r=n&&n.constructor;return n===("function"==typeof r&&r.prototype||i)}function x(n,r){return n===r||n!=n&&r!=r}function F(n){return E(n)&&l.call(n,"callee")&&(!v.call(n,"callee")||f.call(n)==r)}var S=Array.isArray;function k(n){return null!=n&&I(n.length)&&!G(n)}function E(n){return P(n)&&k(n)}function G(n){var r=M(n)?f.call(n):"";return r==t||r==e}function I(r){return"number"==typeof r&&r>-1&&r%1==0&&r<=n}function M(n){var r=typeof n;return!!n&&("object"==r||"function"==r)}function P(n){return!!n&&"object"==typeof n}var $=O(function(n,r){if(y||w(r)||k(r))j(r,q(r),n);else for(var t in r)l.call(r,t)&&g(n,t,r[t])});function q(n){return k(n)?h(n):b(n)}module.exports=$;
},{}],"oSdA":[function(require,module,exports) {
"use strict";var e=a(require("lodash.assign")),t=Object.assign||function(e){for(var t=1;t<arguments.length;t++){var a=arguments[t];for(var n in a)Object.prototype.hasOwnProperty.call(a,n)&&(e[n]=a[n])}return e};function a(e){return e&&e.__esModule?e:{default:e}}var n=wp.i18n.__,i=wp.compose.createHigherOrderComponent,l=wp.element.Fragment,c=wp.blockEditor.InspectorControls,r=wp.hooks.addFilter,u=wp.components,m=u.PanelBody,o=u.BaseControl,s=u.ButtonGroup,g=u.Button,v=u.SelectControl,d=["core/cover","core/group"],p=["core/heading","core/paragraph"],b=["core/heading","core/paragraph","core/separator"],R=["core/image","core/cover","core/group"],E=function(t,a){return d.includes(a)&&(t.attributes=(0,e.default)(t.attributes,{contentWidth:{type:"string",default:""},contentAlign:{type:"string",default:""},verticalSpacingTop:{type:"string",default:""},verticalSpacingBottom:{type:"string",default:""},verticalSpacingLeft:{type:"string",default:""},verticalSpacingRight:{type:"string",default:""}})),p.includes(a)&&(t.attributes=(0,e.default)(t.attributes,{maxWidth:{type:"string",default:""}})),b.includes(a)&&(t.attributes=(0,e.default)(t.attributes,{spacingTop:{type:"string",default:""},spacingBottom:{type:"string",default:""}})),R.includes(a)&&(t.attributes=(0,e.default)(t.attributes,{marginTop:{type:"string",default:""},marginBottom:{type:"string",default:""},marginLeft:{type:"string",default:""},marginRight:{type:"string",default:""}})),t};r("blocks.registerBlockType","mai-engine/attribute/layout-settings",E);var f=i(function(e){return function(t){if(d.includes(t.name)){var a=[{label:n("XS","mai-engine"),value:"xs"},{label:n("S","mai-engine"),value:"sm"},{label:n("M","mai-engine"),value:"md"},{label:n("L","mai-engine"),value:"lg"},{label:n("XL","mai-engine"),value:"xl"},{label:n("Full","mai-engine"),value:"no"}],i=[{label:n("Start","mai-engine"),value:"start"},{label:n("Center","mai-engine"),value:"center"},{label:n("Right","mai-engine"),value:"end"}],r=t.attributes,u=r.contentWidth,v=r.contentAlign,p=r.verticalSpacingTop,b=r.verticalSpacingBottom,R=r.verticalSpacingLeft,E=r.verticalSpacingRight;return React.createElement(l,null,React.createElement(e,t),React.createElement(c,null,React.createElement(m,{title:n("Layout settings","mai-engine"),initialOpen:!1,className:"mai-content-width-align-settings"},React.createElement(o,{id:"mai-content-width",label:n("Content Max Width","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":u},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({contentWidth:e.value})},"data-checked":u===e.value,value:e.value,key:"mai-content-width-".concat(e.value),index:e.value,isSecondary:u!==e.value,isPrimary:u===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({contentWidth:null})}},n("Clear","mai-engine")))),React.createElement(o,{id:"mai-content-align",label:n("Content Alignment","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":v},i.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({contentAlign:e.value})},"data-checked":v===e.value,value:e.value,key:"mai-content-align-".concat(e.value),index:e.value,isSecondary:v!==e.value,isPrimary:v===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({contentAlign:null})}},n("Clear","mai-engine"))))),React.createElement(m,{title:n("Padding","mai-engine"),initialOpen:!1,className:"mai-spacing-settings"},React.createElement(o,{id:"mai-vertical-spacing-top",label:n("Top","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":p},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({verticalSpacingTop:e.value})},"data-checked":p===e.value,value:e.value,key:"mai-vertical-space-top-".concat(e.value),index:e.value,isSecondary:p!==e.value,isPrimary:p===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({verticalSpacingTop:null})}},n("Clear","mai-engine")))),React.createElement(o,{id:"mai-vertical-spacing-bottom",label:n("Bottom","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":b},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({verticalSpacingBottom:e.value})},"data-checked":b===e.value,value:e.value,key:"mai-vertical-space-bottom-".concat(e.value),index:e.value,isSecondary:b!==e.value,isPrimary:b===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({verticalSpacingBottom:null})}},n("Clear","mai-engine")))),React.createElement(o,{id:"mai-vertical-spacing-left",label:n("Left","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":R},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({verticalSpacingLeft:e.value})},"data-checked":R===e.value,value:e.value,key:"mai-vertical-space-left-".concat(e.value),index:e.value,isSecondary:R!==e.value,isPrimary:R===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({verticalSpacingLeft:null})}},n("Clear","mai-engine")))),React.createElement(o,{id:"mai-vertical-spacing-right",label:n("Right","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":E},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({verticalSpacingRight:e.value})},"data-checked":E===e.value,value:e.value,key:"mai-vertical-space-right-".concat(e.value),index:e.value,isSecondary:E!==e.value,isPrimary:E===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({verticalSpacingRight:null})}},n("Clear","mai-engine")))))))}return React.createElement(e,t)}},"withLayoutControls");r("editor.BlockEdit","mai-engine/with-layout-settings",f);var h=i(function(e){return function(t){if(p.includes(t.name)){var a=[{label:n("XS","mai-engine"),value:"xs"},{label:n("S","mai-engine"),value:"sm"},{label:n("M","mai-engine"),value:"md"},{label:n("L","mai-engine"),value:"lg"},{label:n("XL","mai-engine"),value:"xl"}],i=t.attributes.maxWidth;return React.createElement(l,null,React.createElement(e,t),React.createElement(c,null,React.createElement(m,{title:n("Width","mai-engine"),initialOpen:!1,className:"mai-width-settings"},React.createElement(o,{id:"mai-width",label:n("Max Width","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":i},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({maxWidth:e.value})},"data-checked":i===e.value,value:e.value,key:"mai-width-".concat(e.value),index:e.value,isSecondary:i!==e.value,isPrimary:i===e.value},React.createElement("small",null,e.label))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({maxWidth:null})}},n("Clear","mai-engine")))))))}return React.createElement(e,t)}},"withMaxWidthControls");r("editor.BlockEdit","mai-engine/with-max-width-settings",h);var k=i(function(e){return function(t){if(b.includes(t.name)){var a=[{label:n("XXS","mai-engine"),value:"sm"},{label:n("XS","mai-engine"),value:"md"},{label:n("S","mai-engine"),value:"lg"},{label:n("M","mai-engine"),value:"xl"},{label:n("L","mai-engine"),value:"xxl"},{label:n("XL","mai-engine"),value:"xxxl"},{label:n("XXL","mai-engine"),value:"xxxxl"}],i=t.attributes,r=i.spacingTop,u=i.spacingBottom;return React.createElement(l,null,React.createElement(e,t),React.createElement(c,null,React.createElement(m,{title:n("Spacing","mai-engine"),initialOpen:!1,className:"mai-spacing-settings"},React.createElement(o,{id:"mai-spacing-top",label:n("Top","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":r},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({spacingTop:e.value})},"data-checked":r===e.value,value:e.value,key:"mai-space-top-".concat(e.value),index:e.value,isSecondary:r!==e.value,isPrimary:r===e.value},React.createElement("small",null,React.createElement("small",null,e.label)))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({spacingTop:null})}},n("Clear","mai-engine")))),React.createElement(o,{id:"mai-spacing-bottom",label:n("Bottom","mai-engine")},React.createElement("div",null,React.createElement(s,{mode:"radio","data-chosen":u},a.map(function(e){return React.createElement(g,{onClick:function(){t.setAttributes({spacingBottom:e.value})},"data-checked":u===e.value,value:e.value,key:"mai-space-top-".concat(e.value),index:e.value,isSecondary:u!==e.value,isPrimary:u===e.value},React.createElement("small",null,React.createElement("small",null,e.label)))})),React.createElement(g,{isDestructive:!0,isSmall:!0,isLink:!0,onClick:function(){t.setAttributes({spacingBottom:null})}},n("Clear","mai-engine")))))))}return React.createElement(e,t)}},"withSpacingControls");r("editor.BlockEdit","mai-engine/with-spacing-settings",k);var x=i(function(e){return function(t){if(R.includes(t.name)){var a=[{label:n("Default","mai-engine"),value:""},{label:n("None","mai-engine"),value:"no"},{label:n("XS","mai-engine"),value:"md"},{label:n("S","mai-engine"),value:"lg"},{label:n("M","mai-engine"),value:"xl"},{label:n("L","mai-engine"),value:"xxl"},{label:n("XL","mai-engine"),value:"xxxl"},{label:n("XXL","mai-engine"),value:"xxxxl"},{label:n("XS Overlap","mai-engine"),value:"-md"},{label:n("S Overlap","mai-engine"),value:"-lg"},{label:n("M Overlap","mai-engine"),value:"-xl"},{label:n("L Overlap","mai-engine"),value:"-xxl"},{label:n("XL Overlap","mai-engine"),value:"-xxxl"},{label:n("XXL Overlap","mai-engine"),value:"-xxxxl"}],i=t.attributes,r=i.marginTop,u=i.marginBottom,o=i.marginLeft,s=i.marginRight;return React.createElement(l,null,React.createElement(e,t),React.createElement(c,null,React.createElement(m,{title:n("Margin","mai-engine"),initialOpen:!1,className:"mai-margin-settings"},React.createElement(v,{label:n("Top","mai-engine"),value:r,onChange:function(e){t.setAttributes({marginTop:e})},options:a}),React.createElement(v,{label:n("Bottom","mai-engine"),value:u,onChange:function(e){t.setAttributes({marginBottom:e})},options:a}),React.createElement(v,{label:n("Left","mai-engine"),value:o,onChange:function(e){t.setAttributes({marginLeft:e})},options:a}),React.createElement(v,{label:n("Right","mai-engine"),value:s,onChange:function(e){t.setAttributes({marginRight:e})},options:a}),React.createElement("p",null,React.createElement("em",null,n("Note: Left/right overlap settings are disabled on smaller screens.","mai-engine"))))))}return React.createElement(e,t)}},"withMarginControls");r("editor.BlockEdit","mai-engine/with-margin-settings",x);var S=i(function(e){return function(a){var n={};return d.includes(a.name)&&(n["data-content-width"]=a.attributes.contentWidth,n["data-content-align"]=a.attributes.contentAlign,n["data-spacing-top"]=a.attributes.verticalSpacingTop,n["data-spacing-bottom"]=a.attributes.verticalSpacingBottom,n["data-spacing-left"]=a.attributes.verticalSpacingLeft,n["data-spacing-right"]=a.attributes.verticalSpacingRight),p.includes(a.name)&&(n["data-max-width"]=a.attributes.maxWidth),b.includes(a.name)&&(n["data-spacing-top"]=a.attributes.spacingTop,n["data-spacing-bottom"]=a.attributes.spacingBottom),R.includes(a.name)&&(n["data-margin-top"]=a.attributes.marginTop,n["data-margin-bottom"]=a.attributes.marginBottom,n["data-margin-left"]=a.attributes.marginLeft,n["data-margin-right"]=a.attributes.marginRight),n?React.createElement(e,t({},a,{wrapperProps:n})):React.createElement(e,a)}},"addCustomAttributes");r("editor.BlockListBlock","mai-engine/add-custom-attributes",S);
},{"lodash.assign":"PF6Q"}],"X1ux":[function(require,module,exports) {
"use strict";require("./layout-settings");
},{"./layout-settings":"oSdA"}]},{},["X1ux"], null)
//# sourceMappingURL=/blocks.js.map