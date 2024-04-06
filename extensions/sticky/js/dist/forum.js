(()=>{var t={n:e=>{var s=e&&e.__esModule?()=>e.default:()=>e;return t.d(s,{a:s}),s},d:(e,s)=>{for(var o in s)t.o(s,o)&&!t.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:s[o]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},e={};(()=>{"use strict";t.r(e),t.d(e,{extend:()=>B});const s=flarum.reg.get("core","forum/app");var o=t.n(s);const r=flarum.reg.get("core","common/extend"),n=flarum.reg.get("core","common/models/Discussion");var c=t.n(n);const i=flarum.reg.get("core","common/components/Badge");var a=t.n(i);const u=flarum.reg.get("core","forum/utils/DiscussionControls");var l=t.n(u);const d=flarum.reg.get("core","forum/components/DiscussionPage");var f=t.n(d);const y=flarum.reg.get("core","common/components/Button");var g=t.n(y);const k=flarum.reg.get("core","forum/states/DiscussionListState");var p=t.n(k);const b=flarum.reg.get("core","forum/components/DiscussionListItem");var v=t.n(b);const S=flarum.reg.get("core","forum/components/IndexPage");var h=t.n(S);const x=flarum.reg.get("core","common/utils/string"),P=flarum.reg.get("core","common/utils/classList");var _=t.n(P);const D=flarum.reg.get("core","common/extenders");var w=t.n(D);const I=flarum.reg.get("core","forum/components/EventPost");var O=t.n(I);class j extends(O()){icon(){return"fas fa-thumbtack"}descriptionKey(){return this.attrs.post.content().sticky?"flarum-sticky.forum.post_stream.discussion_stickied_text":"flarum-sticky.forum.post_stream.discussion_unstickied_text"}}flarum.reg.add("flarum-sticky","forum/components/DiscussionStickiedPost",j);const q=flarum.reg.get("core","common/query/IGambit"),L=flarum.reg.get("core","common/app");var M=t.n(L);class A extends q.BooleanGambit{key(){return M().translator.trans("flarum-sticky.lib.gambits.discussions.sticky.key",{},!0)}filterKey(){return"sticky"}}flarum.reg.add("flarum-sticky","common/query/discussions/StickyGambit",A);const B=[(new(w().Search)).gambit("discussions",A),(new(w().PostTypes)).add("discussionStickied",j),new(w().Model)(c()).attribute("isSticky").attribute("canSticky")];o().initializers.add("flarum-sticky",(()=>{(0,r.extend)(c().prototype,"badges",(function(t){this.isSticky()&&t.add("sticky",m(a(),{type:"sticky",label:o().translator.trans("flarum-sticky.forum.badge.sticky_tooltip"),icon:"fas fa-thumbtack"}),10)})),(0,r.extend)(l(),"moderationControls",(function(t,e){e.canSticky()&&t.add("sticky",m(g(),{icon:"fas fa-thumbtack",onclick:this.stickyAction.bind(e)},o().translator.trans("flarum-sticky.forum.discussion_controls.".concat(e.isSticky()?"unsticky":"sticky","_button"))))})),l().stickyAction=function(){this.save({isSticky:!this.isSticky()}).then((()=>{o().current.matches(f())&&o().current.get("stream").update(),m.redraw()}))},(0,r.extend)(p().prototype,"requestParams",(function(t){(o().current.matches(h())||o().current.matches(f()))&&t.include.push("firstPost")})),(0,r.extend)(v().prototype,"infoItems",(function(t){const e=this.attrs.discussion;if(e.isSticky()&&!this.attrs.params.q&&!e.lastReadPostNumber()){const s=e.firstPost();if(s){const e=(0,x.truncate)(s.contentPlain(),175);t.add("excerpt",m("div",null,e),-100)}}})),(0,r.extend)(v().prototype,"elementAttrs",(function(t){this.attrs.discussion.isSticky()&&(t.className=_()(t.className,"DiscussionListItem--sticky"))}))}))})(),module.exports=e})();
//# sourceMappingURL=forum.js.map