module.exports=function(t){var e={};function r(o){if(e[o])return e[o].exports;var n=e[o]={i:o,l:!1,exports:{}};return t[o].call(n.exports,n,n.exports,r),n.l=!0,n.exports}return r.m=t,r.c=e,r.d=function(t,e,o){r.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:o})},r.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},r.t=function(t,e){if(1&e&&(t=r(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(r.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)r.d(o,n,function(e){return t[e]}.bind(null,n));return o},r.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(e,"a",e),e},r.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},r.p="",r(r.s=11)}([function(t,e){t.exports=flarum.core.compat.app},function(t,e){t.exports=flarum.core.compat.extend},,function(t,e){t.exports=flarum.core.compat["components/PermissionGrid"]},,,,,,,,function(t,e,r){"use strict";r.r(e);var o=r(1),n=r(0),a=r.n(n),s=r(3),i=r.n(s);a.a.initializers.add("flarum-approval",function(){Object(o.extend)(a.a,"getRequiredPermissions",function(t,e){"discussion.startWithoutApproval"===e&&t.push("startDiscussion"),"discussion.replyWithoutApproval"===e&&t.push("discussion.reply")}),Object(o.extend)(i.a.prototype,"startItems",function(t){t.add("startDiscussionsWithoutApproval",{icon:"fas fa-check",label:a.a.translator.trans("flarum-approval.admin.permissions.start_discussions_without_approval_label"),permission:"discussion.startWithoutApproval"},95)}),Object(o.extend)(i.a.prototype,"replyItems",function(t){t.add("replyWithoutApproval",{icon:"fas fa-check",label:a.a.translator.trans("flarum-approval.admin.permissions.reply_without_approval_label"),permission:"discussion.replyWithoutApproval"},95)}),Object(o.extend)(i.a.prototype,"moderateItems",function(t){t.add("approvePosts",{icon:"fas fa-check",label:a.a.translator.trans("flarum-approval.admin.permissions.approve_posts_label"),permission:"discussion.approvePosts"},65)})})}]);
//# sourceMappingURL=admin.js.map