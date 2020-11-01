module.exports=function(t){var e={};function n(o){if(e[o])return e[o].exports;var s=e[o]={i:o,l:!1,exports:{}};return t[o].call(s.exports,s,s.exports,n),s.l=!0,s.exports}return n.m=t,n.c=e,n.d=function(t,e,o){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:o})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var s in t)n.d(o,s,function(e){return t[e]}.bind(null,s));return o},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=8)}([function(t,e){t.exports=flarum.core.compat.app},function(t,e){t.exports=flarum.core.compat.extend},function(t,e){t.exports=flarum.core.compat["components/DiscussionList"]},function(t,e){t.exports=flarum.core.compat["components/IndexPage"]},function(t,e){t.exports=flarum.core.compat["components/DiscussionPage"]},function(t,e){t.exports=flarum.core.compat["components/Button"]},,,function(t,e,n){"use strict";n.r(e);var o=n(1),s=n(0),r=n.n(s),i=n(2),u=n.n(i),a=n(4),c=n.n(a),d=n(3),p=n.n(d),f=n(5),l=n.n(f);r.a.initializers.add("flarum-pusher",(function(){var t=new Promise((function(t){$.getScript("//cdn.jsdelivr.net/npm/pusher-js@3.0.0/dist/pusher.min.js",(function(){var e=new Pusher(r.a.forum.attribute("pusherKey"),{authEndpoint:r.a.forum.attribute("apiUrl")+"/pusher/auth",cluster:r.a.forum.attribute("pusherCluster"),auth:{headers:{"X-CSRF-Token":r.a.session.csrfToken}}});return t({main:e.subscribe("public"),user:r.a.session.user?e.subscribe("private-user"+r.a.session.user.id()):null})}))}));r.a.pusher=t,r.a.pushedUpdates=[],Object(o.extend)(u.a.prototype,"oncreate",(function(){r.a.pusher.then((function(t){t.main.bind("newPost",(function(t){var e=r.a.discussions.getParams();if(!e.q&&!e.sort&&!e.filter){if(e.tags){var n=r.a.store.getBy("tags","slug",e.tags);if(-1===t.tagIds.indexOf(n.id()))return}var o=String(t.discussionId);r.a.current.get("discussion")&&o===r.a.current.get("discussion").id()||-1!==r.a.pushedUpdates.indexOf(o)||(r.a.pushedUpdates.push(o),r.a.current.matches(p.a)&&r.a.setTitleCount(r.a.pushedUpdates.length),m.redraw())}}))}))})),Object(o.extend)(u.a.prototype,"onremove",(function(){r.a.pusher.then((function(t){t.main.unbind("newPost")}))})),Object(o.extend)(u.a.prototype,"view",(function(t){var e=this;if(r.a.pushedUpdates){var n=r.a.pushedUpdates.length;n&&t.children.unshift(l.a.component({className:"Button Button--block DiscussionList-update",onclick:function(){e.attrs.state.refresh(!1).then((function(){e.loadingUpdated=!1,r.a.pushedUpdates=[],r.a.setTitleCount(0),m.redraw()})),e.loadingUpdated=!0},loading:this.loadingUpdated},r.a.translator.transChoice("flarum-pusher.forum.discussion_list.show_updates_text",n,{count:n})))}})),Object(o.extend)(u.a.prototype,"addDiscussion",(function(t,e){var n=r.a.pushedUpdates.indexOf(e.id());-1!==n&&r.a.pushedUpdates.splice(n,1),r.a.current.matches(p.a)&&r.a.setTitleCount(r.a.pushedUpdates.length),m.redraw()})),Object(o.extend)(c.a.prototype,"oncreate",(function(){var t=this;r.a.pusher.then((function(e){e.main.bind("newPost",(function(e){var n=String(e.discussionId);if(t.discussion&&t.discussion.id()===n&&t.stream){var o=t.discussion.commentCount();r.a.store.find("discussions",t.discussion.id()).then((function(){t.stream.update(),document.hasFocus()||(r.a.setTitleCount(Math.max(0,t.discussion.commentCount()-o)),$(window).one("focus",(function(){return r.a.setTitleCount(0)})))}))}}))}))})),Object(o.extend)(c.a.prototype,"onremove",(function(){r.a.pusher.then((function(t){t.main.unbind("newPost")}))})),Object(o.extend)(p.a.prototype,"actionItems",(function(t){t.remove("refresh")})),r.a.pusher.then((function(t){t.user&&t.user.bind("notification",(function(){r.a.session.user.pushAttributes({unreadNotificationCount:r.a.session.user.unreadNotificationCount()+1,newNotificationCount:r.a.session.user.newNotificationCount()+1}),r.a.notifications.clear(),m.redraw()}))}))}))}]);
//# sourceMappingURL=forum.js.map