module.exports=function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=14)}([function(e,t){e.exports=flarum.core.compat.extend},function(e,t){e.exports=flarum.core.compat.app},function(e,t){e.exports=flarum.core.compat["components/DiscussionPage"]},function(e,t){e.exports=flarum.core.compat["components/PostStream"]},function(e,t){e.exports=flarum.core.compat.ForumApplication},function(e,t){e.exports=flarum.core.compat["components/Composer"]},function(e,t){e.exports=flarum.core.compat["components/ModalManager"]},function(e,t){e.exports=flarum.core.compat["components/PostMeta"]},function(e,t,n){!function(t){"use strict";if("undefined"!=typeof window){var n=!0,o=10,r="",i=0,a="",c=null,u="",s=!1,l={resize:1,click:1},d=128,f=!0,m=1,p="bodyOffset",g=p,h=!0,v="",y={},b=32,w=null,T=!1,O="[iFrameSizer]",E=O.length,S="",x={max:1,min:1,bodyScroll:1,documentElementScroll:1},M="child",I=!0,N=window.parent,C="*",k=0,A=!1,P=null,z=16,F=1,L="scroll",R=L,j=window,D=function(){ue("MessageCallback function not defined")},q=function(){},H=function(){},W={height:function(){return ue("Custom height calculation function not defined"),document.documentElement.offsetHeight},width:function(){return ue("Custom width calculation function not defined"),document.body.scrollWidth}},_={},B=!1;try{var V=Object.create({},{passive:{get:function(){B=!0}},once:{get:function(){!0}}});window.addEventListener("test",ne,V),window.removeEventListener("test",ne,V)}catch(e){}var J,U,$,K,Q,X,Y,G=Date.now||function(){return(new Date).getTime()},Z={bodyOffset:function(){return document.body.offsetHeight+be("marginTop")+be("marginBottom")},offset:function(){return Z.bodyOffset()},bodyScroll:function(){return document.body.scrollHeight},custom:function(){return W.height()},documentElementOffset:function(){return document.documentElement.offsetHeight},documentElementScroll:function(){return document.documentElement.scrollHeight},max:function(){return Math.max.apply(null,Te(Z))},min:function(){return Math.min.apply(null,Te(Z))},grow:function(){return Z.max()},lowestElement:function(){return Math.max(Z.bodyOffset()||Z.documentElementOffset(),we("bottom",Ee()))},taggedElement:function(){return Oe("bottom","data-iframe-height")}},ee={bodyScroll:function(){return document.body.scrollWidth},bodyOffset:function(){return document.body.offsetWidth},custom:function(){return W.width()},documentElementScroll:function(){return document.documentElement.scrollWidth},documentElementOffset:function(){return document.documentElement.offsetWidth},scroll:function(){return Math.max(ee.bodyScroll(),ee.documentElementScroll())},max:function(){return Math.max.apply(null,Te(ee))},min:function(){return Math.min.apply(null,Te(ee))},rightMostElement:function(){return we("right",Ee())},taggedElement:function(){return Oe("right","data-iframe-width")}},te=(J=Se,Q=null,X=0,Y=function(){X=G(),Q=null,K=J.apply(U,$),Q||(U=$=null)},function(){var e=G();X||(X=e);var t=z-(e-X);return U=this,$=arguments,t<=0||t>z?(Q&&(clearTimeout(Q),Q=null),X=e,K=J.apply(U,$),Q||(U=$=null)):Q||(Q=setTimeout(Y,t)),K});oe(window,"message",ke),oe(window,"readystatechange",Ae),Ae()}function ne(){}function oe(e,t,n,o){"addEventListener"in window?e.addEventListener(t,n,!!B&&(o||{})):"attachEvent"in window&&e.attachEvent("on"+t,n)}function re(e,t,n){"removeEventListener"in window?e.removeEventListener(t,n,!1):"detachEvent"in window&&e.detachEvent("on"+t,n)}function ie(e){return e.charAt(0).toUpperCase()+e.slice(1)}function ae(e){return O+"["+S+"] "+e}function ce(e){T&&"object"==typeof window.console&&console.log(ae(e))}function ue(e){"object"==typeof window.console&&console.warn(ae(e))}function se(){var e;!function(){function e(e){return"true"===e}var o=v.substr(E).split(":");S=o[0],i=t!==o[1]?Number(o[1]):i,s=t!==o[2]?e(o[2]):s,T=t!==o[3]?e(o[3]):T,b=t!==o[4]?Number(o[4]):b,n=t!==o[6]?e(o[6]):n,a=o[7],g=t!==o[8]?o[8]:g,r=o[9],u=o[10],k=t!==o[11]?Number(o[11]):k,y.enable=t!==o[12]&&e(o[12]),M=t!==o[13]?o[13]:M,R=t!==o[14]?o[14]:R}(),ce("Initialising iFrame ("+location.href+")"),function(){function e(e,t){return"function"==typeof e&&(ce("Setup custom "+t+"CalcMethod"),W[t]=e,e="custom"),e}"iFrameResizer"in window&&Object===window.iFrameResizer.constructor&&(t=window.iFrameResizer,ce("Reading data from page: "+JSON.stringify(t)),D="messageCallback"in t?t.messageCallback:D,q="readyCallback"in t?t.readyCallback:q,C="targetOrigin"in t?t.targetOrigin:C,g="heightCalculationMethod"in t?t.heightCalculationMethod:g,R="widthCalculationMethod"in t?t.widthCalculationMethod:R,g=e(g,"height"),R=e(R,"width"));var t;ce("TargetOrigin for parent set to: "+C)}(),function(){t===a&&(a=i+"px");le("margin",function(e,t){-1!==t.indexOf("-")&&(ue("Negative CSS value ignored for "+e),t="");return t}("margin",a))}(),le("background",r),le("padding",u),(e=document.createElement("div")).style.clear="both",e.style.display="block",document.body.appendChild(e),pe(),ge(),document.documentElement.style.height="",document.body.style.height="",ce('HTML & body height set to "auto"'),ce("Enable public methods"),j.parentIFrame={autoResize:function(e){return!0===e&&!1===n?(n=!0,he()):!1===e&&!0===n&&(n=!1,ve()),n},close:function(){Ce(0,0,"close"),ce("Disable outgoing messages"),I=!1,ce("Remove event listener: Message"),re(window,"message",ke),!0===n&&ve()},getId:function(){return S},getPageInfo:function(e){"function"==typeof e?(H=e,Ce(0,0,"pageInfo")):(H=function(){},Ce(0,0,"pageInfoStop"))},moveToAnchor:function(e){y.findTarget(e)},reset:function(){Ne("parentIFrame.reset")},scrollTo:function(e,t){Ce(t,e,"scrollTo")},scrollToOffset:function(e,t){Ce(t,e,"scrollToOffset")},sendMessage:function(e,t){Ce(0,0,"message",JSON.stringify(e),t)},setHeightCalculationMethod:function(e){g=e,pe()},setWidthCalculationMethod:function(e){R=e,ge()},setTargetOrigin:function(e){ce("Set targetOrigin: "+e),C=e},size:function(e,t){var n=(e||"")+(t?","+t:"");xe("size","parentIFrame.size("+n+")",e,t)}},he(),y=function(){function e(e){var n=e.getBoundingClientRect(),o={x:window.pageXOffset!==t?window.pageXOffset:document.documentElement.scrollLeft,y:window.pageYOffset!==t?window.pageYOffset:document.documentElement.scrollTop};return{x:parseInt(n.left,10)+parseInt(o.x,10),y:parseInt(n.top,10)+parseInt(o.y,10)}}function n(n){var o=n.split("#")[1]||n,r=decodeURIComponent(o),i=document.getElementById(r)||document.getElementsByName(r)[0];t!==i?function(t){var n=e(t);ce("Moving to in page link (#"+o+") at x: "+n.x+" y: "+n.y),Ce(n.y,n.x,"scrollToOffset")}(i):(ce("In page link (#"+o+") not found in iFrame, so sending to parent"),Ce(0,0,"inPageLink","#"+o))}function o(){""!==location.hash&&"#"!==location.hash&&n(location.href)}y.enable?Array.prototype.forEach&&document.querySelectorAll?(ce("Setting up location.hash handlers"),Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'),function(e){"#"!==e.getAttribute("href")&&oe(e,"click",function(e){e.preventDefault(),n(this.getAttribute("href"))})}),oe(window,"hashchange",o),setTimeout(o,d)):ue("In page linking not fully supported in this browser! (See README.md for IE8 workaround)"):ce("In page linking not enabled");return{findTarget:n}}(),xe("init","Init message from host page"),q()}function le(e,n){t!==n&&""!==n&&"null"!==n&&(document.body.style[e]=n,ce("Body "+e+' set to "'+n+'"'))}function de(e){var t={add:function(t){function n(){xe(e.eventName,e.eventType)}_[t]=n,oe(window,t,n,{passive:!0})},remove:function(e){var t=_[e];delete _[e],re(window,e,t)}};e.eventNames&&Array.prototype.map?(e.eventName=e.eventNames[0],e.eventNames.map(t[e.method])):t[e.method](e.eventName),ce(ie(e.method)+" event listener: "+e.eventType)}function fe(e){de({method:e,eventType:"Animation Start",eventNames:["animationstart","webkitAnimationStart"]}),de({method:e,eventType:"Animation Iteration",eventNames:["animationiteration","webkitAnimationIteration"]}),de({method:e,eventType:"Animation End",eventNames:["animationend","webkitAnimationEnd"]}),de({method:e,eventType:"Input",eventName:"input"}),de({method:e,eventType:"Mouse Up",eventName:"mouseup"}),de({method:e,eventType:"Mouse Down",eventName:"mousedown"}),de({method:e,eventType:"Orientation Change",eventName:"orientationchange"}),de({method:e,eventType:"Print",eventName:["afterprint","beforeprint"]}),de({method:e,eventType:"Ready State Change",eventName:"readystatechange"}),de({method:e,eventType:"Touch Start",eventName:"touchstart"}),de({method:e,eventType:"Touch End",eventName:"touchend"}),de({method:e,eventType:"Touch Cancel",eventName:"touchcancel"}),de({method:e,eventType:"Transition Start",eventNames:["transitionstart","webkitTransitionStart","MSTransitionStart","oTransitionStart","otransitionstart"]}),de({method:e,eventType:"Transition Iteration",eventNames:["transitioniteration","webkitTransitionIteration","MSTransitionIteration","oTransitionIteration","otransitioniteration"]}),de({method:e,eventType:"Transition End",eventNames:["transitionend","webkitTransitionEnd","MSTransitionEnd","oTransitionEnd","otransitionend"]}),"child"===M&&de({method:e,eventType:"IFrame Resized",eventName:"resize"})}function me(e,t,n,o){return t!==e&&(e in n||(ue(e+" is not a valid option for "+o+"CalculationMethod."),e=t),ce(o+' calculation method set to "'+e+'"')),e}function pe(){g=me(g,p,Z,"height")}function ge(){R=me(R,L,ee,"width")}function he(){var e;!0===n?(fe("add"),e=0>b,window.MutationObserver||window.WebKitMutationObserver?e?ye():c=function(){function e(e){function t(e){!1===e.complete&&(ce("Attach listeners to "+e.src),e.addEventListener("load",r,!1),e.addEventListener("error",i,!1),u.push(e))}"attributes"===e.type&&"src"===e.attributeName?t(e.target):"childList"===e.type&&Array.prototype.forEach.call(e.target.querySelectorAll("img"),t)}function n(e){ce("Remove listeners from "+e.src),e.removeEventListener("load",r,!1),e.removeEventListener("error",i,!1),function(e){u.splice(u.indexOf(e),1)}(e)}function o(e,o,r){n(e.target),xe(o,r+": "+e.target.src,t,t)}function r(e){o(e,"imageLoad","Image loaded")}function i(e){o(e,"imageLoadFailed","Image load failed")}function a(t){xe("mutationObserver","mutationObserver: "+t[0].target+" "+t[0].type),t.forEach(e)}var c,u=[],s=window.MutationObserver||window.WebKitMutationObserver,l=(c=document.querySelector("body"),l=new s(a),ce("Create body MutationObserver"),l.observe(c,{attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0}),l);return{disconnect:function(){"disconnect"in l&&(ce("Disconnect body MutationObserver"),l.disconnect(),u.forEach(n))}}}():(ce("MutationObserver not supported in this browser!"),ye())):ce("Auto Resize disabled")}function ve(){fe("remove"),null!==c&&c.disconnect(),clearInterval(w)}function ye(){0!==b&&(ce("setInterval: "+b+"ms"),w=setInterval(function(){xe("interval","setInterval: "+b)},Math.abs(b)))}function be(e,t){var n=0;return t=t||document.body,n="defaultView"in document&&"getComputedStyle"in document.defaultView?null!==(n=document.defaultView.getComputedStyle(t,null))?n[e]:0:function(e){if(/^\d+(px)?$/i.test(e))return parseInt(e,o);var n=t.style.left,r=t.runtimeStyle.left;return t.runtimeStyle.left=t.currentStyle.left,t.style.left=e||0,e=t.style.pixelLeft,t.style.left=n,t.runtimeStyle.left=r,e}(t.currentStyle[e]),parseInt(n,o)}function we(e,t){for(var n=t.length,o=0,r=0,i=ie(e),a=G(),c=0;c<n;c++)(o=t[c].getBoundingClientRect()[e]+be("margin"+i,t[c]))>r&&(r=o);return a=G()-a,ce("Parsed "+n+" HTML elements"),ce("Element position calculated in "+a+"ms"),function(e){e>z/2&&ce("Event throttle increased to "+(z=2*e)+"ms")}(a),r}function Te(e){return[e.bodyOffset(),e.bodyScroll(),e.documentElementOffset(),e.documentElementScroll()]}function Oe(e,t){var n=document.querySelectorAll("["+t+"]");return 0===n.length&&(ue("No tagged elements ("+t+") found on page"),document.querySelectorAll("body *")),we(e,n)}function Ee(){return document.querySelectorAll("body *")}function Se(e,n,o,r){var i,a;!function(){function e(e,t){return!(Math.abs(e-t)<=k)}return i=t!==o?o:Z[g](),a=t!==r?r:ee[R](),e(m,i)||s&&e(F,a)}()&&"init"!==e?e in{init:1,interval:1,size:1}||!(g in x||s&&R in x)?e in{interval:1}||ce("No change in size detected"):Ne(n):(Me(),Ce(m=i,F=a,e))}function xe(e,t,n,o){A&&e in l?ce("Trigger event cancelled: "+e):(e in{reset:1,resetPage:1,init:1}||ce("Trigger event: "+t),"init"===e?Se(e,t,n,o):te(e,t,n,o))}function Me(){A||(A=!0,ce("Trigger event lock on")),clearTimeout(P),P=setTimeout(function(){A=!1,ce("Trigger event lock off"),ce("--")},d)}function Ie(e){m=Z[g](),F=ee[R](),Ce(m,F,e)}function Ne(e){var t=g;g=p,ce("Reset trigger event: "+e),Me(),Ie("reset"),g=t}function Ce(e,n,o,r,i){var a;!0===I&&(t===i?i=C:ce("Message targetOrigin: "+i),ce("Sending message to host page ("+(a=S+":"+e+":"+n+":"+o+(t!==r?":"+r:""))+")"),N.postMessage(O+a,i))}function ke(t){var n={init:function(){v=t.data,N=t.source,se(),f=!1,setTimeout(function(){h=!1},d)},reset:function(){h?ce("Page reset ignored by init"):(ce("Page size reset by host page"),Ie("resetPage"))},resize:function(){xe("resizeParent","Parent window requested size check")},moveToAnchor:function(){y.findTarget(r())},inPageLink:function(){this.moveToAnchor()},pageInfo:function(){var e=r();ce("PageInfoFromParent called from parent: "+e),H(JSON.parse(e)),ce(" --")},message:function(){var e=r();ce("MessageCallback called from parent: "+e),D(JSON.parse(e)),ce(" --")}};function o(){return t.data.split("]")[1].split(":")[0]}function r(){return t.data.substr(t.data.indexOf(":")+1)}function i(){return t.data.split(":")[2]in{true:1,false:1}}function a(){var r=o();r in n?n[r]():!e.exports&&"iFrameResize"in window||"jQuery"in window&&"iFrameResize"in window.jQuery.prototype||i()||ue("Unexpected message ("+t.data+")")}O===(""+t.data).substr(0,E)&&(!1===f?a():i()?n.init():ce('Ignored message of type "'+o()+'". Received before initialization.'))}function Ae(){"loading"!==document.readyState&&window.parent.postMessage("[iFrameResizerChild]Ready","*")}}()},function(e,t){e.exports=flarum.core.compat["components/AlertManager"]},function(e,t){e.exports=flarum.core.compat["utils/mapRoutes"]},function(e,t){e.exports=flarum.core.compat["utils/Pane"]},function(e,t){e.exports=flarum.core.compat["utils/Drawer"]},function(e,t){e.exports=flarum.core.compat["utils/ScrollListener"]},function(e,t,n){"use strict";n.r(t);n(8);var o=n(0),r=n(1),i=n.n(r),a=n(4),c=n.n(a),u=n(5),s=n.n(u),l=n(3),d=n.n(l),f=n(6),p=n.n(f),g=(n(9),n(7)),h=n.n(g),v=(n(10),n(11),n(12),n(13),n(2)),y=n.n(v);Object(o.extend)(c.a.prototype,"mount",function(){m.route.param("hideFirstPost")&&Object(o.extend)(d.a.prototype,"view",function(e){1===e.children[0].attrs["data-number"]&&e.children.splice(0,1)})}),m.route.mode="pathname",Object(o.override)(m,"route",function(e,t,n,o,r){if(1===arguments.length);else if(4===arguments.length&&"string"==typeof n);else if(t.addEventListener||t.attachEvent)return t.href=r.attrs.href.replace("/embed","/d"),void(t.target="_blank");return e.apply(this,Array.prototype.slice.call(arguments,1))}),Object(o.override)(h.a.prototype,"getPermalink",function(e,t){return e(t).replace("/embed","/d")}),i.a.pageInfo=m.prop({});var b=function(){var e=i.a.pageInfo();this.$().css("top",Math.max(0,e.scrollTop-e.offsetTop))};Object(o.extend)(p.a.prototype,"show",b),Object(o.extend)(s.a.prototype,"show",b),window.iFrameResizer={readyCallback:function(){window.parentIFrame.getPageInfo(i.a.pageInfo)}},Object(o.extend)(d.a.prototype,"goToNumber",function(e,t){if("reply"===t&&"parentIFrame"in window&&i.a.composer.isFullScreen()){var n=this.$(".PostStream-item:last").offset().top;window.parentIFrame.scrollToOffset(0,n)}}),Object(o.extend)(y.a.prototype,"sidebarItems",function(e){e.remove("scrubber");var t=this.discussion.replyCount();e.add("replies",m("h3",null,m("a",{href:i.a.route.discussion(this.discussion).replace("/embed","/d"),config:m.route},t," comment",1==t?"":"s")),100);var n=e.get("controls").props;n.className=n.className.replace("App-primaryControl","")}),delete i.a.routes["index.filter"],i.a.routes.discussion={path:"/embed/:id",component:y.a.component()},i.a.routes["discussion.near"]={path:"/embed/:id/:near",component:y.a.component()}}]);
//# sourceMappingURL=forum.js.map