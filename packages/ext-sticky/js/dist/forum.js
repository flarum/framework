module.exports=function(t){var n={};function r(o){if(n[o])return n[o].exports;var e=n[o]={i:o,l:!1,exports:{}};return t[o].call(e.exports,e,e.exports,r),e.l=!0,e.exports}return r.m=t,r.c=n,r.d=function(t,n,o){r.o(t,n)||Object.defineProperty(t,n,{enumerable:!0,get:o})},r.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},r.t=function(t,n){if(1&n&&(t=r(t)),8&n)return t;if(4&n&&"object"==typeof t&&t&&t.__esModule)return t;var o=Object.create(null);if(r.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:t}),2&n&&"string"!=typeof t)for(var e in t)r.d(o,e,function(n){return t[n]}.bind(null,e));return o},r.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return r.d(n,"a",n),n},r.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},r.p="",r(r.s=28)}([function(t,n){t.exports=flarum.core.compat.extend},function(t,n){t.exports=flarum.core.compat.app},function(t,n){t.exports=flarum.core.compat["models/Discussion"]},function(t,n,r){t.exports=!r(16)(function(){return 7!=Object.defineProperty({},"a",{get:function(){return 7}}).a})},function(t,n){var r=t.exports="undefined"!=typeof window&&window.Math==Math?window:"undefined"!=typeof self&&self.Math==Math?self:Function("return this")();"number"==typeof __g&&(__g=r)},function(t,n){t.exports=function(t){return"object"==typeof t?null!==t:"function"==typeof t}},function(t,n,r){var o=r(5);t.exports=function(t){if(!o(t))throw TypeError(t+" is not an object!");return t}},function(t,n){var r=t.exports={version:"2.5.7"};"number"==typeof __e&&(__e=r)},function(t,n){t.exports=flarum.core.compat["utils/DiscussionControls"]},function(t,n){t.exports=flarum.core.compat.Model},function(t,n){t.exports="constructor,hasOwnProperty,isPrototypeOf,propertyIsEnumerable,toLocaleString,toString,valueOf".split(",")},function(t,n,r){var o=r(32)("keys"),e=r(30);t.exports=function(t){return o[t]||(o[t]=e(t))}},function(t,n){var r=Math.ceil,o=Math.floor;t.exports=function(t){return isNaN(t=+t)?0:(t>0?o:r)(t)}},function(t,n,r){var o=r(38),e=r(36);t.exports=function(t){return o(e(t))}},function(t,n){var r={}.hasOwnProperty;t.exports=function(t,n){return r.call(t,n)}},function(t,n,r){var o=r(5),e=r(4).document,i=o(e)&&o(e.createElement);t.exports=function(t){return i?e.createElement(t):{}}},function(t,n){t.exports=function(t){try{return!!t()}catch(t){return!0}}},function(t,n,r){var o=r(6),e=r(45),i=r(44),c=Object.defineProperty;n.f=r(3)?Object.defineProperty:function(t,n,r){if(o(t),n=i(n,!0),o(r),e)try{return c(t,n,r)}catch(t){}if("get"in r||"set"in r)throw TypeError("Accessors not supported!");return"value"in r&&(t[n]=r.value),t}},,function(t,n){t.exports=flarum.core.compat["utils/string"]},function(t,n){t.exports=flarum.core.compat["components/DiscussionListItem"]},function(t,n){t.exports=flarum.core.compat["components/DiscussionList"]},function(t,n){t.exports=flarum.core.compat["components/Button"]},function(t,n){t.exports=flarum.core.compat["components/DiscussionPage"]},function(t,n){t.exports=flarum.core.compat["components/Badge"]},function(t,n){t.exports=flarum.core.compat["components/EventPost"]},function(t,n,r){t.exports=r(51)},,function(t,n,r){"use strict";r.r(n);var o=r(1),e=r.n(o),i=r(9),c=r.n(i),u=r(2),a=r.n(u),s=r(26),f=r.n(s);var p=r(25),l=function(t){var n,r;function o(){return t.apply(this,arguments)||this}r=t,(n=o).prototype=f()(r.prototype),n.prototype.constructor=n,n.__proto__=r;var e=o.prototype;return e.icon=function(){return"fas fa-thumbtack"},e.descriptionKey=function(){return this.props.post.content().sticky?"flarum-sticky.forum.post_stream.discussion_stickied_text":"flarum-sticky.forum.post_stream.discussion_unstickied_text"},o}(r.n(p).a),y=r(0),d=r(24),v=r.n(d);var x=r(8),b=r.n(x),h=r(23),_=r.n(h),k=r(22),O=r.n(k);var g=r(21),j=r.n(g),S=r(20),w=r.n(S),P=r(19);e.a.initializers.add("flarum-sticky",function(){e.a.postComponents.discussionStickied=l,a.a.prototype.isSticky=c.a.attribute("isSticky"),a.a.prototype.canSticky=c.a.attribute("canSticky"),Object(y.extend)(a.a.prototype,"badges",function(t){this.isSticky()&&t.add("sticky",v.a.component({type:"sticky",label:app.translator.trans("flarum-sticky.forum.badge.sticky_tooltip"),icon:"fas fa-thumbtack"}),10)}),Object(y.extend)(b.a,"moderationControls",function(t,n){n.canSticky()&&t.add("sticky",O.a.component({children:app.translator.trans(n.isSticky()?"flarum-sticky.forum.discussion_controls.unsticky_button":"flarum-sticky.forum.discussion_controls.sticky_button"),icon:"fas fa-thumbtack",onclick:this.stickyAction.bind(n)}))}),b.a.stickyAction=function(){this.save({isSticky:!this.isSticky()}).then(function(){app.current instanceof _.a&&app.current.stream.update(),m.redraw()})},Object(y.extend)(j.a.prototype,"requestParams",function(t){t.include.push("startPost")}),Object(y.extend)(w.a.prototype,"infoItems",function(t){var n=this.props.discussion;if(n.isSticky()&&!this.props.params.q&&!n.readNumber()){var r=n.startPost();if(r){var o=Object(P.truncate)(r.contentPlain(),175);t.add("excerpt",o,-100)}}})})},function(t,n,r){var o=r(4).document;t.exports=o&&o.documentElement},function(t,n){var r=0,o=Math.random();t.exports=function(t){return"Symbol(".concat(void 0===t?"":t,")_",(++r+o).toString(36))}},function(t,n){t.exports=!0},function(t,n,r){var o=r(7),e=r(4),i=e["__core-js_shared__"]||(e["__core-js_shared__"]={});(t.exports=function(t,n){return i[t]||(i[t]=void 0!==n?n:{})})("versions",[]).push({version:o.version,mode:r(31)?"pure":"global",copyright:"© 2018 Denis Pushkarev (zloirock.ru)"})},function(t,n,r){var o=r(12),e=Math.max,i=Math.min;t.exports=function(t,n){return(t=o(t))<0?e(t+n,0):i(t,n)}},function(t,n,r){var o=r(12),e=Math.min;t.exports=function(t){return t>0?e(o(t),9007199254740991):0}},function(t,n,r){var o=r(13),e=r(34),i=r(33);t.exports=function(t){return function(n,r,c){var u,a=o(n),s=e(a.length),f=i(c,s);if(t&&r!=r){for(;s>f;)if((u=a[f++])!=u)return!0}else for(;s>f;f++)if((t||f in a)&&a[f]===r)return t||f||0;return!t&&-1}}},function(t,n){t.exports=function(t){if(void 0==t)throw TypeError("Can't call method on  "+t);return t}},function(t,n){var r={}.toString;t.exports=function(t){return r.call(t).slice(8,-1)}},function(t,n,r){var o=r(37);t.exports=Object("z").propertyIsEnumerable(0)?Object:function(t){return"String"==o(t)?t.split(""):Object(t)}},function(t,n,r){var o=r(14),e=r(13),i=r(35)(!1),c=r(11)("IE_PROTO");t.exports=function(t,n){var r,u=e(t),a=0,s=[];for(r in u)r!=c&&o(u,r)&&s.push(r);for(;n.length>a;)o(u,r=n[a++])&&(~i(s,r)||s.push(r));return s}},function(t,n,r){var o=r(39),e=r(10);t.exports=Object.keys||function(t){return o(t,e)}},function(t,n,r){var o=r(17),e=r(6),i=r(40);t.exports=r(3)?Object.defineProperties:function(t,n){e(t);for(var r,c=i(n),u=c.length,a=0;u>a;)o.f(t,r=c[a++],n[r]);return t}},function(t,n,r){var o=r(6),e=r(41),i=r(10),c=r(11)("IE_PROTO"),u=function(){},a=function(){var t,n=r(15)("iframe"),o=i.length;for(n.style.display="none",r(29).appendChild(n),n.src="javascript:",(t=n.contentWindow.document).open(),t.write("<script>document.F=Object<\/script>"),t.close(),a=t.F;o--;)delete a.prototype[i[o]];return a()};t.exports=Object.create||function(t,n){var r;return null!==t?(u.prototype=o(t),r=new u,u.prototype=null,r[c]=t):r=a(),void 0===n?r:e(r,n)}},function(t,n){t.exports=function(t,n){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:n}}},function(t,n,r){var o=r(5);t.exports=function(t,n){if(!o(t))return t;var r,e;if(n&&"function"==typeof(r=t.toString)&&!o(e=r.call(t)))return e;if("function"==typeof(r=t.valueOf)&&!o(e=r.call(t)))return e;if(!n&&"function"==typeof(r=t.toString)&&!o(e=r.call(t)))return e;throw TypeError("Can't convert object to primitive value")}},function(t,n,r){t.exports=!r(3)&&!r(16)(function(){return 7!=Object.defineProperty(r(15)("div"),"a",{get:function(){return 7}}).a})},function(t,n,r){var o=r(17),e=r(43);t.exports=r(3)?function(t,n,r){return o.f(t,n,e(1,r))}:function(t,n,r){return t[n]=r,t}},function(t,n){t.exports=function(t){if("function"!=typeof t)throw TypeError(t+" is not a function!");return t}},function(t,n,r){var o=r(47);t.exports=function(t,n,r){if(o(t),void 0===n)return t;switch(r){case 1:return function(r){return t.call(n,r)};case 2:return function(r,o){return t.call(n,r,o)};case 3:return function(r,o,e){return t.call(n,r,o,e)}}return function(){return t.apply(n,arguments)}}},function(t,n,r){var o=r(4),e=r(7),i=r(48),c=r(46),u=r(14),a=function(t,n,r){var s,f,p,l=t&a.F,y=t&a.G,d=t&a.S,m=t&a.P,v=t&a.B,x=t&a.W,b=y?e:e[n]||(e[n]={}),h=b.prototype,_=y?o:d?o[n]:(o[n]||{}).prototype;for(s in y&&(r=n),r)(f=!l&&_&&void 0!==_[s])&&u(b,s)||(p=f?_[s]:r[s],b[s]=y&&"function"!=typeof _[s]?r[s]:v&&f?i(p,o):x&&_[s]==p?function(t){var n=function(n,r,o){if(this instanceof t){switch(arguments.length){case 0:return new t;case 1:return new t(n);case 2:return new t(n,r)}return new t(n,r,o)}return t.apply(this,arguments)};return n.prototype=t.prototype,n}(p):m&&"function"==typeof p?i(Function.call,p):p,m&&((b.virtual||(b.virtual={}))[s]=p,t&a.R&&h&&!h[s]&&c(h,s,p)))};a.F=1,a.G=2,a.S=4,a.P=8,a.B=16,a.W=32,a.U=64,a.R=128,t.exports=a},function(t,n,r){var o=r(49);o(o.S,"Object",{create:r(42)})},function(t,n,r){r(50);var o=r(7).Object;t.exports=function(t,n){return o.create(t,n)}}]);
//# sourceMappingURL=forum.js.map