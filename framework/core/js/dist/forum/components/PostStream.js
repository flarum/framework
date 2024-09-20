"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[563],{7764:(t,s,e)=>{e.r(s),e.d(s,{default:()=>g});var a=e(6789),o=e(2190),i=e(4718),r=e(7323);class n extends o.Z{view(){return m("div",{className:"Post CommentPost LoadingPost"},m("header",{className:"Post-header"},m(r.Z,{user:null,className:"PostUser-avatar"}),m("div",{className:"fakeText"})),m("div",{className:"Post-body"},m("div",{className:"fakeText"}),m("div",{className:"fakeText"}),m("div",{className:"fakeText"})))}}flarum.reg.add("core","forum/components/LoadingPost",n);var l=e(5537),d=e(3666),c=e(378),h=e(1268);class u extends o.Z{view(){return a.Z.composer.composingReplyTo(this.attrs.discussion)?m("article",{className:"Post CommentPost editing","aria-busy":"true"},m("div",{className:"Post-container"},m("div",{className:"Post-side"},m(r.Z,{user:a.Z.session.user,className:"Post-avatar"})),m("div",{className:"Post-main"},m("header",{className:"Post-header"},m("div",{className:"PostUser"},m("h3",{className:"PostUser-name"},(0,l.Z)(a.Z.session.user)),m("ul",{className:"PostUser-badges badges badges--packed"},(0,h.Z)(a.Z.session.user.badges().toArray())))),m("div",{className:"Post-body"},m(c.Z,{className:"Post-body",composer:a.Z.composer,surround:this.anchorPreview.bind(this)}))))):m("button",{className:"Post ReplyPlaceholder",onclick:()=>{d.Z.replyAction.call(this.attrs.discussion,!0).catch((()=>{}))}},m("div",{className:"Post-container"},m("div",{className:"Post-side"},m(r.Z,{user:a.Z.session.user,className:"Post-avatar"})),m("div",{className:"Post-main"},m("span",{className:"Post-header"},a.Z.translator.trans("core.forum.post_stream.reply_placeholder")))))}anchorPreview(t){const s=$(window).scrollTop()+$(window).height()>=$(document).height();t(),s&&$(window).scrollTop($(document).height())}}flarum.reg.add("core","forum/components/ReplyPlaceholder",u);var p=e(8312),f=e(4041);class g extends o.Z{oninit(t){super.oninit(t),this.discussion=this.attrs.discussion,this.stream=this.attrs.stream,this.scrollListener=new i.Z(this.onscroll.bind(this))}view(){let t;const s=this.stream.viewingEnd(),e=this.stream.posts(),o=this.discussion.postIds(),i=t=>{$(t.dom).addClass("fadeIn"),setTimeout((()=>$(t.dom).removeClass("fadeIn")),500)},r=e.map(((s,e)=>{let r;const l={"data-index":this.stream.visibleStart+e};if(s){const e=s.createdAt(),o=a.Z.postComponents[s.contentType()];r=!!o&&m(o,{post:s}),l.key="post"+s.id(),l.oncreate=i,l["data-time"]=e.toISOString(),l["data-number"]=s.number(),l["data-id"]=s.id(),l["data-type"]=s.contentType();const n=e-t;n>3456e5&&(r=[m("div",{className:"PostStream-timeGap"},m("span",null,a.Z.translator.trans("core.forum.post_stream.time_lapsed_text",{period:dayjs().add(n,"ms").fromNow(!0)}))),r]),t=e}else l.key="post"+o[this.stream.visibleStart+e],r=m(n,null);return m("div",Object.assign({className:"PostStream-item"},l),r)}));return!s&&e[this.stream.visibleEnd-this.stream.visibleStart-1]&&r.push(m("div",{className:"PostStream-loadMore",key:"loadMore"},m(p.Z,{className:"Button",onclick:this.stream.loadNext.bind(this.stream)},a.Z.translator.trans("core.forum.post_stream.load_more_button")))),s&&r.push(...this.endItems().toArray()),!s||a.Z.session.user&&!this.discussion.canReply()||r.push(m("div",{className:"PostStream-item",key:"reply","data-index":this.stream.count(),oncreate:i},m(u,{discussion:this.discussion}))),m("div",{className:"PostStream",role:"feed","aria-live":"off","aria-busy":this.stream.pagesLoading?"true":"false"},r)}endItems(){return new f.Z}onupdate(t){super.onupdate(t),this.triggerScroll()}oncreate(t){super.oncreate(t),this.triggerScroll(),setTimeout((()=>this.scrollListener.start()))}onremove(t){super.onremove(t),this.scrollListener.stop(),clearTimeout(this.calculatePositionTimeout)}triggerScroll(){if(!this.stream.needsScroll)return;const t=this.stream.targetPost;this.stream.needsScroll=!1,"number"in t?this.scrollToNumber(t.number,this.stream.animateScroll):"index"in t&&this.scrollToIndex(t.index,this.stream.animateScroll,t.reply)}onscroll(t){void 0===t&&(t=window.pageYOffset),this.stream.paused||this.stream.pagesLoading||(this.updateScrubber(t),this.loadPostsIfNeeded(t),clearTimeout(this.calculatePositionTimeout),this.calculatePositionTimeout=setTimeout(this.calculatePosition.bind(this,t),100))}loadPostsIfNeeded(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window).height()-s,a=t+s;if(this.stream.visibleStart>0){const t=this.$(".PostStream-item[data-index="+this.stream.visibleStart+"]");t.length&&t.offset().top>a-300&&this.stream.loadPrevious()}if(this.stream.visibleEnd<this.stream.count()){const t=this.$(".PostStream-item[data-index="+(this.stream.visibleEnd-1)+"]");t.length&&t.offset().top+t.outerHeight(!0)<a+e+300&&this.stream.loadNext()}}updateScrubber(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window).height()-s,a=t+s,o=this.$(".PostStream-item[data-index]");let i=0,r="",n=null;o.each((function(){const t=$(this),s=t.offset().top,o=t.outerHeight(!0);if(s+o<a)return!0;if(s>a+e)return!1;const m=Math.max(0,a-s),l=Math.min(o,a+e-s)-m;null===n&&(n=parseFloat(t.data("index"))+m/o),l>0&&(i+=l/o);const d=t.data("time");d&&(r=d)})),this.stream.index=null!==n?n+1:this.stream.count(),this.stream.visible=i,r&&(this.stream.description=dayjs(r).format("MMMM YYYY"))}calculatePosition(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window),a=e.height()-s,o=e.scrollTop()+s,i=t+s;let r,n;this.$(".PostStream-item").each((function(){const t=$(this),s=t.offset().top,e=t.outerHeight(!0),m=Math.max(0,i-s);if(void 0===r&&(m/e<.75||(e-m)/a>.25)&&(r=t.data("number")),s+e>o){if(!(s+e<o+a))return!1;t.data("number")&&(n=t.data("number"))}})),r&&this.attrs.onPositionChange(r||1,n,r)}getMarginTop(){const t="phone"===a.Z.screen()?"#app-navigation":"#header";return this.$()&&$(t).outerHeight()+parseInt(this.$().css("margin-top"),10)}scrollToNumber(t,s){const e=this.$(".PostStream-item[data-number=".concat(t,"]"));return this.scrollToItem(e,s).then(this.flashItem.bind(this,e))}scrollToIndex(t,s,e){const a=e?$(".PostStream-item:last-child"):this.$(".PostStream-item[data-index=".concat(t,"]"));this.scrollToItem(a,s,!0,e),e&&this.flashItem(a)}scrollToItem(t,s,e,o){const i=$("html, body").stop(!0),r=t.data("index");if(t.length){const r=t.offset().top-this.getMarginTop(),n=t.offset().top+t.height(),m=$(document).scrollTop(),l=m+$(window).height();if(e||r<m||n>l){const e=o?n-$(window).height()+a.Z.composer.computedHeight():t.is(":first-child")?0:r;s?e!==m&&i.animate({scrollTop:e},"fast"):i.scrollTop(e)}}const n=()=>{this.updateScrubber(),void 0!==r&&(this.stream.index=r+1)};return n(),this.stream.forceUpdateScrubber=!0,Promise.all([i.promise(),this.stream.loadPromise]).then((()=>{let t;if(m.redraw.sync(),o){const t=$(".PostStream-item:last-child");$(window).scrollTop(t.offset().top+t.height()-$(window).height()+a.Z.composer.computedHeight())}else 0===r?$(window).scrollTop(0):(t=$(".PostStream-item[data-index=".concat(r,"]")).offset())&&$(window).scrollTop(t.top-this.getMarginTop());n(),this.calculatePosition(),this.stream.paused=!1,this.loadPostsIfNeeded()}))}flashItem(t){t.removeClass("fadeIn"),t.addClass("flash").on("animationend webkitAnimationEnd",(s=>{t.removeClass("flash")}))}}flarum.reg.add("core","forum/components/PostStream",g)}}]);
//# sourceMappingURL=PostStream.js.map