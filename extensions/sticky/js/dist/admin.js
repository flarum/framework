(()=>{var e={n:s=>{var t=s&&s.__esModule?()=>s.default:()=>s;return e.d(t,{a:t}),t},d:(s,t)=>{for(var r in t)e.o(t,r)&&!e.o(s,r)&&Object.defineProperty(s,r,{enumerable:!0,get:t[r]})},o:(e,s)=>Object.prototype.hasOwnProperty.call(e,s),r:e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},s={};(()=>{"use strict";e.r(s),e.d(s,{extend:()=>m});const t=flarum.reg.get("core","admin/app");var r=e.n(t);const n=flarum.reg.get("core","common/extenders");var a=e.n(n);const i=flarum.reg.get("core","common/query/IGambit"),o=flarum.reg.get("core","common/app");var l=e.n(o);class c extends i.BooleanGambit{key(){return l().translator.trans("flarum-sticky.lib.gambits.discussions.sticky.key",{},!0)}filterKey(){return"sticky"}}flarum.reg.add("flarum-sticky","common/query/discussions/StickyGambit",c);const m=[(new(a().Search)).gambit("discussions",c),(new(a().Admin)).permission((()=>({icon:"fas fa-thumbtack",label:r().translator.trans("flarum-sticky.admin.permissions.sticky_discussions_label"),permission:"discussion.sticky"})),"moderate",95).setting((()=>({setting:"flarum-sticky.only_sticky_unread_discussions",name:"onlyStickyUnreadDiscussions",type:"boolean",label:r().translator.trans("flarum-sticky.admin.settings.only_sticky_unread_discussions_label"),help:r().translator.trans("flarum-sticky.admin.settings.only_sticky_unread_discussions_help")})))];r().initializers.add("flarum-sticky",(()=>{}))})(),module.exports=s})();
//# sourceMappingURL=admin.js.map