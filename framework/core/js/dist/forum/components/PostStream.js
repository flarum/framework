"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[158],{3038:(t,s,e)=>{e.r(s),e.d(s,{default:()=>P});var o=e(3554),i=e(5710),a=e(2361),r=e(348),n=e(6445),l=e(5952),d=e(1883),c=e(5673),h=e(7891);class u extends i.A{view(){if(this.attrs.composingReply?this.attrs.composingReply():o.A.composer.composingReplyTo(this.attrs.discussion))return m("article",{className:"Post CommentPost editing","aria-busy":"true"},m("div",{className:"Post-container"},m("div",{className:"Post-side"},m(h.A,{user:o.A.session.user,className:"Post-avatar"})),m("div",{className:"Post-main"},m("header",{className:"Post-header"},m("div",{className:"PostUser"},m("h3",{className:"PostUser-name"},(0,n.A)(o.A.session.user)),m("ul",{className:"PostUser-badges badges badges--packed"},(0,c.A)(o.A.session.user.badges().toArray())))),m("div",{className:"Post-body"},m(d.A,{className:"Post-body",composer:o.A.composer,surround:this.anchorPreview.bind(this)})))));const t=this.attrs.onclick||(()=>{l.A.replyAction.call(this.attrs.discussion,!0,!1).catch((()=>{}))});return m("button",{className:"Post ReplyPlaceholder",onclick:t},m("div",{className:"Post-container"},m("div",{className:"Post-side"},m(h.A,{user:o.A.session.user,className:"Post-avatar"})),m("div",{className:"Post-main"},m("span",{className:"Post-header"},o.A.translator.trans("core.forum.post_stream.reply_placeholder")))))}anchorPreview(t){const s=$(window).scrollTop()+$(window).height()>=$(document).height();t(),s&&$(window).scrollTop($(document).height())}}flarum.reg.add("core","forum/components/ReplyPlaceholder",u);var p=e(3092),f=e(6064);class g extends i.A{view(){const t=this.attrs.post,s=o.A.postComponents[t.contentType()];return!!s&&m(s,{post:t})}}flarum.reg.add("core","forum/components/PostType",g);class P extends i.A{oninit(t){super.oninit(t),this.discussion=this.attrs.discussion,this.stream=this.attrs.stream,this.scrollListener=new a.A(this.onscroll.bind(this))}view(){let t;const s=this.stream.viewingEnd(),e=this.stream.posts(),i=this.discussion.postIds(),a=t=>{$(t.dom).addClass("fadeIn"),setTimeout((()=>$(t.dom).removeClass("fadeIn")),500)},n=e.map(((s,e)=>{let n;const l={"data-index":this.stream.visibleStart+e};if(s){const e=s.createdAt();n=m(g,{post:s}),l.key="post"+s.id(),l.oncreate=a,l["data-time"]=e.toISOString(),l["data-number"]=s.number(),l["data-id"]=s.id(),l["data-type"]=s.contentType();const i=e-t;i>3456e5&&(n=[m("div",{className:"PostStream-timeGap"},m("span",null,o.A.translator.trans("core.forum.post_stream.time_lapsed_text",{period:dayjs().add(i,"ms").fromNow(!0)}))),n]),t=e}else l.key="post"+i[this.stream.visibleStart+e],n=m(r.A,null);return m("div",Object.assign({className:"PostStream-item"},l),n)}));return!s&&e[this.stream.visibleEnd-this.stream.visibleStart-1]&&n.push(m("div",{className:"PostStream-loadMore",key:"loadMore"},m(p.A,{className:"Button",onclick:this.stream.loadNext.bind(this.stream)},o.A.translator.trans("core.forum.post_stream.load_more_button")))),s&&n.push(...this.endItems().toArray()),!s||o.A.session.user&&!this.discussion.canReply()||n.push(m("div",{className:"PostStream-item",key:"reply","data-index":this.stream.count(),oncreate:a},m(u,{discussion:this.discussion}))),m("div",{className:"PostStream",role:"feed","aria-live":"off","aria-busy":this.stream.pagesLoading?"true":"false"},n)}endItems(){return new f.A}onupdate(t){super.onupdate(t),this.triggerScroll()}oncreate(t){super.oncreate(t),this.triggerScroll(),setTimeout((()=>this.scrollListener.start()))}onremove(t){super.onremove(t),this.scrollListener.stop(),clearTimeout(this.calculatePositionTimeout)}triggerScroll(){if(!this.stream.needsScroll)return;const t=this.stream.targetPost;this.stream.needsScroll=!1,"number"in t?this.scrollToNumber(t.number,this.stream.animateScroll):"index"in t&&this.scrollToIndex(t.index,this.stream.animateScroll,t.reply)}onscroll(t){void 0===t&&(t=window.pageYOffset),this.stream.paused||this.stream.pagesLoading||(this.updateScrubber(t),this.loadPostsIfNeeded(t),clearTimeout(this.calculatePositionTimeout),this.calculatePositionTimeout=setTimeout(this.calculatePosition.bind(this,t),100))}loadPostsIfNeeded(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window).height()-s,o=t+s;if(this.stream.visibleStart>0){const t=this.$(".PostStream-item[data-index="+this.stream.visibleStart+"]");t.length&&t.offset().top>o-300&&this.stream.loadPrevious()}if(this.stream.visibleEnd<this.stream.count()){const t=this.$(".PostStream-item[data-index="+(this.stream.visibleEnd-1)+"]");t.length&&t.offset().top+t.outerHeight(!0)<o+e+300&&this.stream.loadNext()}}updateScrubber(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window).height()-s,i=t+s,a=this.$(".PostStream-item[data-index]");let r=0,n="",m=null;a.each((function(){const t=$(this),s=t.offset().top,o=t.outerHeight(!0);if(s+o<i)return!0;if(s>i+e)return!1;const a=Math.max(0,i-s),l=Math.min(o,i+e-s)-a;null===m&&(m=parseFloat(t.data("index"))+a/o),l>0&&(r+=l/o);const d=t.data("time");d&&(n=d)})),this.stream.index=null!==m?m+1:this.stream.count(),this.stream.visible=r,n&&(this.stream.description=o.A.translator.formatDateTime(dayjs(n),"core.lib.datetime_formats.scrubber"))}calculatePosition(t){void 0===t&&(t=window.pageYOffset);const s=this.getMarginTop(),e=$(window),o=e.height()-s,i=e.scrollTop()+s,a=t+s;let r,n;this.$(".PostStream-item").each((function(){const t=$(this),s=t.offset().top,e=t.outerHeight(!0),m=Math.max(0,a-s);if(void 0===r&&(m/e<.75||(e-m)/o>.25)&&(r=t.data("number")),s+e>i){if(!(s+e<i+o))return!1;t.data("number")&&(n=t.data("number"))}})),r&&this.attrs.onPositionChange(r||1,n,r)}getMarginTop(){const t="phone"===o.A.screen()?"#app-navigation":"#header";return this.$()&&$(t).outerHeight()+parseInt(this.$().css("margin-top"),10)}scrollToNumber(t,s){const e=this.$(`.PostStream-item[data-number=${t}]`);return this.scrollToItem(e,s).then(this.flashItem.bind(this,e))}scrollToIndex(t,s,e){const o=e?$(".PostStream-item:last-child"):this.$(`.PostStream-item[data-index=${t}]`);this.scrollToItem(o,s,!0,e),e&&this.flashItem(o)}scrollToItem(t,s,e,i){const a=$("html, body").stop(!0),r=t.data("index");if(t.length){const r=t.offset().top-this.getMarginTop(),n=t.offset().top+t.height(),m=$(document).scrollTop(),l=m+$(window).height();if(e||r<m||n>l){const e=i?n-$(window).height()+o.A.composer.computedHeight():t.is(":first-child")?0:r;s?e!==m&&a.animate({scrollTop:e},"fast"):a.scrollTop(e)}}const n=()=>{this.updateScrubber(),void 0!==r&&(this.stream.index=r+1)};return n(),this.stream.forceUpdateScrubber=!0,Promise.all([a.promise(),this.stream.loadPromise]).then((()=>{let t;if(m.redraw.sync(),i){const t=$(".PostStream-item:last-child");$(window).scrollTop(t.offset().top+t.height()-$(window).height()+o.A.composer.computedHeight())}else 0===r?$(window).scrollTop(0):(t=$(`.PostStream-item[data-index=${r}]`).offset())&&$(window).scrollTop(t.top-this.getMarginTop());n(),this.calculatePosition(),this.stream.paused=!1,this.loadPostsIfNeeded()}))}flashItem(t){t.removeClass("fadeIn"),t.addClass("flash").on("animationend webkitAnimationEnd",(s=>{t.removeClass("flash")}))}}flarum.reg.add("core","forum/components/PostStream",P)}}]);
//# sourceMappingURL=PostStream.js.map