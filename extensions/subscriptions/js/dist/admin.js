(()=>{var e={n:r=>{var t=r&&r.__esModule?()=>r.default:()=>r;return e.d(t,{a:t}),t},d:(r,t)=>{for(var s in t)e.o(t,s)&&!e.o(r,s)&&Object.defineProperty(r,s,{enumerable:!0,get:t[s]})},o:(e,r)=>Object.prototype.hasOwnProperty.call(e,r),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},r={};(()=>{"use strict";e.r(r),e.d(r,{extend:()=>u});const t=flarum.reg.get("core","common/extenders");var s=e.n(t);const o=flarum.reg.get("core","common/app");var n=e.n(o);const i=flarum.reg.get("core","common/query/IGambit");class a extends i.BooleanGambit{key(){return[n().translator.trans("flarum-subscriptions.lib.gambits.discussions.subscription.following_key",{},!0),n().translator.trans("flarum-subscriptions.lib.gambits.discussions.subscription.ignoring_key",{},!0)]}toFilter(e,r){const t=(r?"-":"")+this.filterKey();return{[t]:e[1]}}filterKey(){return"subscription"}fromFilter(e,r){return`${r?"-":""}is:${e}`}enabled(){return!!n().session.user}}flarum.reg.add("flarum-subscriptions","common/query/discussions/SubscriptionGambit",a);const u=[(new(s().Search)).gambit("discussions",a)]})(),module.exports=r})();
//# sourceMappingURL=admin.js.map