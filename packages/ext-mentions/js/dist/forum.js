module.exports=function(t){var e={};function n(o){if(e[o])return e[o].exports;var r=e[o]={i:o,l:!1,exports:{}};return t[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=t,n.c=e,n.d=function(t,e,o){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:o})},n.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)n.d(o,r,function(e){return t[e]}.bind(null,r));return o},n.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="",n(n.s=30)}([function(t,e){t.exports=flarum.core.compat.extend},function(t,e){t.exports=flarum.core.compat["components/CommentPost"]},function(t,e){t.exports=flarum.core.compat.app},function(t,e){t.exports=flarum.core.compat["utils/string"]},function(t,e){t.exports=flarum.core.compat["helpers/username"]},function(t,e){t.exports=flarum.core.compat["components/PostPreview"]},function(t,e){t.exports=flarum.core.compat["helpers/icon"]},function(t,e){t.exports=flarum.core.compat["components/EditPostComposer"]},function(t,e){t.exports=flarum.core.compat.Fragment},function(t,e){t.exports=flarum.core.compat["components/Notification"]},function(t,e){t.exports=flarum.core.compat["components/TextEditor"]},function(t,e){t.exports=flarum.core.compat["utils/extractText"]},,function(t,e){t.exports=flarum.core.compat["components/NotificationGrid"]},function(t,e){t.exports=flarum.core.compat["components/LoadingIndicator"]},function(t,e){t.exports=flarum.core.compat.Model},function(t,e){t.exports=flarum.core.compat["models/Post"]},function(t,e){t.exports=flarum.core.compat["components/Link"]},function(t,e){t.exports=flarum.core.compat["helpers/punctuateSeries"]},function(t,e){t.exports=flarum.core.compat["components/Button"]},function(t,e){t.exports=flarum.core.compat["utils/DiscussionControls"]},function(t,e){t.exports=flarum.core.compat["components/TextEditorButton"]},function(t,e){t.exports=flarum.core.compat["components/ReplyComposer"]},function(t,e){t.exports=flarum.core.compat["helpers/avatar"]},function(t,e){t.exports=flarum.core.compat["helpers/highlight"]},function(t,e){t.exports=flarum.core.compat["utils/KeyboardNavigatable"]},function(t,e){t.exports=flarum.core.compat["components/UserPage"]},function(t,e){t.exports=flarum.core.compat["components/LinkButton"]},function(t,e){t.exports=flarum.core.compat["components/PostsUserPage"]},,function(t,e,n){"use strict";n.r(e),n.d(e,"filterUserMentions",(function(){return bt})),n.d(e,"filterPostMentions",(function(){return yt}));var o=n(0),r=n(2),i=n.n(r),s=n(13),a=n.n(s),u=n(3),c=n(1),p=n.n(c),f=n(5),l=n.n(f),d=n(14),h=n.n(d);var v=n(15),b=n.n(v),y=n(16),g=n.n(y),x=n(17),w=n.n(x),P=n(18),C=n.n(P),T=n(4),_=n.n(T),M=n(6),j=n.n(M);var O=n(19),A=n.n(O),B=n(20),k=n.n(B),S=n(7),H=n.n(S);function N(t){return t.displayName().replace(/"#[a-z]{0,3}[0-9]+/,"_")}function I(t,e,n){var o=t.user(),r='@"'+(o&&N(o)||app.translator.trans("core.lib.username.deleted_text"))+'"#p'+t.id()+" ";e.fields.content()||(e.body.attrs.originalContent=r);var i=e.editor.getSelectionRange()[0],s=e.fields.content().slice(0,i),a=0==s.length?0:3-s.match(/(\n{0,2})$/)[0].length;e.editor.insertAtCursor(Array(a).join("\n")+(n?"> "+r+n.trim().replace(/\n/g,"\n> ")+"\n\n":r),!1)}function E(t,e){app.composer.bodyMatches(H.a)&&app.composer.body.attrs.post.discussion()===t.discussion()?I(t,app.composer,e):k.a.replyAction.call(t.discussion()).then((function(n){return I(t,n,e)}))}function W(t,e){t.prototype=Object.create(e.prototype),t.prototype.constructor=t,t.__proto__=e}var D=n(8),L=n.n(D),R=function(t){function e(e){var n;return(n=t.call(this)||this).post=e,n}W(e,t);var n=e.prototype;return n.view=function(){var t=this;return m("button",{class:"Button PostQuoteButton",onclick:function(){E(t.post,t.content)}},j()("fas fa-quote-left",{className:"Button-icon"}),app.translator.trans("flarum-mentions.forum.post.quote_button"))},n.show=function(t,e){var n=this.$().show(),o=n.offsetParent().offset();n.css("left",t-o.left).css("top",e-o.top),this.hideHandler=this.hide.bind(this),$(document).on("mouseup",this.hideHandler)},n.showStart=function(t,e){var n=this.$();this.show(t,$(window).scrollTop()+e-n.outerHeight()-5)},n.showEnd=function(t,e){var n=this.$();this.show(t-n.outerWidth(),$(window).scrollTop()+e+5)},n.hide=function(){this.$().hide(),$(document).off("mouseup",this.hideHandler)},e}(L.a);function U(){Object(o.extend)(p.a.prototype,"oncreate",(function(){var t=this.attrs.post;if(!(t.isHidden()||app.session.user&&!t.discussion().canReply())){var e=this.$(".Post-body"),n=$('<div class="Post-quoteButtonContainer"></div>'),o=new R(t),r=function(t){setTimeout((function(){var r=function(t){var e=window.getSelection();if(e.rangeCount){var n=e.getRangeAt(0),o=n.commonAncestorContainer;if(t[0]===o||$.contains(t[0],o)){var r=$("<div>").append(n.cloneContents());return r.find("img.emoji").replaceWith((function(){return this.alt})),r.find("img").replaceWith((function(){return"![]("+this.src+")"})),r.find("a").replaceWith((function(){return"["+this.innerText+"]("+this.href+")"})),r.text()}}return""}(e);if(r){o.content=r,m.render(n[0],o.render());var i=window.getSelection().getRangeAt(0).getClientRects(),s=i[0];if(t.clientY<s.bottom&&t.clientX-s.right<s.left-t.clientX)o.showStart(s.left,s.top);else{var a=i[i.length-1];o.showEnd(a.right,a.bottom)}}}),1)};this.$().after(n).on("mouseup",r),"ontouchstart"in window&&document.addEventListener("selectionchange",r,!1)}}))}var q=n(10),J=n.n(q),z=n(21),F=n.n(z),X=n(22),Y=n.n(X),G=n(23),K=n.n(G),Q=n(24),V=n.n(Q),Z=n(25),tt=n.n(Z);function et(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}function nt(t,e,n){return e in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}var ot=function(t){function e(){for(var e,n=arguments.length,o=new Array(n),r=0;r<n;r++)o[r]=arguments[r];return nt(et(et(e=t.call.apply(t,[this].concat(o))||this)),"items",[]),nt(et(et(e)),"active",!1),nt(et(et(e)),"index",0),nt(et(et(e)),"keyWasJustPressed",!1),e}W(e,t);var n=e.prototype;return n.view=function(){return m("ul",{className:"Dropdown-menu MentionsDropdown"},this.items.map((function(t){return m("li",null,t)})))},n.show=function(t,e){this.$().show().css({left:t+"px",top:e+"px"}),this.active=!0},n.hide=function(){this.$().hide(),this.active=!1},n.navigate=function(t){var e=this;this.keyWasJustPressed=!0,this.setIndex(this.index+t,!0),clearTimeout(this.keyWasJustPressedTimeout),this.keyWasJustPressedTimeout=setTimeout((function(){return e.keyWasJustPressed=!1}),500)},n.complete=function(){this.$("li").eq(this.index).find("button").click()},n.setIndex=function(t,e){if(!this.keyWasJustPressed||e){var n=this.$(),o=n.find("li"),r=t;r<0?r=o.length-1:r>=o.length&&(r=0),this.index=r;var i=o.removeClass("active").eq(r).addClass("active");if(e){var s,a=n.scrollTop(),u=n.offset().top,c=u+n.outerHeight(),p=i.offset().top,f=p+i.outerHeight();p<u?s=a-u+p-parseInt(n.css("padding-top"),10):f>c&&(s=a-c+f+parseInt(n.css("padding-bottom"),10)),void 0!==s&&n.stop(!0).animate({scrollTop:s},100)}}},e}(L.a);function rt(){var t=$('<div class="ComposerBody-mentionsDropdownContainer"></div>'),e=new ot;Object(o.extend)(J.a.prototype,"oncreate",(function(n){var o=this.$(".TextEditor-editor").wrap('<div class="ComposerBody-mentionsWrapper"></div>');this.navigator=new tt.a,this.navigator.when((function(){return e.active})).onUp((function(){return e.navigate(-1)})).onDown((function(){return e.navigate(1)})).onSelect(e.complete.bind(e)).onCancel(e.hide.bind(e)).bindTo(o),o.after(t)})),Object(o.extend)(J.a.prototype,"buildEditorParams",(function(n){var o,r,i,s,a,c=[],p=Array.from(app.store.all("users")),f=new Set(p.map((function(t){return t.id()})));n.inputListeners.push((function(n){var l=app.composer.editor.getSelectionRange(),d=l[0];if(!(l[1]-d>0)){var h=app.composer.editor.getLastNChars(30);r=0;for(var v=h.length-1;v>=0;v--){if("@"===h.substr(v,1)&&(0==v||/\s/.test(h.substr(v-1,1)))){o=v+1,r=d-h.length+v+1;break}}if(e.hide(),e.active=!1,r){i=h.substring(o).toLowerCase(),s=i.match(/^"((?:(?!"#).)+)$/),i=s&&s[1]||i;var b=function(t,n,o,s){void 0===s&&(s="");var a=_()(t);return i&&(a.children=[V()(a.text,i)],delete a.text),m("button",{className:"PostPreview "+s,onclick:function(){return function(t){app.composer.editor.replaceBeforeCursor(r-1,t+" "),e.hide()}(n)},onmouseenter:function(){e.setIndex($(this).parent().index())}},m("span",{className:"PostPreview-content"},K()(t),a," "," ",o))},y=function(t){return[t.username(),t.displayName()].some((function(t){return t.toLowerCase().substr(0,i.length)===i}))},g=function(){var n=[];if(i&&p.forEach((function(t){y(t)&&n.push(b(t,'@"'+N(t)+'"#'+t.id(),"","MentionsDropdown-user"))})),app.composer.bodyMatches(Y.a)||app.composer.bodyMatches(H.a)){var o=app.composer.body.attrs,s=o.post,a=s&&s.discussion()||o.discussion;a&&a.posts().filter((function(t){return t&&"comment"===t.contentType()&&(!s||t.number()<s.number())})).sort((function(t,e){return e.createdAt()-t.createdAt()})).filter((function(t){var e=t.user();return e&&y(e)})).splice(0,5).forEach((function(t){var e=t.user();n.push(b(e,'@"'+N(e)+'"#p'+t.id(),[app.translator.trans("flarum-mentions.forum.composer.reply_to_post_text",{number:t.number()})," — ",Object(u.truncate)(t.contentPlain(),200)],"MentionsDropdown-post"))}))}if(n.length){e.items=n,m.render(t[0],e.render()),e.show();var c=app.composer.editor.getCaretCoordinates(r),f=e.$().outerWidth(),l=e.$().outerHeight(),d=e.$().offsetParent(),h=c.left,v=c.top+15;v+l>d.height()&&(v=c.top-l-15),h+f>d.width()&&(h=d.width()-f),v=Math.max(-(d.offset().top-$(document).scrollTop()),v),h=Math.max(-d.offset().left,h),e.show(h,v)}else e.active=!1,e.hide()};e.active=!0,g(),e.setIndex(0),e.$().scrollTop(0),clearTimeout(a),i.length>1&&(a=setTimeout((function(){var t=i.toLowerCase();-1===c.indexOf(t)&&(app.store.find("users",{filter:{q:i},page:{limit:5}}).then((function(t){t.forEach((function(t){f.has(t.id())||(f.add(t.id()),p.push(t))})),e.active&&g()})),c.push(t))}),250))}}}))})),Object(o.extend)(J.a.prototype,"toolbarItems",(function(t){var e=this;t.add("mention",m(F.a,{onclick:function(){return e.attrs.composer.editor.insertAtCursor(" @")},icon:"fas fa-at"},app.translator.trans("flarum-mentions.forum.composer.mention_tooltip")))}))}var it=n(9),st=n.n(it),at=function(t){function e(){return t.apply(this,arguments)||this}W(e,t);var n=e.prototype;return n.icon=function(){return"fas fa-reply"},n.href=function(){var t=this.attrs.notification,e=t.subject(),n=t.content();return app.route.discussion(e.discussion(),n&&n.replyNumber)},n.content=function(){var t=this.attrs.notification.fromUser();return app.translator.transChoice("flarum-mentions.forum.notifications.post_mentioned_text",1,{user:t})},n.excerpt=function(){return Object(u.truncate)(this.attrs.notification.subject().contentPlain(),200)},e}(st.a),ut=function(t){function e(){return t.apply(this,arguments)||this}W(e,t);var n=e.prototype;return n.icon=function(){return"fas fa-at"},n.href=function(){var t=this.attrs.notification.subject();return app.route.discussion(t.discussion(),t.number())},n.content=function(){var t=this.attrs.notification.fromUser();return app.translator.trans("flarum-mentions.forum.notifications.user_mentioned_text",{user:t})},n.excerpt=function(){return Object(u.truncate)(this.attrs.notification.subject().contentPlain(),200)},e}(st.a),ct=n(26),pt=n.n(ct),ft=n(27),lt=n.n(ft),mt=n(28),dt=function(t){function e(){return t.apply(this,arguments)||this}return W(e,t),e.prototype.loadResults=function(t){return app.store.find("posts",{filter:{type:"comment",mentioned:this.user.id()},page:{offset:t,limit:this.loadLimit},sort:"-createdAt"})},e}(n.n(mt).a),ht=n(11),vt=n.n(ht);function bt(t){var e;if(app.forum.attribute("allowUsernameMentionFormat")&&t.hasAttribute("username")?e=app.store.getBy("users","username",t.getAttribute("username")):t.hasAttribute("id")&&(e=app.store.getById("users",t.getAttribute("id"))),e)return t.setAttribute("id",e.id()),t.setAttribute("slug",e.slug()),t.setAttribute("displayname",vt()(_()(e))),!0;t.invalidate()}function yt(t){var e=app.store.getById("posts",t.getAttribute("id"));if(e)return t.setAttribute("discussionid",e.discussion().id()),t.setAttribute("number",e.number()),t.setAttribute("displayname",vt()(_()(e.user()))),!0}i.a.initializers.add("flarum-mentions",(function(){!function(){function t(){var t=this.attrs.post.contentHtml();if(t!==this.oldPostContentHtml&&!this.isEditing()){this.oldPostContentHtml=t;var e=this.attrs.post,n=this.$();this.$().on("click",".UserMention:not(.UserMention--deleted), .PostMention:not(.PostMention--deleted)",(function(t){m.route.set(this.getAttribute("href")),t.preventDefault()})),this.$(".PostMention:not(.PostMention--deleted)").each((function(){var t,o=$(this),r=o.data("id"),i=$('<ul class="Dropdown-menu PostMention-preview fade"/>');n.append(i);var s=function(){return $('.PostStream-item[data-id="'+r+'"]')},a=function(){var t=s(),a=!1;if(t.length){var u=t.offset().top,c=window.pageYOffset;u>c&&u+t.height()<c+$(window).height()&&(t.addClass("pulsate"),a=!0)}if(!a){var p=function(){var t=i.outerHeight(!0),e=0;o.offset().top-t<$(window).scrollTop()+$("#header").outerHeight()?e+=o.outerHeight(!0):e-=t,i.show().css("top",o.offset().top-n.offset().top+e).css("left",o.offsetParent().offset().left-n.offset().left).css("max-width",o.offsetParent().width())},f=function(t){var n=t.discussion();m.render(i[0],[n!==e.discussion()?m("li",null,m("span",{className:"PostMention-preview-discussion"},n.title())):"",m("li",null,l.a.component({post:t}))]),p()},d=app.store.getById("posts",r);d&&d.discussion()?f(d):(m.render(i[0],h.a.component()),app.store.find("posts",r).then(f),p()),setTimeout((function(){return i.off("transitionend").addClass("in")}))}},u=function(){s().removeClass("pulsate"),i.hasClass("in")&&i.removeClass("in").one("transitionend",(function(){return i.hide()}))};o.on("touchend",(function(t){t.cancelable&&t.preventDefault()})),o.add(i).hover((function(){clearTimeout(t),t=setTimeout(a,250)}),(function(){clearTimeout(t),s().removeClass("pulsate"),t=setTimeout(u,250)})).on("touchend",(function(t){a(),t.stopPropagation()})),$(document).on("touchend",u)}))}}Object(o.extend)(p.a.prototype,"oncreate",t),Object(o.extend)(p.a.prototype,"onupdate",t)}(),g.a.prototype.mentionedBy=b.a.hasMany("mentionedBy"),Object(o.extend)(p.a.prototype,"footerItems",(function(t){var e=this,n=this.attrs.post.mentionedBy();if(n&&n.length){var o=function(){e.$(".Post-mentionedBy-preview").removeClass("in").one("transitionend",(function(){$(this).hide()}))},r=[],i=n.sort((function(t){return t.user()===app.session.user?-1:0})).filter((function(t){var e=t.user();if(-1===r.indexOf(e))return r.push(e),!0})),s=i.length>4,a=i.slice(0,s?3:4).map((function(t){var e=t.user();return m(w.a,{href:app.route.post(t),onclick:o,"data-number":t.number()},app.session.user===e?app.translator.trans("flarum-mentions.forum.post.you_text"):_()(e))}));if(s){var u=i.length-a.length;a.push(app.translator.transChoice("flarum-mentions.forum.post.others_text",u,{count:u}))}t.add("replies",m("div",{className:"Post-mentionedBy",oncreate:function(t){var e,r=$(t.dom),i=$('<ul class="Dropdown-menu Post-mentionedBy-preview fade"/>');r.append(i),r.children().hover((function(){clearTimeout(e),e=setTimeout((function(){!i.hasClass("in")&&i.is(":visible")||(m.render(i[0],n.map((function(t){return m("li",{"data-number":t.number()},l.a.component({post:t,onclick:o}))}))),i.show(),setTimeout((function(){return i.off("transitionend").addClass("in")})))}),500)}),(function(){clearTimeout(e),e=setTimeout(o,250)})),r.find(".Post-mentionedBy-summary a").hover((function(){i.find('[data-number="'+$(this).data("number")+'"]').addClass("active")}),(function(){i.find("[data-number]").removeClass("active")}))}},m("span",{className:"Post-mentionedBy-summary"},j()("fas fa-reply"),app.translator.transChoice("flarum-mentions.forum.post.mentioned_by"+(i[0].user()===app.session.user?"_self":"")+"_text",a.length,{count:a.length,users:C()(a)}))))}})),Object(o.extend)(p.a.prototype,"actionItems",(function(t){var e=this.attrs.post;e.isHidden()||app.session.user&&!e.discussion().canReply()||t.add("reply",m(A.a,{className:"Button Button--link",onclick:function(){return E(e)}},app.translator.trans("flarum-mentions.forum.post.reply_link")))})),U(),rt(),i.a.notificationComponents.postMentioned=at,i.a.notificationComponents.userMentioned=ut,Object(o.extend)(a.a.prototype,"notificationTypes",(function(t){t.add("postMentioned",{name:"postMentioned",icon:"fas fa-reply",label:i.a.translator.trans("flarum-mentions.forum.settings.notify_post_mentioned_label")}),t.add("userMentioned",{name:"userMentioned",icon:"fas fa-at",label:i.a.translator.trans("flarum-mentions.forum.settings.notify_user_mentioned_label")})})),i.a.routes["user.mentions"]={path:"/u/:username/mentions",component:dt},Object(o.extend)(pt.a.prototype,"navItems",(function(t){var e=this.user;t.add("mentions",lt.a.component({href:i.a.route("user.mentions",{username:e.username()}),name:"mentions",icon:"fas fa-at"},i.a.translator.trans("flarum-mentions.forum.user.mentions_link")),80)})),u.getPlainContent.removeSelectors.push("a.PostMention")}))}]);
//# sourceMappingURL=forum.js.map