module.exports=function(e){var n={};function t(r){if(n[r])return n[r].exports;var a=n[r]={i:r,l:!1,exports:{}};return e[r].call(a.exports,a,a.exports,t),a.l=!0,a.exports}return t.m=e,t.c=n,t.d=function(e,n,r){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:r})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(t.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var a in e)t.d(r,a,function(n){return e[n]}.bind(null,a));return r},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="",t(t.s=10)}({10:function(e,n,t){"use strict";t.r(n);var r=t(4);for(var a in r)["default"].indexOf(a)<0&&function(e){t.d(n,e,(function(){return r[e]}))}(a)},4:function(e,n){app.initializers.add("flarum/nicknames",(function(){app.extensionData.for("flarum-nicknames").registerSetting({setting:"flarum-nicknames.unique",type:"boolean",label:app.translator.trans("flarum-nicknames.admin.settings.unique_label")}).registerSetting({setting:"flarum-nicknames.regex",type:"text",label:app.translator.trans("flarum-nicknames.admin.settings.regex_label")}).registerSetting({setting:"flarum-nicknames.min",type:"number",label:app.translator.trans("flarum-nicknames.admin.settings.min_label")}).registerSetting({setting:"flarum-nicknames.max",type:"number",label:app.translator.trans("flarum-nicknames.admin.settings.max_label")}).registerPermission({icon:"fas fa-user-tag",label:app.translator.trans("flarum-nicknames.admin.permissions.edit_own_nickname_label"),permission:"user.editOwnNickname"},"start")}))}});
//# sourceMappingURL=admin.js.map