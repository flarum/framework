(()=>{var t={n:e=>{var o=e&&e.__esModule?()=>e.default:()=>e;return t.d(o,{a:o}),o},d:(e,o)=>{for(var s in o)t.o(o,s)&&!t.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:o[s]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},e={};(()=>{"use strict";t.r(e),t.d(e,{extend:()=>J});const o=flarum.reg.get("core","common/extend"),s=flarum.reg.get("core","forum/app");var r=t.n(s);const n=flarum.reg.get("core","common/components/Button");var a=t.n(n);const i=flarum.reg.get("core","forum/components/CommentPost");var l=t.n(i);const u=flarum.reg.get("core","common/components/Link");var c=t.n(u);const f=flarum.reg.get("core","common/helpers/punctuateSeries");var d=t.n(f);const p=flarum.reg.get("core","common/helpers/username");var g=t.n(p);const k=flarum.reg.get("core","common/components/Icon");var h=t.n(k);const v=flarum.reg.get("core","common/components/Modal");var b=t.n(v);const y=flarum.reg.get("core","common/components/Avatar");var _=t.n(y);const x=flarum.reg.get("core","common/states/PaginatedListState");var L=t.n(x);class P extends(L()){constructor(t,e){void 0===e&&(e=1),t.page={...t.page||{},limit:10},super(t,e,10)}get type(){return"users"}}flarum.reg.add("flarum-likes","forum/states/PostLikesModalState",P);const N=flarum.reg.get("core","common/components/LoadingIndicator");var M=t.n(N);const B=flarum.reg.get("core","common/components/Form");var S=t.n(B);class j extends(b()){oninit(t){super.oninit(t),this.state=new P({filter:{liked:this.attrs.post.id()}}),this.state.refresh()}className(){return"PostLikesModal Modal--small"}title(){return r().translator.trans("flarum-likes.forum.post_likes.title")}content(){return m("[",null,m("div",{className:"Modal-body"},this.state.isInitialLoading()?m(M(),null):m("ul",{className:"PostLikesModal-list"},this.state.getPages().map((t=>t.items.map((t=>m("li",null,m(c(),{href:r().route.user(t)},m(_(),{user:t})," ",g()(t))))))))),this.state.hasNext()?m("div",{className:"Modal-footer"},m(S(),{className:"Form--centered"},m("div",{className:"Form-group"},m(a(),{className:"Button Button--block",onclick:()=>this.state.loadNext(),loading:this.state.isLoadingNext()},r().translator.trans("flarum-likes.forum.post_likes.load_more_button"))))):null)}}flarum.reg.add("flarum-likes","forum/components/PostLikesModal",j);const I=flarum.reg.get("core","forum/components/Notification");var w=t.n(I);const C=flarum.reg.get("core","common/utils/string");class O extends(w()){icon(){return"far fa-thumbs-up"}href(){return r().route.post(this.attrs.notification.subject())}content(){const t=this.attrs.notification.fromUser();return r().translator.trans("flarum-likes.forum.notifications.post_liked_text",{user:t,count:1})}excerpt(){return(0,C.truncate)(this.attrs.notification.subject().contentPlain(),200)}}flarum.reg.add("flarum-likes","forum/components/PostLikedNotification",O);const U=flarum.reg.get("core","forum/components/UserPage");var F=t.n(U);const T=flarum.reg.get("core","common/components/LinkButton");var A=t.n(T);const R=flarum.reg.get("core","common/extenders");var z=t.n(R);const D=flarum.reg.get("core","common/models/Post");var G=t.n(D);const H=flarum.reg.get("core","forum/components/PostsUserPage");var q=t.n(H);class E extends(q()){loadResults(t){return r().store.find("posts",{filter:{type:"comment",likedBy:this.user.id()},page:{offset:t,limit:this.loadLimit},sort:"-createdAt"})}}flarum.reg.add("flarum-likes","forum/components/LikesUserPage",E);const J=[(new(z().Routes)).add("user.likes","/u/:username/likes",E),new(z().Model)(G()).hasMany("likes").attribute("likesCount").attribute("canLike")];r().initializers.add("flarum-likes",(()=>{r().notificationComponents.postLiked=O,(0,o.extend)(l().prototype,"actionItems",(function(t){const e=this.attrs.post;if(e.isHidden()||!e.canLike())return;const o=e.likes();let s=r().session.user&&o&&o.some((t=>t===r().session.user));t.add("like",m(a(),{className:"Button Button--link",onclick:()=>{s=!s,e.save({isLiked:s});const t=e.data.relationships.likes.data;t.some(((e,o)=>{if(e.id===r().session.user.id())return t.splice(o,1),!0})),s&&t.unshift({type:"users",id:r().session.user.id()})}},r().translator.trans(s?"flarum-likes.forum.post.unlike_link":"flarum-likes.forum.post.like_link")))})),(0,o.extend)(l().prototype,"footerItems",(function(t){const e=this.attrs.post,o=e.likes();if(o&&o.length){const s=4,n=e.likesCount()>s,i=o.sort((t=>t===r().session.user?-1:1)).slice(0,n?s-1:s).map((t=>m(c(),{href:r().route.user(t)},t===r().session.user?r().translator.trans("flarum-likes.forum.post.you_text"):g()(t))));if(n){const t=e.likesCount()-i.length,o=r().translator.trans("flarum-likes.forum.post.others_link",{count:t});r().forum.attribute("canSearchUsers")?i.push(m(a(),{className:"Button Button--ua-reset Button--text",onclick:t=>{t.preventDefault(),r().modal.show(j,{post:e})}},o)):i.push(m("span",null,o))}t.add("liked",m("div",{className:"Post-likedBy"},m(h(),{name:"far fa-thumbs-up"}),r().translator.trans("flarum-likes.forum.post.liked_by".concat(o[0]===r().session.user?"_self":"","_text"),{count:i.length,users:d()(i)})))}})),(0,o.extend)(F().prototype,"navItems",(function(t){const e=this.user;t.add("likes",m(A(),{href:r().route("user.likes",{username:null==e?void 0:e.slug()}),icon:"far fa-thumbs-up"},r().translator.trans("flarum-likes.forum.user.likes_link")),88)})),(0,o.extend)("flarum/forum/components/NotificationGrid","notificationTypes",(function(t){t.add("postLiked",{name:"postLiked",icon:"far fa-thumbs-up",label:r().translator.trans("flarum-likes.forum.settings.notify_post_liked_label")})}))}))})(),module.exports=e})();
//# sourceMappingURL=forum.js.map