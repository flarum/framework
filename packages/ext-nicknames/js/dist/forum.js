module.exports=function(t){var n={};function e(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return t[o].call(r.exports,r,r.exports,e),r.l=!0,r.exports}return e.m=t,e.c=n,e.d=function(t,n,o){e.o(t,n)||Object.defineProperty(t,n,{enumerable:!0,get:o})},e.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},e.t=function(t,n){if(1&n&&(t=e(t)),8&n)return t;if(4&n&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(e.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&n&&"string"!=typeof t)for(var r in t)e.d(o,r,function(n){return t[n]}.bind(null,r));return o},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},e.p="",e(e.s=9)}([function(t,n){t.exports=flarum.core.compat.extend},function(t,n){t.exports=flarum.core.compat["components/Button"]},function(t,n){t.exports=flarum.core.compat["utils/Stream"]},function(t,n){t.exports=flarum.core.compat["components/EditUserModal"]},,function(t,n){t.exports=flarum.core.compat["components/SettingsPage"]},function(t,n){t.exports=flarum.core.compat["utils/extractText"]},function(t,n){t.exports=flarum.core.compat["components/Modal"]},,function(t,n,e){"use strict";e.r(n);var o=e(0),r=e(1),a=e.n(r),i=e(3),s=e.n(i),c=e(5),u=e.n(c),p=e(6),l=e.n(p),f=e(2),d=e.n(f);var h=e(7),b=function(t){var n,e;function o(){return t.apply(this,arguments)||this}e=t,(n=o).prototype=Object.create(e.prototype),n.prototype.constructor=n,n.__proto__=e;var r=o.prototype;return r.oninit=function(n){t.prototype.oninit.call(this,n),this.nickname=d()(app.session.user.displayName())},r.className=function(){return"NickameModal Modal--small"},r.title=function(){return app.translator.trans("flarum-nicknames.forum.nickname.change")},r.content=function(){return m("div",{className:"Modal-body"},m("div",{className:"Form Form--centered"},m("div",{className:"Form-group"},m("input",{type:"text",autocomplete:"off",name:"nickname",className:"FormControl",bidi:this.nickname,disabled:this.loading})),m("div",{className:"Form-group"},a.a.component({className:"Button Button--primary Button--block",type:"submit",loading:this.loading},app.translator.trans("flarum-nicknames.forum.nickname.submit_button")))))},r.onsubmit=function(t){var n=this;t.preventDefault(),this.nickname()!==app.session.user.displayName()?(this.loading=!0,app.session.user.save({nickname:this.nickname()},{errorHandler:this.onerror.bind(this)}).then(this.hide.bind(this)).catch((function(){n.loading=!1,m.redraw()}))):this.hide()},o}(e.n(h).a);app.initializers.add("flarum/nicknames",(function(){Object(o.extend)(u.a.prototype,"accountItems",(function(t){t.add("changeNickname",m(a.a,{className:"Button",onclick:function(){return app.modal.show(b)}},app.translator.trans("flarum-nicknames.forum.settings.change_nickname_button")))})),Object(o.extend)(s.a.prototype,"oninit",(function(){this.nickname=d()(this.attrs.user.displayName())})),Object(o.extend)(s.a.prototype,"fields",(function(t){t.add("nickname",m("div",{className:"Form-group"},m("label",null,app.translator.trans("flarum-nicknames.forum.edit_user.password_heading")),m("input",{className:"FormControl",placeholder:l()(app.translator.trans("flarum-nicknames.forum.edit_user.password_text")),bidi:this.nickname})),100)})),Object(o.extend)(s.a.prototype,"data",(function(t){this.attrs.user;this.nickname()!==this.attrs.user.username()&&(t.nickname=this.nickname())}))}))}]);
//# sourceMappingURL=forum.js.map