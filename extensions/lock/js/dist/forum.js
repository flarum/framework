(()=>{var o={n:e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},d:(e,t)=>{for(var n in t)o.o(t,n)&&!o.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},o:(o,e)=>Object.prototype.hasOwnProperty.call(o,e),r:o=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(o,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(o,"__esModule",{value:!0})}},e={};(()=>{"use strict";o.r(e),o.d(e,{extend:()=>D});const t=flarum.reg.get("core","common/extend"),n=flarum.reg.get("core","forum/app");var r=o.n(n);const s=flarum.reg.get("core","forum/components/Notification");var c=o.n(s);class a extends(c()){icon(){return"fas fa-lock"}href(){const o=this.attrs.notification;return r().route.discussion(o.subject(),o.content().postNumber)}content(){return r().translator.trans("flarum-lock.forum.notifications.discussion_locked_text",{user:this.attrs.notification.fromUser()})}excerpt(){return null}}flarum.reg.add("flarum-lock","forum/components/DiscussionLockedNotification",a);const i=flarum.reg.get("core","common/models/Discussion");var l=o.n(i);const u=flarum.reg.get("core","common/components/Badge");var d=o.n(u);const f=flarum.reg.get("core","forum/utils/DiscussionControls");var k=o.n(f);const g=flarum.reg.get("core","forum/components/DiscussionPage");var p=o.n(g);const b=flarum.reg.get("core","common/components/Button");var y=o.n(b);const _=flarum.reg.get("core","common/extenders");var v=o.n(_);const x=flarum.reg.get("core","forum/components/EventPost");var L=o.n(x);class h extends(L()){icon(){return this.attrs.post.content().locked?"fas fa-lock":"fas fa-unlock"}descriptionKey(){return this.attrs.post.content().locked?"flarum-lock.forum.post_stream.discussion_locked_text":"flarum-lock.forum.post_stream.discussion_unlocked_text"}}flarum.reg.add("flarum-lock","forum/components/DiscussionLockedPost",h);const P=flarum.reg.get("core","common/query/IGambit"),S=flarum.reg.get("core","common/app");var j=o.n(S);class w extends P.BooleanGambit{key(){return j().translator.trans("flarum-lock.lib.gambits.discussions.locked.key",{},!0)}filterKey(){return"locked"}}flarum.reg.add("flarum-lock","common/query/discussions/LockedGambit",w);const D=[(new(v().Search)).gambit("discussions",w),(new(v().PostTypes)).add("discussionLocked",h),new(v().Model)(l()).attribute("isLocked").attribute("canLock")];r().initializers.add("flarum-lock",(()=>{r().notificationComponents.discussionLocked=a,(0,t.extend)(l().prototype,"badges",(function(o){this.isLocked()&&o.add("locked",m(d(),{type:"locked",label:r().translator.trans("flarum-lock.forum.badge.locked_tooltip"),icon:"fas fa-lock"}))})),(0,t.extend)(k(),"moderationControls",(function(o,e){e.canLock()&&o.add("lock",m(y(),{icon:"fas fa-lock",onclick:this.lockAction.bind(e)},r().translator.trans("flarum-lock.forum.discussion_controls.".concat(e.isLocked()?"unlock":"lock","_button"))))})),k().lockAction=function(){this.save({isLocked:!this.isLocked()}).then((()=>{r().current.matches(p())&&r().current.get("stream").update(),m.redraw()}))},(0,t.extend)("flarum/forum/components/NotificationGrid","notificationTypes",(function(o){o.add("discussionLocked",{name:"discussionLocked",icon:"fas fa-lock",label:r().translator.trans("flarum-lock.forum.settings.notify_discussion_locked_label")})}))}))})(),module.exports=e})();
//# sourceMappingURL=forum.js.map