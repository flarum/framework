(()=>{var o={n:e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},d:(e,t)=>{for(var n in t)o.o(t,n)&&!o.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},o:(o,e)=>Object.prototype.hasOwnProperty.call(o,e),r:o=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(o,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(o,"__esModule",{value:!0})}},e={};(()=>{"use strict";o.r(e),o.d(e,{extend:()=>N});const t=flarum.reg.get("core","common/extend"),n=flarum.reg.get("core","forum/app");var r=o.n(n);const s=flarum.reg.get("core","common/models/Discussion");var c=o.n(s);const a=flarum.reg.get("core","common/components/Badge");var i=o.n(a);const l=flarum.reg.get("core","forum/utils/DiscussionControls");var u=o.n(l);const d=flarum.reg.get("core","forum/components/DiscussionPage");var f=o.n(d);const k=flarum.reg.get("core","common/components/Button");var g=o.n(k);const p=flarum.reg.get("core","common/extenders");var b=o.n(p);const y=flarum.reg.get("core","forum/components/EventPost");var _=o.n(y);class v extends(_()){icon(){return this.attrs.post.content().locked?"fas fa-lock":"fas fa-unlock"}descriptionKey(){return this.attrs.post.content().locked?"flarum-lock.forum.post_stream.discussion_locked_text":"flarum-lock.forum.post_stream.discussion_unlocked_text"}}flarum.reg.add("flarum-lock","forum/components/DiscussionLockedPost",v);const x=flarum.reg.get("core","common/query/IGambit"),L=flarum.reg.get("core","common/app");var h=o.n(L);class P extends x.BooleanGambit{key(){return h().translator.trans("flarum-lock.lib.gambits.discussions.locked.key",{},!0)}filterKey(){return"locked"}}flarum.reg.add("flarum-lock","common/query/discussions/LockedGambit",P);const w=[(new(b().Search)).gambit("discussions",P)],S=flarum.reg.get("core","forum/components/Notification");var j=o.n(S);class D extends(j()){icon(){return"fas fa-lock"}href(){const o=this.attrs.notification;return r().route.discussion(o.subject(),o.content().postNumber)}content(){return r().translator.trans("flarum-lock.forum.notifications.discussion_locked_text",{user:this.attrs.notification.fromUser()})}excerpt(){return null}}flarum.reg.add("flarum-lock","forum/components/DiscussionLockedNotification",D);const N=[...w,(new(b().PostTypes)).add("discussionLocked",v),(new(b().Notification)).add("discussionLocked",D),new(b().Model)(c()).attribute("isLocked").attribute("canLock")];r().initializers.add("flarum-lock",(()=>{(0,t.extend)(c().prototype,"badges",(function(o){this.isLocked()&&o.add("locked",m(i(),{type:"locked",label:r().translator.trans("flarum-lock.forum.badge.locked_tooltip"),icon:"fas fa-lock"}))})),(0,t.extend)(u(),"moderationControls",(function(o,e){e.canLock()&&o.add("lock",m(g(),{icon:"fas fa-lock",onclick:this.lockAction.bind(e)},r().translator.trans("flarum-lock.forum.discussion_controls.".concat(e.isLocked()?"unlock":"lock","_button"))))})),u().lockAction=function(){this.save({isLocked:!this.isLocked()}).then((()=>{r().current.matches(f())&&r().current.get("stream").update(),m.redraw()}))},(0,t.extend)("flarum/forum/components/NotificationGrid","notificationTypes",(function(o){o.add("discussionLocked",{name:"discussionLocked",icon:"fas fa-lock",label:r().translator.trans("flarum-lock.forum.settings.notify_discussion_locked_label")})}))}))})(),module.exports=e})();
//# sourceMappingURL=forum.js.map