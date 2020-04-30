flarum.core =
/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin.ts":
/*!******************!*\
  !*** ./admin.ts ***!
  \******************/
/*! exports provided: app, compat */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_common__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/common */ "./src/common/index.ts");
/* empty/unused harmony star reexport *//* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.ts");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "app", function() { return _src_admin__WEBPACK_IMPORTED_MODULE_1__["app"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "compat", function() { return _src_admin__WEBPACK_IMPORTED_MODULE_1__["compat"]; });




/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js ***!
  \**************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _assertThisInitialized; });
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/extends.js":
/*!************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/extends.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _extends; });
function _extends() {
  _extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  return _extends.apply(this, arguments);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _getPrototypeOf; });
function _getPrototypeOf(o) {
  _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _inheritsLoose; });
function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  subClass.__proto__ = superClass;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js":
/*!*********************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js ***!
  \*********************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _objectWithoutPropertiesLoose; });
function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;

  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }

  return target;
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js":
/*!******************************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js ***!
  \******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _possibleConstructorReturn; });
/* harmony import */ var _helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../helpers/esm/typeof */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./assertThisInitialized */ "./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");


function _possibleConstructorReturn(self, call) {
  if (call && (Object(_helpers_esm_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(call) === "object" || typeof call === "function")) {
    return call;
  }

  return Object(_assertThisInitialized__WEBPACK_IMPORTED_MODULE_1__["default"])(self);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _typeof; });
function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

/***/ }),

/***/ "./node_modules/bootstrap/js/dropdown.js":
/*!***********************************************!*\
  !*** ./node_modules/bootstrap/js/dropdown.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* ========================================================================
 * Bootstrap: dropdown.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // DROPDOWN CLASS DEFINITION
  // =========================

  var backdrop = '.dropdown-backdrop'
  var toggle   = '[data-toggle="dropdown"]'
  var Dropdown = function (element) {
    $(element).on('click.bs.dropdown', this.toggle)
  }

  Dropdown.VERSION = '3.4.1'

  function getParent($this) {
    var selector = $this.attr('data-target')

    if (!selector) {
      selector = $this.attr('href')
      selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
    }

    var $parent = selector !== '#' ? $(document).find(selector) : null

    return $parent && $parent.length ? $parent : $this.parent()
  }

  function clearMenus(e) {
    if (e && e.which === 3) return
    $(backdrop).remove()
    $(toggle).each(function () {
      var $this         = $(this)
      var $parent       = getParent($this)
      var relatedTarget = { relatedTarget: this }

      if (!$parent.hasClass('open')) return

      if (e && e.type == 'click' && /input|textarea/i.test(e.target.tagName) && $.contains($parent[0], e.target)) return

      $parent.trigger(e = $.Event('hide.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this.attr('aria-expanded', 'false')
      $parent.removeClass('open').trigger($.Event('hidden.bs.dropdown', relatedTarget))
    })
  }

  Dropdown.prototype.toggle = function (e) {
    var $this = $(this)

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    clearMenus()

    if (!isActive) {
      if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
        // if mobile we use a backdrop because click events don't delegate
        $(document.createElement('div'))
          .addClass('dropdown-backdrop')
          .insertAfter($(this))
          .on('click', clearMenus)
      }

      var relatedTarget = { relatedTarget: this }
      $parent.trigger(e = $.Event('show.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this
        .trigger('focus')
        .attr('aria-expanded', 'true')

      $parent
        .toggleClass('open')
        .trigger($.Event('shown.bs.dropdown', relatedTarget))
    }

    return false
  }

  Dropdown.prototype.keydown = function (e) {
    if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) return

    var $this = $(this)

    e.preventDefault()
    e.stopPropagation()

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    if (!isActive && e.which != 27 || isActive && e.which == 27) {
      if (e.which == 27) $parent.find(toggle).trigger('focus')
      return $this.trigger('click')
    }

    var desc = ' li:not(.disabled):visible a'
    var $items = $parent.find('.dropdown-menu' + desc)

    if (!$items.length) return

    var index = $items.index(e.target)

    if (e.which == 38 && index > 0)                 index--         // up
    if (e.which == 40 && index < $items.length - 1) index++         // down
    if (!~index)                                    index = 0

    $items.eq(index).trigger('focus')
  }


  // DROPDOWN PLUGIN DEFINITION
  // ==========================

  function Plugin(option) {
    return this.each(function () {
      var $this = $(this)
      var data  = $this.data('bs.dropdown')

      if (!data) $this.data('bs.dropdown', (data = new Dropdown(this)))
      if (typeof option == 'string') data[option].call($this)
    })
  }

  var old = $.fn.dropdown

  $.fn.dropdown             = Plugin
  $.fn.dropdown.Constructor = Dropdown


  // DROPDOWN NO CONFLICT
  // ====================

  $.fn.dropdown.noConflict = function () {
    $.fn.dropdown = old
    return this
  }


  // APPLY TO STANDARD DROPDOWN ELEMENTS
  // ===================================

  $(document)
    .on('click.bs.dropdown.data-api', clearMenus)
    .on('click.bs.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
    .on('click.bs.dropdown.data-api', toggle, Dropdown.prototype.toggle)
    .on('keydown.bs.dropdown.data-api', toggle, Dropdown.prototype.keydown)
    .on('keydown.bs.dropdown.data-api', '.dropdown-menu', Dropdown.prototype.keydown)

}(jQuery);


/***/ }),

/***/ "./node_modules/bootstrap/js/transition.js":
/*!*************************************************!*\
  !*** ./node_modules/bootstrap/js/transition.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* ========================================================================
 * Bootstrap: transition.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#transitions
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // CSS TRANSITION SUPPORT (Shoutout: https://modernizr.com/)
  // ============================================================

  function transitionEnd() {
    var el = document.createElement('bootstrap')

    var transEndEventNames = {
      WebkitTransition : 'webkitTransitionEnd',
      MozTransition    : 'transitionend',
      OTransition      : 'oTransitionEnd otransitionend',
      transition       : 'transitionend'
    }

    for (var name in transEndEventNames) {
      if (el.style[name] !== undefined) {
        return { end: transEndEventNames[name] }
      }
    }

    return false // explicit for ie8 (  ._.)
  }

  // https://blog.alexmaccaw.com/css-transitions
  $.fn.emulateTransitionEnd = function (duration) {
    var called = false
    var $el = this
    $(this).one('bsTransitionEnd', function () { called = true })
    var callback = function () { if (!called) $($el).trigger($.support.transition.end) }
    setTimeout(callback, duration)
    return this
  }

  $(function () {
    $.support.transition = transitionEnd()

    if (!$.support.transition) return

    $.event.special.bsTransitionEnd = {
      bindType: $.support.transition.end,
      delegateType: $.support.transition.end,
      handle: function (e) {
        if ($(e.target).is(this)) return e.handleObj.handler.apply(this, arguments)
      }
    }
  })

}(jQuery);


/***/ }),

/***/ "./node_modules/dayjs/plugin/localizedFormat.js":
/*!******************************************************!*\
  !*** ./node_modules/dayjs/plugin/localizedFormat.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(e,t){ true?module.exports=t():undefined}(this,function(){"use strict";return function(e,t,o){var n=t.prototype,r=n.format,M={LTS:"h:mm:ss A",LT:"h:mm A",L:"MM/DD/YYYY",LL:"MMMM D, YYYY",LLL:"MMMM D, YYYY h:mm A",LLLL:"dddd, MMMM D, YYYY h:mm A"};o.en.formats=M;n.format=function(e){void 0===e&&(e="YYYY-MM-DDTHH:mm:ssZ");var t=this.$locale().formats,o=void 0===t?{}:t,n=e.replace(/(\[[^\]]+])|(LTS?|l{1,4}|L{1,4})/g,function(e,t,n){var r=n&&n.toUpperCase();return t||o[n]||M[n]||o[r].replace(/(\[[^\]]+])|(MMMM|MM|DD|dddd)/g,function(e,t,o){return t||o.slice(1)})});return r.call(this,n)}}});


/***/ }),

/***/ "./node_modules/dayjs/plugin/relativeTime.js":
/*!***************************************************!*\
  !*** ./node_modules/dayjs/plugin/relativeTime.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(r,t){ true?module.exports=t():undefined}(this,function(){"use strict";return function(r,t,e){var n=t.prototype;e.en.relativeTime={future:"in %s",past:"%s ago",s:"a few seconds",m:"a minute",mm:"%d minutes",h:"an hour",hh:"%d hours",d:"a day",dd:"%d days",M:"a month",MM:"%d months",y:"a year",yy:"%d years"};var o=function(r,t,n,o){for(var d,i,u,a=n.$locale().relativeTime,f=[{l:"s",r:44,d:"second"},{l:"m",r:89},{l:"mm",r:44,d:"minute"},{l:"h",r:89},{l:"hh",r:21,d:"hour"},{l:"d",r:35},{l:"dd",r:25,d:"day"},{l:"M",r:45},{l:"MM",r:10,d:"month"},{l:"y",r:17},{l:"yy",d:"year"}],s=f.length,l=0;l<s;l+=1){var h=f[l];h.d&&(d=o?e(r).diff(n,h.d,!0):n.diff(r,h.d,!0));var m=Math.round(Math.abs(d));if(u=d>0,m<=h.r||!h.r){1===m&&l>0&&(h=f[l-1]);var c=a[h.l];i="string"==typeof c?c.replace("%d",m):c(m,t,h.l,u);break}}return t?i:(u?a.future:a.past).replace("%s",i)};n.to=function(r,t){return o(r,t,this,!0)},n.from=function(r,t){return o(r,t,this)};var d=function(r){return r.$u?e.utc():e()};n.toNow=function(r){return this.to(d(this),r)},n.fromNow=function(r){return this.from(d(this),r)}}});


/***/ }),

/***/ "./node_modules/expose-loader/index.js?Mousetrap!./node_modules/mousetrap/mousetrap.js-exposed":
/*!********************************************************************************************!*\
  !*** ./node_modules/expose-loader?Mousetrap!./node_modules/mousetrap/mousetrap.js-exposed ***!
  \********************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["Mousetrap"] = __webpack_require__(/*! -!./node_modules/source-map-loader!./mousetrap.js */ "./node_modules/source-map-loader/index.js!./node_modules/mousetrap/mousetrap.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?classNames!./node_modules/classnames/index.js-exposed":
/*!******************************************************************************************!*\
  !*** ./node_modules/expose-loader?classNames!./node_modules/classnames/index.js-exposed ***!
  \******************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["classNames"] = __webpack_require__(/*! -!./node_modules/source-map-loader!./index.js */ "./node_modules/source-map-loader/index.js!./node_modules/classnames/index.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?dayjs!./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js-exposed":
/*!*********************************************************************************************************************!*\
  !*** ./node_modules/expose-loader?dayjs!./node_modules/source-map-loader!./node_modules/dayjs/dayjs.min.js-exposed ***!
  \*********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["dayjs"] = __webpack_require__(/*! -!./node_modules/source-map-loader!./dayjs.min.js */ "./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?jQuery!./node_modules/zepto/dist/zepto.js-exposed":
/*!**************************************************************************************!*\
  !*** ./node_modules/expose-loader?jQuery!./node_modules/zepto/dist/zepto.js-exposed ***!
  \**************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["jQuery"] = __webpack_require__(/*! -!./node_modules/imports-loader?this=>window!./node_modules/source-map-loader!./zepto.js */ "./node_modules/imports-loader/index.js?this=>window!./node_modules/source-map-loader/index.js!./node_modules/zepto/dist/zepto.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?m!./node_modules/mithril/index.js-exposed":
/*!******************************************************************************!*\
  !*** ./node_modules/expose-loader?m!./node_modules/mithril/index.js-exposed ***!
  \******************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["m"] = __webpack_require__(/*! -!./node_modules/source-map-loader!./index.js */ "./node_modules/source-map-loader/index.js!./node_modules/mithril/index.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?m.bidi!./node_modules/m.attrs.bidi/bidi.js-exposed":
/*!***************************************************************************************!*\
  !*** ./node_modules/expose-loader?m.bidi!./node_modules/m.attrs.bidi/bidi.js-exposed ***!
  \***************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {if(!global["m"]) global["m"] = {};
module.exports = global["m"]["bidi"] = __webpack_require__(/*! -!./node_modules/source-map-loader!./bidi.js */ "./node_modules/source-map-loader/index.js!./node_modules/m.attrs.bidi/bidi.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?moment!./node_modules/expose-loader/index.js?dayjs!./node_modules/dayjs/dayjs.min.js-exposed":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/expose-loader?moment!./node_modules/expose-loader?dayjs!./node_modules/dayjs/dayjs.min.js-exposed ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["moment"] = __webpack_require__(/*! -!./node_modules/expose-loader?dayjs!./node_modules/source-map-loader!./dayjs.min.js */ "./node_modules/expose-loader/index.js?dayjs!./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js-exposed");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/hc-sticky/dist/hc-sticky.js":
/*!**************************************************!*\
  !*** ./node_modules/hc-sticky/dist/hc-sticky.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
 * HC-Sticky
 * =========
 * Version: 2.2.3
 * Author: Some Web Media
 * Author URL: http://somewebmedia.com
 * Plugin URL: https://github.com/somewebmedia/hc-sticky
 * Description: Cross-browser plugin that makes any element on your page visible while you scroll
 * License: MIT
 */
!function(t,e){"use strict";if( true&&"object"==typeof module.exports){if(!t.document)throw new Error("HC-Sticky requires a browser to run.");module.exports=e(t)}else true?!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (e(t)),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__)):undefined}("undefined"!=typeof window?window:this,function(U){"use strict";var Y={top:0,bottom:0,bottomEnd:0,innerTop:0,innerSticker:null,stickyClass:"sticky",stickTo:null,followScroll:!0,responsive:null,mobileFirst:!1,onStart:null,onStop:null,onBeforeResize:null,onResize:null,resizeDebounce:100,disable:!1,queries:null,queryFlow:"down"},$=function(t,e,o){console.log("%c! HC Sticky:%c "+t+"%c "+o+" is now deprecated and will be removed. Use%c "+e+"%c instead.","color: red","color: darkviolet","color: black","color: darkviolet","color: black")},Q=U.document,X=function(n,f){var o=this;if("string"==typeof n&&(n=Q.querySelector(n)),!n)return!1;f.queries&&$("queries","responsive","option"),f.queryFlow&&$("queryFlow","mobileFirst","option");var p={},d=X.Helpers,s=n.parentNode;"static"===d.getStyle(s,"position")&&(s.style.position="relative");var u=function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{};d.isEmptyObject(t)&&!d.isEmptyObject(p)||(p=Object.assign({},Y,p,t))},t=function(){return p.disable},e=function(){var t,e=p.responsive||p.queries;if(e){var o=U.innerWidth;if(t=f,(p=Object.assign({},Y,t||{})).mobileFirst)for(var i in e)i<=o&&!d.isEmptyObject(e[i])&&u(e[i]);else{var n=[];for(var s in e){var r={};r[s]=e[s],n.push(r)}for(var l=n.length-1;0<=l;l--){var a=n[l],c=Object.keys(a)[0];o<=c&&!d.isEmptyObject(a[c])&&u(a[c])}}}},r={css:{},position:null,stick:function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{};d.hasClass(n,p.stickyClass)||(!1===l.isAttached&&l.attach(),r.position="fixed",n.style.position="fixed",n.style.left=l.offsetLeft+"px",n.style.width=l.width,void 0===t.bottom?n.style.bottom="auto":n.style.bottom=t.bottom+"px",void 0===t.top?n.style.top="auto":n.style.top=t.top+"px",n.classList?n.classList.add(p.stickyClass):n.className+=" "+p.stickyClass,p.onStart&&p.onStart.call(n,Object.assign({},p)))},release:function(){var t=0<arguments.length&&void 0!==arguments[0]?arguments[0]:{};if(t.stop=t.stop||!1,!0===t.stop||"fixed"===r.position||null===r.position||!(void 0===t.top&&void 0===t.bottom||void 0!==t.top&&(parseInt(d.getStyle(n,"top"))||0)===t.top||void 0!==t.bottom&&(parseInt(d.getStyle(n,"bottom"))||0)===t.bottom)){!0===t.stop?!0===l.isAttached&&l.detach():!1===l.isAttached&&l.attach();var e=t.position||r.css.position;r.position=e,n.style.position=e,n.style.left=!0===t.stop?r.css.left:l.positionLeft+"px",n.style.width="absolute"!==e?r.css.width:l.width,void 0===t.bottom?n.style.bottom=!0===t.stop?"":"auto":n.style.bottom=t.bottom+"px",void 0===t.top?n.style.top=!0===t.stop?"":"auto":n.style.top=t.top+"px",n.classList?n.classList.remove(p.stickyClass):n.className=n.className.replace(new RegExp("(^|\\b)"+p.stickyClass.split(" ").join("|")+"(\\b|$)","gi")," "),p.onStop&&p.onStop.call(n,Object.assign({},p))}}},l={el:Q.createElement("div"),offsetLeft:null,positionLeft:null,width:null,isAttached:!1,init:function(){for(var t in l.el.className="sticky-spacer",r.css)l.el.style[t]=r.css[t];l.el.style["z-index"]="-1";var e=d.getStyle(n);l.offsetLeft=d.offset(n).left-(parseInt(e.marginLeft)||0),l.positionLeft=d.position(n).left,l.width=d.getStyle(n,"width")},attach:function(){s.insertBefore(l.el,n),l.isAttached=!0},detach:function(){l.el=s.removeChild(l.el),l.isAttached=!1}},a=void 0,c=void 0,g=void 0,m=void 0,h=void 0,v=void 0,y=void 0,b=void 0,S=void 0,w=void 0,k=void 0,E=void 0,x=void 0,L=void 0,T=void 0,j=void 0,O=void 0,C=void 0,i=function(){var t,e,o,i;r.css=(t=n,e=d.getCascadedStyle(t),o=d.getStyle(t),i={height:t.offsetHeight+"px",left:e.left,right:e.right,top:e.top,bottom:e.bottom,position:o.position,display:o.display,verticalAlign:o.verticalAlign,boxSizing:o.boxSizing,marginLeft:e.marginLeft,marginRight:e.marginRight,marginTop:e.marginTop,marginBottom:e.marginBottom,paddingLeft:e.paddingLeft,paddingRight:e.paddingRight},e.float&&(i.float=e.float||"none"),e.cssFloat&&(i.cssFloat=e.cssFloat||"none"),o.MozBoxSizing&&(i.MozBoxSizing=o.MozBoxSizing),i.width="auto"!==e.width?e.width:"border-box"===i.boxSizing||"border-box"===i.MozBoxSizing?t.offsetWidth+"px":o.width,i),l.init(),a=!(!p.stickTo||!("document"===p.stickTo||p.stickTo.nodeType&&9===p.stickTo.nodeType||"object"==typeof p.stickTo&&p.stickTo instanceof("undefined"!=typeof HTMLDocument?HTMLDocument:Document))),c=p.stickTo?a?Q:"string"==typeof p.stickTo?Q.querySelector(p.stickTo):p.stickTo:s,T=(C=function(){var t=n.offsetHeight+(parseInt(r.css.marginTop)||0)+(parseInt(r.css.marginBottom)||0),e=(T||0)-t;return-1<=e&&e<=1?T:t})(),m=(O=function(){return a?Math.max(Q.documentElement.clientHeight,Q.body.scrollHeight,Q.documentElement.scrollHeight,Q.body.offsetHeight,Q.documentElement.offsetHeight):c.offsetHeight})(),h=a?0:d.offset(c).top,v=p.stickTo?a?0:d.offset(s).top:h,y=U.innerHeight,j=n.offsetTop-(parseInt(r.css.marginTop)||0),g=p.innerSticker?"string"==typeof p.innerSticker?Q.querySelector(p.innerSticker):p.innerSticker:null,b=isNaN(p.top)&&-1<p.top.indexOf("%")?parseFloat(p.top)/100*y:p.top,S=isNaN(p.bottom)&&-1<p.bottom.indexOf("%")?parseFloat(p.bottom)/100*y:p.bottom,w=g?g.offsetTop:p.innerTop?p.innerTop:0,k=isNaN(p.bottomEnd)&&-1<p.bottomEnd.indexOf("%")?parseFloat(p.bottomEnd)/100*y:p.bottomEnd,E=h-b+w+j},z=U.pageYOffset||Q.documentElement.scrollTop,N=0,H=void 0,R=function(){T=C(),m=O(),x=h+m-b-k,L=y<T;var t=U.pageYOffset||Q.documentElement.scrollTop,e=d.offset(n).top,o=e-t,i=void 0;H=t<z?"up":"down",N=t-z,E<(z=t)?x+b+(L?S:0)-(p.followScroll&&L?0:b)<=t+T-w-(y-(E-w)<T-w&&p.followScroll&&0<(i=T-y-w)?i:0)?r.release({position:"absolute",bottom:v+s.offsetHeight-x-b}):L&&p.followScroll?"down"===H?o+T+S<=y+.9?r.stick({bottom:S}):"fixed"===r.position&&r.release({position:"absolute",top:e-b-E-N+w}):Math.ceil(o+w)<0&&"fixed"===r.position?r.release({position:"absolute",top:e-b-E+w-N}):t+b-w<=e&&r.stick({top:b-w}):r.stick({top:b-w}):r.release({stop:!0})},A=!1,B=!1,I=function(){A&&(d.event.unbind(U,"scroll",R),A=!1)},q=function(){null!==n.offsetParent&&"none"!==d.getStyle(n,"display")?(i(),m<=T?I():(R(),A||(d.event.bind(U,"scroll",R),A=!0))):I()},F=function(){n.style.position="",n.style.left="",n.style.top="",n.style.bottom="",n.style.width="",n.classList?n.classList.remove(p.stickyClass):n.className=n.className.replace(new RegExp("(^|\\b)"+p.stickyClass.split(" ").join("|")+"(\\b|$)","gi")," "),r.css={},!(r.position=null)===l.isAttached&&l.detach()},M=function(){F(),e(),t()?I():q()},D=function(){p.onBeforeResize&&p.onBeforeResize.call(n,Object.assign({},p)),M(),p.onResize&&p.onResize.call(n,Object.assign({},p))},P=p.resizeDebounce?d.debounce(D,p.resizeDebounce):D,W=function(){B&&(d.event.unbind(U,"resize",P),B=!1),I()},V=function(){B||(d.event.bind(U,"resize",P),B=!0),e(),t()?I():q()};this.options=function(t){return t?p[t]:Object.assign({},p)},this.refresh=M,this.update=function(t){u(t),f=Object.assign({},f,t||{}),M()},this.attach=V,this.detach=W,this.destroy=function(){W(),F()},this.triggerMethod=function(t,e){"function"==typeof o[t]&&o[t](e)},this.reinit=function(){$("reinit","refresh","method"),M()},u(f),V(),d.event.bind(U,"load",M)};if(void 0!==U.jQuery){var i=U.jQuery,n="hcSticky";i.fn.extend({hcSticky:function(e,o){return this.length?"options"===e?i.data(this.get(0),n).options():this.each(function(){var t=i.data(this,n);t?t.triggerMethod(e,o):(t=new X(this,e),i.data(this,n,t))}):this}})}return U.hcSticky=U.hcSticky||X,X}),function(c){"use strict";var t=c.hcSticky,f=c.document;"function"!=typeof Object.assign&&Object.defineProperty(Object,"assign",{value:function(t,e){if(null==t)throw new TypeError("Cannot convert undefined or null to object");for(var o=Object(t),i=1;i<arguments.length;i++){var n=arguments[i];if(null!=n)for(var s in n)Object.prototype.hasOwnProperty.call(n,s)&&(o[s]=n[s])}return o},writable:!0,configurable:!0}),Array.prototype.forEach||(Array.prototype.forEach=function(t){var e,o;if(null==this)throw new TypeError("this is null or not defined");var i=Object(this),n=i.length>>>0;if("function"!=typeof t)throw new TypeError(t+" is not a function");for(1<arguments.length&&(e=arguments[1]),o=0;o<n;){var s;o in i&&(s=i[o],t.call(e,s,o,i)),o++}});var e=function(){var t=f.documentElement,e=function(){};function i(t){var e=c.event;return e.target=e.target||e.srcElement||t,e}t.addEventListener?e=function(t,e,o){t.addEventListener(e,o,!1)}:t.attachEvent&&(e=function(e,t,o){e[t+o]=o.handleEvent?function(){var t=i(e);o.handleEvent.call(o,t)}:function(){var t=i(e);o.call(e,t)},e.attachEvent("on"+t,e[t+o])});var o=function(){};return t.removeEventListener?o=function(t,e,o){t.removeEventListener(e,o,!1)}:t.detachEvent&&(o=function(e,o,i){e.detachEvent("on"+o,e[o+i]);try{delete e[o+i]}catch(t){e[o+i]=void 0}}),{bind:e,unbind:o}}(),r=function(t,e){return c.getComputedStyle?e?f.defaultView.getComputedStyle(t,null).getPropertyValue(e):f.defaultView.getComputedStyle(t,null):t.currentStyle?e?t.currentStyle[e.replace(/-\w/g,function(t){return t.toUpperCase().replace("-","")})]:t.currentStyle:void 0},l=function(t){var e=t.getBoundingClientRect(),o=c.pageYOffset||f.documentElement.scrollTop,i=c.pageXOffset||f.documentElement.scrollLeft;return{top:e.top+o,left:e.left+i}};t.Helpers={isEmptyObject:function(t){for(var e in t)return!1;return!0},debounce:function(i,n,s){var r=void 0;return function(){var t=this,e=arguments,o=s&&!r;clearTimeout(r),r=setTimeout(function(){r=null,s||i.apply(t,e)},n),o&&i.apply(t,e)}},hasClass:function(t,e){return t.classList?t.classList.contains(e):new RegExp("(^| )"+e+"( |$)","gi").test(t.className)},offset:l,position:function(t){var e=t.offsetParent,o=l(e),i=l(t),n=r(e),s=r(t);return o.top+=parseInt(n.borderTopWidth)||0,o.left+=parseInt(n.borderLeftWidth)||0,{top:i.top-o.top-(parseInt(s.marginTop)||0),left:i.left-o.left-(parseInt(s.marginLeft)||0)}},getStyle:r,getCascadedStyle:function(t){var e=t.cloneNode(!0);e.style.display="none",Array.prototype.slice.call(e.querySelectorAll('input[type="radio"]')).forEach(function(t){t.removeAttribute("name")}),t.parentNode.insertBefore(e,t.nextSibling);var o=void 0;e.currentStyle?o=e.currentStyle:c.getComputedStyle&&(o=f.defaultView.getComputedStyle(e,null));var i={};for(var n in o)!isNaN(n)||"string"!=typeof o[n]&&"number"!=typeof o[n]||(i[n]=o[n]);if(Object.keys(i).length<3)for(var s in i={},o)isNaN(s)||(i[o[s].replace(/-\w/g,function(t){return t.toUpperCase().replace("-","")})]=o.getPropertyValue(o[s]));if(i.margin||"auto"!==i.marginLeft?i.margin||i.marginLeft!==i.marginRight||i.marginLeft!==i.marginTop||i.marginLeft!==i.marginBottom||(i.margin=i.marginLeft):i.margin="auto",!i.margin&&"0px"===i.marginLeft&&"0px"===i.marginRight){var r=t.offsetLeft-t.parentNode.offsetLeft,l=r-(parseInt(i.left)||0)-(parseInt(i.right)||0),a=t.parentNode.offsetWidth-t.offsetWidth-r-(parseInt(i.right)||0)+(parseInt(i.left)||0)-l;0!==a&&1!==a||(i.margin="auto")}return e.parentNode.removeChild(e),e=null,i},event:e}}(window);

/***/ }),

/***/ "./node_modules/imports-loader/index.js?this=>window!./node_modules/source-map-loader/index.js!./node_modules/zepto/dist/zepto.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/imports-loader?this=>window!./node_modules/source-map-loader!./node_modules/zepto/dist/zepto.js ***!
  \**********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/*** IMPORTS FROM imports-loader ***/
(function() {

/* Zepto v1.2.0 - zepto event ajax form ie - zeptojs.com/license */
(function(global, factory) {
  if (true)
    !(__WEBPACK_AMD_DEFINE_RESULT__ = (function() { return factory(global) }).call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__))
  else
    {}
}(this, function(window) {
  var Zepto = (function() {
  var undefined, key, $, classList, emptyArray = [], concat = emptyArray.concat, filter = emptyArray.filter, slice = emptyArray.slice,
    document = window.document,
    elementDisplay = {}, classCache = {},
    cssNumber = { 'column-count': 1, 'columns': 1, 'font-weight': 1, 'line-height': 1,'opacity': 1, 'z-index': 1, 'zoom': 1 },
    fragmentRE = /^\s*<(\w+|!)[^>]*>/,
    singleTagRE = /^<(\w+)\s*\/?>(?:<\/\1>|)$/,
    tagExpanderRE = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,
    rootNodeRE = /^(?:body|html)$/i,
    capitalRE = /([A-Z])/g,

    // special attributes that should be get/set via method calls
    methodAttributes = ['val', 'css', 'html', 'text', 'data', 'width', 'height', 'offset'],

    adjacencyOperators = [ 'after', 'prepend', 'before', 'append' ],
    table = document.createElement('table'),
    tableRow = document.createElement('tr'),
    containers = {
      'tr': document.createElement('tbody'),
      'tbody': table, 'thead': table, 'tfoot': table,
      'td': tableRow, 'th': tableRow,
      '*': document.createElement('div')
    },
    readyRE = /complete|loaded|interactive/,
    simpleSelectorRE = /^[\w-]*$/,
    class2type = {},
    toString = class2type.toString,
    zepto = {},
    camelize, uniq,
    tempParent = document.createElement('div'),
    propMap = {
      'tabindex': 'tabIndex',
      'readonly': 'readOnly',
      'for': 'htmlFor',
      'class': 'className',
      'maxlength': 'maxLength',
      'cellspacing': 'cellSpacing',
      'cellpadding': 'cellPadding',
      'rowspan': 'rowSpan',
      'colspan': 'colSpan',
      'usemap': 'useMap',
      'frameborder': 'frameBorder',
      'contenteditable': 'contentEditable'
    },
    isArray = Array.isArray ||
      function(object){ return object instanceof Array }

  zepto.matches = function(element, selector) {
    if (!selector || !element || element.nodeType !== 1) return false
    var matchesSelector = element.matches || element.webkitMatchesSelector ||
                          element.mozMatchesSelector || element.oMatchesSelector ||
                          element.matchesSelector
    if (matchesSelector) return matchesSelector.call(element, selector)
    // fall back to performing a selector:
    var match, parent = element.parentNode, temp = !parent
    if (temp) (parent = tempParent).appendChild(element)
    match = ~zepto.qsa(parent, selector).indexOf(element)
    temp && tempParent.removeChild(element)
    return match
  }

  function type(obj) {
    return obj == null ? String(obj) :
      class2type[toString.call(obj)] || "object"
  }

  function isFunction(value) { return type(value) == "function" }
  function isWindow(obj)     { return obj != null && obj == obj.window }
  function isDocument(obj)   { return obj != null && obj.nodeType == obj.DOCUMENT_NODE }
  function isObject(obj)     { return type(obj) == "object" }
  function isPlainObject(obj) {
    return isObject(obj) && !isWindow(obj) && Object.getPrototypeOf(obj) == Object.prototype
  }

  function likeArray(obj) {
    var length = !!obj && 'length' in obj && obj.length,
      type = $.type(obj)

    return 'function' != type && !isWindow(obj) && (
      'array' == type || length === 0 ||
        (typeof length == 'number' && length > 0 && (length - 1) in obj)
    )
  }

  function compact(array) { return filter.call(array, function(item){ return item != null }) }
  function flatten(array) { return array.length > 0 ? $.fn.concat.apply([], array) : array }
  camelize = function(str){ return str.replace(/-+(.)?/g, function(match, chr){ return chr ? chr.toUpperCase() : '' }) }
  function dasherize(str) {
    return str.replace(/::/g, '/')
           .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
           .replace(/([a-z\d])([A-Z])/g, '$1_$2')
           .replace(/_/g, '-')
           .toLowerCase()
  }
  uniq = function(array){ return filter.call(array, function(item, idx){ return array.indexOf(item) == idx }) }

  function classRE(name) {
    return name in classCache ?
      classCache[name] : (classCache[name] = new RegExp('(^|\\s)' + name + '(\\s|$)'))
  }

  function maybeAddPx(name, value) {
    return (typeof value == "number" && !cssNumber[dasherize(name)]) ? value + "px" : value
  }

  function defaultDisplay(nodeName) {
    var element, display
    if (!elementDisplay[nodeName]) {
      element = document.createElement(nodeName)
      document.body.appendChild(element)
      display = getComputedStyle(element, '').getPropertyValue("display")
      element.parentNode.removeChild(element)
      display == "none" && (display = "block")
      elementDisplay[nodeName] = display
    }
    return elementDisplay[nodeName]
  }

  function children(element) {
    return 'children' in element ?
      slice.call(element.children) :
      $.map(element.childNodes, function(node){ if (node.nodeType == 1) return node })
  }

  function Z(dom, selector) {
    var i, len = dom ? dom.length : 0
    for (i = 0; i < len; i++) this[i] = dom[i]
    this.length = len
    this.selector = selector || ''
  }

  // `$.zepto.fragment` takes a html string and an optional tag name
  // to generate DOM nodes from the given html string.
  // The generated DOM nodes are returned as an array.
  // This function can be overridden in plugins for example to make
  // it compatible with browsers that don't support the DOM fully.
  zepto.fragment = function(html, name, properties) {
    var dom, nodes, container

    // A special case optimization for a single tag
    if (singleTagRE.test(html)) dom = $(document.createElement(RegExp.$1))

    if (!dom) {
      if (html.replace) html = html.replace(tagExpanderRE, "<$1></$2>")
      if (name === undefined) name = fragmentRE.test(html) && RegExp.$1
      if (!(name in containers)) name = '*'

      container = containers[name]
      container.innerHTML = '' + html
      dom = $.each(slice.call(container.childNodes), function(){
        container.removeChild(this)
      })
    }

    if (isPlainObject(properties)) {
      nodes = $(dom)
      $.each(properties, function(key, value) {
        if (methodAttributes.indexOf(key) > -1) nodes[key](value)
        else nodes.attr(key, value)
      })
    }

    return dom
  }

  // `$.zepto.Z` swaps out the prototype of the given `dom` array
  // of nodes with `$.fn` and thus supplying all the Zepto functions
  // to the array. This method can be overridden in plugins.
  zepto.Z = function(dom, selector) {
    return new Z(dom, selector)
  }

  // `$.zepto.isZ` should return `true` if the given object is a Zepto
  // collection. This method can be overridden in plugins.
  zepto.isZ = function(object) {
    return object instanceof zepto.Z
  }

  // `$.zepto.init` is Zepto's counterpart to jQuery's `$.fn.init` and
  // takes a CSS selector and an optional context (and handles various
  // special cases).
  // This method can be overridden in plugins.
  zepto.init = function(selector, context) {
    var dom
    // If nothing given, return an empty Zepto collection
    if (!selector) return zepto.Z()
    // Optimize for string selectors
    else if (typeof selector == 'string') {
      selector = selector.trim()
      // If it's a html fragment, create nodes from it
      // Note: In both Chrome 21 and Firefox 15, DOM error 12
      // is thrown if the fragment doesn't begin with <
      if (selector[0] == '<' && fragmentRE.test(selector))
        dom = zepto.fragment(selector, RegExp.$1, context), selector = null
      // If there's a context, create a collection on that context first, and select
      // nodes from there
      else if (context !== undefined) return $(context).find(selector)
      // If it's a CSS selector, use it to select nodes.
      else dom = zepto.qsa(document, selector)
    }
    // If a function is given, call it when the DOM is ready
    else if (isFunction(selector)) return $(document).ready(selector)
    // If a Zepto collection is given, just return it
    else if (zepto.isZ(selector)) return selector
    else {
      // normalize array if an array of nodes is given
      if (isArray(selector)) dom = compact(selector)
      // Wrap DOM nodes.
      else if (isObject(selector))
        dom = [selector], selector = null
      // If it's a html fragment, create nodes from it
      else if (fragmentRE.test(selector))
        dom = zepto.fragment(selector.trim(), RegExp.$1, context), selector = null
      // If there's a context, create a collection on that context first, and select
      // nodes from there
      else if (context !== undefined) return $(context).find(selector)
      // And last but no least, if it's a CSS selector, use it to select nodes.
      else dom = zepto.qsa(document, selector)
    }
    // create a new Zepto collection from the nodes found
    return zepto.Z(dom, selector)
  }

  // `$` will be the base `Zepto` object. When calling this
  // function just call `$.zepto.init, which makes the implementation
  // details of selecting nodes and creating Zepto collections
  // patchable in plugins.
  $ = function(selector, context){
    return zepto.init(selector, context)
  }

  function extend(target, source, deep) {
    for (key in source)
      if (deep && (isPlainObject(source[key]) || isArray(source[key]))) {
        if (isPlainObject(source[key]) && !isPlainObject(target[key]))
          target[key] = {}
        if (isArray(source[key]) && !isArray(target[key]))
          target[key] = []
        extend(target[key], source[key], deep)
      }
      else if (source[key] !== undefined) target[key] = source[key]
  }

  // Copy all but undefined properties from one or more
  // objects to the `target` object.
  $.extend = function(target){
    var deep, args = slice.call(arguments, 1)
    if (typeof target == 'boolean') {
      deep = target
      target = args.shift()
    }
    args.forEach(function(arg){ extend(target, arg, deep) })
    return target
  }

  // `$.zepto.qsa` is Zepto's CSS selector implementation which
  // uses `document.querySelectorAll` and optimizes for some special cases, like `#id`.
  // This method can be overridden in plugins.
  zepto.qsa = function(element, selector){
    var found,
        maybeID = selector[0] == '#',
        maybeClass = !maybeID && selector[0] == '.',
        nameOnly = maybeID || maybeClass ? selector.slice(1) : selector, // Ensure that a 1 char tag name still gets checked
        isSimple = simpleSelectorRE.test(nameOnly)
    return (element.getElementById && isSimple && maybeID) ? // Safari DocumentFragment doesn't have getElementById
      ( (found = element.getElementById(nameOnly)) ? [found] : [] ) :
      (element.nodeType !== 1 && element.nodeType !== 9 && element.nodeType !== 11) ? [] :
      slice.call(
        isSimple && !maybeID && element.getElementsByClassName ? // DocumentFragment doesn't have getElementsByClassName/TagName
          maybeClass ? element.getElementsByClassName(nameOnly) : // If it's simple, it could be a class
          element.getElementsByTagName(selector) : // Or a tag
          element.querySelectorAll(selector) // Or it's not simple, and we need to query all
      )
  }

  function filtered(nodes, selector) {
    return selector == null ? $(nodes) : $(nodes).filter(selector)
  }

  $.contains = document.documentElement.contains ?
    function(parent, node) {
      return parent !== node && parent.contains(node)
    } :
    function(parent, node) {
      while (node && (node = node.parentNode))
        if (node === parent) return true
      return false
    }

  function funcArg(context, arg, idx, payload) {
    return isFunction(arg) ? arg.call(context, idx, payload) : arg
  }

  function setAttribute(node, name, value) {
    value == null ? node.removeAttribute(name) : node.setAttribute(name, value)
  }

  // access className property while respecting SVGAnimatedString
  function className(node, value){
    var klass = node.className || '',
        svg   = klass && klass.baseVal !== undefined

    if (value === undefined) return svg ? klass.baseVal : klass
    svg ? (klass.baseVal = value) : (node.className = value)
  }

  // "true"  => true
  // "false" => false
  // "null"  => null
  // "42"    => 42
  // "42.5"  => 42.5
  // "08"    => "08"
  // JSON    => parse if valid
  // String  => self
  function deserializeValue(value) {
    try {
      return value ?
        value == "true" ||
        ( value == "false" ? false :
          value == "null" ? null :
          +value + "" == value ? +value :
          /^[\[\{]/.test(value) ? $.parseJSON(value) :
          value )
        : value
    } catch(e) {
      return value
    }
  }

  $.type = type
  $.isFunction = isFunction
  $.isWindow = isWindow
  $.isArray = isArray
  $.isPlainObject = isPlainObject

  $.isEmptyObject = function(obj) {
    var name
    for (name in obj) return false
    return true
  }

  $.isNumeric = function(val) {
    var num = Number(val), type = typeof val
    return val != null && type != 'boolean' &&
      (type != 'string' || val.length) &&
      !isNaN(num) && isFinite(num) || false
  }

  $.inArray = function(elem, array, i){
    return emptyArray.indexOf.call(array, elem, i)
  }

  $.camelCase = camelize
  $.trim = function(str) {
    return str == null ? "" : String.prototype.trim.call(str)
  }

  // plugin compatibility
  $.uuid = 0
  $.support = { }
  $.expr = { }
  $.noop = function() {}

  $.map = function(elements, callback){
    var value, values = [], i, key
    if (likeArray(elements))
      for (i = 0; i < elements.length; i++) {
        value = callback(elements[i], i)
        if (value != null) values.push(value)
      }
    else
      for (key in elements) {
        value = callback(elements[key], key)
        if (value != null) values.push(value)
      }
    return flatten(values)
  }

  $.each = function(elements, callback){
    var i, key
    if (likeArray(elements)) {
      for (i = 0; i < elements.length; i++)
        if (callback.call(elements[i], i, elements[i]) === false) return elements
    } else {
      for (key in elements)
        if (callback.call(elements[key], key, elements[key]) === false) return elements
    }

    return elements
  }

  $.grep = function(elements, callback){
    return filter.call(elements, callback)
  }

  if (window.JSON) $.parseJSON = JSON.parse

  // Populate the class2type map
  $.each("Boolean Number String Function Array Date RegExp Object Error".split(" "), function(i, name) {
    class2type[ "[object " + name + "]" ] = name.toLowerCase()
  })

  // Define methods that will be available on all
  // Zepto collections
  $.fn = {
    constructor: zepto.Z,
    length: 0,

    // Because a collection acts like an array
    // copy over these useful array functions.
    forEach: emptyArray.forEach,
    reduce: emptyArray.reduce,
    push: emptyArray.push,
    sort: emptyArray.sort,
    splice: emptyArray.splice,
    indexOf: emptyArray.indexOf,
    concat: function(){
      var i, value, args = []
      for (i = 0; i < arguments.length; i++) {
        value = arguments[i]
        args[i] = zepto.isZ(value) ? value.toArray() : value
      }
      return concat.apply(zepto.isZ(this) ? this.toArray() : this, args)
    },

    // `map` and `slice` in the jQuery API work differently
    // from their array counterparts
    map: function(fn){
      return $($.map(this, function(el, i){ return fn.call(el, i, el) }))
    },
    slice: function(){
      return $(slice.apply(this, arguments))
    },

    ready: function(callback){
      // need to check if document.body exists for IE as that browser reports
      // document ready when it hasn't yet created the body element
      if (readyRE.test(document.readyState) && document.body) callback($)
      else document.addEventListener('DOMContentLoaded', function(){ callback($) }, false)
      return this
    },
    get: function(idx){
      return idx === undefined ? slice.call(this) : this[idx >= 0 ? idx : idx + this.length]
    },
    toArray: function(){ return this.get() },
    size: function(){
      return this.length
    },
    remove: function(){
      return this.each(function(){
        if (this.parentNode != null)
          this.parentNode.removeChild(this)
      })
    },
    each: function(callback){
      emptyArray.every.call(this, function(el, idx){
        return callback.call(el, idx, el) !== false
      })
      return this
    },
    filter: function(selector){
      if (isFunction(selector)) return this.not(this.not(selector))
      return $(filter.call(this, function(element){
        return zepto.matches(element, selector)
      }))
    },
    add: function(selector,context){
      return $(uniq(this.concat($(selector,context))))
    },
    is: function(selector){
      return this.length > 0 && zepto.matches(this[0], selector)
    },
    not: function(selector){
      var nodes=[]
      if (isFunction(selector) && selector.call !== undefined)
        this.each(function(idx){
          if (!selector.call(this,idx)) nodes.push(this)
        })
      else {
        var excludes = typeof selector == 'string' ? this.filter(selector) :
          (likeArray(selector) && isFunction(selector.item)) ? slice.call(selector) : $(selector)
        this.forEach(function(el){
          if (excludes.indexOf(el) < 0) nodes.push(el)
        })
      }
      return $(nodes)
    },
    has: function(selector){
      return this.filter(function(){
        return isObject(selector) ?
          $.contains(this, selector) :
          $(this).find(selector).size()
      })
    },
    eq: function(idx){
      return idx === -1 ? this.slice(idx) : this.slice(idx, + idx + 1)
    },
    first: function(){
      var el = this[0]
      return el && !isObject(el) ? el : $(el)
    },
    last: function(){
      var el = this[this.length - 1]
      return el && !isObject(el) ? el : $(el)
    },
    find: function(selector){
      var result, $this = this
      if (!selector) result = $()
      else if (typeof selector == 'object')
        result = $(selector).filter(function(){
          var node = this
          return emptyArray.some.call($this, function(parent){
            return $.contains(parent, node)
          })
        })
      else if (this.length == 1) result = $(zepto.qsa(this[0], selector))
      else result = this.map(function(){ return zepto.qsa(this, selector) })
      return result
    },
    closest: function(selector, context){
      var nodes = [], collection = typeof selector == 'object' && $(selector)
      this.each(function(_, node){
        while (node && !(collection ? collection.indexOf(node) >= 0 : zepto.matches(node, selector)))
          node = node !== context && !isDocument(node) && node.parentNode
        if (node && nodes.indexOf(node) < 0) nodes.push(node)
      })
      return $(nodes)
    },
    parents: function(selector){
      var ancestors = [], nodes = this
      while (nodes.length > 0)
        nodes = $.map(nodes, function(node){
          if ((node = node.parentNode) && !isDocument(node) && ancestors.indexOf(node) < 0) {
            ancestors.push(node)
            return node
          }
        })
      return filtered(ancestors, selector)
    },
    parent: function(selector){
      return filtered(uniq(this.pluck('parentNode')), selector)
    },
    children: function(selector){
      return filtered(this.map(function(){ return children(this) }), selector)
    },
    contents: function() {
      return this.map(function() { return this.contentDocument || slice.call(this.childNodes) })
    },
    siblings: function(selector){
      return filtered(this.map(function(i, el){
        return filter.call(children(el.parentNode), function(child){ return child!==el })
      }), selector)
    },
    empty: function(){
      return this.each(function(){ this.innerHTML = '' })
    },
    // `pluck` is borrowed from Prototype.js
    pluck: function(property){
      return $.map(this, function(el){ return el[property] })
    },
    show: function(){
      return this.each(function(){
        this.style.display == "none" && (this.style.display = '')
        if (getComputedStyle(this, '').getPropertyValue("display") == "none")
          this.style.display = defaultDisplay(this.nodeName)
      })
    },
    replaceWith: function(newContent){
      return this.before(newContent).remove()
    },
    wrap: function(structure){
      var func = isFunction(structure)
      if (this[0] && !func)
        var dom   = $(structure).get(0),
            clone = dom.parentNode || this.length > 1

      return this.each(function(index){
        $(this).wrapAll(
          func ? structure.call(this, index) :
            clone ? dom.cloneNode(true) : dom
        )
      })
    },
    wrapAll: function(structure){
      if (this[0]) {
        $(this[0]).before(structure = $(structure))
        var children
        // drill down to the inmost element
        while ((children = structure.children()).length) structure = children.first()
        $(structure).append(this)
      }
      return this
    },
    wrapInner: function(structure){
      var func = isFunction(structure)
      return this.each(function(index){
        var self = $(this), contents = self.contents(),
            dom  = func ? structure.call(this, index) : structure
        contents.length ? contents.wrapAll(dom) : self.append(dom)
      })
    },
    unwrap: function(){
      this.parent().each(function(){
        $(this).replaceWith($(this).children())
      })
      return this
    },
    clone: function(){
      return this.map(function(){ return this.cloneNode(true) })
    },
    hide: function(){
      return this.css("display", "none")
    },
    toggle: function(setting){
      return this.each(function(){
        var el = $(this)
        ;(setting === undefined ? el.css("display") == "none" : setting) ? el.show() : el.hide()
      })
    },
    prev: function(selector){ return $(this.pluck('previousElementSibling')).filter(selector || '*') },
    next: function(selector){ return $(this.pluck('nextElementSibling')).filter(selector || '*') },
    html: function(html){
      return 0 in arguments ?
        this.each(function(idx){
          var originHtml = this.innerHTML
          $(this).empty().append( funcArg(this, html, idx, originHtml) )
        }) :
        (0 in this ? this[0].innerHTML : null)
    },
    text: function(text){
      return 0 in arguments ?
        this.each(function(idx){
          var newText = funcArg(this, text, idx, this.textContent)
          this.textContent = newText == null ? '' : ''+newText
        }) :
        (0 in this ? this.pluck('textContent').join("") : null)
    },
    attr: function(name, value){
      var result
      return (typeof name == 'string' && !(1 in arguments)) ?
        (0 in this && this[0].nodeType == 1 && (result = this[0].getAttribute(name)) != null ? result : undefined) :
        this.each(function(idx){
          if (this.nodeType !== 1) return
          if (isObject(name)) for (key in name) setAttribute(this, key, name[key])
          else setAttribute(this, name, funcArg(this, value, idx, this.getAttribute(name)))
        })
    },
    removeAttr: function(name){
      return this.each(function(){ this.nodeType === 1 && name.split(' ').forEach(function(attribute){
        setAttribute(this, attribute)
      }, this)})
    },
    prop: function(name, value){
      name = propMap[name] || name
      return (1 in arguments) ?
        this.each(function(idx){
          this[name] = funcArg(this, value, idx, this[name])
        }) :
        (this[0] && this[0][name])
    },
    removeProp: function(name){
      name = propMap[name] || name
      return this.each(function(){ delete this[name] })
    },
    data: function(name, value){
      var attrName = 'data-' + name.replace(capitalRE, '-$1').toLowerCase()

      var data = (1 in arguments) ?
        this.attr(attrName, value) :
        this.attr(attrName)

      return data !== null ? deserializeValue(data) : undefined
    },
    val: function(value){
      if (0 in arguments) {
        if (value == null) value = ""
        return this.each(function(idx){
          this.value = funcArg(this, value, idx, this.value)
        })
      } else {
        return this[0] && (this[0].multiple ?
           $(this[0]).find('option').filter(function(){ return this.selected }).pluck('value') :
           this[0].value)
      }
    },
    offset: function(coordinates){
      if (coordinates) return this.each(function(index){
        var $this = $(this),
            coords = funcArg(this, coordinates, index, $this.offset()),
            parentOffset = $this.offsetParent().offset(),
            props = {
              top:  coords.top  - parentOffset.top,
              left: coords.left - parentOffset.left
            }

        if ($this.css('position') == 'static') props['position'] = 'relative'
        $this.css(props)
      })
      if (!this.length) return null
      if (document.documentElement !== this[0] && !$.contains(document.documentElement, this[0]))
        return {top: 0, left: 0}
      var obj = this[0].getBoundingClientRect()
      return {
        left: obj.left + window.pageXOffset,
        top: obj.top + window.pageYOffset,
        width: Math.round(obj.width),
        height: Math.round(obj.height)
      }
    },
    css: function(property, value){
      if (arguments.length < 2) {
        var element = this[0]
        if (typeof property == 'string') {
          if (!element) return
          return element.style[camelize(property)] || getComputedStyle(element, '').getPropertyValue(property)
        } else if (isArray(property)) {
          if (!element) return
          var props = {}
          var computedStyle = getComputedStyle(element, '')
          $.each(property, function(_, prop){
            props[prop] = (element.style[camelize(prop)] || computedStyle.getPropertyValue(prop))
          })
          return props
        }
      }

      var css = ''
      if (type(property) == 'string') {
        if (!value && value !== 0)
          this.each(function(){ this.style.removeProperty(dasherize(property)) })
        else
          css = dasherize(property) + ":" + maybeAddPx(property, value)
      } else {
        for (key in property)
          if (!property[key] && property[key] !== 0)
            this.each(function(){ this.style.removeProperty(dasherize(key)) })
          else
            css += dasherize(key) + ':' + maybeAddPx(key, property[key]) + ';'
      }

      return this.each(function(){ this.style.cssText += ';' + css })
    },
    index: function(element){
      return element ? this.indexOf($(element)[0]) : this.parent().children().indexOf(this[0])
    },
    hasClass: function(name){
      if (!name) return false
      return emptyArray.some.call(this, function(el){
        return this.test(className(el))
      }, classRE(name))
    },
    addClass: function(name){
      if (!name) return this
      return this.each(function(idx){
        if (!('className' in this)) return
        classList = []
        var cls = className(this), newName = funcArg(this, name, idx, cls)
        newName.split(/\s+/g).forEach(function(klass){
          if (!$(this).hasClass(klass)) classList.push(klass)
        }, this)
        classList.length && className(this, cls + (cls ? " " : "") + classList.join(" "))
      })
    },
    removeClass: function(name){
      return this.each(function(idx){
        if (!('className' in this)) return
        if (name === undefined) return className(this, '')
        classList = className(this)
        funcArg(this, name, idx, classList).split(/\s+/g).forEach(function(klass){
          classList = classList.replace(classRE(klass), " ")
        })
        className(this, classList.trim())
      })
    },
    toggleClass: function(name, when){
      if (!name) return this
      return this.each(function(idx){
        var $this = $(this), names = funcArg(this, name, idx, className(this))
        names.split(/\s+/g).forEach(function(klass){
          (when === undefined ? !$this.hasClass(klass) : when) ?
            $this.addClass(klass) : $this.removeClass(klass)
        })
      })
    },
    scrollTop: function(value){
      if (!this.length) return
      var hasScrollTop = 'scrollTop' in this[0]
      if (value === undefined) return hasScrollTop ? this[0].scrollTop : this[0].pageYOffset
      return this.each(hasScrollTop ?
        function(){ this.scrollTop = value } :
        function(){ this.scrollTo(this.scrollX, value) })
    },
    scrollLeft: function(value){
      if (!this.length) return
      var hasScrollLeft = 'scrollLeft' in this[0]
      if (value === undefined) return hasScrollLeft ? this[0].scrollLeft : this[0].pageXOffset
      return this.each(hasScrollLeft ?
        function(){ this.scrollLeft = value } :
        function(){ this.scrollTo(value, this.scrollY) })
    },
    position: function() {
      if (!this.length) return

      var elem = this[0],
        // Get *real* offsetParent
        offsetParent = this.offsetParent(),
        // Get correct offsets
        offset       = this.offset(),
        parentOffset = rootNodeRE.test(offsetParent[0].nodeName) ? { top: 0, left: 0 } : offsetParent.offset()

      // Subtract element margins
      // note: when an element has margin: auto the offsetLeft and marginLeft
      // are the same in Safari causing offset.left to incorrectly be 0
      offset.top  -= parseFloat( $(elem).css('margin-top') ) || 0
      offset.left -= parseFloat( $(elem).css('margin-left') ) || 0

      // Add offsetParent borders
      parentOffset.top  += parseFloat( $(offsetParent[0]).css('border-top-width') ) || 0
      parentOffset.left += parseFloat( $(offsetParent[0]).css('border-left-width') ) || 0

      // Subtract the two offsets
      return {
        top:  offset.top  - parentOffset.top,
        left: offset.left - parentOffset.left
      }
    },
    offsetParent: function() {
      return this.map(function(){
        var parent = this.offsetParent || document.body
        while (parent && !rootNodeRE.test(parent.nodeName) && $(parent).css("position") == "static")
          parent = parent.offsetParent
        return parent
      })
    }
  }

  // for now
  $.fn.detach = $.fn.remove

  // Generate the `width` and `height` functions
  ;['width', 'height'].forEach(function(dimension){
    var dimensionProperty =
      dimension.replace(/./, function(m){ return m[0].toUpperCase() })

    $.fn[dimension] = function(value){
      var offset, el = this[0]
      if (value === undefined) return isWindow(el) ? el['inner' + dimensionProperty] :
        isDocument(el) ? el.documentElement['scroll' + dimensionProperty] :
        (offset = this.offset()) && offset[dimension]
      else return this.each(function(idx){
        el = $(this)
        el.css(dimension, funcArg(this, value, idx, el[dimension]()))
      })
    }
  })

  function traverseNode(node, fun) {
    fun(node)
    for (var i = 0, len = node.childNodes.length; i < len; i++)
      traverseNode(node.childNodes[i], fun)
  }

  // Generate the `after`, `prepend`, `before`, `append`,
  // `insertAfter`, `insertBefore`, `appendTo`, and `prependTo` methods.
  adjacencyOperators.forEach(function(operator, operatorIndex) {
    var inside = operatorIndex % 2 //=> prepend, append

    $.fn[operator] = function(){
      // arguments can be nodes, arrays of nodes, Zepto objects and HTML strings
      var argType, nodes = $.map(arguments, function(arg) {
            var arr = []
            argType = type(arg)
            if (argType == "array") {
              arg.forEach(function(el) {
                if (el.nodeType !== undefined) return arr.push(el)
                else if ($.zepto.isZ(el)) return arr = arr.concat(el.get())
                arr = arr.concat(zepto.fragment(el))
              })
              return arr
            }
            return argType == "object" || arg == null ?
              arg : zepto.fragment(arg)
          }),
          parent, copyByClone = this.length > 1
      if (nodes.length < 1) return this

      return this.each(function(_, target){
        parent = inside ? target : target.parentNode

        // convert all methods to a "before" operation
        target = operatorIndex == 0 ? target.nextSibling :
                 operatorIndex == 1 ? target.firstChild :
                 operatorIndex == 2 ? target :
                 null

        var parentInDocument = $.contains(document.documentElement, parent)

        nodes.forEach(function(node){
          if (copyByClone) node = node.cloneNode(true)
          else if (!parent) return $(node).remove()

          parent.insertBefore(node, target)
          if (parentInDocument) traverseNode(node, function(el){
            if (el.nodeName != null && el.nodeName.toUpperCase() === 'SCRIPT' &&
               (!el.type || el.type === 'text/javascript') && !el.src){
              var target = el.ownerDocument ? el.ownerDocument.defaultView : window
              target['eval'].call(target, el.innerHTML)
            }
          })
        })
      })
    }

    // after    => insertAfter
    // prepend  => prependTo
    // before   => insertBefore
    // append   => appendTo
    $.fn[inside ? operator+'To' : 'insert'+(operatorIndex ? 'Before' : 'After')] = function(html){
      $(html)[operator](this)
      return this
    }
  })

  zepto.Z.prototype = Z.prototype = $.fn

  // Export internal API functions in the `$.zepto` namespace
  zepto.uniq = uniq
  zepto.deserializeValue = deserializeValue
  $.zepto = zepto

  return $
})()

window.Zepto = Zepto
window.$ === undefined && (window.$ = Zepto)

;(function($){
  var _zid = 1, undefined,
      slice = Array.prototype.slice,
      isFunction = $.isFunction,
      isString = function(obj){ return typeof obj == 'string' },
      handlers = {},
      specialEvents={},
      focusinSupported = 'onfocusin' in window,
      focus = { focus: 'focusin', blur: 'focusout' },
      hover = { mouseenter: 'mouseover', mouseleave: 'mouseout' }

  specialEvents.click = specialEvents.mousedown = specialEvents.mouseup = specialEvents.mousemove = 'MouseEvents'

  function zid(element) {
    return element._zid || (element._zid = _zid++)
  }
  function findHandlers(element, event, fn, selector) {
    event = parse(event)
    if (event.ns) var matcher = matcherFor(event.ns)
    return (handlers[zid(element)] || []).filter(function(handler) {
      return handler
        && (!event.e  || handler.e == event.e)
        && (!event.ns || matcher.test(handler.ns))
        && (!fn       || zid(handler.fn) === zid(fn))
        && (!selector || handler.sel == selector)
    })
  }
  function parse(event) {
    var parts = ('' + event).split('.')
    return {e: parts[0], ns: parts.slice(1).sort().join(' ')}
  }
  function matcherFor(ns) {
    return new RegExp('(?:^| )' + ns.replace(' ', ' .* ?') + '(?: |$)')
  }

  function eventCapture(handler, captureSetting) {
    return handler.del &&
      (!focusinSupported && (handler.e in focus)) ||
      !!captureSetting
  }

  function realEvent(type) {
    return hover[type] || (focusinSupported && focus[type]) || type
  }

  function add(element, events, fn, data, selector, delegator, capture){
    var id = zid(element), set = (handlers[id] || (handlers[id] = []))
    events.split(/\s/).forEach(function(event){
      if (event == 'ready') return $(document).ready(fn)
      var handler   = parse(event)
      handler.fn    = fn
      handler.sel   = selector
      // emulate mouseenter, mouseleave
      if (handler.e in hover) fn = function(e){
        var related = e.relatedTarget
        if (!related || (related !== this && !$.contains(this, related)))
          return handler.fn.apply(this, arguments)
      }
      handler.del   = delegator
      var callback  = delegator || fn
      handler.proxy = function(e){
        e = compatible(e)
        if (e.isImmediatePropagationStopped()) return
        e.data = data
        var result = callback.apply(element, e._args == undefined ? [e] : [e].concat(e._args))
        if (result === false) e.preventDefault(), e.stopPropagation()
        return result
      }
      handler.i = set.length
      set.push(handler)
      if ('addEventListener' in element)
        element.addEventListener(realEvent(handler.e), handler.proxy, eventCapture(handler, capture))
    })
  }
  function remove(element, events, fn, selector, capture){
    var id = zid(element)
    ;(events || '').split(/\s/).forEach(function(event){
      findHandlers(element, event, fn, selector).forEach(function(handler){
        delete handlers[id][handler.i]
      if ('removeEventListener' in element)
        element.removeEventListener(realEvent(handler.e), handler.proxy, eventCapture(handler, capture))
      })
    })
  }

  $.event = { add: add, remove: remove }

  $.proxy = function(fn, context) {
    var args = (2 in arguments) && slice.call(arguments, 2)
    if (isFunction(fn)) {
      var proxyFn = function(){ return fn.apply(context, args ? args.concat(slice.call(arguments)) : arguments) }
      proxyFn._zid = zid(fn)
      return proxyFn
    } else if (isString(context)) {
      if (args) {
        args.unshift(fn[context], fn)
        return $.proxy.apply(null, args)
      } else {
        return $.proxy(fn[context], fn)
      }
    } else {
      throw new TypeError("expected function")
    }
  }

  $.fn.bind = function(event, data, callback){
    return this.on(event, data, callback)
  }
  $.fn.unbind = function(event, callback){
    return this.off(event, callback)
  }
  $.fn.one = function(event, selector, data, callback){
    return this.on(event, selector, data, callback, 1)
  }

  var returnTrue = function(){return true},
      returnFalse = function(){return false},
      ignoreProperties = /^([A-Z]|returnValue$|layer[XY]$|webkitMovement[XY]$)/,
      eventMethods = {
        preventDefault: 'isDefaultPrevented',
        stopImmediatePropagation: 'isImmediatePropagationStopped',
        stopPropagation: 'isPropagationStopped'
      }

  function compatible(event, source) {
    if (source || !event.isDefaultPrevented) {
      source || (source = event)

      $.each(eventMethods, function(name, predicate) {
        var sourceMethod = source[name]
        event[name] = function(){
          this[predicate] = returnTrue
          return sourceMethod && sourceMethod.apply(source, arguments)
        }
        event[predicate] = returnFalse
      })

      event.timeStamp || (event.timeStamp = Date.now())

      if (source.defaultPrevented !== undefined ? source.defaultPrevented :
          'returnValue' in source ? source.returnValue === false :
          source.getPreventDefault && source.getPreventDefault())
        event.isDefaultPrevented = returnTrue
    }
    return event
  }

  function createProxy(event) {
    var key, proxy = { originalEvent: event }
    for (key in event)
      if (!ignoreProperties.test(key) && event[key] !== undefined) proxy[key] = event[key]

    return compatible(proxy, event)
  }

  $.fn.delegate = function(selector, event, callback){
    return this.on(event, selector, callback)
  }
  $.fn.undelegate = function(selector, event, callback){
    return this.off(event, selector, callback)
  }

  $.fn.live = function(event, callback){
    $(document.body).delegate(this.selector, event, callback)
    return this
  }
  $.fn.die = function(event, callback){
    $(document.body).undelegate(this.selector, event, callback)
    return this
  }

  $.fn.on = function(event, selector, data, callback, one){
    var autoRemove, delegator, $this = this
    if (event && !isString(event)) {
      $.each(event, function(type, fn){
        $this.on(type, selector, data, fn, one)
      })
      return $this
    }

    if (!isString(selector) && !isFunction(callback) && callback !== false)
      callback = data, data = selector, selector = undefined
    if (callback === undefined || data === false)
      callback = data, data = undefined

    if (callback === false) callback = returnFalse

    return $this.each(function(_, element){
      if (one) autoRemove = function(e){
        remove(element, e.type, callback)
        return callback.apply(this, arguments)
      }

      if (selector) delegator = function(e){
        var evt, match = $(e.target).closest(selector, element).get(0)
        if (match && match !== element) {
          evt = $.extend(createProxy(e), {currentTarget: match, liveFired: element})
          return (autoRemove || callback).apply(match, [evt].concat(slice.call(arguments, 1)))
        }
      }

      add(element, event, callback, data, selector, delegator || autoRemove)
    })
  }
  $.fn.off = function(event, selector, callback){
    var $this = this
    if (event && !isString(event)) {
      $.each(event, function(type, fn){
        $this.off(type, selector, fn)
      })
      return $this
    }

    if (!isString(selector) && !isFunction(callback) && callback !== false)
      callback = selector, selector = undefined

    if (callback === false) callback = returnFalse

    return $this.each(function(){
      remove(this, event, callback, selector)
    })
  }

  $.fn.trigger = function(event, args){
    event = (isString(event) || $.isPlainObject(event)) ? $.Event(event) : compatible(event)
    event._args = args
    return this.each(function(){
      // handle focus(), blur() by calling them directly
      if (event.type in focus && typeof this[event.type] == "function") this[event.type]()
      // items in the collection might not be DOM elements
      else if ('dispatchEvent' in this) this.dispatchEvent(event)
      else $(this).triggerHandler(event, args)
    })
  }

  // triggers event handlers on current element just as if an event occurred,
  // doesn't trigger an actual event, doesn't bubble
  $.fn.triggerHandler = function(event, args){
    var e, result
    this.each(function(i, element){
      e = createProxy(isString(event) ? $.Event(event) : event)
      e._args = args
      e.target = element
      $.each(findHandlers(element, event.type || event), function(i, handler){
        result = handler.proxy(e)
        if (e.isImmediatePropagationStopped()) return false
      })
    })
    return result
  }

  // shortcut methods for `.bind(event, fn)` for each event type
  ;('focusin focusout focus blur load resize scroll unload click dblclick '+
  'mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave '+
  'change select keydown keypress keyup error').split(' ').forEach(function(event) {
    $.fn[event] = function(callback) {
      return (0 in arguments) ?
        this.bind(event, callback) :
        this.trigger(event)
    }
  })

  $.Event = function(type, props) {
    if (!isString(type)) props = type, type = props.type
    var event = document.createEvent(specialEvents[type] || 'Events'), bubbles = true
    if (props) for (var name in props) (name == 'bubbles') ? (bubbles = !!props[name]) : (event[name] = props[name])
    event.initEvent(type, bubbles, true)
    return compatible(event)
  }

})(Zepto)

;(function($){
  var jsonpID = +new Date(),
      document = window.document,
      key,
      name,
      rscript = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
      scriptTypeRE = /^(?:text|application)\/javascript/i,
      xmlTypeRE = /^(?:text|application)\/xml/i,
      jsonType = 'application/json',
      htmlType = 'text/html',
      blankRE = /^\s*$/,
      originAnchor = document.createElement('a')

  originAnchor.href = window.location.href

  // trigger a custom event and return false if it was cancelled
  function triggerAndReturn(context, eventName, data) {
    var event = $.Event(eventName)
    $(context).trigger(event, data)
    return !event.isDefaultPrevented()
  }

  // trigger an Ajax "global" event
  function triggerGlobal(settings, context, eventName, data) {
    if (settings.global) return triggerAndReturn(context || document, eventName, data)
  }

  // Number of active Ajax requests
  $.active = 0

  function ajaxStart(settings) {
    if (settings.global && $.active++ === 0) triggerGlobal(settings, null, 'ajaxStart')
  }
  function ajaxStop(settings) {
    if (settings.global && !(--$.active)) triggerGlobal(settings, null, 'ajaxStop')
  }

  // triggers an extra global event "ajaxBeforeSend" that's like "ajaxSend" but cancelable
  function ajaxBeforeSend(xhr, settings) {
    var context = settings.context
    if (settings.beforeSend.call(context, xhr, settings) === false ||
        triggerGlobal(settings, context, 'ajaxBeforeSend', [xhr, settings]) === false)
      return false

    triggerGlobal(settings, context, 'ajaxSend', [xhr, settings])
  }
  function ajaxSuccess(data, xhr, settings, deferred) {
    var context = settings.context, status = 'success'
    settings.success.call(context, data, status, xhr)
    if (deferred) deferred.resolveWith(context, [data, status, xhr])
    triggerGlobal(settings, context, 'ajaxSuccess', [xhr, settings, data])
    ajaxComplete(status, xhr, settings)
  }
  // type: "timeout", "error", "abort", "parsererror"
  function ajaxError(error, type, xhr, settings, deferred) {
    var context = settings.context
    settings.error.call(context, xhr, type, error)
    if (deferred) deferred.rejectWith(context, [xhr, type, error])
    triggerGlobal(settings, context, 'ajaxError', [xhr, settings, error || type])
    ajaxComplete(type, xhr, settings)
  }
  // status: "success", "notmodified", "error", "timeout", "abort", "parsererror"
  function ajaxComplete(status, xhr, settings) {
    var context = settings.context
    settings.complete.call(context, xhr, status)
    triggerGlobal(settings, context, 'ajaxComplete', [xhr, settings])
    ajaxStop(settings)
  }

  function ajaxDataFilter(data, type, settings) {
    if (settings.dataFilter == empty) return data
    var context = settings.context
    return settings.dataFilter.call(context, data, type)
  }

  // Empty function, used as default callback
  function empty() {}

  $.ajaxJSONP = function(options, deferred){
    if (!('type' in options)) return $.ajax(options)

    var _callbackName = options.jsonpCallback,
      callbackName = ($.isFunction(_callbackName) ?
        _callbackName() : _callbackName) || ('Zepto' + (jsonpID++)),
      script = document.createElement('script'),
      originalCallback = window[callbackName],
      responseData,
      abort = function(errorType) {
        $(script).triggerHandler('error', errorType || 'abort')
      },
      xhr = { abort: abort }, abortTimeout

    if (deferred) deferred.promise(xhr)

    $(script).on('load error', function(e, errorType){
      clearTimeout(abortTimeout)
      $(script).off().remove()

      if (e.type == 'error' || !responseData) {
        ajaxError(null, errorType || 'error', xhr, options, deferred)
      } else {
        ajaxSuccess(responseData[0], xhr, options, deferred)
      }

      window[callbackName] = originalCallback
      if (responseData && $.isFunction(originalCallback))
        originalCallback(responseData[0])

      originalCallback = responseData = undefined
    })

    if (ajaxBeforeSend(xhr, options) === false) {
      abort('abort')
      return xhr
    }

    window[callbackName] = function(){
      responseData = arguments
    }

    script.src = options.url.replace(/\?(.+)=\?/, '?$1=' + callbackName)
    document.head.appendChild(script)

    if (options.timeout > 0) abortTimeout = setTimeout(function(){
      abort('timeout')
    }, options.timeout)

    return xhr
  }

  $.ajaxSettings = {
    // Default type of request
    type: 'GET',
    // Callback that is executed before request
    beforeSend: empty,
    // Callback that is executed if the request succeeds
    success: empty,
    // Callback that is executed the the server drops error
    error: empty,
    // Callback that is executed on request complete (both: error and success)
    complete: empty,
    // The context for the callbacks
    context: null,
    // Whether to trigger "global" Ajax events
    global: true,
    // Transport
    xhr: function () {
      return new window.XMLHttpRequest()
    },
    // MIME types mapping
    // IIS returns Javascript as "application/x-javascript"
    accepts: {
      script: 'text/javascript, application/javascript, application/x-javascript',
      json:   jsonType,
      xml:    'application/xml, text/xml',
      html:   htmlType,
      text:   'text/plain'
    },
    // Whether the request is to another domain
    crossDomain: false,
    // Default timeout
    timeout: 0,
    // Whether data should be serialized to string
    processData: true,
    // Whether the browser should be allowed to cache GET responses
    cache: true,
    //Used to handle the raw response data of XMLHttpRequest.
    //This is a pre-filtering function to sanitize the response.
    //The sanitized response should be returned
    dataFilter: empty
  }

  function mimeToDataType(mime) {
    if (mime) mime = mime.split(';', 2)[0]
    return mime && ( mime == htmlType ? 'html' :
      mime == jsonType ? 'json' :
      scriptTypeRE.test(mime) ? 'script' :
      xmlTypeRE.test(mime) && 'xml' ) || 'text'
  }

  function appendQuery(url, query) {
    if (query == '') return url
    return (url + '&' + query).replace(/[&?]{1,2}/, '?')
  }

  // serialize payload and append it to the URL for GET requests
  function serializeData(options) {
    if (options.processData && options.data && $.type(options.data) != "string")
      options.data = $.param(options.data, options.traditional)
    if (options.data && (!options.type || options.type.toUpperCase() == 'GET' || 'jsonp' == options.dataType))
      options.url = appendQuery(options.url, options.data), options.data = undefined
  }

  $.ajax = function(options){
    var settings = $.extend({}, options || {}),
        deferred = $.Deferred && $.Deferred(),
        urlAnchor, hashIndex
    for (key in $.ajaxSettings) if (settings[key] === undefined) settings[key] = $.ajaxSettings[key]

    ajaxStart(settings)

    if (!settings.crossDomain) {
      urlAnchor = document.createElement('a')
      urlAnchor.href = settings.url
      // cleans up URL for .href (IE only), see https://github.com/madrobby/zepto/pull/1049
      urlAnchor.href = urlAnchor.href
      settings.crossDomain = (originAnchor.protocol + '//' + originAnchor.host) !== (urlAnchor.protocol + '//' + urlAnchor.host)
    }

    if (!settings.url) settings.url = window.location.toString()
    if ((hashIndex = settings.url.indexOf('#')) > -1) settings.url = settings.url.slice(0, hashIndex)
    serializeData(settings)

    var dataType = settings.dataType, hasPlaceholder = /\?.+=\?/.test(settings.url)
    if (hasPlaceholder) dataType = 'jsonp'

    if (settings.cache === false || (
         (!options || options.cache !== true) &&
         ('script' == dataType || 'jsonp' == dataType)
        ))
      settings.url = appendQuery(settings.url, '_=' + Date.now())

    if ('jsonp' == dataType) {
      if (!hasPlaceholder)
        settings.url = appendQuery(settings.url,
          settings.jsonp ? (settings.jsonp + '=?') : settings.jsonp === false ? '' : 'callback=?')
      return $.ajaxJSONP(settings, deferred)
    }

    var mime = settings.accepts[dataType],
        headers = { },
        setHeader = function(name, value) { headers[name.toLowerCase()] = [name, value] },
        protocol = /^([\w-]+:)\/\//.test(settings.url) ? RegExp.$1 : window.location.protocol,
        xhr = settings.xhr(),
        nativeSetHeader = xhr.setRequestHeader,
        abortTimeout

    if (deferred) deferred.promise(xhr)

    if (!settings.crossDomain) setHeader('X-Requested-With', 'XMLHttpRequest')
    setHeader('Accept', mime || '*/*')
    if (mime = settings.mimeType || mime) {
      if (mime.indexOf(',') > -1) mime = mime.split(',', 2)[0]
      xhr.overrideMimeType && xhr.overrideMimeType(mime)
    }
    if (settings.contentType || (settings.contentType !== false && settings.data && settings.type.toUpperCase() != 'GET'))
      setHeader('Content-Type', settings.contentType || 'application/x-www-form-urlencoded')

    if (settings.headers) for (name in settings.headers) setHeader(name, settings.headers[name])
    xhr.setRequestHeader = setHeader

    xhr.onreadystatechange = function(){
      if (xhr.readyState == 4) {
        xhr.onreadystatechange = empty
        clearTimeout(abortTimeout)
        var result, error = false
        if ((xhr.status >= 200 && xhr.status < 300) || xhr.status == 304 || (xhr.status == 0 && protocol == 'file:')) {
          dataType = dataType || mimeToDataType(settings.mimeType || xhr.getResponseHeader('content-type'))

          if (xhr.responseType == 'arraybuffer' || xhr.responseType == 'blob')
            result = xhr.response
          else {
            result = xhr.responseText

            try {
              // http://perfectionkills.com/global-eval-what-are-the-options/
              // sanitize response accordingly if data filter callback provided
              result = ajaxDataFilter(result, dataType, settings)
              if (dataType == 'script')    (1,eval)(result)
              else if (dataType == 'xml')  result = xhr.responseXML
              else if (dataType == 'json') result = blankRE.test(result) ? null : $.parseJSON(result)
            } catch (e) { error = e }

            if (error) return ajaxError(error, 'parsererror', xhr, settings, deferred)
          }

          ajaxSuccess(result, xhr, settings, deferred)
        } else {
          ajaxError(xhr.statusText || null, xhr.status ? 'error' : 'abort', xhr, settings, deferred)
        }
      }
    }

    if (ajaxBeforeSend(xhr, settings) === false) {
      xhr.abort()
      ajaxError(null, 'abort', xhr, settings, deferred)
      return xhr
    }

    var async = 'async' in settings ? settings.async : true
    xhr.open(settings.type, settings.url, async, settings.username, settings.password)

    if (settings.xhrFields) for (name in settings.xhrFields) xhr[name] = settings.xhrFields[name]

    for (name in headers) nativeSetHeader.apply(xhr, headers[name])

    if (settings.timeout > 0) abortTimeout = setTimeout(function(){
        xhr.onreadystatechange = empty
        xhr.abort()
        ajaxError(null, 'timeout', xhr, settings, deferred)
      }, settings.timeout)

    // avoid sending empty string (#319)
    xhr.send(settings.data ? settings.data : null)
    return xhr
  }

  // handle optional data/success arguments
  function parseArguments(url, data, success, dataType) {
    if ($.isFunction(data)) dataType = success, success = data, data = undefined
    if (!$.isFunction(success)) dataType = success, success = undefined
    return {
      url: url
    , data: data
    , success: success
    , dataType: dataType
    }
  }

  $.get = function(/* url, data, success, dataType */){
    return $.ajax(parseArguments.apply(null, arguments))
  }

  $.post = function(/* url, data, success, dataType */){
    var options = parseArguments.apply(null, arguments)
    options.type = 'POST'
    return $.ajax(options)
  }

  $.getJSON = function(/* url, data, success */){
    var options = parseArguments.apply(null, arguments)
    options.dataType = 'json'
    return $.ajax(options)
  }

  $.fn.load = function(url, data, success){
    if (!this.length) return this
    var self = this, parts = url.split(/\s/), selector,
        options = parseArguments(url, data, success),
        callback = options.success
    if (parts.length > 1) options.url = parts[0], selector = parts[1]
    options.success = function(response){
      self.html(selector ?
        $('<div>').html(response.replace(rscript, "")).find(selector)
        : response)
      callback && callback.apply(self, arguments)
    }
    $.ajax(options)
    return this
  }

  var escape = encodeURIComponent

  function serialize(params, obj, traditional, scope){
    var type, array = $.isArray(obj), hash = $.isPlainObject(obj)
    $.each(obj, function(key, value) {
      type = $.type(value)
      if (scope) key = traditional ? scope :
        scope + '[' + (hash || type == 'object' || type == 'array' ? key : '') + ']'
      // handle data in serializeArray() format
      if (!scope && array) params.add(value.name, value.value)
      // recurse into nested objects
      else if (type == "array" || (!traditional && type == "object"))
        serialize(params, value, traditional, key)
      else params.add(key, value)
    })
  }

  $.param = function(obj, traditional){
    var params = []
    params.add = function(key, value) {
      if ($.isFunction(value)) value = value()
      if (value == null) value = ""
      this.push(escape(key) + '=' + escape(value))
    }
    serialize(params, obj, traditional)
    return params.join('&').replace(/%20/g, '+')
  }
})(Zepto)

;(function($){
  $.fn.serializeArray = function() {
    var name, type, result = [],
      add = function(value) {
        if (value.forEach) return value.forEach(add)
        result.push({ name: name, value: value })
      }
    if (this[0]) $.each(this[0].elements, function(_, field){
      type = field.type, name = field.name
      if (name && field.nodeName.toLowerCase() != 'fieldset' &&
        !field.disabled && type != 'submit' && type != 'reset' && type != 'button' && type != 'file' &&
        ((type != 'radio' && type != 'checkbox') || field.checked))
          add($(field).val())
    })
    return result
  }

  $.fn.serialize = function(){
    var result = []
    this.serializeArray().forEach(function(elm){
      result.push(encodeURIComponent(elm.name) + '=' + encodeURIComponent(elm.value))
    })
    return result.join('&')
  }

  $.fn.submit = function(callback) {
    if (0 in arguments) this.bind('submit', callback)
    else if (this.length) {
      var event = $.Event('submit')
      this.eq(0).trigger(event)
      if (!event.isDefaultPrevented()) this.get(0).submit()
    }
    return this
  }

})(Zepto)

;(function(){
  // getComputedStyle shouldn't freak out when called
  // without a valid element as argument
  try {
    getComputedStyle(undefined)
  } catch(e) {
    var nativeGetComputedStyle = getComputedStyle
    window.getComputedStyle = function(element, pseudoElement){
      try {
        return nativeGetComputedStyle(element, pseudoElement)
      } catch(e) {
        return null
      }
    }
  }
})()
  return Zepto
}))

}.call(window));

/***/ }),

/***/ "./node_modules/jump.js/dist/jump.module.js":
/*!**************************************************!*\
  !*** ./node_modules/jump.js/dist/jump.module.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// Robert Penner's easeInOutQuad

// find the rest of his easing functions here: http://robertpenner.com/easing/
// find them exported for ES6 consumption here: https://github.com/jaxgeller/ez.js

var easeInOutQuad = function easeInOutQuad(t, b, c, d) {
  t /= d / 2;
  if (t < 1) return c / 2 * t * t + b;
  t--;
  return -c / 2 * (t * (t - 2) - 1) + b;
};

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
  return typeof obj;
} : function (obj) {
  return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
};

var jumper = function jumper() {
  // private variable cache
  // no variables are created during a jump, preventing memory leaks

  var element = void 0; // element to scroll to                   (node)

  var start = void 0; // where scroll starts                    (px)
  var stop = void 0; // where scroll stops                     (px)

  var offset = void 0; // adjustment from the stop position      (px)
  var easing = void 0; // easing function                        (function)
  var a11y = void 0; // accessibility support flag             (boolean)

  var distance = void 0; // distance of scroll                     (px)
  var duration = void 0; // scroll duration                        (ms)

  var timeStart = void 0; // time scroll started                    (ms)
  var timeElapsed = void 0; // time spent scrolling thus far          (ms)

  var next = void 0; // next scroll position                   (px)

  var callback = void 0; // to call when done scrolling            (function)

  // scroll position helper

  function location() {
    return window.scrollY || window.pageYOffset;
  }

  // element offset helper

  function top(element) {
    return element.getBoundingClientRect().top + start;
  }

  // rAF loop helper

  function loop(timeCurrent) {
    // store time scroll started, if not started already
    if (!timeStart) {
      timeStart = timeCurrent;
    }

    // determine time spent scrolling so far
    timeElapsed = timeCurrent - timeStart;

    // calculate next scroll position
    next = easing(timeElapsed, start, distance, duration);

    // scroll to it
    window.scrollTo(0, next);

    // check progress
    timeElapsed < duration ? window.requestAnimationFrame(loop) // continue scroll loop
    : done(); // scrolling is done
  }

  // scroll finished helper

  function done() {
    // account for rAF time rounding inaccuracies
    window.scrollTo(0, start + distance);

    // if scrolling to an element, and accessibility is enabled
    if (element && a11y) {
      // add tabindex indicating programmatic focus
      element.setAttribute('tabindex', '-1');

      // focus the element
      element.focus();
    }

    // if it exists, fire the callback
    if (typeof callback === 'function') {
      callback();
    }

    // reset time for next jump
    timeStart = false;
  }

  // API

  function jump(target) {
    var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    // resolve options, or use defaults
    duration = options.duration || 1000;
    offset = options.offset || 0;
    callback = options.callback; // "undefined" is a suitable default, and won't be called
    easing = options.easing || easeInOutQuad;
    a11y = options.a11y || false;

    // cache starting position
    start = location();

    // resolve target
    switch (typeof target === 'undefined' ? 'undefined' : _typeof(target)) {
      // scroll from current position
      case 'number':
        element = undefined; // no element to scroll to
        a11y = false; // make sure accessibility is off
        stop = start + target;
        break;

      // scroll to element (node)
      // bounding rect is relative to the viewport
      case 'object':
        element = target;
        stop = top(element);
        break;

      // scroll to element (selector)
      // bounding rect is relative to the viewport
      case 'string':
        element = document.querySelector(target);
        stop = top(element);
        break;
    }

    // resolve scroll distance, accounting for offset
    distance = stop - start + offset;

    // resolve duration
    switch (_typeof(options.duration)) {
      // number in ms
      case 'number':
        duration = options.duration;
        break;

      // function passed the distance of the scroll
      case 'function':
        duration = options.duration(distance);
        break;
    }

    // start the loop
    window.requestAnimationFrame(loop);
  }

  // expose only the jump method
  return jump;
};

// export singleton

var singleton = jumper();

/* harmony default export */ __webpack_exports__["default"] = (singleton);


/***/ }),

/***/ "./node_modules/lodash/_Symbol.js":
/*!****************************************!*\
  !*** ./node_modules/lodash/_Symbol.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var root = __webpack_require__(/*! ./_root */ "./node_modules/lodash/_root.js");

/** Built-in value references. */
var Symbol = root.Symbol;

module.exports = Symbol;


/***/ }),

/***/ "./node_modules/lodash/_arrayPush.js":
/*!*******************************************!*\
  !*** ./node_modules/lodash/_arrayPush.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Appends the elements of `values` to `array`.
 *
 * @private
 * @param {Array} array The array to modify.
 * @param {Array} values The values to append.
 * @returns {Array} Returns `array`.
 */
function arrayPush(array, values) {
  var index = -1,
      length = values.length,
      offset = array.length;

  while (++index < length) {
    array[offset + index] = values[index];
  }
  return array;
}

module.exports = arrayPush;


/***/ }),

/***/ "./node_modules/lodash/_baseFlatten.js":
/*!*********************************************!*\
  !*** ./node_modules/lodash/_baseFlatten.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var arrayPush = __webpack_require__(/*! ./_arrayPush */ "./node_modules/lodash/_arrayPush.js"),
    isFlattenable = __webpack_require__(/*! ./_isFlattenable */ "./node_modules/lodash/_isFlattenable.js");

/**
 * The base implementation of `_.flatten` with support for restricting flattening.
 *
 * @private
 * @param {Array} array The array to flatten.
 * @param {number} depth The maximum recursion depth.
 * @param {boolean} [predicate=isFlattenable] The function invoked per iteration.
 * @param {boolean} [isStrict] Restrict to values that pass `predicate` checks.
 * @param {Array} [result=[]] The initial result value.
 * @returns {Array} Returns the new flattened array.
 */
function baseFlatten(array, depth, predicate, isStrict, result) {
  var index = -1,
      length = array.length;

  predicate || (predicate = isFlattenable);
  result || (result = []);

  while (++index < length) {
    var value = array[index];
    if (depth > 0 && predicate(value)) {
      if (depth > 1) {
        // Recursively flatten arrays (susceptible to call stack limits).
        baseFlatten(value, depth - 1, predicate, isStrict, result);
      } else {
        arrayPush(result, value);
      }
    } else if (!isStrict) {
      result[result.length] = value;
    }
  }
  return result;
}

module.exports = baseFlatten;


/***/ }),

/***/ "./node_modules/lodash/_baseGetTag.js":
/*!********************************************!*\
  !*** ./node_modules/lodash/_baseGetTag.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var Symbol = __webpack_require__(/*! ./_Symbol */ "./node_modules/lodash/_Symbol.js"),
    getRawTag = __webpack_require__(/*! ./_getRawTag */ "./node_modules/lodash/_getRawTag.js"),
    objectToString = __webpack_require__(/*! ./_objectToString */ "./node_modules/lodash/_objectToString.js");

/** `Object#toString` result references. */
var nullTag = '[object Null]',
    undefinedTag = '[object Undefined]';

/** Built-in value references. */
var symToStringTag = Symbol ? Symbol.toStringTag : undefined;

/**
 * The base implementation of `getTag` without fallbacks for buggy environments.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the `toStringTag`.
 */
function baseGetTag(value) {
  if (value == null) {
    return value === undefined ? undefinedTag : nullTag;
  }
  return (symToStringTag && symToStringTag in Object(value))
    ? getRawTag(value)
    : objectToString(value);
}

module.exports = baseGetTag;


/***/ }),

/***/ "./node_modules/lodash/_baseIsArguments.js":
/*!*************************************************!*\
  !*** ./node_modules/lodash/_baseIsArguments.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var baseGetTag = __webpack_require__(/*! ./_baseGetTag */ "./node_modules/lodash/_baseGetTag.js"),
    isObjectLike = __webpack_require__(/*! ./isObjectLike */ "./node_modules/lodash/isObjectLike.js");

/** `Object#toString` result references. */
var argsTag = '[object Arguments]';

/**
 * The base implementation of `_.isArguments`.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an `arguments` object,
 */
function baseIsArguments(value) {
  return isObjectLike(value) && baseGetTag(value) == argsTag;
}

module.exports = baseIsArguments;


/***/ }),

/***/ "./node_modules/lodash/_freeGlobal.js":
/*!********************************************!*\
  !*** ./node_modules/lodash/_freeGlobal.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof global == 'object' && global && global.Object === Object && global;

module.exports = freeGlobal;

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/lodash/_getRawTag.js":
/*!*******************************************!*\
  !*** ./node_modules/lodash/_getRawTag.js ***!
  \*******************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var Symbol = __webpack_require__(/*! ./_Symbol */ "./node_modules/lodash/_Symbol.js");

/** Used for built-in method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString = objectProto.toString;

/** Built-in value references. */
var symToStringTag = Symbol ? Symbol.toStringTag : undefined;

/**
 * A specialized version of `baseGetTag` which ignores `Symbol.toStringTag` values.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the raw `toStringTag`.
 */
function getRawTag(value) {
  var isOwn = hasOwnProperty.call(value, symToStringTag),
      tag = value[symToStringTag];

  try {
    value[symToStringTag] = undefined;
    var unmasked = true;
  } catch (e) {}

  var result = nativeObjectToString.call(value);
  if (unmasked) {
    if (isOwn) {
      value[symToStringTag] = tag;
    } else {
      delete value[symToStringTag];
    }
  }
  return result;
}

module.exports = getRawTag;


/***/ }),

/***/ "./node_modules/lodash/_isFlattenable.js":
/*!***********************************************!*\
  !*** ./node_modules/lodash/_isFlattenable.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var Symbol = __webpack_require__(/*! ./_Symbol */ "./node_modules/lodash/_Symbol.js"),
    isArguments = __webpack_require__(/*! ./isArguments */ "./node_modules/lodash/isArguments.js"),
    isArray = __webpack_require__(/*! ./isArray */ "./node_modules/lodash/isArray.js");

/** Built-in value references. */
var spreadableSymbol = Symbol ? Symbol.isConcatSpreadable : undefined;

/**
 * Checks if `value` is a flattenable `arguments` object or array.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is flattenable, else `false`.
 */
function isFlattenable(value) {
  return isArray(value) || isArguments(value) ||
    !!(spreadableSymbol && value && value[spreadableSymbol]);
}

module.exports = isFlattenable;


/***/ }),

/***/ "./node_modules/lodash/_objectToString.js":
/*!************************************************!*\
  !*** ./node_modules/lodash/_objectToString.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString = objectProto.toString;

/**
 * Converts `value` to a string using `Object.prototype.toString`.
 *
 * @private
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 */
function objectToString(value) {
  return nativeObjectToString.call(value);
}

module.exports = objectToString;


/***/ }),

/***/ "./node_modules/lodash/_root.js":
/*!**************************************!*\
  !*** ./node_modules/lodash/_root.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var freeGlobal = __webpack_require__(/*! ./_freeGlobal */ "./node_modules/lodash/_freeGlobal.js");

/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = freeGlobal || freeSelf || Function('return this')();

module.exports = root;


/***/ }),

/***/ "./node_modules/lodash/flattenDeep.js":
/*!********************************************!*\
  !*** ./node_modules/lodash/flattenDeep.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var baseFlatten = __webpack_require__(/*! ./_baseFlatten */ "./node_modules/lodash/_baseFlatten.js");

/** Used as references for various `Number` constants. */
var INFINITY = 1 / 0;

/**
 * Recursively flattens `array`.
 *
 * @static
 * @memberOf _
 * @since 3.0.0
 * @category Array
 * @param {Array} array The array to flatten.
 * @returns {Array} Returns the new flattened array.
 * @example
 *
 * _.flattenDeep([1, [2, [3, [4]], 5]]);
 * // => [1, 2, 3, 4, 5]
 */
function flattenDeep(array) {
  var length = array == null ? 0 : array.length;
  return length ? baseFlatten(array, INFINITY) : [];
}

module.exports = flattenDeep;


/***/ }),

/***/ "./node_modules/lodash/isArguments.js":
/*!********************************************!*\
  !*** ./node_modules/lodash/isArguments.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var baseIsArguments = __webpack_require__(/*! ./_baseIsArguments */ "./node_modules/lodash/_baseIsArguments.js"),
    isObjectLike = __webpack_require__(/*! ./isObjectLike */ "./node_modules/lodash/isObjectLike.js");

/** Used for built-in method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/** Built-in value references. */
var propertyIsEnumerable = objectProto.propertyIsEnumerable;

/**
 * Checks if `value` is likely an `arguments` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an `arguments` object,
 *  else `false`.
 * @example
 *
 * _.isArguments(function() { return arguments; }());
 * // => true
 *
 * _.isArguments([1, 2, 3]);
 * // => false
 */
var isArguments = baseIsArguments(function() { return arguments; }()) ? baseIsArguments : function(value) {
  return isObjectLike(value) && hasOwnProperty.call(value, 'callee') &&
    !propertyIsEnumerable.call(value, 'callee');
};

module.exports = isArguments;


/***/ }),

/***/ "./node_modules/lodash/isArray.js":
/*!****************************************!*\
  !*** ./node_modules/lodash/isArray.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Checks if `value` is classified as an `Array` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an array, else `false`.
 * @example
 *
 * _.isArray([1, 2, 3]);
 * // => true
 *
 * _.isArray(document.body.children);
 * // => false
 *
 * _.isArray('abc');
 * // => false
 *
 * _.isArray(_.noop);
 * // => false
 */
var isArray = Array.isArray;

module.exports = isArray;


/***/ }),

/***/ "./node_modules/lodash/isObjectLike.js":
/*!*********************************************!*\
  !*** ./node_modules/lodash/isObjectLike.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return value != null && typeof value == 'object';
}

module.exports = isObjectLike;


/***/ }),

/***/ "./node_modules/micromodal/dist/micromodal.es.js":
/*!*******************************************************!*\
  !*** ./node_modules/micromodal/dist/micromodal.es.js ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread();
}

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) return _arrayLikeToArray(arr);
}

function _iterableToArray(iter) {
  if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter);
}

function _unsupportedIterableToArray(o, minLen) {
  if (!o) return;
  if (typeof o === "string") return _arrayLikeToArray(o, minLen);
  var n = Object.prototype.toString.call(o).slice(8, -1);
  if (n === "Object" && o.constructor) n = o.constructor.name;
  if (n === "Map" || n === "Set") return Array.from(n);
  if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
}

function _arrayLikeToArray(arr, len) {
  if (len == null || len > arr.length) len = arr.length;

  for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

  return arr2;
}

function _nonIterableSpread() {
  throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

var MicroModal = function () {

  var FOCUSABLE_ELEMENTS = ['a[href]', 'area[href]', 'input:not([disabled]):not([type="hidden"]):not([aria-hidden])', 'select:not([disabled]):not([aria-hidden])', 'textarea:not([disabled]):not([aria-hidden])', 'button:not([disabled]):not([aria-hidden])', 'iframe', 'object', 'embed', '[contenteditable]', '[tabindex]:not([tabindex^="-"])'];

  var Modal = /*#__PURE__*/function () {
    function Modal(_ref) {
      var targetModal = _ref.targetModal,
          _ref$triggers = _ref.triggers,
          triggers = _ref$triggers === void 0 ? [] : _ref$triggers,
          _ref$onShow = _ref.onShow,
          onShow = _ref$onShow === void 0 ? function () {} : _ref$onShow,
          _ref$onClose = _ref.onClose,
          onClose = _ref$onClose === void 0 ? function () {} : _ref$onClose,
          _ref$openTrigger = _ref.openTrigger,
          openTrigger = _ref$openTrigger === void 0 ? 'data-micromodal-trigger' : _ref$openTrigger,
          _ref$closeTrigger = _ref.closeTrigger,
          closeTrigger = _ref$closeTrigger === void 0 ? 'data-micromodal-close' : _ref$closeTrigger,
          _ref$openClass = _ref.openClass,
          openClass = _ref$openClass === void 0 ? 'is-open' : _ref$openClass,
          _ref$disableScroll = _ref.disableScroll,
          disableScroll = _ref$disableScroll === void 0 ? false : _ref$disableScroll,
          _ref$disableFocus = _ref.disableFocus,
          disableFocus = _ref$disableFocus === void 0 ? false : _ref$disableFocus,
          _ref$awaitCloseAnimat = _ref.awaitCloseAnimation,
          awaitCloseAnimation = _ref$awaitCloseAnimat === void 0 ? false : _ref$awaitCloseAnimat,
          _ref$awaitOpenAnimati = _ref.awaitOpenAnimation,
          awaitOpenAnimation = _ref$awaitOpenAnimati === void 0 ? false : _ref$awaitOpenAnimati,
          _ref$debugMode = _ref.debugMode,
          debugMode = _ref$debugMode === void 0 ? false : _ref$debugMode;

      _classCallCheck(this, Modal);

      // Save a reference of the modal
      this.modal = document.getElementById(targetModal); // Save a reference to the passed config

      this.config = {
        debugMode: debugMode,
        disableScroll: disableScroll,
        openTrigger: openTrigger,
        closeTrigger: closeTrigger,
        openClass: openClass,
        onShow: onShow,
        onClose: onClose,
        awaitCloseAnimation: awaitCloseAnimation,
        awaitOpenAnimation: awaitOpenAnimation,
        disableFocus: disableFocus
      }; // Register click events only if pre binding eventListeners

      if (triggers.length > 0) this.registerTriggers.apply(this, _toConsumableArray(triggers)); // pre bind functions for event listeners

      this.onClick = this.onClick.bind(this);
      this.onKeydown = this.onKeydown.bind(this);
    }
    /**
     * Loops through all openTriggers and binds click event
     * @param  {array} triggers [Array of node elements]
     * @return {void}
     */


    _createClass(Modal, [{
      key: "registerTriggers",
      value: function registerTriggers() {
        var _this = this;

        for (var _len = arguments.length, triggers = new Array(_len), _key = 0; _key < _len; _key++) {
          triggers[_key] = arguments[_key];
        }

        triggers.filter(Boolean).forEach(function (trigger) {
          trigger.addEventListener('click', function (event) {
            return _this.showModal(event);
          });
        });
      }
    }, {
      key: "showModal",
      value: function showModal() {
        var _this2 = this;

        var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
        this.activeElement = document.activeElement;
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.classList.add(this.config.openClass);
        this.scrollBehaviour('disable');
        this.addEventListeners();

        if (this.config.awaitOpenAnimation) {
          var handler = function handler() {
            _this2.modal.removeEventListener('animationend', handler, false);

            _this2.setFocusToFirstNode();
          };

          this.modal.addEventListener('animationend', handler, false);
        } else {
          this.setFocusToFirstNode();
        }

        this.config.onShow(this.modal, this.activeElement, event);
      }
    }, {
      key: "closeModal",
      value: function closeModal() {
        var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
        var modal = this.modal;
        this.modal.setAttribute('aria-hidden', 'true');
        this.removeEventListeners();
        this.scrollBehaviour('enable');

        if (this.activeElement && this.activeElement.focus) {
          this.activeElement.focus();
        }

        this.config.onClose(this.modal, this.activeElement, event);

        if (this.config.awaitCloseAnimation) {
          var openClass = this.config.openClass; // <- old school ftw

          this.modal.addEventListener('animationend', function handler() {
            modal.classList.remove(openClass);
            modal.removeEventListener('animationend', handler, false);
          }, false);
        } else {
          modal.classList.remove(this.config.openClass);
        }
      }
    }, {
      key: "closeModalById",
      value: function closeModalById(targetModal) {
        this.modal = document.getElementById(targetModal);
        if (this.modal) this.closeModal();
      }
    }, {
      key: "scrollBehaviour",
      value: function scrollBehaviour(toggle) {
        if (!this.config.disableScroll) return;
        var body = document.querySelector('body');

        switch (toggle) {
          case 'enable':
            Object.assign(body.style, {
              overflow: ''
            });
            break;

          case 'disable':
            Object.assign(body.style, {
              overflow: 'hidden'
            });
            break;
        }
      }
    }, {
      key: "addEventListeners",
      value: function addEventListeners() {
        this.modal.addEventListener('touchstart', this.onClick);
        this.modal.addEventListener('click', this.onClick);
        document.addEventListener('keydown', this.onKeydown);
      }
    }, {
      key: "removeEventListeners",
      value: function removeEventListeners() {
        this.modal.removeEventListener('touchstart', this.onClick);
        this.modal.removeEventListener('click', this.onClick);
        document.removeEventListener('keydown', this.onKeydown);
      }
    }, {
      key: "onClick",
      value: function onClick(event) {
        if (event.target.hasAttribute(this.config.closeTrigger)) {
          this.closeModal(event);
        }
      }
    }, {
      key: "onKeydown",
      value: function onKeydown(event) {
        if (event.keyCode === 27) this.closeModal(event); // esc

        if (event.keyCode === 9) this.retainFocus(event); // tab
      }
    }, {
      key: "getFocusableNodes",
      value: function getFocusableNodes() {
        var nodes = this.modal.querySelectorAll(FOCUSABLE_ELEMENTS);
        return Array.apply(void 0, _toConsumableArray(nodes));
      }
      /**
       * Tries to set focus on a node which is not a close trigger
       * if no other nodes exist then focuses on first close trigger
       */

    }, {
      key: "setFocusToFirstNode",
      value: function setFocusToFirstNode() {
        var _this3 = this;

        if (this.config.disableFocus) return;
        var focusableNodes = this.getFocusableNodes(); // no focusable nodes

        if (focusableNodes.length === 0) return; // remove nodes on whose click, the modal closes
        // could not think of a better name :(

        var nodesWhichAreNotCloseTargets = focusableNodes.filter(function (node) {
          return !node.hasAttribute(_this3.config.closeTrigger);
        });
        if (nodesWhichAreNotCloseTargets.length > 0) nodesWhichAreNotCloseTargets[0].focus();
        if (nodesWhichAreNotCloseTargets.length === 0) focusableNodes[0].focus();
      }
    }, {
      key: "retainFocus",
      value: function retainFocus(event) {
        var focusableNodes = this.getFocusableNodes(); // no focusable nodes

        if (focusableNodes.length === 0) return;
        /**
         * Filters nodes which are hidden to prevent
         * focus leak outside modal
         */

        focusableNodes = focusableNodes.filter(function (node) {
          return node.offsetParent !== null;
        }); // if disableFocus is true

        if (!this.modal.contains(document.activeElement)) {
          focusableNodes[0].focus();
        } else {
          var focusedItemIndex = focusableNodes.indexOf(document.activeElement);

          if (event.shiftKey && focusedItemIndex === 0) {
            focusableNodes[focusableNodes.length - 1].focus();
            event.preventDefault();
          }

          if (!event.shiftKey && focusableNodes.length > 0 && focusedItemIndex === focusableNodes.length - 1) {
            focusableNodes[0].focus();
            event.preventDefault();
          }
        }
      }
    }]);

    return Modal;
  }();
  /**
   * Modal prototype ends.
   * Here on code is responsible for detecting and
   * auto binding event handlers on modal triggers
   */
  // Keep a reference to the opened modal


  var activeModal = null;
  /**
   * Generates an associative array of modals and it's
   * respective triggers
   * @param  {array} triggers     An array of all triggers
   * @param  {string} triggerAttr The data-attribute which triggers the module
   * @return {array}
   */

  var generateTriggerMap = function generateTriggerMap(triggers, triggerAttr) {
    var triggerMap = [];
    triggers.forEach(function (trigger) {
      var targetModal = trigger.attributes[triggerAttr].value;
      if (triggerMap[targetModal] === undefined) triggerMap[targetModal] = [];
      triggerMap[targetModal].push(trigger);
    });
    return triggerMap;
  };
  /**
   * Validates whether a modal of the given id exists
   * in the DOM
   * @param  {number} id  The id of the modal
   * @return {boolean}
   */


  var validateModalPresence = function validateModalPresence(id) {
    if (!document.getElementById(id)) {
      console.warn("MicroModal: \u2757Seems like you have missed %c'".concat(id, "'"), 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', 'ID somewhere in your code. Refer example below to resolve it.');
      console.warn("%cExample:", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', "<div class=\"modal\" id=\"".concat(id, "\"></div>"));
      return false;
    }
  };
  /**
   * Validates if there are modal triggers present
   * in the DOM
   * @param  {array} triggers An array of data-triggers
   * @return {boolean}
   */


  var validateTriggerPresence = function validateTriggerPresence(triggers) {
    if (triggers.length <= 0) {
      console.warn("MicroModal: \u2757Please specify at least one %c'micromodal-trigger'", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', 'data attribute.');
      console.warn("%cExample:", 'background-color: #f8f9fa;color: #50596c;font-weight: bold;', "<a href=\"#\" data-micromodal-trigger=\"my-modal\"></a>");
      return false;
    }
  };
  /**
   * Checks if triggers and their corresponding modals
   * are present in the DOM
   * @param  {array} triggers   Array of DOM nodes which have data-triggers
   * @param  {array} triggerMap Associative array of modals and their triggers
   * @return {boolean}
   */


  var validateArgs = function validateArgs(triggers, triggerMap) {
    validateTriggerPresence(triggers);
    if (!triggerMap) return true;

    for (var id in triggerMap) {
      validateModalPresence(id);
    }

    return true;
  };
  /**
   * Binds click handlers to all modal triggers
   * @param  {object} config [description]
   * @return void
   */


  var init = function init(config) {
    // Create an config object with default openTrigger
    var options = Object.assign({}, {
      openTrigger: 'data-micromodal-trigger'
    }, config); // Collects all the nodes with the trigger

    var triggers = _toConsumableArray(document.querySelectorAll("[".concat(options.openTrigger, "]"))); // Makes a mappings of modals with their trigger nodes


    var triggerMap = generateTriggerMap(triggers, options.openTrigger); // Checks if modals and triggers exist in dom

    if (options.debugMode === true && validateArgs(triggers, triggerMap) === false) return; // For every target modal creates a new instance

    for (var key in triggerMap) {
      var value = triggerMap[key];
      options.targetModal = key;
      options.triggers = _toConsumableArray(value);
      activeModal = new Modal(options); // eslint-disable-line no-new
    }
  };
  /**
   * Shows a particular modal
   * @param  {string} targetModal [The id of the modal to display]
   * @param  {object} config [The configuration object to pass]
   * @return {void}
   */


  var show = function show(targetModal, config) {
    var options = config || {};
    options.targetModal = targetModal; // Checks if modals and triggers exist in dom

    if (options.debugMode === true && validateModalPresence(targetModal) === false) return; // clear events in case previous modal wasn't close

    if (activeModal) activeModal.removeEventListeners(); // stores reference to active modal

    activeModal = new Modal(options); // eslint-disable-line no-new

    activeModal.showModal();
  };
  /**
   * Closes the active modal
   * @param  {string} targetModal [The id of the modal to close]
   * @return {void}
   */


  var close = function close(targetModal) {
    targetModal ? activeModal.closeModalById(targetModal) : activeModal.closeModal();
  };

  return {
    init: init,
    show: show,
    close: close
  };
}();
window.MicroModal = MicroModal;

/* harmony default export */ __webpack_exports__["default"] = (MicroModal);


/***/ }),

/***/ "./node_modules/mithril/api/mount-redraw.js":
/*!**************************************************!*\
  !*** ./node_modules/mithril/api/mount-redraw.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")

module.exports = function(render, schedule, console) {
	var subscriptions = []
	var rendering = false
	var pending = false

	function sync() {
		if (rendering) throw new Error("Nested m.redraw.sync() call")
		rendering = true
		for (var i = 0; i < subscriptions.length; i += 2) {
			try { render(subscriptions[i], Vnode(subscriptions[i + 1]), redraw) }
			catch (e) { console.error(e) }
		}
		rendering = false
	}

	function redraw() {
		if (!pending) {
			pending = true
			schedule(function() {
				pending = false
				sync()
			})
		}
	}

	redraw.sync = sync

	function mount(root, component) {
		if (component != null && component.view == null && typeof component !== "function") {
			throw new TypeError("m.mount(element, component) expects a component, not a vnode")
		}

		var index = subscriptions.indexOf(root)
		if (index >= 0) {
			subscriptions.splice(index, 2)
			render(root, [], redraw)
		}

		if (component != null) {
			subscriptions.push(root, component)
			render(root, Vnode(component), redraw)
		}
	}

	return {mount: mount, redraw: redraw}
}


/***/ }),

/***/ "./node_modules/mithril/api/router.js":
/*!********************************************!*\
  !*** ./node_modules/mithril/api/router.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(setImmediate) {

var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")
var m = __webpack_require__(/*! ../render/hyperscript */ "./node_modules/mithril/render/hyperscript.js")
var Promise = __webpack_require__(/*! ../promise/promise */ "./node_modules/mithril/promise/promise.js")

var buildPathname = __webpack_require__(/*! ../pathname/build */ "./node_modules/mithril/pathname/build.js")
var parsePathname = __webpack_require__(/*! ../pathname/parse */ "./node_modules/mithril/pathname/parse.js")
var compileTemplate = __webpack_require__(/*! ../pathname/compileTemplate */ "./node_modules/mithril/pathname/compileTemplate.js")
var assign = __webpack_require__(/*! ../pathname/assign */ "./node_modules/mithril/pathname/assign.js")

var sentinel = {}

module.exports = function($window, mountRedraw) {
	var fireAsync

	function setPath(path, data, options) {
		path = buildPathname(path, data)
		if (fireAsync != null) {
			fireAsync()
			var state = options ? options.state : null
			var title = options ? options.title : null
			if (options && options.replace) $window.history.replaceState(state, title, route.prefix + path)
			else $window.history.pushState(state, title, route.prefix + path)
		}
		else {
			$window.location.href = route.prefix + path
		}
	}

	var currentResolver = sentinel, component, attrs, currentPath, lastUpdate

	var SKIP = route.SKIP = {}

	function route(root, defaultRoute, routes) {
		if (root == null) throw new Error("Ensure the DOM element that was passed to `m.route` is not undefined")
		// 0 = start
		// 1 = init
		// 2 = ready
		var state = 0

		var compiled = Object.keys(routes).map(function(route) {
			if (route[0] !== "/") throw new SyntaxError("Routes must start with a `/`")
			if ((/:([^\/\.-]+)(\.{3})?:/).test(route)) {
				throw new SyntaxError("Route parameter names must be separated with either `/`, `.`, or `-`")
			}
			return {
				route: route,
				component: routes[route],
				check: compileTemplate(route),
			}
		})
		var callAsync = typeof setImmediate === "function" ? setImmediate : setTimeout
		var p = Promise.resolve()
		var scheduled = false
		var onremove

		fireAsync = null

		if (defaultRoute != null) {
			var defaultData = parsePathname(defaultRoute)

			if (!compiled.some(function (i) { return i.check(defaultData) })) {
				throw new ReferenceError("Default route doesn't match any known routes")
			}
		}

		function resolveRoute() {
			scheduled = false
			// Consider the pathname holistically. The prefix might even be invalid,
			// but that's not our problem.
			var prefix = $window.location.hash
			if (route.prefix[0] !== "#") {
				prefix = $window.location.search + prefix
				if (route.prefix[0] !== "?") {
					prefix = $window.location.pathname + prefix
					if (prefix[0] !== "/") prefix = "/" + prefix
				}
			}
			// This seemingly useless `.concat()` speeds up the tests quite a bit,
			// since the representation is consistently a relatively poorly
			// optimized cons string.
			var path = prefix.concat()
				.replace(/(?:%[a-f89][a-f0-9])+/gim, decodeURIComponent)
				.slice(route.prefix.length)
			var data = parsePathname(path)

			assign(data.params, $window.history.state)

			function fail() {
				if (path === defaultRoute) throw new Error("Could not resolve default route " + defaultRoute)
				setPath(defaultRoute, null, {replace: true})
			}

			loop(0)
			function loop(i) {
				// 0 = init
				// 1 = scheduled
				// 2 = done
				for (; i < compiled.length; i++) {
					if (compiled[i].check(data)) {
						var payload = compiled[i].component
						var matchedRoute = compiled[i].route
						var localComp = payload
						var update = lastUpdate = function(comp) {
							if (update !== lastUpdate) return
							if (comp === SKIP) return loop(i + 1)
							component = comp != null && (typeof comp.view === "function" || typeof comp === "function")? comp : "div"
							attrs = data.params, currentPath = path, lastUpdate = null
							currentResolver = payload.render ? payload : null
							if (state === 2) mountRedraw.redraw()
							else {
								state = 2
								mountRedraw.redraw.sync()
							}
						}
						// There's no understating how much I *wish* I could
						// use `async`/`await` here...
						if (payload.view || typeof payload === "function") {
							payload = {}
							update(localComp)
						}
						else if (payload.onmatch) {
							p.then(function () {
								return payload.onmatch(data.params, path, matchedRoute)
							}).then(update, fail)
						}
						else update("div")
						return
					}
				}
				fail()
			}
		}

		// Set it unconditionally so `m.route.set` and `m.route.Link` both work,
		// even if neither `pushState` nor `hashchange` are supported. It's
		// cleared if `hashchange` is used, since that makes it automatically
		// async.
		fireAsync = function() {
			if (!scheduled) {
				scheduled = true
				callAsync(resolveRoute)
			}
		}

		if (typeof $window.history.pushState === "function") {
			onremove = function() {
				$window.removeEventListener("popstate", fireAsync, false)
			}
			$window.addEventListener("popstate", fireAsync, false)
		} else if (route.prefix[0] === "#") {
			fireAsync = null
			onremove = function() {
				$window.removeEventListener("hashchange", resolveRoute, false)
			}
			$window.addEventListener("hashchange", resolveRoute, false)
		}

		return mountRedraw.mount(root, {
			onbeforeupdate: function() {
				state = state ? 2 : 1
				return !(!state || sentinel === currentResolver)
			},
			oncreate: resolveRoute,
			onremove: onremove,
			view: function() {
				if (!state || sentinel === currentResolver) return
				// Wrap in a fragment to preserve existing key semantics
				var vnode = [Vnode(component, attrs.key, attrs)]
				if (currentResolver) vnode = currentResolver.render(vnode[0])
				return vnode
			},
		})
	}
	route.set = function(path, data, options) {
		if (lastUpdate != null) {
			options = options || {}
			options.replace = true
		}
		lastUpdate = null
		setPath(path, data, options)
	}
	route.get = function() {return currentPath}
	route.prefix = "#!"
	route.Link = {
		view: function(vnode) {
			var options = vnode.attrs.options
			// Remove these so they don't get overwritten
			var attrs = {}, onclick, href
			assign(attrs, vnode.attrs)
			// The first two are internal, but the rest are magic attributes
			// that need censored to not screw up rendering.
			attrs.selector = attrs.options = attrs.key = attrs.oninit =
			attrs.oncreate = attrs.onbeforeupdate = attrs.onupdate =
			attrs.onbeforeremove = attrs.onremove = null

			// Do this now so we can get the most current `href` and `disabled`.
			// Those attributes may also be specified in the selector, and we
			// should honor that.
			var child = m(vnode.attrs.selector || "a", attrs, vnode.children)

			// Let's provide a *right* way to disable a route link, rather than
			// letting people screw up accessibility on accident.
			//
			// The attribute is coerced so users don't get surprised over
			// `disabled: 0` resulting in a button that's somehow routable
			// despite being visibly disabled.
			if (child.attrs.disabled = Boolean(child.attrs.disabled)) {
				child.attrs.href = null
				child.attrs["aria-disabled"] = "true"
				// If you *really* do want to do this on a disabled link, use
				// an `oncreate` hook to add it.
				child.attrs.onclick = null
			} else {
				onclick = child.attrs.onclick
				href = child.attrs.href
				child.attrs.href = route.prefix + href
				child.attrs.onclick = function(e) {
					var result
					if (typeof onclick === "function") {
						result = onclick.call(e.currentTarget, e)
					} else if (onclick == null || typeof onclick !== "object") {
						// do nothing
					} else if (typeof onclick.handleEvent === "function") {
						onclick.handleEvent(e)
					}

					// Adapted from React Router's implementation:
					// https://github.com/ReactTraining/react-router/blob/520a0acd48ae1b066eb0b07d6d4d1790a1d02482/packages/react-router-dom/modules/Link.js
					//
					// Try to be flexible and intuitive in how we handle links.
					// Fun fact: links aren't as obvious to get right as you
					// would expect. There's a lot more valid ways to click a
					// link than this, and one might want to not simply click a
					// link, but right click or command-click it to copy the
					// link target, etc. Nope, this isn't just for blind people.
					if (
						// Skip if `onclick` prevented default
						result !== false && !e.defaultPrevented &&
						// Ignore everything but left clicks
						(e.button === 0 || e.which === 0 || e.which === 1) &&
						// Let the browser handle `target=_blank`, etc.
						(!e.currentTarget.target || e.currentTarget.target === "_self") &&
						// No modifier keys
						!e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey
					) {
						e.preventDefault()
						e.redraw = false
						route.set(href, null, options)
					}
				}
			}
			return child
		},
	}
	route.param = function(key) {
		return attrs && key != null ? attrs[key] : attrs
	}

	return route
}

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../timers-browserify/main.js */ "./node_modules/timers-browserify/main.js").setImmediate))

/***/ }),

/***/ "./node_modules/mithril/hyperscript.js":
/*!*********************************************!*\
  !*** ./node_modules/mithril/hyperscript.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var hyperscript = __webpack_require__(/*! ./render/hyperscript */ "./node_modules/mithril/render/hyperscript.js")

hyperscript.trust = __webpack_require__(/*! ./render/trust */ "./node_modules/mithril/render/trust.js")
hyperscript.fragment = __webpack_require__(/*! ./render/fragment */ "./node_modules/mithril/render/fragment.js")

module.exports = hyperscript


/***/ }),

/***/ "./node_modules/mithril/mount-redraw.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/mount-redraw.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var render = __webpack_require__(/*! ./render */ "./node_modules/mithril/render.js")

module.exports = __webpack_require__(/*! ./api/mount-redraw */ "./node_modules/mithril/api/mount-redraw.js")(render, requestAnimationFrame, console)


/***/ }),

/***/ "./node_modules/mithril/pathname/assign.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/pathname/assign.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = Object.assign || function(target, source) {
	if(source) Object.keys(source).forEach(function(key) { target[key] = source[key] })
}


/***/ }),

/***/ "./node_modules/mithril/pathname/build.js":
/*!************************************************!*\
  !*** ./node_modules/mithril/pathname/build.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var buildQueryString = __webpack_require__(/*! ../querystring/build */ "./node_modules/mithril/querystring/build.js")
var assign = __webpack_require__(/*! ./assign */ "./node_modules/mithril/pathname/assign.js")

// Returns `path` from `template` + `params`
module.exports = function(template, params) {
	if ((/:([^\/\.-]+)(\.{3})?:/).test(template)) {
		throw new SyntaxError("Template parameter names *must* be separated")
	}
	if (params == null) return template
	var queryIndex = template.indexOf("?")
	var hashIndex = template.indexOf("#")
	var queryEnd = hashIndex < 0 ? template.length : hashIndex
	var pathEnd = queryIndex < 0 ? queryEnd : queryIndex
	var path = template.slice(0, pathEnd)
	var query = {}

	assign(query, params)

	var resolved = path.replace(/:([^\/\.-]+)(\.{3})?/g, function(m, key, variadic) {
		delete query[key]
		// If no such parameter exists, don't interpolate it.
		if (params[key] == null) return m
		// Escape normal parameters, but not variadic ones.
		return variadic ? params[key] : encodeURIComponent(String(params[key]))
	})

	// In case the template substitution adds new query/hash parameters.
	var newQueryIndex = resolved.indexOf("?")
	var newHashIndex = resolved.indexOf("#")
	var newQueryEnd = newHashIndex < 0 ? resolved.length : newHashIndex
	var newPathEnd = newQueryIndex < 0 ? newQueryEnd : newQueryIndex
	var result = resolved.slice(0, newPathEnd)

	if (queryIndex >= 0) result += template.slice(queryIndex, queryEnd)
	if (newQueryIndex >= 0) result += (queryIndex < 0 ? "?" : "&") + resolved.slice(newQueryIndex, newQueryEnd)
	var querystring = buildQueryString(query)
	if (querystring) result += (queryIndex < 0 && newQueryIndex < 0 ? "?" : "&") + querystring
	if (hashIndex >= 0) result += template.slice(hashIndex)
	if (newHashIndex >= 0) result += (hashIndex < 0 ? "" : "&") + resolved.slice(newHashIndex)
	return result
}


/***/ }),

/***/ "./node_modules/mithril/pathname/compileTemplate.js":
/*!**********************************************************!*\
  !*** ./node_modules/mithril/pathname/compileTemplate.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var parsePathname = __webpack_require__(/*! ./parse */ "./node_modules/mithril/pathname/parse.js")

// Compiles a template into a function that takes a resolved path (without query
// strings) and returns an object containing the template parameters with their
// parsed values. This expects the input of the compiled template to be the
// output of `parsePathname`. Note that it does *not* remove query parameters
// specified in the template.
module.exports = function(template) {
	var templateData = parsePathname(template)
	var templateKeys = Object.keys(templateData.params)
	var keys = []
	var regexp = new RegExp("^" + templateData.path.replace(
		// I escape literal text so people can use things like `:file.:ext` or
		// `:lang-:locale` in routes. This is all merged into one pass so I
		// don't also accidentally escape `-` and make it harder to detect it to
		// ban it from template parameters.
		/:([^\/.-]+)(\.{3}|\.(?!\.)|-)?|[\\^$*+.()|\[\]{}]/g,
		function(m, key, extra) {
			if (key == null) return "\\" + m
			keys.push({k: key, r: extra === "..."})
			if (extra === "...") return "(.*)"
			if (extra === ".") return "([^/]+)\\."
			return "([^/]+)" + (extra || "")
		}
	) + "$")
	return function(data) {
		// First, check the params. Usually, there isn't any, and it's just
		// checking a static set.
		for (var i = 0; i < templateKeys.length; i++) {
			if (templateData.params[templateKeys[i]] !== data.params[templateKeys[i]]) return false
		}
		// If no interpolations exist, let's skip all the ceremony
		if (!keys.length) return regexp.test(data.path)
		var values = regexp.exec(data.path)
		if (values == null) return false
		for (var i = 0; i < keys.length; i++) {
			data.params[keys[i].k] = keys[i].r ? values[i + 1] : decodeURIComponent(values[i + 1])
		}
		return true
	}
}


/***/ }),

/***/ "./node_modules/mithril/pathname/parse.js":
/*!************************************************!*\
  !*** ./node_modules/mithril/pathname/parse.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var parseQueryString = __webpack_require__(/*! ../querystring/parse */ "./node_modules/mithril/querystring/parse.js")

// Returns `{path, params}` from `url`
module.exports = function(url) {
	var queryIndex = url.indexOf("?")
	var hashIndex = url.indexOf("#")
	var queryEnd = hashIndex < 0 ? url.length : hashIndex
	var pathEnd = queryIndex < 0 ? queryEnd : queryIndex
	var path = url.slice(0, pathEnd).replace(/\/{2,}/g, "/")

	if (!path) path = "/"
	else {
		if (path[0] !== "/") path = "/" + path
		if (path.length > 1 && path[path.length - 1] === "/") path = path.slice(0, -1)
	}
	return {
		path: path,
		params: queryIndex < 0
			? {}
			: parseQueryString(url.slice(queryIndex + 1, queryEnd)),
	}
}


/***/ }),

/***/ "./node_modules/mithril/promise/polyfill.js":
/*!**************************************************!*\
  !*** ./node_modules/mithril/promise/polyfill.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(setImmediate) {
/** @constructor */
var PromisePolyfill = function(executor) {
	if (!(this instanceof PromisePolyfill)) throw new Error("Promise must be called with `new`")
	if (typeof executor !== "function") throw new TypeError("executor must be a function")

	var self = this, resolvers = [], rejectors = [], resolveCurrent = handler(resolvers, true), rejectCurrent = handler(rejectors, false)
	var instance = self._instance = {resolvers: resolvers, rejectors: rejectors}
	var callAsync = typeof setImmediate === "function" ? setImmediate : setTimeout
	function handler(list, shouldAbsorb) {
		return function execute(value) {
			var then
			try {
				if (shouldAbsorb && value != null && (typeof value === "object" || typeof value === "function") && typeof (then = value.then) === "function") {
					if (value === self) throw new TypeError("Promise can't be resolved w/ itself")
					executeOnce(then.bind(value))
				}
				else {
					callAsync(function() {
						if (!shouldAbsorb && list.length === 0) console.error("Possible unhandled promise rejection:", value)
						for (var i = 0; i < list.length; i++) list[i](value)
						resolvers.length = 0, rejectors.length = 0
						instance.state = shouldAbsorb
						instance.retry = function() {execute(value)}
					})
				}
			}
			catch (e) {
				rejectCurrent(e)
			}
		}
	}
	function executeOnce(then) {
		var runs = 0
		function run(fn) {
			return function(value) {
				if (runs++ > 0) return
				fn(value)
			}
		}
		var onerror = run(rejectCurrent)
		try {then(run(resolveCurrent), onerror)} catch (e) {onerror(e)}
	}

	executeOnce(executor)
}
PromisePolyfill.prototype.then = function(onFulfilled, onRejection) {
	var self = this, instance = self._instance
	function handle(callback, list, next, state) {
		list.push(function(value) {
			if (typeof callback !== "function") next(value)
			else try {resolveNext(callback(value))} catch (e) {if (rejectNext) rejectNext(e)}
		})
		if (typeof instance.retry === "function" && state === instance.state) instance.retry()
	}
	var resolveNext, rejectNext
	var promise = new PromisePolyfill(function(resolve, reject) {resolveNext = resolve, rejectNext = reject})
	handle(onFulfilled, instance.resolvers, resolveNext, true), handle(onRejection, instance.rejectors, rejectNext, false)
	return promise
}
PromisePolyfill.prototype.catch = function(onRejection) {
	return this.then(null, onRejection)
}
PromisePolyfill.prototype.finally = function(callback) {
	return this.then(
		function(value) {
			return PromisePolyfill.resolve(callback()).then(function() {
				return value
			})
		},
		function(reason) {
			return PromisePolyfill.resolve(callback()).then(function() {
				return PromisePolyfill.reject(reason);
			})
		}
	)
}
PromisePolyfill.resolve = function(value) {
	if (value instanceof PromisePolyfill) return value
	return new PromisePolyfill(function(resolve) {resolve(value)})
}
PromisePolyfill.reject = function(value) {
	return new PromisePolyfill(function(resolve, reject) {reject(value)})
}
PromisePolyfill.all = function(list) {
	return new PromisePolyfill(function(resolve, reject) {
		var total = list.length, count = 0, values = []
		if (list.length === 0) resolve([])
		else for (var i = 0; i < list.length; i++) {
			(function(i) {
				function consume(value) {
					count++
					values[i] = value
					if (count === total) resolve(values)
				}
				if (list[i] != null && (typeof list[i] === "object" || typeof list[i] === "function") && typeof list[i].then === "function") {
					list[i].then(consume, reject)
				}
				else consume(list[i])
			})(i)
		}
	})
}
PromisePolyfill.race = function(list) {
	return new PromisePolyfill(function(resolve, reject) {
		for (var i = 0; i < list.length; i++) {
			list[i].then(resolve, reject)
		}
	})
}

module.exports = PromisePolyfill

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../timers-browserify/main.js */ "./node_modules/timers-browserify/main.js").setImmediate))

/***/ }),

/***/ "./node_modules/mithril/promise/promise.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/promise/promise.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(global) {

var PromisePolyfill = __webpack_require__(/*! ./polyfill */ "./node_modules/mithril/promise/polyfill.js")

if (typeof window !== "undefined") {
	if (typeof window.Promise === "undefined") {
		window.Promise = PromisePolyfill
	} else if (!window.Promise.prototype.finally) {
		window.Promise.prototype.finally = PromisePolyfill.prototype.finally
	}
	module.exports = window.Promise
} else if (typeof global !== "undefined") {
	if (typeof global.Promise === "undefined") {
		global.Promise = PromisePolyfill
	} else if (!global.Promise.prototype.finally) {
		global.Promise.prototype.finally = PromisePolyfill.prototype.finally
	}
	module.exports = global.Promise
} else {
	module.exports = PromisePolyfill
}

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/mithril/querystring/build.js":
/*!***************************************************!*\
  !*** ./node_modules/mithril/querystring/build.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function(object) {
	if (Object.prototype.toString.call(object) !== "[object Object]") return ""

	var args = []
	for (var key in object) {
		destructure(key, object[key])
	}

	return args.join("&")

	function destructure(key, value) {
		if (Array.isArray(value)) {
			for (var i = 0; i < value.length; i++) {
				destructure(key + "[" + i + "]", value[i])
			}
		}
		else if (Object.prototype.toString.call(value) === "[object Object]") {
			for (var i in value) {
				destructure(key + "[" + i + "]", value[i])
			}
		}
		else args.push(encodeURIComponent(key) + (value != null && value !== "" ? "=" + encodeURIComponent(value) : ""))
	}
}


/***/ }),

/***/ "./node_modules/mithril/querystring/parse.js":
/*!***************************************************!*\
  !*** ./node_modules/mithril/querystring/parse.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function(string) {
	if (string === "" || string == null) return {}
	if (string.charAt(0) === "?") string = string.slice(1)

	var entries = string.split("&"), counters = {}, data = {}
	for (var i = 0; i < entries.length; i++) {
		var entry = entries[i].split("=")
		var key = decodeURIComponent(entry[0])
		var value = entry.length === 2 ? decodeURIComponent(entry[1]) : ""

		if (value === "true") value = true
		else if (value === "false") value = false

		var levels = key.split(/\]\[?|\[/)
		var cursor = data
		if (key.indexOf("[") > -1) levels.pop()
		for (var j = 0; j < levels.length; j++) {
			var level = levels[j], nextLevel = levels[j + 1]
			var isNumber = nextLevel == "" || !isNaN(parseInt(nextLevel, 10))
			if (level === "") {
				var key = levels.slice(0, j).join()
				if (counters[key] == null) {
					counters[key] = Array.isArray(cursor) ? cursor.length : 0
				}
				level = counters[key]++
			}
			// Disallow direct prototype pollution
			else if (level === "__proto__") break
			if (j === levels.length - 1) cursor[level] = value
			else {
				// Read own properties exclusively to disallow indirect
				// prototype pollution
				var desc = Object.getOwnPropertyDescriptor(cursor, level)
				if (desc != null) desc = desc.value
				if (desc == null) cursor[level] = desc = isNumber ? [] : {}
				cursor = desc
			}
		}
	}
	return data
}


/***/ }),

/***/ "./node_modules/mithril/render.js":
/*!****************************************!*\
  !*** ./node_modules/mithril/render.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = __webpack_require__(/*! ./render/render */ "./node_modules/mithril/render/render.js")(window)


/***/ }),

/***/ "./node_modules/mithril/render/fragment.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/render/fragment.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")
var hyperscriptVnode = __webpack_require__(/*! ./hyperscriptVnode */ "./node_modules/mithril/render/hyperscriptVnode.js")

module.exports = function() {
	var vnode = hyperscriptVnode.apply(0, arguments)

	vnode.tag = "["
	vnode.children = Vnode.normalizeChildren(vnode.children)
	return vnode
}


/***/ }),

/***/ "./node_modules/mithril/render/hyperscript.js":
/*!****************************************************!*\
  !*** ./node_modules/mithril/render/hyperscript.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")
var hyperscriptVnode = __webpack_require__(/*! ./hyperscriptVnode */ "./node_modules/mithril/render/hyperscriptVnode.js")

var selectorParser = /(?:(^|#|\.)([^#\.\[\]]+))|(\[(.+?)(?:\s*=\s*("|'|)((?:\\["'\]]|.)*?)\5)?\])/g
var selectorCache = {}
var hasOwn = {}.hasOwnProperty

function isEmpty(object) {
	for (var key in object) if (hasOwn.call(object, key)) return false
	return true
}

function compileSelector(selector) {
	var match, tag = "div", classes = [], attrs = {}
	while (match = selectorParser.exec(selector)) {
		var type = match[1], value = match[2]
		if (type === "" && value !== "") tag = value
		else if (type === "#") attrs.id = value
		else if (type === ".") classes.push(value)
		else if (match[3][0] === "[") {
			var attrValue = match[6]
			if (attrValue) attrValue = attrValue.replace(/\\(["'])/g, "$1").replace(/\\\\/g, "\\")
			if (match[4] === "class") classes.push(attrValue)
			else attrs[match[4]] = attrValue === "" ? attrValue : attrValue || true
		}
	}
	if (classes.length > 0) attrs.className = classes.join(" ")
	return selectorCache[selector] = {tag: tag, attrs: attrs}
}

function execSelector(state, vnode) {
	var attrs = vnode.attrs
	var children = Vnode.normalizeChildren(vnode.children)
	var hasClass = hasOwn.call(attrs, "class")
	var className = hasClass ? attrs.class : attrs.className

	vnode.tag = state.tag
	vnode.attrs = null
	vnode.children = undefined

	if (!isEmpty(state.attrs) && !isEmpty(attrs)) {
		var newAttrs = {}

		for (var key in attrs) {
			if (hasOwn.call(attrs, key)) newAttrs[key] = attrs[key]
		}

		attrs = newAttrs
	}

	for (var key in state.attrs) {
		if (hasOwn.call(state.attrs, key) && key !== "className" && !hasOwn.call(attrs, key)){
			attrs[key] = state.attrs[key]
		}
	}
	if (className != null || state.attrs.className != null) attrs.className =
		className != null
			? state.attrs.className != null
				? String(state.attrs.className) + " " + String(className)
				: className
			: state.attrs.className != null
				? state.attrs.className
				: null

	if (hasClass) attrs.class = null

	for (var key in attrs) {
		if (hasOwn.call(attrs, key) && key !== "key") {
			vnode.attrs = attrs
			break
		}
	}

	if (Array.isArray(children) && children.length === 1 && children[0] != null && children[0].tag === "#") {
		vnode.text = children[0].children
	} else {
		vnode.children = children
	}

	return vnode
}

function hyperscript(selector) {
	if (selector == null || typeof selector !== "string" && typeof selector !== "function" && typeof selector.view !== "function") {
		throw Error("The selector must be either a string or a component.");
	}

	var vnode = hyperscriptVnode.apply(1, arguments)

	if (typeof selector === "string") {
		vnode.children = Vnode.normalizeChildren(vnode.children)
		if (selector !== "[") return execSelector(selectorCache[selector] || compileSelector(selector), vnode)
	}

	vnode.tag = selector
	return vnode
}

module.exports = hyperscript


/***/ }),

/***/ "./node_modules/mithril/render/hyperscriptVnode.js":
/*!*********************************************************!*\
  !*** ./node_modules/mithril/render/hyperscriptVnode.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")

// Call via `hyperscriptVnode.apply(startOffset, arguments)`
//
// The reason I do it this way, forwarding the arguments and passing the start
// offset in `this`, is so I don't have to create a temporary array in a
// performance-critical path.
//
// In native ES6, I'd instead add a final `...args` parameter to the
// `hyperscript` and `fragment` factories and define this as
// `hyperscriptVnode(...args)`, since modern engines do optimize that away. But
// ES5 (what Mithril requires thanks to IE support) doesn't give me that luxury,
// and engines aren't nearly intelligent enough to do either of these:
//
// 1. Elide the allocation for `[].slice.call(arguments, 1)` when it's passed to
//    another function only to be indexed.
// 2. Elide an `arguments` allocation when it's passed to any function other
//    than `Function.prototype.apply` or `Reflect.apply`.
//
// In ES6, it'd probably look closer to this (I'd need to profile it, though):
// module.exports = function(attrs, ...children) {
//     if (attrs == null || typeof attrs === "object" && attrs.tag == null && !Array.isArray(attrs)) {
//         if (children.length === 1 && Array.isArray(children[0])) children = children[0]
//     } else {
//         children = children.length === 0 && Array.isArray(attrs) ? attrs : [attrs, ...children]
//         attrs = undefined
//     }
//
//     if (attrs == null) attrs = {}
//     return Vnode("", attrs.key, attrs, children)
// }
module.exports = function() {
	var attrs = arguments[this], start = this + 1, children

	if (attrs == null) {
		attrs = {}
	} else if (typeof attrs !== "object" || attrs.tag != null || Array.isArray(attrs)) {
		attrs = {}
		start = this
	}

	if (arguments.length === start + 1) {
		children = arguments[start]
		if (!Array.isArray(children)) children = [children]
	} else {
		children = []
		while (start < arguments.length) children.push(arguments[start++])
	}

	return Vnode("", attrs.key, attrs, children)
}


/***/ }),

/***/ "./node_modules/mithril/render/render.js":
/*!***********************************************!*\
  !*** ./node_modules/mithril/render/render.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")

module.exports = function($window) {
	var $doc = $window && $window.document
	var currentRedraw

	var nameSpace = {
		svg: "http://www.w3.org/2000/svg",
		math: "http://www.w3.org/1998/Math/MathML"
	}

	function getNameSpace(vnode) {
		return vnode.attrs && vnode.attrs.xmlns || nameSpace[vnode.tag]
	}

	//sanity check to discourage people from doing `vnode.state = ...`
	function checkState(vnode, original) {
		if (vnode.state !== original) throw new Error("`vnode.state` must not be modified")
	}

	//Note: the hook is passed as the `this` argument to allow proxying the
	//arguments without requiring a full array allocation to do so. It also
	//takes advantage of the fact the current `vnode` is the first argument in
	//all lifecycle methods.
	function callHook(vnode) {
		var original = vnode.state
		try {
			return this.apply(original, arguments)
		} finally {
			checkState(vnode, original)
		}
	}

	// IE11 (at least) throws an UnspecifiedError when accessing document.activeElement when
	// inside an iframe. Catch and swallow this error, and heavy-handidly return null.
	function activeElement() {
		try {
			return $doc.activeElement
		} catch (e) {
			return null
		}
	}
	//create
	function createNodes(parent, vnodes, start, end, hooks, nextSibling, ns) {
		for (var i = start; i < end; i++) {
			var vnode = vnodes[i]
			if (vnode != null) {
				createNode(parent, vnode, hooks, ns, nextSibling)
			}
		}
	}
	function createNode(parent, vnode, hooks, ns, nextSibling) {
		var tag = vnode.tag
		if (typeof tag === "string") {
			vnode.state = {}
			if (vnode.attrs != null) initLifecycle(vnode.attrs, vnode, hooks)
			switch (tag) {
				case "#": createText(parent, vnode, nextSibling); break
				case "<": createHTML(parent, vnode, ns, nextSibling); break
				case "[": createFragment(parent, vnode, hooks, ns, nextSibling); break
				default: createElement(parent, vnode, hooks, ns, nextSibling)
			}
		}
		else createComponent(parent, vnode, hooks, ns, nextSibling)
	}
	function createText(parent, vnode, nextSibling) {
		vnode.dom = $doc.createTextNode(vnode.children)
		insertNode(parent, vnode.dom, nextSibling)
	}
	var possibleParents = {caption: "table", thead: "table", tbody: "table", tfoot: "table", tr: "tbody", th: "tr", td: "tr", colgroup: "table", col: "colgroup"}
	function createHTML(parent, vnode, ns, nextSibling) {
		var match = vnode.children.match(/^\s*?<(\w+)/im) || []
		// not using the proper parent makes the child element(s) vanish.
		//     var div = document.createElement("div")
		//     div.innerHTML = "<td>i</td><td>j</td>"
		//     console.log(div.innerHTML)
		// --> "ij", no <td> in sight.
		var temp = $doc.createElement(possibleParents[match[1]] || "div")
		if (ns === "http://www.w3.org/2000/svg") {
			temp.innerHTML = "<svg xmlns=\"http://www.w3.org/2000/svg\">" + vnode.children + "</svg>"
			temp = temp.firstChild
		} else {
			temp.innerHTML = vnode.children
		}
		vnode.dom = temp.firstChild
		vnode.domSize = temp.childNodes.length
		// Capture nodes to remove, so we don't confuse them.
		vnode.instance = []
		var fragment = $doc.createDocumentFragment()
		var child
		while (child = temp.firstChild) {
			vnode.instance.push(child)
			fragment.appendChild(child)
		}
		insertNode(parent, fragment, nextSibling)
	}
	function createFragment(parent, vnode, hooks, ns, nextSibling) {
		var fragment = $doc.createDocumentFragment()
		if (vnode.children != null) {
			var children = vnode.children
			createNodes(fragment, children, 0, children.length, hooks, null, ns)
		}
		vnode.dom = fragment.firstChild
		vnode.domSize = fragment.childNodes.length
		insertNode(parent, fragment, nextSibling)
	}
	function createElement(parent, vnode, hooks, ns, nextSibling) {
		var tag = vnode.tag
		var attrs = vnode.attrs
		var is = attrs && attrs.is

		ns = getNameSpace(vnode) || ns

		var element = ns ?
			is ? $doc.createElementNS(ns, tag, {is: is}) : $doc.createElementNS(ns, tag) :
			is ? $doc.createElement(tag, {is: is}) : $doc.createElement(tag)
		vnode.dom = element

		if (attrs != null) {
			setAttrs(vnode, attrs, ns)
		}

		insertNode(parent, element, nextSibling)

		if (!maybeSetContentEditable(vnode)) {
			if (vnode.text != null) {
				if (vnode.text !== "") element.textContent = vnode.text
				else vnode.children = [Vnode("#", undefined, undefined, vnode.text, undefined, undefined)]
			}
			if (vnode.children != null) {
				var children = vnode.children
				createNodes(element, children, 0, children.length, hooks, null, ns)
				if (vnode.tag === "select" && attrs != null) setLateSelectAttrs(vnode, attrs)
			}
		}
	}
	function initComponent(vnode, hooks) {
		var sentinel
		if (typeof vnode.tag.view === "function") {
			vnode.state = Object.create(vnode.tag)
			sentinel = vnode.state.view
			if (sentinel.$$reentrantLock$$ != null) return
			sentinel.$$reentrantLock$$ = true
		} else {
			vnode.state = void 0
			sentinel = vnode.tag
			if (sentinel.$$reentrantLock$$ != null) return
			sentinel.$$reentrantLock$$ = true
			vnode.state = (vnode.tag.prototype != null && typeof vnode.tag.prototype.view === "function") ? new vnode.tag(vnode) : vnode.tag(vnode)
		}
		initLifecycle(vnode.state, vnode, hooks)
		if (vnode.attrs != null) initLifecycle(vnode.attrs, vnode, hooks)
		vnode.instance = Vnode.normalize(callHook.call(vnode.state.view, vnode))
		if (vnode.instance === vnode) throw Error("A view cannot return the vnode it received as argument")
		sentinel.$$reentrantLock$$ = null
	}
	function createComponent(parent, vnode, hooks, ns, nextSibling) {
		initComponent(vnode, hooks)
		if (vnode.instance != null) {
			createNode(parent, vnode.instance, hooks, ns, nextSibling)
			vnode.dom = vnode.instance.dom
			vnode.domSize = vnode.dom != null ? vnode.instance.domSize : 0
		}
		else {
			vnode.domSize = 0
		}
	}

	//update
	/**
	 * @param {Element|Fragment} parent - the parent element
	 * @param {Vnode[] | null} old - the list of vnodes of the last `render()` call for
	 *                               this part of the tree
	 * @param {Vnode[] | null} vnodes - as above, but for the current `render()` call.
	 * @param {Function[]} hooks - an accumulator of post-render hooks (oncreate/onupdate)
	 * @param {Element | null} nextSibling - the next DOM node if we're dealing with a
	 *                                       fragment that is not the last item in its
	 *                                       parent
	 * @param {'svg' | 'math' | String | null} ns) - the current XML namespace, if any
	 * @returns void
	 */
	// This function diffs and patches lists of vnodes, both keyed and unkeyed.
	//
	// We will:
	//
	// 1. describe its general structure
	// 2. focus on the diff algorithm optimizations
	// 3. discuss DOM node operations.

	// ## Overview:
	//
	// The updateNodes() function:
	// - deals with trivial cases
	// - determines whether the lists are keyed or unkeyed based on the first non-null node
	//   of each list.
	// - diffs them and patches the DOM if needed (that's the brunt of the code)
	// - manages the leftovers: after diffing, are there:
	//   - old nodes left to remove?
	// 	 - new nodes to insert?
	// 	 deal with them!
	//
	// The lists are only iterated over once, with an exception for the nodes in `old` that
	// are visited in the fourth part of the diff and in the `removeNodes` loop.

	// ## Diffing
	//
	// Reading https://github.com/localvoid/ivi/blob/ddc09d06abaef45248e6133f7040d00d3c6be853/packages/ivi/src/vdom/implementation.ts#L617-L837
	// may be good for context on longest increasing subsequence-based logic for moving nodes.
	//
	// In order to diff keyed lists, one has to
	//
	// 1) match nodes in both lists, per key, and update them accordingly
	// 2) create the nodes present in the new list, but absent in the old one
	// 3) remove the nodes present in the old list, but absent in the new one
	// 4) figure out what nodes in 1) to move in order to minimize the DOM operations.
	//
	// To achieve 1) one can create a dictionary of keys => index (for the old list), then iterate
	// over the new list and for each new vnode, find the corresponding vnode in the old list using
	// the map.
	// 2) is achieved in the same step: if a new node has no corresponding entry in the map, it is new
	// and must be created.
	// For the removals, we actually remove the nodes that have been updated from the old list.
	// The nodes that remain in that list after 1) and 2) have been performed can be safely removed.
	// The fourth step is a bit more complex and relies on the longest increasing subsequence (LIS)
	// algorithm.
	//
	// the longest increasing subsequence is the list of nodes that can remain in place. Imagine going
	// from `1,2,3,4,5` to `4,5,1,2,3` where the numbers are not necessarily the keys, but the indices
	// corresponding to the keyed nodes in the old list (keyed nodes `e,d,c,b,a` => `b,a,e,d,c` would
	//  match the above lists, for example).
	//
	// In there are two increasing subsequences: `4,5` and `1,2,3`, the latter being the longest. We
	// can update those nodes without moving them, and only call `insertNode` on `4` and `5`.
	//
	// @localvoid adapted the algo to also support node deletions and insertions (the `lis` is actually
	// the longest increasing subsequence *of old nodes still present in the new list*).
	//
	// It is a general algorithm that is fireproof in all circumstances, but it requires the allocation
	// and the construction of a `key => oldIndex` map, and three arrays (one with `newIndex => oldIndex`,
	// the `LIS` and a temporary one to create the LIS).
	//
	// So we cheat where we can: if the tails of the lists are identical, they are guaranteed to be part of
	// the LIS and can be updated without moving them.
	//
	// If two nodes are swapped, they are guaranteed not to be part of the LIS, and must be moved (with
	// the exception of the last node if the list is fully reversed).
	//
	// ## Finding the next sibling.
	//
	// `updateNode()` and `createNode()` expect a nextSibling parameter to perform DOM operations.
	// When the list is being traversed top-down, at any index, the DOM nodes up to the previous
	// vnode reflect the content of the new list, whereas the rest of the DOM nodes reflect the old
	// list. The next sibling must be looked for in the old list using `getNextSibling(... oldStart + 1 ...)`.
	//
	// In the other scenarios (swaps, upwards traversal, map-based diff),
	// the new vnodes list is traversed upwards. The DOM nodes at the bottom of the list reflect the
	// bottom part of the new vnodes list, and we can use the `v.dom`  value of the previous node
	// as the next sibling (cached in the `nextSibling` variable).


	// ## DOM node moves
	//
	// In most scenarios `updateNode()` and `createNode()` perform the DOM operations. However,
	// this is not the case if the node moved (second and fourth part of the diff algo). We move
	// the old DOM nodes before updateNode runs because it enables us to use the cached `nextSibling`
	// variable rather than fetching it using `getNextSibling()`.
	//
	// The fourth part of the diff currently inserts nodes unconditionally, leading to issues
	// like #1791 and #1999. We need to be smarter about those situations where adjascent old
	// nodes remain together in the new list in a way that isn't covered by parts one and
	// three of the diff algo.

	function updateNodes(parent, old, vnodes, hooks, nextSibling, ns) {
		if (old === vnodes || old == null && vnodes == null) return
		else if (old == null || old.length === 0) createNodes(parent, vnodes, 0, vnodes.length, hooks, nextSibling, ns)
		else if (vnodes == null || vnodes.length === 0) removeNodes(parent, old, 0, old.length)
		else {
			var isOldKeyed = old[0] != null && old[0].key != null
			var isKeyed = vnodes[0] != null && vnodes[0].key != null
			var start = 0, oldStart = 0
			if (!isOldKeyed) while (oldStart < old.length && old[oldStart] == null) oldStart++
			if (!isKeyed) while (start < vnodes.length && vnodes[start] == null) start++
			if (isKeyed === null && isOldKeyed == null) return // both lists are full of nulls
			if (isOldKeyed !== isKeyed) {
				removeNodes(parent, old, oldStart, old.length)
				createNodes(parent, vnodes, start, vnodes.length, hooks, nextSibling, ns)
			} else if (!isKeyed) {
				// Don't index past the end of either list (causes deopts).
				var commonLength = old.length < vnodes.length ? old.length : vnodes.length
				// Rewind if necessary to the first non-null index on either side.
				// We could alternatively either explicitly create or remove nodes when `start !== oldStart`
				// but that would be optimizing for sparse lists which are more rare than dense ones.
				start = start < oldStart ? start : oldStart
				for (; start < commonLength; start++) {
					o = old[start]
					v = vnodes[start]
					if (o === v || o == null && v == null) continue
					else if (o == null) createNode(parent, v, hooks, ns, getNextSibling(old, start + 1, nextSibling))
					else if (v == null) removeNode(parent, o)
					else updateNode(parent, o, v, hooks, getNextSibling(old, start + 1, nextSibling), ns)
				}
				if (old.length > commonLength) removeNodes(parent, old, start, old.length)
				if (vnodes.length > commonLength) createNodes(parent, vnodes, start, vnodes.length, hooks, nextSibling, ns)
			} else {
				// keyed diff
				var oldEnd = old.length - 1, end = vnodes.length - 1, map, o, v, oe, ve, topSibling

				// bottom-up
				while (oldEnd >= oldStart && end >= start) {
					oe = old[oldEnd]
					ve = vnodes[end]
					if (oe.key !== ve.key) break
					if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns)
					if (ve.dom != null) nextSibling = ve.dom
					oldEnd--, end--
				}
				// top-down
				while (oldEnd >= oldStart && end >= start) {
					o = old[oldStart]
					v = vnodes[start]
					if (o.key !== v.key) break
					oldStart++, start++
					if (o !== v) updateNode(parent, o, v, hooks, getNextSibling(old, oldStart, nextSibling), ns)
				}
				// swaps and list reversals
				while (oldEnd >= oldStart && end >= start) {
					if (start === end) break
					if (o.key !== ve.key || oe.key !== v.key) break
					topSibling = getNextSibling(old, oldStart, nextSibling)
					moveNodes(parent, oe, topSibling)
					if (oe !== v) updateNode(parent, oe, v, hooks, topSibling, ns)
					if (++start <= --end) moveNodes(parent, o, nextSibling)
					if (o !== ve) updateNode(parent, o, ve, hooks, nextSibling, ns)
					if (ve.dom != null) nextSibling = ve.dom
					oldStart++; oldEnd--
					oe = old[oldEnd]
					ve = vnodes[end]
					o = old[oldStart]
					v = vnodes[start]
				}
				// bottom up once again
				while (oldEnd >= oldStart && end >= start) {
					if (oe.key !== ve.key) break
					if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns)
					if (ve.dom != null) nextSibling = ve.dom
					oldEnd--, end--
					oe = old[oldEnd]
					ve = vnodes[end]
				}
				if (start > end) removeNodes(parent, old, oldStart, oldEnd + 1)
				else if (oldStart > oldEnd) createNodes(parent, vnodes, start, end + 1, hooks, nextSibling, ns)
				else {
					// inspired by ivi https://github.com/ivijs/ivi/ by Boris Kaul
					var originalNextSibling = nextSibling, vnodesLength = end - start + 1, oldIndices = new Array(vnodesLength), li=0, i=0, pos = 2147483647, matched = 0, map, lisIndices
					for (i = 0; i < vnodesLength; i++) oldIndices[i] = -1
					for (i = end; i >= start; i--) {
						if (map == null) map = getKeyMap(old, oldStart, oldEnd + 1)
						ve = vnodes[i]
						var oldIndex = map[ve.key]
						if (oldIndex != null) {
							pos = (oldIndex < pos) ? oldIndex : -1 // becomes -1 if nodes were re-ordered
							oldIndices[i-start] = oldIndex
							oe = old[oldIndex]
							old[oldIndex] = null
							if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns)
							if (ve.dom != null) nextSibling = ve.dom
							matched++
						}
					}
					nextSibling = originalNextSibling
					if (matched !== oldEnd - oldStart + 1) removeNodes(parent, old, oldStart, oldEnd + 1)
					if (matched === 0) createNodes(parent, vnodes, start, end + 1, hooks, nextSibling, ns)
					else {
						if (pos === -1) {
							// the indices of the indices of the items that are part of the
							// longest increasing subsequence in the oldIndices list
							lisIndices = makeLisIndices(oldIndices)
							li = lisIndices.length - 1
							for (i = end; i >= start; i--) {
								v = vnodes[i]
								if (oldIndices[i-start] === -1) createNode(parent, v, hooks, ns, nextSibling)
								else {
									if (lisIndices[li] === i - start) li--
									else moveNodes(parent, v, nextSibling)
								}
								if (v.dom != null) nextSibling = vnodes[i].dom
							}
						} else {
							for (i = end; i >= start; i--) {
								v = vnodes[i]
								if (oldIndices[i-start] === -1) createNode(parent, v, hooks, ns, nextSibling)
								if (v.dom != null) nextSibling = vnodes[i].dom
							}
						}
					}
				}
			}
		}
	}
	function updateNode(parent, old, vnode, hooks, nextSibling, ns) {
		var oldTag = old.tag, tag = vnode.tag
		if (oldTag === tag) {
			vnode.state = old.state
			vnode.events = old.events
			if (shouldNotUpdate(vnode, old)) return
			if (typeof oldTag === "string") {
				if (vnode.attrs != null) {
					updateLifecycle(vnode.attrs, vnode, hooks)
				}
				switch (oldTag) {
					case "#": updateText(old, vnode); break
					case "<": updateHTML(parent, old, vnode, ns, nextSibling); break
					case "[": updateFragment(parent, old, vnode, hooks, nextSibling, ns); break
					default: updateElement(old, vnode, hooks, ns)
				}
			}
			else updateComponent(parent, old, vnode, hooks, nextSibling, ns)
		}
		else {
			removeNode(parent, old)
			createNode(parent, vnode, hooks, ns, nextSibling)
		}
	}
	function updateText(old, vnode) {
		if (old.children.toString() !== vnode.children.toString()) {
			old.dom.nodeValue = vnode.children
		}
		vnode.dom = old.dom
	}
	function updateHTML(parent, old, vnode, ns, nextSibling) {
		if (old.children !== vnode.children) {
			removeHTML(parent, old)
			createHTML(parent, vnode, ns, nextSibling)
		}
		else {
			vnode.dom = old.dom
			vnode.domSize = old.domSize
			vnode.instance = old.instance
		}
	}
	function updateFragment(parent, old, vnode, hooks, nextSibling, ns) {
		updateNodes(parent, old.children, vnode.children, hooks, nextSibling, ns)
		var domSize = 0, children = vnode.children
		vnode.dom = null
		if (children != null) {
			for (var i = 0; i < children.length; i++) {
				var child = children[i]
				if (child != null && child.dom != null) {
					if (vnode.dom == null) vnode.dom = child.dom
					domSize += child.domSize || 1
				}
			}
			if (domSize !== 1) vnode.domSize = domSize
		}
	}
	function updateElement(old, vnode, hooks, ns) {
		var element = vnode.dom = old.dom
		ns = getNameSpace(vnode) || ns

		if (vnode.tag === "textarea") {
			if (vnode.attrs == null) vnode.attrs = {}
			if (vnode.text != null) {
				vnode.attrs.value = vnode.text //FIXME handle multiple children
				vnode.text = undefined
			}
		}
		updateAttrs(vnode, old.attrs, vnode.attrs, ns)
		if (!maybeSetContentEditable(vnode)) {
			if (old.text != null && vnode.text != null && vnode.text !== "") {
				if (old.text.toString() !== vnode.text.toString()) old.dom.firstChild.nodeValue = vnode.text
			}
			else {
				if (old.text != null) old.children = [Vnode("#", undefined, undefined, old.text, undefined, old.dom.firstChild)]
				if (vnode.text != null) vnode.children = [Vnode("#", undefined, undefined, vnode.text, undefined, undefined)]
				updateNodes(element, old.children, vnode.children, hooks, null, ns)
			}
		}
	}
	function updateComponent(parent, old, vnode, hooks, nextSibling, ns) {
		vnode.instance = Vnode.normalize(callHook.call(vnode.state.view, vnode))
		if (vnode.instance === vnode) throw Error("A view cannot return the vnode it received as argument")
		updateLifecycle(vnode.state, vnode, hooks)
		if (vnode.attrs != null) updateLifecycle(vnode.attrs, vnode, hooks)
		if (vnode.instance != null) {
			if (old.instance == null) createNode(parent, vnode.instance, hooks, ns, nextSibling)
			else updateNode(parent, old.instance, vnode.instance, hooks, nextSibling, ns)
			vnode.dom = vnode.instance.dom
			vnode.domSize = vnode.instance.domSize
		}
		else if (old.instance != null) {
			removeNode(parent, old.instance)
			vnode.dom = undefined
			vnode.domSize = 0
		}
		else {
			vnode.dom = old.dom
			vnode.domSize = old.domSize
		}
	}
	function getKeyMap(vnodes, start, end) {
		var map = Object.create(null)
		for (; start < end; start++) {
			var vnode = vnodes[start]
			if (vnode != null) {
				var key = vnode.key
				if (key != null) map[key] = start
			}
		}
		return map
	}
	// Lifted from ivi https://github.com/ivijs/ivi/
	// takes a list of unique numbers (-1 is special and can
	// occur multiple times) and returns an array with the indices
	// of the items that are part of the longest increasing
	// subsequece
	var lisTemp = []
	function makeLisIndices(a) {
		var result = [0]
		var u = 0, v = 0, i = 0
		var il = lisTemp.length = a.length
		for (var i = 0; i < il; i++) lisTemp[i] = a[i]
		for (var i = 0; i < il; ++i) {
			if (a[i] === -1) continue
			var j = result[result.length - 1]
			if (a[j] < a[i]) {
				lisTemp[i] = j
				result.push(i)
				continue
			}
			u = 0
			v = result.length - 1
			while (u < v) {
				// Fast integer average without overflow.
				// eslint-disable-next-line no-bitwise
				var c = (u >>> 1) + (v >>> 1) + (u & v & 1)
				if (a[result[c]] < a[i]) {
					u = c + 1
				}
				else {
					v = c
				}
			}
			if (a[i] < a[result[u]]) {
				if (u > 0) lisTemp[i] = result[u - 1]
				result[u] = i
			}
		}
		u = result.length
		v = result[u - 1]
		while (u-- > 0) {
			result[u] = v
			v = lisTemp[v]
		}
		lisTemp.length = 0
		return result
	}

	function getNextSibling(vnodes, i, nextSibling) {
		for (; i < vnodes.length; i++) {
			if (vnodes[i] != null && vnodes[i].dom != null) return vnodes[i].dom
		}
		return nextSibling
	}

	// This covers a really specific edge case:
	// - Parent node is keyed and contains child
	// - Child is removed, returns unresolved promise in `onbeforeremove`
	// - Parent node is moved in keyed diff
	// - Remaining children still need moved appropriately
	//
	// Ideally, I'd track removed nodes as well, but that introduces a lot more
	// complexity and I'm not exactly interested in doing that.
	function moveNodes(parent, vnode, nextSibling) {
		var frag = $doc.createDocumentFragment()
		moveChildToFrag(parent, frag, vnode)
		insertNode(parent, frag, nextSibling)
	}
	function moveChildToFrag(parent, frag, vnode) {
		// Dodge the recursion overhead in a few of the most common cases.
		while (vnode.dom != null && vnode.dom.parentNode === parent) {
			if (typeof vnode.tag !== "string") {
				vnode = vnode.instance
				if (vnode != null) continue
			} else if (vnode.tag === "<") {
				for (var i = 0; i < vnode.instance.length; i++) {
					frag.appendChild(vnode.instance[i])
				}
			} else if (vnode.tag !== "[") {
				// Don't recurse for text nodes *or* elements, just fragments
				frag.appendChild(vnode.dom)
			} else if (vnode.children.length === 1) {
				vnode = vnode.children[0]
				if (vnode != null) continue
			} else {
				for (var i = 0; i < vnode.children.length; i++) {
					var child = vnode.children[i]
					if (child != null) moveChildToFrag(parent, frag, child)
				}
			}
			break
		}
	}

	function insertNode(parent, dom, nextSibling) {
		if (nextSibling != null) parent.insertBefore(dom, nextSibling)
		else parent.appendChild(dom)
	}

	function maybeSetContentEditable(vnode) {
		if (vnode.attrs == null || (
			vnode.attrs.contenteditable == null && // attribute
			vnode.attrs.contentEditable == null // property
		)) return false
		var children = vnode.children
		if (children != null && children.length === 1 && children[0].tag === "<") {
			var content = children[0].children
			if (vnode.dom.innerHTML !== content) vnode.dom.innerHTML = content
		}
		else if (vnode.text != null || children != null && children.length !== 0) throw new Error("Child node of a contenteditable must be trusted")
		return true
	}

	//remove
	function removeNodes(parent, vnodes, start, end) {
		for (var i = start; i < end; i++) {
			var vnode = vnodes[i]
			if (vnode != null) removeNode(parent, vnode)
		}
	}
	function removeNode(parent, vnode) {
		var mask = 0
		var original = vnode.state
		var stateResult, attrsResult
		if (typeof vnode.tag !== "string" && typeof vnode.state.onbeforeremove === "function") {
			var result = callHook.call(vnode.state.onbeforeremove, vnode)
			if (result != null && typeof result.then === "function") {
				mask = 1
				stateResult = result
			}
		}
		if (vnode.attrs && typeof vnode.attrs.onbeforeremove === "function") {
			var result = callHook.call(vnode.attrs.onbeforeremove, vnode)
			if (result != null && typeof result.then === "function") {
				// eslint-disable-next-line no-bitwise
				mask |= 2
				attrsResult = result
			}
		}
		checkState(vnode, original)

		// If we can, try to fast-path it and avoid all the overhead of awaiting
		if (!mask) {
			onremove(vnode)
			removeChild(parent, vnode)
		} else {
			if (stateResult != null) {
				var next = function () {
					// eslint-disable-next-line no-bitwise
					if (mask & 1) { mask &= 2; if (!mask) reallyRemove() }
				}
				stateResult.then(next, next)
			}
			if (attrsResult != null) {
				var next = function () {
					// eslint-disable-next-line no-bitwise
					if (mask & 2) { mask &= 1; if (!mask) reallyRemove() }
				}
				attrsResult.then(next, next)
			}
		}

		function reallyRemove() {
			checkState(vnode, original)
			onremove(vnode)
			removeChild(parent, vnode)
		}
	}
	function removeHTML(parent, vnode) {
		for (var i = 0; i < vnode.instance.length; i++) {
			parent.removeChild(vnode.instance[i])
		}
	}
	function removeChild(parent, vnode) {
		// Dodge the recursion overhead in a few of the most common cases.
		while (vnode.dom != null && vnode.dom.parentNode === parent) {
			if (typeof vnode.tag !== "string") {
				vnode = vnode.instance
				if (vnode != null) continue
			} else if (vnode.tag === "<") {
				removeHTML(parent, vnode)
			} else {
				if (vnode.tag !== "[") {
					parent.removeChild(vnode.dom)
					if (!Array.isArray(vnode.children)) break
				}
				if (vnode.children.length === 1) {
					vnode = vnode.children[0]
					if (vnode != null) continue
				} else {
					for (var i = 0; i < vnode.children.length; i++) {
						var child = vnode.children[i]
						if (child != null) removeChild(parent, child)
					}
				}
			}
			break
		}
	}
	function onremove(vnode) {
		if (typeof vnode.tag !== "string" && typeof vnode.state.onremove === "function") callHook.call(vnode.state.onremove, vnode)
		if (vnode.attrs && typeof vnode.attrs.onremove === "function") callHook.call(vnode.attrs.onremove, vnode)
		if (typeof vnode.tag !== "string") {
			if (vnode.instance != null) onremove(vnode.instance)
		} else {
			var children = vnode.children
			if (Array.isArray(children)) {
				for (var i = 0; i < children.length; i++) {
					var child = children[i]
					if (child != null) onremove(child)
				}
			}
		}
	}

	//attrs
	function setAttrs(vnode, attrs, ns) {
		for (var key in attrs) {
			setAttr(vnode, key, null, attrs[key], ns)
		}
	}
	function setAttr(vnode, key, old, value, ns) {
		if (key === "key" || key === "is" || value == null || isLifecycleMethod(key) || (old === value && !isFormAttribute(vnode, key)) && typeof value !== "object") return
		if (key[0] === "o" && key[1] === "n") return updateEvent(vnode, key, value)
		if (key.slice(0, 6) === "xlink:") vnode.dom.setAttributeNS("http://www.w3.org/1999/xlink", key.slice(6), value)
		else if (key === "style") updateStyle(vnode.dom, old, value)
		else if (hasPropertyKey(vnode, key, ns)) {
			if (key === "value") {
				// Only do the coercion if we're actually going to check the value.
				/* eslint-disable no-implicit-coercion */
				//setting input[value] to same value by typing on focused element moves cursor to end in Chrome
				if ((vnode.tag === "input" || vnode.tag === "textarea") && vnode.dom.value === "" + value && vnode.dom === activeElement()) return
				//setting select[value] to same value while having select open blinks select dropdown in Chrome
				if (vnode.tag === "select" && old !== null && vnode.dom.value === "" + value) return
				//setting option[value] to same value while having select open blinks select dropdown in Chrome
				if (vnode.tag === "option" && old !== null && vnode.dom.value === "" + value) return
				/* eslint-enable no-implicit-coercion */
			}
			// If you assign an input type that is not supported by IE 11 with an assignment expression, an error will occur.
			if (vnode.tag === "input" && key === "type") vnode.dom.setAttribute(key, value)
			else vnode.dom[key] = value
		} else {
			if (typeof value === "boolean") {
				if (value) vnode.dom.setAttribute(key, "")
				else vnode.dom.removeAttribute(key)
			}
			else vnode.dom.setAttribute(key === "className" ? "class" : key, value)
		}
	}
	function removeAttr(vnode, key, old, ns) {
		if (key === "key" || key === "is" || old == null || isLifecycleMethod(key)) return
		if (key[0] === "o" && key[1] === "n" && !isLifecycleMethod(key)) updateEvent(vnode, key, undefined)
		else if (key === "style") updateStyle(vnode.dom, old, null)
		else if (
			hasPropertyKey(vnode, key, ns)
			&& key !== "className"
			&& !(key === "value" && (
				vnode.tag === "option"
				|| vnode.tag === "select" && vnode.dom.selectedIndex === -1 && vnode.dom === activeElement()
			))
			&& !(vnode.tag === "input" && key === "type")
		) {
			vnode.dom[key] = null
		} else {
			var nsLastIndex = key.indexOf(":")
			if (nsLastIndex !== -1) key = key.slice(nsLastIndex + 1)
			if (old !== false) vnode.dom.removeAttribute(key === "className" ? "class" : key)
		}
	}
	function setLateSelectAttrs(vnode, attrs) {
		if ("value" in attrs) {
			if(attrs.value === null) {
				if (vnode.dom.selectedIndex !== -1) vnode.dom.value = null
			} else {
				var normalized = "" + attrs.value // eslint-disable-line no-implicit-coercion
				if (vnode.dom.value !== normalized || vnode.dom.selectedIndex === -1) {
					vnode.dom.value = normalized
				}
			}
		}
		if ("selectedIndex" in attrs) setAttr(vnode, "selectedIndex", null, attrs.selectedIndex, undefined)
	}
	function updateAttrs(vnode, old, attrs, ns) {
		if (attrs != null) {
			for (var key in attrs) {
				setAttr(vnode, key, old && old[key], attrs[key], ns)
			}
		}
		var val
		if (old != null) {
			for (var key in old) {
				if (((val = old[key]) != null) && (attrs == null || attrs[key] == null)) {
					removeAttr(vnode, key, val, ns)
				}
			}
		}
	}
	function isFormAttribute(vnode, attr) {
		return attr === "value" || attr === "checked" || attr === "selectedIndex" || attr === "selected" && vnode.dom === activeElement() || vnode.tag === "option" && vnode.dom.parentNode === $doc.activeElement
	}
	function isLifecycleMethod(attr) {
		return attr === "oninit" || attr === "oncreate" || attr === "onupdate" || attr === "onremove" || attr === "onbeforeremove" || attr === "onbeforeupdate"
	}
	function hasPropertyKey(vnode, key, ns) {
		// Filter out namespaced keys
		return ns === undefined && (
			// If it's a custom element, just keep it.
			vnode.tag.indexOf("-") > -1 || vnode.attrs != null && vnode.attrs.is ||
			// If it's a normal element, let's try to avoid a few browser bugs.
			key !== "href" && key !== "list" && key !== "form" && key !== "width" && key !== "height"// && key !== "type"
			// Defer the property check until *after* we check everything.
		) && key in vnode.dom
	}

	//style
	var uppercaseRegex = /[A-Z]/g
	function toLowerCase(capital) { return "-" + capital.toLowerCase() }
	function normalizeKey(key) {
		return key[0] === "-" && key[1] === "-" ? key :
			key === "cssFloat" ? "float" :
				key.replace(uppercaseRegex, toLowerCase)
	}
	function updateStyle(element, old, style) {
		if (old === style) {
			// Styles are equivalent, do nothing.
		} else if (style == null) {
			// New style is missing, just clear it.
			element.style.cssText = ""
		} else if (typeof style !== "object") {
			// New style is a string, let engine deal with patching.
			element.style.cssText = style
		} else if (old == null || typeof old !== "object") {
			// `old` is missing or a string, `style` is an object.
			element.style.cssText = ""
			// Add new style properties
			for (var key in style) {
				var value = style[key]
				if (value != null) element.style.setProperty(normalizeKey(key), String(value))
			}
		} else {
			// Both old & new are (different) objects.
			// Update style properties that have changed
			for (var key in style) {
				var value = style[key]
				if (value != null && (value = String(value)) !== String(old[key])) {
					element.style.setProperty(normalizeKey(key), value)
				}
			}
			// Remove style properties that no longer exist
			for (var key in old) {
				if (old[key] != null && style[key] == null) {
					element.style.removeProperty(normalizeKey(key))
				}
			}
		}
	}

	// Here's an explanation of how this works:
	// 1. The event names are always (by design) prefixed by `on`.
	// 2. The EventListener interface accepts either a function or an object
	//    with a `handleEvent` method.
	// 3. The object does not inherit from `Object.prototype`, to avoid
	//    any potential interference with that (e.g. setters).
	// 4. The event name is remapped to the handler before calling it.
	// 5. In function-based event handlers, `ev.target === this`. We replicate
	//    that below.
	// 6. In function-based event handlers, `return false` prevents the default
	//    action and stops event propagation. We replicate that below.
	function EventDict() {
		// Save this, so the current redraw is correctly tracked.
		this._ = currentRedraw
	}
	EventDict.prototype = Object.create(null)
	EventDict.prototype.handleEvent = function (ev) {
		var handler = this["on" + ev.type]
		var result
		if (typeof handler === "function") result = handler.call(ev.currentTarget, ev)
		else if (typeof handler.handleEvent === "function") handler.handleEvent(ev)
		if (this._ && ev.redraw !== false) (0, this._)()
		if (result === false) {
			ev.preventDefault()
			ev.stopPropagation()
		}
	}

	//event
	function updateEvent(vnode, key, value) {
		if (vnode.events != null) {
			if (vnode.events[key] === value) return
			if (value != null && (typeof value === "function" || typeof value === "object")) {
				if (vnode.events[key] == null) vnode.dom.addEventListener(key.slice(2), vnode.events, false)
				vnode.events[key] = value
			} else {
				if (vnode.events[key] != null) vnode.dom.removeEventListener(key.slice(2), vnode.events, false)
				vnode.events[key] = undefined
			}
		} else if (value != null && (typeof value === "function" || typeof value === "object")) {
			vnode.events = new EventDict()
			vnode.dom.addEventListener(key.slice(2), vnode.events, false)
			vnode.events[key] = value
		}
	}

	//lifecycle
	function initLifecycle(source, vnode, hooks) {
		if (typeof source.oninit === "function") callHook.call(source.oninit, vnode)
		if (typeof source.oncreate === "function") hooks.push(callHook.bind(source.oncreate, vnode))
	}
	function updateLifecycle(source, vnode, hooks) {
		if (typeof source.onupdate === "function") hooks.push(callHook.bind(source.onupdate, vnode))
	}
	function shouldNotUpdate(vnode, old) {
		do {
			if (vnode.attrs != null && typeof vnode.attrs.onbeforeupdate === "function") {
				var force = callHook.call(vnode.attrs.onbeforeupdate, vnode, old)
				if (force !== undefined && !force) break
			}
			if (typeof vnode.tag !== "string" && typeof vnode.state.onbeforeupdate === "function") {
				var force = callHook.call(vnode.state.onbeforeupdate, vnode, old)
				if (force !== undefined && !force) break
			}
			return false
		} while (false); // eslint-disable-line no-constant-condition
		vnode.dom = old.dom
		vnode.domSize = old.domSize
		vnode.instance = old.instance
		// One would think having the actual latest attributes would be ideal,
		// but it doesn't let us properly diff based on our current internal
		// representation. We have to save not only the old DOM info, but also
		// the attributes used to create it, as we diff *that*, not against the
		// DOM directly (with a few exceptions in `setAttr`). And, of course, we
		// need to save the children and text as they are conceptually not
		// unlike special "attributes" internally.
		vnode.attrs = old.attrs
		vnode.children = old.children
		vnode.text = old.text
		return true
	}

	return function(dom, vnodes, redraw) {
		if (!dom) throw new TypeError("Ensure the DOM element being passed to m.route/m.mount/m.render is not undefined.")
		var hooks = []
		var active = activeElement()
		var namespace = dom.namespaceURI

		// First time rendering into a node clears it out
		if (dom.vnodes == null) dom.textContent = ""

		vnodes = Vnode.normalizeChildren(Array.isArray(vnodes) ? vnodes : [vnodes])
		var prevRedraw = currentRedraw
		try {
			currentRedraw = typeof redraw === "function" ? redraw : undefined
			updateNodes(dom, dom.vnodes, vnodes, hooks, null, namespace === "http://www.w3.org/1999/xhtml" ? undefined : namespace)
		} finally {
			currentRedraw = prevRedraw
		}
		dom.vnodes = vnodes
		// `document.activeElement` can return null: https://html.spec.whatwg.org/multipage/interaction.html#dom-document-activeelement
		if (active != null && activeElement() !== active && typeof active.focus === "function") active.focus()
		for (var i = 0; i < hooks.length; i++) hooks[i]()
	}
}


/***/ }),

/***/ "./node_modules/mithril/render/trust.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/render/trust.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js")

module.exports = function(html) {
	if (html == null) html = ""
	return Vnode("<", undefined, undefined, html, undefined, undefined)
}


/***/ }),

/***/ "./node_modules/mithril/render/vnode.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/render/vnode.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function Vnode(tag, key, attrs, children, text, dom) {
	return {tag: tag, key: key, attrs: attrs, children: children, text: text, dom: dom, domSize: undefined, state: undefined, events: undefined, instance: undefined}
}
Vnode.normalize = function(node) {
	if (Array.isArray(node)) return Vnode("[", undefined, undefined, Vnode.normalizeChildren(node), undefined, undefined)
	if (node == null || typeof node === "boolean") return null
	if (typeof node === "object") return node
	return Vnode("#", undefined, undefined, String(node), undefined, undefined)
}
Vnode.normalizeChildren = function(input) {
	var children = []
	if (input.length) {
		var isKeyed = input[0] != null && input[0].key != null
		// Note: this is a *very* perf-sensitive check.
		// Fun fact: merging the loop like this is somehow faster than splitting
		// it, noticeably so.
		for (var i = 1; i < input.length; i++) {
			if ((input[i] != null && input[i].key != null) !== isKeyed) {
				throw new TypeError("Vnodes must either always have keys or never have keys!")
			}
		}
		for (var i = 0; i < input.length; i++) {
			children[i] = Vnode.normalize(input[i])
		}
	}
	return children
}

module.exports = Vnode


/***/ }),

/***/ "./node_modules/mithril/request.js":
/*!*****************************************!*\
  !*** ./node_modules/mithril/request.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var PromisePolyfill = __webpack_require__(/*! ./promise/promise */ "./node_modules/mithril/promise/promise.js")
var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js")

module.exports = __webpack_require__(/*! ./request/request */ "./node_modules/mithril/request/request.js")(window, PromisePolyfill, mountRedraw.redraw)


/***/ }),

/***/ "./node_modules/mithril/request/request.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/request/request.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var buildPathname = __webpack_require__(/*! ../pathname/build */ "./node_modules/mithril/pathname/build.js")

module.exports = function($window, Promise, oncompletion) {
	var callbackCount = 0

	function PromiseProxy(executor) {
		return new Promise(executor)
	}

	// In case the global Promise is some userland library's where they rely on
	// `foo instanceof this.constructor`, `this.constructor.resolve(value)`, or
	// similar. Let's *not* break them.
	PromiseProxy.prototype = Promise.prototype
	PromiseProxy.__proto__ = Promise // eslint-disable-line no-proto

	function makeRequest(factory) {
		return function(url, args) {
			if (typeof url !== "string") { args = url; url = url.url }
			else if (args == null) args = {}
			var promise = new Promise(function(resolve, reject) {
				factory(buildPathname(url, args.params), args, function (data) {
					if (typeof args.type === "function") {
						if (Array.isArray(data)) {
							for (var i = 0; i < data.length; i++) {
								data[i] = new args.type(data[i])
							}
						}
						else data = new args.type(data)
					}
					resolve(data)
				}, reject)
			})
			if (args.background === true) return promise
			var count = 0
			function complete() {
				if (--count === 0 && typeof oncompletion === "function") oncompletion()
			}

			return wrap(promise)

			function wrap(promise) {
				var then = promise.then
				// Set the constructor, so engines know to not await or resolve
				// this as a native promise. At the time of writing, this is
				// only necessary for V8, but their behavior is the correct
				// behavior per spec. See this spec issue for more details:
				// https://github.com/tc39/ecma262/issues/1577. Also, see the
				// corresponding comment in `request/tests/test-request.js` for
				// a bit more background on the issue at hand.
				promise.constructor = PromiseProxy
				promise.then = function() {
					count++
					var next = then.apply(promise, arguments)
					next.then(complete, function(e) {
						complete()
						if (count === 0) throw e
					})
					return wrap(next)
				}
				return promise
			}
		}
	}

	function hasHeader(args, name) {
		for (var key in args.headers) {
			if ({}.hasOwnProperty.call(args.headers, key) && name.test(key)) return true
		}
		return false
	}

	return {
		request: makeRequest(function(url, args, resolve, reject) {
			var method = args.method != null ? args.method.toUpperCase() : "GET"
			var body = args.body
			var assumeJSON = (args.serialize == null || args.serialize === JSON.serialize) && !(body instanceof $window.FormData)
			var responseType = args.responseType || (typeof args.extract === "function" ? "" : "json")

			var xhr = new $window.XMLHttpRequest(), aborted = false
			var original = xhr, replacedAbort
			var abort = xhr.abort

			xhr.abort = function() {
				aborted = true
				abort.call(this)
			}

			xhr.open(method, url, args.async !== false, typeof args.user === "string" ? args.user : undefined, typeof args.password === "string" ? args.password : undefined)

			if (assumeJSON && body != null && !hasHeader(args, /^content-type$/i)) {
				xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8")
			}
			if (typeof args.deserialize !== "function" && !hasHeader(args, /^accept$/i)) {
				xhr.setRequestHeader("Accept", "application/json, text/*")
			}
			if (args.withCredentials) xhr.withCredentials = args.withCredentials
			if (args.timeout) xhr.timeout = args.timeout
			xhr.responseType = responseType

			for (var key in args.headers) {
				if ({}.hasOwnProperty.call(args.headers, key)) {
					xhr.setRequestHeader(key, args.headers[key])
				}
			}

			xhr.onreadystatechange = function(ev) {
				// Don't throw errors on xhr.abort().
				if (aborted) return

				if (ev.target.readyState === 4) {
					try {
						var success = (ev.target.status >= 200 && ev.target.status < 300) || ev.target.status === 304 || (/^file:\/\//i).test(url)
						// When the response type isn't "" or "text",
						// `xhr.responseText` is the wrong thing to use.
						// Browsers do the right thing and throw here, and we
						// should honor that and do the right thing by
						// preferring `xhr.response` where possible/practical.
						var response = ev.target.response, message

						if (responseType === "json") {
							// For IE and Edge, which don't implement
							// `responseType: "json"`.
							if (!ev.target.responseType && typeof args.extract !== "function") response = JSON.parse(ev.target.responseText)
						} else if (!responseType || responseType === "text") {
							// Only use this default if it's text. If a parsed
							// document is needed on old IE and friends (all
							// unsupported), the user should use a custom
							// `config` instead. They're already using this at
							// their own risk.
							if (response == null) response = ev.target.responseText
						}

						if (typeof args.extract === "function") {
							response = args.extract(ev.target, args)
							success = true
						} else if (typeof args.deserialize === "function") {
							response = args.deserialize(response)
						}
						if (success) resolve(response)
						else {
							try { message = ev.target.responseText }
							catch (e) { message = response }
							var error = new Error(message)
							error.code = ev.target.status
							error.response = response
							reject(error)
						}
					}
					catch (e) {
						reject(e)
					}
				}
			}

			if (typeof args.config === "function") {
				xhr = args.config(xhr, args, url) || xhr

				// Propagate the `abort` to any replacement XHR as well.
				if (xhr !== original) {
					replacedAbort = xhr.abort
					xhr.abort = function() {
						aborted = true
						replacedAbort.call(this)
					}
				}
			}

			if (body == null) xhr.send()
			else if (typeof args.serialize === "function") xhr.send(args.serialize(body))
			else if (body instanceof $window.FormData) xhr.send(body)
			else xhr.send(JSON.stringify(body))
		}),
		jsonp: makeRequest(function(url, args, resolve, reject) {
			var callbackName = args.callbackName || "_mithril_" + Math.round(Math.random() * 1e16) + "_" + callbackCount++
			var script = $window.document.createElement("script")
			$window[callbackName] = function(data) {
				delete $window[callbackName]
				script.parentNode.removeChild(script)
				resolve(data)
			}
			script.onerror = function() {
				delete $window[callbackName]
				script.parentNode.removeChild(script)
				reject(new Error("JSONP request failed"))
			}
			script.src = url + (url.indexOf("?") < 0 ? "?" : "&") +
				encodeURIComponent(args.callbackKey || "callback") + "=" +
				encodeURIComponent(callbackName)
			$window.document.documentElement.appendChild(script)
		}),
	}
}


/***/ }),

/***/ "./node_modules/mithril/route.js":
/*!***************************************!*\
  !*** ./node_modules/mithril/route.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js")

module.exports = __webpack_require__(/*! ./api/router */ "./node_modules/mithril/api/router.js")(window, mountRedraw)


/***/ }),

/***/ "./node_modules/mithril/stream.js":
/*!****************************************!*\
  !*** ./node_modules/mithril/stream.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = __webpack_require__(/*! ./stream/stream */ "./node_modules/mithril/stream/stream.js")


/***/ }),

/***/ "./node_modules/mithril/stream/stream.js":
/*!***********************************************!*\
  !*** ./node_modules/mithril/stream/stream.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* eslint-disable */
;(function() {
"use strict"
/* eslint-enable */
Stream.SKIP = {}
Stream.lift = lift
Stream.scan = scan
Stream.merge = merge
Stream.combine = combine
Stream.scanMerge = scanMerge
Stream["fantasy-land/of"] = Stream

var warnedHalt = false
Object.defineProperty(Stream, "HALT", {
	get: function() {
		warnedHalt || console.log("HALT is deprecated and has been renamed to SKIP");
		warnedHalt = true
		return Stream.SKIP
	}
})

function Stream(value) {
	var dependentStreams = []
	var dependentFns = []

	function stream(v) {
		if (arguments.length && v !== Stream.SKIP) {
			value = v
			if (open(stream)) {
				stream._changing()
				stream._state = "active"
				dependentStreams.forEach(function(s, i) { s(dependentFns[i](value)) })
			}
		}

		return value
	}

	stream.constructor = Stream
	stream._state = arguments.length && value !== Stream.SKIP ? "active" : "pending"
	stream._parents = []

	stream._changing = function() {
		if (open(stream)) stream._state = "changing"
		dependentStreams.forEach(function(s) {
			s._changing()
		})
	}

	stream._map = function(fn, ignoreInitial) {
		var target = ignoreInitial ? Stream() : Stream(fn(value))
		target._parents.push(stream)
		dependentStreams.push(target)
		dependentFns.push(fn)
		return target
	}

	stream.map = function(fn) {
		return stream._map(fn, stream._state !== "active")
	}

	var end
	function createEnd() {
		end = Stream()
		end.map(function(value) {
			if (value === true) {
				stream._parents.forEach(function (p) {p._unregisterChild(stream)})
				stream._state = "ended"
				stream._parents.length = dependentStreams.length = dependentFns.length = 0
			}
			return value
		})
		return end
	}

	stream.toJSON = function() { return value != null && typeof value.toJSON === "function" ? value.toJSON() : value }

	stream["fantasy-land/map"] = stream.map
	stream["fantasy-land/ap"] = function(x) { return combine(function(s1, s2) { return s1()(s2()) }, [x, stream]) }

	stream._unregisterChild = function(child) {
		var childIndex = dependentStreams.indexOf(child)
		if (childIndex !== -1) {
			dependentStreams.splice(childIndex, 1)
			dependentFns.splice(childIndex, 1)
		}
	}

	Object.defineProperty(stream, "end", {
		get: function() { return end || createEnd() }
	})

	return stream
}

function combine(fn, streams) {
	var ready = streams.every(function(s) {
		if (s.constructor !== Stream)
			throw new Error("Ensure that each item passed to stream.combine/stream.merge/lift is a stream")
		return s._state === "active"
	})
	var stream = ready
		? Stream(fn.apply(null, streams.concat([streams])))
		: Stream()

	var changed = []

	var mappers = streams.map(function(s) {
		return s._map(function(value) {
			changed.push(s)
			if (ready || streams.every(function(s) { return s._state !== "pending" })) {
				ready = true
				stream(fn.apply(null, streams.concat([changed])))
				changed = []
			}
			return value
		}, true)
	})

	var endStream = stream.end.map(function(value) {
		if (value === true) {
			mappers.forEach(function(mapper) { mapper.end(true) })
			endStream.end(true)
		}
		return undefined
	})

	return stream
}

function merge(streams) {
	return combine(function() { return streams.map(function(s) { return s() }) }, streams)
}

function scan(fn, acc, origin) {
	var stream = origin.map(function(v) {
		var next = fn(acc, v)
		if (next !== Stream.SKIP) acc = next
		return next
	})
	stream(acc)
	return stream
}

function scanMerge(tuples, seed) {
	var streams = tuples.map(function(tuple) { return tuple[0] })

	var stream = combine(function() {
		var changed = arguments[arguments.length - 1]
		streams.forEach(function(stream, i) {
			if (changed.indexOf(stream) > -1)
				seed = tuples[i][1](seed, stream())
		})

		return seed
	}, streams)

	stream(seed)

	return stream
}

function lift() {
	var fn = arguments[0]
	var streams = Array.prototype.slice.call(arguments, 1)
	return merge(streams).map(function(streams) {
		return fn.apply(undefined, streams)
	})
}

function open(s) {
	return s._state === "pending" || s._state === "active" || s._state === "changing"
}

if (true) module["exports"] = Stream
else {}

}());


/***/ }),

/***/ "./node_modules/popper.js/dist/esm/popper.js":
/*!***************************************************!*\
  !*** ./node_modules/popper.js/dist/esm/popper.js ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(global) {/**!
 * @fileOverview Kickass library to create and place poppers near their reference elements.
 * @version 1.16.1
 * @license
 * Copyright (c) 2016 Federico Zivolo and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
var isBrowser = typeof window !== 'undefined' && typeof document !== 'undefined' && typeof navigator !== 'undefined';

var timeoutDuration = function () {
  var longerTimeoutBrowsers = ['Edge', 'Trident', 'Firefox'];
  for (var i = 0; i < longerTimeoutBrowsers.length; i += 1) {
    if (isBrowser && navigator.userAgent.indexOf(longerTimeoutBrowsers[i]) >= 0) {
      return 1;
    }
  }
  return 0;
}();

function microtaskDebounce(fn) {
  var called = false;
  return function () {
    if (called) {
      return;
    }
    called = true;
    window.Promise.resolve().then(function () {
      called = false;
      fn();
    });
  };
}

function taskDebounce(fn) {
  var scheduled = false;
  return function () {
    if (!scheduled) {
      scheduled = true;
      setTimeout(function () {
        scheduled = false;
        fn();
      }, timeoutDuration);
    }
  };
}

var supportsMicroTasks = isBrowser && window.Promise;

/**
* Create a debounced version of a method, that's asynchronously deferred
* but called in the minimum time possible.
*
* @method
* @memberof Popper.Utils
* @argument {Function} fn
* @returns {Function}
*/
var debounce = supportsMicroTasks ? microtaskDebounce : taskDebounce;

/**
 * Check if the given variable is a function
 * @method
 * @memberof Popper.Utils
 * @argument {Any} functionToCheck - variable to check
 * @returns {Boolean} answer to: is a function?
 */
function isFunction(functionToCheck) {
  var getType = {};
  return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
}

/**
 * Get CSS computed property of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Eement} element
 * @argument {String} property
 */
function getStyleComputedProperty(element, property) {
  if (element.nodeType !== 1) {
    return [];
  }
  // NOTE: 1 DOM access here
  var window = element.ownerDocument.defaultView;
  var css = window.getComputedStyle(element, null);
  return property ? css[property] : css;
}

/**
 * Returns the parentNode or the host of the element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} parent
 */
function getParentNode(element) {
  if (element.nodeName === 'HTML') {
    return element;
  }
  return element.parentNode || element.host;
}

/**
 * Returns the scrolling parent of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} scroll parent
 */
function getScrollParent(element) {
  // Return body, `getScroll` will take care to get the correct `scrollTop` from it
  if (!element) {
    return document.body;
  }

  switch (element.nodeName) {
    case 'HTML':
    case 'BODY':
      return element.ownerDocument.body;
    case '#document':
      return element.body;
  }

  // Firefox want us to check `-x` and `-y` variations as well

  var _getStyleComputedProp = getStyleComputedProperty(element),
      overflow = _getStyleComputedProp.overflow,
      overflowX = _getStyleComputedProp.overflowX,
      overflowY = _getStyleComputedProp.overflowY;

  if (/(auto|scroll|overlay)/.test(overflow + overflowY + overflowX)) {
    return element;
  }

  return getScrollParent(getParentNode(element));
}

/**
 * Returns the reference node of the reference object, or the reference object itself.
 * @method
 * @memberof Popper.Utils
 * @param {Element|Object} reference - the reference element (the popper will be relative to this)
 * @returns {Element} parent
 */
function getReferenceNode(reference) {
  return reference && reference.referenceNode ? reference.referenceNode : reference;
}

var isIE11 = isBrowser && !!(window.MSInputMethodContext && document.documentMode);
var isIE10 = isBrowser && /MSIE 10/.test(navigator.userAgent);

/**
 * Determines if the browser is Internet Explorer
 * @method
 * @memberof Popper.Utils
 * @param {Number} version to check
 * @returns {Boolean} isIE
 */
function isIE(version) {
  if (version === 11) {
    return isIE11;
  }
  if (version === 10) {
    return isIE10;
  }
  return isIE11 || isIE10;
}

/**
 * Returns the offset parent of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} offset parent
 */
function getOffsetParent(element) {
  if (!element) {
    return document.documentElement;
  }

  var noOffsetParent = isIE(10) ? document.body : null;

  // NOTE: 1 DOM access here
  var offsetParent = element.offsetParent || null;
  // Skip hidden elements which don't have an offsetParent
  while (offsetParent === noOffsetParent && element.nextElementSibling) {
    offsetParent = (element = element.nextElementSibling).offsetParent;
  }

  var nodeName = offsetParent && offsetParent.nodeName;

  if (!nodeName || nodeName === 'BODY' || nodeName === 'HTML') {
    return element ? element.ownerDocument.documentElement : document.documentElement;
  }

  // .offsetParent will return the closest TH, TD or TABLE in case
  // no offsetParent is present, I hate this job...
  if (['TH', 'TD', 'TABLE'].indexOf(offsetParent.nodeName) !== -1 && getStyleComputedProperty(offsetParent, 'position') === 'static') {
    return getOffsetParent(offsetParent);
  }

  return offsetParent;
}

function isOffsetContainer(element) {
  var nodeName = element.nodeName;

  if (nodeName === 'BODY') {
    return false;
  }
  return nodeName === 'HTML' || getOffsetParent(element.firstElementChild) === element;
}

/**
 * Finds the root node (document, shadowDOM root) of the given element
 * @method
 * @memberof Popper.Utils
 * @argument {Element} node
 * @returns {Element} root node
 */
function getRoot(node) {
  if (node.parentNode !== null) {
    return getRoot(node.parentNode);
  }

  return node;
}

/**
 * Finds the offset parent common to the two provided nodes
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element1
 * @argument {Element} element2
 * @returns {Element} common offset parent
 */
function findCommonOffsetParent(element1, element2) {
  // This check is needed to avoid errors in case one of the elements isn't defined for any reason
  if (!element1 || !element1.nodeType || !element2 || !element2.nodeType) {
    return document.documentElement;
  }

  // Here we make sure to give as "start" the element that comes first in the DOM
  var order = element1.compareDocumentPosition(element2) & Node.DOCUMENT_POSITION_FOLLOWING;
  var start = order ? element1 : element2;
  var end = order ? element2 : element1;

  // Get common ancestor container
  var range = document.createRange();
  range.setStart(start, 0);
  range.setEnd(end, 0);
  var commonAncestorContainer = range.commonAncestorContainer;

  // Both nodes are inside #document

  if (element1 !== commonAncestorContainer && element2 !== commonAncestorContainer || start.contains(end)) {
    if (isOffsetContainer(commonAncestorContainer)) {
      return commonAncestorContainer;
    }

    return getOffsetParent(commonAncestorContainer);
  }

  // one of the nodes is inside shadowDOM, find which one
  var element1root = getRoot(element1);
  if (element1root.host) {
    return findCommonOffsetParent(element1root.host, element2);
  } else {
    return findCommonOffsetParent(element1, getRoot(element2).host);
  }
}

/**
 * Gets the scroll value of the given element in the given side (top and left)
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @argument {String} side `top` or `left`
 * @returns {number} amount of scrolled pixels
 */
function getScroll(element) {
  var side = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'top';

  var upperSide = side === 'top' ? 'scrollTop' : 'scrollLeft';
  var nodeName = element.nodeName;

  if (nodeName === 'BODY' || nodeName === 'HTML') {
    var html = element.ownerDocument.documentElement;
    var scrollingElement = element.ownerDocument.scrollingElement || html;
    return scrollingElement[upperSide];
  }

  return element[upperSide];
}

/*
 * Sum or subtract the element scroll values (left and top) from a given rect object
 * @method
 * @memberof Popper.Utils
 * @param {Object} rect - Rect object you want to change
 * @param {HTMLElement} element - The element from the function reads the scroll values
 * @param {Boolean} subtract - set to true if you want to subtract the scroll values
 * @return {Object} rect - The modifier rect object
 */
function includeScroll(rect, element) {
  var subtract = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

  var scrollTop = getScroll(element, 'top');
  var scrollLeft = getScroll(element, 'left');
  var modifier = subtract ? -1 : 1;
  rect.top += scrollTop * modifier;
  rect.bottom += scrollTop * modifier;
  rect.left += scrollLeft * modifier;
  rect.right += scrollLeft * modifier;
  return rect;
}

/*
 * Helper to detect borders of a given element
 * @method
 * @memberof Popper.Utils
 * @param {CSSStyleDeclaration} styles
 * Result of `getStyleComputedProperty` on the given element
 * @param {String} axis - `x` or `y`
 * @return {number} borders - The borders size of the given axis
 */

function getBordersSize(styles, axis) {
  var sideA = axis === 'x' ? 'Left' : 'Top';
  var sideB = sideA === 'Left' ? 'Right' : 'Bottom';

  return parseFloat(styles['border' + sideA + 'Width']) + parseFloat(styles['border' + sideB + 'Width']);
}

function getSize(axis, body, html, computedStyle) {
  return Math.max(body['offset' + axis], body['scroll' + axis], html['client' + axis], html['offset' + axis], html['scroll' + axis], isIE(10) ? parseInt(html['offset' + axis]) + parseInt(computedStyle['margin' + (axis === 'Height' ? 'Top' : 'Left')]) + parseInt(computedStyle['margin' + (axis === 'Height' ? 'Bottom' : 'Right')]) : 0);
}

function getWindowSizes(document) {
  var body = document.body;
  var html = document.documentElement;
  var computedStyle = isIE(10) && getComputedStyle(html);

  return {
    height: getSize('Height', body, html, computedStyle),
    width: getSize('Width', body, html, computedStyle)
  };
}

var classCallCheck = function (instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
};

var createClass = function () {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  return function (Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();





var defineProperty = function (obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }

  return obj;
};

var _extends = Object.assign || function (target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i];

    for (var key in source) {
      if (Object.prototype.hasOwnProperty.call(source, key)) {
        target[key] = source[key];
      }
    }
  }

  return target;
};

/**
 * Given element offsets, generate an output similar to getBoundingClientRect
 * @method
 * @memberof Popper.Utils
 * @argument {Object} offsets
 * @returns {Object} ClientRect like output
 */
function getClientRect(offsets) {
  return _extends({}, offsets, {
    right: offsets.left + offsets.width,
    bottom: offsets.top + offsets.height
  });
}

/**
 * Get bounding client rect of given element
 * @method
 * @memberof Popper.Utils
 * @param {HTMLElement} element
 * @return {Object} client rect
 */
function getBoundingClientRect(element) {
  var rect = {};

  // IE10 10 FIX: Please, don't ask, the element isn't
  // considered in DOM in some circumstances...
  // This isn't reproducible in IE10 compatibility mode of IE11
  try {
    if (isIE(10)) {
      rect = element.getBoundingClientRect();
      var scrollTop = getScroll(element, 'top');
      var scrollLeft = getScroll(element, 'left');
      rect.top += scrollTop;
      rect.left += scrollLeft;
      rect.bottom += scrollTop;
      rect.right += scrollLeft;
    } else {
      rect = element.getBoundingClientRect();
    }
  } catch (e) {}

  var result = {
    left: rect.left,
    top: rect.top,
    width: rect.right - rect.left,
    height: rect.bottom - rect.top
  };

  // subtract scrollbar size from sizes
  var sizes = element.nodeName === 'HTML' ? getWindowSizes(element.ownerDocument) : {};
  var width = sizes.width || element.clientWidth || result.width;
  var height = sizes.height || element.clientHeight || result.height;

  var horizScrollbar = element.offsetWidth - width;
  var vertScrollbar = element.offsetHeight - height;

  // if an hypothetical scrollbar is detected, we must be sure it's not a `border`
  // we make this check conditional for performance reasons
  if (horizScrollbar || vertScrollbar) {
    var styles = getStyleComputedProperty(element);
    horizScrollbar -= getBordersSize(styles, 'x');
    vertScrollbar -= getBordersSize(styles, 'y');

    result.width -= horizScrollbar;
    result.height -= vertScrollbar;
  }

  return getClientRect(result);
}

function getOffsetRectRelativeToArbitraryNode(children, parent) {
  var fixedPosition = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

  var isIE10 = isIE(10);
  var isHTML = parent.nodeName === 'HTML';
  var childrenRect = getBoundingClientRect(children);
  var parentRect = getBoundingClientRect(parent);
  var scrollParent = getScrollParent(children);

  var styles = getStyleComputedProperty(parent);
  var borderTopWidth = parseFloat(styles.borderTopWidth);
  var borderLeftWidth = parseFloat(styles.borderLeftWidth);

  // In cases where the parent is fixed, we must ignore negative scroll in offset calc
  if (fixedPosition && isHTML) {
    parentRect.top = Math.max(parentRect.top, 0);
    parentRect.left = Math.max(parentRect.left, 0);
  }
  var offsets = getClientRect({
    top: childrenRect.top - parentRect.top - borderTopWidth,
    left: childrenRect.left - parentRect.left - borderLeftWidth,
    width: childrenRect.width,
    height: childrenRect.height
  });
  offsets.marginTop = 0;
  offsets.marginLeft = 0;

  // Subtract margins of documentElement in case it's being used as parent
  // we do this only on HTML because it's the only element that behaves
  // differently when margins are applied to it. The margins are included in
  // the box of the documentElement, in the other cases not.
  if (!isIE10 && isHTML) {
    var marginTop = parseFloat(styles.marginTop);
    var marginLeft = parseFloat(styles.marginLeft);

    offsets.top -= borderTopWidth - marginTop;
    offsets.bottom -= borderTopWidth - marginTop;
    offsets.left -= borderLeftWidth - marginLeft;
    offsets.right -= borderLeftWidth - marginLeft;

    // Attach marginTop and marginLeft because in some circumstances we may need them
    offsets.marginTop = marginTop;
    offsets.marginLeft = marginLeft;
  }

  if (isIE10 && !fixedPosition ? parent.contains(scrollParent) : parent === scrollParent && scrollParent.nodeName !== 'BODY') {
    offsets = includeScroll(offsets, parent);
  }

  return offsets;
}

function getViewportOffsetRectRelativeToArtbitraryNode(element) {
  var excludeScroll = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  var html = element.ownerDocument.documentElement;
  var relativeOffset = getOffsetRectRelativeToArbitraryNode(element, html);
  var width = Math.max(html.clientWidth, window.innerWidth || 0);
  var height = Math.max(html.clientHeight, window.innerHeight || 0);

  var scrollTop = !excludeScroll ? getScroll(html) : 0;
  var scrollLeft = !excludeScroll ? getScroll(html, 'left') : 0;

  var offset = {
    top: scrollTop - relativeOffset.top + relativeOffset.marginTop,
    left: scrollLeft - relativeOffset.left + relativeOffset.marginLeft,
    width: width,
    height: height
  };

  return getClientRect(offset);
}

/**
 * Check if the given element is fixed or is inside a fixed parent
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @argument {Element} customContainer
 * @returns {Boolean} answer to "isFixed?"
 */
function isFixed(element) {
  var nodeName = element.nodeName;
  if (nodeName === 'BODY' || nodeName === 'HTML') {
    return false;
  }
  if (getStyleComputedProperty(element, 'position') === 'fixed') {
    return true;
  }
  var parentNode = getParentNode(element);
  if (!parentNode) {
    return false;
  }
  return isFixed(parentNode);
}

/**
 * Finds the first parent of an element that has a transformed property defined
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Element} first transformed parent or documentElement
 */

function getFixedPositionOffsetParent(element) {
  // This check is needed to avoid errors in case one of the elements isn't defined for any reason
  if (!element || !element.parentElement || isIE()) {
    return document.documentElement;
  }
  var el = element.parentElement;
  while (el && getStyleComputedProperty(el, 'transform') === 'none') {
    el = el.parentElement;
  }
  return el || document.documentElement;
}

/**
 * Computed the boundaries limits and return them
 * @method
 * @memberof Popper.Utils
 * @param {HTMLElement} popper
 * @param {HTMLElement} reference
 * @param {number} padding
 * @param {HTMLElement} boundariesElement - Element used to define the boundaries
 * @param {Boolean} fixedPosition - Is in fixed position mode
 * @returns {Object} Coordinates of the boundaries
 */
function getBoundaries(popper, reference, padding, boundariesElement) {
  var fixedPosition = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : false;

  // NOTE: 1 DOM access here

  var boundaries = { top: 0, left: 0 };
  var offsetParent = fixedPosition ? getFixedPositionOffsetParent(popper) : findCommonOffsetParent(popper, getReferenceNode(reference));

  // Handle viewport case
  if (boundariesElement === 'viewport') {
    boundaries = getViewportOffsetRectRelativeToArtbitraryNode(offsetParent, fixedPosition);
  } else {
    // Handle other cases based on DOM element used as boundaries
    var boundariesNode = void 0;
    if (boundariesElement === 'scrollParent') {
      boundariesNode = getScrollParent(getParentNode(reference));
      if (boundariesNode.nodeName === 'BODY') {
        boundariesNode = popper.ownerDocument.documentElement;
      }
    } else if (boundariesElement === 'window') {
      boundariesNode = popper.ownerDocument.documentElement;
    } else {
      boundariesNode = boundariesElement;
    }

    var offsets = getOffsetRectRelativeToArbitraryNode(boundariesNode, offsetParent, fixedPosition);

    // In case of HTML, we need a different computation
    if (boundariesNode.nodeName === 'HTML' && !isFixed(offsetParent)) {
      var _getWindowSizes = getWindowSizes(popper.ownerDocument),
          height = _getWindowSizes.height,
          width = _getWindowSizes.width;

      boundaries.top += offsets.top - offsets.marginTop;
      boundaries.bottom = height + offsets.top;
      boundaries.left += offsets.left - offsets.marginLeft;
      boundaries.right = width + offsets.left;
    } else {
      // for all the other DOM elements, this one is good
      boundaries = offsets;
    }
  }

  // Add paddings
  padding = padding || 0;
  var isPaddingNumber = typeof padding === 'number';
  boundaries.left += isPaddingNumber ? padding : padding.left || 0;
  boundaries.top += isPaddingNumber ? padding : padding.top || 0;
  boundaries.right -= isPaddingNumber ? padding : padding.right || 0;
  boundaries.bottom -= isPaddingNumber ? padding : padding.bottom || 0;

  return boundaries;
}

function getArea(_ref) {
  var width = _ref.width,
      height = _ref.height;

  return width * height;
}

/**
 * Utility used to transform the `auto` placement to the placement with more
 * available space.
 * @method
 * @memberof Popper.Utils
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function computeAutoPlacement(placement, refRect, popper, reference, boundariesElement) {
  var padding = arguments.length > 5 && arguments[5] !== undefined ? arguments[5] : 0;

  if (placement.indexOf('auto') === -1) {
    return placement;
  }

  var boundaries = getBoundaries(popper, reference, padding, boundariesElement);

  var rects = {
    top: {
      width: boundaries.width,
      height: refRect.top - boundaries.top
    },
    right: {
      width: boundaries.right - refRect.right,
      height: boundaries.height
    },
    bottom: {
      width: boundaries.width,
      height: boundaries.bottom - refRect.bottom
    },
    left: {
      width: refRect.left - boundaries.left,
      height: boundaries.height
    }
  };

  var sortedAreas = Object.keys(rects).map(function (key) {
    return _extends({
      key: key
    }, rects[key], {
      area: getArea(rects[key])
    });
  }).sort(function (a, b) {
    return b.area - a.area;
  });

  var filteredAreas = sortedAreas.filter(function (_ref2) {
    var width = _ref2.width,
        height = _ref2.height;
    return width >= popper.clientWidth && height >= popper.clientHeight;
  });

  var computedPlacement = filteredAreas.length > 0 ? filteredAreas[0].key : sortedAreas[0].key;

  var variation = placement.split('-')[1];

  return computedPlacement + (variation ? '-' + variation : '');
}

/**
 * Get offsets to the reference element
 * @method
 * @memberof Popper.Utils
 * @param {Object} state
 * @param {Element} popper - the popper element
 * @param {Element} reference - the reference element (the popper will be relative to this)
 * @param {Element} fixedPosition - is in fixed position mode
 * @returns {Object} An object containing the offsets which will be applied to the popper
 */
function getReferenceOffsets(state, popper, reference) {
  var fixedPosition = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;

  var commonOffsetParent = fixedPosition ? getFixedPositionOffsetParent(popper) : findCommonOffsetParent(popper, getReferenceNode(reference));
  return getOffsetRectRelativeToArbitraryNode(reference, commonOffsetParent, fixedPosition);
}

/**
 * Get the outer sizes of the given element (offset size + margins)
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element
 * @returns {Object} object containing width and height properties
 */
function getOuterSizes(element) {
  var window = element.ownerDocument.defaultView;
  var styles = window.getComputedStyle(element);
  var x = parseFloat(styles.marginTop || 0) + parseFloat(styles.marginBottom || 0);
  var y = parseFloat(styles.marginLeft || 0) + parseFloat(styles.marginRight || 0);
  var result = {
    width: element.offsetWidth + y,
    height: element.offsetHeight + x
  };
  return result;
}

/**
 * Get the opposite placement of the given one
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement
 * @returns {String} flipped placement
 */
function getOppositePlacement(placement) {
  var hash = { left: 'right', right: 'left', bottom: 'top', top: 'bottom' };
  return placement.replace(/left|right|bottom|top/g, function (matched) {
    return hash[matched];
  });
}

/**
 * Get offsets to the popper
 * @method
 * @memberof Popper.Utils
 * @param {Object} position - CSS position the Popper will get applied
 * @param {HTMLElement} popper - the popper element
 * @param {Object} referenceOffsets - the reference offsets (the popper will be relative to this)
 * @param {String} placement - one of the valid placement options
 * @returns {Object} popperOffsets - An object containing the offsets which will be applied to the popper
 */
function getPopperOffsets(popper, referenceOffsets, placement) {
  placement = placement.split('-')[0];

  // Get popper node sizes
  var popperRect = getOuterSizes(popper);

  // Add position, width and height to our offsets object
  var popperOffsets = {
    width: popperRect.width,
    height: popperRect.height
  };

  // depending by the popper placement we have to compute its offsets slightly differently
  var isHoriz = ['right', 'left'].indexOf(placement) !== -1;
  var mainSide = isHoriz ? 'top' : 'left';
  var secondarySide = isHoriz ? 'left' : 'top';
  var measurement = isHoriz ? 'height' : 'width';
  var secondaryMeasurement = !isHoriz ? 'height' : 'width';

  popperOffsets[mainSide] = referenceOffsets[mainSide] + referenceOffsets[measurement] / 2 - popperRect[measurement] / 2;
  if (placement === secondarySide) {
    popperOffsets[secondarySide] = referenceOffsets[secondarySide] - popperRect[secondaryMeasurement];
  } else {
    popperOffsets[secondarySide] = referenceOffsets[getOppositePlacement(secondarySide)];
  }

  return popperOffsets;
}

/**
 * Mimics the `find` method of Array
 * @method
 * @memberof Popper.Utils
 * @argument {Array} arr
 * @argument prop
 * @argument value
 * @returns index or -1
 */
function find(arr, check) {
  // use native find if supported
  if (Array.prototype.find) {
    return arr.find(check);
  }

  // use `filter` to obtain the same behavior of `find`
  return arr.filter(check)[0];
}

/**
 * Return the index of the matching object
 * @method
 * @memberof Popper.Utils
 * @argument {Array} arr
 * @argument prop
 * @argument value
 * @returns index or -1
 */
function findIndex(arr, prop, value) {
  // use native findIndex if supported
  if (Array.prototype.findIndex) {
    return arr.findIndex(function (cur) {
      return cur[prop] === value;
    });
  }

  // use `find` + `indexOf` if `findIndex` isn't supported
  var match = find(arr, function (obj) {
    return obj[prop] === value;
  });
  return arr.indexOf(match);
}

/**
 * Loop trough the list of modifiers and run them in order,
 * each of them will then edit the data object.
 * @method
 * @memberof Popper.Utils
 * @param {dataObject} data
 * @param {Array} modifiers
 * @param {String} ends - Optional modifier name used as stopper
 * @returns {dataObject}
 */
function runModifiers(modifiers, data, ends) {
  var modifiersToRun = ends === undefined ? modifiers : modifiers.slice(0, findIndex(modifiers, 'name', ends));

  modifiersToRun.forEach(function (modifier) {
    if (modifier['function']) {
      // eslint-disable-line dot-notation
      console.warn('`modifier.function` is deprecated, use `modifier.fn`!');
    }
    var fn = modifier['function'] || modifier.fn; // eslint-disable-line dot-notation
    if (modifier.enabled && isFunction(fn)) {
      // Add properties to offsets to make them a complete clientRect object
      // we do this before each modifier to make sure the previous one doesn't
      // mess with these values
      data.offsets.popper = getClientRect(data.offsets.popper);
      data.offsets.reference = getClientRect(data.offsets.reference);

      data = fn(data, modifier);
    }
  });

  return data;
}

/**
 * Updates the position of the popper, computing the new offsets and applying
 * the new style.<br />
 * Prefer `scheduleUpdate` over `update` because of performance reasons.
 * @method
 * @memberof Popper
 */
function update() {
  // if popper is destroyed, don't perform any further update
  if (this.state.isDestroyed) {
    return;
  }

  var data = {
    instance: this,
    styles: {},
    arrowStyles: {},
    attributes: {},
    flipped: false,
    offsets: {}
  };

  // compute reference element offsets
  data.offsets.reference = getReferenceOffsets(this.state, this.popper, this.reference, this.options.positionFixed);

  // compute auto placement, store placement inside the data object,
  // modifiers will be able to edit `placement` if needed
  // and refer to originalPlacement to know the original value
  data.placement = computeAutoPlacement(this.options.placement, data.offsets.reference, this.popper, this.reference, this.options.modifiers.flip.boundariesElement, this.options.modifiers.flip.padding);

  // store the computed placement inside `originalPlacement`
  data.originalPlacement = data.placement;

  data.positionFixed = this.options.positionFixed;

  // compute the popper offsets
  data.offsets.popper = getPopperOffsets(this.popper, data.offsets.reference, data.placement);

  data.offsets.popper.position = this.options.positionFixed ? 'fixed' : 'absolute';

  // run the modifiers
  data = runModifiers(this.modifiers, data);

  // the first `update` will call `onCreate` callback
  // the other ones will call `onUpdate` callback
  if (!this.state.isCreated) {
    this.state.isCreated = true;
    this.options.onCreate(data);
  } else {
    this.options.onUpdate(data);
  }
}

/**
 * Helper used to know if the given modifier is enabled.
 * @method
 * @memberof Popper.Utils
 * @returns {Boolean}
 */
function isModifierEnabled(modifiers, modifierName) {
  return modifiers.some(function (_ref) {
    var name = _ref.name,
        enabled = _ref.enabled;
    return enabled && name === modifierName;
  });
}

/**
 * Get the prefixed supported property name
 * @method
 * @memberof Popper.Utils
 * @argument {String} property (camelCase)
 * @returns {String} prefixed property (camelCase or PascalCase, depending on the vendor prefix)
 */
function getSupportedPropertyName(property) {
  var prefixes = [false, 'ms', 'Webkit', 'Moz', 'O'];
  var upperProp = property.charAt(0).toUpperCase() + property.slice(1);

  for (var i = 0; i < prefixes.length; i++) {
    var prefix = prefixes[i];
    var toCheck = prefix ? '' + prefix + upperProp : property;
    if (typeof document.body.style[toCheck] !== 'undefined') {
      return toCheck;
    }
  }
  return null;
}

/**
 * Destroys the popper.
 * @method
 * @memberof Popper
 */
function destroy() {
  this.state.isDestroyed = true;

  // touch DOM only if `applyStyle` modifier is enabled
  if (isModifierEnabled(this.modifiers, 'applyStyle')) {
    this.popper.removeAttribute('x-placement');
    this.popper.style.position = '';
    this.popper.style.top = '';
    this.popper.style.left = '';
    this.popper.style.right = '';
    this.popper.style.bottom = '';
    this.popper.style.willChange = '';
    this.popper.style[getSupportedPropertyName('transform')] = '';
  }

  this.disableEventListeners();

  // remove the popper if user explicitly asked for the deletion on destroy
  // do not use `remove` because IE11 doesn't support it
  if (this.options.removeOnDestroy) {
    this.popper.parentNode.removeChild(this.popper);
  }
  return this;
}

/**
 * Get the window associated with the element
 * @argument {Element} element
 * @returns {Window}
 */
function getWindow(element) {
  var ownerDocument = element.ownerDocument;
  return ownerDocument ? ownerDocument.defaultView : window;
}

function attachToScrollParents(scrollParent, event, callback, scrollParents) {
  var isBody = scrollParent.nodeName === 'BODY';
  var target = isBody ? scrollParent.ownerDocument.defaultView : scrollParent;
  target.addEventListener(event, callback, { passive: true });

  if (!isBody) {
    attachToScrollParents(getScrollParent(target.parentNode), event, callback, scrollParents);
  }
  scrollParents.push(target);
}

/**
 * Setup needed event listeners used to update the popper position
 * @method
 * @memberof Popper.Utils
 * @private
 */
function setupEventListeners(reference, options, state, updateBound) {
  // Resize event listener on window
  state.updateBound = updateBound;
  getWindow(reference).addEventListener('resize', state.updateBound, { passive: true });

  // Scroll event listener on scroll parents
  var scrollElement = getScrollParent(reference);
  attachToScrollParents(scrollElement, 'scroll', state.updateBound, state.scrollParents);
  state.scrollElement = scrollElement;
  state.eventsEnabled = true;

  return state;
}

/**
 * It will add resize/scroll events and start recalculating
 * position of the popper element when they are triggered.
 * @method
 * @memberof Popper
 */
function enableEventListeners() {
  if (!this.state.eventsEnabled) {
    this.state = setupEventListeners(this.reference, this.options, this.state, this.scheduleUpdate);
  }
}

/**
 * Remove event listeners used to update the popper position
 * @method
 * @memberof Popper.Utils
 * @private
 */
function removeEventListeners(reference, state) {
  // Remove resize event listener on window
  getWindow(reference).removeEventListener('resize', state.updateBound);

  // Remove scroll event listener on scroll parents
  state.scrollParents.forEach(function (target) {
    target.removeEventListener('scroll', state.updateBound);
  });

  // Reset state
  state.updateBound = null;
  state.scrollParents = [];
  state.scrollElement = null;
  state.eventsEnabled = false;
  return state;
}

/**
 * It will remove resize/scroll events and won't recalculate popper position
 * when they are triggered. It also won't trigger `onUpdate` callback anymore,
 * unless you call `update` method manually.
 * @method
 * @memberof Popper
 */
function disableEventListeners() {
  if (this.state.eventsEnabled) {
    cancelAnimationFrame(this.scheduleUpdate);
    this.state = removeEventListeners(this.reference, this.state);
  }
}

/**
 * Tells if a given input is a number
 * @method
 * @memberof Popper.Utils
 * @param {*} input to check
 * @return {Boolean}
 */
function isNumeric(n) {
  return n !== '' && !isNaN(parseFloat(n)) && isFinite(n);
}

/**
 * Set the style to the given popper
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element - Element to apply the style to
 * @argument {Object} styles
 * Object with a list of properties and values which will be applied to the element
 */
function setStyles(element, styles) {
  Object.keys(styles).forEach(function (prop) {
    var unit = '';
    // add unit if the value is numeric and is one of the following
    if (['width', 'height', 'top', 'right', 'bottom', 'left'].indexOf(prop) !== -1 && isNumeric(styles[prop])) {
      unit = 'px';
    }
    element.style[prop] = styles[prop] + unit;
  });
}

/**
 * Set the attributes to the given popper
 * @method
 * @memberof Popper.Utils
 * @argument {Element} element - Element to apply the attributes to
 * @argument {Object} styles
 * Object with a list of properties and values which will be applied to the element
 */
function setAttributes(element, attributes) {
  Object.keys(attributes).forEach(function (prop) {
    var value = attributes[prop];
    if (value !== false) {
      element.setAttribute(prop, attributes[prop]);
    } else {
      element.removeAttribute(prop);
    }
  });
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} data.styles - List of style properties - values to apply to popper element
 * @argument {Object} data.attributes - List of attribute properties - values to apply to popper element
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The same data object
 */
function applyStyle(data) {
  // any property present in `data.styles` will be applied to the popper,
  // in this way we can make the 3rd party modifiers add custom styles to it
  // Be aware, modifiers could override the properties defined in the previous
  // lines of this modifier!
  setStyles(data.instance.popper, data.styles);

  // any property present in `data.attributes` will be applied to the popper,
  // they will be set as HTML attributes of the element
  setAttributes(data.instance.popper, data.attributes);

  // if arrowElement is defined and arrowStyles has some properties
  if (data.arrowElement && Object.keys(data.arrowStyles).length) {
    setStyles(data.arrowElement, data.arrowStyles);
  }

  return data;
}

/**
 * Set the x-placement attribute before everything else because it could be used
 * to add margins to the popper margins needs to be calculated to get the
 * correct popper offsets.
 * @method
 * @memberof Popper.modifiers
 * @param {HTMLElement} reference - The reference element used to position the popper
 * @param {HTMLElement} popper - The HTML element used as popper
 * @param {Object} options - Popper.js options
 */
function applyStyleOnLoad(reference, popper, options, modifierOptions, state) {
  // compute reference element offsets
  var referenceOffsets = getReferenceOffsets(state, popper, reference, options.positionFixed);

  // compute auto placement, store placement inside the data object,
  // modifiers will be able to edit `placement` if needed
  // and refer to originalPlacement to know the original value
  var placement = computeAutoPlacement(options.placement, referenceOffsets, popper, reference, options.modifiers.flip.boundariesElement, options.modifiers.flip.padding);

  popper.setAttribute('x-placement', placement);

  // Apply `position` to popper before anything else because
  // without the position applied we can't guarantee correct computations
  setStyles(popper, { position: options.positionFixed ? 'fixed' : 'absolute' });

  return options;
}

/**
 * @function
 * @memberof Popper.Utils
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Boolean} shouldRound - If the offsets should be rounded at all
 * @returns {Object} The popper's position offsets rounded
 *
 * The tale of pixel-perfect positioning. It's still not 100% perfect, but as
 * good as it can be within reason.
 * Discussion here: https://github.com/FezVrasta/popper.js/pull/715
 *
 * Low DPI screens cause a popper to be blurry if not using full pixels (Safari
 * as well on High DPI screens).
 *
 * Firefox prefers no rounding for positioning and does not have blurriness on
 * high DPI screens.
 *
 * Only horizontal placement and left/right values need to be considered.
 */
function getRoundedOffsets(data, shouldRound) {
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;
  var round = Math.round,
      floor = Math.floor;

  var noRound = function noRound(v) {
    return v;
  };

  var referenceWidth = round(reference.width);
  var popperWidth = round(popper.width);

  var isVertical = ['left', 'right'].indexOf(data.placement) !== -1;
  var isVariation = data.placement.indexOf('-') !== -1;
  var sameWidthParity = referenceWidth % 2 === popperWidth % 2;
  var bothOddWidth = referenceWidth % 2 === 1 && popperWidth % 2 === 1;

  var horizontalToInteger = !shouldRound ? noRound : isVertical || isVariation || sameWidthParity ? round : floor;
  var verticalToInteger = !shouldRound ? noRound : round;

  return {
    left: horizontalToInteger(bothOddWidth && !isVariation && shouldRound ? popper.left - 1 : popper.left),
    top: verticalToInteger(popper.top),
    bottom: verticalToInteger(popper.bottom),
    right: horizontalToInteger(popper.right)
  };
}

var isFirefox = isBrowser && /Firefox/i.test(navigator.userAgent);

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function computeStyle(data, options) {
  var x = options.x,
      y = options.y;
  var popper = data.offsets.popper;

  // Remove this legacy support in Popper.js v2

  var legacyGpuAccelerationOption = find(data.instance.modifiers, function (modifier) {
    return modifier.name === 'applyStyle';
  }).gpuAcceleration;
  if (legacyGpuAccelerationOption !== undefined) {
    console.warn('WARNING: `gpuAcceleration` option moved to `computeStyle` modifier and will not be supported in future versions of Popper.js!');
  }
  var gpuAcceleration = legacyGpuAccelerationOption !== undefined ? legacyGpuAccelerationOption : options.gpuAcceleration;

  var offsetParent = getOffsetParent(data.instance.popper);
  var offsetParentRect = getBoundingClientRect(offsetParent);

  // Styles
  var styles = {
    position: popper.position
  };

  var offsets = getRoundedOffsets(data, window.devicePixelRatio < 2 || !isFirefox);

  var sideA = x === 'bottom' ? 'top' : 'bottom';
  var sideB = y === 'right' ? 'left' : 'right';

  // if gpuAcceleration is set to `true` and transform is supported,
  //  we use `translate3d` to apply the position to the popper we
  // automatically use the supported prefixed version if needed
  var prefixedProperty = getSupportedPropertyName('transform');

  // now, let's make a step back and look at this code closely (wtf?)
  // If the content of the popper grows once it's been positioned, it
  // may happen that the popper gets misplaced because of the new content
  // overflowing its reference element
  // To avoid this problem, we provide two options (x and y), which allow
  // the consumer to define the offset origin.
  // If we position a popper on top of a reference element, we can set
  // `x` to `top` to make the popper grow towards its top instead of
  // its bottom.
  var left = void 0,
      top = void 0;
  if (sideA === 'bottom') {
    // when offsetParent is <html> the positioning is relative to the bottom of the screen (excluding the scrollbar)
    // and not the bottom of the html element
    if (offsetParent.nodeName === 'HTML') {
      top = -offsetParent.clientHeight + offsets.bottom;
    } else {
      top = -offsetParentRect.height + offsets.bottom;
    }
  } else {
    top = offsets.top;
  }
  if (sideB === 'right') {
    if (offsetParent.nodeName === 'HTML') {
      left = -offsetParent.clientWidth + offsets.right;
    } else {
      left = -offsetParentRect.width + offsets.right;
    }
  } else {
    left = offsets.left;
  }
  if (gpuAcceleration && prefixedProperty) {
    styles[prefixedProperty] = 'translate3d(' + left + 'px, ' + top + 'px, 0)';
    styles[sideA] = 0;
    styles[sideB] = 0;
    styles.willChange = 'transform';
  } else {
    // othwerise, we use the standard `top`, `left`, `bottom` and `right` properties
    var invertTop = sideA === 'bottom' ? -1 : 1;
    var invertLeft = sideB === 'right' ? -1 : 1;
    styles[sideA] = top * invertTop;
    styles[sideB] = left * invertLeft;
    styles.willChange = sideA + ', ' + sideB;
  }

  // Attributes
  var attributes = {
    'x-placement': data.placement
  };

  // Update `data` attributes, styles and arrowStyles
  data.attributes = _extends({}, attributes, data.attributes);
  data.styles = _extends({}, styles, data.styles);
  data.arrowStyles = _extends({}, data.offsets.arrow, data.arrowStyles);

  return data;
}

/**
 * Helper used to know if the given modifier depends from another one.<br />
 * It checks if the needed modifier is listed and enabled.
 * @method
 * @memberof Popper.Utils
 * @param {Array} modifiers - list of modifiers
 * @param {String} requestingName - name of requesting modifier
 * @param {String} requestedName - name of requested modifier
 * @returns {Boolean}
 */
function isModifierRequired(modifiers, requestingName, requestedName) {
  var requesting = find(modifiers, function (_ref) {
    var name = _ref.name;
    return name === requestingName;
  });

  var isRequired = !!requesting && modifiers.some(function (modifier) {
    return modifier.name === requestedName && modifier.enabled && modifier.order < requesting.order;
  });

  if (!isRequired) {
    var _requesting = '`' + requestingName + '`';
    var requested = '`' + requestedName + '`';
    console.warn(requested + ' modifier is required by ' + _requesting + ' modifier in order to work, be sure to include it before ' + _requesting + '!');
  }
  return isRequired;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function arrow(data, options) {
  var _data$offsets$arrow;

  // arrow depends on keepTogether in order to work
  if (!isModifierRequired(data.instance.modifiers, 'arrow', 'keepTogether')) {
    return data;
  }

  var arrowElement = options.element;

  // if arrowElement is a string, suppose it's a CSS selector
  if (typeof arrowElement === 'string') {
    arrowElement = data.instance.popper.querySelector(arrowElement);

    // if arrowElement is not found, don't run the modifier
    if (!arrowElement) {
      return data;
    }
  } else {
    // if the arrowElement isn't a query selector we must check that the
    // provided DOM node is child of its popper node
    if (!data.instance.popper.contains(arrowElement)) {
      console.warn('WARNING: `arrow.element` must be child of its popper element!');
      return data;
    }
  }

  var placement = data.placement.split('-')[0];
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var isVertical = ['left', 'right'].indexOf(placement) !== -1;

  var len = isVertical ? 'height' : 'width';
  var sideCapitalized = isVertical ? 'Top' : 'Left';
  var side = sideCapitalized.toLowerCase();
  var altSide = isVertical ? 'left' : 'top';
  var opSide = isVertical ? 'bottom' : 'right';
  var arrowElementSize = getOuterSizes(arrowElement)[len];

  //
  // extends keepTogether behavior making sure the popper and its
  // reference have enough pixels in conjunction
  //

  // top/left side
  if (reference[opSide] - arrowElementSize < popper[side]) {
    data.offsets.popper[side] -= popper[side] - (reference[opSide] - arrowElementSize);
  }
  // bottom/right side
  if (reference[side] + arrowElementSize > popper[opSide]) {
    data.offsets.popper[side] += reference[side] + arrowElementSize - popper[opSide];
  }
  data.offsets.popper = getClientRect(data.offsets.popper);

  // compute center of the popper
  var center = reference[side] + reference[len] / 2 - arrowElementSize / 2;

  // Compute the sideValue using the updated popper offsets
  // take popper margin in account because we don't have this info available
  var css = getStyleComputedProperty(data.instance.popper);
  var popperMarginSide = parseFloat(css['margin' + sideCapitalized]);
  var popperBorderSide = parseFloat(css['border' + sideCapitalized + 'Width']);
  var sideValue = center - data.offsets.popper[side] - popperMarginSide - popperBorderSide;

  // prevent arrowElement from being placed not contiguously to its popper
  sideValue = Math.max(Math.min(popper[len] - arrowElementSize, sideValue), 0);

  data.arrowElement = arrowElement;
  data.offsets.arrow = (_data$offsets$arrow = {}, defineProperty(_data$offsets$arrow, side, Math.round(sideValue)), defineProperty(_data$offsets$arrow, altSide, ''), _data$offsets$arrow);

  return data;
}

/**
 * Get the opposite placement variation of the given one
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement variation
 * @returns {String} flipped placement variation
 */
function getOppositeVariation(variation) {
  if (variation === 'end') {
    return 'start';
  } else if (variation === 'start') {
    return 'end';
  }
  return variation;
}

/**
 * List of accepted placements to use as values of the `placement` option.<br />
 * Valid placements are:
 * - `auto`
 * - `top`
 * - `right`
 * - `bottom`
 * - `left`
 *
 * Each placement can have a variation from this list:
 * - `-start`
 * - `-end`
 *
 * Variations are interpreted easily if you think of them as the left to right
 * written languages. Horizontally (`top` and `bottom`), `start` is left and `end`
 * is right.<br />
 * Vertically (`left` and `right`), `start` is top and `end` is bottom.
 *
 * Some valid examples are:
 * - `top-end` (on top of reference, right aligned)
 * - `right-start` (on right of reference, top aligned)
 * - `bottom` (on bottom, centered)
 * - `auto-end` (on the side with more space available, alignment depends by placement)
 *
 * @static
 * @type {Array}
 * @enum {String}
 * @readonly
 * @method placements
 * @memberof Popper
 */
var placements = ['auto-start', 'auto', 'auto-end', 'top-start', 'top', 'top-end', 'right-start', 'right', 'right-end', 'bottom-end', 'bottom', 'bottom-start', 'left-end', 'left', 'left-start'];

// Get rid of `auto` `auto-start` and `auto-end`
var validPlacements = placements.slice(3);

/**
 * Given an initial placement, returns all the subsequent placements
 * clockwise (or counter-clockwise).
 *
 * @method
 * @memberof Popper.Utils
 * @argument {String} placement - A valid placement (it accepts variations)
 * @argument {Boolean} counter - Set to true to walk the placements counterclockwise
 * @returns {Array} placements including their variations
 */
function clockwise(placement) {
  var counter = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  var index = validPlacements.indexOf(placement);
  var arr = validPlacements.slice(index + 1).concat(validPlacements.slice(0, index));
  return counter ? arr.reverse() : arr;
}

var BEHAVIORS = {
  FLIP: 'flip',
  CLOCKWISE: 'clockwise',
  COUNTERCLOCKWISE: 'counterclockwise'
};

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function flip(data, options) {
  // if `inner` modifier is enabled, we can't use the `flip` modifier
  if (isModifierEnabled(data.instance.modifiers, 'inner')) {
    return data;
  }

  if (data.flipped && data.placement === data.originalPlacement) {
    // seems like flip is trying to loop, probably there's not enough space on any of the flippable sides
    return data;
  }

  var boundaries = getBoundaries(data.instance.popper, data.instance.reference, options.padding, options.boundariesElement, data.positionFixed);

  var placement = data.placement.split('-')[0];
  var placementOpposite = getOppositePlacement(placement);
  var variation = data.placement.split('-')[1] || '';

  var flipOrder = [];

  switch (options.behavior) {
    case BEHAVIORS.FLIP:
      flipOrder = [placement, placementOpposite];
      break;
    case BEHAVIORS.CLOCKWISE:
      flipOrder = clockwise(placement);
      break;
    case BEHAVIORS.COUNTERCLOCKWISE:
      flipOrder = clockwise(placement, true);
      break;
    default:
      flipOrder = options.behavior;
  }

  flipOrder.forEach(function (step, index) {
    if (placement !== step || flipOrder.length === index + 1) {
      return data;
    }

    placement = data.placement.split('-')[0];
    placementOpposite = getOppositePlacement(placement);

    var popperOffsets = data.offsets.popper;
    var refOffsets = data.offsets.reference;

    // using floor because the reference offsets may contain decimals we are not going to consider here
    var floor = Math.floor;
    var overlapsRef = placement === 'left' && floor(popperOffsets.right) > floor(refOffsets.left) || placement === 'right' && floor(popperOffsets.left) < floor(refOffsets.right) || placement === 'top' && floor(popperOffsets.bottom) > floor(refOffsets.top) || placement === 'bottom' && floor(popperOffsets.top) < floor(refOffsets.bottom);

    var overflowsLeft = floor(popperOffsets.left) < floor(boundaries.left);
    var overflowsRight = floor(popperOffsets.right) > floor(boundaries.right);
    var overflowsTop = floor(popperOffsets.top) < floor(boundaries.top);
    var overflowsBottom = floor(popperOffsets.bottom) > floor(boundaries.bottom);

    var overflowsBoundaries = placement === 'left' && overflowsLeft || placement === 'right' && overflowsRight || placement === 'top' && overflowsTop || placement === 'bottom' && overflowsBottom;

    // flip the variation if required
    var isVertical = ['top', 'bottom'].indexOf(placement) !== -1;

    // flips variation if reference element overflows boundaries
    var flippedVariationByRef = !!options.flipVariations && (isVertical && variation === 'start' && overflowsLeft || isVertical && variation === 'end' && overflowsRight || !isVertical && variation === 'start' && overflowsTop || !isVertical && variation === 'end' && overflowsBottom);

    // flips variation if popper content overflows boundaries
    var flippedVariationByContent = !!options.flipVariationsByContent && (isVertical && variation === 'start' && overflowsRight || isVertical && variation === 'end' && overflowsLeft || !isVertical && variation === 'start' && overflowsBottom || !isVertical && variation === 'end' && overflowsTop);

    var flippedVariation = flippedVariationByRef || flippedVariationByContent;

    if (overlapsRef || overflowsBoundaries || flippedVariation) {
      // this boolean to detect any flip loop
      data.flipped = true;

      if (overlapsRef || overflowsBoundaries) {
        placement = flipOrder[index + 1];
      }

      if (flippedVariation) {
        variation = getOppositeVariation(variation);
      }

      data.placement = placement + (variation ? '-' + variation : '');

      // this object contains `position`, we want to preserve it along with
      // any additional property we may add in the future
      data.offsets.popper = _extends({}, data.offsets.popper, getPopperOffsets(data.instance.popper, data.offsets.reference, data.placement));

      data = runModifiers(data.instance.modifiers, data, 'flip');
    }
  });
  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function keepTogether(data) {
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var placement = data.placement.split('-')[0];
  var floor = Math.floor;
  var isVertical = ['top', 'bottom'].indexOf(placement) !== -1;
  var side = isVertical ? 'right' : 'bottom';
  var opSide = isVertical ? 'left' : 'top';
  var measurement = isVertical ? 'width' : 'height';

  if (popper[side] < floor(reference[opSide])) {
    data.offsets.popper[opSide] = floor(reference[opSide]) - popper[measurement];
  }
  if (popper[opSide] > floor(reference[side])) {
    data.offsets.popper[opSide] = floor(reference[side]);
  }

  return data;
}

/**
 * Converts a string containing value + unit into a px value number
 * @function
 * @memberof {modifiers~offset}
 * @private
 * @argument {String} str - Value + unit string
 * @argument {String} measurement - `height` or `width`
 * @argument {Object} popperOffsets
 * @argument {Object} referenceOffsets
 * @returns {Number|String}
 * Value in pixels, or original string if no values were extracted
 */
function toValue(str, measurement, popperOffsets, referenceOffsets) {
  // separate value from unit
  var split = str.match(/((?:\-|\+)?\d*\.?\d*)(.*)/);
  var value = +split[1];
  var unit = split[2];

  // If it's not a number it's an operator, I guess
  if (!value) {
    return str;
  }

  if (unit.indexOf('%') === 0) {
    var element = void 0;
    switch (unit) {
      case '%p':
        element = popperOffsets;
        break;
      case '%':
      case '%r':
      default:
        element = referenceOffsets;
    }

    var rect = getClientRect(element);
    return rect[measurement] / 100 * value;
  } else if (unit === 'vh' || unit === 'vw') {
    // if is a vh or vw, we calculate the size based on the viewport
    var size = void 0;
    if (unit === 'vh') {
      size = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
    } else {
      size = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
    }
    return size / 100 * value;
  } else {
    // if is an explicit pixel unit, we get rid of the unit and keep the value
    // if is an implicit unit, it's px, and we return just the value
    return value;
  }
}

/**
 * Parse an `offset` string to extrapolate `x` and `y` numeric offsets.
 * @function
 * @memberof {modifiers~offset}
 * @private
 * @argument {String} offset
 * @argument {Object} popperOffsets
 * @argument {Object} referenceOffsets
 * @argument {String} basePlacement
 * @returns {Array} a two cells array with x and y offsets in numbers
 */
function parseOffset(offset, popperOffsets, referenceOffsets, basePlacement) {
  var offsets = [0, 0];

  // Use height if placement is left or right and index is 0 otherwise use width
  // in this way the first offset will use an axis and the second one
  // will use the other one
  var useHeight = ['right', 'left'].indexOf(basePlacement) !== -1;

  // Split the offset string to obtain a list of values and operands
  // The regex addresses values with the plus or minus sign in front (+10, -20, etc)
  var fragments = offset.split(/(\+|\-)/).map(function (frag) {
    return frag.trim();
  });

  // Detect if the offset string contains a pair of values or a single one
  // they could be separated by comma or space
  var divider = fragments.indexOf(find(fragments, function (frag) {
    return frag.search(/,|\s/) !== -1;
  }));

  if (fragments[divider] && fragments[divider].indexOf(',') === -1) {
    console.warn('Offsets separated by white space(s) are deprecated, use a comma (,) instead.');
  }

  // If divider is found, we divide the list of values and operands to divide
  // them by ofset X and Y.
  var splitRegex = /\s*,\s*|\s+/;
  var ops = divider !== -1 ? [fragments.slice(0, divider).concat([fragments[divider].split(splitRegex)[0]]), [fragments[divider].split(splitRegex)[1]].concat(fragments.slice(divider + 1))] : [fragments];

  // Convert the values with units to absolute pixels to allow our computations
  ops = ops.map(function (op, index) {
    // Most of the units rely on the orientation of the popper
    var measurement = (index === 1 ? !useHeight : useHeight) ? 'height' : 'width';
    var mergeWithPrevious = false;
    return op
    // This aggregates any `+` or `-` sign that aren't considered operators
    // e.g.: 10 + +5 => [10, +, +5]
    .reduce(function (a, b) {
      if (a[a.length - 1] === '' && ['+', '-'].indexOf(b) !== -1) {
        a[a.length - 1] = b;
        mergeWithPrevious = true;
        return a;
      } else if (mergeWithPrevious) {
        a[a.length - 1] += b;
        mergeWithPrevious = false;
        return a;
      } else {
        return a.concat(b);
      }
    }, [])
    // Here we convert the string values into number values (in px)
    .map(function (str) {
      return toValue(str, measurement, popperOffsets, referenceOffsets);
    });
  });

  // Loop trough the offsets arrays and execute the operations
  ops.forEach(function (op, index) {
    op.forEach(function (frag, index2) {
      if (isNumeric(frag)) {
        offsets[index] += frag * (op[index2 - 1] === '-' ? -1 : 1);
      }
    });
  });
  return offsets;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @argument {Number|String} options.offset=0
 * The offset value as described in the modifier description
 * @returns {Object} The data object, properly modified
 */
function offset(data, _ref) {
  var offset = _ref.offset;
  var placement = data.placement,
      _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var basePlacement = placement.split('-')[0];

  var offsets = void 0;
  if (isNumeric(+offset)) {
    offsets = [+offset, 0];
  } else {
    offsets = parseOffset(offset, popper, reference, basePlacement);
  }

  if (basePlacement === 'left') {
    popper.top += offsets[0];
    popper.left -= offsets[1];
  } else if (basePlacement === 'right') {
    popper.top += offsets[0];
    popper.left += offsets[1];
  } else if (basePlacement === 'top') {
    popper.left += offsets[0];
    popper.top -= offsets[1];
  } else if (basePlacement === 'bottom') {
    popper.left += offsets[0];
    popper.top += offsets[1];
  }

  data.popper = popper;
  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function preventOverflow(data, options) {
  var boundariesElement = options.boundariesElement || getOffsetParent(data.instance.popper);

  // If offsetParent is the reference element, we really want to
  // go one step up and use the next offsetParent as reference to
  // avoid to make this modifier completely useless and look like broken
  if (data.instance.reference === boundariesElement) {
    boundariesElement = getOffsetParent(boundariesElement);
  }

  // NOTE: DOM access here
  // resets the popper's position so that the document size can be calculated excluding
  // the size of the popper element itself
  var transformProp = getSupportedPropertyName('transform');
  var popperStyles = data.instance.popper.style; // assignment to help minification
  var top = popperStyles.top,
      left = popperStyles.left,
      transform = popperStyles[transformProp];

  popperStyles.top = '';
  popperStyles.left = '';
  popperStyles[transformProp] = '';

  var boundaries = getBoundaries(data.instance.popper, data.instance.reference, options.padding, boundariesElement, data.positionFixed);

  // NOTE: DOM access here
  // restores the original style properties after the offsets have been computed
  popperStyles.top = top;
  popperStyles.left = left;
  popperStyles[transformProp] = transform;

  options.boundaries = boundaries;

  var order = options.priority;
  var popper = data.offsets.popper;

  var check = {
    primary: function primary(placement) {
      var value = popper[placement];
      if (popper[placement] < boundaries[placement] && !options.escapeWithReference) {
        value = Math.max(popper[placement], boundaries[placement]);
      }
      return defineProperty({}, placement, value);
    },
    secondary: function secondary(placement) {
      var mainSide = placement === 'right' ? 'left' : 'top';
      var value = popper[mainSide];
      if (popper[placement] > boundaries[placement] && !options.escapeWithReference) {
        value = Math.min(popper[mainSide], boundaries[placement] - (placement === 'right' ? popper.width : popper.height));
      }
      return defineProperty({}, mainSide, value);
    }
  };

  order.forEach(function (placement) {
    var side = ['left', 'top'].indexOf(placement) !== -1 ? 'primary' : 'secondary';
    popper = _extends({}, popper, check[side](placement));
  });

  data.offsets.popper = popper;

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function shift(data) {
  var placement = data.placement;
  var basePlacement = placement.split('-')[0];
  var shiftvariation = placement.split('-')[1];

  // if shift shiftvariation is specified, run the modifier
  if (shiftvariation) {
    var _data$offsets = data.offsets,
        reference = _data$offsets.reference,
        popper = _data$offsets.popper;

    var isVertical = ['bottom', 'top'].indexOf(basePlacement) !== -1;
    var side = isVertical ? 'left' : 'top';
    var measurement = isVertical ? 'width' : 'height';

    var shiftOffsets = {
      start: defineProperty({}, side, reference[side]),
      end: defineProperty({}, side, reference[side] + reference[measurement] - popper[measurement])
    };

    data.offsets.popper = _extends({}, popper, shiftOffsets[shiftvariation]);
  }

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by update method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function hide(data) {
  if (!isModifierRequired(data.instance.modifiers, 'hide', 'preventOverflow')) {
    return data;
  }

  var refRect = data.offsets.reference;
  var bound = find(data.instance.modifiers, function (modifier) {
    return modifier.name === 'preventOverflow';
  }).boundaries;

  if (refRect.bottom < bound.top || refRect.left > bound.right || refRect.top > bound.bottom || refRect.right < bound.left) {
    // Avoid unnecessary DOM access if visibility hasn't changed
    if (data.hide === true) {
      return data;
    }

    data.hide = true;
    data.attributes['x-out-of-boundaries'] = '';
  } else {
    // Avoid unnecessary DOM access if visibility hasn't changed
    if (data.hide === false) {
      return data;
    }

    data.hide = false;
    data.attributes['x-out-of-boundaries'] = false;
  }

  return data;
}

/**
 * @function
 * @memberof Modifiers
 * @argument {Object} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {Object} The data object, properly modified
 */
function inner(data) {
  var placement = data.placement;
  var basePlacement = placement.split('-')[0];
  var _data$offsets = data.offsets,
      popper = _data$offsets.popper,
      reference = _data$offsets.reference;

  var isHoriz = ['left', 'right'].indexOf(basePlacement) !== -1;

  var subtractLength = ['top', 'left'].indexOf(basePlacement) === -1;

  popper[isHoriz ? 'left' : 'top'] = reference[basePlacement] - (subtractLength ? popper[isHoriz ? 'width' : 'height'] : 0);

  data.placement = getOppositePlacement(placement);
  data.offsets.popper = getClientRect(popper);

  return data;
}

/**
 * Modifier function, each modifier can have a function of this type assigned
 * to its `fn` property.<br />
 * These functions will be called on each update, this means that you must
 * make sure they are performant enough to avoid performance bottlenecks.
 *
 * @function ModifierFn
 * @argument {dataObject} data - The data object generated by `update` method
 * @argument {Object} options - Modifiers configuration and options
 * @returns {dataObject} The data object, properly modified
 */

/**
 * Modifiers are plugins used to alter the behavior of your poppers.<br />
 * Popper.js uses a set of 9 modifiers to provide all the basic functionalities
 * needed by the library.
 *
 * Usually you don't want to override the `order`, `fn` and `onLoad` props.
 * All the other properties are configurations that could be tweaked.
 * @namespace modifiers
 */
var modifiers = {
  /**
   * Modifier used to shift the popper on the start or end of its reference
   * element.<br />
   * It will read the variation of the `placement` property.<br />
   * It can be one either `-end` or `-start`.
   * @memberof modifiers
   * @inner
   */
  shift: {
    /** @prop {number} order=100 - Index used to define the order of execution */
    order: 100,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: shift
  },

  /**
   * The `offset` modifier can shift your popper on both its axis.
   *
   * It accepts the following units:
   * - `px` or unit-less, interpreted as pixels
   * - `%` or `%r`, percentage relative to the length of the reference element
   * - `%p`, percentage relative to the length of the popper element
   * - `vw`, CSS viewport width unit
   * - `vh`, CSS viewport height unit
   *
   * For length is intended the main axis relative to the placement of the popper.<br />
   * This means that if the placement is `top` or `bottom`, the length will be the
   * `width`. In case of `left` or `right`, it will be the `height`.
   *
   * You can provide a single value (as `Number` or `String`), or a pair of values
   * as `String` divided by a comma or one (or more) white spaces.<br />
   * The latter is a deprecated method because it leads to confusion and will be
   * removed in v2.<br />
   * Additionally, it accepts additions and subtractions between different units.
   * Note that multiplications and divisions aren't supported.
   *
   * Valid examples are:
   * ```
   * 10
   * '10%'
   * '10, 10'
   * '10%, 10'
   * '10 + 10%'
   * '10 - 5vh + 3%'
   * '-10px + 5vh, 5px - 6%'
   * ```
   * > **NB**: If you desire to apply offsets to your poppers in a way that may make them overlap
   * > with their reference element, unfortunately, you will have to disable the `flip` modifier.
   * > You can read more on this at this [issue](https://github.com/FezVrasta/popper.js/issues/373).
   *
   * @memberof modifiers
   * @inner
   */
  offset: {
    /** @prop {number} order=200 - Index used to define the order of execution */
    order: 200,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: offset,
    /** @prop {Number|String} offset=0
     * The offset value as described in the modifier description
     */
    offset: 0
  },

  /**
   * Modifier used to prevent the popper from being positioned outside the boundary.
   *
   * A scenario exists where the reference itself is not within the boundaries.<br />
   * We can say it has "escaped the boundaries" — or just "escaped".<br />
   * In this case we need to decide whether the popper should either:
   *
   * - detach from the reference and remain "trapped" in the boundaries, or
   * - if it should ignore the boundary and "escape with its reference"
   *
   * When `escapeWithReference` is set to`true` and reference is completely
   * outside its boundaries, the popper will overflow (or completely leave)
   * the boundaries in order to remain attached to the edge of the reference.
   *
   * @memberof modifiers
   * @inner
   */
  preventOverflow: {
    /** @prop {number} order=300 - Index used to define the order of execution */
    order: 300,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: preventOverflow,
    /**
     * @prop {Array} [priority=['left','right','top','bottom']]
     * Popper will try to prevent overflow following these priorities by default,
     * then, it could overflow on the left and on top of the `boundariesElement`
     */
    priority: ['left', 'right', 'top', 'bottom'],
    /**
     * @prop {number} padding=5
     * Amount of pixel used to define a minimum distance between the boundaries
     * and the popper. This makes sure the popper always has a little padding
     * between the edges of its container
     */
    padding: 5,
    /**
     * @prop {String|HTMLElement} boundariesElement='scrollParent'
     * Boundaries used by the modifier. Can be `scrollParent`, `window`,
     * `viewport` or any DOM element.
     */
    boundariesElement: 'scrollParent'
  },

  /**
   * Modifier used to make sure the reference and its popper stay near each other
   * without leaving any gap between the two. Especially useful when the arrow is
   * enabled and you want to ensure that it points to its reference element.
   * It cares only about the first axis. You can still have poppers with margin
   * between the popper and its reference element.
   * @memberof modifiers
   * @inner
   */
  keepTogether: {
    /** @prop {number} order=400 - Index used to define the order of execution */
    order: 400,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: keepTogether
  },

  /**
   * This modifier is used to move the `arrowElement` of the popper to make
   * sure it is positioned between the reference element and its popper element.
   * It will read the outer size of the `arrowElement` node to detect how many
   * pixels of conjunction are needed.
   *
   * It has no effect if no `arrowElement` is provided.
   * @memberof modifiers
   * @inner
   */
  arrow: {
    /** @prop {number} order=500 - Index used to define the order of execution */
    order: 500,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: arrow,
    /** @prop {String|HTMLElement} element='[x-arrow]' - Selector or node used as arrow */
    element: '[x-arrow]'
  },

  /**
   * Modifier used to flip the popper's placement when it starts to overlap its
   * reference element.
   *
   * Requires the `preventOverflow` modifier before it in order to work.
   *
   * **NOTE:** this modifier will interrupt the current update cycle and will
   * restart it if it detects the need to flip the placement.
   * @memberof modifiers
   * @inner
   */
  flip: {
    /** @prop {number} order=600 - Index used to define the order of execution */
    order: 600,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: flip,
    /**
     * @prop {String|Array} behavior='flip'
     * The behavior used to change the popper's placement. It can be one of
     * `flip`, `clockwise`, `counterclockwise` or an array with a list of valid
     * placements (with optional variations)
     */
    behavior: 'flip',
    /**
     * @prop {number} padding=5
     * The popper will flip if it hits the edges of the `boundariesElement`
     */
    padding: 5,
    /**
     * @prop {String|HTMLElement} boundariesElement='viewport'
     * The element which will define the boundaries of the popper position.
     * The popper will never be placed outside of the defined boundaries
     * (except if `keepTogether` is enabled)
     */
    boundariesElement: 'viewport',
    /**
     * @prop {Boolean} flipVariations=false
     * The popper will switch placement variation between `-start` and `-end` when
     * the reference element overlaps its boundaries.
     *
     * The original placement should have a set variation.
     */
    flipVariations: false,
    /**
     * @prop {Boolean} flipVariationsByContent=false
     * The popper will switch placement variation between `-start` and `-end` when
     * the popper element overlaps its reference boundaries.
     *
     * The original placement should have a set variation.
     */
    flipVariationsByContent: false
  },

  /**
   * Modifier used to make the popper flow toward the inner of the reference element.
   * By default, when this modifier is disabled, the popper will be placed outside
   * the reference element.
   * @memberof modifiers
   * @inner
   */
  inner: {
    /** @prop {number} order=700 - Index used to define the order of execution */
    order: 700,
    /** @prop {Boolean} enabled=false - Whether the modifier is enabled or not */
    enabled: false,
    /** @prop {ModifierFn} */
    fn: inner
  },

  /**
   * Modifier used to hide the popper when its reference element is outside of the
   * popper boundaries. It will set a `x-out-of-boundaries` attribute which can
   * be used to hide with a CSS selector the popper when its reference is
   * out of boundaries.
   *
   * Requires the `preventOverflow` modifier before it in order to work.
   * @memberof modifiers
   * @inner
   */
  hide: {
    /** @prop {number} order=800 - Index used to define the order of execution */
    order: 800,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: hide
  },

  /**
   * Computes the style that will be applied to the popper element to gets
   * properly positioned.
   *
   * Note that this modifier will not touch the DOM, it just prepares the styles
   * so that `applyStyle` modifier can apply it. This separation is useful
   * in case you need to replace `applyStyle` with a custom implementation.
   *
   * This modifier has `850` as `order` value to maintain backward compatibility
   * with previous versions of Popper.js. Expect the modifiers ordering method
   * to change in future major versions of the library.
   *
   * @memberof modifiers
   * @inner
   */
  computeStyle: {
    /** @prop {number} order=850 - Index used to define the order of execution */
    order: 850,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: computeStyle,
    /**
     * @prop {Boolean} gpuAcceleration=true
     * If true, it uses the CSS 3D transformation to position the popper.
     * Otherwise, it will use the `top` and `left` properties
     */
    gpuAcceleration: true,
    /**
     * @prop {string} [x='bottom']
     * Where to anchor the X axis (`bottom` or `top`). AKA X offset origin.
     * Change this if your popper should grow in a direction different from `bottom`
     */
    x: 'bottom',
    /**
     * @prop {string} [x='left']
     * Where to anchor the Y axis (`left` or `right`). AKA Y offset origin.
     * Change this if your popper should grow in a direction different from `right`
     */
    y: 'right'
  },

  /**
   * Applies the computed styles to the popper element.
   *
   * All the DOM manipulations are limited to this modifier. This is useful in case
   * you want to integrate Popper.js inside a framework or view library and you
   * want to delegate all the DOM manipulations to it.
   *
   * Note that if you disable this modifier, you must make sure the popper element
   * has its position set to `absolute` before Popper.js can do its work!
   *
   * Just disable this modifier and define your own to achieve the desired effect.
   *
   * @memberof modifiers
   * @inner
   */
  applyStyle: {
    /** @prop {number} order=900 - Index used to define the order of execution */
    order: 900,
    /** @prop {Boolean} enabled=true - Whether the modifier is enabled or not */
    enabled: true,
    /** @prop {ModifierFn} */
    fn: applyStyle,
    /** @prop {Function} */
    onLoad: applyStyleOnLoad,
    /**
     * @deprecated since version 1.10.0, the property moved to `computeStyle` modifier
     * @prop {Boolean} gpuAcceleration=true
     * If true, it uses the CSS 3D transformation to position the popper.
     * Otherwise, it will use the `top` and `left` properties
     */
    gpuAcceleration: undefined
  }
};

/**
 * The `dataObject` is an object containing all the information used by Popper.js.
 * This object is passed to modifiers and to the `onCreate` and `onUpdate` callbacks.
 * @name dataObject
 * @property {Object} data.instance The Popper.js instance
 * @property {String} data.placement Placement applied to popper
 * @property {String} data.originalPlacement Placement originally defined on init
 * @property {Boolean} data.flipped True if popper has been flipped by flip modifier
 * @property {Boolean} data.hide True if the reference element is out of boundaries, useful to know when to hide the popper
 * @property {HTMLElement} data.arrowElement Node used as arrow by arrow modifier
 * @property {Object} data.styles Any CSS property defined here will be applied to the popper. It expects the JavaScript nomenclature (eg. `marginBottom`)
 * @property {Object} data.arrowStyles Any CSS property defined here will be applied to the popper arrow. It expects the JavaScript nomenclature (eg. `marginBottom`)
 * @property {Object} data.boundaries Offsets of the popper boundaries
 * @property {Object} data.offsets The measurements of popper, reference and arrow elements
 * @property {Object} data.offsets.popper `top`, `left`, `width`, `height` values
 * @property {Object} data.offsets.reference `top`, `left`, `width`, `height` values
 * @property {Object} data.offsets.arrow] `top` and `left` offsets, only one of them will be different from 0
 */

/**
 * Default options provided to Popper.js constructor.<br />
 * These can be overridden using the `options` argument of Popper.js.<br />
 * To override an option, simply pass an object with the same
 * structure of the `options` object, as the 3rd argument. For example:
 * ```
 * new Popper(ref, pop, {
 *   modifiers: {
 *     preventOverflow: { enabled: false }
 *   }
 * })
 * ```
 * @type {Object}
 * @static
 * @memberof Popper
 */
var Defaults = {
  /**
   * Popper's placement.
   * @prop {Popper.placements} placement='bottom'
   */
  placement: 'bottom',

  /**
   * Set this to true if you want popper to position it self in 'fixed' mode
   * @prop {Boolean} positionFixed=false
   */
  positionFixed: false,

  /**
   * Whether events (resize, scroll) are initially enabled.
   * @prop {Boolean} eventsEnabled=true
   */
  eventsEnabled: true,

  /**
   * Set to true if you want to automatically remove the popper when
   * you call the `destroy` method.
   * @prop {Boolean} removeOnDestroy=false
   */
  removeOnDestroy: false,

  /**
   * Callback called when the popper is created.<br />
   * By default, it is set to no-op.<br />
   * Access Popper.js instance with `data.instance`.
   * @prop {onCreate}
   */
  onCreate: function onCreate() {},

  /**
   * Callback called when the popper is updated. This callback is not called
   * on the initialization/creation of the popper, but only on subsequent
   * updates.<br />
   * By default, it is set to no-op.<br />
   * Access Popper.js instance with `data.instance`.
   * @prop {onUpdate}
   */
  onUpdate: function onUpdate() {},

  /**
   * List of modifiers used to modify the offsets before they are applied to the popper.
   * They provide most of the functionalities of Popper.js.
   * @prop {modifiers}
   */
  modifiers: modifiers
};

/**
 * @callback onCreate
 * @param {dataObject} data
 */

/**
 * @callback onUpdate
 * @param {dataObject} data
 */

// Utils
// Methods
var Popper = function () {
  /**
   * Creates a new Popper.js instance.
   * @class Popper
   * @param {Element|referenceObject} reference - The reference element used to position the popper
   * @param {Element} popper - The HTML / XML element used as the popper
   * @param {Object} options - Your custom options to override the ones defined in [Defaults](#defaults)
   * @return {Object} instance - The generated Popper.js instance
   */
  function Popper(reference, popper) {
    var _this = this;

    var options = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    classCallCheck(this, Popper);

    this.scheduleUpdate = function () {
      return requestAnimationFrame(_this.update);
    };

    // make update() debounced, so that it only runs at most once-per-tick
    this.update = debounce(this.update.bind(this));

    // with {} we create a new object with the options inside it
    this.options = _extends({}, Popper.Defaults, options);

    // init state
    this.state = {
      isDestroyed: false,
      isCreated: false,
      scrollParents: []
    };

    // get reference and popper elements (allow jQuery wrappers)
    this.reference = reference && reference.jquery ? reference[0] : reference;
    this.popper = popper && popper.jquery ? popper[0] : popper;

    // Deep merge modifiers options
    this.options.modifiers = {};
    Object.keys(_extends({}, Popper.Defaults.modifiers, options.modifiers)).forEach(function (name) {
      _this.options.modifiers[name] = _extends({}, Popper.Defaults.modifiers[name] || {}, options.modifiers ? options.modifiers[name] : {});
    });

    // Refactoring modifiers' list (Object => Array)
    this.modifiers = Object.keys(this.options.modifiers).map(function (name) {
      return _extends({
        name: name
      }, _this.options.modifiers[name]);
    })
    // sort the modifiers by order
    .sort(function (a, b) {
      return a.order - b.order;
    });

    // modifiers have the ability to execute arbitrary code when Popper.js get inited
    // such code is executed in the same order of its modifier
    // they could add new properties to their options configuration
    // BE AWARE: don't add options to `options.modifiers.name` but to `modifierOptions`!
    this.modifiers.forEach(function (modifierOptions) {
      if (modifierOptions.enabled && isFunction(modifierOptions.onLoad)) {
        modifierOptions.onLoad(_this.reference, _this.popper, _this.options, modifierOptions, _this.state);
      }
    });

    // fire the first update to position the popper in the right place
    this.update();

    var eventsEnabled = this.options.eventsEnabled;
    if (eventsEnabled) {
      // setup event listeners, they will take care of update the position in specific situations
      this.enableEventListeners();
    }

    this.state.eventsEnabled = eventsEnabled;
  }

  // We can't use class properties because they don't get listed in the
  // class prototype and break stuff like Sinon stubs


  createClass(Popper, [{
    key: 'update',
    value: function update$$1() {
      return update.call(this);
    }
  }, {
    key: 'destroy',
    value: function destroy$$1() {
      return destroy.call(this);
    }
  }, {
    key: 'enableEventListeners',
    value: function enableEventListeners$$1() {
      return enableEventListeners.call(this);
    }
  }, {
    key: 'disableEventListeners',
    value: function disableEventListeners$$1() {
      return disableEventListeners.call(this);
    }

    /**
     * Schedules an update. It will run on the next UI update available.
     * @method scheduleUpdate
     * @memberof Popper
     */


    /**
     * Collection of utilities useful when writing custom modifiers.
     * Starting from version 1.7, this method is available only if you
     * include `popper-utils.js` before `popper.js`.
     *
     * **DEPRECATION**: This way to access PopperUtils is deprecated
     * and will be removed in v2! Use the PopperUtils module directly instead.
     * Due to the high instability of the methods contained in Utils, we can't
     * guarantee them to follow semver. Use them at your own risk!
     * @static
     * @private
     * @type {Object}
     * @deprecated since version 1.8
     * @member Utils
     * @memberof Popper
     */

  }]);
  return Popper;
}();

/**
 * The `referenceObject` is an object that provides an interface compatible with Popper.js
 * and lets you use it as replacement of a real DOM node.<br />
 * You can use this method to position a popper relatively to a set of coordinates
 * in case you don't have a DOM node to use as reference.
 *
 * ```
 * new Popper(referenceObject, popperNode);
 * ```
 *
 * NB: This feature isn't supported in Internet Explorer 10.
 * @name referenceObject
 * @property {Function} data.getBoundingClientRect
 * A function that returns a set of coordinates compatible with the native `getBoundingClientRect` method.
 * @property {number} data.clientWidth
 * An ES6 getter that will return the width of the virtual reference element.
 * @property {number} data.clientHeight
 * An ES6 getter that will return the height of the virtual reference element.
 */


Popper.Utils = (typeof window !== 'undefined' ? window : global).PopperUtils;
Popper.placements = placements;
Popper.Defaults = Defaults;

/* harmony default export */ __webpack_exports__["default"] = (Popper);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/process/browser.js":
/*!*****************************************!*\
  !*** ./node_modules/process/browser.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),

/***/ "./node_modules/setimmediate/setImmediate.js":
/*!***************************************************!*\
  !*** ./node_modules/setimmediate/setImmediate.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global, process) {(function (global, undefined) {
    "use strict";

    if (global.setImmediate) {
        return;
    }

    var nextHandle = 1; // Spec says greater than zero
    var tasksByHandle = {};
    var currentlyRunningATask = false;
    var doc = global.document;
    var registerImmediate;

    function setImmediate(callback) {
      // Callback can either be a function or a string
      if (typeof callback !== "function") {
        callback = new Function("" + callback);
      }
      // Copy function arguments
      var args = new Array(arguments.length - 1);
      for (var i = 0; i < args.length; i++) {
          args[i] = arguments[i + 1];
      }
      // Store and register the task
      var task = { callback: callback, args: args };
      tasksByHandle[nextHandle] = task;
      registerImmediate(nextHandle);
      return nextHandle++;
    }

    function clearImmediate(handle) {
        delete tasksByHandle[handle];
    }

    function run(task) {
        var callback = task.callback;
        var args = task.args;
        switch (args.length) {
        case 0:
            callback();
            break;
        case 1:
            callback(args[0]);
            break;
        case 2:
            callback(args[0], args[1]);
            break;
        case 3:
            callback(args[0], args[1], args[2]);
            break;
        default:
            callback.apply(undefined, args);
            break;
        }
    }

    function runIfPresent(handle) {
        // From the spec: "Wait until any invocations of this algorithm started before this one have completed."
        // So if we're currently running a task, we'll need to delay this invocation.
        if (currentlyRunningATask) {
            // Delay by doing a setTimeout. setImmediate was tried instead, but in Firefox 7 it generated a
            // "too much recursion" error.
            setTimeout(runIfPresent, 0, handle);
        } else {
            var task = tasksByHandle[handle];
            if (task) {
                currentlyRunningATask = true;
                try {
                    run(task);
                } finally {
                    clearImmediate(handle);
                    currentlyRunningATask = false;
                }
            }
        }
    }

    function installNextTickImplementation() {
        registerImmediate = function(handle) {
            process.nextTick(function () { runIfPresent(handle); });
        };
    }

    function canUsePostMessage() {
        // The test against `importScripts` prevents this implementation from being installed inside a web worker,
        // where `global.postMessage` means something completely different and can't be used for this purpose.
        if (global.postMessage && !global.importScripts) {
            var postMessageIsAsynchronous = true;
            var oldOnMessage = global.onmessage;
            global.onmessage = function() {
                postMessageIsAsynchronous = false;
            };
            global.postMessage("", "*");
            global.onmessage = oldOnMessage;
            return postMessageIsAsynchronous;
        }
    }

    function installPostMessageImplementation() {
        // Installs an event handler on `global` for the `message` event: see
        // * https://developer.mozilla.org/en/DOM/window.postMessage
        // * http://www.whatwg.org/specs/web-apps/current-work/multipage/comms.html#crossDocumentMessages

        var messagePrefix = "setImmediate$" + Math.random() + "$";
        var onGlobalMessage = function(event) {
            if (event.source === global &&
                typeof event.data === "string" &&
                event.data.indexOf(messagePrefix) === 0) {
                runIfPresent(+event.data.slice(messagePrefix.length));
            }
        };

        if (global.addEventListener) {
            global.addEventListener("message", onGlobalMessage, false);
        } else {
            global.attachEvent("onmessage", onGlobalMessage);
        }

        registerImmediate = function(handle) {
            global.postMessage(messagePrefix + handle, "*");
        };
    }

    function installMessageChannelImplementation() {
        var channel = new MessageChannel();
        channel.port1.onmessage = function(event) {
            var handle = event.data;
            runIfPresent(handle);
        };

        registerImmediate = function(handle) {
            channel.port2.postMessage(handle);
        };
    }

    function installReadyStateChangeImplementation() {
        var html = doc.documentElement;
        registerImmediate = function(handle) {
            // Create a <script> element; its readystatechange event will be fired asynchronously once it is inserted
            // into the document. Do so, thus queuing up the task. Remember to clean up once it's been called.
            var script = doc.createElement("script");
            script.onreadystatechange = function () {
                runIfPresent(handle);
                script.onreadystatechange = null;
                html.removeChild(script);
                script = null;
            };
            html.appendChild(script);
        };
    }

    function installSetTimeoutImplementation() {
        registerImmediate = function(handle) {
            setTimeout(runIfPresent, 0, handle);
        };
    }

    // If supported, we should attach to the prototype of global, since that is where setTimeout et al. live.
    var attachTo = Object.getPrototypeOf && Object.getPrototypeOf(global);
    attachTo = attachTo && attachTo.setTimeout ? attachTo : global;

    // Don't get fooled by e.g. browserify environments.
    if ({}.toString.call(global.process) === "[object process]") {
        // For Node.js before 0.9
        installNextTickImplementation();

    } else if (canUsePostMessage()) {
        // For non-IE10 modern browsers
        installPostMessageImplementation();

    } else if (global.MessageChannel) {
        // For web workers, where supported
        installMessageChannelImplementation();

    } else if (doc && "onreadystatechange" in doc.createElement("script")) {
        // For IE 6–8
        installReadyStateChangeImplementation();

    } else {
        // For older browsers
        installSetTimeoutImplementation();
    }

    attachTo.setImmediate = setImmediate;
    attachTo.clearImmediate = clearImmediate;
}(typeof self === "undefined" ? typeof global === "undefined" ? this : global : self));

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js"), __webpack_require__(/*! ./../process/browser.js */ "./node_modules/process/browser.js")))

/***/ }),

/***/ "./node_modules/source-map-loader/index.js!./node_modules/classnames/index.js":
/*!***************************************************************************!*\
  !*** ./node_modules/source-map-loader!./node_modules/classnames/index.js ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
  Copyright (c) 2017 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames () {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg) && arg.length) {
				var inner = classNames.apply(null, arg);
				if (inner) {
					classes.push(inner);
				}
			} else if (argType === 'object') {
				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js":
/*!**************************************************************************!*\
  !*** ./node_modules/source-map-loader!./node_modules/dayjs/dayjs.min.js ***!
  \**************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function(t,n){ true?module.exports=n():undefined}(this,function(){"use strict";var t="millisecond",n="second",e="minute",r="hour",i="day",s="week",u="month",o="quarter",a="year",h=/^(\d{4})-?(\d{1,2})-?(\d{0,2})[^0-9]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?.?(\d{1,3})?$/,f=/\[([^\]]+)]|Y{2,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g,c=function(t,n,e){var r=String(t);return!r||r.length>=n?t:""+Array(n+1-r.length).join(e)+t},d={s:c,z:function(t){var n=-t.utcOffset(),e=Math.abs(n),r=Math.floor(e/60),i=e%60;return(n<=0?"+":"-")+c(r,2,"0")+":"+c(i,2,"0")},m:function(t,n){var e=12*(n.year()-t.year())+(n.month()-t.month()),r=t.clone().add(e,u),i=n-r<0,s=t.clone().add(e+(i?-1:1),u);return Number(-(e+(n-r)/(i?r-s:s-r))||0)},a:function(t){return t<0?Math.ceil(t)||0:Math.floor(t)},p:function(h){return{M:u,y:a,w:s,d:i,D:"date",h:r,m:e,s:n,ms:t,Q:o}[h]||String(h||"").toLowerCase().replace(/s$/,"")},u:function(t){return void 0===t}},$={name:"en",weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_")},l="en",m={};m[l]=$;var y=function(t){return t instanceof v},M=function(t,n,e){var r;if(!t)return l;if("string"==typeof t)m[t]&&(r=t),n&&(m[t]=n,r=t);else{var i=t.name;m[i]=t,r=i}return!e&&r&&(l=r),r||!e&&l},g=function(t,n,e){if(y(t))return t.clone();var r=n?"string"==typeof n?{format:n,pl:e}:n:{};return r.date=t,new v(r)},D=d;D.l=M,D.i=y,D.w=function(t,n){return g(t,{locale:n.$L,utc:n.$u,$offset:n.$offset})};var v=function(){function c(t){this.$L=this.$L||M(t.locale,null,!0),this.parse(t)}var d=c.prototype;return d.parse=function(t){this.$d=function(t){var n=t.date,e=t.utc;if(null===n)return new Date(NaN);if(D.u(n))return new Date;if(n instanceof Date)return new Date(n);if("string"==typeof n&&!/Z$/i.test(n)){var r=n.match(h);if(r)return e?new Date(Date.UTC(r[1],r[2]-1,r[3]||1,r[4]||0,r[5]||0,r[6]||0,r[7]||0)):new Date(r[1],r[2]-1,r[3]||1,r[4]||0,r[5]||0,r[6]||0,r[7]||0)}return new Date(n)}(t),this.init()},d.init=function(){var t=this.$d;this.$y=t.getFullYear(),this.$M=t.getMonth(),this.$D=t.getDate(),this.$W=t.getDay(),this.$H=t.getHours(),this.$m=t.getMinutes(),this.$s=t.getSeconds(),this.$ms=t.getMilliseconds()},d.$utils=function(){return D},d.isValid=function(){return!("Invalid Date"===this.$d.toString())},d.isSame=function(t,n){var e=g(t);return this.startOf(n)<=e&&e<=this.endOf(n)},d.isAfter=function(t,n){return g(t)<this.startOf(n)},d.isBefore=function(t,n){return this.endOf(n)<g(t)},d.$g=function(t,n,e){return D.u(t)?this[n]:this.set(e,t)},d.year=function(t){return this.$g(t,"$y",a)},d.month=function(t){return this.$g(t,"$M",u)},d.day=function(t){return this.$g(t,"$W",i)},d.date=function(t){return this.$g(t,"$D","date")},d.hour=function(t){return this.$g(t,"$H",r)},d.minute=function(t){return this.$g(t,"$m",e)},d.second=function(t){return this.$g(t,"$s",n)},d.millisecond=function(n){return this.$g(n,"$ms",t)},d.unix=function(){return Math.floor(this.valueOf()/1e3)},d.valueOf=function(){return this.$d.getTime()},d.startOf=function(t,o){var h=this,f=!!D.u(o)||o,c=D.p(t),d=function(t,n){var e=D.w(h.$u?Date.UTC(h.$y,n,t):new Date(h.$y,n,t),h);return f?e:e.endOf(i)},$=function(t,n){return D.w(h.toDate()[t].apply(h.toDate(),(f?[0,0,0,0]:[23,59,59,999]).slice(n)),h)},l=this.$W,m=this.$M,y=this.$D,M="set"+(this.$u?"UTC":"");switch(c){case a:return f?d(1,0):d(31,11);case u:return f?d(1,m):d(0,m+1);case s:var g=this.$locale().weekStart||0,v=(l<g?l+7:l)-g;return d(f?y-v:y+(6-v),m);case i:case"date":return $(M+"Hours",0);case r:return $(M+"Minutes",1);case e:return $(M+"Seconds",2);case n:return $(M+"Milliseconds",3);default:return this.clone()}},d.endOf=function(t){return this.startOf(t,!1)},d.$set=function(s,o){var h,f=D.p(s),c="set"+(this.$u?"UTC":""),d=(h={},h[i]=c+"Date",h.date=c+"Date",h[u]=c+"Month",h[a]=c+"FullYear",h[r]=c+"Hours",h[e]=c+"Minutes",h[n]=c+"Seconds",h[t]=c+"Milliseconds",h)[f],$=f===i?this.$D+(o-this.$W):o;if(f===u||f===a){var l=this.clone().set("date",1);l.$d[d]($),l.init(),this.$d=l.set("date",Math.min(this.$D,l.daysInMonth())).toDate()}else d&&this.$d[d]($);return this.init(),this},d.set=function(t,n){return this.clone().$set(t,n)},d.get=function(t){return this[D.p(t)]()},d.add=function(t,o){var h,f=this;t=Number(t);var c=D.p(o),d=function(n){var e=g(f);return D.w(e.date(e.date()+Math.round(n*t)),f)};if(c===u)return this.set(u,this.$M+t);if(c===a)return this.set(a,this.$y+t);if(c===i)return d(1);if(c===s)return d(7);var $=(h={},h[e]=6e4,h[r]=36e5,h[n]=1e3,h)[c]||1,l=this.$d.getTime()+t*$;return D.w(l,this)},d.subtract=function(t,n){return this.add(-1*t,n)},d.format=function(t){var n=this;if(!this.isValid())return"Invalid Date";var e=t||"YYYY-MM-DDTHH:mm:ssZ",r=D.z(this),i=this.$locale(),s=this.$H,u=this.$m,o=this.$M,a=i.weekdays,h=i.months,c=function(t,r,i,s){return t&&(t[r]||t(n,e))||i[r].substr(0,s)},d=function(t){return D.s(s%12||12,t,"0")},$=i.meridiem||function(t,n,e){var r=t<12?"AM":"PM";return e?r.toLowerCase():r},l={YY:String(this.$y).slice(-2),YYYY:this.$y,M:o+1,MM:D.s(o+1,2,"0"),MMM:c(i.monthsShort,o,h,3),MMMM:h[o]||h(this,e),D:this.$D,DD:D.s(this.$D,2,"0"),d:String(this.$W),dd:c(i.weekdaysMin,this.$W,a,2),ddd:c(i.weekdaysShort,this.$W,a,3),dddd:a[this.$W],H:String(s),HH:D.s(s,2,"0"),h:d(1),hh:d(2),a:$(s,u,!0),A:$(s,u,!1),m:String(u),mm:D.s(u,2,"0"),s:String(this.$s),ss:D.s(this.$s,2,"0"),SSS:D.s(this.$ms,3,"0"),Z:r};return e.replace(f,function(t,n){return n||l[t]||r.replace(":","")})},d.utcOffset=function(){return 15*-Math.round(this.$d.getTimezoneOffset()/15)},d.diff=function(t,h,f){var c,d=D.p(h),$=g(t),l=6e4*($.utcOffset()-this.utcOffset()),m=this-$,y=D.m(this,$);return y=(c={},c[a]=y/12,c[u]=y,c[o]=y/3,c[s]=(m-l)/6048e5,c[i]=(m-l)/864e5,c[r]=m/36e5,c[e]=m/6e4,c[n]=m/1e3,c)[d]||m,f?y:D.a(y)},d.daysInMonth=function(){return this.endOf(u).$D},d.$locale=function(){return m[this.$L]},d.locale=function(t,n){if(!t)return this.$L;var e=this.clone(),r=M(t,n,!0);return r&&(e.$L=r),e},d.clone=function(){return D.w(this.$d,this)},d.toDate=function(){return new Date(this.valueOf())},d.toJSON=function(){return this.isValid()?this.toISOString():null},d.toISOString=function(){return this.$d.toISOString()},d.toString=function(){return this.$d.toUTCString()},c}();return g.prototype=v.prototype,g.extend=function(t,n){return t(n,v,g),g},g.locale=M,g.isDayjs=y,g.unix=function(t){return g(1e3*t)},g.en=m[l],g.Ls=m,g});


/***/ }),

/***/ "./node_modules/source-map-loader/index.js!./node_modules/m.attrs.bidi/bidi.js":
/*!****************************************************************************!*\
  !*** ./node_modules/source-map-loader!./node_modules/m.attrs.bidi/bidi.js ***!
  \****************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;( function _package( factory ){
	if( true ){
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [ __webpack_require__(/*! mithril */ "mithril") ], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__))
	}
	else {}
}( function define( m ){
	function bidi( node, prop ){
		var type = node.tag === 'select'
			? node.attrs.multi
				? 'multi'
				: 'select'
			: node.attrs.type

		// Setup: bind listeners
		if( type === 'multi' ){
			node.attrs.onchange = function(){
				prop( [].slice.call( this.selectedOptions, function( x ){
					return x.value
				} ) )
			}
		}
		else if( type === 'select' ){
			node.attrs.onchange = function( e ){
				prop( this.selectedOptions[ 0 ].value )
			}
		}
		else if( type === 'checkbox' ){
			node.attrs.onchange = function( e ){
				prop( this.checked )
			}
		}
		else {
			node.attrs.onchange = node.attrs.oninput = function( e ){
				prop( this.value )
			}
		}

		if( node.tag === 'select' ){
			node.children.forEach( function( option ){
				if( option.attrs.value === prop() || option.children[ 0 ] === prop() ){
					option.attrs.selected = true
				}
			} )
		}
		else if( type === 'checkbox' ){
			node.attrs.checked = prop()
		}
		else if( type === 'radio' ){
			node.attrs.checked = prop() === node.attrs.value
		}
		else {
			node.attrs.value   = prop()
		}

		return node
	}

	bidi.view = function( ctrl, node, prop ){
	  return bidi( node, node.attrs.bidi )
	}

	if( m.attrs ) m.attrs.bidi = bidi
	
	m.bidi = bidi

	return bidi
} ) )


/***/ }),

/***/ "./node_modules/source-map-loader/index.js!./node_modules/mithril/index.js":
/*!************************************************************************!*\
  !*** ./node_modules/source-map-loader!./node_modules/mithril/index.js ***!
  \************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var hyperscript = __webpack_require__(/*! ./hyperscript */ "./node_modules/mithril/hyperscript.js")
var request = __webpack_require__(/*! ./request */ "./node_modules/mithril/request.js")
var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js")

var m = function m() { return hyperscript.apply(this, arguments) }
m.m = hyperscript
m.trust = hyperscript.trust
m.fragment = hyperscript.fragment
m.mount = mountRedraw.mount
m.route = __webpack_require__(/*! ./route */ "./node_modules/mithril/route.js")
m.render = __webpack_require__(/*! ./render */ "./node_modules/mithril/render.js")
m.redraw = mountRedraw.redraw
m.request = request.request
m.jsonp = request.jsonp
m.parseQueryString = __webpack_require__(/*! ./querystring/parse */ "./node_modules/mithril/querystring/parse.js")
m.buildQueryString = __webpack_require__(/*! ./querystring/build */ "./node_modules/mithril/querystring/build.js")
m.parsePathname = __webpack_require__(/*! ./pathname/parse */ "./node_modules/mithril/pathname/parse.js")
m.buildPathname = __webpack_require__(/*! ./pathname/build */ "./node_modules/mithril/pathname/build.js")
m.vnode = __webpack_require__(/*! ./render/vnode */ "./node_modules/mithril/render/vnode.js")
m.PromisePolyfill = __webpack_require__(/*! ./promise/polyfill */ "./node_modules/mithril/promise/polyfill.js")

module.exports = m


/***/ }),

/***/ "./node_modules/source-map-loader/index.js!./node_modules/mousetrap/mousetrap.js":
/*!******************************************************************************!*\
  !*** ./node_modules/source-map-loader!./node_modules/mousetrap/mousetrap.js ***!
  \******************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/*global define:false */
/**
 * Copyright 2012-2017 Craig Campbell
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Mousetrap is a simple keyboard shortcut library for Javascript with
 * no external dependencies
 *
 * @version 1.6.5
 * @url craig.is/killing/mice
 */
(function(window, document, undefined) {

    // Check if mousetrap is used inside browser, if not, return
    if (!window) {
        return;
    }

    /**
     * mapping of special keycodes to their corresponding keys
     *
     * everything in this dictionary cannot use keypress events
     * so it has to be here to map to the correct keycodes for
     * keyup/keydown events
     *
     * @type {Object}
     */
    var _MAP = {
        8: 'backspace',
        9: 'tab',
        13: 'enter',
        16: 'shift',
        17: 'ctrl',
        18: 'alt',
        20: 'capslock',
        27: 'esc',
        32: 'space',
        33: 'pageup',
        34: 'pagedown',
        35: 'end',
        36: 'home',
        37: 'left',
        38: 'up',
        39: 'right',
        40: 'down',
        45: 'ins',
        46: 'del',
        91: 'meta',
        93: 'meta',
        224: 'meta'
    };

    /**
     * mapping for special characters so they can support
     *
     * this dictionary is only used incase you want to bind a
     * keyup or keydown event to one of these keys
     *
     * @type {Object}
     */
    var _KEYCODE_MAP = {
        106: '*',
        107: '+',
        109: '-',
        110: '.',
        111 : '/',
        186: ';',
        187: '=',
        188: ',',
        189: '-',
        190: '.',
        191: '/',
        192: '`',
        219: '[',
        220: '\\',
        221: ']',
        222: '\''
    };

    /**
     * this is a mapping of keys that require shift on a US keypad
     * back to the non shift equivelents
     *
     * this is so you can use keyup events with these keys
     *
     * note that this will only work reliably on US keyboards
     *
     * @type {Object}
     */
    var _SHIFT_MAP = {
        '~': '`',
        '!': '1',
        '@': '2',
        '#': '3',
        '$': '4',
        '%': '5',
        '^': '6',
        '&': '7',
        '*': '8',
        '(': '9',
        ')': '0',
        '_': '-',
        '+': '=',
        ':': ';',
        '\"': '\'',
        '<': ',',
        '>': '.',
        '?': '/',
        '|': '\\'
    };

    /**
     * this is a list of special strings you can use to map
     * to modifier keys when you specify your keyboard shortcuts
     *
     * @type {Object}
     */
    var _SPECIAL_ALIASES = {
        'option': 'alt',
        'command': 'meta',
        'return': 'enter',
        'escape': 'esc',
        'plus': '+',
        'mod': /Mac|iPod|iPhone|iPad/.test(navigator.platform) ? 'meta' : 'ctrl'
    };

    /**
     * variable to store the flipped version of _MAP from above
     * needed to check if we should use keypress or not when no action
     * is specified
     *
     * @type {Object|undefined}
     */
    var _REVERSE_MAP;

    /**
     * loop through the f keys, f1 to f19 and add them to the map
     * programatically
     */
    for (var i = 1; i < 20; ++i) {
        _MAP[111 + i] = 'f' + i;
    }

    /**
     * loop through to map numbers on the numeric keypad
     */
    for (i = 0; i <= 9; ++i) {

        // This needs to use a string cause otherwise since 0 is falsey
        // mousetrap will never fire for numpad 0 pressed as part of a keydown
        // event.
        //
        // @see https://github.com/ccampbell/mousetrap/pull/258
        _MAP[i + 96] = i.toString();
    }

    /**
     * cross browser add event method
     *
     * @param {Element|HTMLDocument} object
     * @param {string} type
     * @param {Function} callback
     * @returns void
     */
    function _addEvent(object, type, callback) {
        if (object.addEventListener) {
            object.addEventListener(type, callback, false);
            return;
        }

        object.attachEvent('on' + type, callback);
    }

    /**
     * takes the event and returns the key character
     *
     * @param {Event} e
     * @return {string}
     */
    function _characterFromEvent(e) {

        // for keypress events we should return the character as is
        if (e.type == 'keypress') {
            var character = String.fromCharCode(e.which);

            // if the shift key is not pressed then it is safe to assume
            // that we want the character to be lowercase.  this means if
            // you accidentally have caps lock on then your key bindings
            // will continue to work
            //
            // the only side effect that might not be desired is if you
            // bind something like 'A' cause you want to trigger an
            // event when capital A is pressed caps lock will no longer
            // trigger the event.  shift+a will though.
            if (!e.shiftKey) {
                character = character.toLowerCase();
            }

            return character;
        }

        // for non keypress events the special maps are needed
        if (_MAP[e.which]) {
            return _MAP[e.which];
        }

        if (_KEYCODE_MAP[e.which]) {
            return _KEYCODE_MAP[e.which];
        }

        // if it is not in the special map

        // with keydown and keyup events the character seems to always
        // come in as an uppercase character whether you are pressing shift
        // or not.  we should make sure it is always lowercase for comparisons
        return String.fromCharCode(e.which).toLowerCase();
    }

    /**
     * checks if two arrays are equal
     *
     * @param {Array} modifiers1
     * @param {Array} modifiers2
     * @returns {boolean}
     */
    function _modifiersMatch(modifiers1, modifiers2) {
        return modifiers1.sort().join(',') === modifiers2.sort().join(',');
    }

    /**
     * takes a key event and figures out what the modifiers are
     *
     * @param {Event} e
     * @returns {Array}
     */
    function _eventModifiers(e) {
        var modifiers = [];

        if (e.shiftKey) {
            modifiers.push('shift');
        }

        if (e.altKey) {
            modifiers.push('alt');
        }

        if (e.ctrlKey) {
            modifiers.push('ctrl');
        }

        if (e.metaKey) {
            modifiers.push('meta');
        }

        return modifiers;
    }

    /**
     * prevents default for this event
     *
     * @param {Event} e
     * @returns void
     */
    function _preventDefault(e) {
        if (e.preventDefault) {
            e.preventDefault();
            return;
        }

        e.returnValue = false;
    }

    /**
     * stops propogation for this event
     *
     * @param {Event} e
     * @returns void
     */
    function _stopPropagation(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
            return;
        }

        e.cancelBubble = true;
    }

    /**
     * determines if the keycode specified is a modifier key or not
     *
     * @param {string} key
     * @returns {boolean}
     */
    function _isModifier(key) {
        return key == 'shift' || key == 'ctrl' || key == 'alt' || key == 'meta';
    }

    /**
     * reverses the map lookup so that we can look for specific keys
     * to see what can and can't use keypress
     *
     * @return {Object}
     */
    function _getReverseMap() {
        if (!_REVERSE_MAP) {
            _REVERSE_MAP = {};
            for (var key in _MAP) {

                // pull out the numeric keypad from here cause keypress should
                // be able to detect the keys from the character
                if (key > 95 && key < 112) {
                    continue;
                }

                if (_MAP.hasOwnProperty(key)) {
                    _REVERSE_MAP[_MAP[key]] = key;
                }
            }
        }
        return _REVERSE_MAP;
    }

    /**
     * picks the best action based on the key combination
     *
     * @param {string} key - character for key
     * @param {Array} modifiers
     * @param {string=} action passed in
     */
    function _pickBestAction(key, modifiers, action) {

        // if no action was picked in we should try to pick the one
        // that we think would work best for this key
        if (!action) {
            action = _getReverseMap()[key] ? 'keydown' : 'keypress';
        }

        // modifier keys don't work as expected with keypress,
        // switch to keydown
        if (action == 'keypress' && modifiers.length) {
            action = 'keydown';
        }

        return action;
    }

    /**
     * Converts from a string key combination to an array
     *
     * @param  {string} combination like "command+shift+l"
     * @return {Array}
     */
    function _keysFromString(combination) {
        if (combination === '+') {
            return ['+'];
        }

        combination = combination.replace(/\+{2}/g, '+plus');
        return combination.split('+');
    }

    /**
     * Gets info for a specific key combination
     *
     * @param  {string} combination key combination ("command+s" or "a" or "*")
     * @param  {string=} action
     * @returns {Object}
     */
    function _getKeyInfo(combination, action) {
        var keys;
        var key;
        var i;
        var modifiers = [];

        // take the keys from this pattern and figure out what the actual
        // pattern is all about
        keys = _keysFromString(combination);

        for (i = 0; i < keys.length; ++i) {
            key = keys[i];

            // normalize key names
            if (_SPECIAL_ALIASES[key]) {
                key = _SPECIAL_ALIASES[key];
            }

            // if this is not a keypress event then we should
            // be smart about using shift keys
            // this will only work for US keyboards however
            if (action && action != 'keypress' && _SHIFT_MAP[key]) {
                key = _SHIFT_MAP[key];
                modifiers.push('shift');
            }

            // if this key is a modifier then add it to the list of modifiers
            if (_isModifier(key)) {
                modifiers.push(key);
            }
        }

        // depending on what the key combination is
        // we will try to pick the best event for it
        action = _pickBestAction(key, modifiers, action);

        return {
            key: key,
            modifiers: modifiers,
            action: action
        };
    }

    function _belongsTo(element, ancestor) {
        if (element === null || element === document) {
            return false;
        }

        if (element === ancestor) {
            return true;
        }

        return _belongsTo(element.parentNode, ancestor);
    }

    function Mousetrap(targetElement) {
        var self = this;

        targetElement = targetElement || document;

        if (!(self instanceof Mousetrap)) {
            return new Mousetrap(targetElement);
        }

        /**
         * element to attach key events to
         *
         * @type {Element}
         */
        self.target = targetElement;

        /**
         * a list of all the callbacks setup via Mousetrap.bind()
         *
         * @type {Object}
         */
        self._callbacks = {};

        /**
         * direct map of string combinations to callbacks used for trigger()
         *
         * @type {Object}
         */
        self._directMap = {};

        /**
         * keeps track of what level each sequence is at since multiple
         * sequences can start out with the same sequence
         *
         * @type {Object}
         */
        var _sequenceLevels = {};

        /**
         * variable to store the setTimeout call
         *
         * @type {null|number}
         */
        var _resetTimer;

        /**
         * temporary state where we will ignore the next keyup
         *
         * @type {boolean|string}
         */
        var _ignoreNextKeyup = false;

        /**
         * temporary state where we will ignore the next keypress
         *
         * @type {boolean}
         */
        var _ignoreNextKeypress = false;

        /**
         * are we currently inside of a sequence?
         * type of action ("keyup" or "keydown" or "keypress") or false
         *
         * @type {boolean|string}
         */
        var _nextExpectedAction = false;

        /**
         * resets all sequence counters except for the ones passed in
         *
         * @param {Object} doNotReset
         * @returns void
         */
        function _resetSequences(doNotReset) {
            doNotReset = doNotReset || {};

            var activeSequences = false,
                key;

            for (key in _sequenceLevels) {
                if (doNotReset[key]) {
                    activeSequences = true;
                    continue;
                }
                _sequenceLevels[key] = 0;
            }

            if (!activeSequences) {
                _nextExpectedAction = false;
            }
        }

        /**
         * finds all callbacks that match based on the keycode, modifiers,
         * and action
         *
         * @param {string} character
         * @param {Array} modifiers
         * @param {Event|Object} e
         * @param {string=} sequenceName - name of the sequence we are looking for
         * @param {string=} combination
         * @param {number=} level
         * @returns {Array}
         */
        function _getMatches(character, modifiers, e, sequenceName, combination, level) {
            var i;
            var callback;
            var matches = [];
            var action = e.type;

            // if there are no events related to this keycode
            if (!self._callbacks[character]) {
                return [];
            }

            // if a modifier key is coming up on its own we should allow it
            if (action == 'keyup' && _isModifier(character)) {
                modifiers = [character];
            }

            // loop through all callbacks for the key that was pressed
            // and see if any of them match
            for (i = 0; i < self._callbacks[character].length; ++i) {
                callback = self._callbacks[character][i];

                // if a sequence name is not specified, but this is a sequence at
                // the wrong level then move onto the next match
                if (!sequenceName && callback.seq && _sequenceLevels[callback.seq] != callback.level) {
                    continue;
                }

                // if the action we are looking for doesn't match the action we got
                // then we should keep going
                if (action != callback.action) {
                    continue;
                }

                // if this is a keypress event and the meta key and control key
                // are not pressed that means that we need to only look at the
                // character, otherwise check the modifiers as well
                //
                // chrome will not fire a keypress if meta or control is down
                // safari will fire a keypress if meta or meta+shift is down
                // firefox will fire a keypress if meta or control is down
                if ((action == 'keypress' && !e.metaKey && !e.ctrlKey) || _modifiersMatch(modifiers, callback.modifiers)) {

                    // when you bind a combination or sequence a second time it
                    // should overwrite the first one.  if a sequenceName or
                    // combination is specified in this call it does just that
                    //
                    // @todo make deleting its own method?
                    var deleteCombo = !sequenceName && callback.combo == combination;
                    var deleteSequence = sequenceName && callback.seq == sequenceName && callback.level == level;
                    if (deleteCombo || deleteSequence) {
                        self._callbacks[character].splice(i, 1);
                    }

                    matches.push(callback);
                }
            }

            return matches;
        }

        /**
         * actually calls the callback function
         *
         * if your callback function returns false this will use the jquery
         * convention - prevent default and stop propogation on the event
         *
         * @param {Function} callback
         * @param {Event} e
         * @returns void
         */
        function _fireCallback(callback, e, combo, sequence) {

            // if this event should not happen stop here
            if (self.stopCallback(e, e.target || e.srcElement, combo, sequence)) {
                return;
            }

            if (callback(e, combo) === false) {
                _preventDefault(e);
                _stopPropagation(e);
            }
        }

        /**
         * handles a character key event
         *
         * @param {string} character
         * @param {Array} modifiers
         * @param {Event} e
         * @returns void
         */
        self._handleKey = function(character, modifiers, e) {
            var callbacks = _getMatches(character, modifiers, e);
            var i;
            var doNotReset = {};
            var maxLevel = 0;
            var processedSequenceCallback = false;

            // Calculate the maxLevel for sequences so we can only execute the longest callback sequence
            for (i = 0; i < callbacks.length; ++i) {
                if (callbacks[i].seq) {
                    maxLevel = Math.max(maxLevel, callbacks[i].level);
                }
            }

            // loop through matching callbacks for this key event
            for (i = 0; i < callbacks.length; ++i) {

                // fire for all sequence callbacks
                // this is because if for example you have multiple sequences
                // bound such as "g i" and "g t" they both need to fire the
                // callback for matching g cause otherwise you can only ever
                // match the first one
                if (callbacks[i].seq) {

                    // only fire callbacks for the maxLevel to prevent
                    // subsequences from also firing
                    //
                    // for example 'a option b' should not cause 'option b' to fire
                    // even though 'option b' is part of the other sequence
                    //
                    // any sequences that do not match here will be discarded
                    // below by the _resetSequences call
                    if (callbacks[i].level != maxLevel) {
                        continue;
                    }

                    processedSequenceCallback = true;

                    // keep a list of which sequences were matches for later
                    doNotReset[callbacks[i].seq] = 1;
                    _fireCallback(callbacks[i].callback, e, callbacks[i].combo, callbacks[i].seq);
                    continue;
                }

                // if there were no sequence matches but we are still here
                // that means this is a regular match so we should fire that
                if (!processedSequenceCallback) {
                    _fireCallback(callbacks[i].callback, e, callbacks[i].combo);
                }
            }

            // if the key you pressed matches the type of sequence without
            // being a modifier (ie "keyup" or "keypress") then we should
            // reset all sequences that were not matched by this event
            //
            // this is so, for example, if you have the sequence "h a t" and you
            // type "h e a r t" it does not match.  in this case the "e" will
            // cause the sequence to reset
            //
            // modifier keys are ignored because you can have a sequence
            // that contains modifiers such as "enter ctrl+space" and in most
            // cases the modifier key will be pressed before the next key
            //
            // also if you have a sequence such as "ctrl+b a" then pressing the
            // "b" key will trigger a "keypress" and a "keydown"
            //
            // the "keydown" is expected when there is a modifier, but the
            // "keypress" ends up matching the _nextExpectedAction since it occurs
            // after and that causes the sequence to reset
            //
            // we ignore keypresses in a sequence that directly follow a keydown
            // for the same character
            var ignoreThisKeypress = e.type == 'keypress' && _ignoreNextKeypress;
            if (e.type == _nextExpectedAction && !_isModifier(character) && !ignoreThisKeypress) {
                _resetSequences(doNotReset);
            }

            _ignoreNextKeypress = processedSequenceCallback && e.type == 'keydown';
        };

        /**
         * handles a keydown event
         *
         * @param {Event} e
         * @returns void
         */
        function _handleKeyEvent(e) {

            // normalize e.which for key events
            // @see http://stackoverflow.com/questions/4285627/javascript-keycode-vs-charcode-utter-confusion
            if (typeof e.which !== 'number') {
                e.which = e.keyCode;
            }

            var character = _characterFromEvent(e);

            // no character found then stop
            if (!character) {
                return;
            }

            // need to use === for the character check because the character can be 0
            if (e.type == 'keyup' && _ignoreNextKeyup === character) {
                _ignoreNextKeyup = false;
                return;
            }

            self.handleKey(character, _eventModifiers(e), e);
        }

        /**
         * called to set a 1 second timeout on the specified sequence
         *
         * this is so after each key press in the sequence you have 1 second
         * to press the next key before you have to start over
         *
         * @returns void
         */
        function _resetSequenceTimer() {
            clearTimeout(_resetTimer);
            _resetTimer = setTimeout(_resetSequences, 1000);
        }

        /**
         * binds a key sequence to an event
         *
         * @param {string} combo - combo specified in bind call
         * @param {Array} keys
         * @param {Function} callback
         * @param {string=} action
         * @returns void
         */
        function _bindSequence(combo, keys, callback, action) {

            // start off by adding a sequence level record for this combination
            // and setting the level to 0
            _sequenceLevels[combo] = 0;

            /**
             * callback to increase the sequence level for this sequence and reset
             * all other sequences that were active
             *
             * @param {string} nextAction
             * @returns {Function}
             */
            function _increaseSequence(nextAction) {
                return function() {
                    _nextExpectedAction = nextAction;
                    ++_sequenceLevels[combo];
                    _resetSequenceTimer();
                };
            }

            /**
             * wraps the specified callback inside of another function in order
             * to reset all sequence counters as soon as this sequence is done
             *
             * @param {Event} e
             * @returns void
             */
            function _callbackAndReset(e) {
                _fireCallback(callback, e, combo);

                // we should ignore the next key up if the action is key down
                // or keypress.  this is so if you finish a sequence and
                // release the key the final key will not trigger a keyup
                if (action !== 'keyup') {
                    _ignoreNextKeyup = _characterFromEvent(e);
                }

                // weird race condition if a sequence ends with the key
                // another sequence begins with
                setTimeout(_resetSequences, 10);
            }

            // loop through keys one at a time and bind the appropriate callback
            // function.  for any key leading up to the final one it should
            // increase the sequence. after the final, it should reset all sequences
            //
            // if an action is specified in the original bind call then that will
            // be used throughout.  otherwise we will pass the action that the
            // next key in the sequence should match.  this allows a sequence
            // to mix and match keypress and keydown events depending on which
            // ones are better suited to the key provided
            for (var i = 0; i < keys.length; ++i) {
                var isFinal = i + 1 === keys.length;
                var wrappedCallback = isFinal ? _callbackAndReset : _increaseSequence(action || _getKeyInfo(keys[i + 1]).action);
                _bindSingle(keys[i], wrappedCallback, action, combo, i);
            }
        }

        /**
         * binds a single keyboard combination
         *
         * @param {string} combination
         * @param {Function} callback
         * @param {string=} action
         * @param {string=} sequenceName - name of sequence if part of sequence
         * @param {number=} level - what part of the sequence the command is
         * @returns void
         */
        function _bindSingle(combination, callback, action, sequenceName, level) {

            // store a direct mapped reference for use with Mousetrap.trigger
            self._directMap[combination + ':' + action] = callback;

            // make sure multiple spaces in a row become a single space
            combination = combination.replace(/\s+/g, ' ');

            var sequence = combination.split(' ');
            var info;

            // if this pattern is a sequence of keys then run through this method
            // to reprocess each pattern one key at a time
            if (sequence.length > 1) {
                _bindSequence(combination, sequence, callback, action);
                return;
            }

            info = _getKeyInfo(combination, action);

            // make sure to initialize array if this is the first time
            // a callback is added for this key
            self._callbacks[info.key] = self._callbacks[info.key] || [];

            // remove an existing match if there is one
            _getMatches(info.key, info.modifiers, {type: info.action}, sequenceName, combination, level);

            // add this call back to the array
            // if it is a sequence put it at the beginning
            // if not put it at the end
            //
            // this is important because the way these are processed expects
            // the sequence ones to come first
            self._callbacks[info.key][sequenceName ? 'unshift' : 'push']({
                callback: callback,
                modifiers: info.modifiers,
                action: info.action,
                seq: sequenceName,
                level: level,
                combo: combination
            });
        }

        /**
         * binds multiple combinations to the same callback
         *
         * @param {Array} combinations
         * @param {Function} callback
         * @param {string|undefined} action
         * @returns void
         */
        self._bindMultiple = function(combinations, callback, action) {
            for (var i = 0; i < combinations.length; ++i) {
                _bindSingle(combinations[i], callback, action);
            }
        };

        // start!
        _addEvent(targetElement, 'keypress', _handleKeyEvent);
        _addEvent(targetElement, 'keydown', _handleKeyEvent);
        _addEvent(targetElement, 'keyup', _handleKeyEvent);
    }

    /**
     * binds an event to mousetrap
     *
     * can be a single key, a combination of keys separated with +,
     * an array of keys, or a sequence of keys separated by spaces
     *
     * be sure to list the modifier keys first to make sure that the
     * correct key ends up getting bound (the last key in the pattern)
     *
     * @param {string|Array} keys
     * @param {Function} callback
     * @param {string=} action - 'keypress', 'keydown', or 'keyup'
     * @returns void
     */
    Mousetrap.prototype.bind = function(keys, callback, action) {
        var self = this;
        keys = keys instanceof Array ? keys : [keys];
        self._bindMultiple.call(self, keys, callback, action);
        return self;
    };

    /**
     * unbinds an event to mousetrap
     *
     * the unbinding sets the callback function of the specified key combo
     * to an empty function and deletes the corresponding key in the
     * _directMap dict.
     *
     * TODO: actually remove this from the _callbacks dictionary instead
     * of binding an empty function
     *
     * the keycombo+action has to be exactly the same as
     * it was defined in the bind method
     *
     * @param {string|Array} keys
     * @param {string} action
     * @returns void
     */
    Mousetrap.prototype.unbind = function(keys, action) {
        var self = this;
        return self.bind.call(self, keys, function() {}, action);
    };

    /**
     * triggers an event that has already been bound
     *
     * @param {string} keys
     * @param {string=} action
     * @returns void
     */
    Mousetrap.prototype.trigger = function(keys, action) {
        var self = this;
        if (self._directMap[keys + ':' + action]) {
            self._directMap[keys + ':' + action]({}, keys);
        }
        return self;
    };

    /**
     * resets the library back to its initial state.  this is useful
     * if you want to clear out the current keyboard shortcuts and bind
     * new ones - for example if you switch to another page
     *
     * @returns void
     */
    Mousetrap.prototype.reset = function() {
        var self = this;
        self._callbacks = {};
        self._directMap = {};
        return self;
    };

    /**
     * should we stop this event before firing off callbacks
     *
     * @param {Event} e
     * @param {Element} element
     * @return {boolean}
     */
    Mousetrap.prototype.stopCallback = function(e, element) {
        var self = this;

        // if the element has the class "mousetrap" then no need to stop
        if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
            return false;
        }

        if (_belongsTo(element, self.target)) {
            return false;
        }

        // Events originating from a shadow DOM are re-targetted and `e.target` is the shadow host,
        // not the initial event target in the shadow tree. Note that not all events cross the
        // shadow boundary.
        // For shadow trees with `mode: 'open'`, the initial event target is the first element in
        // the event’s composed path. For shadow trees with `mode: 'closed'`, the initial event
        // target cannot be obtained.
        if ('composedPath' in e && typeof e.composedPath === 'function') {
            // For open shadow trees, update `element` so that the following check works.
            var initialEventTarget = e.composedPath()[0];
            if (initialEventTarget !== e.target) {
                element = initialEventTarget;
            }
        }

        // stop for input, select, and textarea
        return element.tagName == 'INPUT' || element.tagName == 'SELECT' || element.tagName == 'TEXTAREA' || element.isContentEditable;
    };

    /**
     * exposes _handleKey publicly so it can be overwritten by extensions
     */
    Mousetrap.prototype.handleKey = function() {
        var self = this;
        return self._handleKey.apply(self, arguments);
    };

    /**
     * allow custom key mappings
     */
    Mousetrap.addKeycodes = function(object) {
        for (var key in object) {
            if (object.hasOwnProperty(key)) {
                _MAP[key] = object[key];
            }
        }
        _REVERSE_MAP = null;
    };

    /**
     * Init the global mousetrap functions
     *
     * This method is needed to allow the global mousetrap functions to work
     * now that mousetrap is a constructor function.
     */
    Mousetrap.init = function() {
        var documentMousetrap = Mousetrap(document);
        for (var method in documentMousetrap) {
            if (method.charAt(0) !== '_') {
                Mousetrap[method] = (function(method) {
                    return function() {
                        return documentMousetrap[method].apply(documentMousetrap, arguments);
                    };
                } (method));
            }
        }
    };

    Mousetrap.init();

    // expose mousetrap to the global object
    window.Mousetrap = Mousetrap;

    // expose as a common js module
    if ( true && module.exports) {
        module.exports = Mousetrap;
    }

    // expose mousetrap as an AMD module
    if (true) {
        !(__WEBPACK_AMD_DEFINE_RESULT__ = (function() {
            return Mousetrap;
        }).call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    }
}) (typeof window !== 'undefined' ? window : null, typeof  window !== 'undefined' ? document : null);


/***/ }),

/***/ "./node_modules/spin.js/spin.js":
/*!**************************************!*\
  !*** ./node_modules/spin.js/spin.js ***!
  \**************************************/
/*! exports provided: Spinner */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Spinner", function() { return Spinner; });
var __assign = (undefined && undefined.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var defaults = {
    lines: 12,
    length: 7,
    width: 5,
    radius: 10,
    scale: 1.0,
    corners: 1,
    color: '#000',
    fadeColor: 'transparent',
    animation: 'spinner-line-fade-default',
    rotate: 0,
    direction: 1,
    speed: 1,
    zIndex: 2e9,
    className: 'spinner',
    top: '50%',
    left: '50%',
    shadow: '0 0 1px transparent',
    position: 'absolute',
};
var Spinner = /** @class */ (function () {
    function Spinner(opts) {
        if (opts === void 0) { opts = {}; }
        this.opts = __assign(__assign({}, defaults), opts);
    }
    /**
     * Adds the spinner to the given target element. If this instance is already
     * spinning, it is automatically removed from its previous target by calling
     * stop() internally.
     */
    Spinner.prototype.spin = function (target) {
        this.stop();
        this.el = document.createElement('div');
        this.el.className = this.opts.className;
        this.el.setAttribute('role', 'progressbar');
        css(this.el, {
            position: this.opts.position,
            width: 0,
            zIndex: this.opts.zIndex,
            left: this.opts.left,
            top: this.opts.top,
            transform: "scale(" + this.opts.scale + ")",
        });
        if (target) {
            target.insertBefore(this.el, target.firstChild || null);
        }
        drawLines(this.el, this.opts);
        return this;
    };
    /**
     * Stops and removes the Spinner.
     * Stopped spinners may be reused by calling spin() again.
     */
    Spinner.prototype.stop = function () {
        if (this.el) {
            if (typeof requestAnimationFrame !== 'undefined') {
                cancelAnimationFrame(this.animateId);
            }
            else {
                clearTimeout(this.animateId);
            }
            if (this.el.parentNode) {
                this.el.parentNode.removeChild(this.el);
            }
            this.el = undefined;
        }
        return this;
    };
    return Spinner;
}());

/**
 * Sets multiple style properties at once.
 */
function css(el, props) {
    for (var prop in props) {
        el.style[prop] = props[prop];
    }
    return el;
}
/**
 * Returns the line color from the given string or array.
 */
function getColor(color, idx) {
    return typeof color == 'string' ? color : color[idx % color.length];
}
/**
 * Internal method that draws the individual lines.
 */
function drawLines(el, opts) {
    var borderRadius = (Math.round(opts.corners * opts.width * 500) / 1000) + 'px';
    var shadow = 'none';
    if (opts.shadow === true) {
        shadow = '0 2px 4px #000'; // default shadow
    }
    else if (typeof opts.shadow === 'string') {
        shadow = opts.shadow;
    }
    var shadows = parseBoxShadow(shadow);
    for (var i = 0; i < opts.lines; i++) {
        var degrees = ~~(360 / opts.lines * i + opts.rotate);
        var backgroundLine = css(document.createElement('div'), {
            position: 'absolute',
            top: -opts.width / 2 + "px",
            width: (opts.length + opts.width) + 'px',
            height: opts.width + 'px',
            background: getColor(opts.fadeColor, i),
            borderRadius: borderRadius,
            transformOrigin: 'left',
            transform: "rotate(" + degrees + "deg) translateX(" + opts.radius + "px)",
        });
        var delay = i * opts.direction / opts.lines / opts.speed;
        delay -= 1 / opts.speed; // so initial animation state will include trail
        var line = css(document.createElement('div'), {
            width: '100%',
            height: '100%',
            background: getColor(opts.color, i),
            borderRadius: borderRadius,
            boxShadow: normalizeShadow(shadows, degrees),
            animation: 1 / opts.speed + "s linear " + delay + "s infinite " + opts.animation,
        });
        backgroundLine.appendChild(line);
        el.appendChild(backgroundLine);
    }
}
function parseBoxShadow(boxShadow) {
    var regex = /^\s*([a-zA-Z]+\s+)?(-?\d+(\.\d+)?)([a-zA-Z]*)\s+(-?\d+(\.\d+)?)([a-zA-Z]*)(.*)$/;
    var shadows = [];
    for (var _i = 0, _a = boxShadow.split(','); _i < _a.length; _i++) {
        var shadow = _a[_i];
        var matches = shadow.match(regex);
        if (matches === null) {
            continue; // invalid syntax
        }
        var x = +matches[2];
        var y = +matches[5];
        var xUnits = matches[4];
        var yUnits = matches[7];
        if (x === 0 && !xUnits) {
            xUnits = yUnits;
        }
        if (y === 0 && !yUnits) {
            yUnits = xUnits;
        }
        if (xUnits !== yUnits) {
            continue; // units must match to use as coordinates
        }
        shadows.push({
            prefix: matches[1] || '',
            x: x,
            y: y,
            xUnits: xUnits,
            yUnits: yUnits,
            end: matches[8],
        });
    }
    return shadows;
}
/**
 * Modify box-shadow x/y offsets to counteract rotation
 */
function normalizeShadow(shadows, degrees) {
    var normalized = [];
    for (var _i = 0, shadows_1 = shadows; _i < shadows_1.length; _i++) {
        var shadow = shadows_1[_i];
        var xy = convertOffset(shadow.x, shadow.y, degrees);
        normalized.push(shadow.prefix + xy[0] + shadow.xUnits + ' ' + xy[1] + shadow.yUnits + shadow.end);
    }
    return normalized.join(', ');
}
function convertOffset(x, y, degrees) {
    var radians = degrees * Math.PI / 180;
    var sin = Math.sin(radians);
    var cos = Math.cos(radians);
    return [
        Math.round((x * cos + y * sin) * 1000) / 1000,
        Math.round((-x * sin + y * cos) * 1000) / 1000,
    ];
}


/***/ }),

/***/ "./node_modules/timers-browserify/main.js":
/*!************************************************!*\
  !*** ./node_modules/timers-browserify/main.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {var scope = (typeof global !== "undefined" && global) ||
            (typeof self !== "undefined" && self) ||
            window;
var apply = Function.prototype.apply;

// DOM APIs, for completeness

exports.setTimeout = function() {
  return new Timeout(apply.call(setTimeout, scope, arguments), clearTimeout);
};
exports.setInterval = function() {
  return new Timeout(apply.call(setInterval, scope, arguments), clearInterval);
};
exports.clearTimeout =
exports.clearInterval = function(timeout) {
  if (timeout) {
    timeout.close();
  }
};

function Timeout(id, clearFn) {
  this._id = id;
  this._clearFn = clearFn;
}
Timeout.prototype.unref = Timeout.prototype.ref = function() {};
Timeout.prototype.close = function() {
  this._clearFn.call(scope, this._id);
};

// Does not start the time, just sets up the members needed.
exports.enroll = function(item, msecs) {
  clearTimeout(item._idleTimeoutId);
  item._idleTimeout = msecs;
};

exports.unenroll = function(item) {
  clearTimeout(item._idleTimeoutId);
  item._idleTimeout = -1;
};

exports._unrefActive = exports.active = function(item) {
  clearTimeout(item._idleTimeoutId);

  var msecs = item._idleTimeout;
  if (msecs >= 0) {
    item._idleTimeoutId = setTimeout(function onTimeout() {
      if (item._onTimeout)
        item._onTimeout();
    }, msecs);
  }
};

// setimmediate attaches itself to the global object
__webpack_require__(/*! setimmediate */ "./node_modules/setimmediate/setImmediate.js");
// On some exotic environments, it's not clear which object `setimmediate` was
// able to install onto.  Search each possibility in the same order as the
// `setimmediate` library.
exports.setImmediate = (typeof self !== "undefined" && self.setImmediate) ||
                       (typeof global !== "undefined" && global.setImmediate) ||
                       (this && this.setImmediate);
exports.clearImmediate = (typeof self !== "undefined" && self.clearImmediate) ||
                         (typeof global !== "undefined" && global.clearImmediate) ||
                         (this && this.clearImmediate);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/tooltip.js/dist/esm/tooltip.js":
/*!*****************************************************!*\
  !*** ./node_modules/tooltip.js/dist/esm/tooltip.js ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var popper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! popper.js */ "./node_modules/popper.js/dist/esm/popper.js");
/**!
 * @fileOverview Kickass library to create and place poppers near their reference elements.
 * @version 1.3.3
 * @license
 * Copyright (c) 2016 Federico Zivolo and contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */


/**
 * Check if the given variable is a function
 * @method
 * @memberof Popper.Utils
 * @argument {Any} functionToCheck - variable to check
 * @returns {Boolean} answer to: is a function?
 */
function isFunction(functionToCheck) {
  var getType = {};
  return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
}

var classCallCheck = function (instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
};

var createClass = function () {
  function defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  return function (Constructor, protoProps, staticProps) {
    if (protoProps) defineProperties(Constructor.prototype, protoProps);
    if (staticProps) defineProperties(Constructor, staticProps);
    return Constructor;
  };
}();







var _extends = Object.assign || function (target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = arguments[i];

    for (var key in source) {
      if (Object.prototype.hasOwnProperty.call(source, key)) {
        target[key] = source[key];
      }
    }
  }

  return target;
};

var DEFAULT_OPTIONS = {
  container: false,
  delay: 0,
  html: false,
  placement: 'top',
  title: '',
  template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',
  trigger: 'hover focus',
  offset: 0,
  arrowSelector: '.tooltip-arrow, .tooltip__arrow',
  innerSelector: '.tooltip-inner, .tooltip__inner'
};

var Tooltip = function () {
  /**
   * Create a new Tooltip.js instance
   * @class Tooltip
   * @param {HTMLElement} reference - The DOM node used as reference of the tooltip (it can be a jQuery element).
   * @param {Object} options
   * @param {String} options.placement='top'
   *      Placement of the popper accepted values: `top(-start, -end), right(-start, -end), bottom(-start, -end),
   *      left(-start, -end)`
   * @param {String} [options.arrowSelector='.tooltip-arrow, .tooltip__arrow'] - className used to locate the DOM arrow element in the tooltip.
   * @param {String} [options.innerSelector='.tooltip-inner, .tooltip__inner'] - className used to locate the DOM inner element in the tooltip.
   * @param {HTMLElement|String|false} options.container=false - Append the tooltip to a specific element.
   * @param {Number|Object} options.delay=0
   *      Delay showing and hiding the tooltip (ms) - does not apply to manual trigger type.
   *      If a number is supplied, delay is applied to both hide/show.
   *      Object structure is: `{ show: 500, hide: 100 }`
   * @param {Boolean} options.html=false - Insert HTML into the tooltip. If false, the content will inserted with `textContent`.
   * @param {String} [options.template='<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>']
   *      Base HTML to used when creating the tooltip.
   *      The tooltip's `title` will be injected into the `.tooltip-inner` or `.tooltip__inner`.
   *      `.tooltip-arrow` or `.tooltip__arrow` will become the tooltip's arrow.
   *      The outermost wrapper element should have the `.tooltip` class.
   * @param {String|HTMLElement|TitleFunction} options.title='' - Default title value if `title` attribute isn't present.
   * @param {String} [options.trigger='hover focus']
   *      How tooltip is triggered - click, hover, focus, manual.
   *      You may pass multiple triggers; separate them with a space. `manual` cannot be combined with any other trigger.
   * @param {Boolean} options.closeOnClickOutside=false - Close a popper on click outside of the popper and reference element. This has effect only when options.trigger is 'click'.
   * @param {String|HTMLElement} options.boundariesElement
   *      The element used as boundaries for the tooltip. For more information refer to Popper.js'
   *      [boundariesElement docs](https://popper.js.org/popper-documentation.html)
   * @param {Number|String} options.offset=0 - Offset of the tooltip relative to its reference. For more information refer to Popper.js'
   *      [offset docs](https://popper.js.org/popper-documentation.html)
   * @param {Object} options.popperOptions={} - Popper options, will be passed directly to popper instance. For more information refer to Popper.js'
   *      [options docs](https://popper.js.org/popper-documentation.html)
   * @return {Object} instance - The generated tooltip instance
   */
  function Tooltip(reference, options) {
    classCallCheck(this, Tooltip);

    _initialiseProps.call(this);

    // apply user options over default ones
    options = _extends({}, DEFAULT_OPTIONS, options);

    reference.jquery && (reference = reference[0]);

    // cache reference and options
    this.reference = reference;
    this.options = options;

    // get events list
    var events = typeof options.trigger === 'string' ? options.trigger.split(' ').filter(function (trigger) {
      return ['click', 'hover', 'focus'].indexOf(trigger) !== -1;
    }) : [];

    // set initial state
    this._isOpen = false;
    this._popperOptions = {};

    // set event listeners
    this._setEventListeners(reference, events, options);
  }

  //
  // Public methods
  //

  /**
   * Reveals an element's tooltip. This is considered a "manual" triggering of the tooltip.
   * Tooltips with zero-length titles are never displayed.
   * @method Tooltip#show
   * @memberof Tooltip
   */


  /**
   * Hides an element’s tooltip. This is considered a “manual” triggering of the tooltip.
   * @method Tooltip#hide
   * @memberof Tooltip
   */


  /**
   * Hides and destroys an element’s tooltip.
   * @method Tooltip#dispose
   * @memberof Tooltip
   */


  /**
   * Toggles an element’s tooltip. This is considered a “manual” triggering of the tooltip.
   * @method Tooltip#toggle
   * @memberof Tooltip
   */


  /**
   * Updates the tooltip's title content
   * @method Tooltip#updateTitleContent
   * @memberof Tooltip
   * @param {String|HTMLElement} title - The new content to use for the title
   */


  //
  // Private methods
  //

  createClass(Tooltip, [{
    key: '_create',


    /**
     * Creates a new tooltip node
     * @memberof Tooltip
     * @private
     * @param {HTMLElement} reference
     * @param {String} template
     * @param {String|HTMLElement|TitleFunction} title
     * @param {Boolean} allowHtml
     * @return {HTMLElement} tooltipNode
     */
    value: function _create(reference, template, title, allowHtml) {
      // create tooltip element
      var tooltipGenerator = window.document.createElement('div');
      tooltipGenerator.innerHTML = template.trim();
      var tooltipNode = tooltipGenerator.childNodes[0];

      // add unique ID to our tooltip (needed for accessibility reasons)
      tooltipNode.id = 'tooltip_' + Math.random().toString(36).substr(2, 10);

      // set initial `aria-hidden` state to `false` (it's visible!)
      tooltipNode.setAttribute('aria-hidden', 'false');

      // add title to tooltip
      var titleNode = tooltipGenerator.querySelector(this.options.innerSelector);
      this._addTitleContent(reference, title, allowHtml, titleNode);

      // return the generated tooltip node
      return tooltipNode;
    }
  }, {
    key: '_addTitleContent',
    value: function _addTitleContent(reference, title, allowHtml, titleNode) {
      if (title.nodeType === 1 || title.nodeType === 11) {
        // if title is a element node or document fragment, append it only if allowHtml is true
        allowHtml && titleNode.appendChild(title);
      } else if (isFunction(title)) {
        // Recursively call ourself so that the return value of the function gets handled appropriately - either
        // as a dom node, a string, or even as another function.
        this._addTitleContent(reference, title.call(reference), allowHtml, titleNode);
      } else {
        // if it's just a simple text, set textContent or innerHtml depending by `allowHtml` value
        allowHtml ? titleNode.innerHTML = title : titleNode.textContent = title;
      }
    }
  }, {
    key: '_show',
    value: function _show(reference, options) {
      // don't show if it's already visible
      // or if it's not being showed
      if (this._isOpen && !this._isOpening) {
        return this;
      }
      this._isOpen = true;

      // if the tooltipNode already exists, just show it
      if (this._tooltipNode) {
        this._tooltipNode.style.visibility = 'visible';
        this._tooltipNode.setAttribute('aria-hidden', 'false');
        this.popperInstance.update();
        return this;
      }

      // get title
      var title = reference.getAttribute('title') || options.title;

      // don't show tooltip if no title is defined
      if (!title) {
        return this;
      }

      // create tooltip node
      var tooltipNode = this._create(reference, options.template, title, options.html);

      // Add `aria-describedby` to our reference element for accessibility reasons
      reference.setAttribute('aria-describedby', tooltipNode.id);

      // append tooltip to container
      var container = this._findContainer(options.container, reference);

      this._append(tooltipNode, container);

      this._popperOptions = _extends({}, options.popperOptions, {
        placement: options.placement
      });

      this._popperOptions.modifiers = _extends({}, this._popperOptions.modifiers, {
        arrow: _extends({}, this._popperOptions.modifiers && this._popperOptions.modifiers.arrow, {
          element: options.arrowSelector
        }),
        offset: _extends({}, this._popperOptions.modifiers && this._popperOptions.modifiers.offset, {
          offset: options.offset || this._popperOptions.modifiers && this._popperOptions.modifiers.offset && this._popperOptions.modifiers.offset.offset || options.offset
        })
      });

      if (options.boundariesElement) {
        this._popperOptions.modifiers.preventOverflow = {
          boundariesElement: options.boundariesElement
        };
      }

      this.popperInstance = new popper_js__WEBPACK_IMPORTED_MODULE_0__["default"](reference, tooltipNode, this._popperOptions);

      this._tooltipNode = tooltipNode;

      return this;
    }
  }, {
    key: '_hide',
    value: function _hide() /*reference, options*/{
      // don't hide if it's already hidden
      if (!this._isOpen) {
        return this;
      }

      this._isOpen = false;

      // hide tooltipNode
      this._tooltipNode.style.visibility = 'hidden';
      this._tooltipNode.setAttribute('aria-hidden', 'true');

      return this;
    }
  }, {
    key: '_dispose',
    value: function _dispose() {
      var _this = this;

      // remove event listeners first to prevent any unexpected behaviour
      this._events.forEach(function (_ref) {
        var func = _ref.func,
            event = _ref.event;

        _this.reference.removeEventListener(event, func);
      });
      this._events = [];

      if (this._tooltipNode) {
        this._hide();

        // destroy instance
        this.popperInstance.destroy();

        // destroy tooltipNode if removeOnDestroy is not set, as popperInstance.destroy() already removes the element
        if (!this.popperInstance.options.removeOnDestroy) {
          this._tooltipNode.parentNode.removeChild(this._tooltipNode);
          this._tooltipNode = null;
        }
      }
      return this;
    }
  }, {
    key: '_findContainer',
    value: function _findContainer(container, reference) {
      // if container is a query, get the relative element
      if (typeof container === 'string') {
        container = window.document.querySelector(container);
      } else if (container === false) {
        // if container is `false`, set it to reference parent
        container = reference.parentNode;
      }
      return container;
    }

    /**
     * Append tooltip to container
     * @memberof Tooltip
     * @private
     * @param {HTMLElement} tooltipNode
     * @param {HTMLElement|String|false} container
     */

  }, {
    key: '_append',
    value: function _append(tooltipNode, container) {
      container.appendChild(tooltipNode);
    }
  }, {
    key: '_setEventListeners',
    value: function _setEventListeners(reference, events, options) {
      var _this2 = this;

      var directEvents = [];
      var oppositeEvents = [];

      events.forEach(function (event) {
        switch (event) {
          case 'hover':
            directEvents.push('mouseenter');
            oppositeEvents.push('mouseleave');
            break;
          case 'focus':
            directEvents.push('focus');
            oppositeEvents.push('blur');
            break;
          case 'click':
            directEvents.push('click');
            oppositeEvents.push('click');
            break;
        }
      });

      // schedule show tooltip
      directEvents.forEach(function (event) {
        var func = function func(evt) {
          if (_this2._isOpening === true) {
            return;
          }
          evt.usedByTooltip = true;
          _this2._scheduleShow(reference, options.delay, options, evt);
        };
        _this2._events.push({ event: event, func: func });
        reference.addEventListener(event, func);
      });

      // schedule hide tooltip
      oppositeEvents.forEach(function (event) {
        var func = function func(evt) {
          if (evt.usedByTooltip === true) {
            return;
          }
          _this2._scheduleHide(reference, options.delay, options, evt);
        };
        _this2._events.push({ event: event, func: func });
        reference.addEventListener(event, func);
        if (event === 'click' && options.closeOnClickOutside) {
          document.addEventListener('mousedown', function (e) {
            if (!_this2._isOpening) {
              return;
            }
            var popper = _this2.popperInstance.popper;
            if (reference.contains(e.target) || popper.contains(e.target)) {
              return;
            }
            func(e);
          }, true);
        }
      });
    }
  }, {
    key: '_scheduleShow',
    value: function _scheduleShow(reference, delay, options /*, evt */) {
      var _this3 = this;

      this._isOpening = true;
      // defaults to 0
      var computedDelay = delay && delay.show || delay || 0;
      this._showTimeout = window.setTimeout(function () {
        return _this3._show(reference, options);
      }, computedDelay);
    }
  }, {
    key: '_scheduleHide',
    value: function _scheduleHide(reference, delay, options, evt) {
      var _this4 = this;

      this._isOpening = false;
      // defaults to 0
      var computedDelay = delay && delay.hide || delay || 0;
      window.clearTimeout(this._showTimeout);
      window.setTimeout(function () {
        if (_this4._isOpen === false) {
          return;
        }
        if (!document.body.contains(_this4._tooltipNode)) {
          return;
        }

        // if we are hiding because of a mouseleave, we must check that the new
        // reference isn't the tooltip, because in this case we don't want to hide it
        if (evt.type === 'mouseleave') {
          var isSet = _this4._setTooltipNodeEvent(evt, reference, delay, options);

          // if we set the new event, don't hide the tooltip yet
          // the new event will take care to hide it if necessary
          if (isSet) {
            return;
          }
        }

        _this4._hide(reference, options);
      }, computedDelay);
    }
  }, {
    key: '_updateTitleContent',
    value: function _updateTitleContent(title) {
      if (typeof this._tooltipNode === 'undefined') {
        if (typeof this.options.title !== 'undefined') {
          this.options.title = title;
        }
        return;
      }
      var titleNode = this._tooltipNode.querySelector(this.options.innerSelector);
      this._clearTitleContent(titleNode, this.options.html, this.reference.getAttribute('title') || this.options.title);
      this._addTitleContent(this.reference, title, this.options.html, titleNode);
      this.options.title = title;
      this.popperInstance.update();
    }
  }, {
    key: '_clearTitleContent',
    value: function _clearTitleContent(titleNode, allowHtml, lastTitle) {
      if (lastTitle.nodeType === 1 || lastTitle.nodeType === 11) {
        allowHtml && titleNode.removeChild(lastTitle);
      } else {
        allowHtml ? titleNode.innerHTML = '' : titleNode.textContent = '';
      }
    }
  }]);
  return Tooltip;
}();

/**
 * Title function, its context is the Tooltip instance.
 * @memberof Tooltip
 * @callback TitleFunction
 * @return {String} placement - The desired title.
 */


var _initialiseProps = function _initialiseProps() {
  var _this5 = this;

  this.show = function () {
    return _this5._show(_this5.reference, _this5.options);
  };

  this.hide = function () {
    return _this5._hide();
  };

  this.dispose = function () {
    return _this5._dispose();
  };

  this.toggle = function () {
    if (_this5._isOpen) {
      return _this5.hide();
    } else {
      return _this5.show();
    }
  };

  this.updateTitleContent = function (title) {
    return _this5._updateTitleContent(title);
  };

  this._events = [];

  this._setTooltipNodeEvent = function (evt, reference, delay, options) {
    var relatedreference = evt.relatedreference || evt.toElement || evt.relatedTarget;

    var callback = function callback(evt2) {
      var relatedreference2 = evt2.relatedreference || evt2.toElement || evt2.relatedTarget;

      // Remove event listener after call
      _this5._tooltipNode.removeEventListener(evt.type, callback);

      // If the new reference is not the reference element
      if (!reference.contains(relatedreference2)) {
        // Schedule to hide tooltip
        _this5._scheduleHide(reference, options.delay, options, evt2);
      }
    };

    if (_this5._tooltipNode.contains(relatedreference)) {
      // listen to mouseleave on the tooltip element to be able to hide the tooltip
      _this5._tooltipNode.addEventListener(evt.type, callback);
      return true;
    }

    return false;
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Tooltip);


/***/ }),

/***/ "./node_modules/webpack/buildin/global.js":
/*!***********************************!*\
  !*** (webpack)/buildin/global.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || new Function("return this")();
} catch (e) {
	// This works if the window reference is available
	if (typeof window === "object") g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),

/***/ "./node_modules/zepto/src/data.js":
/*!****************************************!*\
  !*** ./node_modules/zepto/src/data.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

//     Zepto.js
//     (c) 2010-2016 Thomas Fuchs
//     Zepto.js may be freely distributed under the MIT license.

// The following code is heavily inspired by jQuery's $.fn.data()

;(function($){
  var data = {}, dataAttr = $.fn.data, camelize = $.camelCase,
    exp = $.expando = 'Zepto' + (+new Date()), emptyArray = []

  // Get value from node:
  // 1. first try key as given,
  // 2. then try camelized key,
  // 3. fall back to reading "data-*" attribute.
  function getData(node, name) {
    var id = node[exp], store = id && data[id]
    if (name === undefined) return store || setData(node)
    else {
      if (store) {
        if (name in store) return store[name]
        var camelName = camelize(name)
        if (camelName in store) return store[camelName]
      }
      return dataAttr.call($(node), name)
    }
  }

  // Store value under camelized key on node
  function setData(node, name, value) {
    var id = node[exp] || (node[exp] = ++$.uuid),
      store = data[id] || (data[id] = attributeData(node))
    if (name !== undefined) store[camelize(name)] = value
    return store
  }

  // Read all "data-*" attributes from a node
  function attributeData(node) {
    var store = {}
    $.each(node.attributes || emptyArray, function(i, attr){
      if (attr.name.indexOf('data-') == 0)
        store[camelize(attr.name.replace('data-', ''))] =
          $.zepto.deserializeValue(attr.value)
    })
    return store
  }

  $.fn.data = function(name, value) {
    return value === undefined ?
      // set multiple values via object
      $.isPlainObject(name) ?
        this.each(function(i, node){
          $.each(name, function(key, value){ setData(node, key, value) })
        }) :
        // get value from first element
        (0 in this ? getData(this[0], name) : undefined) :
      // set value on all elements
      this.each(function(){ setData(this, name, value) })
  }

  $.data = function(elem, name, value) {
    return $(elem).data(name, value)
  }

  $.hasData = function(elem) {
    var id = elem[exp], store = id && data[id]
    return store ? !$.isEmptyObject(store) : false
  }

  $.fn.removeData = function(names) {
    if (typeof names == 'string') names = names.split(/\s+/)
    return this.each(function(){
      var id = this[exp], store = id && data[id]
      if (store) $.each(names || store, function(key){
        delete store[names ? camelize(this) : key]
      })
    })
  }

  // Generate extended `remove` and `empty` functions
  ;['remove', 'empty'].forEach(function(methodName){
    var origFn = $.fn[methodName]
    $.fn[methodName] = function() {
      var elements = this.find('*')
      if (methodName === 'remove') elements = elements.add(this)
      elements.removeData()
      return origFn.call(this)
    }
  })
})(Zepto)


/***/ }),

/***/ "./node_modules/zepto/src/fx.js":
/*!**************************************!*\
  !*** ./node_modules/zepto/src/fx.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

//     Zepto.js
//     (c) 2010-2016 Thomas Fuchs
//     Zepto.js may be freely distributed under the MIT license.

;(function($, undefined){
  var prefix = '', eventPrefix,
    vendors = { Webkit: 'webkit', Moz: '', O: 'o' },
    testEl = document.createElement('div'),
    supportedTransforms = /^((translate|rotate|scale)(X|Y|Z|3d)?|matrix(3d)?|perspective|skew(X|Y)?)$/i,
    transform,
    transitionProperty, transitionDuration, transitionTiming, transitionDelay,
    animationName, animationDuration, animationTiming, animationDelay,
    cssReset = {}

  function dasherize(str) { return str.replace(/([A-Z])/g, '-$1').toLowerCase() }
  function normalizeEvent(name) { return eventPrefix ? eventPrefix + name : name.toLowerCase() }

  if (testEl.style.transform === undefined) $.each(vendors, function(vendor, event){
    if (testEl.style[vendor + 'TransitionProperty'] !== undefined) {
      prefix = '-' + vendor.toLowerCase() + '-'
      eventPrefix = event
      return false
    }
  })

  transform = prefix + 'transform'
  cssReset[transitionProperty = prefix + 'transition-property'] =
  cssReset[transitionDuration = prefix + 'transition-duration'] =
  cssReset[transitionDelay    = prefix + 'transition-delay'] =
  cssReset[transitionTiming   = prefix + 'transition-timing-function'] =
  cssReset[animationName      = prefix + 'animation-name'] =
  cssReset[animationDuration  = prefix + 'animation-duration'] =
  cssReset[animationDelay     = prefix + 'animation-delay'] =
  cssReset[animationTiming    = prefix + 'animation-timing-function'] = ''

  $.fx = {
    off: (eventPrefix === undefined && testEl.style.transitionProperty === undefined),
    speeds: { _default: 400, fast: 200, slow: 600 },
    cssPrefix: prefix,
    transitionEnd: normalizeEvent('TransitionEnd'),
    animationEnd: normalizeEvent('AnimationEnd')
  }

  $.fn.animate = function(properties, duration, ease, callback, delay){
    if ($.isFunction(duration))
      callback = duration, ease = undefined, duration = undefined
    if ($.isFunction(ease))
      callback = ease, ease = undefined
    if ($.isPlainObject(duration))
      ease = duration.easing, callback = duration.complete, delay = duration.delay, duration = duration.duration
    if (duration) duration = (typeof duration == 'number' ? duration :
                    ($.fx.speeds[duration] || $.fx.speeds._default)) / 1000
    if (delay) delay = parseFloat(delay) / 1000
    return this.anim(properties, duration, ease, callback, delay)
  }

  $.fn.anim = function(properties, duration, ease, callback, delay){
    var key, cssValues = {}, cssProperties, transforms = '',
        that = this, wrappedCallback, endEvent = $.fx.transitionEnd,
        fired = false

    if (duration === undefined) duration = $.fx.speeds._default / 1000
    if (delay === undefined) delay = 0
    if ($.fx.off) duration = 0

    if (typeof properties == 'string') {
      // keyframe animation
      cssValues[animationName] = properties
      cssValues[animationDuration] = duration + 's'
      cssValues[animationDelay] = delay + 's'
      cssValues[animationTiming] = (ease || 'linear')
      endEvent = $.fx.animationEnd
    } else {
      cssProperties = []
      // CSS transitions
      for (key in properties)
        if (supportedTransforms.test(key)) transforms += key + '(' + properties[key] + ') '
        else cssValues[key] = properties[key], cssProperties.push(dasherize(key))

      if (transforms) cssValues[transform] = transforms, cssProperties.push(transform)
      if (duration > 0 && typeof properties === 'object') {
        cssValues[transitionProperty] = cssProperties.join(', ')
        cssValues[transitionDuration] = duration + 's'
        cssValues[transitionDelay] = delay + 's'
        cssValues[transitionTiming] = (ease || 'linear')
      }
    }

    wrappedCallback = function(event){
      if (typeof event !== 'undefined') {
        if (event.target !== event.currentTarget) return // makes sure the event didn't bubble from "below"
        $(event.target).unbind(endEvent, wrappedCallback)
      } else
        $(this).unbind(endEvent, wrappedCallback) // triggered by setTimeout

      fired = true
      $(this).css(cssReset)
      callback && callback.call(this)
    }
    if (duration > 0){
      this.bind(endEvent, wrappedCallback)
      // transitionEnd is not always firing on older Android phones
      // so make sure it gets fired
      setTimeout(function(){
        if (fired) return
        wrappedCallback.call(that)
      }, ((duration + delay) * 1000) + 25)
    }

    // trigger page reflow so new elements can animate
    this.size() && this.get(0).clientLeft

    this.css(cssValues)

    if (duration <= 0) setTimeout(function() {
      that.each(function(){ wrappedCallback.call(this) })
    }, 0)

    return this
  }

  testEl = null
})(Zepto)


/***/ }),

/***/ "./node_modules/zepto/src/fx_methods.js":
/*!**********************************************!*\
  !*** ./node_modules/zepto/src/fx_methods.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

//     Zepto.js
//     (c) 2010-2016 Thomas Fuchs
//     Zepto.js may be freely distributed under the MIT license.

;(function($, undefined){
  var document = window.document, docElem = document.documentElement,
    origShow = $.fn.show, origHide = $.fn.hide, origToggle = $.fn.toggle

  function anim(el, speed, opacity, scale, callback) {
    if (typeof speed == 'function' && !callback) callback = speed, speed = undefined
    var props = { opacity: opacity }
    if (scale) {
      props.scale = scale
      el.css($.fx.cssPrefix + 'transform-origin', '0 0')
    }
    return el.animate(props, speed, null, callback)
  }

  function hide(el, speed, scale, callback) {
    return anim(el, speed, 0, scale, function(){
      origHide.call($(this))
      callback && callback.call(this)
    })
  }

  $.fn.show = function(speed, callback) {
    origShow.call(this)
    if (speed === undefined) speed = 0
    else this.css('opacity', 0)
    return anim(this, speed, 1, '1,1', callback)
  }

  $.fn.hide = function(speed, callback) {
    if (speed === undefined) return origHide.call(this)
    else return hide(this, speed, '0,0', callback)
  }

  $.fn.toggle = function(speed, callback) {
    if (speed === undefined || typeof speed == 'boolean')
      return origToggle.call(this, speed)
    else return this.each(function(){
      var el = $(this)
      el[el.css('display') == 'none' ? 'show' : 'hide'](speed, callback)
    })
  }

  $.fn.fadeTo = function(speed, opacity, callback) {
    return anim(this, speed, opacity, null, callback)
  }

  $.fn.fadeIn = function(speed, callback) {
    var target = this.css('opacity')
    if (target > 0) this.css('opacity', 0)
    else target = 1
    return origShow.call(this).fadeTo(speed, target, callback)
  }

  $.fn.fadeOut = function(speed, callback) {
    return hide(this, speed, null, callback)
  }

  $.fn.fadeToggle = function(speed, callback) {
    return this.each(function(){
      var el = $(this)
      el[
        (el.css('opacity') == 0 || el.css('display') == 'none') ? 'fadeIn' : 'fadeOut'
      ](speed, callback)
    })
  }

})(Zepto)


/***/ }),

/***/ "./node_modules/zepto/src/selector.js":
/*!********************************************!*\
  !*** ./node_modules/zepto/src/selector.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

//     Zepto.js
//     (c) 2010-2016 Thomas Fuchs
//     Zepto.js may be freely distributed under the MIT license.

;(function($){
  var zepto = $.zepto, oldQsa = zepto.qsa, oldMatches = zepto.matches

  function visible(elem){
    elem = $(elem)
    return !!(elem.width() || elem.height()) && elem.css("display") !== "none"
  }

  // Implements a subset from:
  // http://api.jquery.com/category/selectors/jquery-selector-extensions/
  //
  // Each filter function receives the current index, all nodes in the
  // considered set, and a value if there were parentheses. The value
  // of `this` is the node currently being considered. The function returns the
  // resulting node(s), null, or undefined.
  //
  // Complex selectors are not supported:
  //   li:has(label:contains("foo")) + li:has(label:contains("bar"))
  //   ul.inner:first > li
  var filters = $.expr[':'] = {
    visible:  function(){ if (visible(this)) return this },
    hidden:   function(){ if (!visible(this)) return this },
    selected: function(){ if (this.selected) return this },
    checked:  function(){ if (this.checked) return this },
    parent:   function(){ return this.parentNode },
    first:    function(idx){ if (idx === 0) return this },
    last:     function(idx, nodes){ if (idx === nodes.length - 1) return this },
    eq:       function(idx, _, value){ if (idx === value) return this },
    contains: function(idx, _, text){ if ($(this).text().indexOf(text) > -1) return this },
    has:      function(idx, _, sel){ if (zepto.qsa(this, sel).length) return this }
  }

  var filterRe = new RegExp('(.*):(\\w+)(?:\\(([^)]+)\\))?$\\s*'),
      childRe  = /^\s*>/,
      classTag = 'Zepto' + (+new Date())

  function process(sel, fn) {
    // quote the hash in `a[href^=#]` expression
    sel = sel.replace(/=#\]/g, '="#"]')
    var filter, arg, match = filterRe.exec(sel)
    if (match && match[2] in filters) {
      filter = filters[match[2]], arg = match[3]
      sel = match[1]
      if (arg) {
        var num = Number(arg)
        if (isNaN(num)) arg = arg.replace(/^["']|["']$/g, '')
        else arg = num
      }
    }
    return fn(sel, filter, arg)
  }

  zepto.qsa = function(node, selector) {
    return process(selector, function(sel, filter, arg){
      try {
        var taggedParent
        if (!sel && filter) sel = '*'
        else if (childRe.test(sel))
          // support "> *" child queries by tagging the parent node with a
          // unique class and prepending that classname onto the selector
          taggedParent = $(node).addClass(classTag), sel = '.'+classTag+' '+sel

        var nodes = oldQsa(node, sel)
      } catch(e) {
        console.error('error performing selector: %o', selector)
        throw e
      } finally {
        if (taggedParent) taggedParent.removeClass(classTag)
      }
      return !filter ? nodes :
        zepto.uniq($.map(nodes, function(n, i){ return filter.call(n, i, nodes, arg) }))
    })
  }

  zepto.matches = function(node, selector){
    return process(selector, function(sel, filter, arg){
      return (!sel || oldMatches(node, sel)) &&
        (!filter || filter.call(node, null, arg) === node)
    })
  }
})(Zepto)


/***/ }),

/***/ "./src/admin/Admin.ts":
/*!****************************!*\
  !*** ./src/admin/Admin.ts ***!
  \****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Admin; });
/* harmony import */ var _babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/assertThisInitialized */ "./node_modules/@babel/runtime/helpers/esm/assertThisInitialized.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _components_HeaderPrimary__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/HeaderPrimary */ "./src/admin/components/HeaderPrimary.tsx");
/* harmony import */ var _components_HeaderSecondary__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/HeaderSecondary */ "./src/admin/components/HeaderSecondary.tsx");
/* harmony import */ var _routes__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./routes */ "./src/admin/routes.ts");
/* harmony import */ var _common_Application__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../common/Application */ "./src/common/Application.ts");
/* harmony import */ var _common_components_Navigation__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../common/components/Navigation */ "./src/common/components/Navigation.tsx");
/* harmony import */ var _components_AdminNav__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/AdminNav */ "./src/admin/components/AdminNav.tsx");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }








var Admin = /*#__PURE__*/function (_Application) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(Admin, _Application);

  var _super = _createSuper(Admin);

  function Admin() {
    var _this;

    _this = _Application.call(this) || this;
    _this.extensionSettings = {};
    _this.history = {
      canGoBack: function canGoBack() {
        return true;
      },
      getPrevious: function getPrevious() {},
      backUrl: function backUrl() {
        return _this.forum.attribute('baseUrl');
      },
      back: function back() {
        window.location = this.backUrl();
      }
    };
    _this.data = void 0;
    Object(_routes__WEBPACK_IMPORTED_MODULE_6__["default"])(Object(_babel_runtime_helpers_esm_assertThisInitialized__WEBPACK_IMPORTED_MODULE_0__["default"])(_this));
    return _this;
  }
  /**
   * @inheritdoc
   */


  var _proto = Admin.prototype;

  _proto.mount = function mount() {
    m.mount(document.getElementById('app-navigation'), new _common_components_Navigation__WEBPACK_IMPORTED_MODULE_8__["default"]({
      className: 'App-backControl',
      drawer: true
    }));
    m.mount(document.getElementById('header-navigation'), new _common_components_Navigation__WEBPACK_IMPORTED_MODULE_8__["default"]());
    m.mount(document.getElementById('header-primary'), new _components_HeaderPrimary__WEBPACK_IMPORTED_MODULE_4__["default"]());
    m.mount(document.getElementById('header-secondary'), new _components_HeaderSecondary__WEBPACK_IMPORTED_MODULE_5__["default"]());
    m.mount(document.getElementById('admin-navigation'), new _components_AdminNav__WEBPACK_IMPORTED_MODULE_9__["default"]());
    m.route.prefix = '#';

    _Application.prototype.mount.call(this); // If an extension has just been enabled, then we will run its settings
    // callback.


    var enabled = localStorage.getItem('enabledExtension');

    if (enabled && this.extensionSettings[enabled]) {
      this.extensionSettings[enabled]();
      localStorage.removeItem('enabledExtension');
    }
  };

  _proto.getRequiredPermissions = function getRequiredPermissions(permission) {
    var required = [];

    if (permission === 'startDiscussion' || permission.indexOf('discussion.') === 0) {
      required.push('viewDiscussions');
    }

    if (permission === 'discussion.delete') {
      required.push('discussion.hide');
    }

    if (permission === 'discussion.deletePosts') {
      required.push('discussion.hidePosts');
    }

    return required;
  };

  return Admin;
}(_common_Application__WEBPACK_IMPORTED_MODULE_7__["default"]);



/***/ }),

/***/ "./src/admin/app.ts":
/*!**************************!*\
  !*** ./src/admin/app.ts ***!
  \**************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Admin */ "./src/admin/Admin.ts");

var app = new _Admin__WEBPACK_IMPORTED_MODULE_0__["default"](); // @ts-ignore

window.app = app;
/* harmony default export */ __webpack_exports__["default"] = (app);

/***/ }),

/***/ "./src/admin/compat.ts":
/*!*****************************!*\
  !*** ./src/admin/compat.ts ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _common_compat__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../common/compat */ "./src/common/compat.ts");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/saveSettings */ "./src/admin/utils/saveSettings.ts");
/* harmony import */ var _components_SettingDropdown__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/SettingDropdown */ "./src/admin/components/SettingDropdown.tsx");
/* harmony import */ var _components_EditCustomFooterModal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/EditCustomFooterModal */ "./src/admin/components/EditCustomFooterModal.tsx");
/* harmony import */ var _components_SessionDropdown__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/SessionDropdown */ "./src/admin/components/SessionDropdown.tsx");
/* harmony import */ var _components_HeaderPrimary__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/HeaderPrimary */ "./src/admin/components/HeaderPrimary.tsx");
/* harmony import */ var _components_AppearancePage__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/AppearancePage */ "./src/admin/components/AppearancePage.tsx");
/* harmony import */ var _components_Page__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./components/Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _components_StatusWidget__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/StatusWidget */ "./src/admin/components/StatusWidget.tsx");
/* harmony import */ var _components_HeaderSecondary__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./components/HeaderSecondary */ "./src/admin/components/HeaderSecondary.tsx");
/* harmony import */ var _components_SettingsModal__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./components/SettingsModal */ "./src/admin/components/SettingsModal.tsx");
/* harmony import */ var _components_DashboardWidget__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./components/DashboardWidget */ "./src/admin/components/DashboardWidget.tsx");
/* harmony import */ var _components_AddExtensionModal__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./components/AddExtensionModal */ "./src/admin/components/AddExtensionModal.tsx");
/* harmony import */ var _components_ExtensionsPage__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./components/ExtensionsPage */ "./src/admin/components/ExtensionsPage.tsx");
/* harmony import */ var _components_AdminLinkButton__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./components/AdminLinkButton */ "./src/admin/components/AdminLinkButton.tsx");
/* harmony import */ var _components_PermissionGrid__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./components/PermissionGrid */ "./src/admin/components/PermissionGrid.tsx");
/* harmony import */ var _components_MailPage__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./components/MailPage */ "./src/admin/components/MailPage.tsx");
/* harmony import */ var _components_UploadImageButton__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./components/UploadImageButton */ "./src/admin/components/UploadImageButton.tsx");
/* harmony import */ var _components_LoadingModal__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./components/LoadingModal */ "./src/admin/components/LoadingModal.tsx");
/* harmony import */ var _components_DashboardPage__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./components/DashboardPage */ "./src/admin/components/DashboardPage.tsx");
/* harmony import */ var _components_BasicsPage__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./components/BasicsPage */ "./src/admin/components/BasicsPage.tsx");
/* harmony import */ var _components_EditCustomHeaderModal__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./components/EditCustomHeaderModal */ "./src/admin/components/EditCustomHeaderModal.tsx");
/* harmony import */ var _components_PermissionsPage__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./components/PermissionsPage */ "./src/admin/components/PermissionsPage.tsx");
/* harmony import */ var _components_PermissionDropdown__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./components/PermissionDropdown */ "./src/admin/components/PermissionDropdown.tsx");
/* harmony import */ var _components_AdminNav__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./components/AdminNav */ "./src/admin/components/AdminNav.tsx");
/* harmony import */ var _components_EditCustomCssModal__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./components/EditCustomCssModal */ "./src/admin/components/EditCustomCssModal.tsx");
/* harmony import */ var _components_EditGroupModal__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./components/EditGroupModal */ "./src/admin/components/EditGroupModal.tsx");
/* harmony import */ var _routes__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./routes */ "./src/admin/routes.ts");
/* harmony import */ var _Admin__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./Admin */ "./src/admin/Admin.ts");






























/* harmony default export */ __webpack_exports__["default"] = (Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(_common_compat__WEBPACK_IMPORTED_MODULE_1__["default"], {
  'utils/saveSettings': _utils_saveSettings__WEBPACK_IMPORTED_MODULE_2__["default"],
  'components/SettingDropdown': _components_SettingDropdown__WEBPACK_IMPORTED_MODULE_3__["default"],
  'components/EditCustomFooterModal': _components_EditCustomFooterModal__WEBPACK_IMPORTED_MODULE_4__["default"],
  'components/SessionDropdown': _components_SessionDropdown__WEBPACK_IMPORTED_MODULE_5__["default"],
  'components/HeaderPrimary': _components_HeaderPrimary__WEBPACK_IMPORTED_MODULE_6__["default"],
  'components/AppearancePage': _components_AppearancePage__WEBPACK_IMPORTED_MODULE_7__["default"],
  'components/Page': _components_Page__WEBPACK_IMPORTED_MODULE_8__["default"],
  'components/StatusWidget': _components_StatusWidget__WEBPACK_IMPORTED_MODULE_9__["default"],
  'components/HeaderSecondary': _components_HeaderSecondary__WEBPACK_IMPORTED_MODULE_10__["default"],
  'components/SettingsModal': _components_SettingsModal__WEBPACK_IMPORTED_MODULE_11__["default"],
  'components/DashboardWidget': _components_DashboardWidget__WEBPACK_IMPORTED_MODULE_12__["default"],
  'components/AddExtensionModal': _components_AddExtensionModal__WEBPACK_IMPORTED_MODULE_13__["default"],
  'components/ExtensionsPage': _components_ExtensionsPage__WEBPACK_IMPORTED_MODULE_14__["default"],
  'components/AdminLinkButton': _components_AdminLinkButton__WEBPACK_IMPORTED_MODULE_15__["default"],
  'components/PermissionGrid': _components_PermissionGrid__WEBPACK_IMPORTED_MODULE_16__["default"],
  'components/MailPage': _components_MailPage__WEBPACK_IMPORTED_MODULE_17__["default"],
  'components/UploadImageButton': _components_UploadImageButton__WEBPACK_IMPORTED_MODULE_18__["default"],
  'components/LoadingModal': _components_LoadingModal__WEBPACK_IMPORTED_MODULE_19__["default"],
  'components/DashboardPage': _components_DashboardPage__WEBPACK_IMPORTED_MODULE_20__["default"],
  'components/BasicsPage': _components_BasicsPage__WEBPACK_IMPORTED_MODULE_21__["default"],
  'components/EditCustomHeaderModal': _components_EditCustomHeaderModal__WEBPACK_IMPORTED_MODULE_22__["default"],
  'components/PermissionsPage': _components_PermissionsPage__WEBPACK_IMPORTED_MODULE_23__["default"],
  'components/PermissionDropdown': _components_PermissionDropdown__WEBPACK_IMPORTED_MODULE_24__["default"],
  'components/AdminNav': _components_AdminNav__WEBPACK_IMPORTED_MODULE_25__["default"],
  'components/EditCustomCssModal': _components_EditCustomCssModal__WEBPACK_IMPORTED_MODULE_26__["default"],
  'components/EditGroupModal': _components_EditGroupModal__WEBPACK_IMPORTED_MODULE_27__["default"],
  routes: _routes__WEBPACK_IMPORTED_MODULE_28__["default"],
  Admin: _Admin__WEBPACK_IMPORTED_MODULE_29__["default"]
}));

/***/ }),

/***/ "./src/admin/components/AddExtensionModal.tsx":
/*!****************************************************!*\
  !*** ./src/admin/components/AddExtensionModal.tsx ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return AddExtensionModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_Modal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Modal */ "./src/common/components/Modal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




var AddExtensionModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(AddExtensionModal, _Modal);

  var _super = _createSuper(AddExtensionModal);

  function AddExtensionModal() {
    return _Modal.apply(this, arguments) || this;
  }

  var _proto = AddExtensionModal.prototype;

  _proto.className = function className() {
    return 'AddExtensionModal Modal--small';
  };

  _proto.title = function title() {
    return _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.add_extension.title');
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, m("p", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.add_extension.temporary_text')), m("p", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.add_extension.install_text', {
      a: m("a", {
        href: "https://discuss.flarum.org/t/extensions",
        target: "_blank"
      })
    })), m("p", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.add_extension.developer_text', {
      a: m("a", {
        href: "http://flarum.org/docs/extend",
        target: "_blank"
      })
    })));
  };

  return AddExtensionModal;
}(_common_components_Modal__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/AdminLinkButton.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/AdminLinkButton.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return AdminLinkButton; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/LinkButton */ "./src/common/components/LinkButton.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var AdminLinkButton = /*#__PURE__*/function (_LinkButton) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(AdminLinkButton, _LinkButton);

  var _super = _createSuper(AdminLinkButton);

  function AdminLinkButton() {
    return _LinkButton.apply(this, arguments) || this;
  }

  var _proto = AdminLinkButton.prototype;

  _proto.getButtonContent = function getButtonContent() {
    var content = _LinkButton.prototype.getButtonContent.call(this, this.props.icon, this.props.loading, this.props.children);

    content.push(m("div", {
      className: "AdminLinkButton-description"
    }, this.props.description));
    return content;
  };

  return AdminLinkButton;
}(_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/AdminNav.tsx":
/*!*******************************************!*\
  !*** ./src/admin/components/AdminNav.tsx ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return AdminNav; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AdminLinkButton */ "./src/admin/components/AdminLinkButton.tsx");
/* harmony import */ var _common_components_SelectDropdown__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/SelectDropdown */ "./src/common/components/SelectDropdown.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






var AdminNav = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(AdminNav, _Component);

  var _super = _createSuper(AdminNav);

  function AdminNav() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = AdminNav.prototype;

  _proto.view = function view() {
    return m(_common_components_SelectDropdown__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "AdminNav App-titleControl",
      buttonClassName: "Button"
    }, this.items().toArray());
  }
  /**
   * Build an item list of links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  ;

  _proto.items = function items() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    items.add('dashboard', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('dashboard'),
      icon: 'far fa-chart-bar',
      children: app.translator.trans('core.admin.nav.dashboard_button'),
      description: app.translator.trans('core.admin.nav.dashboard_text')
    }));
    items.add('basics', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('basics'),
      icon: 'fas fa-pencil-alt',
      children: app.translator.trans('core.admin.nav.basics_button'),
      description: app.translator.trans('core.admin.nav.basics_text')
    }));
    items.add('mail', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('mail'),
      icon: 'fas fa-envelope',
      children: app.translator.trans('core.admin.nav.email_button'),
      description: app.translator.trans('core.admin.nav.email_text')
    }));
    items.add('permissions', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('permissions'),
      icon: 'fas fa-key',
      children: app.translator.trans('core.admin.nav.permissions_button'),
      description: app.translator.trans('core.admin.nav.permissions_text')
    }));
    items.add('appearance', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('appearance'),
      icon: 'fas fa-paint-brush',
      children: app.translator.trans('core.admin.nav.appearance_button'),
      description: app.translator.trans('core.admin.nav.appearance_text')
    }));
    items.add('extensions', _AdminLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      href: app.route('extensions'),
      icon: 'fas fa-puzzle-piece',
      children: app.translator.trans('core.admin.nav.extensions_button'),
      description: app.translator.trans('core.admin.nav.extensions_text')
    }));
    return items;
  };

  return AdminNav;
}(_common_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/AppearancePage.tsx":
/*!*************************************************!*\
  !*** ./src/admin/components/AppearancePage.tsx ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return AppearancePage; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Switch__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Switch */ "./src/common/components/Switch.tsx");
/* harmony import */ var _EditCustomCssModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./EditCustomCssModal */ "./src/admin/components/EditCustomCssModal.tsx");
/* harmony import */ var _EditCustomHeaderModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./EditCustomHeaderModal */ "./src/admin/components/EditCustomHeaderModal.tsx");
/* harmony import */ var _EditCustomFooterModal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./EditCustomFooterModal */ "./src/admin/components/EditCustomFooterModal.tsx");
/* harmony import */ var _UploadImageButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./UploadImageButton */ "./src/admin/components/UploadImageButton.tsx");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/saveSettings */ "./src/admin/utils/saveSettings.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }











var AppearancePage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(AppearancePage, _Page);

  var _super = _createSuper(AppearancePage);

  function AppearancePage() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Page.call.apply(_Page, [this].concat(args)) || this;
    _this.loading = false;
    _this.primaryColor = m.prop(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.theme_primary_color);
    _this.secondaryColor = m.prop(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.theme_secondary_color);
    _this.darkMode = m.prop(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.theme_dark_mode === '1');
    _this.coloredHeader = m.prop(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.theme_colored_header === '1');
    return _this;
  }

  var _proto = AppearancePage.prototype;

  _proto.view = function view() {
    return m("div", {
      className: "AppearancePage"
    }, m("div", {
      className: "container"
    }, m("form", {
      onsubmit: this.onsubmit.bind(this)
    }, m("fieldset", {
      className: "AppearancePage-colors"
    }, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.colors_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.colors_text')), m("div", {
      className: "AppearancePage-colors-input"
    }, m("input", {
      className: "FormControl",
      type: "text",
      placeholder: "#aaaaaa",
      value: this.primaryColor(),
      onchange: m.withAttr('value', this.primaryColor)
    }), m("input", {
      className: "FormControl",
      type: "text",
      placeholder: "#aaaaaa",
      value: this.secondaryColor(),
      onchange: m.withAttr('value', this.secondaryColor)
    })), _common_components_Switch__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      state: this.darkMode(),
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.dark_mode_label'),
      onchange: this.darkMode
    }), _common_components_Switch__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      state: this.coloredHeader(),
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.colored_header_label'),
      onchange: this.coloredHeader
    }), _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      className: 'Button Button--primary',
      type: 'submit',
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.submit_button'),
      loading: this.loading
    }))), m("fieldset", null, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.logo_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.logo_text')), m(_UploadImageButton__WEBPACK_IMPORTED_MODULE_10__["default"], {
      name: "logo"
    })), m("fieldset", null, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.favicon_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.favicon_text')), m(_UploadImageButton__WEBPACK_IMPORTED_MODULE_10__["default"], {
      name: "favicon"
    })), m("fieldset", null, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_header_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_header_text')), _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      className: 'Button',
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.edit_header_button'),
      onclick: function onclick() {
        return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_EditCustomHeaderModal__WEBPACK_IMPORTED_MODULE_8__["default"]);
      }
    })), m("fieldset", null, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_footer_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_footer_text')), _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      className: 'Button',
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.edit_footer_button'),
      onclick: function onclick() {
        return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_EditCustomFooterModal__WEBPACK_IMPORTED_MODULE_9__["default"]);
      }
    })), m("fieldset", null, m("legend", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_styles_heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.custom_styles_text')), _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      className: 'Button',
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.edit_css_button'),
      onclick: function onclick() {
        return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_EditCustomCssModal__WEBPACK_IMPORTED_MODULE_7__["default"]);
      }
    }))));
  };

  _proto.onsubmit = function onsubmit(e) {
    e.preventDefault();
    var hex = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

    if (!hex.test(this.primaryColor()) || !hex.test(this.secondaryColor())) {
      alert(_app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.appearance.enter_hex_message'));
      return;
    }

    this.loading = true;
    Object(_utils_saveSettings__WEBPACK_IMPORTED_MODULE_11__["default"])({
      theme_primary_color: this.primaryColor(),
      theme_secondary_color: this.secondaryColor(),
      theme_dark_mode: this.darkMode(),
      theme_colored_header: this.coloredHeader()
    }).then(function () {
      return window.location.reload();
    });
  };

  return AppearancePage;
}(_Page__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/BasicsPage.tsx":
/*!*********************************************!*\
  !*** ./src/admin/components/BasicsPage.tsx ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return BasicsPage; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/FieldSet */ "./src/common/components/FieldSet.tsx");
/* harmony import */ var _common_components_Select__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Select */ "./src/common/components/Select.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Alert__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/components/Alert */ "./src/common/components/Alert.tsx");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/saveSettings */ "./src/admin/utils/saveSettings.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Switch__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../common/components/Switch */ "./src/common/components/Switch.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }











var BasicsPage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(BasicsPage, _Page);

  var _super = _createSuper(BasicsPage);

  function BasicsPage() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Page.call.apply(_Page, [this].concat(args)) || this;
    _this.loading = false;
    _this.fields = ['forum_title', 'forum_description', 'default_locale', 'show_language_selector', 'default_route', 'welcome_title', 'welcome_message'];
    _this.values = {};
    _this.localeOptions = {};
    _this.successAlert = void 0;
    return _this;
  }

  var _proto = BasicsPage.prototype;

  _proto.oninit = function oninit(vnode) {
    var _this2 = this;

    _Page.prototype.oninit.call(this, vnode);

    var settings = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings;
    this.fields.forEach(function (key) {
      return _this2.values[key] = m.prop(settings[key]);
    });
    var locales = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.locales;

    for (var i in locales) {
      this.localeOptions[i] = locales[i] + " (" + i + ")";
    }

    if (typeof this.values.show_language_selector() !== 'number') this.values.show_language_selector(1);
  };

  _proto.view = function view() {
    var _this3 = this;

    return m("div", {
      className: "BasicsPage"
    }, m("div", {
      className: "container"
    }, m("form", {
      onsubmit: this.onsubmit.bind(this)
    }, _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.forum_title_heading'),
      children: [m("input", {
        className: "FormControl",
        value: this.values.forum_title(),
        oninput: m.withAttr('value', this.values.forum_title)
      })]
    }), _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.forum_description_heading'),
      children: [m("div", {
        className: "helpText"
      }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.forum_description_text')), m("textarea", {
        className: "FormControl",
        value: this.values.forum_description(),
        oninput: m.withAttr('value', this.values.forum_description)
      })]
    }), Object.keys(this.localeOptions).length > 1 ? _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.default_language_heading'),
      children: [_common_components_Select__WEBPACK_IMPORTED_MODULE_6__["default"].component({
        options: this.localeOptions,
        value: this.values.default_locale(),
        onchange: this.values.default_locale
      }), _common_components_Switch__WEBPACK_IMPORTED_MODULE_11__["default"].component({
        state: this.values.show_language_selector(),
        onchange: this.values.show_language_selector,
        children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.show_language_selector_label')
      })]
    }) : '', _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.home_page_heading'),
      className: 'BasicsPage-homePage',
      children: [m("div", {
        className: "helpText"
      }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.home_page_text')), this.homePageItems().toArray().map(function (_ref) {
        var path = _ref.path,
            label = _ref.label;
        return m("label", {
          className: "checkbox"
        }, m("input", {
          type: "radio",
          name: "homePage",
          value: path,
          checked: _this3.values.default_route() === path,
          onclick: m.withAttr('value', _this3.values.default_route)
        }), label);
      })]
    }), _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.welcome_banner_heading'),
      className: 'BasicsPage-welcomeBanner',
      children: [m("div", {
        className: "helpText"
      }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.welcome_banner_text')), m("div", {
        className: "BasicsPage-welcomeBanner-input"
      }, m("input", {
        className: "FormControl",
        value: this.values.welcome_title(),
        oninput: m.withAttr('value', this.values.welcome_title)
      }), m("textarea", {
        className: "FormControl",
        value: this.values.welcome_message(),
        oninput: m.withAttr('value', this.values.welcome_message)
      }))]
    }), _common_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"].component({
      type: 'submit',
      className: 'Button Button--primary',
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.submit_button'),
      loading: this.loading,
      disabled: !this.changed()
    }))));
  };

  _proto.changed = function changed() {
    var _this4 = this;

    return this.fields.some(function (key) {
      return _this4.values[key]() !== _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings[key];
    });
  }
  /**
   * Build a list of options for the default homepage. Each option must be an
   * object with `path` and `label` properties.
   *
   * @return {ItemList}
   * @public
   */
  ;

  _proto.homePageItems = function homePageItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_10__["default"]();
    items.add('allDiscussions', {
      path: '/all',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.all_discussions_label')
    });
    return items;
  };

  _proto.onsubmit = function onsubmit(e) {
    var _this5 = this;

    e.preventDefault();
    if (this.loading) return;
    this.loading = true;
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].alerts.dismiss(this.successAlert);
    var settings = {};
    this.fields.forEach(function (key) {
      return settings[key] = _this5.values[key]();
    });
    Object(_utils_saveSettings__WEBPACK_IMPORTED_MODULE_9__["default"])(settings).then(function () {
      _app__WEBPACK_IMPORTED_MODULE_3__["default"].alerts.show(_this5.successAlert = _common_components_Alert__WEBPACK_IMPORTED_MODULE_8__["default"].component({
        type: 'success',
        children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.basics.saved_message')
      }));
    }).catch(function () {}).then(function () {
      _this5.loading = false;
      m.redraw();
    });
  };

  return BasicsPage;
}(_Page__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/DashboardPage.tsx":
/*!************************************************!*\
  !*** ./src/admin/components/DashboardPage.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return DashboardPage; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _StatusWidget__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./StatusWidget */ "./src/admin/components/StatusWidget.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




var DashboardPage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(DashboardPage, _Page);

  var _super = _createSuper(DashboardPage);

  function DashboardPage() {
    return _Page.apply(this, arguments) || this;
  }

  var _proto = DashboardPage.prototype;

  _proto.view = function view() {
    return m("div", {
      className: "DashboardPage"
    }, m("div", {
      className: "container"
    }, this.availableWidgets()));
  };

  _proto.availableWidgets = function availableWidgets() {
    return [m(_StatusWidget__WEBPACK_IMPORTED_MODULE_4__["default"], null)];
  };

  return DashboardPage;
}(_Page__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/DashboardWidget.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/DashboardWidget.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return DashboardWidget; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var DashboardWidget = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(DashboardWidget, _Component);

  var _super = _createSuper(DashboardWidget);

  function DashboardWidget() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = DashboardWidget.prototype;

  _proto.view = function view() {
    return m("div", {
      className: 'DashboardWidget ' + this.className()
    }, this.content());
  }
  /**
   * Get the class name to apply to the widget.
   *
   * @return {String}
   */
  ;

  _proto.className = function className() {
    return '';
  }
  /**
   * Get the content of the widget.
   *
   * @return {VirtualElement}
   */
  ;

  return DashboardWidget;
}(_common_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/EditCustomCssModal.tsx":
/*!*****************************************************!*\
  !*** ./src/admin/components/EditCustomCssModal.tsx ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return EditCustomCssModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _SettingsModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsModal */ "./src/admin/components/SettingsModal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var EditCustomCssModal = /*#__PURE__*/function (_SettingsModal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(EditCustomCssModal, _SettingsModal);

  var _super = _createSuper(EditCustomCssModal);

  function EditCustomCssModal() {
    return _SettingsModal.apply(this, arguments) || this;
  }

  var _proto = EditCustomCssModal.prototype;

  _proto.className = function className() {
    return 'EditCustomCssModal Modal--large';
  };

  _proto.title = function title() {
    return app.translator.trans('core.admin.edit_css.title');
  };

  _proto.form = function form() {
    return [m("p", null, app.translator.trans('core.admin.edit_css.customize_text', {
      a: m("a", {
        href: "https://github.com/flarum/core/tree/master/less",
        target: "_blank"
      })
    })), m("div", {
      className: "Form-group"
    }, m("textarea", {
      className: "FormControl",
      rows: "30",
      bidi: this.setting('custom_less')
    }))];
  };

  _proto.onsaved = function onsaved() {
    window.location.reload();
  };

  return EditCustomCssModal;
}(_SettingsModal__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/EditCustomFooterModal.tsx":
/*!********************************************************!*\
  !*** ./src/admin/components/EditCustomFooterModal.tsx ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return EditCustomFooterModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _SettingsModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsModal */ "./src/admin/components/SettingsModal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var EditCustomFooterModal = /*#__PURE__*/function (_SettingsModal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(EditCustomFooterModal, _SettingsModal);

  var _super = _createSuper(EditCustomFooterModal);

  function EditCustomFooterModal() {
    return _SettingsModal.apply(this, arguments) || this;
  }

  var _proto = EditCustomFooterModal.prototype;

  _proto.className = function className() {
    return 'EditCustomFooterModal Modal--large';
  };

  _proto.title = function title() {
    return app.translator.trans('core.admin.edit_footer.title');
  };

  _proto.form = function form() {
    return [m("p", null, app.translator.trans('core.admin.edit_footer.customize_text')), m("div", {
      className: "Form-group"
    }, m("textarea", {
      className: "FormControl",
      rows: "30",
      bidi: this.setting('custom_footer')
    }))];
  };

  _proto.onsaved = function onsaved() {
    window.location.reload();
  };

  return EditCustomFooterModal;
}(_SettingsModal__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/EditCustomHeaderModal.tsx":
/*!********************************************************!*\
  !*** ./src/admin/components/EditCustomHeaderModal.tsx ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return EditCustomHeaderModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _SettingsModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SettingsModal */ "./src/admin/components/SettingsModal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var EditCustomHeaderModal = /*#__PURE__*/function (_SettingsModal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(EditCustomHeaderModal, _SettingsModal);

  var _super = _createSuper(EditCustomHeaderModal);

  function EditCustomHeaderModal() {
    return _SettingsModal.apply(this, arguments) || this;
  }

  var _proto = EditCustomHeaderModal.prototype;

  _proto.className = function className() {
    return 'EditCustomHeaderModal Modal--large';
  };

  _proto.title = function title() {
    return app.translator.trans('core.admin.edit_header.title');
  };

  _proto.form = function form() {
    return [m("p", null, app.translator.trans('core.admin.edit_header.customize_text')), m("div", {
      className: "Form-group"
    }, m("textarea", {
      className: "FormControl",
      rows: "30",
      bidi: this.setting('custom_header')
    }))];
  };

  _proto.onsaved = function onsaved() {
    window.location.reload();
  };

  return EditCustomHeaderModal;
}(_SettingsModal__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/EditGroupModal.tsx":
/*!*************************************************!*\
  !*** ./src/admin/components/EditGroupModal.tsx ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return EditGroupModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_Modal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Modal */ "./src/common/components/Modal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Badge__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Badge */ "./src/common/components/Badge.tsx");
/* harmony import */ var _common_models_Group__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }








/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
var EditGroupModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(EditGroupModal, _Modal);

  var _super = _createSuper(EditGroupModal);

  function EditGroupModal() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.group = void 0;
    _this.nameSingular = void 0;
    _this.namePlural = void 0;
    _this.icon = void 0;
    _this.color = void 0;
    return _this;
  }

  var _proto = EditGroupModal.prototype;

  _proto.oninit = function oninit(vnode) {
    _Modal.prototype.oninit.call(this, vnode);

    this.group = this.props.group || _app__WEBPACK_IMPORTED_MODULE_3__["default"].store.createRecord('groups');
    this.nameSingular = m.prop(this.group.nameSingular() || '');
    this.namePlural = m.prop(this.group.namePlural() || '');
    this.icon = m.prop(this.group.icon() || '');
    this.color = m.prop(this.group.color() || '');
  };

  _proto.className = function className() {
    return 'EditGroupModal Modal--small';
  };

  _proto.title = function title() {
    return [this.color() || this.icon() ? _common_components_Badge__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      icon: this.icon(),
      style: {
        backgroundColor: this.color()
      }
    }) : '', ' ', this.namePlural() || _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.title')];
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, m("div", {
      className: "Form"
    }, this.fields().toArray()));
  };

  _proto.fields = function fields() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('name', m("div", {
      className: "Form-group"
    }, m("label", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.name_label')), m("div", {
      className: "EditGroupModal-name-input"
    }, m("input", {
      className: "FormControl",
      placeholder: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.edit_group.singular_placeholder'),
      value: this.nameSingular(),
      oninput: m.withAttr('value', this.nameSingular)
    }), m("input", {
      className: "FormControl",
      placeholder: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.edit_group.plural_placeholder'),
      value: this.namePlural(),
      oninput: m.withAttr('value', this.namePlural)
    }))), 30);
    items.add('color', m("div", {
      className: "Form-group"
    }, m("label", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.color_label')), m("input", {
      className: "FormControl",
      placeholder: "#aaaaaa",
      value: this.color(),
      oninput: m.withAttr('value', this.color)
    })), 20);
    items.add('icon', m("div", {
      className: "Form-group"
    }, m("label", null, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.icon_label')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.icon_text', {
      a: m("a", {
        href: "https://fontawesome.com/icons?m=free",
        tabindex: "-1"
      })
    })), m("input", {
      className: "FormControl",
      placeholder: "fas fa-bolt",
      value: this.icon(),
      oninput: m.withAttr('value', this.icon)
    })), 10);
    items.add('submit', m("div", {
      className: "Form-group"
    }, _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      type: 'submit',
      className: 'Button Button--primary EditGroupModal-save',
      loading: this.loading,
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.submit_button')
    }), this.group.exists && this.group.id() !== _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].ADMINISTRATOR_ID ? m("button", {
      type: "button",
      className: "Button EditGroupModal-delete",
      onclick: this.deleteGroup.bind(this)
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.edit_group.delete_button')) : ''), -10);
    return items;
  };

  _proto.submitData = function submitData() {
    return {
      nameSingular: this.nameSingular(),
      namePlural: this.namePlural(),
      color: this.color(),
      icon: this.icon()
    };
  };

  _proto.onsubmit = function onsubmit(e) {
    var _this2 = this;

    e.preventDefault();
    this.loading = true;
    this.group.save(this.submitData(), {
      errorHandler: this.onerror.bind(this)
    }).then(this.hide.bind(this)).catch(function () {
      _this2.loading = false;
      m.redraw();
    });
  };

  _proto.deleteGroup = function deleteGroup() {
    if (confirm(_app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.edit_group.delete_confirmation'))) {
      this.group.delete().then(function () {
        return m.redraw();
      });
      this.hide();
    }
  };

  return EditGroupModal;
}(_common_components_Modal__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/ExtensionsPage.tsx":
/*!*************************************************!*\
  !*** ./src/admin/components/ExtensionsPage.tsx ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ExtensionsPage; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Dropdown__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _AddExtensionModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./AddExtensionModal */ "./src/admin/components/AddExtensionModal.tsx");
/* harmony import */ var _LoadingModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./LoadingModal */ "./src/admin/components/LoadingModal.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_helpers_icon__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/helpers/icon */ "./src/common/helpers/icon.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }










var ExtensionsPage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(ExtensionsPage, _Page);

  var _super = _createSuper(ExtensionsPage);

  function ExtensionsPage() {
    return _Page.apply(this, arguments) || this;
  }

  var _proto = ExtensionsPage.prototype;

  _proto.view = function view() {
    var _this = this;

    return m("div", {
      className: "ExtensionsPage"
    }, m("div", {
      className: "ExtensionsPage-header"
    }, m("div", {
      className: "container"
    }, _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
      children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.extensions.add_button'),
      icon: 'fas fa-plus',
      className: 'Button Button--primary',
      onclick: function onclick() {
        return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_AddExtensionModal__WEBPACK_IMPORTED_MODULE_7__["default"]);
      }
    }))), m("div", {
      className: "ExtensionsPage-list"
    }, m("div", {
      className: "container"
    }, m("ul", {
      className: "ExtensionList"
    }, Object.keys(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.extensions).map(function (id) {
      var extension = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.extensions[id];

      var controls = _this.controlItems(extension.id).toArray();

      return m("li", {
        className: 'ExtensionListItem ' + (!_this.isEnabled(extension.id) ? 'disabled' : '')
      }, m("div", {
        className: "ExtensionListItem-content"
      }, m("span", {
        className: "ExtensionListItem-icon ExtensionIcon",
        style: extension.icon
      }, extension.icon ? Object(_common_helpers_icon__WEBPACK_IMPORTED_MODULE_10__["default"])(extension.icon.name) : ''), controls.length ? m(_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_6__["default"], {
        className: "ExtensionListItem-controls",
        buttonClassName: "Button Button--icon Button--flat",
        menuClassName: "Dropdown-menu--right",
        icon: "fas fa-ellipsis-h"
      }, controls) : '', m("div", {
        className: "ExtensionListItem-main"
      }, m("label", {
        className: "ExtensionListItem-title"
      }, m("input", {
        type: "checkbox",
        checked: _this.isEnabled(extension.id),
        onclick: _this.toggle.bind(_this, extension.id)
      }), ' ', extension.extra['flarum-extension'].title), m("div", {
        className: "ExtensionListItem-version"
      }, extension.version), m("div", {
        className: "ExtensionListItem-description"
      }, extension.description))));
    })))));
  };

  _proto.controlItems = function controlItems(name) {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_9__["default"]();
    var enabled = this.isEnabled(name);

    if (_app__WEBPACK_IMPORTED_MODULE_3__["default"].extensionSettings[name]) {
      items.add('settings', _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        icon: 'fas fa-cog',
        children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.extensions.settings_button'),
        onclick: _app__WEBPACK_IMPORTED_MODULE_3__["default"].extensionSettings[name]
      }));
    }

    if (!enabled) {
      items.add('uninstall', _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        icon: 'far fa-trash-alt',
        children: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.extensions.uninstall_button'),
        onclick: function onclick() {
          _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
            url: _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('apiUrl') + '/extensions/' + name,
            method: 'DELETE'
          }).then(function () {
            return window.location.reload();
          });
          _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_LoadingModal__WEBPACK_IMPORTED_MODULE_8__["default"]);
        }
      }));
    }

    return items;
  };

  _proto.isEnabled = function isEnabled(name) {
    var enabled = JSON.parse(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.extensions_enabled);
    return enabled.indexOf(name) !== -1;
  };

  _proto.toggle = function toggle(id) {
    var enabled = this.isEnabled(id);
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
      url: _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('apiUrl') + '/extensions/' + id,
      method: 'PATCH',
      body: {
        enabled: !enabled
      }
    }).then(function () {
      if (!enabled) localStorage.setItem('enabledExtension', id);
      window.location.reload();
    });
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_LoadingModal__WEBPACK_IMPORTED_MODULE_8__["default"]);
  };

  return ExtensionsPage;
}(_Page__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/HeaderPrimary.tsx":
/*!************************************************!*\
  !*** ./src/admin/components/HeaderPrimary.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return HeaderPrimary; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */

var HeaderPrimary = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(HeaderPrimary, _Component);

  var _super = _createSuper(HeaderPrimary);

  function HeaderPrimary() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = HeaderPrimary.prototype;

  _proto.view = function view() {
    return m("ul", {
      className: "Header-controls"
    }, Object(_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__["default"])(this.items().toArray()));
  }
  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  ;

  _proto.items = function items() {
    return new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_4__["default"]();
  };

  return HeaderPrimary;
}(_common_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/HeaderSecondary.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/HeaderSecondary.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return HeaderSecondary; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _SessionDropdown__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SessionDropdown */ "./src/admin/components/SessionDropdown.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





/**
 * The `HeaderSecondary` component displays secondary header controls.
 */

var HeaderSecondary = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(HeaderSecondary, _Component);

  var _super = _createSuper(HeaderSecondary);

  function HeaderSecondary() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = HeaderSecondary.prototype;

  _proto.view = function view() {
    return m("ul", {
      className: "Header-controls"
    }, Object(_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_6__["default"])(this.items().toArray()));
  }
  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  ;

  _proto.items = function items() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
    items.add('session', _SessionDropdown__WEBPACK_IMPORTED_MODULE_4__["default"].component());
    return items;
  };

  return HeaderSecondary;
}(_common_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/LoadingModal.tsx":
/*!***********************************************!*\
  !*** ./src/admin/components/LoadingModal.tsx ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return LoadingModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_components_Modal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Modal */ "./src/common/components/Modal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var LoadingModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(LoadingModal, _Modal);

  var _super = _createSuper(LoadingModal);

  function LoadingModal() {
    return _Modal.apply(this, arguments) || this;
  }

  var _proto = LoadingModal.prototype;

  _proto.isDismissible = function isDismissible() {
    return false;
  };

  _proto.className = function className() {
    return 'LoadingModal Modal--small';
  };

  _proto.title = function title() {
    return app.translator.transText('core.admin.loading.title');
  };

  _proto.content = function content() {
    return '';
  };

  return LoadingModal;
}(_common_components_Modal__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/MailPage.tsx":
/*!*******************************************!*\
  !*** ./src/admin/components/MailPage.tsx ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return MailPage; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/FieldSet */ "./src/common/components/FieldSet.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Alert__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/components/Alert */ "./src/common/components/Alert.tsx");
/* harmony import */ var _common_components_Select__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/components/Select */ "./src/common/components/Select.tsx");
/* harmony import */ var _common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/components/LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/saveSettings */ "./src/admin/utils/saveSettings.ts");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }










var MailPage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(MailPage, _Page);

  var _super = _createSuper(MailPage);

  function MailPage() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Page.call.apply(_Page, [this].concat(args)) || this;
    _this.loading = true;
    _this.saving = false;
    _this.driverFields = {};
    _this.fields = [];
    _this.values = {};
    _this.status = {
      sending: false,
      errors: {}
    };
    _this.successAlert = void 0;
    return _this;
  }

  var _proto = MailPage.prototype;

  _proto.oninit = function oninit(vnode) {
    _Page.prototype.oninit.call(this, vnode);

    this.refresh();
  };

  _proto.refresh = function refresh() {
    var _this2 = this;

    this.loading = true;
    this.fields = ['mail_driver', 'mail_from'];
    this.values = {};
    _app__WEBPACK_IMPORTED_MODULE_4__["default"].request({
      method: 'GET',
      url: _app__WEBPACK_IMPORTED_MODULE_4__["default"].forum.attribute('apiUrl') + '/mail-settings'
    }).then(function (response) {
      _this2.driverFields = response['data']['attributes']['fields'];
      _this2.status.sending = response['data']['attributes']['sending'];
      _this2.status.errors = response['data']['attributes']['errors'];

      for (var driver in _this2.driverFields) {
        for (var field in _this2.driverFields[driver]) {
          _this2.fields.push(field);
        }
      }

      var settings = _app__WEBPACK_IMPORTED_MODULE_4__["default"].data.settings;

      _this2.fields.forEach(function (key) {
        return _this2.values[key] = m.prop(settings[key]);
      });

      _this2.loading = false;
      m.redraw();
    });
  };

  _proto.view = function view() {
    var _this3 = this;

    if (this.loading || this.saving) {
      return m("div", {
        className: "MailPage"
      }, m("div", {
        className: "container"
      }, m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__["default"], null)));
    }

    var fields = this.driverFields[this.values.mail_driver()];
    var fieldKeys = Object.keys(fields);
    return m("div", {
      className: "MailPage"
    }, m("div", {
      className: "container"
    }, m("form", {
      onsubmit: this.onsubmit.bind(this)
    }, m("h2", null, _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.heading')), m("div", {
      className: "helpText"
    }, _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.text')), _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.addresses_heading'),
      className: 'MailPage-MailSettings',
      children: [m("div", {
        className: "MailPage-MailSettings-input"
      }, m("label", null, _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.from_label'), m("input", {
        className: "FormControl",
        value: this.values.mail_from() || '',
        oninput: m.withAttr('value', this.values.mail_from)
      })))]
    }), _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.driver_heading'),
      className: 'MailPage-MailSettings',
      children: [m("div", {
        className: "MailPage-MailSettings-input"
      }, m("label", null, _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.driver_label'), m(_common_components_Select__WEBPACK_IMPORTED_MODULE_9__["default"], {
        value: this.values.mail_driver(),
        options: Object.keys(this.driverFields).reduce(function (memo, val) {
          var _extends2;

          return Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, memo, (_extends2 = {}, _extends2[val] = val, _extends2));
        }, {}),
        onchange: this.values.mail_driver
      })))]
    }), this.status.sending || _common_components_Alert__WEBPACK_IMPORTED_MODULE_8__["default"].component({
      children: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.not_sending_message'),
      dismissible: false
    }), fieldKeys.length > 0 && _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      label: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans("core.admin.email." + this.values.mail_driver() + "_heading"),
      className: 'MailPage-MailSettings',
      children: [m("div", {
        className: "MailPage-MailSettings-input"
      }, fieldKeys.map(function (field) {
        return [m("label", null, _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans("core.admin.email." + field + "_label"), _this3.renderField(field)), _this3.status.errors[field] && m("p", {
          className: "ValidationError"
        }, _this3.status.errors[field])];
      }))]
    }), _common_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"].component({
      type: 'submit',
      className: 'Button Button--primary',
      children: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.email.submit_button'),
      disabled: !this.changed()
    }))));
  };

  _proto.renderField = function renderField(name) {
    var driver = this.values.mail_driver();
    var field = this.driverFields[driver][name];
    var prop = this.values[name];

    if (prop == undefined) {}

    if (typeof field === 'string') {
      return m("input", {
        className: "FormControl",
        value: prop() || '',
        oninput: m.withAttr('value', prop)
      });
    } else {
      return m(_common_components_Select__WEBPACK_IMPORTED_MODULE_9__["default"], {
        value: prop(),
        options: field,
        onchange: prop
      });
    }
  };

  _proto.changed = function changed() {
    var _this4 = this;

    return this.fields.some(function (key) {
      return _this4.values[key]() !== _app__WEBPACK_IMPORTED_MODULE_4__["default"].data.settings[key];
    });
  };

  _proto.onsubmit = function onsubmit(e) {
    var _this5 = this;

    e.preventDefault();
    if (this.saving) return;
    this.saving = true;
    _app__WEBPACK_IMPORTED_MODULE_4__["default"].alerts.dismiss(this.successAlert);
    var settings = {};
    this.fields.forEach(function (key) {
      return settings[key] = _this5.values[key]();
    });
    Object(_utils_saveSettings__WEBPACK_IMPORTED_MODULE_11__["default"])(settings).then(function () {
      _app__WEBPACK_IMPORTED_MODULE_4__["default"].alerts.show(_this5.successAlert = _common_components_Alert__WEBPACK_IMPORTED_MODULE_8__["default"].component({
        type: 'success',
        children: _app__WEBPACK_IMPORTED_MODULE_4__["default"].translator.trans('core.admin.basics.saved_message')
      }));
    }).catch(function () {}).then(function () {
      _this5.saving = false;

      _this5.refresh();
    });
  };

  return MailPage;
}(_Page__WEBPACK_IMPORTED_MODULE_5__["default"]);



/***/ }),

/***/ "./src/admin/components/Page.tsx":
/*!***************************************!*\
  !*** ./src/admin/components/Page.tsx ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Page; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }


/**
 * The `Page` component
 */

var Page = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Page, _Component);

  var _super = _createSuper(Page);

  function Page() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.bodyClass = '';
    return _this;
  }

  var _proto = Page.prototype;

  _proto.oninit = function oninit(vnode) {
    _Component.prototype.oninit.call(this, vnode);

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);
    }
  };

  _proto.oncreate = function oncreate(vnode) {
    _Component.prototype.oncreate.call(this, vnode);

    app.previous = app.current;
    app.current = this;
    app.modal.close();
  };

  _proto.onremove = function onremove(vnode) {
    _Component.prototype.onremove.call(this, vnode);

    $('#app').removeClass(this.bodyClass);
  };

  return Page;
}(_common_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/admin/components/PermissionDropdown.tsx":
/*!*****************************************************!*\
  !*** ./src/admin/components/PermissionDropdown.tsx ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PermissionDropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_Dropdown__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Separator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Separator */ "./src/common/components/Separator.tsx");
/* harmony import */ var _common_models_Group__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _common_components_Badge__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/components/Badge */ "./src/common/components/Badge.tsx");
/* harmony import */ var _common_components_GroupBadge__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/components/GroupBadge */ "./src/common/components/GroupBadge.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }









function badgeForId(id) {
  var group = _app__WEBPACK_IMPORTED_MODULE_3__["default"].store.getById('groups', id);
  return group ? _common_components_GroupBadge__WEBPACK_IMPORTED_MODULE_9__["default"].component({
    group: group,
    label: null
  }) : '';
}

function filterByRequiredPermissions(groupIds, permission) {
  _app__WEBPACK_IMPORTED_MODULE_3__["default"].getRequiredPermissions(permission).forEach(function (required) {
    var restrictToGroupIds = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.permissions[required] || [];

    if (restrictToGroupIds.indexOf(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID) !== -1) {// do nothing
    } else if (restrictToGroupIds.indexOf(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID) !== -1) {
      groupIds = groupIds.filter(function (id) {
        return id !== _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID;
      });
    } else if (groupIds.indexOf(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID) !== -1) {
      groupIds = restrictToGroupIds;
    } else {
      groupIds = restrictToGroupIds.filter(function (id) {
        return groupIds.indexOf(id) !== -1;
      });
    }

    groupIds = filterByRequiredPermissions(groupIds, required);
  });
  return groupIds;
}

var PermissionDropdown = /*#__PURE__*/function (_Dropdown) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(PermissionDropdown, _Dropdown);

  var _super = _createSuper(PermissionDropdown);

  function PermissionDropdown() {
    return _Dropdown.apply(this, arguments) || this;
  }

  PermissionDropdown.initProps = function initProps(props) {
    _Dropdown.initProps.call(this, props);

    props.className = 'PermissionDropdown';
    props.buttonClassName = 'Button Button--text';
  };

  var _proto = PermissionDropdown.prototype;

  _proto.view = function view() {
    var _this = this;

    this.props.children = [];
    var groupIds = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.permissions[this.props.permission] || [];
    groupIds = filterByRequiredPermissions(groupIds, this.props.permission);
    var everyone = groupIds.indexOf(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID) !== -1;
    var members = groupIds.indexOf(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID) !== -1;
    var adminGroup = _app__WEBPACK_IMPORTED_MODULE_3__["default"].store.getById('groups', _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].ADMINISTRATOR_ID);

    if (everyone) {
      this.props.label = _common_components_Badge__WEBPACK_IMPORTED_MODULE_8__["default"].component({
        icon: 'fas fa-globe'
      });
    } else if (members) {
      this.props.label = _common_components_Badge__WEBPACK_IMPORTED_MODULE_8__["default"].component({
        icon: 'fas fa-user'
      });
    } else {
      this.props.label = [badgeForId(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].ADMINISTRATOR_ID), groupIds.map(badgeForId)];
    }

    if (this.showing) {
      if (this.props.allowGuest) {
        this.props.children.push(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
          children: [_common_components_Badge__WEBPACK_IMPORTED_MODULE_8__["default"].component({
            icon: 'fas fa-globe'
          }), ' ', _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions_controls.everyone_button')],
          icon: everyone ? 'fas fa-check' : true,
          onclick: function onclick() {
            return _this.save([_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID]);
          },
          disabled: this.isGroupDisabled(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID)
        }));
      }

      this.props.children.push(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        children: [_common_components_Badge__WEBPACK_IMPORTED_MODULE_8__["default"].component({
          icon: 'fas fa-user'
        }), ' ', _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions_controls.members_button')],
        icon: members ? 'fas fa-check' : true,
        onclick: function onclick() {
          return _this.save([_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID]);
        },
        disabled: this.isGroupDisabled(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID)
      }), _common_components_Separator__WEBPACK_IMPORTED_MODULE_6__["default"].component(), _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        children: [badgeForId(adminGroup.id()), ' ', adminGroup.namePlural()],
        icon: !everyone && !members ? 'fas fa-check' : true,
        disabled: !everyone && !members,
        onclick: function onclick(e) {
          if (e.shiftKey) e.stopPropagation();

          _this.save([]);
        }
      }));
      [].push.apply(this.props.children, _app__WEBPACK_IMPORTED_MODULE_3__["default"].store.all('groups').filter(function (group) {
        return [_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].ADMINISTRATOR_ID, _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID, _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID].indexOf(group.id()) === -1;
      }).map(function (group) {
        return _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
          children: [badgeForId(group.id()), ' ', group.namePlural()],
          icon: groupIds.indexOf(group.id()) !== -1 ? 'fas fa-check' : true,
          onclick: function onclick(e) {
            if (e.shiftKey) e.stopPropagation();

            _this.toggle(group.id());
          },
          disabled: _this.isGroupDisabled(group.id()) && _this.isGroupDisabled(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID) && _this.isGroupDisabled(_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID)
        });
      }));
    }

    return _Dropdown.prototype.view.call(this);
  };

  _proto.save = function save(groupIds) {
    var permission = this.props.permission;
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.permissions[permission] = groupIds;
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
      method: 'POST',
      url: _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('apiUrl') + '/permission',
      body: {
        permission: permission,
        groupIds: groupIds
      }
    });
  };

  _proto.toggle = function toggle(groupId) {
    var permission = this.props.permission;
    var groupIds = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.permissions[permission] || [];
    var index = groupIds.indexOf(groupId);

    if (index !== -1) {
      groupIds.splice(index, 1);
    } else {
      groupIds.push(groupId);
      groupIds = groupIds.filter(function (id) {
        return [_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID, _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID].indexOf(id) === -1;
      });
    }

    this.save(groupIds);
  };

  _proto.isGroupDisabled = function isGroupDisabled(id) {
    return filterByRequiredPermissions([id], this.props.permission).indexOf(id) === -1;
  };

  return PermissionDropdown;
}(_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/PermissionGrid.tsx":
/*!*************************************************!*\
  !*** ./src/admin/components/PermissionGrid.tsx ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PermissionGrid; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _PermissionDropdown__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./PermissionDropdown */ "./src/admin/components/PermissionDropdown.tsx");
/* harmony import */ var _SettingDropdown__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./SettingDropdown */ "./src/admin/components/SettingDropdown.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_helpers_icon__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/helpers/icon */ "./src/common/helpers/icon.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }









var PermissionGrid = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(PermissionGrid, _Component);

  var _super = _createSuper(PermissionGrid);

  function PermissionGrid() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = PermissionGrid.prototype;

  _proto.view = function view() {
    var scopes = this.scopeItems().toArray();

    var permissionCells = function permissionCells(permission) {
      return scopes.map(function (scope) {
        return m("td", null, scope.render(permission));
      });
    };

    return m("table", {
      className: "PermissionGrid"
    }, m("thead", null, m("tr", null, m("td", null), scopes.map(function (scope) {
      return m("th", null, scope.label, ' ', scope.onremove ? _common_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"].component({
        icon: 'fas fa-times',
        className: 'Button Button--text PermissionGrid-removeScope',
        onclick: scope.onremove
      }) : '');
    }), m("th", null, this.scopeControlItems().toArray()))), this.permissionItems().toArray().map(function (section) {
      return m("tbody", null, m("tr", {
        className: "PermissionGrid-section"
      }, m("th", null, section.label), permissionCells(section), m("td", null)), section.children.map(function (child) {
        return m("tr", {
          className: "PermissionGrid-child"
        }, m("th", null, Object(_common_helpers_icon__WEBPACK_IMPORTED_MODULE_9__["default"])(child.icon), child.label), permissionCells(child), m("td", null));
      }));
    }));
  };

  _proto.permissionItems = function permissionItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('view', {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.read_heading'),
      children: this.viewItems().toArray()
    }, 100);
    items.add('start', {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.create_heading'),
      children: this.startItems().toArray()
    }, 90);
    items.add('reply', {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.participate_heading'),
      children: this.replyItems().toArray()
    }, 80);
    items.add('moderate', {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.moderate_heading'),
      children: this.moderateItems().toArray()
    }, 70);
    return items;
  };

  _proto.viewItems = function viewItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('viewDiscussions', {
      icon: 'fas fa-eye',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.view_discussions_label'),
      permission: 'viewDiscussions',
      allowGuest: true
    }, 100);
    items.add('viewUserList', {
      icon: 'fas fa-users',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.view_user_list_label'),
      permission: 'viewUserList',
      allowGuest: true
    }, 100);
    items.add('signUp', {
      icon: 'fas fa-user-plus',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.sign_up_label'),
      setting: function setting() {
        return _SettingDropdown__WEBPACK_IMPORTED_MODULE_6__["default"].component({
          key: 'allow_sign_up',
          options: [{
            value: '1',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.signup_open_button')
          }, {
            value: '0',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.signup_closed_button')
          }]
        });
      }
    }, 90);
    items.add('viewLastSeenAt', {
      icon: 'far fa-clock',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.view_last_seen_at_label'),
      permission: 'user.viewLastSeenAt'
    });
    return items;
  };

  _proto.startItems = function startItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('start', {
      icon: 'fas fa-edit',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.start_discussions_label'),
      permission: 'startDiscussion'
    }, 100);
    items.add('allowRenaming', {
      icon: 'fas fa-i-cursor',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.allow_renaming_label'),
      setting: function setting() {
        var minutes = parseInt(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.allow_renaming, 10);
        return _SettingDropdown__WEBPACK_IMPORTED_MODULE_6__["default"].component({
          defaultLabel: minutes ? _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transChoice('core.admin.permissions_controls.allow_some_minutes_button', minutes, {
            count: minutes
          }) : _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
          key: 'allow_renaming',
          options: [{
            value: '-1',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_indefinitely_button')
          }, {
            value: '10',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_ten_minutes_button')
          }, {
            value: 'reply',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_until_reply_button')
          }]
        });
      }
    }, 90);
    return items;
  };

  _proto.replyItems = function replyItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('reply', {
      icon: 'fas fa-reply',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.reply_to_discussions_label'),
      permission: 'discussion.reply'
    }, 100);
    items.add('allowPostEditing', {
      icon: 'fas fa-pencil-alt',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.allow_post_editing_label'),
      setting: function setting() {
        var minutes = parseInt(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings.allow_post_editing, 10);
        return _SettingDropdown__WEBPACK_IMPORTED_MODULE_6__["default"].component({
          defaultLabel: minutes ? _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transChoice('core.admin.permissions_controls.allow_some_minutes_button', minutes, {
            count: minutes
          }) : _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
          key: 'allow_post_editing',
          options: [{
            value: '-1',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_indefinitely_button')
          }, {
            value: '10',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_ten_minutes_button')
          }, {
            value: 'reply',
            label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.permissions_controls.allow_until_reply_button')
          }]
        });
      }
    }, 90);
    return items;
  };

  _proto.moderateItems = function moderateItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('viewIpsPosts', {
      icon: 'fas fa-bullseye',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.view_post_ips_label'),
      permission: 'discussion.viewIpsPosts'
    }, 110);
    items.add('renameDiscussions', {
      icon: 'fas fa-i-cursor',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.rename_discussions_label'),
      permission: 'discussion.rename'
    }, 100);
    items.add('hideDiscussions', {
      icon: 'far fa-trash-alt',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.delete_discussions_label'),
      permission: 'discussion.hide'
    }, 90);
    items.add('deleteDiscussions', {
      icon: 'fas fa-times',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.delete_discussions_forever_label'),
      permission: 'discussion.delete'
    }, 80);
    items.add('postWithoutThrottle', {
      icon: 'fas fa-swimmer',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.post_without_throttle_label'),
      permission: 'postWithoutThrottle'
    }, 70);
    items.add('editPosts', {
      icon: 'fas fa-pencil-alt',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.edit_posts_label'),
      permission: 'discussion.editPosts'
    }, 70);
    items.add('hidePosts', {
      icon: 'far fa-trash-alt',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.delete_posts_label'),
      permission: 'discussion.hidePosts'
    }, 60);
    items.add('deletePosts', {
      icon: 'fas fa-times',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.delete_posts_forever_label'),
      permission: 'discussion.deletePosts'
    }, 60);
    items.add('userEdit', {
      icon: 'fas fa-user-cog',
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.edit_users_label'),
      permission: 'user.edit'
    }, 60);
    return items;
  };

  _proto.scopeItems = function scopeItems() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
    items.add('global', {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.global_heading'),
      render: function render(item) {
        if (item.setting) {
          return item.setting();
        } else if (item.permission) {
          return _PermissionDropdown__WEBPACK_IMPORTED_MODULE_5__["default"].component({
            permission: item.permission,
            allowGuest: item.allowGuest
          });
        }

        return '';
      }
    }, 100);
    return items;
  };

  _proto.scopeControlItems = function scopeControlItems() {
    return new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"]();
  };

  return PermissionGrid;
}(_common_Component__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/PermissionsPage.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/PermissionsPage.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PermissionsPage; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _Page__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Page */ "./src/admin/components/Page.tsx");
/* harmony import */ var _common_components_GroupBadge__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/GroupBadge */ "./src/common/components/GroupBadge.ts");
/* harmony import */ var _EditGroupModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./EditGroupModal */ "./src/admin/components/EditGroupModal.tsx");
/* harmony import */ var _common_models_Group__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _common_helpers_icon__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/helpers/icon */ "./src/common/helpers/icon.tsx");
/* harmony import */ var _PermissionGrid__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./PermissionGrid */ "./src/admin/components/PermissionGrid.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }









var PermissionsPage = /*#__PURE__*/function (_Page) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(PermissionsPage, _Page);

  var _super = _createSuper(PermissionsPage);

  function PermissionsPage() {
    return _Page.apply(this, arguments) || this;
  }

  var _proto = PermissionsPage.prototype;

  _proto.view = function view() {
    return m("div", {
      className: "PermissionsPage"
    }, m("div", {
      className: "PermissionsPage-groups"
    }, m("div", {
      className: "container"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].store.all('groups').filter(function (group) {
      return [_common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].GUEST_ID, _common_models_Group__WEBPACK_IMPORTED_MODULE_7__["default"].MEMBER_ID].indexOf(group.id()) === -1;
    }).map(function (group) {
      return m("button", {
        className: "Button Group",
        onclick: function onclick() {
          return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_EditGroupModal__WEBPACK_IMPORTED_MODULE_6__["default"], {
            group: group
          });
        }
      }, _common_components_GroupBadge__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        group: group,
        className: 'Group-icon',
        label: null
      }), m("span", {
        className: "Group-name"
      }, group.namePlural()));
    }), m("button", {
      className: "Button Group Group--add",
      onclick: function onclick() {
        return _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_EditGroupModal__WEBPACK_IMPORTED_MODULE_6__["default"]);
      }
    }, Object(_common_helpers_icon__WEBPACK_IMPORTED_MODULE_8__["default"])('fas fa-plus', {
      className: 'Group-icon'
    }), m("span", {
      className: "Group-name"
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.permissions.new_group_button'))))), m("div", {
      className: "PermissionsPage-permissions"
    }, m("div", {
      className: "container"
    }, _PermissionGrid__WEBPACK_IMPORTED_MODULE_9__["default"].component())));
  };

  return PermissionsPage;
}(_Page__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/SessionDropdown.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/SessionDropdown.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SessionDropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _common_helpers_avatar__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/helpers/avatar */ "./src/common/helpers/avatar.tsx");
/* harmony import */ var _common_helpers_username__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/helpers/username */ "./src/common/helpers/username.tsx");
/* harmony import */ var _common_components_Dropdown__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */

var SessionDropdown = /*#__PURE__*/function (_Dropdown) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(SessionDropdown, _Dropdown);

  var _super = _createSuper(SessionDropdown);

  function SessionDropdown() {
    return _Dropdown.apply(this, arguments) || this;
  }

  SessionDropdown.initProps = function initProps(props) {
    _Dropdown.initProps.call(this, props);

    props.className = 'SessionDropdown';
    props.buttonClassName = 'Button Button--user Button--flat';
    props.menuClassName = 'Dropdown-menu--right';
  };

  var _proto = SessionDropdown.prototype;

  _proto.view = function view() {
    this.props.children = this.items().toArray();
    return _Dropdown.prototype.view.call(this);
  };

  _proto.getButtonContent = function getButtonContent() {
    var user = app.session.user;
    return [Object(_common_helpers_avatar__WEBPACK_IMPORTED_MODULE_3__["default"])(user), ' ', m("span", {
      className: "Button-label"
    }, Object(_common_helpers_username__WEBPACK_IMPORTED_MODULE_4__["default"])(user))];
  }
  /**
   * Build an item list for the contents of the dropdown menu.
   *
   * @return {ItemList}
   */
  ;

  _proto.items = function items() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_7__["default"]();
    items.add('logOut', _common_components_Button__WEBPACK_IMPORTED_MODULE_6__["default"].component({
      icon: 'fas fa-sign-out-alt',
      children: app.translator.trans('core.admin.header.log_out_button'),
      onclick: app.session.logout.bind(app.session)
    }), -100);
    return items;
  };

  return SessionDropdown;
}(_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_5__["default"]);



/***/ }),

/***/ "./src/admin/components/SettingDropdown.tsx":
/*!**************************************************!*\
  !*** ./src/admin/components/SettingDropdown.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SettingDropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_SelectDropdown__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/SelectDropdown */ "./src/common/components/SelectDropdown.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/saveSettings */ "./src/admin/utils/saveSettings.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






var SettingDropdown = /*#__PURE__*/function (_SelectDropdown) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(SettingDropdown, _SelectDropdown);

  var _super = _createSuper(SettingDropdown);

  function SettingDropdown() {
    return _SelectDropdown.apply(this, arguments) || this;
  }

  SettingDropdown.initProps = function initProps(props) {
    var _this = this;

    _SelectDropdown.initProps.call(this, props);

    props.className = 'SettingDropdown';
    props.buttonClassName = 'Button Button--text';
    props.caretIcon = 'fas fa-caret-down';
    props.defaultLabel = 'Custom';
    props.children = props.options.map(function (_ref) {
      var _saveSettings$bind;

      var value = _ref.value,
          label = _ref.label;
      var active = _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings[props.key] === value;
      return _common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component({
        children: label,
        icon: active ? 'fas fa-check' : true,
        onclick: _utils_saveSettings__WEBPACK_IMPORTED_MODULE_6__["default"].bind(_this, (_saveSettings$bind = {}, _saveSettings$bind[props.key] = value, _saveSettings$bind)),
        active: active
      });
    });
  };

  return SettingDropdown;
}(_common_components_SelectDropdown__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/SettingsModal.tsx":
/*!************************************************!*\
  !*** ./src/admin/components/SettingsModal.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SettingsModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_Modal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Modal */ "./src/common/components/Modal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _utils_saveSettings__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/saveSettings */ "./src/admin/utils/saveSettings.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






var SettingsModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(SettingsModal, _Modal);

  var _super = _createSuper(SettingsModal);

  function SettingsModal() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.settings = {};
    _this.loading = false;
    return _this;
  }

  var _proto = SettingsModal.prototype;

  _proto.form = function form() {
    return '';
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, m("div", {
      className: "Form"
    }, this.form(), m("div", {
      className: "Form-group"
    }, this.submitButton())));
  };

  _proto.submitButton = function submitButton() {
    return m(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"], {
      type: "submit",
      className: "Button Button--primary",
      loading: this.loading,
      disabled: !this.changed()
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.settings.submit_button'));
  };

  _proto.setting = function setting(key, fallback) {
    if (fallback === void 0) {
      fallback = '';
    }

    this.settings[key] = this.settings[key] || m.prop(_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings[key] || fallback);
    return this.settings[key];
  };

  _proto.dirty = function dirty() {
    var _this2 = this;

    var dirty = {};
    Object.keys(this.settings).forEach(function (key) {
      var value = _this2.settings[key]();

      if (value !== _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings[key]) {
        dirty[key] = value;
      }
    });
    return dirty;
  };

  _proto.changed = function changed() {
    return Object.keys(this.dirty()).length;
  };

  _proto.onsubmit = function onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    Object(_utils_saveSettings__WEBPACK_IMPORTED_MODULE_6__["default"])(this.dirty()).then(this.onsaved.bind(this), this.loaded.bind(this));
  };

  _proto.onsaved = function onsaved() {
    this.hide();
  };

  return SettingsModal;
}(_common_components_Modal__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/StatusWidget.tsx":
/*!***********************************************!*\
  !*** ./src/admin/components/StatusWidget.tsx ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return StatusWidget; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _DashboardWidget__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./DashboardWidget */ "./src/admin/components/DashboardWidget.tsx");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Dropdown__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _LoadingModal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./LoadingModal */ "./src/admin/components/LoadingModal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }









var StatusWidget = /*#__PURE__*/function (_DashboardWidget) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(StatusWidget, _DashboardWidget);

  var _super = _createSuper(StatusWidget);

  function StatusWidget() {
    return _DashboardWidget.apply(this, arguments) || this;
  }

  var _proto = StatusWidget.prototype;

  _proto.className = function className() {
    return 'StatusWidget';
  };

  _proto.content = function content() {
    return m("ul", null, Object(_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__["default"])(this.items().toArray()));
  };

  _proto.items = function items() {
    var items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    items.add('tools', m(_common_components_Dropdown__WEBPACK_IMPORTED_MODULE_7__["default"], {
      label: _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.transText('core.admin.dashboard.tools_button'),
      icon: "fas fa-cog",
      buttonClassName: "Button",
      menuClassName: "Dropdown-menu--right"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_8__["default"], {
      onclick: this.handleClearCache.bind(this)
    }, _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.dashboard.clear_cache_button'))));
    items.add('version-flarum', [m("strong", null, "Flarum"), m("br", null), _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('version')]);
    items.add('version-php', [m("strong", null, "PHP"), m("br", null), _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.phpVersion]);
    items.add('version-mysql', [m("strong", null, "MySQL"), m("br", null), _app__WEBPACK_IMPORTED_MODULE_3__["default"].data.mysqlVersion]);
    return items;
  };

  _proto.handleClearCache = function handleClearCache(e) {
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].modal.show(_LoadingModal__WEBPACK_IMPORTED_MODULE_9__["default"]);
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
      method: 'DELETE',
      url: _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('apiUrl') + '/cache'
    }).then(function () {
      return window.location.reload();
    });
  };

  return StatusWidget;
}(_DashboardWidget__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/components/UploadImageButton.tsx":
/*!****************************************************!*\
  !*** ./src/admin/components/UploadImageButton.tsx ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return UploadImageButton; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




var UploadImageButton = /*#__PURE__*/function (_Button) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(UploadImageButton, _Button);

  var _super = _createSuper(UploadImageButton);

  function UploadImageButton() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Button.call.apply(_Button, [this].concat(args)) || this;
    _this.loading = false;
    return _this;
  }

  var _proto = UploadImageButton.prototype;

  _proto.view = function view() {
    this.props.loading = this.loading;
    this.props.className = (this.props.className || '') + ' Button';

    if (_app__WEBPACK_IMPORTED_MODULE_3__["default"].data.settings[this.props.name + '_path']) {
      this.props.onclick = this.remove.bind(this);
      this.props.children = _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.upload_image.remove_button');
      return m("div", null, m("p", null, m("img", {
        src: _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute(this.props.name + 'Url'),
        alt: ""
      })), m("p", null, _Button.prototype.view.call(this)));
    } else {
      this.props.onclick = this.upload.bind(this);
      this.props.children = _app__WEBPACK_IMPORTED_MODULE_3__["default"].translator.trans('core.admin.upload_image.upload_button');
    }

    return _Button.prototype.view.call(this);
  }
  /**
   * Prompt the user to upload an image.
   */
  ;

  _proto.upload = function upload() {
    var _this2 = this;

    if (this.loading) return;
    var $input = $('<input type="file">');
    $input.appendTo('body').hide().click().on('change', function (e) {
      var data = new FormData();
      data.append(_this2.props.name, $(e.target)[0].files[0]);
      _this2.loading = true;
      m.redraw();
      _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
        method: 'POST',
        url: _this2.resourceUrl(),
        serialize: function serialize(raw) {
          return raw;
        },
        body: data
      }).then(_this2.success.bind(_this2), _this2.failure.bind(_this2));
    });
  }
  /**
   * Remove the logo.
   */
  ;

  _proto.remove = function remove() {
    this.loading = true;
    m.redraw();
    _app__WEBPACK_IMPORTED_MODULE_3__["default"].request({
      method: 'DELETE',
      url: this.resourceUrl()
    }).then(this.success.bind(this), this.failure.bind(this));
  };

  _proto.resourceUrl = function resourceUrl() {
    return _app__WEBPACK_IMPORTED_MODULE_3__["default"].forum.attribute('apiUrl') + '/' + this.props.name;
  }
  /**
   * After a successful upload/removal, reload the page.
   *
   * @param {Object} response
   * @protected
   */
  ;

  _proto.success = function success(response) {
    window.location.reload();
  }
  /**
   * If upload/removal fails, stop loading.
   *
   * @param {Object} response
   * @protected
   */
  ;

  _proto.failure = function failure(response) {
    this.loading = false;
    m.redraw();
  };

  return UploadImageButton;
}(_common_components_Button__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/admin/index.ts":
/*!****************************!*\
  !*** ./src/admin/index.ts ***!
  \****************************/
/*! exports provided: app, compat */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./app */ "./src/admin/app.ts");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "app", function() { return _app__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _compat__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./compat */ "./src/admin/compat.ts");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "compat", function() { return _compat__WEBPACK_IMPORTED_MODULE_1__["default"]; });


 // Export compat API


_compat__WEBPACK_IMPORTED_MODULE_1__["default"].app = _app__WEBPACK_IMPORTED_MODULE_0__["default"];


/***/ }),

/***/ "./src/admin/routes.ts":
/*!*****************************!*\
  !*** ./src/admin/routes.ts ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _components_BasicsPage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/BasicsPage */ "./src/admin/components/BasicsPage.tsx");
/* harmony import */ var _components_DashboardPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/DashboardPage */ "./src/admin/components/DashboardPage.tsx");
/* harmony import */ var _components_MailPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/MailPage */ "./src/admin/components/MailPage.tsx");
/* harmony import */ var _components_PermissionsPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/PermissionsPage */ "./src/admin/components/PermissionsPage.tsx");
/* harmony import */ var _components_AppearancePage__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/AppearancePage */ "./src/admin/components/AppearancePage.tsx");
/* harmony import */ var _components_ExtensionsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/ExtensionsPage */ "./src/admin/components/ExtensionsPage.tsx");






/* harmony default export */ __webpack_exports__["default"] = (function (app) {
  app.routes = {
    dashboard: {
      path: '/',
      component: _components_DashboardPage__WEBPACK_IMPORTED_MODULE_1__["default"]
    },
    basics: {
      path: '/basics',
      component: _components_BasicsPage__WEBPACK_IMPORTED_MODULE_0__["default"]
    },
    mail: {
      path: '/mail',
      component: _components_MailPage__WEBPACK_IMPORTED_MODULE_2__["default"]
    },
    permissions: {
      path: '/permissions',
      component: _components_PermissionsPage__WEBPACK_IMPORTED_MODULE_3__["default"]
    },
    appearance: {
      path: '/appearance',
      component: _components_AppearancePage__WEBPACK_IMPORTED_MODULE_4__["default"]
    },
    extensions: {
      path: '/extensions',
      component: _components_ExtensionsPage__WEBPACK_IMPORTED_MODULE_5__["default"]
    }
  };
});

/***/ }),

/***/ "./src/admin/utils/saveSettings.ts":
/*!*****************************************!*\
  !*** ./src/admin/utils/saveSettings.ts ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return saveSettings; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../app */ "./src/admin/app.ts");


function saveSettings(settings) {
  var oldSettings = JSON.parse(JSON.stringify(_app__WEBPACK_IMPORTED_MODULE_1__["default"].data.settings));

  Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(_app__WEBPACK_IMPORTED_MODULE_1__["default"].data.settings, settings);

  return _app__WEBPACK_IMPORTED_MODULE_1__["default"].request({
    method: 'POST',
    url: _app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('apiUrl') + '/settings',
    body: settings
  }).catch(function (error) {
    _app__WEBPACK_IMPORTED_MODULE_1__["default"].data.settings = oldSettings;
    throw error;
  });
}

/***/ }),

/***/ "./src/common/Application.ts":
/*!***********************************!*\
  !*** ./src/common/Application.ts ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Application; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _Translator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Translator */ "./src/common/Translator.ts");
/* harmony import */ var _Session__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Session */ "./src/common/Session.ts");
/* harmony import */ var _Store__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Store */ "./src/common/Store.ts");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./extend */ "./src/common/extend.ts");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./utils/extract */ "./src/common/utils/extract.ts");
/* harmony import */ var _utils_mapRoutes__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./utils/mapRoutes */ "./src/common/utils/mapRoutes.ts");
/* harmony import */ var _utils_Drawer__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./utils/Drawer */ "./src/common/utils/Drawer.ts");
/* harmony import */ var _utils_RequestError__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./utils/RequestError */ "./src/common/utils/RequestError.ts");
/* harmony import */ var _utils_ItemList__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _utils_ScrollListener__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./utils/ScrollListener */ "./src/common/utils/ScrollListener.ts");
/* harmony import */ var _models_Forum__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./models/Forum */ "./src/common/models/Forum.ts");
/* harmony import */ var _models_Discussion__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./models/Discussion */ "./src/common/models/Discussion.tsx");
/* harmony import */ var _models_User__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./models/User */ "./src/common/models/User.ts");
/* harmony import */ var _models_Post__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./models/Post */ "./src/common/models/Post.ts");
/* harmony import */ var _models_Group__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _models_Notification__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./models/Notification */ "./src/common/models/Notification.ts");
/* harmony import */ var _components_Alert__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./components/Alert */ "./src/common/components/Alert.tsx");
/* harmony import */ var _components_Button__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _components_ModalManager__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./components/ModalManager */ "./src/common/components/ModalManager.tsx");
/* harmony import */ var _components_RequestErrorModal__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./components/RequestErrorModal */ "./src/common/components/RequestErrorModal.tsx");
/* harmony import */ var lodash_flattenDeep__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! lodash/flattenDeep */ "./node_modules/lodash/flattenDeep.js");
/* harmony import */ var lodash_flattenDeep__WEBPACK_IMPORTED_MODULE_21___default = /*#__PURE__*/__webpack_require__.n(lodash_flattenDeep__WEBPACK_IMPORTED_MODULE_21__);
/* harmony import */ var _components_AlertManager__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./components/AlertManager */ "./src/common/components/AlertManager.tsx");


function _createForOfIteratorHelperLoose(o) { var i = 0; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (o = _unsupportedIterableToArray(o))) return function () { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }; throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } i = o[Symbol.iterator](); return i.next.bind(i); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(n); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
























var Application = /*#__PURE__*/function () {
  function Application() {
    this.forum = void 0;
    this.routes = {};
    this.initializers = new _utils_ItemList__WEBPACK_IMPORTED_MODULE_9__["default"]();
    this.session = void 0;
    this.translator = new _Translator__WEBPACK_IMPORTED_MODULE_1__["default"]();
    this.store = new _Store__WEBPACK_IMPORTED_MODULE_3__["default"]({
      forums: _models_Forum__WEBPACK_IMPORTED_MODULE_11__["default"],
      users: _models_User__WEBPACK_IMPORTED_MODULE_13__["default"],
      discussions: _models_Discussion__WEBPACK_IMPORTED_MODULE_12__["default"],
      posts: _models_Post__WEBPACK_IMPORTED_MODULE_14__["default"],
      groups: _models_Group__WEBPACK_IMPORTED_MODULE_15__["default"],
      notifications: _models_Notification__WEBPACK_IMPORTED_MODULE_16__["default"]
    });
    this.cache = {};
    this.booted = false;
    this.requestError = null;
    this.data = void 0;
    this.title = '';
    this.titleCount = 0;
    this.drawer = new _utils_Drawer__WEBPACK_IMPORTED_MODULE_7__["default"]();
    this.modal = void 0;
    this.alerts = void 0;
  }

  var _proto = Application.prototype;

  _proto.load = function load(payload) {
    this.data = payload;
    this.translator.locale = payload.locale;
  };

  _proto.boot = function boot() {
    var _this = this;

    this.initializers.toArray().forEach(function (initializer) {
      return initializer(_this);
    });
    this.store.pushPayload({
      data: this.data.resources
    });
    this.forum = this.store.getById('forums', 1);
    this.session = new _Session__WEBPACK_IMPORTED_MODULE_2__["default"](this.store.getById('users', this.data.session.userId), this.data.session.csrfToken);
    this.mount();
    this.booted = true;
  };

  _proto.bootExtensions = function bootExtensions(extensions) {
    var _this2 = this;

    Object.keys(extensions).forEach(function (name) {
      var extension = extensions[name];
      var extenders = lodash_flattenDeep__WEBPACK_IMPORTED_MODULE_21___default()(extension.extend);

      for (var _iterator = _createForOfIteratorHelperLoose(extenders), _step; !(_step = _iterator()).done;) {
        var extender = _step.value;
        extender.extend(_this2, {
          name: name,
          exports: extension
        });
      }
    });
  };

  _proto.mount = function mount(basePath) {
    var _this3 = this;

    if (basePath === void 0) {
      basePath = '';
    }

    var $modal = document.getElementById('modal');
    var $alerts = document.getElementById('alerts');
    var $content = document.getElementById('content');
    if ($modal) m.mount($modal, this.modal = new _components_ModalManager__WEBPACK_IMPORTED_MODULE_19__["default"]());
    if ($alerts) m.mount($alerts, this.alerts = new _components_AlertManager__WEBPACK_IMPORTED_MODULE_22__["default"]({
      oninit: function oninit(vnode) {
        return _this3.alerts = vnode.state;
      }
    }));
    if ($content) m.route($content, basePath + '/', Object(_utils_mapRoutes__WEBPACK_IMPORTED_MODULE_6__["default"])(this.routes, basePath)); // Add a class to the body which indicates that the page has been scrolled
    // down.

    new _utils_ScrollListener__WEBPACK_IMPORTED_MODULE_10__["default"](function (top) {
      var $app = $('#app');
      var offset = $app.offset().top;
      $app.toggleClass('affix', top >= offset).toggleClass('scrolled', top > offset);
    }).start();
    $(function () {
      $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
    });
  }
  /**
   * Get the API response document that has been preloaded into the application.
   */
  ;

  _proto.preloadedApiDocument = function preloadedApiDocument() {
    if (this.data.apiDocument) {
      var results = this.store.pushPayload(this.data.apiDocument);
      this.data.apiDocument = null;
      return results;
    }

    return null;
  }
  /**
   * Set the <title> of the page.
   */
  ;

  _proto.setTitle = function setTitle(title) {
    this.title = title;
    this.updateTitle();
  }
  /**
   * Set a number to display in the <title> of the page.
   */
  ;

  _proto.setTitleCount = function setTitleCount(count) {
    this.titleCount = count;
    this.updateTitle();
  };

  _proto.updateTitle = function updateTitle() {
    document.title = (this.titleCount ? "(" + this.titleCount + ") " : '') + (this.title ? this.title + ' - ' : '') + this.forum.attribute('title');
  }
  /**
   * Construct a URL to the route with the given name.
   */
  ;

  _proto.route = function route(name, params) {
    if (params === void 0) {
      params = {};
    }

    var route = this.routes[name];
    if (!route) throw new Error("Route '" + name + "' does not exist");
    var url = route.path.replace(/:([^\/]+)/g, function (m, key) {
      return Object(_utils_extract__WEBPACK_IMPORTED_MODULE_5__["default"])(params, key);
    }); // Remove falsy values in params to avoid
    // having urls like '/?sort&q'

    for (var _key in params) {
      if (params.hasOwnProperty(_key) && !params[_key]) delete params[_key];
    }

    var queryString = m.buildQueryString(params);
    var prefix = m.route.prefix === '' ? this.forum.attribute('basePath') : '';
    return prefix + url + (queryString ? '?' + queryString : '');
  }
  /**
   * Make an AJAX request, handling any low-level errors that may occur.
   *
   * @see https://mithril.js.org/request.html
   */
  ;

  _proto.request = function request(originalOptions) {
    var _this4 = this;

    var options = Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, originalOptions); // Set some default options if they haven't been overridden. We want to
    // authenticate all requests with the session token. We also want all
    // requests to run asynchronously in the background, so that they don't
    // prevent redraws from occurring.


    options.background = options.background || true;
    Object(_extend__WEBPACK_IMPORTED_MODULE_4__["extend"])(options, 'config', function (result, xhr) {
      return xhr.setRequestHeader('X-CSRF-Token', _this4.session.csrfToken);
    }); // If the method is something like PATCH or DELETE, which not all servers
    // and clients support, then we'll send it as a POST request with the
    // intended method specified in the X-HTTP-Method-Override header.

    if (options.method !== 'GET' && options.method !== 'POST') {
      var method = options.method;
      Object(_extend__WEBPACK_IMPORTED_MODULE_4__["extend"])(options, 'config', function (result, xhr) {
        return xhr.setRequestHeader('X-HTTP-Method-Override', method);
      });
      options.method = 'POST';
    } // When we deserialize JSON data, if for some reason the server has provided
    // a dud response, we don't want the application to crash. We'll show an
    // error message to the user instead.


    options.deserialize = options.deserialize || function (responseText) {
      return responseText;
    };

    options.errorHandler = options.errorHandler || function (error) {
      throw error;
    }; // When extracting the data from the response, we can check the server
    // response code and show an error message to the user if something's gone
    // awry.


    var original = options.extract;

    options.extract = function (xhr) {
      var responseText;

      if (original) {
        responseText = original(xhr.responseText);
      } else {
        responseText = xhr.responseText || null;
      }

      var status = xhr.status;

      if (status < 200 || status > 299) {
        throw new _utils_RequestError__WEBPACK_IMPORTED_MODULE_8__["default"](status, responseText, options, xhr);
      }

      if (xhr.getResponseHeader) {
        var csrfToken = xhr.getResponseHeader('X-CSRF-Token');
        if (csrfToken) app.session.csrfToken = csrfToken;
      }

      try {
        return JSON.parse(responseText);
      } catch (e) {
        throw new _utils_RequestError__WEBPACK_IMPORTED_MODULE_8__["default"](500, responseText, options, xhr);
      }
    };

    if (this.requestError) this.alerts.dismiss(this.requestError.alert); // Now make the request. If it's a failure, inspect the error that was
    // returned and show an alert containing its contents.

    return m.request(options).then(function (res) {
      return res;
    }, function (error) {
      _this4.requestError = error;
      var children;

      switch (error.status) {
        case 422:
          children = error.response.errors.map(function (error) {
            return [error.detail, m('br')];
          }).reduce(function (a, b) {
            return a.concat(b);
          }, []).slice(0, -1);
          break;

        case 401:
        case 403:
          children = _this4.translator.trans('core.lib.error.permission_denied_message');
          break;

        case 404:
        case 410:
          children = _this4.translator.trans('core.lib.error.not_found_message');
          break;

        case 429:
          children = _this4.translator.trans('core.lib.error.rate_limit_exceeded_message');
          break;

        default:
          children = _this4.translator.trans('core.lib.error.generic_message');
      }

      var isDebug = app.forum.attribute('debug');
      error.alert = _components_Alert__WEBPACK_IMPORTED_MODULE_17__["default"].component({
        type: 'error',
        children: children,
        controls: isDebug && [_components_Button__WEBPACK_IMPORTED_MODULE_18__["default"].component({
          className: 'Button Button--link',
          onclick: _this4.showDebug.bind(_this4, error),
          children: 'DEBUG' // TODO make translatable

        })]
      });

      try {
        options.errorHandler(error);
      } catch (error) {
        console.error(error);

        _this4.alerts.show(error.alert);
      }

      return Promise.reject(error);
    });
  };

  _proto.showDebug = function showDebug(error) {
    this.alerts.dismiss(this.requestError.alert);
    this.modal.show(_components_RequestErrorModal__WEBPACK_IMPORTED_MODULE_20__["default"], {
      error: error
    });
  };

  return Application;
}();



/***/ }),

/***/ "./src/common/Component.ts":
/*!*********************************!*\
  !*** ./src/common/Component.ts ***!
  \*********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Component; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


var Component = /*#__PURE__*/function () {
  function Component(props) {
    if (props === void 0) {
      props = {};
    }

    this.element = void 0;
    this.props = void 0;
    this.props = props.tag ? {} : props;
  }

  var _proto = Component.prototype;

  _proto.view = function view(vnode) {
    throw new Error('Component#view must be implemented by subclass');
  };

  _proto.oninit = function oninit(vnode) {
    this.setProps(vnode);
  };

  _proto.oncreate = function oncreate(vnode) {
    this.setProps(vnode);
    this.element = vnode.dom;
  };

  _proto.onbeforeupdate = function onbeforeupdate(vnode) {
    this.setProps(vnode);
  };

  _proto.onupdate = function onupdate(vnode) {
    this.setProps(vnode);
  };

  _proto.onbeforeremove = function onbeforeremove(vnode) {
    this.setProps(vnode);
  };

  _proto.onremove = function onremove(vnode) {
    this.setProps(vnode);
  }
  /**
   * Returns a jQuery object for this component's element. If you pass in a
   * selector string, this method will return a jQuery object, using the current
   * element as its buffer.
   *
   * For example, calling `component.$('li')` will return a jQuery object
   * containing all of the `li` elements inside the DOM element of this
   * component.
   *
   * @param selector a jQuery-compatible selector string
   * @final
   */
  ;

  _proto.$ = function (_$) {
    function $(_x) {
      return _$.apply(this, arguments);
    }

    $.toString = function () {
      return _$.toString();
    };

    return $;
  }(function (selector) {
    var $element = $(this.element);
    return selector ? $element.find(selector) : $element;
  });

  _proto.render = function render() {
    return m(this.constructor, this.props);
  };

  Component.component = function component(props, children) {
    if (props === void 0) {
      props = {};
    }

    var componentProps = Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, props);

    if (children) componentProps.children = children;
    return m(this, componentProps);
  };

  Component.initProps = function initProps(props) {
    if (props === void 0) {
      props = {};
    }
  };

  _proto.setProps = function setProps(vnode) {
    var props = vnode.attrs || {};
    this.constructor.initProps(props);
    if (!props.children) props.children = vnode.children;
    this.props = props;
  };

  return Component;
}();



/***/ }),

/***/ "./src/common/Model.ts":
/*!*****************************!*\
  !*** ./src/common/Model.ts ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Model; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


/**
 * The `Model` class represents a local data resource. It provides methods to
 * persist changes via the API.
 */
var Model = /*#__PURE__*/function () {
  /**
   * The resource object from the API.
   */

  /**
   * The time at which the model's data was last updated. Watching the value
   * of this property is a fast way to retain/cache a subtree if data hasn't
   * changed.
   */

  /**
   * Whether or not the resource exists on the server.
   */

  /**
   * The data store that this resource should be persisted to.
   */

  /**
   * @param data A resource object from the API.
   * @param store The data store that this model should be persisted to.
   */
  function Model(data, store) {
    if (data === void 0) {
      data = {};
    }

    this.data = void 0;
    this.payload = void 0;
    this.freshness = void 0;
    this.exists = void 0;
    this.store = void 0;
    this.data = data;
    this.store = store;
    this.freshness = new Date();
    this.exists = false;
  }
  /**
   * Get the model's ID.
   * @final
   */


  var _proto = Model.prototype;

  _proto.id = function id() {
    return this.data.id;
  }
  /**
   * Get one of the model's attributes.
   * @final
   */
  ;

  _proto.attribute = function attribute(_attribute) {
    return this.data.attributes && this.data.attributes[_attribute];
  }
  /**
   * Merge new data into this model locally.
   *
   * @param data A resource object to merge into this model
   */
  ;

  _proto.pushData = function pushData(data) {
    // Since most of the top-level items in a resource object are objects
    // (e.g. relationships, attributes), we'll need to check and perform the
    // merge at the second level if that's the case.
    for (var _key in data) {
      if (typeof data[_key] === 'object') {
        this.data[_key] = this.data[_key] || {}; // For every item in a second-level object, we want to check if we've
        // been handed a Model instance. If so, we will convert it to a
        // relationship data object.

        for (var innerKey in data[_key]) {
          if (data[_key][innerKey] instanceof Model) {
            data[_key][innerKey] = {
              data: Model.getIdentifier(data[_key][innerKey])
            };
          }

          this.data[_key][innerKey] = data[_key][innerKey];
        }
      } else {
        this.data[_key] = data[_key];
      }
    } // Now that we've updated the data, we can say that the model is fresh.
    // This is an easy way to invalidate retained subtrees etc.


    this.freshness = new Date();
  }
  /**
   * Merge new attributes into this model locally.
   *
   * @param attributes The attributes to merge.
   */
  ;

  _proto.pushAttributes = function pushAttributes(attributes) {
    this.pushData({
      attributes: attributes
    });
  }
  /**
   * Merge new attributes into this model, both locally and with persistence.
   *
   * @param attributes The attributes to save. If a 'relationships' key
   *     exists, it will be extracted and relationships will also be saved.
   * @param [options]
   */
  ;

  _proto.save = function save(attributes, options) {
    var _this = this;

    if (options === void 0) {
      options = {};
    }

    var data = {
      type: this.data.type,
      id: this.data.id,
      attributes: attributes
    }; // If a 'relationships' key exists, extract it from the attributes hash and
    // set it on the top-level data object instead. We will be sending this data
    // object to the API for persistence.

    if (attributes.relationships) {
      data.relationships = {};

      for (var _key2 in attributes.relationships) {
        var model = attributes.relationships[_key2];
        data.relationships[_key2] = {
          data: model instanceof Array ? model.map(Model.getIdentifier) : Model.getIdentifier(model)
        };
      }

      delete attributes.relationships;
    } // Before we update the model's data, we should make a copy of the model's
    // old data so that we can revert back to it if something goes awry during
    // persistence.


    var oldData = this.copyData();
    this.pushData(data);
    var request = {
      data: data
    };
    if (options.meta) request.meta = options.meta;
    return app.request(Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      method: this.exists ? 'PATCH' : 'POST',
      url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
      body: request
    }, options)).then( // If everything went well, we'll make sure the store knows that this
    // model exists now (if it didn't already), and we'll push the data that
    // the API returned into the store.
    function (payload) {
      _this.store.data[payload.data.type] = _this.store.data[payload.data.type] || {};
      _this.store.data[payload.data.type][payload.data.id] = _this;
      return _this.store.pushPayload(payload);
    }, // If something went wrong, though... good thing we backed up our model's
    // old data! We'll revert to that and let others handle the error.
    function (response) {
      _this.pushData(oldData);

      m.redraw();
      throw response;
    });
  }
  /**
   * Send a request to delete the resource.
   *
   * @param {Object} body Data to send along with the DELETE request.
   * @param {Object} [options]
   * @return {Promise}
   * @public
   */
  ;

  _proto.delete = function _delete(body, options) {
    var _this2 = this;

    if (body === void 0) {
      body = {};
    }

    if (options === void 0) {
      options = {};
    }

    if (!this.exists) return Promise.resolve();
    return app.request(Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      method: 'DELETE',
      url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
      body: body
    }, options)).then(function () {
      _this2.exists = false;

      _this2.store.remove(_this2);
    });
  }
  /**
   * Construct a path to the API endpoint for this resource.
   *
   * @return {String}
   * @protected
   */
  ;

  _proto.apiEndpoint = function apiEndpoint() {
    return '/' + this.data.type + (this.exists ? '/' + this.data.id : '');
  };

  _proto.copyData = function copyData() {
    return JSON.parse(JSON.stringify(this.data));
  }
  /**
   * Generate a function which returns the value of the given attribute.
   *
   * @param name
   * @param [transform] A function to transform the attribute value
   */
  ;

  Model.attribute = function attribute(name, transform) {
    return function () {
      var value = this.data.attributes && this.data.attributes[name];
      return transform ? transform(value) : value;
    };
  }
  /**
   * Generate a function which returns the value of the given has-one
   * relationship.
   *
   * @return false if no information about the
   *     relationship exists; undefined if the relationship exists but the model
   *     has not been loaded; or the model if it has been loaded.
   */
  ;

  Model.hasOne = function hasOne(name) {
    return function () {
      if (this.data.relationships) {
        var relationship = this.data.relationships[name];

        if (relationship && !Array.isArray(relationship.data)) {
          return app.store.getById(relationship.data.type, relationship.data.id);
        }
      }

      return false;
    };
  }
  /**
   * Generate a function which returns the value of the given has-many
   * relationship.
   *
   * @return false if no information about the relationship
   *     exists; an array if it does, containing models if they have been
   *     loaded, and undefined for those that have not.
   */
  ;

  Model.hasMany = function hasMany(name) {
    return function () {
      if (this.data.relationships) {
        var relationship = this.data.relationships[name];

        if (relationship && Array.isArray(relationship.data)) {
          return relationship.data.map(function (data) {
            return app.store.getById(data.type, data.id);
          });
        }
      }

      return false;
    };
  }
  /**
   * Transform the given value into a Date object.
   */
  ;

  Model.transformDate = function transformDate(value) {
    return value ? new Date(value) : null;
  }
  /**
   * Get a resource identifier object for the given model.
   */
  ;

  Model.getIdentifier = function getIdentifier(model) {
    return {
      type: model.data.type,
      id: model.data.id
    };
  };

  return Model;
}();



/***/ }),

/***/ "./src/common/Session.ts":
/*!*******************************!*\
  !*** ./src/common/Session.ts ***!
  \*******************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Session; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


/**
 * The `Session` class defines the current user session. It stores a reference
 * to the current authenticated user, and provides methods to log in/out.
 */
var Session = /*#__PURE__*/function () {
  /**
   * The current authenticated user.
   */

  /**
   * The CSRF token.
   */
  function Session(user, csrfToken) {
    this.user = void 0;
    this.csrfToken = void 0;
    this.user = user;
    this.csrfToken = csrfToken;
  }
  /**
   * Attempt to log in a user.
   */


  var _proto = Session.prototype;

  _proto.login = function login(body, options) {
    if (options === void 0) {
      options = {};
    }

    return app.request(Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      method: 'POST',
      url: app.forum.attribute('baseUrl') + "/login",
      body: body
    }, options));
  }
  /**
   * Log the user out.
   *
   * @public
   */
  ;

  _proto.logout = function logout() {
    window.location.href = app.forum.attribute('baseUrl') + "/logout?token=" + this.csrfToken;
  };

  return Session;
}();



/***/ }),

/***/ "./src/common/Store.ts":
/*!*****************************!*\
  !*** ./src/common/Store.ts ***!
  \*****************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Store; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
var Store = /*#__PURE__*/function () {
  /**
   * The local data store. A tree of resource types to IDs, such that
   * accessing data[type][id] will return the model for that type/ID.
   */

  /**
   * The model registry. A map of resource types to the model class that
   * should be used to represent resources of that type.
   */
  function Store(models) {
    this.data = {};
    this.models = void 0;
    this.models = models;
  }
  /**
   * Push resources contained within an API payload into the store.
   *
   * @param payload
   * @return The model(s) representing the resource(s) contained
   *     within the 'data' key of the payload.
   */


  var _proto = Store.prototype;

  _proto.pushPayload = function pushPayload(payload) {
    if (payload.included) payload.included.map(this.pushObject.bind(this));
    var result = payload.data instanceof Array ? payload.data.map(this.pushObject.bind(this)) : this.pushObject(payload.data); // Attach the original payload to the model that we give back. This is
    // useful to consumers as it allows them to access meta information
    // associated with their request.

    result.payload = payload;
    return result;
  }
  /**
   * Create a model to represent a resource object (or update an existing one),
   * and push it into the store.
   *
   * @param {Object} data The resource object
   * @return The model, or null if no model class has been
   *     registered for this resource type.
   */
  ;

  _proto.pushObject = function pushObject(data) {
    if (!this.models[data.type]) return null;
    var type = this.data[data.type] = this.data[data.type] || {};

    if (type[data.id]) {
      type[data.id].pushData(data);
    } else {
      type[data.id] = this.createRecord(data.type, data);
    }

    type[data.id].exists = true;
    return type[data.id];
  }
  /**
   * Make a request to the API to find record(s) of a specific type.
   *
   * @param type The resource type.
   * @param [id] The ID(s) of the model(s) to retrieve.
   *     Alternatively, if an object is passed, it will be handled as the
   *     `query` parameter.
   * @param query
   * @param options
   */
  ;

  _proto.find = function find(type, id, query, options) {
    if (query === void 0) {
      query = {};
    }

    if (options === void 0) {
      options = {};
    }

    var params = query;
    var url = app.forum.attribute('apiUrl') + "/" + type;

    if (id instanceof Array) {
      url += "?filter[id]=" + id.join(',');
    } else if (typeof id === 'object') {
      params = id;
    } else if (id) {
      url += "/" + id;
    }

    return app.request(Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({
      method: 'GET',
      url: url,
      params: params
    }, options)).then(this.pushPayload.bind(this));
  }
  /**
   * Get a record from the store by ID.
   *
   * @param type The resource type.
   * @param id The resource ID.
   */
  ;

  _proto.getById = function getById(type, id) {
    return this.data[type] && this.data[type][id];
  }
  /**
   * Get a record from the store by the value of a model attribute.
   *
   * @param type The resource type.
   * @param key The name of the method on the model.
   * @param value The value of the model attribute.
   */
  ;

  _proto.getBy = function getBy(type, key, value) {
    return this.all(type).filter(function (model) {
      return model[key]() === value;
    })[0];
  }
  /**
   * Get all loaded records of a specific type.
   */
  ;

  _proto.all = function all(type) {
    var records = this.data[type];
    return records ? Object.keys(records).map(function (id) {
      return records[id];
    }) : [];
  }
  /**
   * Remove the given model from the store.
   */
  ;

  _proto.remove = function remove(model) {
    delete this.data[model.data.type][model.id()];
  }
  /**
   * Create a new record of the given type.
   *
   * @param {String} type The resource type
   * @param {Object} [data] Any data to initialize the model with
   */
  ;

  _proto.createRecord = function createRecord(type, data) {
    if (data === void 0) {
      data = {};
    }

    data.type = data.type || type;
    return new this.models[type](data, this);
  };

  return Store;
}();



/***/ }),

/***/ "./src/common/Translator.ts":
/*!**********************************!*\
  !*** ./src/common/Translator.ts ***!
  \**********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Translator; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/extract */ "./src/common/utils/extract.ts");
/* harmony import */ var _utils_extractText__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _helpers_username__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./helpers/username */ "./src/common/helpers/username.tsx");





var Translator = /*#__PURE__*/function () {
  function Translator() {
    this.translations = {};
    this.locale = void 0;
  }

  var _proto = Translator.prototype;

  _proto.addTranslations = function addTranslations(translations) {
    Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(this.translations, translations);
  };

  _proto.trans = function trans(id, parameters) {
    var translation = this.translations[id];

    if (translation) {
      return this.apply(translation, parameters || {});
    }

    return id;
  };

  _proto.transText = function transText(id, parameters) {
    return Object(_utils_extractText__WEBPACK_IMPORTED_MODULE_2__["default"])(this.trans(id, parameters));
  };

  _proto.transChoice = function transChoice(id, number, parameters) {
    var translation = this.translations[id];

    if (translation) {
      translation = this.pluralize(translation, number);
      return this.apply(translation, parameters || {});
    }

    return id;
  };

  _proto.apply = function apply(translation, input) {
    if ('user' in input) {
      var user = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_1__["default"])(input, 'user');
      if (!input.username) input.username = Object(_helpers_username__WEBPACK_IMPORTED_MODULE_3__["default"])(user);
    }

    var parts = translation.split(new RegExp('({[a-z0-9_]+}|</?[a-z0-9_]+>)', 'gi'));
    var hydrated = [];
    var open = [hydrated];
    parts.forEach(function (part) {
      var match = part.match(new RegExp('{([a-z0-9_]+)}|<(/?)([a-z0-9_]+)>', 'i'));

      if (match) {
        if (match[1]) {
          open[0].push(input[match[1]]);
        } else if (match[3]) {
          if (match[2]) {
            open.shift();
          } else {
            var tag = input[match[3]] || {
              tag: match[3],
              children: []
            };
            open[0].push(tag);
            open.unshift(tag.children || tag);
          }
        }
      } else {
        open[0].push({
          tag: 'span',
          text: part
        });
      }
    });
    return hydrated.filter(function (part) {
      return part;
    });
  };

  _proto.pluralize = function pluralize(translation, number) {
    var _this = this;

    var sPluralRegex = new RegExp(/^\w+\: +(.+)$/),
        cPluralRegex = new RegExp(/^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/),
        iPluralRegex = new RegExp(/^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/),
        standardRules = [],
        explicitRules = [];
    translation.split('|').forEach(function (part) {
      if (cPluralRegex.test(part)) {
        var matches = part.match(cPluralRegex);
        explicitRules[matches[0]] = matches[matches.length - 1];
      } else if (sPluralRegex.test(part)) {
        var _matches = part.match(sPluralRegex);

        standardRules.push(_matches[1]);
      } else {
        standardRules.push(part);
      }
    });
    explicitRules.forEach(function (rule, e) {
      if (iPluralRegex.test(e)) {
        var matches = e.match(iPluralRegex);

        if (matches[1]) {
          var ns = matches[2].split(',');

          for (var n in ns) {
            if (number == ns[n]) {
              return explicitRules[e];
            }
          }
        } else {
          var leftNumber = _this.convertNumber(matches[4]);

          var rightNumber = _this.convertNumber(matches[5]);

          if (('[' === matches[3] ? number >= leftNumber : number > leftNumber) && (']' === matches[6] ? number <= rightNumber : number < rightNumber)) {
            return explicitRules[e];
          }
        }
      }
    });
    return standardRules[this.pluralPosition(number, this.locale)] || standardRules[0] || undefined;
  };

  _proto.convertNumber = function convertNumber(number) {
    if ('-Inf' === number) {
      return Number.NEGATIVE_INFINITY;
    } else if ('+Inf' === number || 'Inf' === number) {
      return Number.POSITIVE_INFINITY;
    }

    return parseInt(number, 10);
  };

  _proto.pluralPosition = function pluralPosition(number, locale) {
    if ('pt_BR' === locale) {
      locale = 'xbr';
    }

    if (locale.length > 3) {
      locale = locale.split('_')[0];
    }

    switch (locale) {
      case 'bo':
      case 'dz':
      case 'id':
      case 'ja':
      case 'jv':
      case 'ka':
      case 'km':
      case 'kn':
      case 'ko':
      case 'ms':
      case 'th':
      case 'vi':
      case 'zh':
        return 0;

      case 'af':
      case 'az':
      case 'bn':
      case 'bg':
      case 'ca':
      case 'da':
      case 'de':
      case 'el':
      case 'en':
      case 'eo':
      case 'es':
      case 'et':
      case 'eu':
      case 'fa':
      case 'fi':
      case 'fo':
      case 'fur':
      case 'fy':
      case 'gl':
      case 'gu':
      case 'ha':
      case 'he':
      case 'hu':
      case 'is':
      case 'it':
      case 'ku':
      case 'lb':
      case 'ml':
      case 'mn':
      case 'mr':
      case 'nah':
      case 'nb':
      case 'ne':
      case 'nl':
      case 'nn':
      case 'no':
      case 'om':
      case 'or':
      case 'pa':
      case 'pap':
      case 'ps':
      case 'pt':
      case 'so':
      case 'sq':
      case 'sv':
      case 'sw':
      case 'ta':
      case 'te':
      case 'tk':
      case 'tr':
      case 'ur':
      case 'zu':
        return number == 1 ? 0 : 1;

      case 'am':
      case 'bh':
      case 'fil':
      case 'fr':
      case 'gun':
      case 'hi':
      case 'ln':
      case 'mg':
      case 'nso':
      case 'xbr':
      case 'ti':
      case 'wa':
        return number === 0 || number == 1 ? 0 : 1;

      case 'be':
      case 'bs':
      case 'hr':
      case 'ru':
      case 'sr':
      case 'uk':
        return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;

      case 'cs':
      case 'sk':
        return number == 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;

      case 'ga':
        return number == 1 ? 0 : number == 2 ? 1 : 2;

      case 'lt':
        return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;

      case 'sl':
        return number % 100 == 1 ? 0 : number % 100 == 2 ? 1 : number % 100 == 3 || number % 100 == 4 ? 2 : 3;

      case 'mk':
        return number % 10 == 1 ? 0 : 1;

      case 'mt':
        return number == 1 ? 0 : number === 0 || number % 100 > 1 && number % 100 < 11 ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;

      case 'lv':
        return number === 0 ? 0 : number % 10 == 1 && number % 100 != 11 ? 1 : 2;

      case 'pl':
        return number == 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;

      case 'cy':
        return number == 1 ? 0 : number == 2 ? 1 : number == 8 || number == 11 ? 2 : 3;

      case 'ro':
        return number == 1 ? 0 : number === 0 || number % 100 > 0 && number % 100 < 20 ? 1 : 2;

      case 'ar':
        return number === 0 ? 0 : number == 1 ? 1 : number == 2 ? 2 : number >= 3 && number <= 10 ? 3 : number >= 11 && number <= 99 ? 4 : 5;

      default:
        return 0;
    }
  };

  return Translator;
}();



/***/ }),

/***/ "./src/common/compat.ts":
/*!******************************!*\
  !*** ./src/common/compat.ts ***!
  \******************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Application__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Application */ "./src/common/Application.ts");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Component */ "./src/common/Component.ts");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./extend */ "./src/common/extend.ts");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Model */ "./src/common/Model.ts");
/* harmony import */ var _Session__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Session */ "./src/common/Session.ts");
/* harmony import */ var _Store__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Store */ "./src/common/Store.ts");
/* harmony import */ var _Translator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Translator */ "./src/common/Translator.ts");
/* harmony import */ var _utils_Evented__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./utils/Evented */ "./src/common/utils/Evented.ts");
/* harmony import */ var _utils_ItemList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _utils_humanTime__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/humanTime */ "./src/common/utils/humanTime.ts");
/* harmony import */ var _utils_computed__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./utils/computed */ "./src/common/utils/computed.ts");
/* harmony import */ var _utils_Drawer__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./utils/Drawer */ "./src/common/utils/Drawer.ts");
/* harmony import */ var _utils_anchorScroll__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./utils/anchorScroll */ "./src/common/utils/anchorScroll.ts");
/* harmony import */ var _utils_RequestError__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./utils/RequestError */ "./src/common/utils/RequestError.ts");
/* harmony import */ var _utils_abbreviateNumber__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./utils/abbreviateNumber */ "./src/common/utils/abbreviateNumber.tsx");
/* harmony import */ var _utils_string__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./utils/string */ "./src/common/utils/string.ts");
/* harmony import */ var _utils_SubtreeRetainer__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./utils/SubtreeRetainer */ "./src/common/utils/SubtreeRetainer.ts");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./utils/extract */ "./src/common/utils/extract.ts");
/* harmony import */ var _utils_ScrollListener__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./utils/ScrollListener */ "./src/common/utils/ScrollListener.ts");
/* harmony import */ var _utils_stringToColor__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./utils/stringToColor */ "./src/common/utils/stringToColor.ts");
/* harmony import */ var _utils_patchMithril__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./utils/patchMithril */ "./src/common/utils/patchMithril.ts");
/* harmony import */ var _utils_extractText__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _utils_formatNumber__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./utils/formatNumber */ "./src/common/utils/formatNumber.ts");
/* harmony import */ var _utils_mapRoutes__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ./utils/mapRoutes */ "./src/common/utils/mapRoutes.ts");
/* harmony import */ var _models_Notification__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ./models/Notification */ "./src/common/models/Notification.ts");
/* harmony import */ var _models_User__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ./models/User */ "./src/common/models/User.ts");
/* harmony import */ var _models_Post__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ./models/Post */ "./src/common/models/Post.ts");
/* harmony import */ var _models_Discussion__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ./models/Discussion */ "./src/common/models/Discussion.tsx");
/* harmony import */ var _models_Group__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _models_Forum__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./models/Forum */ "./src/common/models/Forum.ts");
/* harmony import */ var _components_AlertManager__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./components/AlertManager */ "./src/common/components/AlertManager.tsx");
/* harmony import */ var _components_Switch__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ./components/Switch */ "./src/common/components/Switch.tsx");
/* harmony import */ var _components_Badge__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ./components/Badge */ "./src/common/components/Badge.tsx");
/* harmony import */ var _components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ./components/LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _components_Placeholder__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ./components/Placeholder */ "./src/common/components/Placeholder.tsx");
/* harmony import */ var _components_Separator__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ./components/Separator */ "./src/common/components/Separator.tsx");
/* harmony import */ var _components_Dropdown__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ./components/Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _components_SplitDropdown__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! ./components/SplitDropdown */ "./src/common/components/SplitDropdown.tsx");
/* harmony import */ var _components_RequestErrorModal__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! ./components/RequestErrorModal */ "./src/common/components/RequestErrorModal.tsx");
/* harmony import */ var _components_FieldSet__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! ./components/FieldSet */ "./src/common/components/FieldSet.tsx");
/* harmony import */ var _components_Select__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! ./components/Select */ "./src/common/components/Select.tsx");
/* harmony import */ var _components_Navigation__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! ./components/Navigation */ "./src/common/components/Navigation.tsx");
/* harmony import */ var _components_Alert__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! ./components/Alert */ "./src/common/components/Alert.tsx");
/* harmony import */ var _components_LinkButton__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! ./components/LinkButton */ "./src/common/components/LinkButton.tsx");
/* harmony import */ var _components_Checkbox__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! ./components/Checkbox */ "./src/common/components/Checkbox.tsx");
/* harmony import */ var _components_SelectDropdown__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! ./components/SelectDropdown */ "./src/common/components/SelectDropdown.tsx");
/* harmony import */ var _components_ModalManager__WEBPACK_IMPORTED_MODULE_46__ = __webpack_require__(/*! ./components/ModalManager */ "./src/common/components/ModalManager.tsx");
/* harmony import */ var _components_Button__WEBPACK_IMPORTED_MODULE_47__ = __webpack_require__(/*! ./components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _components_Modal__WEBPACK_IMPORTED_MODULE_48__ = __webpack_require__(/*! ./components/Modal */ "./src/common/components/Modal.tsx");
/* harmony import */ var _components_GroupBadge__WEBPACK_IMPORTED_MODULE_49__ = __webpack_require__(/*! ./components/GroupBadge */ "./src/common/components/GroupBadge.ts");
/* harmony import */ var _helpers_fullTime__WEBPACK_IMPORTED_MODULE_50__ = __webpack_require__(/*! ./helpers/fullTime */ "./src/common/helpers/fullTime.tsx");
/* harmony import */ var _helpers_avatar__WEBPACK_IMPORTED_MODULE_51__ = __webpack_require__(/*! ./helpers/avatar */ "./src/common/helpers/avatar.tsx");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_52__ = __webpack_require__(/*! ./helpers/icon */ "./src/common/helpers/icon.tsx");
/* harmony import */ var _helpers_humanTime__WEBPACK_IMPORTED_MODULE_53__ = __webpack_require__(/*! ./helpers/humanTime */ "./src/common/helpers/humanTime.tsx");
/* harmony import */ var _helpers_highlight__WEBPACK_IMPORTED_MODULE_54__ = __webpack_require__(/*! ./helpers/highlight */ "./src/common/helpers/highlight.ts");
/* harmony import */ var _helpers_username__WEBPACK_IMPORTED_MODULE_55__ = __webpack_require__(/*! ./helpers/username */ "./src/common/helpers/username.tsx");
/* harmony import */ var _helpers_userOnline__WEBPACK_IMPORTED_MODULE_56__ = __webpack_require__(/*! ./helpers/userOnline */ "./src/common/helpers/userOnline.tsx");
/* harmony import */ var _helpers_listItems__WEBPACK_IMPORTED_MODULE_57__ = __webpack_require__(/*! ./helpers/listItems */ "./src/common/helpers/listItems.tsx");







 // import liveHumanTimes from './utils/liveHumanTimes';














































 // import punctuateSeries from './helpers/punctuateSeries';





/* harmony default export */ __webpack_exports__["default"] = ({
  Application: _Application__WEBPACK_IMPORTED_MODULE_0__["default"],
  Component: _Component__WEBPACK_IMPORTED_MODULE_1__["default"],
  extend: _extend__WEBPACK_IMPORTED_MODULE_2__,
  Model: _Model__WEBPACK_IMPORTED_MODULE_3__["default"],
  Session: _Session__WEBPACK_IMPORTED_MODULE_4__["default"],
  Store: _Store__WEBPACK_IMPORTED_MODULE_5__["default"],
  Translator: _Translator__WEBPACK_IMPORTED_MODULE_6__["default"],
  'utils/Evented': _utils_Evented__WEBPACK_IMPORTED_MODULE_7__["default"],
  // 'utils/liveHumanTimes': liveHumanTimes,
  'utils/ItemList': _utils_ItemList__WEBPACK_IMPORTED_MODULE_8__["default"],
  'utils/humanTime': _utils_humanTime__WEBPACK_IMPORTED_MODULE_9__["default"],
  'utils/computed': _utils_computed__WEBPACK_IMPORTED_MODULE_10__["default"],
  'utils/Drawer': _utils_Drawer__WEBPACK_IMPORTED_MODULE_11__["default"],
  'utils/anchorScroll': _utils_anchorScroll__WEBPACK_IMPORTED_MODULE_12__["default"],
  'utils/RequestError': _utils_RequestError__WEBPACK_IMPORTED_MODULE_13__["default"],
  'utils/abbreviateNumber': _utils_abbreviateNumber__WEBPACK_IMPORTED_MODULE_14__["default"],
  'utils/string': _utils_string__WEBPACK_IMPORTED_MODULE_15__,
  'utils/SubtreeRetainer': _utils_SubtreeRetainer__WEBPACK_IMPORTED_MODULE_16__["default"],
  'utils/extract': _utils_extract__WEBPACK_IMPORTED_MODULE_17__["default"],
  'utils/ScrollListener': _utils_ScrollListener__WEBPACK_IMPORTED_MODULE_18__["default"],
  'utils/stringToColor': _utils_stringToColor__WEBPACK_IMPORTED_MODULE_19__["default"],
  'utils/patchMithril': _utils_patchMithril__WEBPACK_IMPORTED_MODULE_20__["default"],
  'utils/extractText': _utils_extractText__WEBPACK_IMPORTED_MODULE_21__["default"],
  'utils/formatNumber': _utils_formatNumber__WEBPACK_IMPORTED_MODULE_22__["default"],
  'utils/mapRoutes': _utils_mapRoutes__WEBPACK_IMPORTED_MODULE_23__["default"],
  'models/Notification': _models_Notification__WEBPACK_IMPORTED_MODULE_24__["default"],
  'models/User': _models_User__WEBPACK_IMPORTED_MODULE_25__["default"],
  'models/Post': _models_Post__WEBPACK_IMPORTED_MODULE_26__["default"],
  'models/Discussion': _models_Discussion__WEBPACK_IMPORTED_MODULE_27__["default"],
  'models/Group': _models_Group__WEBPACK_IMPORTED_MODULE_28__["default"],
  'models/Forum': _models_Forum__WEBPACK_IMPORTED_MODULE_29__["default"],
  'components/AlertManager': _components_AlertManager__WEBPACK_IMPORTED_MODULE_30__["default"],
  'components/Switch': _components_Switch__WEBPACK_IMPORTED_MODULE_31__["default"],
  'components/Badge': _components_Badge__WEBPACK_IMPORTED_MODULE_32__["default"],
  'components/LoadingIndicator': _components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_33__["default"],
  'components/Placeholder': _components_Placeholder__WEBPACK_IMPORTED_MODULE_34__["default"],
  'components/Separator': _components_Separator__WEBPACK_IMPORTED_MODULE_35__["default"],
  'components/Dropdown': _components_Dropdown__WEBPACK_IMPORTED_MODULE_36__["default"],
  'components/SplitDropdown': _components_SplitDropdown__WEBPACK_IMPORTED_MODULE_37__["default"],
  'components/RequestErrorModal': _components_RequestErrorModal__WEBPACK_IMPORTED_MODULE_38__["default"],
  'components/FieldSet': _components_FieldSet__WEBPACK_IMPORTED_MODULE_39__["default"],
  'components/Select': _components_Select__WEBPACK_IMPORTED_MODULE_40__["default"],
  'components/Navigation': _components_Navigation__WEBPACK_IMPORTED_MODULE_41__["default"],
  'components/Alert': _components_Alert__WEBPACK_IMPORTED_MODULE_42__["default"],
  'components/LinkButton': _components_LinkButton__WEBPACK_IMPORTED_MODULE_43__["default"],
  'components/Checkbox': _components_Checkbox__WEBPACK_IMPORTED_MODULE_44__["default"],
  'components/SelectDropdown': _components_SelectDropdown__WEBPACK_IMPORTED_MODULE_45__["default"],
  'components/ModalManager': _components_ModalManager__WEBPACK_IMPORTED_MODULE_46__["default"],
  'components/Button': _components_Button__WEBPACK_IMPORTED_MODULE_47__["default"],
  'components/Modal': _components_Modal__WEBPACK_IMPORTED_MODULE_48__["default"],
  'components/GroupBadge': _components_GroupBadge__WEBPACK_IMPORTED_MODULE_49__["default"],
  'helpers/fullTime': _helpers_fullTime__WEBPACK_IMPORTED_MODULE_50__["default"],
  'helpers/avatar': _helpers_avatar__WEBPACK_IMPORTED_MODULE_51__["default"],
  'helpers/icon': _helpers_icon__WEBPACK_IMPORTED_MODULE_52__["default"],
  'helpers/humanTime': _helpers_humanTime__WEBPACK_IMPORTED_MODULE_53__["default"],
  // 'helpers/punctuateSeries': punctuateSeries,
  'helpers/highlight': _helpers_highlight__WEBPACK_IMPORTED_MODULE_54__["default"],
  'helpers/username': _helpers_username__WEBPACK_IMPORTED_MODULE_55__["default"],
  'helpers/userOnline': _helpers_userOnline__WEBPACK_IMPORTED_MODULE_56__["default"],
  'helpers/listItems': _helpers_listItems__WEBPACK_IMPORTED_MODULE_57__["default"]
});

/***/ }),

/***/ "./src/common/components/Alert.tsx":
/*!*****************************************!*\
  !*** ./src/common/components/Alert.tsx ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Alert; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _helpers_listItems__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/extract */ "./src/common/utils/extract.ts");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






/**
 * The `Alert` component represents an alert box, which contains a message,
 * some controls, and may be dismissible.
 *
 * The alert may have the following special props:
 *
 * - `type` The type of alert this is. Will be used to give the alert a class
 *   name of `Alert--{type}`.
 * - `controls` An array of controls to show in the alert.
 * - `dismissible` Whether or not the alert can be dismissed.
 * - `ondismiss` A callback to run when the alert is dismissed.
 *
 * All other props will be assigned as attributes on the alert element.
 */
var Alert = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(Alert, _Component);

  var _super = _createSuper(Alert);

  function Alert() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Alert.prototype;

  _proto.view = function view() {
    var attrs = Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, this.props);

    var type = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_7__["default"])(attrs, 'type');
    attrs.className = "Alert Alert--" + type + " " + (attrs.className || '');
    var children = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_7__["default"])(attrs, 'children');
    var controls = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_7__["default"])(attrs, 'controls') || []; // If the alert is meant to be dismissible (which is the case by default),
    // then we will create a dismiss button to append as the final control in
    // the alert.

    var dismissible = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_7__["default"])(attrs, 'dismissible');
    var ondismiss = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_7__["default"])(attrs, 'ondismiss');
    var dismissControl = [];

    if (dismissible || dismissible === undefined) {
      dismissControl.push(m(_Button__WEBPACK_IMPORTED_MODULE_5__["default"], {
        icon: "fas fa-times",
        className: "Button Button--link Button--icon Alert-dismiss",
        onclick: ondismiss
      }));
    }

    return m("div", attrs, m("span", {
      className: "Alert-body"
    }, children), m("ul", {
      className: "Alert-controls"
    }, Object(_helpers_listItems__WEBPACK_IMPORTED_MODULE_6__["default"])(controls.concat(dismissControl))));
  };

  return Alert;
}(_Component__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/components/AlertManager.tsx":
/*!************************************************!*\
  !*** ./src/common/components/AlertManager.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return AlertManager; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
var AlertManager = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(AlertManager, _Component);

  var _super = _createSuper(AlertManager);

  function AlertManager() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.components = [];
    return _this;
  }

  var _proto = AlertManager.prototype;

  _proto.view = function view() {
    return m("div", {
      className: "AlertManager"
    }, this.components.map(function (vnode) {
      return m("div", {
        className: "AlertManager-alert"
      }, vnode);
    }));
  }
  /**
   * Show an Alert in the alerts area.
   */
  ;

  _proto.show = function show(vnode) {
    vnode.attrs.ondismiss = this.dismiss.bind(this, vnode);
    this.components.push(vnode);
    m.redraw();
  }
  /**
   * Dismiss an alert.
   */
  ;

  _proto.dismiss = function dismiss(vnode) {
    var index = this.components.indexOf(vnode);

    if (index !== -1) {
      this.components.splice(index, 1);
      m.redraw();
    }
  }
  /**
   * Clear all alerts.
   */
  ;

  _proto.clear = function clear() {
    this.components = [];
    m.redraw();
  };

  return AlertManager;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Badge.tsx":
/*!*****************************************!*\
  !*** ./src/common/components/Badge.tsx ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Badge; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/extract */ "./src/common/utils/extract.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `Badge` component represents a user/discussion badge, indicating some
 * status (e.g. a discussion is stickied, a user is an admin).
 *
 * A badge may have the following special props:
 *
 * - `type` The type of badge this is. This will be used to give the badge a
 *   class name of `Badge--{type}`.
 * - `icon` The name of an icon to show inside the badge.
 * - `label`
 *
 * All other props will be assigned as attributes on the badge element.
 */

var Badge = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Badge, _Component);

  var _super = _createSuper(Badge);

  function Badge() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Badge.prototype;

  _proto.view = function view(vnode) {
    var attrs = vnode.attrs;
    var type = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_5__["default"])(attrs, 'type');
    var iconName = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_5__["default"])(attrs, 'icon');
    attrs.className = "Badge " + (type ? "Badge--" + type : '') + " " + (attrs.className || '');
    attrs.title = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_5__["default"])(attrs, 'label') || '';
    return m("span", attrs, iconName ? Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_4__["default"])(iconName, {
      className: 'Badge-icon'
    }) : m.trust('&nbsp;'));
  };

  _proto.oncreate = function oncreate(vnode) {
    _Component.prototype.oncreate.call(this, vnode);

    if (this.props.label) this.$().tooltip({
      container: 'body'
    });
  };

  return Badge;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Button.tsx":
/*!******************************************!*\
  !*** ./src/common/components/Button.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Button; });
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/objectWithoutPropertiesLoose */ "./node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/extract */ "./src/common/utils/extract.ts");
/* harmony import */ var _utils_extractText__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }







/**
 * The `Button` component defines an element which, when clicked, performs an
 * action. The button may have the following special props:
 *
 * - `icon` The name of the icon class. If specified, the button will be given a
 *   'has-icon' class name.
 * - `disabled` Whether or not the button is disabled. If truthy, the button
 *   will be given a 'disabled' class name, and any `onclick` handler will be
 *   removed.
 * - `loading` Whether or not the button should be in a disabled loading state.
 *
 * All other props will be assigned as attributes on the button element.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item.
 */
var Button = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(Button, _Component);

  var _super = _createSuper(Button);

  function Button() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Button.prototype;

  _proto.view = function view() {
    var _this$props = this.props,
        children = _this$props.children,
        attrs = Object(_babel_runtime_helpers_esm_objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(_this$props, ["children"]);

    attrs.className = attrs.className || '';
    attrs.type = attrs.type || 'button'; // If a tooltip was provided for buttons without additional content, we also
    // use this tooltip as text for screen readers

    if (attrs.title && !this.props.children) {
      attrs['aria-label'] = attrs.title;
    } // If nothing else is provided, we use the textual button content as tooltip


    if (!attrs.title && this.props.children) {
      attrs.title = Object(_utils_extractText__WEBPACK_IMPORTED_MODULE_7__["default"])(this.props.children);
    }

    var iconName = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_6__["default"])(attrs, 'icon');
    if (iconName) attrs.className += ' hasIcon';
    var loading = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_6__["default"])(attrs, 'loading');

    if (attrs.disabled || loading) {
      attrs.className = classNames(attrs.className, 'disabled', loading && 'loading');
      delete attrs.onclick;
    }

    return m("button", attrs, this.getButtonContent(iconName, loading, children));
  }
  /**
   * Get the template for the button's content.
   */
  ;

  _proto.getButtonContent = function getButtonContent(iconName, loading, children) {
    return [iconName && iconName !== true ? Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_5__["default"])(iconName, {
      className: 'Button-icon'
    }) : '', children ? m("span", {
      className: "Button-label"
    }, children) : '', loading ? _LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__["default"].component({
      size: 'tiny',
      className: 'LoadingIndicator--inline'
    }) : ''];
  };

  return Button;
}(_Component__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/components/Checkbox.tsx":
/*!********************************************!*\
  !*** ./src/common/components/Checkbox.tsx ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Checkbox; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





/**
 * The `Checkbox` component defines a checkbox input.
 */
var Checkbox = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Checkbox, _Component);

  var _super = _createSuper(Checkbox);

  function Checkbox() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.loading = false;
    return _this;
  }

  var _proto = Checkbox.prototype;

  _proto.view = function view() {
    var className = classNames('Checkbox', this.props.className, this.props.state ? 'on' : 'off', this.loading && 'loading', this.props.disabled && 'disabled');
    return m("label", {
      className: className
    }, m("input", {
      type: "checkbox",
      checked: this.props.state,
      disabled: this.props.disabled,
      onchange: m.withAttr('checked', this.onchange.bind(this))
    }), m("div", {
      className: "Checkbox-display"
    }, this.getDisplay()), this.props.children);
  }
  /**
   * Get the template for the checkbox's display (tick/cross icon).
   */
  ;

  _proto.getDisplay = function getDisplay() {
    return this.loading ? _LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      size: 'tiny'
    }) : Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_5__["default"])(this.props.state ? 'fas fa-check' : 'fas fa-times');
  }
  /**
   * Run a callback when the state of the checkbox is changed.
   */
  ;

  _proto.onchange = function onchange(checked) {
    if (this.props.onchange) this.props.onchange(checked, this);
  };

  return Checkbox;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Dropdown.tsx":
/*!********************************************!*\
  !*** ./src/common/components/Dropdown.tsx ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Dropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");
/* harmony import */ var _helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/listItems */ "./src/common/helpers/listItems.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * ### Props
 *
 * - `buttonClassName` A class name to apply to the dropdown toggle button.
 * - `menuClassName` A class name to apply to the dropdown menu.
 * - `icon` The name of an icon to show in the dropdown toggle button.
 * - `caretIcon` The name of an icon to show on the right of the button.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 * - `onhide`
 * - `onshow`
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
var Dropdown = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Dropdown, _Component);

  var _super = _createSuper(Dropdown);

  function Dropdown() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.showing = false;
    return _this;
  }

  Dropdown.initProps = function initProps(props) {
    props.className = props.className || '';
    props.buttonClassName = props.buttonClassName || '';
    props.menuClassName = props.menuClassName || '';
    props.label = props.label || '';
    props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'fas fa-caret-down';
  };

  var _proto = Dropdown.prototype;

  _proto.view = function view() {
    var items = this.props.children ? Object(_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__["default"])(this.props.children) : [];
    return m("div", {
      className: "ButtonGroup Dropdown dropdown " + this.props.className + " itemCount" + items.length + (this.showing ? ' open' : '')
    }, this.getButton(), this.getMenu(items));
  };

  _proto.oncreate = function oncreate(vnode) {
    var _this2 = this;

    _Component.prototype.oncreate.call(this, vnode);

    this.$('> .Dropdown-toggle').dropdown(); // When opening the dropdown menu, work out if the menu goes beyond the
    // bottom of the viewport. If it does, we will apply class to make it show
    // above the toggle button instead of below it.

    this.element.addEventListener('shown.bs.dropdown', function () {
      _this2.showing = true;

      if (_this2.props.onshow) {
        _this2.props.onshow();
      }

      m.redraw();

      var $menu = _this2.$('.Dropdown-menu');

      var isRight = $menu.hasClass('Dropdown-menu--right');
      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');
      $menu.toggleClass('Dropdown-menu--top', $menu.offset().top + $menu.height() > $(window).scrollTop() + $(window).height());

      if ($menu.offset().top < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      $menu.toggleClass('Dropdown-menu--right', isRight || $menu.offset().left + $menu.width() > $(window).scrollLeft() + $(window).width());
    });
    this.element.addEventListener('hidden.bs.dropdown', function () {
      _this2.showing = false;

      if (_this2.props.onhide) {
        _this2.props.onhide();
      }

      m.redraw();
    });
  }
  /**
   * Get the template for the button.
   */
  ;

  _proto.getButton = function getButton() {
    return m("button", {
      className: 'Dropdown-toggle ' + this.props.buttonClassName,
      "data-toggle": "dropdown",
      onclick: this.props.onclick
    }, this.getButtonContent());
  }
  /**
   * Get the template for the button's content.
   *
   * @return {*}
   */
  ;

  _proto.getButtonContent = function getButtonContent() {
    var attrs = this.props;
    return [attrs.icon ? Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_4__["default"])(attrs.icon, {
      className: 'Button-icon'
    }) : '', m("span", {
      className: "Button-label"
    }, attrs.label), attrs.caretIcon ? Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_4__["default"])(attrs.caretIcon, {
      className: 'Button-caret'
    }) : ''];
  };

  _proto.getMenu = function getMenu(items) {
    return m("ul", {
      className: 'Dropdown-menu dropdown-menu ' + this.props.menuClassName
    }, items);
  };

  return Dropdown;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/FieldSet.tsx":
/*!********************************************!*\
  !*** ./src/common/components/FieldSet.tsx ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return FieldSet; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _helpers_listItems__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/listItems */ "./src/common/helpers/listItems.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `FieldSet` component defines a collection of fields, displayed in a list
 * underneath a title.
 *
 * The children should be an array of items to show in the fieldset.
 */
var FieldSet = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(FieldSet, _Component);

  var _super = _createSuper(FieldSet);

  function FieldSet() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = FieldSet.prototype;

  _proto.view = function view() {
    return m("fieldset", {
      className: this.props.className
    }, m("legend", null, this.props.label), m("ul", null, Object(_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__["default"])(this.props.children)));
  };

  return FieldSet;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/GroupBadge.ts":
/*!*********************************************!*\
  !*** ./src/common/components/GroupBadge.ts ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return GroupBadge; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Badge__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Badge */ "./src/common/components/Badge.tsx");
/* harmony import */ var _utils_extract__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/extract */ "./src/common/utils/extract.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




var GroupBadge = /*#__PURE__*/function (_Badge) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(GroupBadge, _Badge);

  var _super = _createSuper(GroupBadge);

  function GroupBadge() {
    return _Badge.apply(this, arguments) || this;
  }

  GroupBadge.initProps = function initProps(props) {
    _Badge.initProps.call(this, props);

    var group = Object(_utils_extract__WEBPACK_IMPORTED_MODULE_4__["default"])(props, 'group');

    if (group) {
      props.icon = group.icon();
      props.style = {
        backgroundColor: group.color()
      };
      props.label = typeof props.label === 'undefined' ? group.nameSingular() : props.label;
      props.type = "group--" + group.id();
    }
  };

  return GroupBadge;
}(_Badge__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/LinkButton.tsx":
/*!**********************************************!*\
  !*** ./src/common/components/LinkButton.tsx ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return LinkButton; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Props
 *
 * All of the props accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 */
var LinkButton = /*#__PURE__*/function (_Button) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(LinkButton, _Button);

  var _super = _createSuper(LinkButton);

  function LinkButton() {
    return _Button.apply(this, arguments) || this;
  }

  LinkButton.initProps = function initProps(props) {
    props.active = this.isActive(props);
  };

  var _proto = LinkButton.prototype;

  _proto.view = function view() {
    var vdom = _Button.prototype.view.call(this);

    vdom.tag = m.route.Link;
    vdom.attrs.active = String(vdom.attrs.active);
    return vdom;
  }
  /**
   * Determine whether a component with the given props is 'active'.
   */
  ;

  LinkButton.isActive = function isActive(props) {
    return typeof props.active !== 'undefined' ? props.active : m.route.get() === props.href;
  };

  return LinkButton;
}(_Button__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/LoadingIndicator.tsx":
/*!****************************************************!*\
  !*** ./src/common/components/LoadingIndicator.tsx ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return LoadingIndicator; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var spin_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! spin.js */ "./node_modules/spin.js/spin.js");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



/**
 * The `LoadingIndicator` component displays a loading spinner with spin.js. It
 * may have the following special props:
 *
 * - `size` The spin.js size preset to use. Defaults to 'small'.
 *
 * All other props will be assigned as attributes on the element.
 */

var LoadingIndicator = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(LoadingIndicator, _Component);

  var _super = _createSuper(LoadingIndicator);

  function LoadingIndicator() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = LoadingIndicator.prototype;

  _proto.view = function view(vnode) {
    var attrs = vnode.attrs;
    attrs.className = 'LoadingIndicator ' + (attrs.className || '');
    delete attrs.size;
    return m("div", attrs, m.trust('&nbsp;'));
  };

  _proto.oncreate = function oncreate(vnode) {
    _Component.prototype.oncreate.call(this, vnode);

    var options = {
      zIndex: 'auto',
      color: this.$().css('color')
    };
    var sizeOptions = {};

    switch (vnode.attrs.size) {
      case 'large':
        sizeOptions = {
          lines: 10,
          length: 8,
          width: 4,
          radius: 8
        };
        break;

      case 'tiny':
        sizeOptions = {
          lines: 8,
          length: 2,
          width: 2,
          radius: 3
        };
        break;

      default:
        sizeOptions = {
          lines: 8,
          length: 4,
          width: 3,
          radius: 5
        };
    }

    new spin_js__WEBPACK_IMPORTED_MODULE_5__["Spinner"](Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, options, {}, sizeOptions)).spin(this.element);
  };

  return LoadingIndicator;
}(_Component__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/components/Modal.tsx":
/*!*****************************************!*\
  !*** ./src/common/components/Modal.tsx ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Modal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 */
var Modal = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Modal, _Component);

  var _super = _createSuper(Modal);

  function Modal() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.alert = void 0;
    _this.loading = void 0;
    return _this;
  }

  var _proto = Modal.prototype;

  _proto.view = function view() {
    if (this.alert) {
      this.alert.attrs.dismissible = false;
    }

    return m("div", {
      className: "Modal modal-dialog " + this.className()
    }, m("div", {
      className: "Modal-content"
    }, this.isDismissible() ? m("div", {
      className: "Modal-close App-backControl"
    }, _Button__WEBPACK_IMPORTED_MODULE_4__["default"].component({
      icon: 'fas fa-times',
      onclick: this.hide.bind(this),
      className: 'Button Button--icon Button--link'
    })) : '', m("form", {
      onsubmit: this.onsubmit.bind(this)
    }, m("div", {
      className: "Modal-header"
    }, m("h3", {
      className: "App-titleControl App-titleControl--text"
    }, this.title())), this.alert && m("div", {
      className: "Modal-alert"
    }, this.alert), this.content())));
  }
  /**
   * Determine whether or not the modal should be dismissible via an 'x' button.
   */
  ;

  _proto.isDismissible = function isDismissible() {
    return true;
  }
  /**
   * Get the class name to apply to the modal.
   */
  ;

  /**
   * Handle the modal form's submit event.
   */
  _proto.onsubmit = function onsubmit(e) {}
  /**
   * Focus on the first input when the modal is ready to be used.
   */
  ;

  _proto.onready = function onready() {
    this.$('form').find('input, select, textarea').first().focus().select();
  };

  _proto.onhide = function onhide() {}
  /**
   * Hide the modal.
   */
  ;

  _proto.hide = function hide() {
    app.modal.close();
  }
  /**
   * Stop loading.
   */
  ;

  _proto.loaded = function loaded() {
    this.loading = false;
    m.redraw();
  }
  /**
   * Show an alert describing an error returned from the API, and give focus to
   * the first relevant field.
   */
  ;

  _proto.onerror = function onerror(error) {
    this.alert = error.alert;
    m.redraw();

    if (error.status === 422 && error.response.errors) {
      this.$("form [name=\"" + error.response.errors[0].source.pointer.replace('/data/attributes/', '') + "\"]").select();
    } else {
      this.onready();
    }
  };

  return Modal;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/ModalManager.tsx":
/*!************************************************!*\
  !*** ./src/common/components/ModalManager.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ModalManager; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var micromodal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! micromodal */ "./node_modules/micromodal/dist/micromodal.es.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../extend */ "./src/common/extend.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
var ModalManager = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(ModalManager, _Component);

  var _super = _createSuper(ModalManager);

  function ModalManager() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.showing = void 0;
    _this.hideTimeout = void 0;
    _this.modal = null;
    _this.modalProps = {};
    _this.component = null;
    return _this;
  }

  var _proto = ModalManager.prototype;

  _proto.oncreate = function oncreate(vnode) {
    _Component.prototype.oncreate.call(this, vnode);

    app.modal = this;
  };

  _proto.view = function view() {
    return m("div", {
      className: "ModalManager modal",
      id: "Modal",
      onclick: this.onclick.bind(this),
      key: "modal"
    }, this.modal && m(this.modal, this.modalProps));
  }
  /**
   * Show a modal dialog.
   */
  ;

  _proto.show = function show(component, props) {
    var _this2 = this;

    if (props === void 0) {
      props = {};
    }

    clearTimeout(this.hideTimeout);
    this.showing = true;
    this.modal = component;
    this.modalProps = props;
    this.component = null; // Store the vnode state in app.modal.component

    Object(_extend__WEBPACK_IMPORTED_MODULE_5__["extend"])(this.modalProps, 'oninit', function (v, vnode) {
      return _this2.component = vnode.state;
    }); // if (app.current) app.current.retain = true;

    m.redraw();

    if (!$('.modal-backdrop').length) {
      $('<div />').addClass('modal-backdrop').appendTo('body');
    }

    micromodal__WEBPACK_IMPORTED_MODULE_3__["default"].show('Modal', {
      awaitCloseAnimation: true,
      awaitOpenAnimation: true,
      disableFocus: true,
      onShow: function onShow() {
        return $('body').addClass('modal-open');
      },
      onClose: function onClose() {
        $('body').removeClass('modal-open');
        var backdrop = $('.modal-backdrop');
        backdrop.fadeOut(200, function () {
          backdrop.remove();

          _this2.clear();
        });
        _this2.showing = false;
      }
    });
    this.$().one('animationend', function () {
      return _this2.onready();
    });
  };

  _proto.onclick = function onclick(e) {
    if (e.target === this.element) {
      this.close();
    }
  }
  /**
   * Close the modal dialog.
   */
  ;

  _proto.close = function close() {
    if (!this.showing) return; // Don't hide the modal immediately, because if the consumer happens to call
    // the `show` method straight after to show another modal dialog, it will
    // cause the new modal dialog to disappear. Instead we will wait for a tiny
    // bit to give the `show` method the opportunity to prevent this from going
    // ahead.

    this.hideTimeout = setTimeout(function () {
      return micromodal__WEBPACK_IMPORTED_MODULE_3__["default"].close('Modal');
    });
  }
  /**
   * Clear content from the modal area.
   */
  ;

  _proto.clear = function clear() {
    if (this.component) {
      this.component.onhide();
    }

    this.modal = null;
    this.component = null;
    this.modalProps = {}; // app.current.retain = false;

    m.redraw();
  }
  /**
   * When the modal dialog is ready to be used, tell it!
   */
  ;

  _proto.onready = function onready() {
    if (this.component) {
      this.component.onready();
    }
  };

  return ModalManager;
}(_Component__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/components/Navigation.tsx":
/*!**********************************************!*\
  !*** ./src/common/components/Navigation.tsx ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Navigation; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _LinkButton__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./LinkButton */ "./src/common/components/LinkButton.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





/**
 * The `Navigation` component displays a set of navigation buttons. Typically
 * this is just a back button which pops the app's History. If the user is on
 * the root page and there is no history to pop, then in some instances it may
 * show a button that toggles the app's drawer.
 *
 * If the app has a pane, it will also include a 'pin' button which toggles the
 * pinned state of the pane.
 */
var Navigation = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Navigation, _Component);

  var _super = _createSuper(Navigation);

  function Navigation(options) {
    var _this;

    if (options === void 0) {
      options = {};
    }

    _this = _Component.call(this) || this;
    _this.className = void 0;
    _this.drawer = void 0;
    _this.className = options.className;
    _this.drawer = !!options.drawer;
    return _this;
  }

  var _proto = Navigation.prototype;

  _proto.view = function view() {
    var _app = app,
        history = _app.history,
        pane = _app.pane;
    return m("div", {
      className: 'Navigation ButtonGroup ' + (this.className || ''),
      onmouseenter: pane && pane.show.bind(pane),
      onmouseleave: pane && pane.onmouseleave.bind(pane)
    }, history.canGoBack() ? [this.getBackButton(), this.getPaneButton()] : this.getDrawerButton());
  }
  /**
   * Get the back button.
   */
  ;

  _proto.getBackButton = function getBackButton() {
    var _app2 = app,
        history = _app2.history;
    var previous = history.getPrevious() || {};
    return m(_LinkButton__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "Button Navigation-back Button--icon",
      href: history.backUrl(),
      icon: "fas fa-chevron-left",
      title: previous.title,
      onclick: function onclick(e) {
        if (e.shiftKey || e.ctrlKey || e.metaKey || e.which === 2) return;
        e.preventDefault();
        history.back();
      }
    });
  }
  /**
   * Get the pane pinned toggle button.
   */
  ;

  _proto.getPaneButton = function getPaneButton() {
    var _app3 = app,
        pane = _app3.pane;
    if (!pane || !pane.active) return '';
    return m(_Button__WEBPACK_IMPORTED_MODULE_4__["default"], {
      className: 'Button Button--icon Navigation-pin' + (pane.pinned ? ' active' : ''),
      onclick: pane.togglePinned.bind(pane),
      icon: "fas fa-thumbtack"
    });
  }
  /**
   * Get the drawer toggle button.
   */
  ;

  _proto.getDrawerButton = function getDrawerButton() {
    if (!this.drawer) return '';
    var _app4 = app,
        drawer = _app4.drawer;
    var user = app.session.user;
    return m(_Button__WEBPACK_IMPORTED_MODULE_4__["default"], {
      className: 'Button Button--icon Navigation-drawer' + (user && user.newNotificationCount() ? ' new' : ''),
      onclick: function onclick(e) {
        e.stopPropagation();
        drawer.show();
      },
      icon: "fas fa-bars"
    });
  };

  return Navigation;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Placeholder.tsx":
/*!***********************************************!*\
  !*** ./src/common/components/Placeholder.tsx ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Placeholder; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



/**
 * The `Placeholder` component displays a muted text with some call to action,
 * usually used as an empty state.
 */
var Placeholder = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Placeholder, _Component);

  var _super = _createSuper(Placeholder);

  function Placeholder() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Placeholder.prototype;

  _proto.view = function view() {
    return m("div", {
      className: "Placeholder"
    }, m("p", null, this.props.text));
  };

  return Placeholder;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/RequestErrorModal.tsx":
/*!*****************************************************!*\
  !*** ./src/common/components/RequestErrorModal.tsx ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return RequestErrorModal; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Modal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Modal */ "./src/common/components/Modal.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var RequestErrorModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(RequestErrorModal, _Modal);

  var _super = _createSuper(RequestErrorModal);

  function RequestErrorModal() {
    return _Modal.apply(this, arguments) || this;
  }

  var _proto = RequestErrorModal.prototype;

  _proto.className = function className() {
    return 'RequestErrorModal Modal--large';
  };

  _proto.title = function title() {
    return this.props.error.xhr ? this.props.error.xhr.status + " " + this.props.error.xhr.statusText : '';
  };

  _proto.content = function content() {
    var responseText;

    try {
      responseText = JSON.stringify(JSON.parse(this.props.error.responseText), null, 2);
    } catch (e) {
      responseText = this.props.error.responseText;
    }

    return m("div", {
      className: "Modal-body"
    }, m("pre", null, this.props.error.options.method, " ", this.props.error.options.url, m("br", null), m("br", null), responseText));
  };

  return RequestErrorModal;
}(_Modal__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Select.tsx":
/*!******************************************!*\
  !*** ./src/common/components/Select.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Select; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling.
 */
var Select = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Select, _Component);

  var _super = _createSuper(Select);

  function Select() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Select.prototype;

  _proto.view = function view() {
    var _this = this;

    return m("span", {
      className: "Select"
    }, m("select", {
      className: "Select-input FormControl",
      disabled: this.props.disabled,
      onchange: m.withAttr('value', this.onchange.bind(this)),
      value: this.props.value
    }, Object.keys(this.props.options).map(function (key) {
      return m("option", {
        value: key
      }, _this.props.options[key]);
    })), Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_4__["default"])('fas fa-sort', {
      className: 'Select-caret'
    }));
  }
  /**
   * Run a callback when the state of the checkbox is changed.
   */
  ;

  _proto.onchange = function onchange(value) {
    if (this.props.onchange) this.props.onchange(value);
  };

  return Select;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/SelectDropdown.tsx":
/*!**************************************************!*\
  !*** ./src/common/components/SelectDropdown.tsx ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SelectDropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Dropdown__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 *
 * ### Props
 *
 * - `caretIcon`
 * - `defaultLabel`
 */
var SelectDropdown = /*#__PURE__*/function (_Dropdown) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(SelectDropdown, _Dropdown);

  var _super = _createSuper(SelectDropdown);

  function SelectDropdown() {
    return _Dropdown.apply(this, arguments) || this;
  }

  SelectDropdown.initProps = function initProps(props) {
    props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'fas fa-sort';

    _Dropdown.initProps.call(this, props);

    props.className += ' Dropdown--select';
  };

  var _proto = SelectDropdown.prototype;

  _proto.getButtonContent = function getButtonContent() {
    var activeChild = this.props.children.filter(function (child) {
      return child.attrs.active;
    })[0];
    var label = activeChild && activeChild.attrs.children || this.props.defaultLabel;
    if (label instanceof Array) label = label[0];
    return [m("span", {
      className: "Button-label"
    }, label), Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_4__["default"])(this.props.caretIcon, {
      className: 'Button-caret'
    })];
  };

  return SelectDropdown;
}(_Dropdown__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/components/Separator.tsx":
/*!*********************************************!*\
  !*** ./src/common/components/Separator.tsx ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Separator; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }


/**
 * The `Separator` component defines a menu separator item.
 */

var Separator = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Separator, _Component);

  var _super = _createSuper(Separator);

  function Separator() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = Separator.prototype;

  _proto.view = function view() {
    return m("li", {
      className: "Dropdown-separator"
    });
  };

  return Separator;
}(_Component__WEBPACK_IMPORTED_MODULE_3__["default"]);

Separator.isListItem = true;


/***/ }),

/***/ "./src/common/components/SplitDropdown.tsx":
/*!*************************************************!*\
  !*** ./src/common/components/SplitDropdown.tsx ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SplitDropdown; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Dropdown__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Dropdown */ "./src/common/components/Dropdown.tsx");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _helpers_icon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/icon */ "./src/common/helpers/icon.tsx");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }




/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */

var SplitDropdown = /*#__PURE__*/function (_Dropdown) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(SplitDropdown, _Dropdown);

  var _super = _createSuper(SplitDropdown);

  function SplitDropdown() {
    return _Dropdown.apply(this, arguments) || this;
  }

  SplitDropdown.initProps = function initProps(props) {
    _Dropdown.initProps.call(this, props);

    props.className += ' Dropdown--split';
    props.menuClassName += ' Dropdown-menu--right';
  };

  var _proto = SplitDropdown.prototype;

  _proto.getButton = function getButton() {
    // Make a copy of the props of the first child component. We will assign
    // these props to a new button, so that it has exactly the same behaviour as
    // the first child.
    var firstChild = this.getFirstChild();

    var buttonProps = Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, firstChild.attrs);

    buttonProps.className = classNames(buttonProps.className, 'SplitDropdown-button', 'Button', this.props.buttonClassName);
    return [_Button__WEBPACK_IMPORTED_MODULE_5__["default"].component(buttonProps), m("button", {
      className: 'Dropdown-toggle Button Button--icon ' + this.props.buttonClassName,
      "data-toggle": "dropdown"
    }, Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_6__["default"])(this.props.icon, {
      className: 'Button-icon'
    }), Object(_helpers_icon__WEBPACK_IMPORTED_MODULE_6__["default"])('fas fa-caret-down', {
      className: 'Button-caret'
    }))];
  }
  /**
   * Get the first child. If the first child is an array, the first item in that
   * array will be returned.
   */
  ;

  _proto.getFirstChild = function getFirstChild() {
    var firstChild = this.props.children;

    while (firstChild instanceof Array) {
      firstChild = firstChild[0];
    }

    return firstChild;
  };

  return SplitDropdown;
}(_Dropdown__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/components/Switch.tsx":
/*!******************************************!*\
  !*** ./src/common/components/Switch.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Switch; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Checkbox__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Checkbox */ "./src/common/components/Checkbox.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }


/**
 * The `Switch` component is a `Checkbox`, but with a switch display instead of
 * a tick/cross one.
 */

var Switch = /*#__PURE__*/function (_Checkbox) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Switch, _Checkbox);

  var _super = _createSuper(Switch);

  function Switch() {
    return _Checkbox.apply(this, arguments) || this;
  }

  Switch.initProps = function initProps(props) {
    _Checkbox.initProps.call(this, props);

    props.className = (props.className || '') + " Checkbox--switch";
  };

  var _proto = Switch.prototype;

  _proto.getDisplay = function getDisplay() {
    return this.loading ? _Checkbox.prototype.getDisplay.call(this) : '';
  };

  return Switch;
}(_Checkbox__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/extend.ts":
/*!******************************!*\
  !*** ./src/common/extend.ts ***!
  \******************************/
/*! exports provided: extend, override */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "extend", function() { return extend; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "override", function() { return override; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


/**
 * Extend an object's method by running its output through a mutating callback
 * every time it is called.
 *
 * The callback accepts the method's return value and should perform any
 * mutations directly on this value. For this reason, this function will not be
 * effective on methods which return scalar values (numbers, strings, booleans).
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example
 * extend(Discussion.prototype, 'badges', function(badges) {
 *   // do something with `badges`
 * });
 *
 * @param {Object} object The object that owns the method
 * @param {String} method The name of the method to extend
 * @param {function} callback A callback which mutates the method's output
 */
function extend(object, method, callback) {
  var original = object[method];

  object[method] = function () {
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    // @ts-ignore
    var value = original ? original.apply(object, args) : undefined; // @ts-ignore

    callback.apply(object, [value].concat(args));
    return value;
  };

  Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(object[method], original);
}
/**
 * Override an object's method by replacing it with a new function, so that the
 * new function will be run every time the object's method is called.
 *
 * The replacement function accepts the original method as its first argument,
 * which is like a call to 'super'. Any arguments passed to the original method
 * are also passed to the replacement.
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example
 * override(Discussion.prototype, 'badges', function(original) {
 *   const badges = original();
 *   // do something with badges
 *   return badges;
 * });
 *
 * @param {Object} object The object that owns the method
 * @param {String} method The name of the method to override
 * @param {function} newMethod The method to replace it with
 */

function override(object, method, newMethod) {
  var original = object[method];

  object[method] = function () {
    for (var _len2 = arguments.length, args = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
      args[_key2] = arguments[_key2];
    }

    // @ts-ignore
    return newMethod.apply(this, [original.bind(this)].concat(args));
  };

  Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(object[method], original);
}

/***/ }),

/***/ "./src/common/helpers/avatar.tsx":
/*!***************************************!*\
  !*** ./src/common/helpers/avatar.tsx ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return avatar; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");


/**
 * The `avatar` helper displays a user's avatar.
 *
 * @param {User} user
 * @param {Object} attrs Attributes to apply to the avatar element
 * @return {Object}
 */
function avatar(user, attrs) {
  if (attrs === void 0) {
    attrs = {};
  }

  attrs.className = 'Avatar ' + (attrs.className || '');
  var content = ''; // If the `title` attribute is set to null or false, we don't want to give the
  // avatar a title. On the other hand, if it hasn't been given at all, we can
  // safely default it to the user's username.

  var hasTitle = attrs.title === 'undefined' || attrs.title;
  if (!hasTitle) delete attrs.title; // If a user has been passed, then we will set up an avatar using their
  // uploaded image, or the first letter of their username if they haven't
  // uploaded one.

  if (user) {
    var username = user.displayName() || '?';
    var avatarUrl = user.avatarUrl();
    if (hasTitle) attrs.title = attrs.title || username;

    if (avatarUrl) {
      return m("img", Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])({}, attrs, {
        src: avatarUrl
      }));
    }

    content = username.charAt(0).toUpperCase();
    attrs.style = {
      background: user.color()
    };
  }

  return m("span", attrs, content);
}

/***/ }),

/***/ "./src/common/helpers/fullTime.tsx":
/*!*****************************************!*\
  !*** ./src/common/helpers/fullTime.tsx ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return fullTime; });
/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 */
function fullTime(time) {
  var mo = dayjs(time);
  var datetime = mo.format();
  var full = mo.format('LLLL');
  return m("time", {
    pubdate: true,
    datetime: datetime
  }, full);
}

/***/ }),

/***/ "./src/common/helpers/highlight.ts":
/*!*****************************************!*\
  !*** ./src/common/helpers/highlight.ts ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return highlight; });
/* harmony import */ var _utils_string__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/string */ "./src/common/utils/string.ts");

/**
 * The `highlight` helper searches for a word phrase in a string, and wraps
 * matches with the <mark> tag.
 *
 * @param {String} string The string to highlight.
 * @param {String|RegExp} phrase The word or words to highlight.
 * @param {Integer} [length] The number of characters to truncate the string to.
 *     The string will be truncated surrounding the first match.
 */

function highlight(string, phrase, length) {
  if (!phrase && !length) return string; // Convert the word phrase into a global regular expression (if it isn't
  // already) so we can search the string for matched.

  var regexp = phrase instanceof RegExp ? phrase : new RegExp(phrase, 'gi');
  var highlighted = string;
  var start = 0; // If a length was given, the truncate the string surrounding the first match.

  if (length) {
    if (phrase) start = Math.max(0, string.search(regexp) - length / 2);
    highlighted = Object(_utils_string__WEBPACK_IMPORTED_MODULE_0__["truncate"])(highlighted, length, start);
  } // Convert the string into HTML entities, then highlight all matches with
  // <mark> tags. Then we will return the result as a trusted HTML string.


  highlighted = $('<div/>').text(highlighted).html();
  if (phrase) highlighted = highlighted.replace(regexp, '<mark>$&</mark>');
  return m.trust(highlighted);
}

/***/ }),

/***/ "./src/common/helpers/humanTime.tsx":
/*!******************************************!*\
  !*** ./src/common/helpers/humanTime.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return humanTime; });
/* harmony import */ var _utils_humanTime__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/humanTime */ "./src/common/utils/humanTime.ts");

/**
 * The `humanTime` helper displays a time in a human-friendly time-ago format
 * (e.g. '12 days ago'), wrapped in a <time> tag with other information about
 * the time.
 */

function humanTime(time) {
  var mo = dayjs(time);
  var datetime = mo.format();
  var full = mo.format('LLLL');
  var ago = Object(_utils_humanTime__WEBPACK_IMPORTED_MODULE_0__["default"])(time);
  return m("time", {
    pubdate: true,
    datetime: datetime,
    title: full,
    "data-humantime": true
  }, ago);
}

/***/ }),

/***/ "./src/common/helpers/icon.tsx":
/*!*************************************!*\
  !*** ./src/common/helpers/icon.tsx ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return icon; });
/**
 * The `icon` helper displays an icon.
 *
 * @param {String} fontClass The full icon class, prefix and the icon’s name.
 * @param {Object} attrs Any other attributes to apply.
 */
function icon(fontClass, attrs) {
  if (attrs === void 0) {
    attrs = {};
  }

  attrs.className = 'icon ' + fontClass + ' ' + (attrs.className || '');
  return m("i", attrs);
}

/***/ }),

/***/ "./src/common/helpers/listItems.tsx":
/*!******************************************!*\
  !*** ./src/common/helpers/listItems.tsx ***!
  \******************************************/
/*! exports provided: isSeparator, withoutUnnecessarySeparators, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isSeparator", function() { return isSeparator; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withoutUnnecessarySeparators", function() { return withoutUnnecessarySeparators; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return listItems; });
/* harmony import */ var _components_Separator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../components/Separator */ "./src/common/components/Separator.tsx");

function isSeparator(item) {
  return (item === null || item === void 0 ? void 0 : item.tag) === _components_Separator__WEBPACK_IMPORTED_MODULE_0__["default"];
}
function withoutUnnecessarySeparators(items) {
  var newItems = [];
  var prevItem;
  items.forEach(function (item, i) {
    if (!isSeparator(item) || prevItem && !isSeparator(prevItem) && i !== items.length - 1) {
      prevItem = item;
      newItems.push(item);
    }
  });
  return newItems;
}
/**
 * The `listItems` helper wraps a collection of components in <li> tags,
 * stripping out any unnecessary `Separator` components.
 *
 * @param {*} items
 * @return {Array}
 */

function listItems(items) {
  if (!(items instanceof Array)) items = [items];
  return withoutUnnecessarySeparators(items).map(function (item) {
    var _item$tag, _item$tag2, _item$attrs, _item$attrs2;

    var isListItem = (_item$tag = item.tag) === null || _item$tag === void 0 ? void 0 : _item$tag.isListItem;
    var active = ((_item$tag2 = item.tag) === null || _item$tag2 === void 0 ? void 0 : _item$tag2.isActive) && item.tag.isActive(item.attrs);
    var className = ((_item$attrs = item.attrs) === null || _item$attrs === void 0 ? void 0 : _item$attrs.itemClassName) || item.itemClassName;

    if (isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
      item.key = item.attrs.key;
    }

    var node = isListItem ? item : m("li", {
      className: classNames(className, item.itemName && "item-" + item.itemName, active && 'active'),
      key: ((_item$attrs2 = item.attrs) === null || _item$attrs2 === void 0 ? void 0 : _item$attrs2.key) || item.itemName
    }, item);
    node.state = node.state || {};
    return node;
  });
}

/***/ }),

/***/ "./src/common/helpers/userOnline.tsx":
/*!*******************************************!*\
  !*** ./src/common/helpers/userOnline.tsx ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return userOnline; });
/* harmony import */ var _icon__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./icon */ "./src/common/helpers/icon.tsx");


/**
 * The `useronline` helper displays a green circle if the user is online
 *
 * @param {User} user
 * @return {Object}
 */
function userOnline(user) {
  if (user.lastSeenAt() && user.isOnline()) {
    return m("span", {
      className: "UserOnline"
    }, Object(_icon__WEBPACK_IMPORTED_MODULE_0__["default"])('fas fa-circle'));
  }
}

/***/ }),

/***/ "./src/common/helpers/username.tsx":
/*!*****************************************!*\
  !*** ./src/common/helpers/username.tsx ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return username; });
/**
 * The `username` helper displays a user's username in a <span class="username">
 * tag. If the user doesn't exist, the username will be displayed as [deleted].
 *
 * @param {User} user
 */
function username(user) {
  var name = user && user.displayName() || app.translator.trans('core.lib.username.deleted_text');
  return m("span", {
    className: "username"
  }, name);
}

/***/ }),

/***/ "./src/common/index.ts":
/*!*****************************!*\
  !*** ./src/common/index.ts ***!
  \*****************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var expose_loader_jQuery_zepto__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! expose-loader?jQuery!zepto */ "./node_modules/expose-loader/index.js?jQuery!./node_modules/zepto/dist/zepto.js-exposed");
/* harmony import */ var expose_loader_jQuery_zepto__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(expose_loader_jQuery_zepto__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var expose_loader_moment_expose_loader_dayjs_dayjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! expose-loader?moment!expose-loader?dayjs!dayjs */ "./node_modules/expose-loader/index.js?moment!./node_modules/expose-loader/index.js?dayjs!./node_modules/dayjs/dayjs.min.js-exposed");
/* harmony import */ var expose_loader_moment_expose_loader_dayjs_dayjs__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(expose_loader_moment_expose_loader_dayjs_dayjs__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var expose_loader_m_mithril__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! expose-loader?m!mithril */ "./node_modules/expose-loader/index.js?m!./node_modules/mithril/index.js-exposed");
/* harmony import */ var expose_loader_m_mithril__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(expose_loader_m_mithril__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var expose_loader_m_bidi_m_attrs_bidi__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! expose-loader?m.bidi!m.attrs.bidi */ "./node_modules/expose-loader/index.js?m.bidi!./node_modules/m.attrs.bidi/bidi.js-exposed");
/* harmony import */ var expose_loader_m_bidi_m_attrs_bidi__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(expose_loader_m_bidi_m_attrs_bidi__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var expose_loader_Mousetrap_mousetrap__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! expose-loader?Mousetrap!mousetrap */ "./node_modules/expose-loader/index.js?Mousetrap!./node_modules/mousetrap/mousetrap.js-exposed");
/* harmony import */ var expose_loader_Mousetrap_mousetrap__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(expose_loader_Mousetrap_mousetrap__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var expose_loader_classNames_classnames__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! expose-loader?classNames!classnames */ "./node_modules/expose-loader/index.js?classNames!./node_modules/classnames/index.js-exposed");
/* harmony import */ var expose_loader_classNames_classnames__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(expose_loader_classNames_classnames__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var zepto_src_selector__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! zepto/src/selector */ "./node_modules/zepto/src/selector.js");
/* harmony import */ var zepto_src_selector__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(zepto_src_selector__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var zepto_src_data__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! zepto/src/data */ "./node_modules/zepto/src/data.js");
/* harmony import */ var zepto_src_data__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(zepto_src_data__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var zepto_src_fx__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! zepto/src/fx */ "./node_modules/zepto/src/fx.js");
/* harmony import */ var zepto_src_fx__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(zepto_src_fx__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! zepto/src/fx_methods */ "./node_modules/zepto/src/fx_methods.js");
/* harmony import */ var zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _utils_patchZepto__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./utils/patchZepto */ "./src/common/utils/patchZepto.ts");
/* harmony import */ var hc_sticky__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! hc-sticky */ "./node_modules/hc-sticky/dist/hc-sticky.js");
/* harmony import */ var hc_sticky__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(hc_sticky__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var bootstrap_js_dropdown__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! bootstrap/js/dropdown */ "./node_modules/bootstrap/js/dropdown.js");
/* harmony import */ var bootstrap_js_dropdown__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dropdown__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var bootstrap_js_transition__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! bootstrap/js/transition */ "./node_modules/bootstrap/js/transition.js");
/* harmony import */ var bootstrap_js_transition__WEBPACK_IMPORTED_MODULE_13___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_transition__WEBPACK_IMPORTED_MODULE_13__);
/* harmony import */ var dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! dayjs/plugin/relativeTime */ "./node_modules/dayjs/plugin/relativeTime.js");
/* harmony import */ var dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_14__);
/* harmony import */ var dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! dayjs/plugin/localizedFormat */ "./node_modules/dayjs/plugin/localizedFormat.js");
/* harmony import */ var dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_15___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_15__);
/* harmony import */ var _utils_patchMithril__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./utils/patchMithril */ "./src/common/utils/patchMithril.ts");
















dayjs.extend(dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_14___default.a);
dayjs.extend(dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_15___default.a);

Object(_utils_patchMithril__WEBPACK_IMPORTED_MODULE_16__["default"])(); // import * as Extend from './extend/index';
// export { Extend };

/***/ }),

/***/ "./src/common/models/Discussion.tsx":
/*!******************************************!*\
  !*** ./src/common/models/Discussion.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Discussion; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");
/* harmony import */ var _utils_computed__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/computed */ "./src/common/utils/computed.ts");
/* harmony import */ var _utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _components_Badge__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/Badge */ "./src/common/components/Badge.tsx");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }






var Discussion = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Discussion, _Model);

  var _super = _createSuper(Discussion);

  function Discussion() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Model.call.apply(_Model, [this].concat(args)) || this;
    _this.title = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('title');
    _this.slug = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('slug');
    _this.createdAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('createdAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.user = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('user');
    _this.firstPost = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('firstPost');
    _this.lastPostedAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('lastPostedAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.lastPostedUser = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('lastPostedUser');
    _this.lastPost = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('lastPost');
    _this.lastPostNumber = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('lastPostNumber');
    _this.commentCount = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('commentCount');
    _this.replyCount = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('commentCount', function (commentCount) {
      return Math.max(0, commentCount - 1);
    });
    _this.posts = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasMany('posts');
    _this.mostRelevantPost = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('mostRelevantPost');
    _this.lastReadAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('lastReadAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.lastReadPostNumber = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('lastReadPostNumber');
    _this.isUnread = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('unreadCount', function (unreadCount) {
      return !!unreadCount;
    });
    _this.isRead = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('unreadCount', function (unreadCount) {
      return app.session.user && !unreadCount;
    });
    _this.hiddenAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('hiddenAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.hiddenUser = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('hiddenUser');
    _this.isHidden = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('hiddenAt', function (hiddenAt) {
      return !!hiddenAt;
    });
    _this.canReply = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canReply');
    _this.canRename = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canRename');
    _this.canHide = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canHide');
    _this.canDelete = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canDelete');
    return _this;
  }

  var _proto = Discussion.prototype;

  /**
   * Remove a post from the discussion's posts relationship.
   *
   * @param id The ID of the post to remove.
   */
  _proto.removePost = function removePost(id) {
    var relationships = this.data.relationships;
    var posts = relationships && relationships.posts;

    if (posts) {
      posts.data.some(function (data, i) {
        if (id === data.id) {
          posts.data.splice(i, 1);
          return true;
        }
      });
    }
  }
  /**
   * Get the estimated number of unread posts in this discussion for the current
   * user.
   */
  ;

  _proto.unreadCount = function unreadCount() {
    var user = app.session.user;

    if (user && user.markedAllAsReadAt() < this.lastPostedAt()) {
      return Math.max(0, this.lastPostNumber() - (this.lastReadPostNumber() || 0));
    }

    return 0;
  }
  /**
   * Get the Badge components that apply to this discussion.
   */
  ;

  _proto.badges = function badges() {
    var items = new _utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();

    if (this.isHidden()) {
      items.add('hidden', m(_components_Badge__WEBPACK_IMPORTED_MODULE_6__["default"], {
        type: "hidden",
        icon: "fas fa-trash",
        label: app.translator.transText('core.lib.badge.hidden_tooltip')
      }));
    }

    return items;
  }
  /**
   * Get a list of all of the post IDs in this discussion.
   */
  ;

  _proto.postIds = function postIds() {
    var _this$data$relationsh;

    var posts = (_this$data$relationsh = this.data.relationships) === null || _this$data$relationsh === void 0 ? void 0 : _this$data$relationsh.posts;
    return posts ? posts.data.map(function (link) {
      return link.id;
    }) : [];
  };

  return Discussion;
}(_Model__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/models/Forum.ts":
/*!************************************!*\
  !*** ./src/common/models/Forum.ts ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Forum; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var Forum = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Forum, _Model);

  var _super = _createSuper(Forum);

  function Forum() {
    return _Model.apply(this, arguments) || this;
  }

  var _proto = Forum.prototype;

  _proto.apiEndpoint = function apiEndpoint() {
    return '/';
  };

  return Forum;
}(_Model__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/models/Group.ts":
/*!************************************!*\
  !*** ./src/common/models/Group.ts ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Group; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var Group = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Group, _Model);

  var _super = _createSuper(Group);

  function Group() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Model.call.apply(_Model, [this].concat(args)) || this;
    _this.nameSingular = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('nameSingular');
    _this.namePlural = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('namePlural');
    _this.color = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('color');
    _this.icon = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('icon');
    return _this;
  }

  return Group;
}(_Model__WEBPACK_IMPORTED_MODULE_3__["default"]);

Group.ADMINISTRATOR_ID = '1';
Group.GUEST_ID = '2';
Group.MEMBER_ID = '3';


/***/ }),

/***/ "./src/common/models/Notification.ts":
/*!*******************************************!*\
  !*** ./src/common/models/Notification.ts ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Notification; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }



var Notification = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Notification, _Model);

  var _super = _createSuper(Notification);

  function Notification() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Model.call.apply(_Model, [this].concat(args)) || this;
    _this.contentType = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('contentType');
    _this.content = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('content');
    _this.createdAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('createdAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.isRead = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('isRead');
    _this.user = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('user');
    _this.fromUser = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('fromUser');
    _this.subject = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('subhect');
    return _this;
  }

  return Notification;
}(_Model__WEBPACK_IMPORTED_MODULE_3__["default"]);

Notification.ADMINISTRATOR_ID = '1';
Notification.GUEST_ID = '2';
Notification.MEMBER_ID = '3';


/***/ }),

/***/ "./src/common/models/Post.ts":
/*!***********************************!*\
  !*** ./src/common/models/Post.ts ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Post; });
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");
/* harmony import */ var _utils_computed__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/computed */ "./src/common/utils/computed.ts");
/* harmony import */ var _utils_string__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/string */ "./src/common/utils/string.ts");




function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_1__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_0__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }





var Post = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_2__["default"])(Post, _Model);

  var _super = _createSuper(Post);

  function Post() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Model.call.apply(_Model, [this].concat(args)) || this;
    _this.number = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('number');
    _this.discussion = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('discussion');
    _this.createdAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('createdAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.user = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('user');
    _this.contentType = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('contentType');
    _this.content = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('content');
    _this.contentHtml = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('contentHtml');
    _this.contentPlain = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('contentHtml', _utils_string__WEBPACK_IMPORTED_MODULE_5__["getPlainContent"]);
    _this.editedAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('editedAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.editedUser = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('editedUser');
    _this.isEdited = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('editedAt', function (editedAt) {
      return !!editedAt;
    });
    _this.hiddenAt = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('hiddenAt', _Model__WEBPACK_IMPORTED_MODULE_3__["default"].transformDate);
    _this.hiddenUser = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].hasOne('hiddenUser');
    _this.isHidden = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_4__["default"])('hiddenAt', function (hiddenAt) {
      return !!hiddenAt;
    });
    _this.canEdit = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canEdit');
    _this.canHide = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canHide');
    _this.canDelete = _Model__WEBPACK_IMPORTED_MODULE_3__["default"].attribute('canDelete');
    return _this;
  }

  return Post;
}(_Model__WEBPACK_IMPORTED_MODULE_3__["default"]);



/***/ }),

/***/ "./src/common/models/User.ts":
/*!***********************************!*\
  !*** ./src/common/models/User.ts ***!
  \***********************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return User; });
/* harmony import */ var _babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/extends */ "./node_modules/@babel/runtime/helpers/esm/extends.js");
/* harmony import */ var _babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/possibleConstructorReturn */ "./node_modules/@babel/runtime/helpers/esm/possibleConstructorReturn.js");
/* harmony import */ var _babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/esm/getPrototypeOf */ "./node_modules/@babel/runtime/helpers/esm/getPrototypeOf.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var _Model__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../Model */ "./src/common/Model.ts");
/* harmony import */ var _utils_stringToColor__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/stringToColor */ "./src/common/utils/stringToColor.ts");
/* harmony import */ var _utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _utils_computed__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/computed */ "./src/common/utils/computed.ts");
/* harmony import */ var _components_GroupBadge__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/GroupBadge */ "./src/common/components/GroupBadge.ts");





function _createSuper(Derived) { return function () { var Super = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(Derived), result; if (_isNativeReflectConstruct()) { var NewTarget = Object(_babel_runtime_helpers_esm_getPrototypeOf__WEBPACK_IMPORTED_MODULE_2__["default"])(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return Object(_babel_runtime_helpers_esm_possibleConstructorReturn__WEBPACK_IMPORTED_MODULE_1__["default"])(this, result); }; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }







var User = /*#__PURE__*/function (_Model) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_3__["default"])(User, _Model);

  var _super = _createSuper(User);

  function User() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Model.call.apply(_Model, [this].concat(args)) || this;
    _this.username = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('username');
    _this.displayName = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('displayName');
    _this.email = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('email');
    _this.isEmailConfirmed = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('isEmailConfirmed');
    _this.password = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('password');
    _this.avatarUrl = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('avatarUrl');
    _this.preferences = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('preferences');
    _this.groups = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].hasMany('groups');
    _this.joinTime = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('joinTime', _Model__WEBPACK_IMPORTED_MODULE_4__["default"].transformDate);
    _this.lastSeenAt = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('lastSeenAt', _Model__WEBPACK_IMPORTED_MODULE_4__["default"].transformDate);
    _this.markedAllAsReadAt = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('markedAllAsReadAt', _Model__WEBPACK_IMPORTED_MODULE_4__["default"].transformDate);
    _this.unreadNotificationCount = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('unreadNotificationCount');
    _this.newNotificationCount = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('newNotificationCount');
    _this.discussionCount = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('discussionCount');
    _this.commentCount = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('commentCount');
    _this.canEdit = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('canEdit');
    _this.canDelete = _Model__WEBPACK_IMPORTED_MODULE_4__["default"].attribute('canDelete');
    _this.avatarColor = null;
    _this.color = Object(_utils_computed__WEBPACK_IMPORTED_MODULE_7__["default"])(['username', 'avatarUrl', 'avatarColor'], function (username, avatarUrl, avatarColor) {
      // If we've already calculated and cached the dominant color of the user's
      // avatar, then we can return that in RGB format. If we haven't, we'll want
      // to calculate it. Unless the user doesn't have an avatar, in which case
      // we generate a color from their username.
      if (avatarColor) {
        return 'rgb(' + avatarColor.join(', ') + ')';
      } else if (avatarUrl) {
        this.calculateAvatarColor();
        return '';
      }

      return '#' + Object(_utils_stringToColor__WEBPACK_IMPORTED_MODULE_5__["default"])(username);
    });
    return _this;
  }

  var _proto = User.prototype;

  _proto.isOnline = function isOnline() {
    return this.lastSeenAt() > dayjs().subtract(5, 'minutes').toDate();
  }
  /**
   * Get the Badge components that apply to this user.
   */
  ;

  _proto.badges = function badges() {
    var items = new _utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    var groups = this.groups();

    if (groups) {
      groups.forEach(function (group) {
        items.add('group' + group.id(), _components_GroupBadge__WEBPACK_IMPORTED_MODULE_8__["default"].component({
          group: group
        }));
      });
    }

    return items;
  }
  /**
   * Calculate the dominant color of the user's avatar. The dominant color will
   * be set to the `avatarColor` property once it has been calculated.
   *
   * @protected
   */
  ;

  _proto.calculateAvatarColor = function calculateAvatarColor() {
    var image = new Image();
    var user = this;

    image.onload = function () {
      var colorThief = new ColorThief();
      user.avatarColor = colorThief.getColor(this);
      user.freshness = new Date();
      m.redraw();
    };

    image.crossOrigin = 'anonymous';
    image.src = this.avatarUrl();
  }
  /**
   * Update the user's preferences.
   */
  ;

  _proto.savePreferences = function savePreferences(newPreferences) {
    var preferences = this.preferences();

    Object(_babel_runtime_helpers_esm_extends__WEBPACK_IMPORTED_MODULE_0__["default"])(preferences, newPreferences);

    return this.save({
      preferences: preferences
    });
  };

  return User;
}(_Model__WEBPACK_IMPORTED_MODULE_4__["default"]);



/***/ }),

/***/ "./src/common/utils/Drawer.ts":
/*!************************************!*\
  !*** ./src/common/utils/Drawer.ts ***!
  \************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Drawer; });
/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
var Drawer = /*#__PURE__*/function () {
  function Drawer() {
    var _this = this;

    this.$backdrop = void 0;
    // Set up an event handler so that whenever the content area is tapped,
    // the drawer will close.
    $('#content').click(function (e) {
      if (_this.isOpen()) {
        e.preventDefault();

        _this.hide();
      }
    });
  }
  /**
   * Check whether or not the drawer is currently open.
   */


  var _proto = Drawer.prototype;

  _proto.isOpen = function isOpen() {
    return $('#app').hasClass('drawerOpen');
  }
  /**
   * Hide the drawer.
   */
  ;

  _proto.hide = function hide() {
    $('#app').removeClass('drawerOpen');
    if (this.$backdrop) this.$backdrop.remove();
  }
  /**
   * Show the drawer.
   */
  ;

  _proto.show = function show() {
    var _this2 = this;

    $('#app').addClass('drawerOpen');
    this.$backdrop = $('<div/>').addClass('drawer-backdrop fade').appendTo('body').click(function () {
      return _this2.hide();
    });
    requestAnimationFrame(function () {
      return _this2.$backdrop.addClass('in');
    });
  };

  return Drawer;
}();



/***/ }),

/***/ "./src/common/utils/Evented.ts":
/*!*************************************!*\
  !*** ./src/common/utils/Evented.ts ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Evented; });
var Evented = /*#__PURE__*/function () {
  function Evented() {
    this.handlers = {};
  }

  var _proto = Evented.prototype;

  /**
   * Get all of the registered handlers for an event.
   *
   * @param event The name of the event.
   */
  _proto.getHandlers = function getHandlers(event) {
    this.handlers = this.handlers || {};
    this.handlers[event] = this.handlers[event] || [];
    return this.handlers[event];
  }
  /**
   * Trigger an event.
   *
   * @param event The name of the event.
   * @param args Arguments to pass to event handlers.
   */
  ;

  _proto.trigger = function trigger(event) {
    var _this = this;

    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    this.getHandlers(event).forEach(function (handler) {
      return handler.apply(_this, args);
    });
    return this;
  }
  /**
   * Register an event handler.
   *
   * @param event The name of the event.
   * @param handler The function to handle the event.
   */
  ;

  _proto.on = function on(event, handler) {
    this.getHandlers(event).push(handler);
    return this;
  }
  /**
   * Register an event handler so that it will run only once, and then
   * unregister itself.
   *
   * @param event The name of the event.
   * @param handler The function to handle the event.
   */
  ;

  _proto.one = function one(event, handler) {
    var wrapper = function wrapper() {
      handler.apply(this, Array.from(arguments));
      this.off(event, wrapper);
    };

    this.getHandlers(event).push(wrapper);
    return this;
  }
  /**
   * Unregister an event handler.
   *
   * @param event The name of the event.
   * @param handler The function that handles the event.
   */
  ;

  _proto.off = function off(event, handler) {
    var handlers = this.getHandlers(event);
    var index = handlers.indexOf(handler);

    if (index !== -1) {
      handlers.splice(index, 1);
    }

    return this;
  };

  return Evented;
}();



/***/ }),

/***/ "./src/common/utils/ItemList.ts":
/*!**************************************!*\
  !*** ./src/common/utils/ItemList.ts ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ItemList; });
var Item = function Item(content, priority) {
  this.content = void 0;
  this.priority = void 0;
  this.key = 0;
  this.content = content;
  this.priority = priority;
};

var ItemList = /*#__PURE__*/function () {
  function ItemList() {
    this.items = {};
  }

  var _proto = ItemList.prototype;

  /**
   * Check whether the list is empty.
   *
   * @returns {boolean}
   * @public
   */
  _proto.isEmpty = function isEmpty() {
    for (var i in this.items) {
      if (this.items.hasOwnProperty(i)) {
        return false;
      }
    }

    return true;
  }
  /**
   * Check whether an item is present in the list.
   */
  ;

  _proto.has = function has(key) {
    return !!this.items[key];
  }
  /**
   * Get the content of an item.
   */
  ;

  _proto.get = function get(key) {
    var _this$items$key;

    return (_this$items$key = this.items[key]) === null || _this$items$key === void 0 ? void 0 : _this$items$key.content;
  }
  /**
   * Add an item to the list.
   *
   * @param {String} key A unique key for the item.
   * @param {*} content The item's content.
   * @param {Integer} [priority] The priority of the item. Items with a higher
   *     priority will be positioned before items with a lower priority.
   * @return {ItemList}
   * @public
   */
  ;

  _proto.add = function add(key, content, priority) {
    if (priority === void 0) {
      priority = 0;
    }

    this.items[key] = new Item(content, priority);
    return this;
  };

  _proto.toArray = function toArray() {
    var items = [];

    for (var i in this.items) {
      if (this.items.hasOwnProperty(i)) {
        if (this.items[i] !== null && this.items[i] instanceof Item) {
          this.items[i].content = Object(this.items[i].content); // @ts-ignore

          this.items[i].content.itemName = i;
          items.push(this.items[i]);
          this.items[i].key = items.length;
        }
      }
    }

    return items.sort(function (a, b) {
      if (a.priority === b.priority) {
        return a.key - b.key;
      } else if (a.priority > b.priority) {
        return -1;
      }

      return 1;
    }).map(function (item) {
      return item.content;
    });
  };

  return ItemList;
}();



/***/ }),

/***/ "./src/common/utils/RequestError.ts":
/*!******************************************!*\
  !*** ./src/common/utils/RequestError.ts ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return RequestError; });
var RequestError = function RequestError(status, responseText, options, xhr) {
  this.status = void 0;
  this.responseText = void 0;
  this.options = void 0;
  this.xhr = void 0;
  this.response = void 0;
  this.alert = void 0;
  this.status = status;
  this.responseText = responseText;
  this.options = options;
  this.xhr = xhr;

  try {
    this.response = JSON.parse(responseText);
  } catch (e) {
    this.response = null;
  }

  this.alert = null;
};



/***/ }),

/***/ "./src/common/utils/ScrollListener.ts":
/*!********************************************!*\
  !*** ./src/common/utils/ScrollListener.ts ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ScrollListener; });
var later = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.msRequestAnimationFrame || window.oRequestAnimationFrame || function (callback) {
  return window.setTimeout(callback, 1000 / 60);
};
/**
 * The `ScrollListener` class sets up a listener that handles window scroll
 * events.
 */


var ScrollListener = /*#__PURE__*/function () {
  /**
   * @param callback The callback to run when the scroll position
   *     changes.
   */
  function ScrollListener(callback) {
    this.ticking = false;
    this.callback = void 0;
    this.active = void 0;
    this.callback = callback;
  }
  /**
   * On each animation frame, as long as the listener is active, run the
   * `update` method.
   */


  var _proto = ScrollListener.prototype;

  _proto.loop = function loop() {
    var _this = this;

    // THROTTLE: If the callback is still running (or hasn't yet run), we ignore
    // further scroll events.
    if (this.ticking) return; // Schedule the callback to be executed soon (TM), and stop throttling once
    // the callback is done.

    later(function () {
      _this.update();

      _this.ticking = false;
    });
    this.ticking = true;
  }
  /**
   * Run the callback, whether there was a scroll event or not.
   */
  ;

  _proto.update = function update() {
    this.callback(window.pageYOffset);
  }
  /**
   * Start listening to and handling the window's scroll position.
   */
  ;

  _proto.start = function start() {
    if (!this.active) {
      window.addEventListener('scroll', this.active = this.loop.bind(this));
    }
  }
  /**
   * Stop listening to and handling the window's scroll position.
   */
  ;

  _proto.stop = function stop() {
    window.removeEventListener('scroll', this.active);
    this.active = null;
  };

  return ScrollListener;
}();



/***/ }),

/***/ "./src/common/utils/SubtreeRetainer.ts":
/*!*********************************************!*\
  !*** ./src/common/utils/SubtreeRetainer.ts ***!
  \*********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return SubtreeRetainer; });
var SubtreeRetainer = /*#__PURE__*/function () {
  function SubtreeRetainer() {
    this.callbacks = void 0;
    this.data = {};

    for (var _len = arguments.length, callbacks = new Array(_len), _key = 0; _key < _len; _key++) {
      callbacks[_key] = arguments[_key];
    }

    this.callbacks = callbacks;
  }

  var _proto = SubtreeRetainer.prototype;

  _proto.check = function check() {
    var _this$callbacks;

    (_this$callbacks = this.callbacks).push.apply(_this$callbacks, arguments);
  }
  /**
   * Return whether the component should redraw.
   */
  ;

  _proto.update = function update() {
    var _this = this;

    var update = false;
    this.callbacks.forEach(function (callback, i) {
      var result = callback();

      if (result !== _this.data[i]) {
        _this.data[i] = result;
        update = true;
      }
    });
    return update;
  };

  return SubtreeRetainer;
}();



/***/ }),

/***/ "./src/common/utils/abbreviateNumber.tsx":
/*!***********************************************!*\
  !*** ./src/common/utils/abbreviateNumber.tsx ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/**
 * The `abbreviateNumber` utility converts a number to a shorter localized form.
 *
 * @example
 * abbreviateNumber(1234);
 * // "1.2K"
 */
/* harmony default export */ __webpack_exports__["default"] = (function (number) {
  // TODO: translation
  if (number >= 1000000) {
    return Math.floor(number / 1000000) + app.translator.transText('core.lib.number_suffix.mega_text');
  } else if (number >= 1000) {
    return Math.floor(number / 1000) + app.translator.transText('core.lib.number_suffix.kilo_text');
  } else {
    return number.toString();
  }
});

/***/ }),

/***/ "./src/common/utils/anchorScroll.ts":
/*!******************************************!*\
  !*** ./src/common/utils/anchorScroll.ts ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return anchorScroll; });
/**
 * The `anchorScroll` utility saves the scroll position relative to an element,
 * and then restores it after a callback has been run.
 *
 * This is useful if a redraw will change the page's content above the viewport.
 * Normally doing this will result in the content in the viewport being pushed
 * down or pulled up. By wrapping the redraw with this utility, the scroll
 * position can be anchor to an element that is in or below the viewport, so
 * the content in the viewport will stay the same.
 *
 * @param element The element to anchor the scroll position to.
 * @param callback The callback to run that will change page content.
 */
function anchorScroll(element, callback) {
  var $window = $(window);
  var $el = $(element);

  if (!element || !$el.length) {
    return callback();
  }

  var relativeScroll = $el.offset().top - $window.scrollTop();
  callback();
  $window.scrollTop($el.offset().top - relativeScroll);
}

/***/ }),

/***/ "./src/common/utils/computed.ts":
/*!**************************************!*\
  !*** ./src/common/utils/computed.ts ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return computed; });
/**
 * The `computed` utility creates a function that will cache its output until
 * any of the dependent values are dirty.
 *
 * @param dependentKeys The keys of the dependent values.
 * @param compute The function which computes the value using the
 *     dependent values.
 */
function computed(dependentKeys, compute) {
  var keys = [].concat(dependentKeys);
  var dependentValues = {};
  var computedValue;
  return function () {
    var _this = this;

    var recompute = false; // Read all of the dependent values. If any of them have changed since last
    // time, then we'll want to recompute our output.

    keys.forEach(function (key) {
      var value = typeof _this[key] === 'function' ? _this[key]() : _this[key];

      if (dependentValues[key] !== value) {
        recompute = true;
        dependentValues[key] = value;
      }
    });

    if (recompute) {
      computedValue = compute.apply(this, keys.map(function (key) {
        return dependentValues[key];
      }));
    }

    return computedValue;
  };
}

/***/ }),

/***/ "./src/common/utils/extract.ts":
/*!*************************************!*\
  !*** ./src/common/utils/extract.ts ***!
  \*************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return extract; });
/**
 * The `extract` utility deletes a property from an object and returns its
 * value.
 */
function extract(object, property) {
  var value = object[property];
  delete object[property];
  return value;
}

/***/ }),

/***/ "./src/common/utils/extractText.ts":
/*!*****************************************!*\
  !*** ./src/common/utils/extractText.ts ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return extractText; });
/**
 * Extract the text nodes from a virtual element.
 *
 * @param {VirtualElement} vdom
 * @return {String}
 */
function extractText(vdom) {
  if (vdom instanceof Array) {
    return vdom.map(function (element) {
      return extractText(element);
    }).join('');
  } else if (typeof vdom === 'object' && vdom !== null) {
    return vdom.text || extractText(vdom.children);
  } else {
    return vdom;
  }
}

/***/ }),

/***/ "./src/common/utils/formatNumber.ts":
/*!******************************************!*\
  !*** ./src/common/utils/formatNumber.ts ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return formatNumber; });
/**
 * The `formatNumber` utility localizes a number into a string with the
 * appropriate punctuation.
 *
 * @param number Number to format
 * @param options Maximum significant digits or formatting options object
 *
 * @example
 * formatNumber(1234);
 * // 1,234
 */
function formatNumber(number, options) {
  if (options === void 0) {
    options = {};
  }

  var config = typeof options === 'number' ? {
    maximumSignificantDigits: options
  } : options;
  return number.toLocaleString(app.translator.locale, config);
}

/***/ }),

/***/ "./src/common/utils/humanTime.ts":
/*!***************************************!*\
  !*** ./src/common/utils/humanTime.ts ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return humanTime; });
/**
 * The `humanTime` utility converts a date to a localized, human-readable time-
 * ago string.
 */
function humanTime(time) {
  var m = dayjs(time);
  var now = dayjs(); // To prevent showing things like "in a few seconds" due to small offsets
  // between client and server time, we always reset future dates to the
  // current time. This will result in "just now" being shown instead.

  if (m.isAfter(now)) {
    m = now;
  }

  var day = 864e5;
  var diff = m.diff(dayjs());
  var ago = null; // If this date was more than a month ago, we'll show the name of the month
  // in the string. If it wasn't this year, we'll show the year as well.

  if (diff < -30 * day) {
    if (m.year() === dayjs().year()) {
      ago = m.format('D MMM');
    } else {
      ago = m.format("MMM 'YY");
    }
  } else {
    ago = m.fromNow();
  }

  return ago;
}

/***/ }),

/***/ "./src/common/utils/mapRoutes.ts":
/*!***************************************!*\
  !*** ./src/common/utils/mapRoutes.ts ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return mapRoutes; });
/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://lhorie.github.io/mithril/mithril.route.html#defining-routes
 */
function mapRoutes(routes, basePath) {
  if (basePath === void 0) {
    basePath = '';
  }

  var map = {};

  for (var key in routes) {
    var route = routes[key];

    if (route.component) {
      if (!route.component.attrs) route.component.attrs = {};
      route.component.attrs.routeName = key;
    }

    map[basePath + route.path] = route.component;
  }

  return map;
}

/***/ }),

/***/ "./src/common/utils/patchMithril.ts":
/*!******************************************!*\
  !*** ./src/common/utils/patchMithril.ts ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var mithril__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! mithril */ "mithril");
/* harmony import */ var mithril__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(mithril__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var mithril_stream__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! mithril/stream */ "./node_modules/mithril/stream.js");
/* harmony import */ var mithril_stream__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(mithril_stream__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");



/* harmony default export */ __webpack_exports__["default"] = (function () {
  var mo = window['m'];

  var _m = function _m(comp) {
    for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      args[_key - 1] = arguments[_key];
    }

    if (!arguments[1]) arguments[1] = {};

    if (comp.prototype && comp.prototype instanceof _Component__WEBPACK_IMPORTED_MODULE_2__["default"]) {
      // allow writing to children attribute
      Object.defineProperty(arguments[1], 'children', {
        writable: true
      });
    }

    var node = mo.apply(this, arguments);
    if (!node.attrs) node.attrs = {};

    if (node.attrs.bidi) {
      mithril__WEBPACK_IMPORTED_MODULE_0___default.a.bidi(node, node.attrs.bidi);
    }

    if (node.attrs.route) {
      node.attrs.href = node.attrs.route;
      node.attrs.tag = mithril__WEBPACK_IMPORTED_MODULE_0___default.a.route.Link;
      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(mo).forEach(function (key) {
    return _m[key] = mo[key];
  });

  _m.withAttr = function (key, cb) {
    return function () {
      cb(this.getAttribute(key) || this[key]);
    };
  };

  _m.prop = mithril_stream__WEBPACK_IMPORTED_MODULE_1___default.a;
  window['m'] = _m;
});

/***/ }),

/***/ "./src/common/utils/patchZepto.ts":
/*!****************************************!*\
  !*** ./src/common/utils/patchZepto.ts ***!
  \****************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var jump_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! jump.js */ "./node_modules/jump.js/dist/jump.module.js");
/* harmony import */ var tooltip_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! tooltip.js */ "./node_modules/tooltip.js/dist/esm/tooltip.js");

 // add $.fn.tooltip

$.fn.tooltip = function (option) {
  return this.each(function () {
    var $this = $(this);
    var data = $this.data('bs.tooltip');
    var options = typeof option === 'object' && option || {};

    if ($this.attr('title')) {
      options.title = $this.attr('title');
      $this.removeAttr('title');
      $this.attr('data-original-title', options.title);
    }

    if (option === 'destroy') option = 'dispose';
    if (!data && ['dispose', 'hide'].includes(option)) return;
    if (!data) $this.data('bs.tooltip', data = new tooltip_js__WEBPACK_IMPORTED_MODULE_1__["default"](this, options));
    if (typeof option === 'string' && data[option]) data[option]();
  });
}; // add $.fn.outerWidth and $.fn.outerHeight


['width', 'height'].forEach(function (dimension) {
  var Dimension = dimension.replace(/./, function (m) {
    return m[0].toUpperCase();
  });

  $.fn["outer" + Dimension] = function (margin) {
    var elem = this;

    if (elem) {
      var sides = {
        width: ['left', 'right'],
        height: ['top', 'bottom']
      };
      var size = elem[dimension]();
      sides[dimension].forEach(function (side) {
        if (margin) size += parseInt(elem.css('margin-' + side), 10);
      });
      return size;
    } else {
      return null;
    }
  };
}); // allow use of $(':input')
// @ts-ignore

$.expr[':']['input'] = function () {
  if ('disabled' in this || ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'].includes(this.tagName)) return this;
}; // add $().hover() method


$.fn.hover = function (hover, leave) {
  return this.on('mouseenter', hover).on('mouseleave', leave || hover);
}; // add animated scroll


$.fn.animateScrollTop = function (to, duration, callback) {
  if (duration === void 0) {
    duration = $.fx.speeds._default;
  }

  if (typeof to === 'number') to -= window.scrollY || window.pageYOffset;
  Object(jump_js__WEBPACK_IMPORTED_MODULE_0__["default"])(to, {
    duration: $.fx.speeds[duration] || duration,
    callback: callback
  });
  return this;
}; // add basic $().slideUp() function


$.fn.slideUp = function (duration, easing, callback) {
  if (duration === void 0) {
    duration = $.fx.speeds._default;
  }

  this.css({
    overflow: 'hidden',
    height: this.height()
  });
  this.animate({
    height: 0
  }, duration, easing, callback);
  return this;
}; // required for compatibility with jquery plugins
// ex: bootstrap plugins


$.fn.extend = $.extend.bind($);
/**
 * Enable special events on Zepto
 * @license Original Copyright 2013 Enideo. Released under dual MIT and GPL licenses.
 */

$.event.special = $.event.special || {};
var bindBeforeSpecialEvents = $.fn.bind;

$.fn.bind = function (eventName, data, callback) {
  var el = this;

  if (!callback) {
    callback = data;
    data = null;
  }

  $.each(eventName.split(/\s/), function (key, value) {
    value = value.split(/\./)[0];

    if (value in $.event.special) {
      var specialEvent = $.event.special[value]; /// init enable special events on Zepto

      if (!specialEvent._init) {
        specialEvent._init = true; /// intercept and replace the special event handler to add functionality

        specialEvent.originalHandler = specialEvent.handler;

        specialEvent.handler = function () {
          /// make event argument writable, like on jQuery
          var args = Array.prototype.slice.call(arguments);
          args[0] = $.extend({}, args[0]); /// define the event handle, $.event.dispatch is only for newer versions of jQuery

          $.event.handle = function () {
            /// make context of trigger the event element
            var args = Array.prototype.slice.call(arguments);
            var event = args[0];
            var $target = $(event.target);
            $target.trigger.apply($target, arguments);
          };

          specialEvent.originalHandler.apply(this, args);
        };
      } /// setup special events on Zepto


      specialEvent.setup.apply(el, [data]);
    }

    return true;
  });
  return bindBeforeSpecialEvents.apply(this, [eventName, callback]);
};

/***/ }),

/***/ "./src/common/utils/string.ts":
/*!************************************!*\
  !*** ./src/common/utils/string.ts ***!
  \************************************/
/*! exports provided: truncate, slug, getPlainContent, ucfirst */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "truncate", function() { return truncate; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "slug", function() { return slug; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getPlainContent", function() { return getPlainContent; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ucfirst", function() { return ucfirst; });
/**
 * Truncate a string to the given length, appending ellipses if necessary.
 */
function truncate(string, length, start) {
  if (start === void 0) {
    start = 0;
  }

  return (start > 0 ? '...' : '') + string.substring(start, start + length) + (string.length > start + length ? '...' : '');
}
/**
 * Create a slug out of the given string. Non-alphanumeric characters are
 * converted to hyphens.
 */

function slug(string) {
  return string.toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, '');
}
/**
 * Strip HTML tags and quotes out of the given string, replacing them with
 * meaningful punctuation.
 */

function getPlainContent(string) {
  var html = string.replace(/(<\/p>|<br>)/g, '$1 &nbsp;').replace(/<img\b[^>]*>/gi, ' ');
  var dom = $('<div/>').html(html);
  dom.find(getPlainContent.removeSelectors.join(',')).remove();
  return dom.text().replace(/\s+/g, ' ').trim();
}
/**
 * An array of DOM selectors to remove when getting plain content.
 *
 * @type {String[]}
 */

getPlainContent.removeSelectors = ['blockquote', 'script'];
/**
 * Make a string's first character uppercase.
 */

function ucfirst(string) {
  return string.substr(0, 1).toUpperCase() + string.substr(1);
}

/***/ }),

/***/ "./src/common/utils/stringToColor.ts":
/*!*******************************************!*\
  !*** ./src/common/utils/stringToColor.ts ***!
  \*******************************************/
/*! exports provided: hsvToRgb, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "hsvToRgb", function() { return hsvToRgb; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return stringToColor; });
function hsvToRgb(h, s, v) {
  var r;
  var g;
  var b;
  var i = Math.floor(h * 6);
  var f = h * 6 - i;
  var p = v * (1 - s);
  var q = v * (1 - f * s);
  var t = v * (1 - (1 - f) * s);

  switch (i % 6) {
    case 0:
      r = v;
      g = t;
      b = p;
      break;

    case 1:
      r = q;
      g = v;
      b = p;
      break;

    case 2:
      r = p;
      g = v;
      b = t;
      break;

    case 3:
      r = p;
      g = q;
      b = v;
      break;

    case 4:
      r = t;
      g = p;
      b = v;
      break;

    case 5:
      r = v;
      g = p;
      b = q;
      break;
  }

  return {
    r: Math.floor(r * 255),
    g: Math.floor(g * 255),
    b: Math.floor(b * 255)
  };
}
/**
 * Convert the given string to a unique color.
 */

function stringToColor(string) {
  var num = 0; // Convert the username into a number based on the ASCII value of each
  // character.

  for (var i = 0; i < string.length; i++) {
    num += string.charCodeAt(i);
  } // Construct a color using the remainder of that number divided by 360, and
  // some predefined saturation and value values.


  var hue = num % 360;
  var rgb = hsvToRgb(hue / 360, 0.3, 0.9);
  return '' + rgb.r.toString(16) + rgb.g.toString(16) + rgb.b.toString(16);
}

/***/ }),

/***/ "mithril":
/*!********************!*\
  !*** external "m" ***!
  \********************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = m;

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map