(()=>{var e={n:t=>{var a=t&&t.__esModule?()=>t.default:()=>t;return e.d(a,{a}),a},d:(t,a)=>{for(var s in a)e.o(a,s)&&!e.o(t,s)&&Object.defineProperty(t,s,{enumerable:!0,get:a[s]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};(()=>{"use strict";e.r(t),e.d(t,{extend:()=>n});const a=flarum.reg.get("core","admin/app");var s=e.n(a);const r=flarum.reg.get("core","common/extenders"),n=[(new(e.n(r)().Admin)).setting((()=>({setting:"flarum-akismet.api_key",type:"text",label:s().translator.trans("flarum-akismet.admin.akismet_settings.api_key_label")}))).setting((()=>({setting:"flarum-akismet.delete_blatant_spam",type:"boolean",label:s().translator.trans("flarum-akismet.admin.akismet_settings.delete_blatant_spam_label"),help:s().translator.trans("flarum-akismet.admin.akismet_settings.delete_blatant_spam_help")}))).permission((()=>({icon:"fas fa-vote-yea",label:s().translator.trans("flarum-akismet.admin.permissions.bypass_akismet"),permission:"bypassAkismet"})),"start")];s().initializers.add("flarum-akismet",(()=>{}))})(),module.exports=t})();
//# sourceMappingURL=admin.js.map