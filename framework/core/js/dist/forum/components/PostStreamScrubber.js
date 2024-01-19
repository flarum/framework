"use strict";(self.webpackChunkflarum_core=self.webpackChunkflarum_core||[]).push([[808],{9816:(e,t,s)=>{s.r(t),s.d(t,{default:()=>h});var r=s(6789),o=s(2190),i=s(6917),a=s(4718),n=s(9133);class h extends o.Z{oninit(e){super.oninit(e),this.stream=this.attrs.stream,this.handlers={},this.scrollListener=new a.Z(this.updateScrubberValues.bind(this,{fromScroll:!0,forceHeightChange:!0}))}view(){const e=this.stream.count(),t=r.Z.translator.trans("core.forum.post_scrubber.viewing_text",{count:e,index:m("span",{className:"Scrubber-index"}),formattedCount:m("span",{className:"Scrubber-count"},(0,i.Z)(e))}),s=this.stream.discussion.unreadCount(),o=e?Math.min(e-this.stream.index,s)/e:0;function a(e){const t=$(e.dom),s={top:100-100*o+"%",height:100*o+"%",opacity:o?1:0};e.state.oldStyle?t.stop(!0).css(e.state.oldStyle).animate(s):t.css(s),e.state.oldStyle=s}const h=["PostStreamScrubber","Dropdown"];return this.attrs.className&&h.push(this.attrs.className),m("div",{className:h.join(" ")},m("button",{className:"Button Dropdown-toggle","data-toggle":"dropdown"},t," ",m(n.Z,{name:"fas fa-sort"})),m("div",{className:"Dropdown-menu dropdown-menu"},m("div",{className:"Scrubber"},m("a",{className:"Scrubber-first",onclick:this.goToFirst.bind(this)},m(n.Z,{name:"fas fa-angle-double-up"})," ",r.Z.translator.trans("core.forum.post_scrubber.original_post_link")),m("div",{className:"Scrubber-scrollbar"},m("div",{className:"Scrubber-before"}),m("div",{className:"Scrubber-handle"},m("div",{className:"Scrubber-bar"}),m("div",{className:"Scrubber-info"},m("strong",null,t),m("span",{className:"Scrubber-description"}))),m("div",{className:"Scrubber-after"}),m("div",{className:"Scrubber-unread",oncreate:a,onupdate:a},r.Z.translator.trans("core.forum.post_scrubber.unread_text",{count:s}))),m("a",{className:"Scrubber-last",onclick:this.goToLast.bind(this)},m(n.Z,{name:"fas fa-angle-double-down"})," ",r.Z.translator.trans("core.forum.post_scrubber.now_link")))))}onupdate(e){super.onupdate(e),this.stream.forceUpdateScrubber&&(this.stream.forceUpdateScrubber=!1,this.stream.loadPromise.then((()=>this.updateScrubberValues({animate:!0,forceHeightChange:!0}))))}oncreate(e){super.oncreate(e),$(window).on("resize",this.handlers.onresize=this.onresize.bind(this)).resize(),this.$(".Scrubber-scrollbar").bind("click",this.onclick.bind(this)).bind("dragstart mousedown touchstart",(e=>e.preventDefault())),this.dragging=!1,this.mouseStart=0,this.indexStart=0,this.$(".Scrubber-handle").bind("mousedown touchstart",this.onmousedown.bind(this)).click((e=>e.stopPropagation())),$(document).on("mousemove touchmove",this.handlers.onmousemove=this.onmousemove.bind(this)).on("mouseup touchend",this.handlers.onmouseup=this.onmouseup.bind(this)),setTimeout((()=>this.scrollListener.start())),this.stream.loadPromise.then((()=>this.updateScrubberValues({animate:!1,forceHeightChange:!0})))}onremove(e){super.onremove(e),this.scrollListener.stop(),$(window).off("resize",this.handlers.onresize),$(document).off("mousemove touchmove",this.handlers.onmousemove).off("mouseup touchend",this.handlers.onmouseup)}updateScrubberValues(e){void 0===e&&(e={});const t=this.stream.index,s=this.stream.count(),r=this.stream.visible||1,o=this.percentPerPost(),a=this.$();a.find(".Scrubber-index").text((0,i.Z)(this.stream.sanitizeIndex(Math.max(1,t)))),a.find(".Scrubber-description").text(this.stream.description),a.toggleClass("disabled",this.stream.disabled());const n={};if(n.before=Math.max(0,o.index*Math.min(t-1,s-r)),n.handle=Math.min(100-n.before,o.visible*r),n.after=100-n.before-n.handle,e.fromScroll&&this.stream.paused||this.adjustingHeight&&!e.forceHeightChange)return;const h=e.animate?"animate":"css";this.adjustingHeight=!0;const c=[];for(const e in n){const t=a.find(".Scrubber-".concat(e));c.push(t.stop(!0,!0)[h]({height:n[e]+"%"},"fast").promise()),"animate"===h&&t.css("overflow","visible")}Promise.all(c).then((()=>this.adjustingHeight=!1))}goToFirst(){this.stream.goToFirst(),this.updateScrubberValues({animate:!0,forceHeightChange:!0})}goToLast(){this.stream.goToLast(),this.updateScrubberValues({animate:!0,forceHeightChange:!0})}onresize(){const e=this.$(),t=this.$(".Scrubber-scrollbar");t.css("max-height",$(window).height()-e.offset().top+$(window).scrollTop()-parseInt($("#app").css("padding-bottom"),10)-(e.outerHeight()-t.outerHeight()))}onmousedown(e){e.redraw=!1,this.mouseStart=e.clientY||e.originalEvent.touches[0].clientY,this.indexStart=this.stream.index,this.dragging=!0,$("body").css("cursor","move"),this.$().toggleClass("dragging",this.dragging)}onmousemove(e){if(!this.dragging)return;const t=((e.clientY||e.originalEvent.touches[0].clientY)-this.mouseStart)/this.$(".Scrubber-scrollbar").outerHeight()*100/this.percentPerPost().index||0,s=Math.min(this.indexStart+t,this.stream.count()-1);this.stream.index=Math.max(0,s),this.updateScrubberValues()}onmouseup(){if(this.$().toggleClass("dragging",this.dragging),!this.dragging)return;this.mouseStart=0,this.indexStart=0,this.dragging=!1,$("body").css("cursor",""),this.$().removeClass("open");const e=Math.floor(this.stream.index);this.stream.goToIndex(e)}onclick(e){const t=this.$(".Scrubber-scrollbar");let s=((e.pageY||e.originalEvent.touches[0].pageY)-t.offset().top+$("body").scrollTop())/t.outerHeight()*100;s-=parseFloat(t.find(".Scrubber-handle")[0].style.height)/2;let r=s/this.percentPerPost().index;r=Math.max(0,Math.min(this.stream.count()-1,r)),this.stream.goToIndex(Math.floor(r)),this.updateScrubberValues({animate:!0,forceHeightChange:!0}),this.$().removeClass("open")}percentPerPost(){const e=this.stream.count()||1,t=this.stream.visible||1,s=50/this.$(".Scrubber-scrollbar").outerHeight()*100,r=Math.max(100/e,s/t);return{index:e===t?0:(100-r*t)/(e-t),visible:r}}}flarum.reg.add("core","forum/components/PostStreamScrubber",h)}}]);
//# sourceMappingURL=PostStreamScrubber.js.map