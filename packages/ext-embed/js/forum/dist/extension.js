/*! iFrame Resizer (iframeSizer.contentWindow.min.js) - v3.2.0 - 2015-09-04
 *  Desc: Include this file in any page being loaded into an iframe
 *        to force the iframe to resize to the content size.
 *  Requires: iframeResizer.min.js on host page.
 *  Copyright: (c) 2015 David J. Bradshaw - dave@bradshaw.net
 *  License: MIT
 */

!function(a){"use strict";function b(b,c,d){"addEventListener"in a?b.addEventListener(c,d,!1):"attachEvent"in a&&b.attachEvent("on"+c,d)}function c(b,c,d){"removeEventListener"in a?b.removeEventListener(c,d,!1):"detachEvent"in a&&b.detachEvent("on"+c,d)}function d(a){return a.charAt(0).toUpperCase()+a.slice(1)}function e(a){var b,c,d,e=null,f=0,g=function(){f=Ba(),e=null,d=a.apply(b,c),e||(b=c=null)};return function(){var h=Ba();f||(f=h);var i=va-(h-f);return b=this,c=arguments,0>=i||i>va?(e&&(clearTimeout(e),e=null),f=h,d=a.apply(b,c),e||(b=c=null)):e||(e=setTimeout(g,i)),d}}function f(a){return ka+"["+ma+"] "+a}function g(b){ja&&"object"==typeof a.console&&console.log(f(b))}function h(b){"object"==typeof a.console&&console.warn(f(b))}function i(){j(),g("Initialising iFrame ("+location.href+")"),k(),n(),m("background",V),m("padding",Z),z(),s(),t(),o(),B(),u(),ha=A(),M("init","Init message from host page"),Aa()}function j(){function a(a){return"true"===a?!0:!1}var b=ga.substr(la).split(":");ma=b[0],W=void 0!==b[1]?Number(b[1]):W,$=void 0!==b[2]?a(b[2]):$,ja=void 0!==b[3]?a(b[3]):ja,ia=void 0!==b[4]?Number(b[4]):ia,T=void 0!==b[6]?a(b[6]):T,X=b[7],ea=void 0!==b[8]?b[8]:ea,V=b[9],Z=b[10],sa=void 0!==b[11]?Number(b[11]):sa,ha.enable=void 0!==b[12]?a(b[12]):!1,oa=void 0!==b[13]?b[13]:oa,ya=void 0!==b[14]?b[14]:ya}function k(){function b(){var b=a.iFrameResizer;g("Reading data from page: "+JSON.stringify(b)),za=void 0!==b.messageCallback?b.messageCallback:za,Aa=void 0!==b.readyCallback?b.readyCallback:Aa,ra=void 0!==b.targetOrigin?b.targetOrigin:ra,ea=void 0!==b.heightCalculationMethod?b.heightCalculationMethod:ea,ya=void 0!==b.widthCalculationMethod?b.widthCalculationMethod:ya}"iFrameResizer"in a&&Object===a.iFrameResizer.constructor&&b()}function l(a,b){return-1!==b.indexOf("-")&&(h("Negative CSS value ignored for "+a),b=""),b}function m(a,b){void 0!==b&&""!==b&&"null"!==b&&(document.body.style[a]=b,g("Body "+a+' set to "'+b+'"'))}function n(){void 0===X&&(X=W+"px"),l("margin",X),m("margin",X)}function o(){document.documentElement.style.height="",document.body.style.height="",g('HTML & body height set to "auto"')}function p(e){function f(){M(e.eventName,e.eventType)}var h={add:function(c){b(a,c,f)},remove:function(b){c(a,b,f)}};e.eventNames&&Array.prototype.map?(e.eventName=e.eventNames[0],e.eventNames.map(h[e.method])):h[e.method](e.eventName),g(d(e.method)+" event listener: "+e.eventType)}function q(a){p({method:a,eventType:"Animation Start",eventNames:["animationstart","webkitAnimationStart"]}),p({method:a,eventType:"Animation Iteration",eventNames:["animationiteration","webkitAnimationIteration"]}),p({method:a,eventType:"Animation End",eventNames:["animationend","webkitAnimationEnd"]}),p({method:a,eventType:"Orientation Change",eventName:"orientationchange"}),p({method:a,eventType:"Input",eventName:"input"}),p({method:a,eventType:"Print",eventName:["afterprint","beforeprint"]}),p({method:a,eventType:"Transition End",eventNames:["transitionend","webkitTransitionEnd","MSTransitionEnd","oTransitionEnd","otransitionend"]}),p({method:a,eventType:"Mouse Up",eventName:"mouseup"}),p({method:a,eventType:"Mouse Down",eventName:"mousedown"}),"child"===oa&&p({method:a,eventType:"IFrame Resized",eventName:"resize"})}function r(a,b,c,d){b!==a&&(a in c||(h(a+" is not a valid option for "+d+"CalculationMethod."),a=b),g(d+' calculation method set to "'+a+'"'))}function s(){r(ea,da,Ca,"height")}function t(){r(ya,xa,Da,"width")}function u(){!0===T?(q("add"),E()):g("Auto Resize disabled")}function v(){g("Disable outgoing messages"),pa=!1}function w(){g("Remove event listener: Message"),c(a,"message",R)}function x(){null!==Y&&Y.disconnect()}function y(){v(),w(),q("remove"),x()}function z(){var a=document.createElement("div");a.style.clear="both",a.style.display="block",document.body.appendChild(a)}function A(){function c(){return{x:void 0!==a.pageXOffset?a.pageXOffset:document.documentElement.scrollLeft,y:void 0!==a.pageYOffset?a.pageYOffset:document.documentElement.scrollTop}}function d(a){var b=a.getBoundingClientRect(),d=c();return{x:parseInt(b.left,10)+parseInt(d.x,10),y:parseInt(b.top,10)+parseInt(d.y,10)}}function e(a){function b(a){var b=d(a);g("Moving to in page link (#"+c+") at x: "+b.x+" y: "+b.y),Q(b.y,b.x,"scrollToOffset")}var c=a.split("#")[1]||a,e=decodeURIComponent(c),f=document.getElementById(e)||document.getElementsByName(e)[0];void 0!==f?b(f):(g("In page link (#"+c+") not found in iFrame, so sending to parent"),Q(0,0,"inPageLink","#"+c))}function f(){""!==location.hash&&"#"!==location.hash&&e(location.href)}function i(){function a(a){function c(a){a.preventDefault(),e(this.getAttribute("href"))}"#"!==a.getAttribute("href")&&b(a,"click",c)}Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'),a)}function j(){b(a,"hashchange",f)}function k(){setTimeout(f,aa)}function l(){Array.prototype.forEach&&document.querySelectorAll?(g("Setting up location.hash handlers"),i(),j(),k()):h("In page linking not fully supported in this browser! (See README.md for IE8 workaround)")}return ha.enable?l():g("In page linking not enabled"),{findTarget:e}}function B(){g("Enable public methods"),a.parentIFrame={close:function(){Q(0,0,"close"),y()},getId:function(){return ma},moveToAnchor:function(a){ha.findTarget(a)},reset:function(){P("parentIFrame.reset")},scrollTo:function(a,b){Q(b,a,"scrollTo")},scrollToOffset:function(a,b){Q(b,a,"scrollToOffset")},sendMessage:function(a,b){Q(0,0,"message",JSON.stringify(a),b)},setHeightCalculationMethod:function(a){ea=a,s()},setWidthCalculationMethod:function(a){ya=a,t()},setTargetOrigin:function(a){g("Set targetOrigin: "+a),ra=a},size:function(a,b){var c=""+(a?a:"")+(b?","+b:"");N(),M("size","parentIFrame.size("+c+")",a,b)}}}function C(){0!==ia&&(g("setInterval: "+ia+"ms"),setInterval(function(){M("interval","setInterval: "+ia)},Math.abs(ia)))}function D(){function b(a){function b(a){!1===a.complete&&(g("Attach listeners to "+a.src),a.addEventListener("load",f,!1),a.addEventListener("error",h,!1),k.push(a))}"attributes"===a.type&&"src"===a.attributeName?b(a.target):"childList"===a.type&&Array.prototype.forEach.call(a.target.querySelectorAll("img"),b)}function c(a){k.splice(k.indexOf(a),1)}function d(a){g("Remove listeners from "+a.src),a.removeEventListener("load",f,!1),a.removeEventListener("error",h,!1),c(a)}function e(a,b,c){d(a.target),M(b,c+": "+a.target.src,void 0,void 0)}function f(a){e(a,"imageLoad","Image loaded")}function h(a){e(a,"imageLoadFailed","Image load failed")}function i(a){M("mutationObserver","mutationObserver: "+a[0].target+" "+a[0].type),a.forEach(b)}function j(){var a=document.querySelector("body"),b={attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0};return m=new l(i),g("Enable MutationObserver"),m.observe(a,b),m}var k=[],l=a.MutationObserver||a.WebKitMutationObserver,m=j();return{disconnect:function(){"disconnect"in m&&(g("Disconnect MutationObserver"),m.disconnect(),k.forEach(d))}}}function E(){var b=0>ia;a.MutationObserver||a.WebKitMutationObserver?b?C():Y=D():(g("MutationObserver not supported in this browser!"),C())}function F(a){function b(a){var b=/^\d+(px)?$/i;if(b.test(a))return parseInt(a,U);var d=c.style.left,e=c.runtimeStyle.left;return c.runtimeStyle.left=c.currentStyle.left,c.style.left=a||0,a=c.style.pixelLeft,c.style.left=d,c.runtimeStyle.left=e,a}var c=document.body,d=0;return"defaultView"in document&&"getComputedStyle"in document.defaultView?(d=document.defaultView.getComputedStyle(c,null),d=null!==d?d[a]:0):d=b(c.currentStyle[a]),parseInt(d,U)}function G(a){a>va/2&&(va=2*a,g("Event throttle increased to "+va+"ms"))}function H(a,b){for(var c=b.length,e=0,f=0,h=d(a),i=Ba(),j=0;c>j;j++)e=b[j].getBoundingClientRect()[a]+F("margin"+h),e>f&&(f=e);return i=Ba()-i,g("Parsed "+c+" HTML elements"),g("Element position calculated in "+i+"ms"),G(i),f}function I(a){return[a.bodyOffset(),a.bodyScroll(),a.documentElementOffset(),a.documentElementScroll()]}function J(a,b){function c(){return h("No tagged elements ("+b+") found on page"),ca}var d=document.querySelectorAll("["+b+"]");return 0===d.length?c():H(a,d)}function K(){return document.querySelectorAll("body *")}function L(a,b,c,d){function e(){ca=l,wa=m,Q(ca,wa,a)}function f(){function a(a,b){var c=Math.abs(a-b)<=sa;return!c}return l=void 0!==c?c:Ca[ea](),m=void 0!==d?d:Da[ya](),a(ca,l)||$&&a(wa,m)}function h(){return!(a in{init:1,interval:1,size:1})}function i(){return ea in na||$&&ya in na}function j(){g("No change in size detected")}function k(){h()&&i()?P(b):a in{interval:1}||j()}var l,m;f()||"init"===a?(N(),e()):k()}function M(a,b,c,d){function e(){a in{reset:1,resetPage:1,init:1}||g("Trigger event: "+b)}function f(){return ta&&a in _}f()?g("Trigger event cancelled: "+a):(e(),Ea(a,b,c,d))}function N(){ta||(ta=!0,g("Trigger event lock on")),clearTimeout(ua),ua=setTimeout(function(){ta=!1,g("Trigger event lock off"),g("--")},aa)}function O(a){ca=Ca[ea](),wa=Da[ya](),Q(ca,wa,a)}function P(a){var b=ea;ea=da,g("Reset trigger event: "+a),N(),O("reset"),ea=b}function Q(a,b,c,d,e){function f(){void 0===e?e=ra:g("Message targetOrigin: "+e)}function h(){var f=a+":"+b,h=ma+":"+f+":"+c+(void 0!==d?":"+d:"");g("Sending message to host page ("+h+")"),qa.postMessage(ka+h,e)}!0===pa&&(f(),h())}function R(b){function c(){return ka===(""+b.data).substr(0,la)}function d(){ga=b.data,qa=b.source,i(),ba=!1,setTimeout(function(){fa=!1},aa)}function e(){fa?g("Page reset ignored by init"):(g("Page size reset by host page"),O("resetPage"))}function f(){M("resizeParent","Parent window requested size check")}function h(){var a=k();ha.findTarget(a)}function j(){return b.data.split("]")[1].split(":")[0]}function k(){return b.data.substr(b.data.indexOf(":")+1)}function l(){return"iFrameResize"in a}function m(){var a=k();g("MessageCallback called from parent: "+a),za(JSON.parse(a)),g(" --")}function n(){return b.data.split(":")[2]in{"true":1,"false":1}}function o(){switch(j()){case"reset":e();break;case"resize":f();break;case"moveToAnchor":h();break;case"message":m();break;default:!l()&&!n()}}function p(){!1===ba?o():n()?d():g('Ignored message of type "'+j()+'". Received before initialization.')}c()&&p()}function S(){"loading"!==document.readyState&&a.parent.postMessage("[iFrameResizerChild]Ready","*")}var T=!0,U=10,V="",W=0,X="",Y=null,Z="",$=!1,_={resize:1,click:1},aa=128,ba=!0,ca=1,da="bodyOffset",ea=da,fa=!0,ga="",ha={},ia=32,ja=!1,ka="[iFrameSizer]",la=ka.length,ma="",na={max:1,min:1,bodyScroll:1,documentElementScroll:1},oa="child",pa=!0,qa=a.parent,ra="*",sa=0,ta=!1,ua=null,va=0,wa=1,xa="scroll",ya=xa,za=function(){h("MessageCallback function not defined")},Aa=function(){},Ba=Date.now||function(){return(new Date).getTime()},Ca={bodyOffset:function(){return document.body.offsetHeight+F("marginTop")+F("marginBottom")},offset:function(){return Ca.bodyOffset()},bodyScroll:function(){return document.body.scrollHeight},documentElementOffset:function(){return document.documentElement.offsetHeight},documentElementScroll:function(){return document.documentElement.scrollHeight},max:function(){return Math.max.apply(null,I(Ca))},min:function(){return Math.min.apply(null,I(Ca))},grow:function(){return Ca.max()},lowestElement:function(){return Math.max(Ca.bodyOffset(),H("bottom",K()))},taggedElement:function(){return J("bottom","data-iframe-height")}},Da={bodyScroll:function(){return document.body.scrollWidth},bodyOffset:function(){return document.body.offsetWidth},documentElementScroll:function(){return document.documentElement.scrollWidth},documentElementOffset:function(){return document.documentElement.offsetWidth},scroll:function(){return Math.max(Da.bodyScroll(),Da.documentElementScroll())},max:function(){return Math.max.apply(null,I(Da))},min:function(){return Math.min.apply(null,I(Da))},leftMostElement:function(){return H("left",K())},taggedElement:function(){return J("left","data-iframe-width")}},Ea=e(L);b(a,"message",R),S()}(window||{});
//# sourceMappingURL=iframeResizer.contentWindow.map;
System.register('flarum/embed/components/DiscussionPage', ['flarum/components/DiscussionPage', 'flarum/components/PostStream', 'flarum/helpers/listItems'], function (_export) {
  'use strict';

  var BaseDiscussionPage, PostStream, listItems, DiscussionPage;
  return {
    setters: [function (_flarumComponentsDiscussionPage) {
      BaseDiscussionPage = _flarumComponentsDiscussionPage['default'];
    }, function (_flarumComponentsPostStream) {
      PostStream = _flarumComponentsPostStream['default'];
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems['default'];
    }],
    execute: function () {
      DiscussionPage = (function (_BaseDiscussionPage) {
        babelHelpers.inherits(DiscussionPage, _BaseDiscussionPage);

        function DiscussionPage() {
          babelHelpers.classCallCheck(this, DiscussionPage);
          babelHelpers.get(Object.getPrototypeOf(DiscussionPage.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(DiscussionPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(Object.getPrototypeOf(DiscussionPage.prototype), 'init', this).call(this);

            this.bodyClass = null;
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'DiscussionPage' },
              m(
                'div',
                { 'class': 'container' },
                m(
                  'div',
                  { className: 'DiscussionPage-discussion' },
                  m(
                    'nav',
                    { className: 'DiscussionPage-nav--embed' },
                    m(
                      'ul',
                      null,
                      listItems(this.sidebarItems().toArray())
                    )
                  ),
                  m(
                    'div',
                    { className: 'DiscussionPage-stream' },
                    this.stream ? this.stream.render() : ''
                  )
                )
              )
            );
          }
        }, {
          key: 'sidebarItems',
          value: function sidebarItems() {
            var items = babelHelpers.get(Object.getPrototypeOf(DiscussionPage.prototype), 'sidebarItems', this).call(this);

            items.remove('scrubber');

            return items;
          }
        }]);
        return DiscussionPage;
      })(BaseDiscussionPage);

      _export('default', DiscussionPage);
    }
  };
});;
System.register('flarum/embed/main', ['flarum/extend', 'flarum/app', 'flarum/components/Composer', 'flarum/components/ModalManager', 'flarum/components/AlertManager', 'flarum/components/PostMeta', 'flarum/utils/mapRoutes', 'flarum/utils/Pane', 'flarum/utils/Drawer', 'flarum/embed/components/DiscussionPage'], function (_export) {
  'use strict';

  var override, app, Composer, ModalManager, AlertManager, PostMeta, mapRoutes, Pane, Drawer, DiscussionPage;
  return {
    setters: [function (_flarumExtend) {
      override = _flarumExtend.override;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumComponentsComposer) {
      Composer = _flarumComponentsComposer['default'];
    }, function (_flarumComponentsModalManager) {
      ModalManager = _flarumComponentsModalManager['default'];
    }, function (_flarumComponentsAlertManager) {
      AlertManager = _flarumComponentsAlertManager['default'];
    }, function (_flarumComponentsPostMeta) {
      PostMeta = _flarumComponentsPostMeta['default'];
    }, function (_flarumUtilsMapRoutes) {
      mapRoutes = _flarumUtilsMapRoutes['default'];
    }, function (_flarumUtilsPane) {
      Pane = _flarumUtilsPane['default'];
    }, function (_flarumUtilsDrawer) {
      Drawer = _flarumUtilsDrawer['default'];
    }, function (_flarumEmbedComponentsDiscussionPage) {
      DiscussionPage = _flarumEmbedComponentsDiscussionPage['default'];
    }],
    execute: function () {

      app.initializers.replace('boot', function () {
        m.route.mode = 'pathname';

        override(m, 'route', function (original, root, arg1, arg2, vdom) {
          if (arguments.length === 1) {} else if (arguments.length === 4 && typeof arg1 === 'string') {} else if (root.addEventListener || root.attachEvent) {
            root.href = vdom.attrs.href;
            root.target = '_blank';

            // TODO: If href leads to a post within this discussion that we have
            // already loaded, then scroll to it?
            return;
          }

          return original.apply(this, Array.prototype.slice.call(arguments, 1));
        });

        // Trim the /embed prefix off of post permalinks
        override(PostMeta.prototype, 'getPermalink', function (original, post) {
          return original(post).replace('/embed', '');
        });

        app.pane = new Pane(document.getElementById('app'));
        app.drawer = new Drawer();
        app.composer = m.mount(document.getElementById('composer'), Composer.component());
        app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
        app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

        app.viewingDiscussion = function (discussion) {
          return this.current instanceof DiscussionPage && this.current.discussion === discussion;
        };

        delete app.routes['index.filter'];
        app.routes['discussion'] = { path: '/embed/:id', component: DiscussionPage.component() };
        app.routes['discussion.near'] = { path: '/embed/:id/:near', component: DiscussionPage.component() };

        var basePath = app.forum.attribute('basePath');
        m.route.mode = 'pathname';
        m.route(document.getElementById('content'), basePath + '/', mapRoutes(app.routes, basePath));
      });
    }
  };
});