(()=>{var e={n:t=>{var r=t&&t.__esModule?()=>t.default:()=>t;return e.d(r,{a:r}),r},d:(t,r)=>{for(var n in r)e.o(r,n)&&!e.o(t,n)&&Object.defineProperty(t,n,{enumerable:!0,get:r[n]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};(()=>{"use strict";e.r(t);const r=flarum.reg.get("core","admin/app");var n=e.n(r);n().initializers.add("flarum-mentions",(function(){n().extensionData.for("flarum-mentions").registerSetting({setting:"flarum-mentions.allow_username_format",type:"boolean",label:n().translator.trans("flarum-mentions.admin.settings.allow_username_format_label"),help:n().translator.trans("flarum-mentions.admin.settings.allow_username_format_text")}).registerPermission({permission:"mentionGroups",label:n().translator.trans("flarum-mentions.admin.permissions.mention_groups_label"),icon:"fas fa-at"},"start")}))})(),module.exports=t})();
//# sourceMappingURL=admin.js.map