(()=>{var e={n:r=>{var t=r&&r.__esModule?()=>r.default:()=>r;return e.d(t,{a:t}),t},d:(r,t)=>{for(var s in t)e.o(t,s)&&!e.o(r,s)&&Object.defineProperty(r,s,{enumerable:!0,get:t[s]})},o:(e,r)=>Object.prototype.hasOwnProperty.call(e,r),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},r={};(()=>{"use strict";e.r(r),e.d(r,{extend:()=>l});const t=flarum.reg.get("core","admin/app");var s=e.n(t);const a=flarum.reg.get("core","common/extenders");var o=e.n(a);const i=flarum.reg.get("core","common/query/IGambit"),n=flarum.reg.get("core","common/app");var c=e.n(n);class m extends i.BooleanGambit{key(){return c().translator.trans("flarum-sticky.lib.gambits.discussions.sticky.key",{},!0)}filterKey(){return"sticky"}}flarum.reg.add("flarum-sticky","common/query/discussions/StickyGambit",m);const l=[(new(o().Search)).gambit("discussions",m)];s().initializers.add("flarum-sticky",(()=>{s().extensionData.for("flarum-sticky").registerPermission({icon:"fas fa-thumbtack",label:s().translator.trans("flarum-sticky.admin.permissions.sticky_discussions_label"),permission:"discussion.sticky"},"moderate",95)}))})(),module.exports=r})();
//# sourceMappingURL=admin.js.map