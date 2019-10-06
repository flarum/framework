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
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_common__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/common */ "./src/common/index.ts");
/* empty/unused harmony star reexport *//* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.js");
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_src_admin__WEBPACK_IMPORTED_MODULE_1__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _src_admin__WEBPACK_IMPORTED_MODULE_1__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _src_admin__WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */



/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/imports-loader/index.js?this=>window!./node_modules/source-map-loader/index.js!./node_modules/zepto/dist/zepto.js":
/*!**************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--5!./node_modules/imports-loader?this=>window!./node_modules/source-map-loader!./node_modules/zepto/dist/zepto.js ***!
  \**************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_RESULT__;/*** IMPORTS FROM imports-loader ***/
(function () {
  /* Zepto v1.2.0 - zepto event ajax form ie - zeptojs.com/license */
  (function (global, factory) {
    if (true) !(__WEBPACK_AMD_DEFINE_RESULT__ = (function () {
      return factory(global);
    }).call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));else {}
  })(this, function (window) {
    var Zepto = function () {
      var undefined,
          key,
          $,
          classList,
          emptyArray = [],
          _concat = emptyArray.concat,
          _filter = emptyArray.filter,
          _slice = emptyArray.slice,
          document = window.document,
          elementDisplay = {},
          classCache = {},
          cssNumber = {
        'column-count': 1,
        'columns': 1,
        'font-weight': 1,
        'line-height': 1,
        'opacity': 1,
        'z-index': 1,
        'zoom': 1
      },
          fragmentRE = /^\s*<(\w+|!)[^>]*>/,
          singleTagRE = /^<(\w+)\s*\/?>(?:<\/\1>|)$/,
          tagExpanderRE = /<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,
          rootNodeRE = /^(?:body|html)$/i,
          capitalRE = /([A-Z])/g,
          // special attributes that should be get/set via method calls
      methodAttributes = ['val', 'css', 'html', 'text', 'data', 'width', 'height', 'offset'],
          adjacencyOperators = ['after', 'prepend', 'before', 'append'],
          table = document.createElement('table'),
          tableRow = document.createElement('tr'),
          containers = {
        'tr': document.createElement('tbody'),
        'tbody': table,
        'thead': table,
        'tfoot': table,
        'td': tableRow,
        'th': tableRow,
        '*': document.createElement('div')
      },
          readyRE = /complete|loaded|interactive/,
          simpleSelectorRE = /^[\w-]*$/,
          class2type = {},
          toString = class2type.toString,
          zepto = {},
          camelize,
          uniq,
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
          isArray = Array.isArray || function (object) {
        return object instanceof Array;
      };

      zepto.matches = function (element, selector) {
        if (!selector || !element || element.nodeType !== 1) return false;
        var matchesSelector = element.matches || element.webkitMatchesSelector || element.mozMatchesSelector || element.oMatchesSelector || element.matchesSelector;
        if (matchesSelector) return matchesSelector.call(element, selector); // fall back to performing a selector:

        var match,
            parent = element.parentNode,
            temp = !parent;
        if (temp) (parent = tempParent).appendChild(element);
        match = ~zepto.qsa(parent, selector).indexOf(element);
        temp && tempParent.removeChild(element);
        return match;
      };

      function type(obj) {
        return obj == null ? String(obj) : class2type[toString.call(obj)] || "object";
      }

      function isFunction(value) {
        return type(value) == "function";
      }

      function isWindow(obj) {
        return obj != null && obj == obj.window;
      }

      function isDocument(obj) {
        return obj != null && obj.nodeType == obj.DOCUMENT_NODE;
      }

      function isObject(obj) {
        return type(obj) == "object";
      }

      function isPlainObject(obj) {
        return isObject(obj) && !isWindow(obj) && Object.getPrototypeOf(obj) == Object.prototype;
      }

      function likeArray(obj) {
        var length = !!obj && 'length' in obj && obj.length,
            type = $.type(obj);
        return 'function' != type && !isWindow(obj) && ('array' == type || length === 0 || typeof length == 'number' && length > 0 && length - 1 in obj);
      }

      function compact(array) {
        return _filter.call(array, function (item) {
          return item != null;
        });
      }

      function flatten(array) {
        return array.length > 0 ? $.fn.concat.apply([], array) : array;
      }

      camelize = function camelize(str) {
        return str.replace(/-+(.)?/g, function (match, chr) {
          return chr ? chr.toUpperCase() : '';
        });
      };

      function dasherize(str) {
        return str.replace(/::/g, '/').replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2').replace(/([a-z\d])([A-Z])/g, '$1_$2').replace(/_/g, '-').toLowerCase();
      }

      uniq = function uniq(array) {
        return _filter.call(array, function (item, idx) {
          return array.indexOf(item) == idx;
        });
      };

      function classRE(name) {
        return name in classCache ? classCache[name] : classCache[name] = new RegExp('(^|\\s)' + name + '(\\s|$)');
      }

      function maybeAddPx(name, value) {
        return typeof value == "number" && !cssNumber[dasherize(name)] ? value + "px" : value;
      }

      function defaultDisplay(nodeName) {
        var element, display;

        if (!elementDisplay[nodeName]) {
          element = document.createElement(nodeName);
          document.body.appendChild(element);
          display = getComputedStyle(element, '').getPropertyValue("display");
          element.parentNode.removeChild(element);
          display == "none" && (display = "block");
          elementDisplay[nodeName] = display;
        }

        return elementDisplay[nodeName];
      }

      function _children(element) {
        return 'children' in element ? _slice.call(element.children) : $.map(element.childNodes, function (node) {
          if (node.nodeType == 1) return node;
        });
      }

      function Z(dom, selector) {
        var i,
            len = dom ? dom.length : 0;

        for (i = 0; i < len; i++) {
          this[i] = dom[i];
        }

        this.length = len;
        this.selector = selector || '';
      } // `$.zepto.fragment` takes a html string and an optional tag name
      // to generate DOM nodes from the given html string.
      // The generated DOM nodes are returned as an array.
      // This function can be overridden in plugins for example to make
      // it compatible with browsers that don't support the DOM fully.


      zepto.fragment = function (html, name, properties) {
        var dom, nodes, container; // A special case optimization for a single tag

        if (singleTagRE.test(html)) dom = $(document.createElement(RegExp.$1));

        if (!dom) {
          if (html.replace) html = html.replace(tagExpanderRE, "<$1></$2>");
          if (name === undefined) name = fragmentRE.test(html) && RegExp.$1;
          if (!(name in containers)) name = '*';
          container = containers[name];
          container.innerHTML = '' + html;
          dom = $.each(_slice.call(container.childNodes), function () {
            container.removeChild(this);
          });
        }

        if (isPlainObject(properties)) {
          nodes = $(dom);
          $.each(properties, function (key, value) {
            if (methodAttributes.indexOf(key) > -1) nodes[key](value);else nodes.attr(key, value);
          });
        }

        return dom;
      }; // `$.zepto.Z` swaps out the prototype of the given `dom` array
      // of nodes with `$.fn` and thus supplying all the Zepto functions
      // to the array. This method can be overridden in plugins.


      zepto.Z = function (dom, selector) {
        return new Z(dom, selector);
      }; // `$.zepto.isZ` should return `true` if the given object is a Zepto
      // collection. This method can be overridden in plugins.


      zepto.isZ = function (object) {
        return object instanceof zepto.Z;
      }; // `$.zepto.init` is Zepto's counterpart to jQuery's `$.fn.init` and
      // takes a CSS selector and an optional context (and handles various
      // special cases).
      // This method can be overridden in plugins.


      zepto.init = function (selector, context) {
        var dom; // If nothing given, return an empty Zepto collection

        if (!selector) return zepto.Z(); // Optimize for string selectors
        else if (typeof selector == 'string') {
            selector = selector.trim(); // If it's a html fragment, create nodes from it
            // Note: In both Chrome 21 and Firefox 15, DOM error 12
            // is thrown if the fragment doesn't begin with <

            if (selector[0] == '<' && fragmentRE.test(selector)) dom = zepto.fragment(selector, RegExp.$1, context), selector = null; // If there's a context, create a collection on that context first, and select
            // nodes from there
            else if (context !== undefined) return $(context).find(selector); // If it's a CSS selector, use it to select nodes.
              else dom = zepto.qsa(document, selector);
          } // If a function is given, call it when the DOM is ready
          else if (isFunction(selector)) return $(document).ready(selector); // If a Zepto collection is given, just return it
            else if (zepto.isZ(selector)) return selector;else {
                // normalize array if an array of nodes is given
                if (isArray(selector)) dom = compact(selector); // Wrap DOM nodes.
                else if (isObject(selector)) dom = [selector], selector = null; // If it's a html fragment, create nodes from it
                  else if (fragmentRE.test(selector)) dom = zepto.fragment(selector.trim(), RegExp.$1, context), selector = null; // If there's a context, create a collection on that context first, and select
                    // nodes from there
                    else if (context !== undefined) return $(context).find(selector); // And last but no least, if it's a CSS selector, use it to select nodes.
                      else dom = zepto.qsa(document, selector);
              } // create a new Zepto collection from the nodes found

        return zepto.Z(dom, selector);
      }; // `$` will be the base `Zepto` object. When calling this
      // function just call `$.zepto.init, which makes the implementation
      // details of selecting nodes and creating Zepto collections
      // patchable in plugins.


      $ = function $(selector, context) {
        return zepto.init(selector, context);
      };

      function extend(target, source, deep) {
        for (key in source) {
          if (deep && (isPlainObject(source[key]) || isArray(source[key]))) {
            if (isPlainObject(source[key]) && !isPlainObject(target[key])) target[key] = {};
            if (isArray(source[key]) && !isArray(target[key])) target[key] = [];
            extend(target[key], source[key], deep);
          } else if (source[key] !== undefined) target[key] = source[key];
        }
      } // Copy all but undefined properties from one or more
      // objects to the `target` object.


      $.extend = function (target) {
        var deep,
            args = _slice.call(arguments, 1);

        if (typeof target == 'boolean') {
          deep = target;
          target = args.shift();
        }

        args.forEach(function (arg) {
          extend(target, arg, deep);
        });
        return target;
      }; // `$.zepto.qsa` is Zepto's CSS selector implementation which
      // uses `document.querySelectorAll` and optimizes for some special cases, like `#id`.
      // This method can be overridden in plugins.


      zepto.qsa = function (element, selector) {
        var found,
            maybeID = selector[0] == '#',
            maybeClass = !maybeID && selector[0] == '.',
            nameOnly = maybeID || maybeClass ? selector.slice(1) : selector,
            // Ensure that a 1 char tag name still gets checked
        isSimple = simpleSelectorRE.test(nameOnly);
        return element.getElementById && isSimple && maybeID ? // Safari DocumentFragment doesn't have getElementById
        (found = element.getElementById(nameOnly)) ? [found] : [] : element.nodeType !== 1 && element.nodeType !== 9 && element.nodeType !== 11 ? [] : _slice.call(isSimple && !maybeID && element.getElementsByClassName ? // DocumentFragment doesn't have getElementsByClassName/TagName
        maybeClass ? element.getElementsByClassName(nameOnly) : // If it's simple, it could be a class
        element.getElementsByTagName(selector) : // Or a tag
        element.querySelectorAll(selector) // Or it's not simple, and we need to query all
        );
      };

      function filtered(nodes, selector) {
        return selector == null ? $(nodes) : $(nodes).filter(selector);
      }

      $.contains = document.documentElement.contains ? function (parent, node) {
        return parent !== node && parent.contains(node);
      } : function (parent, node) {
        while (node && (node = node.parentNode)) {
          if (node === parent) return true;
        }

        return false;
      };

      function funcArg(context, arg, idx, payload) {
        return isFunction(arg) ? arg.call(context, idx, payload) : arg;
      }

      function setAttribute(node, name, value) {
        value == null ? node.removeAttribute(name) : node.setAttribute(name, value);
      } // access className property while respecting SVGAnimatedString


      function className(node, value) {
        var klass = node.className || '',
            svg = klass && klass.baseVal !== undefined;
        if (value === undefined) return svg ? klass.baseVal : klass;
        svg ? klass.baseVal = value : node.className = value;
      } // "true"  => true
      // "false" => false
      // "null"  => null
      // "42"    => 42
      // "42.5"  => 42.5
      // "08"    => "08"
      // JSON    => parse if valid
      // String  => self


      function deserializeValue(value) {
        try {
          return value ? value == "true" || (value == "false" ? false : value == "null" ? null : +value + "" == value ? +value : /^[\[\{]/.test(value) ? $.parseJSON(value) : value) : value;
        } catch (e) {
          return value;
        }
      }

      $.type = type;
      $.isFunction = isFunction;
      $.isWindow = isWindow;
      $.isArray = isArray;
      $.isPlainObject = isPlainObject;

      $.isEmptyObject = function (obj) {
        var name;

        for (name in obj) {
          return false;
        }

        return true;
      };

      $.isNumeric = function (val) {
        var num = Number(val),
            type = typeof val;
        return val != null && type != 'boolean' && (type != 'string' || val.length) && !isNaN(num) && isFinite(num) || false;
      };

      $.inArray = function (elem, array, i) {
        return emptyArray.indexOf.call(array, elem, i);
      };

      $.camelCase = camelize;

      $.trim = function (str) {
        return str == null ? "" : String.prototype.trim.call(str);
      }; // plugin compatibility


      $.uuid = 0;
      $.support = {};
      $.expr = {};

      $.noop = function () {};

      $.map = function (elements, callback) {
        var value,
            values = [],
            i,
            key;
        if (likeArray(elements)) for (i = 0; i < elements.length; i++) {
          value = callback(elements[i], i);
          if (value != null) values.push(value);
        } else for (key in elements) {
          value = callback(elements[key], key);
          if (value != null) values.push(value);
        }
        return flatten(values);
      };

      $.each = function (elements, callback) {
        var i, key;

        if (likeArray(elements)) {
          for (i = 0; i < elements.length; i++) {
            if (callback.call(elements[i], i, elements[i]) === false) return elements;
          }
        } else {
          for (key in elements) {
            if (callback.call(elements[key], key, elements[key]) === false) return elements;
          }
        }

        return elements;
      };

      $.grep = function (elements, callback) {
        return _filter.call(elements, callback);
      };

      if (window.JSON) $.parseJSON = JSON.parse; // Populate the class2type map

      $.each("Boolean Number String Function Array Date RegExp Object Error".split(" "), function (i, name) {
        class2type["[object " + name + "]"] = name.toLowerCase();
      }); // Define methods that will be available on all
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
        concat: function concat() {
          var i,
              value,
              args = [];

          for (i = 0; i < arguments.length; i++) {
            value = arguments[i];
            args[i] = zepto.isZ(value) ? value.toArray() : value;
          }

          return _concat.apply(zepto.isZ(this) ? this.toArray() : this, args);
        },
        // `map` and `slice` in the jQuery API work differently
        // from their array counterparts
        map: function map(fn) {
          return $($.map(this, function (el, i) {
            return fn.call(el, i, el);
          }));
        },
        slice: function slice() {
          return $(_slice.apply(this, arguments));
        },
        ready: function ready(callback) {
          // need to check if document.body exists for IE as that browser reports
          // document ready when it hasn't yet created the body element
          if (readyRE.test(document.readyState) && document.body) callback($);else document.addEventListener('DOMContentLoaded', function () {
            callback($);
          }, false);
          return this;
        },
        get: function get(idx) {
          return idx === undefined ? _slice.call(this) : this[idx >= 0 ? idx : idx + this.length];
        },
        toArray: function toArray() {
          return this.get();
        },
        size: function size() {
          return this.length;
        },
        remove: function remove() {
          return this.each(function () {
            if (this.parentNode != null) this.parentNode.removeChild(this);
          });
        },
        each: function each(callback) {
          emptyArray.every.call(this, function (el, idx) {
            return callback.call(el, idx, el) !== false;
          });
          return this;
        },
        filter: function filter(selector) {
          if (isFunction(selector)) return this.not(this.not(selector));
          return $(_filter.call(this, function (element) {
            return zepto.matches(element, selector);
          }));
        },
        add: function add(selector, context) {
          return $(uniq(this.concat($(selector, context))));
        },
        is: function is(selector) {
          return this.length > 0 && zepto.matches(this[0], selector);
        },
        not: function not(selector) {
          var nodes = [];
          if (isFunction(selector) && selector.call !== undefined) this.each(function (idx) {
            if (!selector.call(this, idx)) nodes.push(this);
          });else {
            var excludes = typeof selector == 'string' ? this.filter(selector) : likeArray(selector) && isFunction(selector.item) ? _slice.call(selector) : $(selector);
            this.forEach(function (el) {
              if (excludes.indexOf(el) < 0) nodes.push(el);
            });
          }
          return $(nodes);
        },
        has: function has(selector) {
          return this.filter(function () {
            return isObject(selector) ? $.contains(this, selector) : $(this).find(selector).size();
          });
        },
        eq: function eq(idx) {
          return idx === -1 ? this.slice(idx) : this.slice(idx, +idx + 1);
        },
        first: function first() {
          var el = this[0];
          return el && !isObject(el) ? el : $(el);
        },
        last: function last() {
          var el = this[this.length - 1];
          return el && !isObject(el) ? el : $(el);
        },
        find: function find(selector) {
          var result,
              $this = this;
          if (!selector) result = $();else if (typeof selector == 'object') result = $(selector).filter(function () {
            var node = this;
            return emptyArray.some.call($this, function (parent) {
              return $.contains(parent, node);
            });
          });else if (this.length == 1) result = $(zepto.qsa(this[0], selector));else result = this.map(function () {
            return zepto.qsa(this, selector);
          });
          return result;
        },
        closest: function closest(selector, context) {
          var nodes = [],
              collection = typeof selector == 'object' && $(selector);
          this.each(function (_, node) {
            while (node && !(collection ? collection.indexOf(node) >= 0 : zepto.matches(node, selector))) {
              node = node !== context && !isDocument(node) && node.parentNode;
            }

            if (node && nodes.indexOf(node) < 0) nodes.push(node);
          });
          return $(nodes);
        },
        parents: function parents(selector) {
          var ancestors = [],
              nodes = this;

          while (nodes.length > 0) {
            nodes = $.map(nodes, function (node) {
              if ((node = node.parentNode) && !isDocument(node) && ancestors.indexOf(node) < 0) {
                ancestors.push(node);
                return node;
              }
            });
          }

          return filtered(ancestors, selector);
        },
        parent: function parent(selector) {
          return filtered(uniq(this.pluck('parentNode')), selector);
        },
        children: function children(selector) {
          return filtered(this.map(function () {
            return _children(this);
          }), selector);
        },
        contents: function contents() {
          return this.map(function () {
            return this.contentDocument || _slice.call(this.childNodes);
          });
        },
        siblings: function siblings(selector) {
          return filtered(this.map(function (i, el) {
            return _filter.call(_children(el.parentNode), function (child) {
              return child !== el;
            });
          }), selector);
        },
        empty: function empty() {
          return this.each(function () {
            this.innerHTML = '';
          });
        },
        // `pluck` is borrowed from Prototype.js
        pluck: function pluck(property) {
          return $.map(this, function (el) {
            return el[property];
          });
        },
        show: function show() {
          return this.each(function () {
            this.style.display == "none" && (this.style.display = '');
            if (getComputedStyle(this, '').getPropertyValue("display") == "none") this.style.display = defaultDisplay(this.nodeName);
          });
        },
        replaceWith: function replaceWith(newContent) {
          return this.before(newContent).remove();
        },
        wrap: function wrap(structure) {
          var func = isFunction(structure);
          if (this[0] && !func) var dom = $(structure).get(0),
              clone = dom.parentNode || this.length > 1;
          return this.each(function (index) {
            $(this).wrapAll(func ? structure.call(this, index) : clone ? dom.cloneNode(true) : dom);
          });
        },
        wrapAll: function wrapAll(structure) {
          if (this[0]) {
            $(this[0]).before(structure = $(structure));
            var children; // drill down to the inmost element

            while ((children = structure.children()).length) {
              structure = children.first();
            }

            $(structure).append(this);
          }

          return this;
        },
        wrapInner: function wrapInner(structure) {
          var func = isFunction(structure);
          return this.each(function (index) {
            var self = $(this),
                contents = self.contents(),
                dom = func ? structure.call(this, index) : structure;
            contents.length ? contents.wrapAll(dom) : self.append(dom);
          });
        },
        unwrap: function unwrap() {
          this.parent().each(function () {
            $(this).replaceWith($(this).children());
          });
          return this;
        },
        clone: function clone() {
          return this.map(function () {
            return this.cloneNode(true);
          });
        },
        hide: function hide() {
          return this.css("display", "none");
        },
        toggle: function toggle(setting) {
          return this.each(function () {
            var el = $(this);
            (setting === undefined ? el.css("display") == "none" : setting) ? el.show() : el.hide();
          });
        },
        prev: function prev(selector) {
          return $(this.pluck('previousElementSibling')).filter(selector || '*');
        },
        next: function next(selector) {
          return $(this.pluck('nextElementSibling')).filter(selector || '*');
        },
        html: function html(_html) {
          return 0 in arguments ? this.each(function (idx) {
            var originHtml = this.innerHTML;
            $(this).empty().append(funcArg(this, _html, idx, originHtml));
          }) : 0 in this ? this[0].innerHTML : null;
        },
        text: function text(_text) {
          return 0 in arguments ? this.each(function (idx) {
            var newText = funcArg(this, _text, idx, this.textContent);
            this.textContent = newText == null ? '' : '' + newText;
          }) : 0 in this ? this.pluck('textContent').join("") : null;
        },
        attr: function attr(name, value) {
          var result;
          return typeof name == 'string' && !(1 in arguments) ? 0 in this && this[0].nodeType == 1 && (result = this[0].getAttribute(name)) != null ? result : undefined : this.each(function (idx) {
            if (this.nodeType !== 1) return;
            if (isObject(name)) for (key in name) {
              setAttribute(this, key, name[key]);
            } else setAttribute(this, name, funcArg(this, value, idx, this.getAttribute(name)));
          });
        },
        removeAttr: function removeAttr(name) {
          return this.each(function () {
            this.nodeType === 1 && name.split(' ').forEach(function (attribute) {
              setAttribute(this, attribute);
            }, this);
          });
        },
        prop: function prop(name, value) {
          name = propMap[name] || name;
          return 1 in arguments ? this.each(function (idx) {
            this[name] = funcArg(this, value, idx, this[name]);
          }) : this[0] && this[0][name];
        },
        removeProp: function removeProp(name) {
          name = propMap[name] || name;
          return this.each(function () {
            delete this[name];
          });
        },
        data: function data(name, value) {
          var attrName = 'data-' + name.replace(capitalRE, '-$1').toLowerCase();
          var data = 1 in arguments ? this.attr(attrName, value) : this.attr(attrName);
          return data !== null ? deserializeValue(data) : undefined;
        },
        val: function val(value) {
          if (0 in arguments) {
            if (value == null) value = "";
            return this.each(function (idx) {
              this.value = funcArg(this, value, idx, this.value);
            });
          } else {
            return this[0] && (this[0].multiple ? $(this[0]).find('option').filter(function () {
              return this.selected;
            }).pluck('value') : this[0].value);
          }
        },
        offset: function offset(coordinates) {
          if (coordinates) return this.each(function (index) {
            var $this = $(this),
                coords = funcArg(this, coordinates, index, $this.offset()),
                parentOffset = $this.offsetParent().offset(),
                props = {
              top: coords.top - parentOffset.top,
              left: coords.left - parentOffset.left
            };
            if ($this.css('position') == 'static') props['position'] = 'relative';
            $this.css(props);
          });
          if (!this.length) return null;
          if (document.documentElement !== this[0] && !$.contains(document.documentElement, this[0])) return {
            top: 0,
            left: 0
          };
          var obj = this[0].getBoundingClientRect();
          return {
            left: obj.left + window.pageXOffset,
            top: obj.top + window.pageYOffset,
            width: Math.round(obj.width),
            height: Math.round(obj.height)
          };
        },
        css: function css(property, value) {
          if (arguments.length < 2) {
            var element = this[0];

            if (typeof property == 'string') {
              if (!element) return;
              return element.style[camelize(property)] || getComputedStyle(element, '').getPropertyValue(property);
            } else if (isArray(property)) {
              if (!element) return;
              var props = {};
              var computedStyle = getComputedStyle(element, '');
              $.each(property, function (_, prop) {
                props[prop] = element.style[camelize(prop)] || computedStyle.getPropertyValue(prop);
              });
              return props;
            }
          }

          var css = '';

          if (type(property) == 'string') {
            if (!value && value !== 0) this.each(function () {
              this.style.removeProperty(dasherize(property));
            });else css = dasherize(property) + ":" + maybeAddPx(property, value);
          } else {
            for (key in property) {
              if (!property[key] && property[key] !== 0) this.each(function () {
                this.style.removeProperty(dasherize(key));
              });else css += dasherize(key) + ':' + maybeAddPx(key, property[key]) + ';';
            }
          }

          return this.each(function () {
            this.style.cssText += ';' + css;
          });
        },
        index: function index(element) {
          return element ? this.indexOf($(element)[0]) : this.parent().children().indexOf(this[0]);
        },
        hasClass: function hasClass(name) {
          if (!name) return false;
          return emptyArray.some.call(this, function (el) {
            return this.test(className(el));
          }, classRE(name));
        },
        addClass: function addClass(name) {
          if (!name) return this;
          return this.each(function (idx) {
            if (!('className' in this)) return;
            classList = [];
            var cls = className(this),
                newName = funcArg(this, name, idx, cls);
            newName.split(/\s+/g).forEach(function (klass) {
              if (!$(this).hasClass(klass)) classList.push(klass);
            }, this);
            classList.length && className(this, cls + (cls ? " " : "") + classList.join(" "));
          });
        },
        removeClass: function removeClass(name) {
          return this.each(function (idx) {
            if (!('className' in this)) return;
            if (name === undefined) return className(this, '');
            classList = className(this);
            funcArg(this, name, idx, classList).split(/\s+/g).forEach(function (klass) {
              classList = classList.replace(classRE(klass), " ");
            });
            className(this, classList.trim());
          });
        },
        toggleClass: function toggleClass(name, when) {
          if (!name) return this;
          return this.each(function (idx) {
            var $this = $(this),
                names = funcArg(this, name, idx, className(this));
            names.split(/\s+/g).forEach(function (klass) {
              (when === undefined ? !$this.hasClass(klass) : when) ? $this.addClass(klass) : $this.removeClass(klass);
            });
          });
        },
        scrollTop: function scrollTop(value) {
          if (!this.length) return;
          var hasScrollTop = 'scrollTop' in this[0];
          if (value === undefined) return hasScrollTop ? this[0].scrollTop : this[0].pageYOffset;
          return this.each(hasScrollTop ? function () {
            this.scrollTop = value;
          } : function () {
            this.scrollTo(this.scrollX, value);
          });
        },
        scrollLeft: function scrollLeft(value) {
          if (!this.length) return;
          var hasScrollLeft = 'scrollLeft' in this[0];
          if (value === undefined) return hasScrollLeft ? this[0].scrollLeft : this[0].pageXOffset;
          return this.each(hasScrollLeft ? function () {
            this.scrollLeft = value;
          } : function () {
            this.scrollTo(value, this.scrollY);
          });
        },
        position: function position() {
          if (!this.length) return;
          var elem = this[0],
              // Get *real* offsetParent
          offsetParent = this.offsetParent(),
              // Get correct offsets
          offset = this.offset(),
              parentOffset = rootNodeRE.test(offsetParent[0].nodeName) ? {
            top: 0,
            left: 0
          } : offsetParent.offset(); // Subtract element margins
          // note: when an element has margin: auto the offsetLeft and marginLeft
          // are the same in Safari causing offset.left to incorrectly be 0

          offset.top -= parseFloat($(elem).css('margin-top')) || 0;
          offset.left -= parseFloat($(elem).css('margin-left')) || 0; // Add offsetParent borders

          parentOffset.top += parseFloat($(offsetParent[0]).css('border-top-width')) || 0;
          parentOffset.left += parseFloat($(offsetParent[0]).css('border-left-width')) || 0; // Subtract the two offsets

          return {
            top: offset.top - parentOffset.top,
            left: offset.left - parentOffset.left
          };
        },
        offsetParent: function offsetParent() {
          return this.map(function () {
            var parent = this.offsetParent || document.body;

            while (parent && !rootNodeRE.test(parent.nodeName) && $(parent).css("position") == "static") {
              parent = parent.offsetParent;
            }

            return parent;
          });
        }
      }; // for now

      $.fn.detach = $.fn.remove // Generate the `width` and `height` functions
      ;
      ['width', 'height'].forEach(function (dimension) {
        var dimensionProperty = dimension.replace(/./, function (m) {
          return m[0].toUpperCase();
        });

        $.fn[dimension] = function (value) {
          var offset,
              el = this[0];
          if (value === undefined) return isWindow(el) ? el['inner' + dimensionProperty] : isDocument(el) ? el.documentElement['scroll' + dimensionProperty] : (offset = this.offset()) && offset[dimension];else return this.each(function (idx) {
            el = $(this);
            el.css(dimension, funcArg(this, value, idx, el[dimension]()));
          });
        };
      });

      function traverseNode(node, fun) {
        fun(node);

        for (var i = 0, len = node.childNodes.length; i < len; i++) {
          traverseNode(node.childNodes[i], fun);
        }
      } // Generate the `after`, `prepend`, `before`, `append`,
      // `insertAfter`, `insertBefore`, `appendTo`, and `prependTo` methods.


      adjacencyOperators.forEach(function (operator, operatorIndex) {
        var inside = operatorIndex % 2; //=> prepend, append

        $.fn[operator] = function () {
          // arguments can be nodes, arrays of nodes, Zepto objects and HTML strings
          var argType,
              nodes = $.map(arguments, function (arg) {
            var arr = [];
            argType = type(arg);

            if (argType == "array") {
              arg.forEach(function (el) {
                if (el.nodeType !== undefined) return arr.push(el);else if ($.zepto.isZ(el)) return arr = arr.concat(el.get());
                arr = arr.concat(zepto.fragment(el));
              });
              return arr;
            }

            return argType == "object" || arg == null ? arg : zepto.fragment(arg);
          }),
              parent,
              copyByClone = this.length > 1;
          if (nodes.length < 1) return this;
          return this.each(function (_, target) {
            parent = inside ? target : target.parentNode; // convert all methods to a "before" operation

            target = operatorIndex == 0 ? target.nextSibling : operatorIndex == 1 ? target.firstChild : operatorIndex == 2 ? target : null;
            var parentInDocument = $.contains(document.documentElement, parent);
            nodes.forEach(function (node) {
              if (copyByClone) node = node.cloneNode(true);else if (!parent) return $(node).remove();
              parent.insertBefore(node, target);
              if (parentInDocument) traverseNode(node, function (el) {
                if (el.nodeName != null && el.nodeName.toUpperCase() === 'SCRIPT' && (!el.type || el.type === 'text/javascript') && !el.src) {
                  var target = el.ownerDocument ? el.ownerDocument.defaultView : window;
                  target['eval'].call(target, el.innerHTML);
                }
              });
            });
          });
        }; // after    => insertAfter
        // prepend  => prependTo
        // before   => insertBefore
        // append   => appendTo


        $.fn[inside ? operator + 'To' : 'insert' + (operatorIndex ? 'Before' : 'After')] = function (html) {
          $(html)[operator](this);
          return this;
        };
      });
      zepto.Z.prototype = Z.prototype = $.fn; // Export internal API functions in the `$.zepto` namespace

      zepto.uniq = uniq;
      zepto.deserializeValue = deserializeValue;
      $.zepto = zepto;
      return $;
    }();

    window.Zepto = Zepto;
    window.$ === undefined && (window.$ = Zepto);

    (function ($) {
      var _zid = 1,
          undefined,
          slice = Array.prototype.slice,
          isFunction = $.isFunction,
          isString = function isString(obj) {
        return typeof obj == 'string';
      },
          handlers = {},
          specialEvents = {},
          focusinSupported = 'onfocusin' in window,
          focus = {
        focus: 'focusin',
        blur: 'focusout'
      },
          hover = {
        mouseenter: 'mouseover',
        mouseleave: 'mouseout'
      };

      specialEvents.click = specialEvents.mousedown = specialEvents.mouseup = specialEvents.mousemove = 'MouseEvents';

      function zid(element) {
        return element._zid || (element._zid = _zid++);
      }

      function findHandlers(element, event, fn, selector) {
        event = parse(event);
        if (event.ns) var matcher = matcherFor(event.ns);
        return (handlers[zid(element)] || []).filter(function (handler) {
          return handler && (!event.e || handler.e == event.e) && (!event.ns || matcher.test(handler.ns)) && (!fn || zid(handler.fn) === zid(fn)) && (!selector || handler.sel == selector);
        });
      }

      function parse(event) {
        var parts = ('' + event).split('.');
        return {
          e: parts[0],
          ns: parts.slice(1).sort().join(' ')
        };
      }

      function matcherFor(ns) {
        return new RegExp('(?:^| )' + ns.replace(' ', ' .* ?') + '(?: |$)');
      }

      function eventCapture(handler, captureSetting) {
        return handler.del && !focusinSupported && handler.e in focus || !!captureSetting;
      }

      function realEvent(type) {
        return hover[type] || focusinSupported && focus[type] || type;
      }

      function add(element, events, fn, data, selector, delegator, capture) {
        var id = zid(element),
            set = handlers[id] || (handlers[id] = []);
        events.split(/\s/).forEach(function (event) {
          if (event == 'ready') return $(document).ready(fn);
          var handler = parse(event);
          handler.fn = fn;
          handler.sel = selector; // emulate mouseenter, mouseleave

          if (handler.e in hover) fn = function fn(e) {
            var related = e.relatedTarget;
            if (!related || related !== this && !$.contains(this, related)) return handler.fn.apply(this, arguments);
          };
          handler.del = delegator;
          var callback = delegator || fn;

          handler.proxy = function (e) {
            e = compatible(e);
            if (e.isImmediatePropagationStopped()) return;
            e.data = data;
            var result = callback.apply(element, e._args == undefined ? [e] : [e].concat(e._args));
            if (result === false) e.preventDefault(), e.stopPropagation();
            return result;
          };

          handler.i = set.length;
          set.push(handler);
          if ('addEventListener' in element) element.addEventListener(realEvent(handler.e), handler.proxy, eventCapture(handler, capture));
        });
      }

      function remove(element, events, fn, selector, capture) {
        var id = zid(element);
        (events || '').split(/\s/).forEach(function (event) {
          findHandlers(element, event, fn, selector).forEach(function (handler) {
            delete handlers[id][handler.i];
            if ('removeEventListener' in element) element.removeEventListener(realEvent(handler.e), handler.proxy, eventCapture(handler, capture));
          });
        });
      }

      $.event = {
        add: add,
        remove: remove
      };

      $.proxy = function (fn, context) {
        var args = 2 in arguments && slice.call(arguments, 2);

        if (isFunction(fn)) {
          var proxyFn = function proxyFn() {
            return fn.apply(context, args ? args.concat(slice.call(arguments)) : arguments);
          };

          proxyFn._zid = zid(fn);
          return proxyFn;
        } else if (isString(context)) {
          if (args) {
            args.unshift(fn[context], fn);
            return $.proxy.apply(null, args);
          } else {
            return $.proxy(fn[context], fn);
          }
        } else {
          throw new TypeError("expected function");
        }
      };

      $.fn.bind = function (event, data, callback) {
        return this.on(event, data, callback);
      };

      $.fn.unbind = function (event, callback) {
        return this.off(event, callback);
      };

      $.fn.one = function (event, selector, data, callback) {
        return this.on(event, selector, data, callback, 1);
      };

      var returnTrue = function returnTrue() {
        return true;
      },
          returnFalse = function returnFalse() {
        return false;
      },
          ignoreProperties = /^([A-Z]|returnValue$|layer[XY]$|webkitMovement[XY]$)/,
          eventMethods = {
        preventDefault: 'isDefaultPrevented',
        stopImmediatePropagation: 'isImmediatePropagationStopped',
        stopPropagation: 'isPropagationStopped'
      };

      function compatible(event, source) {
        if (source || !event.isDefaultPrevented) {
          source || (source = event);
          $.each(eventMethods, function (name, predicate) {
            var sourceMethod = source[name];

            event[name] = function () {
              this[predicate] = returnTrue;
              return sourceMethod && sourceMethod.apply(source, arguments);
            };

            event[predicate] = returnFalse;
          });
          event.timeStamp || (event.timeStamp = Date.now());
          if (source.defaultPrevented !== undefined ? source.defaultPrevented : 'returnValue' in source ? source.returnValue === false : source.getPreventDefault && source.getPreventDefault()) event.isDefaultPrevented = returnTrue;
        }

        return event;
      }

      function createProxy(event) {
        var key,
            proxy = {
          originalEvent: event
        };

        for (key in event) {
          if (!ignoreProperties.test(key) && event[key] !== undefined) proxy[key] = event[key];
        }

        return compatible(proxy, event);
      }

      $.fn.delegate = function (selector, event, callback) {
        return this.on(event, selector, callback);
      };

      $.fn.undelegate = function (selector, event, callback) {
        return this.off(event, selector, callback);
      };

      $.fn.live = function (event, callback) {
        $(document.body).delegate(this.selector, event, callback);
        return this;
      };

      $.fn.die = function (event, callback) {
        $(document.body).undelegate(this.selector, event, callback);
        return this;
      };

      $.fn.on = function (event, selector, data, callback, one) {
        var autoRemove,
            delegator,
            $this = this;

        if (event && !isString(event)) {
          $.each(event, function (type, fn) {
            $this.on(type, selector, data, fn, one);
          });
          return $this;
        }

        if (!isString(selector) && !isFunction(callback) && callback !== false) callback = data, data = selector, selector = undefined;
        if (callback === undefined || data === false) callback = data, data = undefined;
        if (callback === false) callback = returnFalse;
        return $this.each(function (_, element) {
          if (one) autoRemove = function autoRemove(e) {
            remove(element, e.type, callback);
            return callback.apply(this, arguments);
          };
          if (selector) delegator = function delegator(e) {
            var evt,
                match = $(e.target).closest(selector, element).get(0);

            if (match && match !== element) {
              evt = $.extend(createProxy(e), {
                currentTarget: match,
                liveFired: element
              });
              return (autoRemove || callback).apply(match, [evt].concat(slice.call(arguments, 1)));
            }
          };
          add(element, event, callback, data, selector, delegator || autoRemove);
        });
      };

      $.fn.off = function (event, selector, callback) {
        var $this = this;

        if (event && !isString(event)) {
          $.each(event, function (type, fn) {
            $this.off(type, selector, fn);
          });
          return $this;
        }

        if (!isString(selector) && !isFunction(callback) && callback !== false) callback = selector, selector = undefined;
        if (callback === false) callback = returnFalse;
        return $this.each(function () {
          remove(this, event, callback, selector);
        });
      };

      $.fn.trigger = function (event, args) {
        event = isString(event) || $.isPlainObject(event) ? $.Event(event) : compatible(event);
        event._args = args;
        return this.each(function () {
          // handle focus(), blur() by calling them directly
          if (event.type in focus && typeof this[event.type] == "function") this[event.type](); // items in the collection might not be DOM elements
          else if ('dispatchEvent' in this) this.dispatchEvent(event);else $(this).triggerHandler(event, args);
        });
      }; // triggers event handlers on current element just as if an event occurred,
      // doesn't trigger an actual event, doesn't bubble


      $.fn.triggerHandler = function (event, args) {
        var e, result;
        this.each(function (i, element) {
          e = createProxy(isString(event) ? $.Event(event) : event);
          e._args = args;
          e.target = element;
          $.each(findHandlers(element, event.type || event), function (i, handler) {
            result = handler.proxy(e);
            if (e.isImmediatePropagationStopped()) return false;
          });
        });
        return result;
      } // shortcut methods for `.bind(event, fn)` for each event type
      ;

      ('focusin focusout focus blur load resize scroll unload click dblclick ' + 'mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave ' + 'change select keydown keypress keyup error').split(' ').forEach(function (event) {
        $.fn[event] = function (callback) {
          return 0 in arguments ? this.bind(event, callback) : this.trigger(event);
        };
      });

      $.Event = function (type, props) {
        if (!isString(type)) props = type, type = props.type;
        var event = document.createEvent(specialEvents[type] || 'Events'),
            bubbles = true;
        if (props) for (var name in props) {
          name == 'bubbles' ? bubbles = !!props[name] : event[name] = props[name];
        }
        event.initEvent(type, bubbles, true);
        return compatible(event);
      };
    })(Zepto);

    (function ($) {
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
          originAnchor = document.createElement('a');
      originAnchor.href = window.location.href; // trigger a custom event and return false if it was cancelled

      function triggerAndReturn(context, eventName, data) {
        var event = $.Event(eventName);
        $(context).trigger(event, data);
        return !event.isDefaultPrevented();
      } // trigger an Ajax "global" event


      function triggerGlobal(settings, context, eventName, data) {
        if (settings.global) return triggerAndReturn(context || document, eventName, data);
      } // Number of active Ajax requests


      $.active = 0;

      function ajaxStart(settings) {
        if (settings.global && $.active++ === 0) triggerGlobal(settings, null, 'ajaxStart');
      }

      function ajaxStop(settings) {
        if (settings.global && ! --$.active) triggerGlobal(settings, null, 'ajaxStop');
      } // triggers an extra global event "ajaxBeforeSend" that's like "ajaxSend" but cancelable


      function ajaxBeforeSend(xhr, settings) {
        var context = settings.context;
        if (settings.beforeSend.call(context, xhr, settings) === false || triggerGlobal(settings, context, 'ajaxBeforeSend', [xhr, settings]) === false) return false;
        triggerGlobal(settings, context, 'ajaxSend', [xhr, settings]);
      }

      function ajaxSuccess(data, xhr, settings, deferred) {
        var context = settings.context,
            status = 'success';
        settings.success.call(context, data, status, xhr);
        if (deferred) deferred.resolveWith(context, [data, status, xhr]);
        triggerGlobal(settings, context, 'ajaxSuccess', [xhr, settings, data]);
        ajaxComplete(status, xhr, settings);
      } // type: "timeout", "error", "abort", "parsererror"


      function ajaxError(error, type, xhr, settings, deferred) {
        var context = settings.context;
        settings.error.call(context, xhr, type, error);
        if (deferred) deferred.rejectWith(context, [xhr, type, error]);
        triggerGlobal(settings, context, 'ajaxError', [xhr, settings, error || type]);
        ajaxComplete(type, xhr, settings);
      } // status: "success", "notmodified", "error", "timeout", "abort", "parsererror"


      function ajaxComplete(status, xhr, settings) {
        var context = settings.context;
        settings.complete.call(context, xhr, status);
        triggerGlobal(settings, context, 'ajaxComplete', [xhr, settings]);
        ajaxStop(settings);
      }

      function ajaxDataFilter(data, type, settings) {
        if (settings.dataFilter == empty) return data;
        var context = settings.context;
        return settings.dataFilter.call(context, data, type);
      } // Empty function, used as default callback


      function empty() {}

      $.ajaxJSONP = function (options, deferred) {
        if (!('type' in options)) return $.ajax(options);

        var _callbackName = options.jsonpCallback,
            callbackName = ($.isFunction(_callbackName) ? _callbackName() : _callbackName) || 'Zepto' + jsonpID++,
            script = document.createElement('script'),
            originalCallback = window[callbackName],
            responseData,
            abort = function abort(errorType) {
          $(script).triggerHandler('error', errorType || 'abort');
        },
            xhr = {
          abort: abort
        },
            abortTimeout;

        if (deferred) deferred.promise(xhr);
        $(script).on('load error', function (e, errorType) {
          clearTimeout(abortTimeout);
          $(script).off().remove();

          if (e.type == 'error' || !responseData) {
            ajaxError(null, errorType || 'error', xhr, options, deferred);
          } else {
            ajaxSuccess(responseData[0], xhr, options, deferred);
          }

          window[callbackName] = originalCallback;
          if (responseData && $.isFunction(originalCallback)) originalCallback(responseData[0]);
          originalCallback = responseData = undefined;
        });

        if (ajaxBeforeSend(xhr, options) === false) {
          abort('abort');
          return xhr;
        }

        window[callbackName] = function () {
          responseData = arguments;
        };

        script.src = options.url.replace(/\?(.+)=\?/, '?$1=' + callbackName);
        document.head.appendChild(script);
        if (options.timeout > 0) abortTimeout = setTimeout(function () {
          abort('timeout');
        }, options.timeout);
        return xhr;
      };

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
        xhr: function xhr() {
          return new window.XMLHttpRequest();
        },
        // MIME types mapping
        // IIS returns Javascript as "application/x-javascript"
        accepts: {
          script: 'text/javascript, application/javascript, application/x-javascript',
          json: jsonType,
          xml: 'application/xml, text/xml',
          html: htmlType,
          text: 'text/plain'
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
      };

      function mimeToDataType(mime) {
        if (mime) mime = mime.split(';', 2)[0];
        return mime && (mime == htmlType ? 'html' : mime == jsonType ? 'json' : scriptTypeRE.test(mime) ? 'script' : xmlTypeRE.test(mime) && 'xml') || 'text';
      }

      function appendQuery(url, query) {
        if (query == '') return url;
        return (url + '&' + query).replace(/[&?]{1,2}/, '?');
      } // serialize payload and append it to the URL for GET requests


      function serializeData(options) {
        if (options.processData && options.data && $.type(options.data) != "string") options.data = $.param(options.data, options.traditional);
        if (options.data && (!options.type || options.type.toUpperCase() == 'GET' || 'jsonp' == options.dataType)) options.url = appendQuery(options.url, options.data), options.data = undefined;
      }

      $.ajax = function (options) {
        var settings = $.extend({}, options || {}),
            deferred = $.Deferred && $.Deferred(),
            urlAnchor,
            hashIndex;

        for (key in $.ajaxSettings) {
          if (settings[key] === undefined) settings[key] = $.ajaxSettings[key];
        }

        ajaxStart(settings);

        if (!settings.crossDomain) {
          urlAnchor = document.createElement('a');
          urlAnchor.href = settings.url; // cleans up URL for .href (IE only), see https://github.com/madrobby/zepto/pull/1049

          urlAnchor.href = urlAnchor.href;
          settings.crossDomain = originAnchor.protocol + '//' + originAnchor.host !== urlAnchor.protocol + '//' + urlAnchor.host;
        }

        if (!settings.url) settings.url = window.location.toString();
        if ((hashIndex = settings.url.indexOf('#')) > -1) settings.url = settings.url.slice(0, hashIndex);
        serializeData(settings);
        var dataType = settings.dataType,
            hasPlaceholder = /\?.+=\?/.test(settings.url);
        if (hasPlaceholder) dataType = 'jsonp';
        if (settings.cache === false || (!options || options.cache !== true) && ('script' == dataType || 'jsonp' == dataType)) settings.url = appendQuery(settings.url, '_=' + Date.now());

        if ('jsonp' == dataType) {
          if (!hasPlaceholder) settings.url = appendQuery(settings.url, settings.jsonp ? settings.jsonp + '=?' : settings.jsonp === false ? '' : 'callback=?');
          return $.ajaxJSONP(settings, deferred);
        }

        var mime = settings.accepts[dataType],
            headers = {},
            setHeader = function setHeader(name, value) {
          headers[name.toLowerCase()] = [name, value];
        },
            protocol = /^([\w-]+:)\/\//.test(settings.url) ? RegExp.$1 : window.location.protocol,
            xhr = settings.xhr(),
            nativeSetHeader = xhr.setRequestHeader,
            abortTimeout;

        if (deferred) deferred.promise(xhr);
        if (!settings.crossDomain) setHeader('X-Requested-With', 'XMLHttpRequest');
        setHeader('Accept', mime || '*/*');

        if (mime = settings.mimeType || mime) {
          if (mime.indexOf(',') > -1) mime = mime.split(',', 2)[0];
          xhr.overrideMimeType && xhr.overrideMimeType(mime);
        }

        if (settings.contentType || settings.contentType !== false && settings.data && settings.type.toUpperCase() != 'GET') setHeader('Content-Type', settings.contentType || 'application/x-www-form-urlencoded');
        if (settings.headers) for (name in settings.headers) {
          setHeader(name, settings.headers[name]);
        }
        xhr.setRequestHeader = setHeader;

        xhr.onreadystatechange = function () {
          if (xhr.readyState == 4) {
            xhr.onreadystatechange = empty;
            clearTimeout(abortTimeout);
            var result,
                error = false;

            if (xhr.status >= 200 && xhr.status < 300 || xhr.status == 304 || xhr.status == 0 && protocol == 'file:') {
              dataType = dataType || mimeToDataType(settings.mimeType || xhr.getResponseHeader('content-type'));
              if (xhr.responseType == 'arraybuffer' || xhr.responseType == 'blob') result = xhr.response;else {
                result = xhr.responseText;

                try {
                  // http://perfectionkills.com/global-eval-what-are-the-options/
                  // sanitize response accordingly if data filter callback provided
                  result = ajaxDataFilter(result, dataType, settings);
                  if (dataType == 'script') (1, eval)(result);else if (dataType == 'xml') result = xhr.responseXML;else if (dataType == 'json') result = blankRE.test(result) ? null : $.parseJSON(result);
                } catch (e) {
                  error = e;
                }

                if (error) return ajaxError(error, 'parsererror', xhr, settings, deferred);
              }
              ajaxSuccess(result, xhr, settings, deferred);
            } else {
              ajaxError(xhr.statusText || null, xhr.status ? 'error' : 'abort', xhr, settings, deferred);
            }
          }
        };

        if (ajaxBeforeSend(xhr, settings) === false) {
          xhr.abort();
          ajaxError(null, 'abort', xhr, settings, deferred);
          return xhr;
        }

        var async = 'async' in settings ? settings.async : true;
        xhr.open(settings.type, settings.url, async, settings.username, settings.password);
        if (settings.xhrFields) for (name in settings.xhrFields) {
          xhr[name] = settings.xhrFields[name];
        }

        for (name in headers) {
          nativeSetHeader.apply(xhr, headers[name]);
        }

        if (settings.timeout > 0) abortTimeout = setTimeout(function () {
          xhr.onreadystatechange = empty;
          xhr.abort();
          ajaxError(null, 'timeout', xhr, settings, deferred);
        }, settings.timeout); // avoid sending empty string (#319)

        xhr.send(settings.data ? settings.data : null);
        return xhr;
      }; // handle optional data/success arguments


      function parseArguments(url, data, success, dataType) {
        if ($.isFunction(data)) dataType = success, success = data, data = undefined;
        if (!$.isFunction(success)) dataType = success, success = undefined;
        return {
          url: url,
          data: data,
          success: success,
          dataType: dataType
        };
      }

      $.get = function ()
      /* url, data, success, dataType */
      {
        return $.ajax(parseArguments.apply(null, arguments));
      };

      $.post = function ()
      /* url, data, success, dataType */
      {
        var options = parseArguments.apply(null, arguments);
        options.type = 'POST';
        return $.ajax(options);
      };

      $.getJSON = function ()
      /* url, data, success */
      {
        var options = parseArguments.apply(null, arguments);
        options.dataType = 'json';
        return $.ajax(options);
      };

      $.fn.load = function (url, data, success) {
        if (!this.length) return this;
        var self = this,
            parts = url.split(/\s/),
            selector,
            options = parseArguments(url, data, success),
            callback = options.success;
        if (parts.length > 1) options.url = parts[0], selector = parts[1];

        options.success = function (response) {
          self.html(selector ? $('<div>').html(response.replace(rscript, "")).find(selector) : response);
          callback && callback.apply(self, arguments);
        };

        $.ajax(options);
        return this;
      };

      var escape = encodeURIComponent;

      function serialize(params, obj, traditional, scope) {
        var type,
            array = $.isArray(obj),
            hash = $.isPlainObject(obj);
        $.each(obj, function (key, value) {
          type = $.type(value);
          if (scope) key = traditional ? scope : scope + '[' + (hash || type == 'object' || type == 'array' ? key : '') + ']'; // handle data in serializeArray() format

          if (!scope && array) params.add(value.name, value.value); // recurse into nested objects
          else if (type == "array" || !traditional && type == "object") serialize(params, value, traditional, key);else params.add(key, value);
        });
      }

      $.param = function (obj, traditional) {
        var params = [];

        params.add = function (key, value) {
          if ($.isFunction(value)) value = value();
          if (value == null) value = "";
          this.push(escape(key) + '=' + escape(value));
        };

        serialize(params, obj, traditional);
        return params.join('&').replace(/%20/g, '+');
      };
    })(Zepto);

    (function ($) {
      $.fn.serializeArray = function () {
        var name,
            type,
            result = [],
            add = function add(value) {
          if (value.forEach) return value.forEach(add);
          result.push({
            name: name,
            value: value
          });
        };

        if (this[0]) $.each(this[0].elements, function (_, field) {
          type = field.type, name = field.name;
          if (name && field.nodeName.toLowerCase() != 'fieldset' && !field.disabled && type != 'submit' && type != 'reset' && type != 'button' && type != 'file' && (type != 'radio' && type != 'checkbox' || field.checked)) add($(field).val());
        });
        return result;
      };

      $.fn.serialize = function () {
        var result = [];
        this.serializeArray().forEach(function (elm) {
          result.push(encodeURIComponent(elm.name) + '=' + encodeURIComponent(elm.value));
        });
        return result.join('&');
      };

      $.fn.submit = function (callback) {
        if (0 in arguments) this.bind('submit', callback);else if (this.length) {
          var event = $.Event('submit');
          this.eq(0).trigger(event);
          if (!event.isDefaultPrevented()) this.get(0).submit();
        }
        return this;
      };
    })(Zepto);

    (function () {
      // getComputedStyle shouldn't freak out when called
      // without a valid element as argument
      try {
        getComputedStyle(undefined);
      } catch (e) {
        var nativeGetComputedStyle = getComputedStyle;

        window.getComputedStyle = function (element, pseudoElement) {
          try {
            return nativeGetComputedStyle(element, pseudoElement);
          } catch (e) {
            return null;
          }
        };
      }
    })();

    return Zepto;
  });
}).call(window);

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/m.attrs.bidi/bidi.js":
/*!********************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./node_modules/m.attrs.bidi/bidi.js ***!
  \********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;(function _package(factory) {
  if (true) {
    !(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! mithril */ "mithril")], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  } else {}
})(function define(m) {
  function bidi(node, prop) {
    var type = node.tag === 'select' ? node.attrs.multi ? 'multi' : 'select' : node.attrs.type; // Setup: bind listeners

    if (type === 'multi') {
      node.attrs.onchange = function () {
        prop([].slice.call(this.selectedOptions, function (x) {
          return x.value;
        }));
      };
    } else if (type === 'select') {
      node.attrs.onchange = function (e) {
        prop(this.selectedOptions[0].value);
      };
    } else if (type === 'checkbox') {
      node.attrs.onchange = function (e) {
        prop(this.checked);
      };
    } else {
      node.attrs.onchange = node.attrs.oninput = function (e) {
        prop(this.value);
      };
    }

    if (node.tag === 'select') {
      node.children.forEach(function (option) {
        if (option.attrs.value === prop() || option.children[0] === prop()) {
          option.attrs.selected = true;
        }
      });
    } else if (type === 'checkbox') {
      node.attrs.checked = prop();
    } else if (type === 'radio') {
      node.attrs.checked = prop() === node.attrs.value;
    } else {
      node.attrs.value = prop();
    }

    return node;
  }

  bidi.view = function (ctrl, node, prop) {
    return bidi(node, node.attrs.bidi);
  };

  if (m.attrs) m.attrs.bidi = bidi;
  m.bidi = bidi;
  return bidi;
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/mithril/index.js":
/*!****************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./node_modules/mithril/index.js ***!
  \****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var hyperscript = __webpack_require__(/*! ./hyperscript */ "./node_modules/mithril/hyperscript.js");

var request = __webpack_require__(/*! ./request */ "./node_modules/mithril/request.js");

var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js");

var m = function m() {
  return hyperscript.apply(this, arguments);
};

m.m = hyperscript;
m.trust = hyperscript.trust;
m.fragment = hyperscript.fragment;
m.mount = mountRedraw.mount;
m.route = __webpack_require__(/*! ./route */ "./node_modules/mithril/route.js");
m.render = __webpack_require__(/*! ./render */ "./node_modules/mithril/render.js");
m.redraw = mountRedraw.redraw;
m.request = request.request;
m.jsonp = request.jsonp;
m.parseQueryString = __webpack_require__(/*! ./querystring/parse */ "./node_modules/mithril/querystring/parse.js");
m.buildQueryString = __webpack_require__(/*! ./querystring/build */ "./node_modules/mithril/querystring/build.js");
m.parsePathname = __webpack_require__(/*! ./pathname/parse */ "./node_modules/mithril/pathname/parse.js");
m.buildPathname = __webpack_require__(/*! ./pathname/build */ "./node_modules/mithril/pathname/build.js");
m.vnode = __webpack_require__(/*! ./render/vnode */ "./node_modules/mithril/render/vnode.js");
m.PromisePolyfill = __webpack_require__(/*! ./promise/polyfill */ "./node_modules/mithril/promise/polyfill.js");
module.exports = m;

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/mousetrap/mousetrap.js":
/*!**********************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./node_modules/mousetrap/mousetrap.js ***!
  \**********************************************************************************************************************/
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
 * @version 1.6.3
 * @url craig.is/killing/mice
 */
(function (window, document, undefined) {
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
    111: '/',
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
      var character = String.fromCharCode(e.which); // if the shift key is not pressed then it is safe to assume
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
    } // for non keypress events the special maps are needed


    if (_MAP[e.which]) {
      return _MAP[e.which];
    }

    if (_KEYCODE_MAP[e.which]) {
      return _KEYCODE_MAP[e.which];
    } // if it is not in the special map
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
    } // modifier keys don't work as expected with keypress,
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
    var modifiers = []; // take the keys from this pattern and figure out what the actual
    // pattern is all about

    keys = _keysFromString(combination);

    for (i = 0; i < keys.length; ++i) {
      key = keys[i]; // normalize key names

      if (_SPECIAL_ALIASES[key]) {
        key = _SPECIAL_ALIASES[key];
      } // if this is not a keypress event then we should
      // be smart about using shift keys
      // this will only work for US keyboards however


      if (action && action != 'keypress' && _SHIFT_MAP[key]) {
        key = _SHIFT_MAP[key];
        modifiers.push('shift');
      } // if this key is a modifier then add it to the list of modifiers


      if (_isModifier(key)) {
        modifiers.push(key);
      }
    } // depending on what the key combination is
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
      var action = e.type; // if there are no events related to this keycode

      if (!self._callbacks[character]) {
        return [];
      } // if a modifier key is coming up on its own we should allow it


      if (action == 'keyup' && _isModifier(character)) {
        modifiers = [character];
      } // loop through all callbacks for the key that was pressed
      // and see if any of them match


      for (i = 0; i < self._callbacks[character].length; ++i) {
        callback = self._callbacks[character][i]; // if a sequence name is not specified, but this is a sequence at
        // the wrong level then move onto the next match

        if (!sequenceName && callback.seq && _sequenceLevels[callback.seq] != callback.level) {
          continue;
        } // if the action we are looking for doesn't match the action we got
        // then we should keep going


        if (action != callback.action) {
          continue;
        } // if this is a keypress event and the meta key and control key
        // are not pressed that means that we need to only look at the
        // character, otherwise check the modifiers as well
        //
        // chrome will not fire a keypress if meta or control is down
        // safari will fire a keypress if meta or meta+shift is down
        // firefox will fire a keypress if meta or control is down


        if (action == 'keypress' && !e.metaKey && !e.ctrlKey || _modifiersMatch(modifiers, callback.modifiers)) {
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


    self._handleKey = function (character, modifiers, e) {
      var callbacks = _getMatches(character, modifiers, e);

      var i;
      var doNotReset = {};
      var maxLevel = 0;
      var processedSequenceCallback = false; // Calculate the maxLevel for sequences so we can only execute the longest callback sequence

      for (i = 0; i < callbacks.length; ++i) {
        if (callbacks[i].seq) {
          maxLevel = Math.max(maxLevel, callbacks[i].level);
        }
      } // loop through matching callbacks for this key event


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

          processedSequenceCallback = true; // keep a list of which sequences were matches for later

          doNotReset[callbacks[i].seq] = 1;

          _fireCallback(callbacks[i].callback, e, callbacks[i].combo, callbacks[i].seq);

          continue;
        } // if there were no sequence matches but we are still here
        // that means this is a regular match so we should fire that


        if (!processedSequenceCallback) {
          _fireCallback(callbacks[i].callback, e, callbacks[i].combo);
        }
      } // if the key you pressed matches the type of sequence without
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

      var character = _characterFromEvent(e); // no character found then stop


      if (!character) {
        return;
      } // need to use === for the character check because the character can be 0


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
        return function () {
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
        _fireCallback(callback, e, combo); // we should ignore the next key up if the action is key down
        // or keypress.  this is so if you finish a sequence and
        // release the key the final key will not trigger a keyup


        if (action !== 'keyup') {
          _ignoreNextKeyup = _characterFromEvent(e);
        } // weird race condition if a sequence ends with the key
        // another sequence begins with


        setTimeout(_resetSequences, 10);
      } // loop through keys one at a time and bind the appropriate callback
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
      self._directMap[combination + ':' + action] = callback; // make sure multiple spaces in a row become a single space

      combination = combination.replace(/\s+/g, ' ');
      var sequence = combination.split(' ');
      var info; // if this pattern is a sequence of keys then run through this method
      // to reprocess each pattern one key at a time

      if (sequence.length > 1) {
        _bindSequence(combination, sequence, callback, action);

        return;
      }

      info = _getKeyInfo(combination, action); // make sure to initialize array if this is the first time
      // a callback is added for this key

      self._callbacks[info.key] = self._callbacks[info.key] || []; // remove an existing match if there is one

      _getMatches(info.key, info.modifiers, {
        type: info.action
      }, sequenceName, combination, level); // add this call back to the array
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


    self._bindMultiple = function (combinations, callback, action) {
      for (var i = 0; i < combinations.length; ++i) {
        _bindSingle(combinations[i], callback, action);
      }
    }; // start!


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


  Mousetrap.prototype.bind = function (keys, callback, action) {
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


  Mousetrap.prototype.unbind = function (keys, action) {
    var self = this;
    return self.bind.call(self, keys, function () {}, action);
  };
  /**
   * triggers an event that has already been bound
   *
   * @param {string} keys
   * @param {string=} action
   * @returns void
   */


  Mousetrap.prototype.trigger = function (keys, action) {
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


  Mousetrap.prototype.reset = function () {
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


  Mousetrap.prototype.stopCallback = function (e, element) {
    var self = this; // if the element has the class "mousetrap" then no need to stop

    if ((' ' + element.className + ' ').indexOf(' mousetrap ') > -1) {
      return false;
    }

    if (_belongsTo(element, self.target)) {
      return false;
    } // Events originating from a shadow DOM are re-targetted and `e.target` is the shadow host,
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
    } // stop for input, select, and textarea


    return element.tagName == 'INPUT' || element.tagName == 'SELECT' || element.tagName == 'TEXTAREA' || element.isContentEditable;
  };
  /**
   * exposes _handleKey publicly so it can be overwritten by extensions
   */


  Mousetrap.prototype.handleKey = function () {
    var self = this;
    return self._handleKey.apply(self, arguments);
  };
  /**
   * allow custom key mappings
   */


  Mousetrap.addKeycodes = function (object) {
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


  Mousetrap.init = function () {
    var documentMousetrap = Mousetrap(document);

    for (var method in documentMousetrap) {
      if (method.charAt(0) !== '_') {
        Mousetrap[method] = function (method) {
          return function () {
            return documentMousetrap[method].apply(documentMousetrap, arguments);
          };
        }(method);
      }
    }
  };

  Mousetrap.init(); // expose mousetrap to the global object

  window.Mousetrap = Mousetrap; // expose as a common js module

  if ( true && module.exports) {
    module.exports = Mousetrap;
  } // expose mousetrap as an AMD module


  if (true) {
    !(__WEBPACK_AMD_DEFINE_RESULT__ = (function () {
      return Mousetrap;
    }).call(exports, __webpack_require__, exports, module),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
  }
})(typeof window !== 'undefined' ? window : null, typeof window !== 'undefined' ? document : null);

/***/ }),

/***/ "./node_modules/dayjs/dayjs.min.js":
/*!*****************************************!*\
  !*** ./node_modules/dayjs/dayjs.min.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function (t, n) {
   true ? module.exports = n() : undefined;
}(this, function () {
  "use strict";

  var t = "millisecond",
      n = "second",
      e = "minute",
      r = "hour",
      i = "day",
      s = "week",
      u = "month",
      a = "quarter",
      o = "year",
      h = /^(\d{4})-?(\d{1,2})-?(\d{0,2})[^0-9]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?.?(\d{1,3})?$/,
      f = /\[([^\]]+)]|Y{2,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g,
      c = function c(t, n, e) {
    var r = String(t);
    return !r || r.length >= n ? t : "" + Array(n + 1 - r.length).join(e) + t;
  },
      d = {
    s: c,
    z: function z(t) {
      var n = -t.utcOffset(),
          e = Math.abs(n),
          r = Math.floor(e / 60),
          i = e % 60;
      return (n <= 0 ? "+" : "-") + c(r, 2, "0") + ":" + c(i, 2, "0");
    },
    m: function m(t, n) {
      var e = 12 * (n.year() - t.year()) + (n.month() - t.month()),
          r = t.clone().add(e, u),
          i = n - r < 0,
          s = t.clone().add(e + (i ? -1 : 1), u);
      return Number(-(e + (n - r) / (i ? r - s : s - r)) || 0);
    },
    a: function a(t) {
      return t < 0 ? Math.ceil(t) || 0 : Math.floor(t);
    },
    p: function p(h) {
      return {
        M: u,
        y: o,
        w: s,
        d: i,
        h: r,
        m: e,
        s: n,
        ms: t,
        Q: a
      }[h] || String(h || "").toLowerCase().replace(/s$/, "");
    },
    u: function u(t) {
      return void 0 === t;
    }
  },
      $ = {
    name: "en",
    weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),
    months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_")
  },
      l = "en",
      m = {};

  m[l] = $;

  var y = function y(t) {
    return t instanceof v;
  },
      M = function M(t, n, e) {
    var r;
    if (!t) return l;
    if ("string" == typeof t) m[t] && (r = t), n && (m[t] = n, r = t);else {
      var i = t.name;
      m[i] = t, r = i;
    }
    return e || (l = r), r;
  },
      g = function g(t, n, e) {
    if (y(t)) return t.clone();
    var r = n ? "string" == typeof n ? {
      format: n,
      pl: e
    } : n : {};
    return r.date = t, new v(r);
  },
      D = d;

  D.l = M, D.i = y, D.w = function (t, n) {
    return g(t, {
      locale: n.$L,
      utc: n.$u
    });
  };

  var v = function () {
    function c(t) {
      this.$L = this.$L || M(t.locale, null, !0), this.parse(t);
    }

    var d = c.prototype;
    return d.parse = function (t) {
      this.$d = function (t) {
        var n = t.date,
            e = t.utc;
        if (null === n) return new Date(NaN);
        if (D.u(n)) return new Date();
        if (n instanceof Date) return new Date(n);

        if ("string" == typeof n && !/Z$/i.test(n)) {
          var r = n.match(h);
          if (r) return e ? new Date(Date.UTC(r[1], r[2] - 1, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, r[7] || 0)) : new Date(r[1], r[2] - 1, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, r[7] || 0);
        }

        return new Date(n);
      }(t), this.init();
    }, d.init = function () {
      var t = this.$d;
      this.$y = t.getFullYear(), this.$M = t.getMonth(), this.$D = t.getDate(), this.$W = t.getDay(), this.$H = t.getHours(), this.$m = t.getMinutes(), this.$s = t.getSeconds(), this.$ms = t.getMilliseconds();
    }, d.$utils = function () {
      return D;
    }, d.isValid = function () {
      return !("Invalid Date" === this.$d.toString());
    }, d.isSame = function (t, n) {
      var e = g(t);
      return this.startOf(n) <= e && e <= this.endOf(n);
    }, d.isAfter = function (t, n) {
      return g(t) < this.startOf(n);
    }, d.isBefore = function (t, n) {
      return this.endOf(n) < g(t);
    }, d.$g = function (t, n, e) {
      return D.u(t) ? this[n] : this.set(e, t);
    }, d.year = function (t) {
      return this.$g(t, "$y", o);
    }, d.month = function (t) {
      return this.$g(t, "$M", u);
    }, d.day = function (t) {
      return this.$g(t, "$W", i);
    }, d.date = function (t) {
      return this.$g(t, "$D", "date");
    }, d.hour = function (t) {
      return this.$g(t, "$H", r);
    }, d.minute = function (t) {
      return this.$g(t, "$m", e);
    }, d.second = function (t) {
      return this.$g(t, "$s", n);
    }, d.millisecond = function (n) {
      return this.$g(n, "$ms", t);
    }, d.unix = function () {
      return Math.floor(this.valueOf() / 1e3);
    }, d.valueOf = function () {
      return this.$d.getTime();
    }, d.startOf = function (t, a) {
      var h = this,
          f = !!D.u(a) || a,
          c = D.p(t),
          d = function d(t, n) {
        var e = D.w(h.$u ? Date.UTC(h.$y, n, t) : new Date(h.$y, n, t), h);
        return f ? e : e.endOf(i);
      },
          $ = function $(t, n) {
        return D.w(h.toDate()[t].apply(h.toDate(), (f ? [0, 0, 0, 0] : [23, 59, 59, 999]).slice(n)), h);
      },
          l = this.$W,
          m = this.$M,
          y = this.$D,
          M = "set" + (this.$u ? "UTC" : "");

      switch (c) {
        case o:
          return f ? d(1, 0) : d(31, 11);

        case u:
          return f ? d(1, m) : d(0, m + 1);

        case s:
          var g = this.$locale().weekStart || 0,
              v = (l < g ? l + 7 : l) - g;
          return d(f ? y - v : y + (6 - v), m);

        case i:
        case "date":
          return $(M + "Hours", 0);

        case r:
          return $(M + "Minutes", 1);

        case e:
          return $(M + "Seconds", 2);

        case n:
          return $(M + "Milliseconds", 3);

        default:
          return this.clone();
      }
    }, d.endOf = function (t) {
      return this.startOf(t, !1);
    }, d.$set = function (s, a) {
      var h,
          f = D.p(s),
          c = "set" + (this.$u ? "UTC" : ""),
          d = (h = {}, h[i] = c + "Date", h.date = c + "Date", h[u] = c + "Month", h[o] = c + "FullYear", h[r] = c + "Hours", h[e] = c + "Minutes", h[n] = c + "Seconds", h[t] = c + "Milliseconds", h)[f],
          $ = f === i ? this.$D + (a - this.$W) : a;

      if (f === u || f === o) {
        var l = this.clone().set("date", 1);
        l.$d[d]($), l.init(), this.$d = l.set("date", Math.min(this.$D, l.daysInMonth())).toDate();
      } else d && this.$d[d]($);

      return this.init(), this;
    }, d.set = function (t, n) {
      return this.clone().$set(t, n);
    }, d.get = function (t) {
      return this[D.p(t)]();
    }, d.add = function (t, a) {
      var h,
          f = this;
      t = Number(t);

      var c = D.p(a),
          d = function d(n) {
        var e = g(f);
        return D.w(e.date(e.date() + Math.round(n * t)), f);
      };

      if (c === u) return this.set(u, this.$M + t);
      if (c === o) return this.set(o, this.$y + t);
      if (c === i) return d(1);
      if (c === s) return d(7);
      var $ = (h = {}, h[e] = 6e4, h[r] = 36e5, h[n] = 1e3, h)[c] || 1,
          l = this.valueOf() + t * $;
      return D.w(l, this);
    }, d.subtract = function (t, n) {
      return this.add(-1 * t, n);
    }, d.format = function (t) {
      var n = this;
      if (!this.isValid()) return "Invalid Date";

      var e = t || "YYYY-MM-DDTHH:mm:ssZ",
          r = D.z(this),
          i = this.$locale(),
          s = this.$H,
          u = this.$m,
          a = this.$M,
          o = i.weekdays,
          h = i.months,
          c = function c(t, r, i, s) {
        return t && (t[r] || t(n, e)) || i[r].substr(0, s);
      },
          d = function d(t) {
        return D.s(s % 12 || 12, t, "0");
      },
          $ = i.meridiem || function (t, n, e) {
        var r = t < 12 ? "AM" : "PM";
        return e ? r.toLowerCase() : r;
      },
          l = {
        YY: String(this.$y).slice(-2),
        YYYY: this.$y,
        M: a + 1,
        MM: D.s(a + 1, 2, "0"),
        MMM: c(i.monthsShort, a, h, 3),
        MMMM: h[a] || h(this, e),
        D: this.$D,
        DD: D.s(this.$D, 2, "0"),
        d: String(this.$W),
        dd: c(i.weekdaysMin, this.$W, o, 2),
        ddd: c(i.weekdaysShort, this.$W, o, 3),
        dddd: o[this.$W],
        H: String(s),
        HH: D.s(s, 2, "0"),
        h: d(1),
        hh: d(2),
        a: $(s, u, !0),
        A: $(s, u, !1),
        m: String(u),
        mm: D.s(u, 2, "0"),
        s: String(this.$s),
        ss: D.s(this.$s, 2, "0"),
        SSS: D.s(this.$ms, 3, "0"),
        Z: r
      };

      return e.replace(f, function (t, n) {
        return n || l[t] || r.replace(":", "");
      });
    }, d.utcOffset = function () {
      return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
    }, d.diff = function (t, h, f) {
      var c,
          d = D.p(h),
          $ = g(t),
          l = 6e4 * ($.utcOffset() - this.utcOffset()),
          m = this - $,
          y = D.m(this, $);
      return y = (c = {}, c[o] = y / 12, c[u] = y, c[a] = y / 3, c[s] = (m - l) / 6048e5, c[i] = (m - l) / 864e5, c[r] = m / 36e5, c[e] = m / 6e4, c[n] = m / 1e3, c)[d] || m, f ? y : D.a(y);
    }, d.daysInMonth = function () {
      return this.endOf(u).$D;
    }, d.$locale = function () {
      return m[this.$L];
    }, d.locale = function (t, n) {
      if (!t) return this.$L;
      var e = this.clone();
      return e.$L = M(t, n, !0), e;
    }, d.clone = function () {
      return D.w(this.toDate(), this);
    }, d.toDate = function () {
      return new Date(this.$d);
    }, d.toJSON = function () {
      return this.isValid() ? this.toISOString() : null;
    }, d.toISOString = function () {
      return this.$d.toISOString();
    }, d.toString = function () {
      return this.$d.toUTCString();
    }, c;
  }();

  return g.prototype = v.prototype, g.extend = function (t, n) {
    return t(n, v, g), g;
  }, g.locale = M, g.isDayjs = y, g.unix = function (t) {
    return g(1e3 * t);
  }, g.en = m[l], g.Ls = m, g;
});

/***/ }),

/***/ "./node_modules/dayjs/plugin/localizedFormat.js":
/*!******************************************************!*\
  !*** ./node_modules/dayjs/plugin/localizedFormat.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function (e, t) {
   true ? module.exports = t() : undefined;
}(this, function () {
  "use strict";

  return function (e, t, o) {
    var n = t.prototype,
        r = n.format,
        M = {
      LTS: "h:mm:ss A",
      LT: "h:mm A",
      L: "MM/DD/YYYY",
      LL: "MMMM D, YYYY",
      LLL: "MMMM D, YYYY h:mm A",
      LLLL: "dddd, MMMM D, YYYY h:mm A"
    };
    o.en.formats = M;

    n.format = function (e) {
      void 0 === e && (e = "YYYY-MM-DDTHH:mm:ssZ");
      var t = this.$locale().formats,
          o = void 0 === t ? {} : t,
          n = e.replace(/(\[[^\]]+])|(LTS?|l{1,4}|L{1,4})/g, function (e, t, n) {
        var r = n && n.toUpperCase();
        return t || o[n] || M[n] || o[r].replace(/(\[[^\]]+])|(MMMM|MM|DD|dddd)/g, function (e, t, o) {
          return t || o.slice(1);
        });
      });
      return r.call(this, n);
    };
  };
});

/***/ }),

/***/ "./node_modules/dayjs/plugin/relativeTime.js":
/*!***************************************************!*\
  !*** ./node_modules/dayjs/plugin/relativeTime.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

!function (r, t) {
   true ? module.exports = t() : undefined;
}(this, function () {
  "use strict";

  return function (r, t, e) {
    var n = t.prototype;
    e.en.relativeTime = {
      future: "in %s",
      past: "%s ago",
      s: "a few seconds",
      m: "a minute",
      mm: "%d minutes",
      h: "an hour",
      hh: "%d hours",
      d: "a day",
      dd: "%d days",
      M: "a month",
      MM: "%d months",
      y: "a year",
      yy: "%d years"
    };

    var o = function o(r, t, n, _o) {
      for (var d, i, u = n.$locale().relativeTime, a = [{
        l: "s",
        r: 44,
        d: "second"
      }, {
        l: "m",
        r: 89
      }, {
        l: "mm",
        r: 44,
        d: "minute"
      }, {
        l: "h",
        r: 89
      }, {
        l: "hh",
        r: 21,
        d: "hour"
      }, {
        l: "d",
        r: 35
      }, {
        l: "dd",
        r: 25,
        d: "day"
      }, {
        l: "M",
        r: 45
      }, {
        l: "MM",
        r: 10,
        d: "month"
      }, {
        l: "y",
        r: 17
      }, {
        l: "yy",
        d: "year"
      }], f = a.length, s = 0; s < f; s += 1) {
        var l = a[s];
        l.d && (d = _o ? e(r).diff(n, l.d, !0) : n.diff(r, l.d, !0));
        var h = Math.round(Math.abs(d));

        if (h <= l.r || !l.r) {
          1 === h && s > 0 && (l = a[s - 1]), i = u[l.l].replace("%d", h);
          break;
        }
      }

      return t ? i : (d > 0 ? u.future : u.past).replace("%s", i);
    };

    n.to = function (r, t) {
      return o(r, t, this, !0);
    }, n.from = function (r, t) {
      return o(r, t, this);
    };

    var d = function d(r) {
      return r.$u ? e.utc() : e();
    };

    n.toNow = function (r) {
      return this.to(d(this), r);
    }, n.fromNow = function (r) {
      return this.from(d(this), r);
    };
  };
});

/***/ }),

/***/ "./node_modules/expose-loader/index.js?Mousetrap!./node_modules/mousetrap/mousetrap.js-exposed":
/*!********************************************************************************************!*\
  !*** ./node_modules/expose-loader?Mousetrap!./node_modules/mousetrap/mousetrap.js-exposed ***!
  \********************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["Mousetrap"] = __webpack_require__(/*! -!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./mousetrap.js */ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/mousetrap/mousetrap.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?dayjs!./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js-exposed":
/*!*************************************************************************************************************************************************************!*\
  !*** ./node_modules/expose-loader?dayjs!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./node_modules/dayjs/dayjs.min.js-exposed ***!
  \*************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["dayjs"] = __webpack_require__(/*! -!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./dayjs.min.js */ "./node_modules/dayjs/dayjs.min.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?jQuery!./node_modules/zepto/dist/zepto.js-exposed":
/*!**************************************************************************************!*\
  !*** ./node_modules/expose-loader?jQuery!./node_modules/zepto/dist/zepto.js-exposed ***!
  \**************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["jQuery"] = __webpack_require__(/*! -!./node_modules/babel-loader/lib??ref--5!./node_modules/imports-loader?this=>window!./node_modules/source-map-loader!./zepto.js */ "./node_modules/babel-loader/lib/index.js?!./node_modules/imports-loader/index.js?this=>window!./node_modules/source-map-loader/index.js!./node_modules/zepto/dist/zepto.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?m!./node_modules/mithril/index.js-exposed":
/*!******************************************************************************!*\
  !*** ./node_modules/expose-loader?m!./node_modules/mithril/index.js-exposed ***!
  \******************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["m"] = __webpack_require__(/*! -!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./index.js */ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/mithril/index.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?m.bidi!./node_modules/m.attrs.bidi/bidi.js-exposed":
/*!***************************************************************************************!*\
  !*** ./node_modules/expose-loader?m.bidi!./node_modules/m.attrs.bidi/bidi.js-exposed ***!
  \***************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {if(!global["m"]) global["m"] = {};
module.exports = global["m"]["bidi"] = __webpack_require__(/*! -!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./bidi.js */ "./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/m.attrs.bidi/bidi.js");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/expose-loader/index.js?moment!./node_modules/expose-loader/index.js?dayjs!./node_modules/dayjs/dayjs.min.js-exposed":
/*!************************************************************************************************************************!*\
  !*** ./node_modules/expose-loader?moment!./node_modules/expose-loader?dayjs!./node_modules/dayjs/dayjs.min.js-exposed ***!
  \************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {module.exports = global["moment"] = __webpack_require__(/*! -!./node_modules/expose-loader?dayjs!./node_modules/babel-loader/lib??ref--5!./node_modules/source-map-loader!./dayjs.min.js */ "./node_modules/expose-loader/index.js?dayjs!./node_modules/babel-loader/lib/index.js?!./node_modules/source-map-loader/index.js!./node_modules/dayjs/dayjs.min.js-exposed");
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/mithril/api/mount-redraw.js":
/*!**************************************************!*\
  !*** ./node_modules/mithril/api/mount-redraw.js ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

module.exports = function (render, schedule, console) {
  var subscriptions = [];
  var rendering = false;
  var pending = false;

  function sync() {
    if (rendering) throw new Error("Nested m.redraw.sync() call");
    rendering = true;

    for (var i = 0; i < subscriptions.length; i += 2) {
      try {
        render(subscriptions[i], Vnode(subscriptions[i + 1]), redraw);
      } catch (e) {
        console.error(e);
      }
    }

    rendering = false;
  }

  function redraw() {
    if (!pending) {
      pending = true;
      schedule(function () {
        pending = false;
        sync();
      });
    }
  }

  redraw.sync = sync;

  function mount(root, component) {
    if (component != null && component.view == null && typeof component !== "function") {
      throw new TypeError("m.mount(element, component) expects a component, not a vnode");
    }

    var index = subscriptions.indexOf(root);

    if (index >= 0) {
      subscriptions.splice(index, 2);
      render(root, [], redraw);
    }

    if (component != null) {
      subscriptions.push(root, component);
      render(root, Vnode(component), redraw);
    }
  }

  return {
    mount: mount,
    redraw: redraw
  };
};

/***/ }),

/***/ "./node_modules/mithril/api/router.js":
/*!********************************************!*\
  !*** ./node_modules/mithril/api/router.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function(setImmediate) {

var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

var m = __webpack_require__(/*! ../render/hyperscript */ "./node_modules/mithril/render/hyperscript.js");

var Promise = __webpack_require__(/*! ../promise/promise */ "./node_modules/mithril/promise/promise.js");

var buildPathname = __webpack_require__(/*! ../pathname/build */ "./node_modules/mithril/pathname/build.js");

var parsePathname = __webpack_require__(/*! ../pathname/parse */ "./node_modules/mithril/pathname/parse.js");

var compileTemplate = __webpack_require__(/*! ../pathname/compileTemplate */ "./node_modules/mithril/pathname/compileTemplate.js");

var assign = __webpack_require__(/*! ../pathname/assign */ "./node_modules/mithril/pathname/assign.js");

var sentinel = {};

module.exports = function ($window, mountRedraw) {
  var fireAsync;

  function setPath(path, data, options) {
    path = buildPathname(path, data);

    if (fireAsync != null) {
      fireAsync();
      var state = options ? options.state : null;
      var title = options ? options.title : null;
      if (options && options.replace) $window.history.replaceState(state, title, route.prefix + path);else $window.history.pushState(state, title, route.prefix + path);
    } else {
      $window.location.href = route.prefix + path;
    }
  }

  var currentResolver = sentinel,
      component,
      attrs,
      currentPath,
      _lastUpdate;

  var SKIP = route.SKIP = {};

  function route(root, defaultRoute, routes) {
    if (root == null) throw new Error("Ensure the DOM element that was passed to `m.route` is not undefined"); // 0 = start
    // 1 = init
    // 2 = ready

    var state = 0;
    var compiled = Object.keys(routes).map(function (route) {
      if (route[0] !== "/") throw new SyntaxError("Routes must start with a `/`");

      if (/:([^\/\.-]+)(\.{3})?:/.test(route)) {
        throw new SyntaxError("Route parameter names must be separated with either `/`, `.`, or `-`");
      }

      return {
        route: route,
        component: routes[route],
        check: compileTemplate(route)
      };
    });
    var callAsync = typeof setImmediate === "function" ? setImmediate : setTimeout;
    var p = Promise.resolve();
    var scheduled = false;
    var onremove;
    fireAsync = null;

    if (defaultRoute != null) {
      var defaultData = parsePathname(defaultRoute);

      if (!compiled.some(function (i) {
        return i.check(defaultData);
      })) {
        throw new ReferenceError("Default route doesn't match any known routes");
      }
    }

    function resolveRoute() {
      scheduled = false; // Consider the pathname holistically. The prefix might even be invalid,
      // but that's not our problem.

      var prefix = $window.location.hash;

      if (route.prefix[0] !== "#") {
        prefix = $window.location.search + prefix;

        if (route.prefix[0] !== "?") {
          prefix = $window.location.pathname + prefix;
          if (prefix[0] !== "/") prefix = "/" + prefix;
        }
      } // This seemingly useless `.concat()` speeds up the tests quite a bit,
      // since the representation is consistently a relatively poorly
      // optimized cons string.


      var path = prefix.concat().replace(/(?:%[a-f89][a-f0-9])+/gim, decodeURIComponent).slice(route.prefix.length);
      var data = parsePathname(path);
      assign(data.params, $window.history.state);

      function fail() {
        if (path === defaultRoute) throw new Error("Could not resolve default route " + defaultRoute);
        setPath(defaultRoute, null, {
          replace: true
        });
      }

      loop(0);

      function loop(i) {
        // 0 = init
        // 1 = scheduled
        // 2 = done
        for (; i < compiled.length; i++) {
          if (compiled[i].check(data)) {
            var payload = compiled[i].component;
            var matchedRoute = compiled[i].route;
            var localComp = payload;

            var update = _lastUpdate = function lastUpdate(comp) {
              if (update !== _lastUpdate) return;
              if (comp === SKIP) return loop(i + 1);
              component = comp != null && (typeof comp.view === "function" || typeof comp === "function") ? comp : "div";
              attrs = data.params, currentPath = path, _lastUpdate = null;
              currentResolver = payload.render ? payload : null;
              if (state === 2) mountRedraw.redraw();else {
                state = 2;
                mountRedraw.redraw.sync();
              }
            }; // There's no understating how much I *wish* I could
            // use `async`/`await` here...


            if (payload.view || typeof payload === "function") {
              payload = {};
              update(localComp);
            } else if (payload.onmatch) {
              p.then(function () {
                return payload.onmatch(data.params, path, matchedRoute);
              }).then(update, fail);
            } else update("div");

            return;
          }
        }

        fail();
      }
    } // Set it unconditionally so `m.route.set` and `m.route.Link` both work,
    // even if neither `pushState` nor `hashchange` are supported. It's
    // cleared if `hashchange` is used, since that makes it automatically
    // async.


    fireAsync = function fireAsync() {
      if (!scheduled) {
        scheduled = true;
        callAsync(resolveRoute);
      }
    };

    if (typeof $window.history.pushState === "function") {
      onremove = function onremove() {
        $window.removeEventListener("popstate", fireAsync, false);
      };

      $window.addEventListener("popstate", fireAsync, false);
    } else if (route.prefix[0] === "#") {
      fireAsync = null;

      onremove = function onremove() {
        $window.removeEventListener("hashchange", resolveRoute, false);
      };

      $window.addEventListener("hashchange", resolveRoute, false);
    }

    return mountRedraw.mount(root, {
      onbeforeupdate: function onbeforeupdate() {
        state = state ? 2 : 1;
        return !(!state || sentinel === currentResolver);
      },
      oncreate: resolveRoute,
      onremove: onremove,
      view: function view() {
        if (!state || sentinel === currentResolver) return; // Wrap in a fragment to preserve existing key semantics

        var vnode = [Vnode(component, attrs.key, attrs)];
        if (currentResolver) vnode = currentResolver.render(vnode[0]);
        return vnode;
      }
    });
  }

  route.set = function (path, data, options) {
    if (_lastUpdate != null) {
      options = options || {};
      options.replace = true;
    }

    _lastUpdate = null;
    setPath(path, data, options);
  };

  route.get = function () {
    return currentPath;
  };

  route.prefix = "#!";
  route.Link = {
    view: function view(vnode) {
      var options = vnode.attrs.options; // Remove these so they don't get overwritten

      var attrs = {},
          onclick,
          href;
      assign(attrs, vnode.attrs); // The first two are internal, but the rest are magic attributes
      // that need censored to not screw up rendering.

      attrs.selector = attrs.options = attrs.key = attrs.oninit = attrs.oncreate = attrs.onbeforeupdate = attrs.onupdate = attrs.onbeforeremove = attrs.onremove = null; // Do this now so we can get the most current `href` and `disabled`.
      // Those attributes may also be specified in the selector, and we
      // should honor that.

      var child = m(vnode.attrs.selector || "a", attrs, vnode.children); // Let's provide a *right* way to disable a route link, rather than
      // letting people screw up accessibility on accident.
      //
      // The attribute is coerced so users don't get surprised over
      // `disabled: 0` resulting in a button that's somehow routable
      // despite being visibly disabled.

      if (child.attrs.disabled = Boolean(child.attrs.disabled)) {
        child.attrs.href = null;
        child.attrs["aria-disabled"] = "true"; // If you *really* do want to do this on a disabled link, use
        // an `oncreate` hook to add it.

        child.attrs.onclick = null;
      } else {
        onclick = child.attrs.onclick;
        href = child.attrs.href;
        child.attrs.href = route.prefix + href;

        child.attrs.onclick = function (e) {
          var result;

          if (typeof onclick === "function") {
            result = onclick.call(e.currentTarget, e);
          } else if (onclick == null || typeof onclick !== "object") {// do nothing
          } else if (typeof onclick.handleEvent === "function") {
            onclick.handleEvent(e);
          } // Adapted from React Router's implementation:
          // https://github.com/ReactTraining/react-router/blob/520a0acd48ae1b066eb0b07d6d4d1790a1d02482/packages/react-router-dom/modules/Link.js
          //
          // Try to be flexible and intuitive in how we handle links.
          // Fun fact: links aren't as obvious to get right as you
          // would expect. There's a lot more valid ways to click a
          // link than this, and one might want to not simply click a
          // link, but right click or command-click it to copy the
          // link target, etc. Nope, this isn't just for blind people.


          if ( // Skip if `onclick` prevented default
          result !== false && !e.defaultPrevented && ( // Ignore everything but left clicks
          e.button === 0 || e.which === 0 || e.which === 1) && ( // Let the browser handle `target=_blank`, etc.
          !e.currentTarget.target || e.currentTarget.target === "_self") && // No modifier keys
          !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey) {
            e.preventDefault();
            e.redraw = false;
            route.set(href, null, options);
          }
        };
      }

      return child;
    }
  };

  route.param = function (key) {
    return attrs && key != null ? attrs[key] : attrs;
  };

  return route;
};
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../timers-browserify/main.js */ "./node_modules/timers-browserify/main.js").setImmediate))

/***/ }),

/***/ "./node_modules/mithril/hyperscript.js":
/*!*********************************************!*\
  !*** ./node_modules/mithril/hyperscript.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var hyperscript = __webpack_require__(/*! ./render/hyperscript */ "./node_modules/mithril/render/hyperscript.js");

hyperscript.trust = __webpack_require__(/*! ./render/trust */ "./node_modules/mithril/render/trust.js");
hyperscript.fragment = __webpack_require__(/*! ./render/fragment */ "./node_modules/mithril/render/fragment.js");
module.exports = hyperscript;

/***/ }),

/***/ "./node_modules/mithril/mount-redraw.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/mount-redraw.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var render = __webpack_require__(/*! ./render */ "./node_modules/mithril/render.js");

module.exports = __webpack_require__(/*! ./api/mount-redraw */ "./node_modules/mithril/api/mount-redraw.js")(render, requestAnimationFrame, console);

/***/ }),

/***/ "./node_modules/mithril/pathname/assign.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/pathname/assign.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = Object.assign || function (target, source) {
  if (source) Object.keys(source).forEach(function (key) {
    target[key] = source[key];
  });
};

/***/ }),

/***/ "./node_modules/mithril/pathname/build.js":
/*!************************************************!*\
  !*** ./node_modules/mithril/pathname/build.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var buildQueryString = __webpack_require__(/*! ../querystring/build */ "./node_modules/mithril/querystring/build.js");

var assign = __webpack_require__(/*! ./assign */ "./node_modules/mithril/pathname/assign.js"); // Returns `path` from `template` + `params`


module.exports = function (template, params) {
  if (/:([^\/\.-]+)(\.{3})?:/.test(template)) {
    throw new SyntaxError("Template parameter names *must* be separated");
  }

  if (params == null) return template;
  var queryIndex = template.indexOf("?");
  var hashIndex = template.indexOf("#");
  var queryEnd = hashIndex < 0 ? template.length : hashIndex;
  var pathEnd = queryIndex < 0 ? queryEnd : queryIndex;
  var path = template.slice(0, pathEnd);
  var query = {};
  assign(query, params);
  var resolved = path.replace(/:([^\/\.-]+)(\.{3})?/g, function (m, key, variadic) {
    delete query[key]; // If no such parameter exists, don't interpolate it.

    if (params[key] == null) return m; // Escape normal parameters, but not variadic ones.

    return variadic ? params[key] : encodeURIComponent(String(params[key]));
  }); // In case the template substitution adds new query/hash parameters.

  var newQueryIndex = resolved.indexOf("?");
  var newHashIndex = resolved.indexOf("#");
  var newQueryEnd = newHashIndex < 0 ? resolved.length : newHashIndex;
  var newPathEnd = newQueryIndex < 0 ? newQueryEnd : newQueryIndex;
  var result = resolved.slice(0, newPathEnd);
  if (queryIndex >= 0) result += template.slice(queryIndex, queryEnd);
  if (newQueryIndex >= 0) result += (queryIndex < 0 ? "?" : "&") + resolved.slice(newQueryIndex, newQueryEnd);
  var querystring = buildQueryString(query);
  if (querystring) result += (queryIndex < 0 && newQueryIndex < 0 ? "?" : "&") + querystring;
  if (hashIndex >= 0) result += template.slice(hashIndex);
  if (newHashIndex >= 0) result += (hashIndex < 0 ? "" : "&") + resolved.slice(newHashIndex);
  return result;
};

/***/ }),

/***/ "./node_modules/mithril/pathname/compileTemplate.js":
/*!**********************************************************!*\
  !*** ./node_modules/mithril/pathname/compileTemplate.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var parsePathname = __webpack_require__(/*! ./parse */ "./node_modules/mithril/pathname/parse.js"); // Compiles a template into a function that takes a resolved path (without query
// strings) and returns an object containing the template parameters with their
// parsed values. This expects the input of the compiled template to be the
// output of `parsePathname`. Note that it does *not* remove query parameters
// specified in the template.


module.exports = function (template) {
  var templateData = parsePathname(template);
  var templateKeys = Object.keys(templateData.params);
  var keys = [];
  var regexp = new RegExp("^" + templateData.path.replace( // I escape literal text so people can use things like `:file.:ext` or
  // `:lang-:locale` in routes. This is all merged into one pass so I
  // don't also accidentally escape `-` and make it harder to detect it to
  // ban it from template parameters.
  /:([^\/.-]+)(\.{3}|\.(?!\.)|-)?|[\\^$*+.()|\[\]{}]/g, function (m, key, extra) {
    if (key == null) return "\\" + m;
    keys.push({
      k: key,
      r: extra === "..."
    });
    if (extra === "...") return "(.*)";
    if (extra === ".") return "([^/]+)\\.";
    return "([^/]+)" + (extra || "");
  }) + "$");
  return function (data) {
    // First, check the params. Usually, there isn't any, and it's just
    // checking a static set.
    for (var i = 0; i < templateKeys.length; i++) {
      if (templateData.params[templateKeys[i]] !== data.params[templateKeys[i]]) return false;
    } // If no interpolations exist, let's skip all the ceremony


    if (!keys.length) return regexp.test(data.path);
    var values = regexp.exec(data.path);
    if (values == null) return false;

    for (var i = 0; i < keys.length; i++) {
      data.params[keys[i].k] = keys[i].r ? values[i + 1] : decodeURIComponent(values[i + 1]);
    }

    return true;
  };
};

/***/ }),

/***/ "./node_modules/mithril/pathname/parse.js":
/*!************************************************!*\
  !*** ./node_modules/mithril/pathname/parse.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var parseQueryString = __webpack_require__(/*! ../querystring/parse */ "./node_modules/mithril/querystring/parse.js"); // Returns `{path, params}` from `url`


module.exports = function (url) {
  var queryIndex = url.indexOf("?");
  var hashIndex = url.indexOf("#");
  var queryEnd = hashIndex < 0 ? url.length : hashIndex;
  var pathEnd = queryIndex < 0 ? queryEnd : queryIndex;
  var path = url.slice(0, pathEnd).replace(/\/{2,}/g, "/");
  if (!path) path = "/";else {
    if (path[0] !== "/") path = "/" + path;
    if (path.length > 1 && path[path.length - 1] === "/") path = path.slice(0, -1);
  }
  return {
    path: path,
    params: queryIndex < 0 ? {} : parseQueryString(url.slice(queryIndex + 1, queryEnd))
  };
};

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

var PromisePolyfill = function PromisePolyfill(executor) {
  if (!(this instanceof PromisePolyfill)) throw new Error("Promise must be called with `new`");
  if (typeof executor !== "function") throw new TypeError("executor must be a function");
  var self = this,
      resolvers = [],
      rejectors = [],
      resolveCurrent = handler(resolvers, true),
      rejectCurrent = handler(rejectors, false);
  var instance = self._instance = {
    resolvers: resolvers,
    rejectors: rejectors
  };
  var callAsync = typeof setImmediate === "function" ? setImmediate : setTimeout;

  function handler(list, shouldAbsorb) {
    return function execute(value) {
      var then;

      try {
        if (shouldAbsorb && value != null && (typeof value === "object" || typeof value === "function") && typeof (then = value.then) === "function") {
          if (value === self) throw new TypeError("Promise can't be resolved w/ itself");
          executeOnce(then.bind(value));
        } else {
          callAsync(function () {
            if (!shouldAbsorb && list.length === 0) console.error("Possible unhandled promise rejection:", value);

            for (var i = 0; i < list.length; i++) {
              list[i](value);
            }

            resolvers.length = 0, rejectors.length = 0;
            instance.state = shouldAbsorb;

            instance.retry = function () {
              execute(value);
            };
          });
        }
      } catch (e) {
        rejectCurrent(e);
      }
    };
  }

  function executeOnce(then) {
    var runs = 0;

    function run(fn) {
      return function (value) {
        if (runs++ > 0) return;
        fn(value);
      };
    }

    var onerror = run(rejectCurrent);

    try {
      then(run(resolveCurrent), onerror);
    } catch (e) {
      onerror(e);
    }
  }

  executeOnce(executor);
};

PromisePolyfill.prototype.then = function (onFulfilled, onRejection) {
  var self = this,
      instance = self._instance;

  function handle(callback, list, next, state) {
    list.push(function (value) {
      if (typeof callback !== "function") next(value);else try {
        resolveNext(callback(value));
      } catch (e) {
        if (rejectNext) rejectNext(e);
      }
    });
    if (typeof instance.retry === "function" && state === instance.state) instance.retry();
  }

  var resolveNext, rejectNext;
  var promise = new PromisePolyfill(function (resolve, reject) {
    resolveNext = resolve, rejectNext = reject;
  });
  handle(onFulfilled, instance.resolvers, resolveNext, true), handle(onRejection, instance.rejectors, rejectNext, false);
  return promise;
};

PromisePolyfill.prototype.catch = function (onRejection) {
  return this.then(null, onRejection);
};

PromisePolyfill.prototype.finally = function (callback) {
  return this.then(function (value) {
    return PromisePolyfill.resolve(callback()).then(function () {
      return value;
    });
  }, function (reason) {
    return PromisePolyfill.resolve(callback()).then(function () {
      return PromisePolyfill.reject(reason);
    });
  });
};

PromisePolyfill.resolve = function (value) {
  if (value instanceof PromisePolyfill) return value;
  return new PromisePolyfill(function (resolve) {
    resolve(value);
  });
};

PromisePolyfill.reject = function (value) {
  return new PromisePolyfill(function (resolve, reject) {
    reject(value);
  });
};

PromisePolyfill.all = function (list) {
  return new PromisePolyfill(function (resolve, reject) {
    var total = list.length,
        count = 0,
        values = [];
    if (list.length === 0) resolve([]);else for (var i = 0; i < list.length; i++) {
      (function (i) {
        function consume(value) {
          count++;
          values[i] = value;
          if (count === total) resolve(values);
        }

        if (list[i] != null && (typeof list[i] === "object" || typeof list[i] === "function") && typeof list[i].then === "function") {
          list[i].then(consume, reject);
        } else consume(list[i]);
      })(i);
    }
  });
};

PromisePolyfill.race = function (list) {
  return new PromisePolyfill(function (resolve, reject) {
    for (var i = 0; i < list.length; i++) {
      list[i].then(resolve, reject);
    }
  });
};

module.exports = PromisePolyfill;
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

var PromisePolyfill = __webpack_require__(/*! ./polyfill */ "./node_modules/mithril/promise/polyfill.js");

if (typeof window !== "undefined") {
  if (typeof window.Promise === "undefined") {
    window.Promise = PromisePolyfill;
  } else if (!window.Promise.prototype.finally) {
    window.Promise.prototype.finally = PromisePolyfill.prototype.finally;
  }

  module.exports = window.Promise;
} else if (typeof global !== "undefined") {
  if (typeof global.Promise === "undefined") {
    global.Promise = PromisePolyfill;
  } else if (!global.Promise.prototype.finally) {
    global.Promise.prototype.finally = PromisePolyfill.prototype.finally;
  }

  module.exports = global.Promise;
} else {
  module.exports = PromisePolyfill;
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


module.exports = function (object) {
  if (Object.prototype.toString.call(object) !== "[object Object]") return "";
  var args = [];

  for (var key in object) {
    destructure(key, object[key]);
  }

  return args.join("&");

  function destructure(key, value) {
    if (Array.isArray(value)) {
      for (var i = 0; i < value.length; i++) {
        destructure(key + "[" + i + "]", value[i]);
      }
    } else if (Object.prototype.toString.call(value) === "[object Object]") {
      for (var i in value) {
        destructure(key + "[" + i + "]", value[i]);
      }
    } else args.push(encodeURIComponent(key) + (value != null && value !== "" ? "=" + encodeURIComponent(value) : ""));
  }
};

/***/ }),

/***/ "./node_modules/mithril/querystring/parse.js":
/*!***************************************************!*\
  !*** ./node_modules/mithril/querystring/parse.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = function (string) {
  if (string === "" || string == null) return {};
  if (string.charAt(0) === "?") string = string.slice(1);
  var entries = string.split("&"),
      counters = {},
      data = {};

  for (var i = 0; i < entries.length; i++) {
    var entry = entries[i].split("=");
    var key = decodeURIComponent(entry[0]);
    var value = entry.length === 2 ? decodeURIComponent(entry[1]) : "";
    if (value === "true") value = true;else if (value === "false") value = false;
    var levels = key.split(/\]\[?|\[/);
    var cursor = data;
    if (key.indexOf("[") > -1) levels.pop();

    for (var j = 0; j < levels.length; j++) {
      var level = levels[j],
          nextLevel = levels[j + 1];
      var isNumber = nextLevel == "" || !isNaN(parseInt(nextLevel, 10));

      if (level === "") {
        var key = levels.slice(0, j).join();

        if (counters[key] == null) {
          counters[key] = Array.isArray(cursor) ? cursor.length : 0;
        }

        level = counters[key]++;
      } // Disallow direct prototype pollution
      else if (level === "__proto__") break;

      if (j === levels.length - 1) cursor[level] = value;else {
        // Read own properties exclusively to disallow indirect
        // prototype pollution
        var desc = Object.getOwnPropertyDescriptor(cursor, level);
        if (desc != null) desc = desc.value;
        if (desc == null) cursor[level] = desc = isNumber ? [] : {};
        cursor = desc;
      }
    }
  }

  return data;
};

/***/ }),

/***/ "./node_modules/mithril/render.js":
/*!****************************************!*\
  !*** ./node_modules/mithril/render.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


module.exports = __webpack_require__(/*! ./render/render */ "./node_modules/mithril/render/render.js")(window);

/***/ }),

/***/ "./node_modules/mithril/render/fragment.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/render/fragment.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

var hyperscriptVnode = __webpack_require__(/*! ./hyperscriptVnode */ "./node_modules/mithril/render/hyperscriptVnode.js");

module.exports = function () {
  var vnode = hyperscriptVnode.apply(0, arguments);
  vnode.tag = "[";
  vnode.children = Vnode.normalizeChildren(vnode.children);
  return vnode;
};

/***/ }),

/***/ "./node_modules/mithril/render/hyperscript.js":
/*!****************************************************!*\
  !*** ./node_modules/mithril/render/hyperscript.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

var hyperscriptVnode = __webpack_require__(/*! ./hyperscriptVnode */ "./node_modules/mithril/render/hyperscriptVnode.js");

var selectorParser = /(?:(^|#|\.)([^#\.\[\]]+))|(\[(.+?)(?:\s*=\s*("|'|)((?:\\["'\]]|.)*?)\5)?\])/g;
var selectorCache = {};
var hasOwn = {}.hasOwnProperty;

function isEmpty(object) {
  for (var key in object) {
    if (hasOwn.call(object, key)) return false;
  }

  return true;
}

function compileSelector(selector) {
  var match,
      tag = "div",
      classes = [],
      attrs = {};

  while (match = selectorParser.exec(selector)) {
    var type = match[1],
        value = match[2];
    if (type === "" && value !== "") tag = value;else if (type === "#") attrs.id = value;else if (type === ".") classes.push(value);else if (match[3][0] === "[") {
      var attrValue = match[6];
      if (attrValue) attrValue = attrValue.replace(/\\(["'])/g, "$1").replace(/\\\\/g, "\\");
      if (match[4] === "class") classes.push(attrValue);else attrs[match[4]] = attrValue === "" ? attrValue : attrValue || true;
    }
  }

  if (classes.length > 0) attrs.className = classes.join(" ");
  return selectorCache[selector] = {
    tag: tag,
    attrs: attrs
  };
}

function execSelector(state, vnode) {
  var attrs = vnode.attrs;
  var children = Vnode.normalizeChildren(vnode.children);
  var hasClass = hasOwn.call(attrs, "class");
  var className = hasClass ? attrs.class : attrs.className;
  vnode.tag = state.tag;
  vnode.attrs = null;
  vnode.children = undefined;

  if (!isEmpty(state.attrs) && !isEmpty(attrs)) {
    var newAttrs = {};

    for (var key in attrs) {
      if (hasOwn.call(attrs, key)) newAttrs[key] = attrs[key];
    }

    attrs = newAttrs;
  }

  for (var key in state.attrs) {
    if (hasOwn.call(state.attrs, key) && key !== "className" && !hasOwn.call(attrs, key)) {
      attrs[key] = state.attrs[key];
    }
  }

  if (className != null || state.attrs.className != null) attrs.className = className != null ? state.attrs.className != null ? String(state.attrs.className) + " " + String(className) : className : state.attrs.className != null ? state.attrs.className : null;
  if (hasClass) attrs.class = null;

  for (var key in attrs) {
    if (hasOwn.call(attrs, key) && key !== "key") {
      vnode.attrs = attrs;
      break;
    }
  }

  if (Array.isArray(children) && children.length === 1 && children[0] != null && children[0].tag === "#") {
    vnode.text = children[0].children;
  } else {
    vnode.children = children;
  }

  return vnode;
}

function hyperscript(selector) {
  if (selector == null || typeof selector !== "string" && typeof selector !== "function" && typeof selector.view !== "function") {
    throw Error("The selector must be either a string or a component.");
  }

  var vnode = hyperscriptVnode.apply(1, arguments);

  if (typeof selector === "string") {
    vnode.children = Vnode.normalizeChildren(vnode.children);
    if (selector !== "[") return execSelector(selectorCache[selector] || compileSelector(selector), vnode);
  }

  vnode.tag = selector;
  return vnode;
}

module.exports = hyperscript;

/***/ }),

/***/ "./node_modules/mithril/render/hyperscriptVnode.js":
/*!*********************************************************!*\
  !*** ./node_modules/mithril/render/hyperscriptVnode.js ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js"); // Call via `hyperscriptVnode.apply(startOffset, arguments)`
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


module.exports = function () {
  var attrs = arguments[this],
      start = this + 1,
      children;

  if (attrs == null) {
    attrs = {};
  } else if (typeof attrs !== "object" || attrs.tag != null || Array.isArray(attrs)) {
    attrs = {};
    start = this;
  }

  if (arguments.length === start + 1) {
    children = arguments[start];
    if (!Array.isArray(children)) children = [children];
  } else {
    children = [];

    while (start < arguments.length) {
      children.push(arguments[start++]);
    }
  }

  return Vnode("", attrs.key, attrs, children);
};

/***/ }),

/***/ "./node_modules/mithril/render/render.js":
/*!***********************************************!*\
  !*** ./node_modules/mithril/render/render.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

module.exports = function ($window) {
  var $doc = $window && $window.document;
  var currentRedraw;
  var nameSpace = {
    svg: "http://www.w3.org/2000/svg",
    math: "http://www.w3.org/1998/Math/MathML"
  };

  function getNameSpace(vnode) {
    return vnode.attrs && vnode.attrs.xmlns || nameSpace[vnode.tag];
  } //sanity check to discourage people from doing `vnode.state = ...`


  function checkState(vnode, original) {
    if (vnode.state !== original) throw new Error("`vnode.state` must not be modified");
  } //Note: the hook is passed as the `this` argument to allow proxying the
  //arguments without requiring a full array allocation to do so. It also
  //takes advantage of the fact the current `vnode` is the first argument in
  //all lifecycle methods.


  function callHook(vnode) {
    var original = vnode.state;

    try {
      return this.apply(original, arguments);
    } finally {
      checkState(vnode, original);
    }
  } // IE11 (at least) throws an UnspecifiedError when accessing document.activeElement when
  // inside an iframe. Catch and swallow this error, and heavy-handidly return null.


  function activeElement() {
    try {
      return $doc.activeElement;
    } catch (e) {
      return null;
    }
  } //create


  function createNodes(parent, vnodes, start, end, hooks, nextSibling, ns) {
    for (var i = start; i < end; i++) {
      var vnode = vnodes[i];

      if (vnode != null) {
        createNode(parent, vnode, hooks, ns, nextSibling);
      }
    }
  }

  function createNode(parent, vnode, hooks, ns, nextSibling) {
    var tag = vnode.tag;

    if (typeof tag === "string") {
      vnode.state = {};
      if (vnode.attrs != null) initLifecycle(vnode.attrs, vnode, hooks);

      switch (tag) {
        case "#":
          createText(parent, vnode, nextSibling);
          break;

        case "<":
          createHTML(parent, vnode, ns, nextSibling);
          break;

        case "[":
          createFragment(parent, vnode, hooks, ns, nextSibling);
          break;

        default:
          createElement(parent, vnode, hooks, ns, nextSibling);
      }
    } else createComponent(parent, vnode, hooks, ns, nextSibling);
  }

  function createText(parent, vnode, nextSibling) {
    vnode.dom = $doc.createTextNode(vnode.children);
    insertNode(parent, vnode.dom, nextSibling);
  }

  var possibleParents = {
    caption: "table",
    thead: "table",
    tbody: "table",
    tfoot: "table",
    tr: "tbody",
    th: "tr",
    td: "tr",
    colgroup: "table",
    col: "colgroup"
  };

  function createHTML(parent, vnode, ns, nextSibling) {
    var match = vnode.children.match(/^\s*?<(\w+)/im) || []; // not using the proper parent makes the child element(s) vanish.
    //     var div = document.createElement("div")
    //     div.innerHTML = "<td>i</td><td>j</td>"
    //     console.log(div.innerHTML)
    // --> "ij", no <td> in sight.

    var temp = $doc.createElement(possibleParents[match[1]] || "div");

    if (ns === "http://www.w3.org/2000/svg") {
      temp.innerHTML = "<svg xmlns=\"http://www.w3.org/2000/svg\">" + vnode.children + "</svg>";
      temp = temp.firstChild;
    } else {
      temp.innerHTML = vnode.children;
    }

    vnode.dom = temp.firstChild;
    vnode.domSize = temp.childNodes.length; // Capture nodes to remove, so we don't confuse them.

    vnode.instance = [];
    var fragment = $doc.createDocumentFragment();
    var child;

    while (child = temp.firstChild) {
      vnode.instance.push(child);
      fragment.appendChild(child);
    }

    insertNode(parent, fragment, nextSibling);
  }

  function createFragment(parent, vnode, hooks, ns, nextSibling) {
    var fragment = $doc.createDocumentFragment();

    if (vnode.children != null) {
      var children = vnode.children;
      createNodes(fragment, children, 0, children.length, hooks, null, ns);
    }

    vnode.dom = fragment.firstChild;
    vnode.domSize = fragment.childNodes.length;
    insertNode(parent, fragment, nextSibling);
  }

  function createElement(parent, vnode, hooks, ns, nextSibling) {
    var tag = vnode.tag;
    var attrs = vnode.attrs;
    var is = attrs && attrs.is;
    ns = getNameSpace(vnode) || ns;
    var element = ns ? is ? $doc.createElementNS(ns, tag, {
      is: is
    }) : $doc.createElementNS(ns, tag) : is ? $doc.createElement(tag, {
      is: is
    }) : $doc.createElement(tag);
    vnode.dom = element;

    if (attrs != null) {
      setAttrs(vnode, attrs, ns);
    }

    insertNode(parent, element, nextSibling);

    if (!maybeSetContentEditable(vnode)) {
      if (vnode.text != null) {
        if (vnode.text !== "") element.textContent = vnode.text;else vnode.children = [Vnode("#", undefined, undefined, vnode.text, undefined, undefined)];
      }

      if (vnode.children != null) {
        var children = vnode.children;
        createNodes(element, children, 0, children.length, hooks, null, ns);
        if (vnode.tag === "select" && attrs != null) setLateSelectAttrs(vnode, attrs);
      }
    }
  }

  function initComponent(vnode, hooks) {
    var sentinel;

    if (typeof vnode.tag.view === "function") {
      vnode.state = Object.create(vnode.tag);
      sentinel = vnode.state.view;
      if (sentinel.$$reentrantLock$$ != null) return;
      sentinel.$$reentrantLock$$ = true;
    } else {
      vnode.state = void 0;
      sentinel = vnode.tag;
      if (sentinel.$$reentrantLock$$ != null) return;
      sentinel.$$reentrantLock$$ = true;
      vnode.state = vnode.tag.prototype != null && typeof vnode.tag.prototype.view === "function" ? new vnode.tag(vnode) : vnode.tag(vnode);
    }

    initLifecycle(vnode.state, vnode, hooks);
    if (vnode.attrs != null) initLifecycle(vnode.attrs, vnode, hooks);
    vnode.instance = Vnode.normalize(callHook.call(vnode.state.view, vnode));
    if (vnode.instance === vnode) throw Error("A view cannot return the vnode it received as argument");
    sentinel.$$reentrantLock$$ = null;
  }

  function createComponent(parent, vnode, hooks, ns, nextSibling) {
    initComponent(vnode, hooks);

    if (vnode.instance != null) {
      createNode(parent, vnode.instance, hooks, ns, nextSibling);
      vnode.dom = vnode.instance.dom;
      vnode.domSize = vnode.dom != null ? vnode.instance.domSize : 0;
    } else {
      vnode.domSize = 0;
    }
  } //update

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
    if (old === vnodes || old == null && vnodes == null) return;else if (old == null || old.length === 0) createNodes(parent, vnodes, 0, vnodes.length, hooks, nextSibling, ns);else if (vnodes == null || vnodes.length === 0) removeNodes(parent, old, 0, old.length);else {
      var isOldKeyed = old[0] != null && old[0].key != null;
      var isKeyed = vnodes[0] != null && vnodes[0].key != null;
      var start = 0,
          oldStart = 0;
      if (!isOldKeyed) while (oldStart < old.length && old[oldStart] == null) {
        oldStart++;
      }
      if (!isKeyed) while (start < vnodes.length && vnodes[start] == null) {
        start++;
      }
      if (isKeyed === null && isOldKeyed == null) return; // both lists are full of nulls

      if (isOldKeyed !== isKeyed) {
        removeNodes(parent, old, oldStart, old.length);
        createNodes(parent, vnodes, start, vnodes.length, hooks, nextSibling, ns);
      } else if (!isKeyed) {
        // Don't index past the end of either list (causes deopts).
        var commonLength = old.length < vnodes.length ? old.length : vnodes.length; // Rewind if necessary to the first non-null index on either side.
        // We could alternatively either explicitly create or remove nodes when `start !== oldStart`
        // but that would be optimizing for sparse lists which are more rare than dense ones.

        start = start < oldStart ? start : oldStart;

        for (; start < commonLength; start++) {
          o = old[start];
          v = vnodes[start];
          if (o === v || o == null && v == null) continue;else if (o == null) createNode(parent, v, hooks, ns, getNextSibling(old, start + 1, nextSibling));else if (v == null) removeNode(parent, o);else updateNode(parent, o, v, hooks, getNextSibling(old, start + 1, nextSibling), ns);
        }

        if (old.length > commonLength) removeNodes(parent, old, start, old.length);
        if (vnodes.length > commonLength) createNodes(parent, vnodes, start, vnodes.length, hooks, nextSibling, ns);
      } else {
        // keyed diff
        var oldEnd = old.length - 1,
            end = vnodes.length - 1,
            map,
            o,
            v,
            oe,
            ve,
            topSibling; // bottom-up

        while (oldEnd >= oldStart && end >= start) {
          oe = old[oldEnd];
          ve = vnodes[end];
          if (oe.key !== ve.key) break;
          if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns);
          if (ve.dom != null) nextSibling = ve.dom;
          oldEnd--, end--;
        } // top-down


        while (oldEnd >= oldStart && end >= start) {
          o = old[oldStart];
          v = vnodes[start];
          if (o.key !== v.key) break;
          oldStart++, start++;
          if (o !== v) updateNode(parent, o, v, hooks, getNextSibling(old, oldStart, nextSibling), ns);
        } // swaps and list reversals


        while (oldEnd >= oldStart && end >= start) {
          if (start === end) break;
          if (o.key !== ve.key || oe.key !== v.key) break;
          topSibling = getNextSibling(old, oldStart, nextSibling);
          moveNodes(parent, oe, topSibling);
          if (oe !== v) updateNode(parent, oe, v, hooks, topSibling, ns);
          if (++start <= --end) moveNodes(parent, o, nextSibling);
          if (o !== ve) updateNode(parent, o, ve, hooks, nextSibling, ns);
          if (ve.dom != null) nextSibling = ve.dom;
          oldStart++;
          oldEnd--;
          oe = old[oldEnd];
          ve = vnodes[end];
          o = old[oldStart];
          v = vnodes[start];
        } // bottom up once again


        while (oldEnd >= oldStart && end >= start) {
          if (oe.key !== ve.key) break;
          if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns);
          if (ve.dom != null) nextSibling = ve.dom;
          oldEnd--, end--;
          oe = old[oldEnd];
          ve = vnodes[end];
        }

        if (start > end) removeNodes(parent, old, oldStart, oldEnd + 1);else if (oldStart > oldEnd) createNodes(parent, vnodes, start, end + 1, hooks, nextSibling, ns);else {
          // inspired by ivi https://github.com/ivijs/ivi/ by Boris Kaul
          var originalNextSibling = nextSibling,
              vnodesLength = end - start + 1,
              oldIndices = new Array(vnodesLength),
              li = 0,
              i = 0,
              pos = 2147483647,
              matched = 0,
              map,
              lisIndices;

          for (i = 0; i < vnodesLength; i++) {
            oldIndices[i] = -1;
          }

          for (i = end; i >= start; i--) {
            if (map == null) map = getKeyMap(old, oldStart, oldEnd + 1);
            ve = vnodes[i];
            var oldIndex = map[ve.key];

            if (oldIndex != null) {
              pos = oldIndex < pos ? oldIndex : -1; // becomes -1 if nodes were re-ordered

              oldIndices[i - start] = oldIndex;
              oe = old[oldIndex];
              old[oldIndex] = null;
              if (oe !== ve) updateNode(parent, oe, ve, hooks, nextSibling, ns);
              if (ve.dom != null) nextSibling = ve.dom;
              matched++;
            }
          }

          nextSibling = originalNextSibling;
          if (matched !== oldEnd - oldStart + 1) removeNodes(parent, old, oldStart, oldEnd + 1);
          if (matched === 0) createNodes(parent, vnodes, start, end + 1, hooks, nextSibling, ns);else {
            if (pos === -1) {
              // the indices of the indices of the items that are part of the
              // longest increasing subsequence in the oldIndices list
              lisIndices = makeLisIndices(oldIndices);
              li = lisIndices.length - 1;

              for (i = end; i >= start; i--) {
                v = vnodes[i];
                if (oldIndices[i - start] === -1) createNode(parent, v, hooks, ns, nextSibling);else {
                  if (lisIndices[li] === i - start) li--;else moveNodes(parent, v, nextSibling);
                }
                if (v.dom != null) nextSibling = vnodes[i].dom;
              }
            } else {
              for (i = end; i >= start; i--) {
                v = vnodes[i];
                if (oldIndices[i - start] === -1) createNode(parent, v, hooks, ns, nextSibling);
                if (v.dom != null) nextSibling = vnodes[i].dom;
              }
            }
          }
        }
      }
    }
  }

  function updateNode(parent, old, vnode, hooks, nextSibling, ns) {
    var oldTag = old.tag,
        tag = vnode.tag;

    if (oldTag === tag) {
      vnode.state = old.state;
      vnode.events = old.events;
      if (shouldNotUpdate(vnode, old)) return;

      if (typeof oldTag === "string") {
        if (vnode.attrs != null) {
          updateLifecycle(vnode.attrs, vnode, hooks);
        }

        switch (oldTag) {
          case "#":
            updateText(old, vnode);
            break;

          case "<":
            updateHTML(parent, old, vnode, ns, nextSibling);
            break;

          case "[":
            updateFragment(parent, old, vnode, hooks, nextSibling, ns);
            break;

          default:
            updateElement(old, vnode, hooks, ns);
        }
      } else updateComponent(parent, old, vnode, hooks, nextSibling, ns);
    } else {
      removeNode(parent, old);
      createNode(parent, vnode, hooks, ns, nextSibling);
    }
  }

  function updateText(old, vnode) {
    if (old.children.toString() !== vnode.children.toString()) {
      old.dom.nodeValue = vnode.children;
    }

    vnode.dom = old.dom;
  }

  function updateHTML(parent, old, vnode, ns, nextSibling) {
    if (old.children !== vnode.children) {
      removeHTML(parent, old);
      createHTML(parent, vnode, ns, nextSibling);
    } else {
      vnode.dom = old.dom;
      vnode.domSize = old.domSize;
      vnode.instance = old.instance;
    }
  }

  function updateFragment(parent, old, vnode, hooks, nextSibling, ns) {
    updateNodes(parent, old.children, vnode.children, hooks, nextSibling, ns);
    var domSize = 0,
        children = vnode.children;
    vnode.dom = null;

    if (children != null) {
      for (var i = 0; i < children.length; i++) {
        var child = children[i];

        if (child != null && child.dom != null) {
          if (vnode.dom == null) vnode.dom = child.dom;
          domSize += child.domSize || 1;
        }
      }

      if (domSize !== 1) vnode.domSize = domSize;
    }
  }

  function updateElement(old, vnode, hooks, ns) {
    var element = vnode.dom = old.dom;
    ns = getNameSpace(vnode) || ns;

    if (vnode.tag === "textarea") {
      if (vnode.attrs == null) vnode.attrs = {};

      if (vnode.text != null) {
        vnode.attrs.value = vnode.text; //FIXME handle multiple children

        vnode.text = undefined;
      }
    }

    updateAttrs(vnode, old.attrs, vnode.attrs, ns);

    if (!maybeSetContentEditable(vnode)) {
      if (old.text != null && vnode.text != null && vnode.text !== "") {
        if (old.text.toString() !== vnode.text.toString()) old.dom.firstChild.nodeValue = vnode.text;
      } else {
        if (old.text != null) old.children = [Vnode("#", undefined, undefined, old.text, undefined, old.dom.firstChild)];
        if (vnode.text != null) vnode.children = [Vnode("#", undefined, undefined, vnode.text, undefined, undefined)];
        updateNodes(element, old.children, vnode.children, hooks, null, ns);
      }
    }
  }

  function updateComponent(parent, old, vnode, hooks, nextSibling, ns) {
    vnode.instance = Vnode.normalize(callHook.call(vnode.state.view, vnode));
    if (vnode.instance === vnode) throw Error("A view cannot return the vnode it received as argument");
    updateLifecycle(vnode.state, vnode, hooks);
    if (vnode.attrs != null) updateLifecycle(vnode.attrs, vnode, hooks);

    if (vnode.instance != null) {
      if (old.instance == null) createNode(parent, vnode.instance, hooks, ns, nextSibling);else updateNode(parent, old.instance, vnode.instance, hooks, nextSibling, ns);
      vnode.dom = vnode.instance.dom;
      vnode.domSize = vnode.instance.domSize;
    } else if (old.instance != null) {
      removeNode(parent, old.instance);
      vnode.dom = undefined;
      vnode.domSize = 0;
    } else {
      vnode.dom = old.dom;
      vnode.domSize = old.domSize;
    }
  }

  function getKeyMap(vnodes, start, end) {
    var map = Object.create(null);

    for (; start < end; start++) {
      var vnode = vnodes[start];

      if (vnode != null) {
        var key = vnode.key;
        if (key != null) map[key] = start;
      }
    }

    return map;
  } // Lifted from ivi https://github.com/ivijs/ivi/
  // takes a list of unique numbers (-1 is special and can
  // occur multiple times) and returns an array with the indices
  // of the items that are part of the longest increasing
  // subsequece


  var lisTemp = [];

  function makeLisIndices(a) {
    var result = [0];
    var u = 0,
        v = 0,
        i = 0;
    var il = lisTemp.length = a.length;

    for (var i = 0; i < il; i++) {
      lisTemp[i] = a[i];
    }

    for (var i = 0; i < il; ++i) {
      if (a[i] === -1) continue;
      var j = result[result.length - 1];

      if (a[j] < a[i]) {
        lisTemp[i] = j;
        result.push(i);
        continue;
      }

      u = 0;
      v = result.length - 1;

      while (u < v) {
        // Fast integer average without overflow.
        // eslint-disable-next-line no-bitwise
        var c = (u >>> 1) + (v >>> 1) + (u & v & 1);

        if (a[result[c]] < a[i]) {
          u = c + 1;
        } else {
          v = c;
        }
      }

      if (a[i] < a[result[u]]) {
        if (u > 0) lisTemp[i] = result[u - 1];
        result[u] = i;
      }
    }

    u = result.length;
    v = result[u - 1];

    while (u-- > 0) {
      result[u] = v;
      v = lisTemp[v];
    }

    lisTemp.length = 0;
    return result;
  }

  function getNextSibling(vnodes, i, nextSibling) {
    for (; i < vnodes.length; i++) {
      if (vnodes[i] != null && vnodes[i].dom != null) return vnodes[i].dom;
    }

    return nextSibling;
  } // This covers a really specific edge case:
  // - Parent node is keyed and contains child
  // - Child is removed, returns unresolved promise in `onbeforeremove`
  // - Parent node is moved in keyed diff
  // - Remaining children still need moved appropriately
  //
  // Ideally, I'd track removed nodes as well, but that introduces a lot more
  // complexity and I'm not exactly interested in doing that.


  function moveNodes(parent, vnode, nextSibling) {
    var frag = $doc.createDocumentFragment();
    moveChildToFrag(parent, frag, vnode);
    insertNode(parent, frag, nextSibling);
  }

  function moveChildToFrag(parent, frag, vnode) {
    // Dodge the recursion overhead in a few of the most common cases.
    while (vnode.dom != null && vnode.dom.parentNode === parent) {
      if (typeof vnode.tag !== "string") {
        vnode = vnode.instance;
        if (vnode != null) continue;
      } else if (vnode.tag === "<") {
        for (var i = 0; i < vnode.instance.length; i++) {
          frag.appendChild(vnode.instance[i]);
        }
      } else if (vnode.tag !== "[") {
        // Don't recurse for text nodes *or* elements, just fragments
        frag.appendChild(vnode.dom);
      } else if (vnode.children.length === 1) {
        vnode = vnode.children[0];
        if (vnode != null) continue;
      } else {
        for (var i = 0; i < vnode.children.length; i++) {
          var child = vnode.children[i];
          if (child != null) moveChildToFrag(parent, frag, child);
        }
      }

      break;
    }
  }

  function insertNode(parent, dom, nextSibling) {
    if (nextSibling != null) parent.insertBefore(dom, nextSibling);else parent.appendChild(dom);
  }

  function maybeSetContentEditable(vnode) {
    if (vnode.attrs == null || vnode.attrs.contenteditable == null && // attribute
    vnode.attrs.contentEditable == null // property
    ) return false;
    var children = vnode.children;

    if (children != null && children.length === 1 && children[0].tag === "<") {
      var content = children[0].children;
      if (vnode.dom.innerHTML !== content) vnode.dom.innerHTML = content;
    } else if (vnode.text != null || children != null && children.length !== 0) throw new Error("Child node of a contenteditable must be trusted");

    return true;
  } //remove


  function removeNodes(parent, vnodes, start, end) {
    for (var i = start; i < end; i++) {
      var vnode = vnodes[i];
      if (vnode != null) removeNode(parent, vnode);
    }
  }

  function removeNode(parent, vnode) {
    var mask = 0;
    var original = vnode.state;
    var stateResult, attrsResult;

    if (typeof vnode.tag !== "string" && typeof vnode.state.onbeforeremove === "function") {
      var result = callHook.call(vnode.state.onbeforeremove, vnode);

      if (result != null && typeof result.then === "function") {
        mask = 1;
        stateResult = result;
      }
    }

    if (vnode.attrs && typeof vnode.attrs.onbeforeremove === "function") {
      var result = callHook.call(vnode.attrs.onbeforeremove, vnode);

      if (result != null && typeof result.then === "function") {
        // eslint-disable-next-line no-bitwise
        mask |= 2;
        attrsResult = result;
      }
    }

    checkState(vnode, original); // If we can, try to fast-path it and avoid all the overhead of awaiting

    if (!mask) {
      onremove(vnode);
      removeChild(parent, vnode);
    } else {
      if (stateResult != null) {
        var next = function next() {
          // eslint-disable-next-line no-bitwise
          if (mask & 1) {
            mask &= 2;
            if (!mask) reallyRemove();
          }
        };

        stateResult.then(next, next);
      }

      if (attrsResult != null) {
        var next = function next() {
          // eslint-disable-next-line no-bitwise
          if (mask & 2) {
            mask &= 1;
            if (!mask) reallyRemove();
          }
        };

        attrsResult.then(next, next);
      }
    }

    function reallyRemove() {
      checkState(vnode, original);
      onremove(vnode);
      removeChild(parent, vnode);
    }
  }

  function removeHTML(parent, vnode) {
    for (var i = 0; i < vnode.instance.length; i++) {
      parent.removeChild(vnode.instance[i]);
    }
  }

  function removeChild(parent, vnode) {
    // Dodge the recursion overhead in a few of the most common cases.
    while (vnode.dom != null && vnode.dom.parentNode === parent) {
      if (typeof vnode.tag !== "string") {
        vnode = vnode.instance;
        if (vnode != null) continue;
      } else if (vnode.tag === "<") {
        removeHTML(parent, vnode);
      } else {
        if (vnode.tag !== "[") {
          parent.removeChild(vnode.dom);
          if (!Array.isArray(vnode.children)) break;
        }

        if (vnode.children.length === 1) {
          vnode = vnode.children[0];
          if (vnode != null) continue;
        } else {
          for (var i = 0; i < vnode.children.length; i++) {
            var child = vnode.children[i];
            if (child != null) removeChild(parent, child);
          }
        }
      }

      break;
    }
  }

  function onremove(vnode) {
    if (typeof vnode.tag !== "string" && typeof vnode.state.onremove === "function") callHook.call(vnode.state.onremove, vnode);
    if (vnode.attrs && typeof vnode.attrs.onremove === "function") callHook.call(vnode.attrs.onremove, vnode);

    if (typeof vnode.tag !== "string") {
      if (vnode.instance != null) onremove(vnode.instance);
    } else {
      var children = vnode.children;

      if (Array.isArray(children)) {
        for (var i = 0; i < children.length; i++) {
          var child = children[i];
          if (child != null) onremove(child);
        }
      }
    }
  } //attrs


  function setAttrs(vnode, attrs, ns) {
    for (var key in attrs) {
      setAttr(vnode, key, null, attrs[key], ns);
    }
  }

  function setAttr(vnode, key, old, value, ns) {
    if (key === "key" || key === "is" || value == null || isLifecycleMethod(key) || old === value && !isFormAttribute(vnode, key) && typeof value !== "object") return;
    if (key[0] === "o" && key[1] === "n") return updateEvent(vnode, key, value);
    if (key.slice(0, 6) === "xlink:") vnode.dom.setAttributeNS("http://www.w3.org/1999/xlink", key.slice(6), value);else if (key === "style") updateStyle(vnode.dom, old, value);else if (hasPropertyKey(vnode, key, ns)) {
      if (key === "value") {
        // Only do the coercion if we're actually going to check the value.

        /* eslint-disable no-implicit-coercion */
        //setting input[value] to same value by typing on focused element moves cursor to end in Chrome
        if ((vnode.tag === "input" || vnode.tag === "textarea") && vnode.dom.value === "" + value && vnode.dom === activeElement()) return; //setting select[value] to same value while having select open blinks select dropdown in Chrome

        if (vnode.tag === "select" && old !== null && vnode.dom.value === "" + value) return; //setting option[value] to same value while having select open blinks select dropdown in Chrome

        if (vnode.tag === "option" && old !== null && vnode.dom.value === "" + value) return;
        /* eslint-enable no-implicit-coercion */
      } // If you assign an input type that is not supported by IE 11 with an assignment expression, an error will occur.


      if (vnode.tag === "input" && key === "type") vnode.dom.setAttribute(key, value);else vnode.dom[key] = value;
    } else {
      if (typeof value === "boolean") {
        if (value) vnode.dom.setAttribute(key, "");else vnode.dom.removeAttribute(key);
      } else vnode.dom.setAttribute(key === "className" ? "class" : key, value);
    }
  }

  function removeAttr(vnode, key, old, ns) {
    if (key === "key" || key === "is" || old == null || isLifecycleMethod(key)) return;
    if (key[0] === "o" && key[1] === "n" && !isLifecycleMethod(key)) updateEvent(vnode, key, undefined);else if (key === "style") updateStyle(vnode.dom, old, null);else if (hasPropertyKey(vnode, key, ns) && key !== "className" && !(key === "value" && (vnode.tag === "option" || vnode.tag === "select" && vnode.dom.selectedIndex === -1 && vnode.dom === activeElement())) && !(vnode.tag === "input" && key === "type")) {
      vnode.dom[key] = null;
    } else {
      var nsLastIndex = key.indexOf(":");
      if (nsLastIndex !== -1) key = key.slice(nsLastIndex + 1);
      if (old !== false) vnode.dom.removeAttribute(key === "className" ? "class" : key);
    }
  }

  function setLateSelectAttrs(vnode, attrs) {
    if ("value" in attrs) {
      if (attrs.value === null) {
        if (vnode.dom.selectedIndex !== -1) vnode.dom.value = null;
      } else {
        var normalized = "" + attrs.value; // eslint-disable-line no-implicit-coercion

        if (vnode.dom.value !== normalized || vnode.dom.selectedIndex === -1) {
          vnode.dom.value = normalized;
        }
      }
    }

    if ("selectedIndex" in attrs) setAttr(vnode, "selectedIndex", null, attrs.selectedIndex, undefined);
  }

  function updateAttrs(vnode, old, attrs, ns) {
    if (attrs != null) {
      for (var key in attrs) {
        setAttr(vnode, key, old && old[key], attrs[key], ns);
      }
    }

    var val;

    if (old != null) {
      for (var key in old) {
        if ((val = old[key]) != null && (attrs == null || attrs[key] == null)) {
          removeAttr(vnode, key, val, ns);
        }
      }
    }
  }

  function isFormAttribute(vnode, attr) {
    return attr === "value" || attr === "checked" || attr === "selectedIndex" || attr === "selected" && vnode.dom === activeElement() || vnode.tag === "option" && vnode.dom.parentNode === $doc.activeElement;
  }

  function isLifecycleMethod(attr) {
    return attr === "oninit" || attr === "oncreate" || attr === "onupdate" || attr === "onremove" || attr === "onbeforeremove" || attr === "onbeforeupdate";
  }

  function hasPropertyKey(vnode, key, ns) {
    // Filter out namespaced keys
    return ns === undefined && ( // If it's a custom element, just keep it.
    vnode.tag.indexOf("-") > -1 || vnode.attrs != null && vnode.attrs.is || // If it's a normal element, let's try to avoid a few browser bugs.
    key !== "href" && key !== "list" && key !== "form" && key !== "width" && key !== "height" // && key !== "type"
    // Defer the property check until *after* we check everything.
    ) && key in vnode.dom;
  } //style


  var uppercaseRegex = /[A-Z]/g;

  function toLowerCase(capital) {
    return "-" + capital.toLowerCase();
  }

  function normalizeKey(key) {
    return key[0] === "-" && key[1] === "-" ? key : key === "cssFloat" ? "float" : key.replace(uppercaseRegex, toLowerCase);
  }

  function updateStyle(element, old, style) {
    if (old === style) {// Styles are equivalent, do nothing.
    } else if (style == null) {
      // New style is missing, just clear it.
      element.style.cssText = "";
    } else if (typeof style !== "object") {
      // New style is a string, let engine deal with patching.
      element.style.cssText = style;
    } else if (old == null || typeof old !== "object") {
      // `old` is missing or a string, `style` is an object.
      element.style.cssText = ""; // Add new style properties

      for (var key in style) {
        var value = style[key];
        if (value != null) element.style.setProperty(normalizeKey(key), String(value));
      }
    } else {
      // Both old & new are (different) objects.
      // Update style properties that have changed
      for (var key in style) {
        var value = style[key];

        if (value != null && (value = String(value)) !== String(old[key])) {
          element.style.setProperty(normalizeKey(key), value);
        }
      } // Remove style properties that no longer exist


      for (var key in old) {
        if (old[key] != null && style[key] == null) {
          element.style.removeProperty(normalizeKey(key));
        }
      }
    }
  } // Here's an explanation of how this works:
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
    this._ = currentRedraw;
  }

  EventDict.prototype = Object.create(null);

  EventDict.prototype.handleEvent = function (ev) {
    var handler = this["on" + ev.type];
    var result;
    if (typeof handler === "function") result = handler.call(ev.currentTarget, ev);else if (typeof handler.handleEvent === "function") handler.handleEvent(ev);
    if (this._ && ev.redraw !== false) (0, this._)();

    if (result === false) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  }; //event


  function updateEvent(vnode, key, value) {
    if (vnode.events != null) {
      if (vnode.events[key] === value) return;

      if (value != null && (typeof value === "function" || typeof value === "object")) {
        if (vnode.events[key] == null) vnode.dom.addEventListener(key.slice(2), vnode.events, false);
        vnode.events[key] = value;
      } else {
        if (vnode.events[key] != null) vnode.dom.removeEventListener(key.slice(2), vnode.events, false);
        vnode.events[key] = undefined;
      }
    } else if (value != null && (typeof value === "function" || typeof value === "object")) {
      vnode.events = new EventDict();
      vnode.dom.addEventListener(key.slice(2), vnode.events, false);
      vnode.events[key] = value;
    }
  } //lifecycle


  function initLifecycle(source, vnode, hooks) {
    if (typeof source.oninit === "function") callHook.call(source.oninit, vnode);
    if (typeof source.oncreate === "function") hooks.push(callHook.bind(source.oncreate, vnode));
  }

  function updateLifecycle(source, vnode, hooks) {
    if (typeof source.onupdate === "function") hooks.push(callHook.bind(source.onupdate, vnode));
  }

  function shouldNotUpdate(vnode, old) {
    do {
      if (vnode.attrs != null && typeof vnode.attrs.onbeforeupdate === "function") {
        var force = callHook.call(vnode.attrs.onbeforeupdate, vnode, old);
        if (force !== undefined && !force) break;
      }

      if (typeof vnode.tag !== "string" && typeof vnode.state.onbeforeupdate === "function") {
        var force = callHook.call(vnode.state.onbeforeupdate, vnode, old);
        if (force !== undefined && !force) break;
      }

      return false;
    } while (false); // eslint-disable-line no-constant-condition


    vnode.dom = old.dom;
    vnode.domSize = old.domSize;
    vnode.instance = old.instance; // One would think having the actual latest attributes would be ideal,
    // but it doesn't let us properly diff based on our current internal
    // representation. We have to save not only the old DOM info, but also
    // the attributes used to create it, as we diff *that*, not against the
    // DOM directly (with a few exceptions in `setAttr`). And, of course, we
    // need to save the children and text as they are conceptually not
    // unlike special "attributes" internally.

    vnode.attrs = old.attrs;
    vnode.children = old.children;
    vnode.text = old.text;
    return true;
  }

  return function (dom, vnodes, redraw) {
    if (!dom) throw new TypeError("Ensure the DOM element being passed to m.route/m.mount/m.render is not undefined.");
    var hooks = [];
    var active = activeElement();
    var namespace = dom.namespaceURI; // First time rendering into a node clears it out

    if (dom.vnodes == null) dom.textContent = "";
    vnodes = Vnode.normalizeChildren(Array.isArray(vnodes) ? vnodes : [vnodes]);
    var prevRedraw = currentRedraw;

    try {
      currentRedraw = typeof redraw === "function" ? redraw : undefined;
      updateNodes(dom, dom.vnodes, vnodes, hooks, null, namespace === "http://www.w3.org/1999/xhtml" ? undefined : namespace);
    } finally {
      currentRedraw = prevRedraw;
    }

    dom.vnodes = vnodes; // `document.activeElement` can return null: https://html.spec.whatwg.org/multipage/interaction.html#dom-document-activeelement

    if (active != null && activeElement() !== active && typeof active.focus === "function") active.focus();

    for (var i = 0; i < hooks.length; i++) {
      hooks[i]();
    }
  };
};

/***/ }),

/***/ "./node_modules/mithril/render/trust.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/render/trust.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var Vnode = __webpack_require__(/*! ../render/vnode */ "./node_modules/mithril/render/vnode.js");

module.exports = function (html) {
  if (html == null) html = "";
  return Vnode("<", undefined, undefined, html, undefined, undefined);
};

/***/ }),

/***/ "./node_modules/mithril/render/vnode.js":
/*!**********************************************!*\
  !*** ./node_modules/mithril/render/vnode.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function Vnode(tag, key, attrs, children, text, dom) {
  return {
    tag: tag,
    key: key,
    attrs: attrs,
    children: children,
    text: text,
    dom: dom,
    domSize: undefined,
    state: undefined,
    events: undefined,
    instance: undefined
  };
}

Vnode.normalize = function (node) {
  if (Array.isArray(node)) return Vnode("[", undefined, undefined, Vnode.normalizeChildren(node), undefined, undefined);
  if (node == null || typeof node === "boolean") return null;
  if (typeof node === "object") return node;
  return Vnode("#", undefined, undefined, String(node), undefined, undefined);
};

Vnode.normalizeChildren = function (input) {
  var children = [];

  if (input.length) {
    var isKeyed = input[0] != null && input[0].key != null; // Note: this is a *very* perf-sensitive check.
    // Fun fact: merging the loop like this is somehow faster than splitting
    // it, noticeably so.

    for (var i = 1; i < input.length; i++) {
      if ((input[i] != null && input[i].key != null) !== isKeyed) {
        throw new TypeError("Vnodes must either always have keys or never have keys!");
      }
    }

    for (var i = 0; i < input.length; i++) {
      children[i] = Vnode.normalize(input[i]);
    }
  }

  return children;
};

module.exports = Vnode;

/***/ }),

/***/ "./node_modules/mithril/request.js":
/*!*****************************************!*\
  !*** ./node_modules/mithril/request.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var PromisePolyfill = __webpack_require__(/*! ./promise/promise */ "./node_modules/mithril/promise/promise.js");

var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js");

module.exports = __webpack_require__(/*! ./request/request */ "./node_modules/mithril/request/request.js")(window, PromisePolyfill, mountRedraw.redraw);

/***/ }),

/***/ "./node_modules/mithril/request/request.js":
/*!*************************************************!*\
  !*** ./node_modules/mithril/request/request.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var buildPathname = __webpack_require__(/*! ../pathname/build */ "./node_modules/mithril/pathname/build.js");

module.exports = function ($window, Promise, oncompletion) {
  var callbackCount = 0;

  function PromiseProxy(executor) {
    return new Promise(executor);
  } // In case the global Promise is some userland library's where they rely on
  // `foo instanceof this.constructor`, `this.constructor.resolve(value)`, or
  // similar. Let's *not* break them.


  PromiseProxy.prototype = Promise.prototype;
  PromiseProxy.__proto__ = Promise; // eslint-disable-line no-proto

  function makeRequest(factory) {
    return function (url, args) {
      if (typeof url !== "string") {
        args = url;
        url = url.url;
      } else if (args == null) args = {};

      var promise = new Promise(function (resolve, reject) {
        factory(buildPathname(url, args.params), args, function (data) {
          if (typeof args.type === "function") {
            if (Array.isArray(data)) {
              for (var i = 0; i < data.length; i++) {
                data[i] = new args.type(data[i]);
              }
            } else data = new args.type(data);
          }

          resolve(data);
        }, reject);
      });
      if (args.background === true) return promise;
      var count = 0;

      function complete() {
        if (--count === 0 && typeof oncompletion === "function") oncompletion();
      }

      return wrap(promise);

      function wrap(promise) {
        var then = promise.then; // Set the constructor, so engines know to not await or resolve
        // this as a native promise. At the time of writing, this is
        // only necessary for V8, but their behavior is the correct
        // behavior per spec. See this spec issue for more details:
        // https://github.com/tc39/ecma262/issues/1577. Also, see the
        // corresponding comment in `request/tests/test-request.js` for
        // a bit more background on the issue at hand.

        promise.constructor = PromiseProxy;

        promise.then = function () {
          count++;
          var next = then.apply(promise, arguments);
          next.then(complete, function (e) {
            complete();
            if (count === 0) throw e;
          });
          return wrap(next);
        };

        return promise;
      }
    };
  }

  function hasHeader(args, name) {
    for (var key in args.headers) {
      if ({}.hasOwnProperty.call(args.headers, key) && name.test(key)) return true;
    }

    return false;
  }

  return {
    request: makeRequest(function (url, args, resolve, reject) {
      var method = args.method != null ? args.method.toUpperCase() : "GET";
      var body = args.body;
      var assumeJSON = (args.serialize == null || args.serialize === JSON.serialize) && !(body instanceof $window.FormData);
      var responseType = args.responseType || (typeof args.extract === "function" ? "" : "json");
      var xhr = new $window.XMLHttpRequest(),
          aborted = false;
      var original = xhr,
          replacedAbort;
      var abort = xhr.abort;

      xhr.abort = function () {
        aborted = true;
        abort.call(this);
      };

      xhr.open(method, url, args.async !== false, typeof args.user === "string" ? args.user : undefined, typeof args.password === "string" ? args.password : undefined);

      if (assumeJSON && body != null && !hasHeader(args, /^content-type$/i)) {
        xhr.setRequestHeader("Content-Type", "application/json; charset=utf-8");
      }

      if (typeof args.deserialize !== "function" && !hasHeader(args, /^accept$/i)) {
        xhr.setRequestHeader("Accept", "application/json, text/*");
      }

      if (args.withCredentials) xhr.withCredentials = args.withCredentials;
      if (args.timeout) xhr.timeout = args.timeout;
      xhr.responseType = responseType;

      for (var key in args.headers) {
        if ({}.hasOwnProperty.call(args.headers, key)) {
          xhr.setRequestHeader(key, args.headers[key]);
        }
      }

      xhr.onreadystatechange = function (ev) {
        // Don't throw errors on xhr.abort().
        if (aborted) return;

        if (ev.target.readyState === 4) {
          try {
            var success = ev.target.status >= 200 && ev.target.status < 300 || ev.target.status === 304 || /^file:\/\//i.test(url); // When the response type isn't "" or "text",
            // `xhr.responseText` is the wrong thing to use.
            // Browsers do the right thing and throw here, and we
            // should honor that and do the right thing by
            // preferring `xhr.response` where possible/practical.

            var response = ev.target.response,
                message;

            if (responseType === "json") {
              // For IE and Edge, which don't implement
              // `responseType: "json"`.
              if (!ev.target.responseType && typeof args.extract !== "function") response = JSON.parse(ev.target.responseText);
            } else if (!responseType || responseType === "text") {
              // Only use this default if it's text. If a parsed
              // document is needed on old IE and friends (all
              // unsupported), the user should use a custom
              // `config` instead. They're already using this at
              // their own risk.
              if (response == null) response = ev.target.responseText;
            }

            if (typeof args.extract === "function") {
              response = args.extract(ev.target, args);
              success = true;
            } else if (typeof args.deserialize === "function") {
              response = args.deserialize(response);
            }

            if (success) resolve(response);else {
              try {
                message = ev.target.responseText;
              } catch (e) {
                message = response;
              }

              var error = new Error(message);
              error.code = ev.target.status;
              error.response = response;
              reject(error);
            }
          } catch (e) {
            reject(e);
          }
        }
      };

      if (typeof args.config === "function") {
        xhr = args.config(xhr, args, url) || xhr; // Propagate the `abort` to any replacement XHR as well.

        if (xhr !== original) {
          replacedAbort = xhr.abort;

          xhr.abort = function () {
            aborted = true;
            replacedAbort.call(this);
          };
        }
      }

      if (body == null) xhr.send();else if (typeof args.serialize === "function") xhr.send(args.serialize(body));else if (body instanceof $window.FormData) xhr.send(body);else xhr.send(JSON.stringify(body));
    }),
    jsonp: makeRequest(function (url, args, resolve, reject) {
      var callbackName = args.callbackName || "_mithril_" + Math.round(Math.random() * 1e16) + "_" + callbackCount++;
      var script = $window.document.createElement("script");

      $window[callbackName] = function (data) {
        delete $window[callbackName];
        script.parentNode.removeChild(script);
        resolve(data);
      };

      script.onerror = function () {
        delete $window[callbackName];
        script.parentNode.removeChild(script);
        reject(new Error("JSONP request failed"));
      };

      script.src = url + (url.indexOf("?") < 0 ? "?" : "&") + encodeURIComponent(args.callbackKey || "callback") + "=" + encodeURIComponent(callbackName);
      $window.document.documentElement.appendChild(script);
    })
  };
};

/***/ }),

/***/ "./node_modules/mithril/route.js":
/*!***************************************!*\
  !*** ./node_modules/mithril/route.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var mountRedraw = __webpack_require__(/*! ./mount-redraw */ "./node_modules/mithril/mount-redraw.js");

module.exports = __webpack_require__(/*! ./api/router */ "./node_modules/mithril/api/router.js")(window, mountRedraw);

/***/ }),

/***/ "./node_modules/process/browser.js":
/*!*****************************************!*\
  !*** ./node_modules/process/browser.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

// shim for using process in browser
var process = module.exports = {}; // cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
  throw new Error('setTimeout has not been defined');
}

function defaultClearTimeout() {
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
})();

function runTimeout(fun) {
  if (cachedSetTimeout === setTimeout) {
    //normal enviroments in sane situations
    return setTimeout(fun, 0);
  } // if setTimeout wasn't available but was latter defined


  if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
    cachedSetTimeout = setTimeout;
    return setTimeout(fun, 0);
  }

  try {
    // when when somebody has screwed with setTimeout but no I.E. maddness
    return cachedSetTimeout(fun, 0);
  } catch (e) {
    try {
      // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
      return cachedSetTimeout.call(null, fun, 0);
    } catch (e) {
      // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
      return cachedSetTimeout.call(this, fun, 0);
    }
  }
}

function runClearTimeout(marker) {
  if (cachedClearTimeout === clearTimeout) {
    //normal enviroments in sane situations
    return clearTimeout(marker);
  } // if clearTimeout wasn't available but was latter defined


  if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
    cachedClearTimeout = clearTimeout;
    return clearTimeout(marker);
  }

  try {
    // when when somebody has screwed with setTimeout but no I.E. maddness
    return cachedClearTimeout(marker);
  } catch (e) {
    try {
      // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
      return cachedClearTimeout.call(null, marker);
    } catch (e) {
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

  while (len) {
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
}; // v8 likes predictible objects


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

process.listeners = function (name) {
  return [];
};

process.binding = function (name) {
  throw new Error('process.binding is not supported');
};

process.cwd = function () {
  return '/';
};

process.chdir = function (dir) {
  throw new Error('process.chdir is not supported');
};

process.umask = function () {
  return 0;
};

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
    } // Copy function arguments


    var args = new Array(arguments.length - 1);

    for (var i = 0; i < args.length; i++) {
      args[i] = arguments[i + 1];
    } // Store and register the task


    var task = {
      callback: callback,
      args: args
    };
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
    registerImmediate = function registerImmediate(handle) {
      process.nextTick(function () {
        runIfPresent(handle);
      });
    };
  }

  function canUsePostMessage() {
    // The test against `importScripts` prevents this implementation from being installed inside a web worker,
    // where `global.postMessage` means something completely different and can't be used for this purpose.
    if (global.postMessage && !global.importScripts) {
      var postMessageIsAsynchronous = true;
      var oldOnMessage = global.onmessage;

      global.onmessage = function () {
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

    var onGlobalMessage = function onGlobalMessage(event) {
      if (event.source === global && typeof event.data === "string" && event.data.indexOf(messagePrefix) === 0) {
        runIfPresent(+event.data.slice(messagePrefix.length));
      }
    };

    if (global.addEventListener) {
      global.addEventListener("message", onGlobalMessage, false);
    } else {
      global.attachEvent("onmessage", onGlobalMessage);
    }

    registerImmediate = function registerImmediate(handle) {
      global.postMessage(messagePrefix + handle, "*");
    };
  }

  function installMessageChannelImplementation() {
    var channel = new MessageChannel();

    channel.port1.onmessage = function (event) {
      var handle = event.data;
      runIfPresent(handle);
    };

    registerImmediate = function registerImmediate(handle) {
      channel.port2.postMessage(handle);
    };
  }

  function installReadyStateChangeImplementation() {
    var html = doc.documentElement;

    registerImmediate = function registerImmediate(handle) {
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
    registerImmediate = function registerImmediate(handle) {
      setTimeout(runIfPresent, 0, handle);
    };
  } // If supported, we should attach to the prototype of global, since that is where setTimeout et al. live.


  var attachTo = Object.getPrototypeOf && Object.getPrototypeOf(global);
  attachTo = attachTo && attachTo.setTimeout ? attachTo : global; // Don't get fooled by e.g. browserify environments.

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
})(typeof self === "undefined" ? typeof global === "undefined" ? this : global : self);
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js"), __webpack_require__(/*! ./../process/browser.js */ "./node_modules/process/browser.js")))

/***/ }),

/***/ "./node_modules/timers-browserify/main.js":
/*!************************************************!*\
  !*** ./node_modules/timers-browserify/main.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {var scope = typeof global !== "undefined" && global || typeof self !== "undefined" && self || window;
var apply = Function.prototype.apply; // DOM APIs, for completeness

exports.setTimeout = function () {
  return new Timeout(apply.call(setTimeout, scope, arguments), clearTimeout);
};

exports.setInterval = function () {
  return new Timeout(apply.call(setInterval, scope, arguments), clearInterval);
};

exports.clearTimeout = exports.clearInterval = function (timeout) {
  if (timeout) {
    timeout.close();
  }
};

function Timeout(id, clearFn) {
  this._id = id;
  this._clearFn = clearFn;
}

Timeout.prototype.unref = Timeout.prototype.ref = function () {};

Timeout.prototype.close = function () {
  this._clearFn.call(scope, this._id);
}; // Does not start the time, just sets up the members needed.


exports.enroll = function (item, msecs) {
  clearTimeout(item._idleTimeoutId);
  item._idleTimeout = msecs;
};

exports.unenroll = function (item) {
  clearTimeout(item._idleTimeoutId);
  item._idleTimeout = -1;
};

exports._unrefActive = exports.active = function (item) {
  clearTimeout(item._idleTimeoutId);
  var msecs = item._idleTimeout;

  if (msecs >= 0) {
    item._idleTimeoutId = setTimeout(function onTimeout() {
      if (item._onTimeout) item._onTimeout();
    }, msecs);
  }
}; // setimmediate attaches itself to the global object


__webpack_require__(/*! setimmediate */ "./node_modules/setimmediate/setImmediate.js"); // On some exotic environments, it's not clear which object `setimmediate` was
// able to install onto.  Search each possibility in the same order as the
// `setimmediate` library.


exports.setImmediate = typeof self !== "undefined" && self.setImmediate || typeof global !== "undefined" && global.setImmediate || this && this.setImmediate;
exports.clearImmediate = typeof self !== "undefined" && self.clearImmediate || typeof global !== "undefined" && global.clearImmediate || this && this.clearImmediate;
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/webpack/buildin/global.js":
/*!***********************************!*\
  !*** (webpack)/buildin/global.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

var g; // This works in non-strict mode

g = function () {
  return this;
}();

try {
  // This works if eval is allowed (see CSP)
  g = g || new Function("return this")();
} catch (e) {
  // This works if the window reference is available
  if (typeof window === "object") g = window;
} // g can still be undefined, but nothing to do about it...
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
;

(function ($) {
  var data = {},
      dataAttr = $.fn.data,
      camelize = $.camelCase,
      exp = $.expando = 'Zepto' + +new Date(),
      emptyArray = []; // Get value from node:
  // 1. first try key as given,
  // 2. then try camelized key,
  // 3. fall back to reading "data-*" attribute.

  function getData(node, name) {
    var id = node[exp],
        store = id && data[id];
    if (name === undefined) return store || setData(node);else {
      if (store) {
        if (name in store) return store[name];
        var camelName = camelize(name);
        if (camelName in store) return store[camelName];
      }

      return dataAttr.call($(node), name);
    }
  } // Store value under camelized key on node


  function setData(node, name, value) {
    var id = node[exp] || (node[exp] = ++$.uuid),
        store = data[id] || (data[id] = attributeData(node));
    if (name !== undefined) store[camelize(name)] = value;
    return store;
  } // Read all "data-*" attributes from a node


  function attributeData(node) {
    var store = {};
    $.each(node.attributes || emptyArray, function (i, attr) {
      if (attr.name.indexOf('data-') == 0) store[camelize(attr.name.replace('data-', ''))] = $.zepto.deserializeValue(attr.value);
    });
    return store;
  }

  $.fn.data = function (name, value) {
    return value === undefined ? // set multiple values via object
    $.isPlainObject(name) ? this.each(function (i, node) {
      $.each(name, function (key, value) {
        setData(node, key, value);
      });
    }) : // get value from first element
    0 in this ? getData(this[0], name) : undefined : // set value on all elements
    this.each(function () {
      setData(this, name, value);
    });
  };

  $.data = function (elem, name, value) {
    return $(elem).data(name, value);
  };

  $.hasData = function (elem) {
    var id = elem[exp],
        store = id && data[id];
    return store ? !$.isEmptyObject(store) : false;
  };

  $.fn.removeData = function (names) {
    if (typeof names == 'string') names = names.split(/\s+/);
    return this.each(function () {
      var id = this[exp],
          store = id && data[id];
      if (store) $.each(names || store, function (key) {
        delete store[names ? camelize(this) : key];
      });
    });
  } // Generate extended `remove` and `empty` functions
  ;

  ['remove', 'empty'].forEach(function (methodName) {
    var origFn = $.fn[methodName];

    $.fn[methodName] = function () {
      var elements = this.find('*');
      if (methodName === 'remove') elements = elements.add(this);
      elements.removeData();
      return origFn.call(this);
    };
  });
})(Zepto);

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
;

(function ($, undefined) {
  var prefix = '',
      eventPrefix,
      vendors = {
    Webkit: 'webkit',
    Moz: '',
    O: 'o'
  },
      testEl = document.createElement('div'),
      supportedTransforms = /^((translate|rotate|scale)(X|Y|Z|3d)?|matrix(3d)?|perspective|skew(X|Y)?)$/i,
      transform,
      transitionProperty,
      transitionDuration,
      transitionTiming,
      transitionDelay,
      animationName,
      animationDuration,
      animationTiming,
      animationDelay,
      cssReset = {};

  function dasherize(str) {
    return str.replace(/([A-Z])/g, '-$1').toLowerCase();
  }

  function normalizeEvent(name) {
    return eventPrefix ? eventPrefix + name : name.toLowerCase();
  }

  if (testEl.style.transform === undefined) $.each(vendors, function (vendor, event) {
    if (testEl.style[vendor + 'TransitionProperty'] !== undefined) {
      prefix = '-' + vendor.toLowerCase() + '-';
      eventPrefix = event;
      return false;
    }
  });
  transform = prefix + 'transform';
  cssReset[transitionProperty = prefix + 'transition-property'] = cssReset[transitionDuration = prefix + 'transition-duration'] = cssReset[transitionDelay = prefix + 'transition-delay'] = cssReset[transitionTiming = prefix + 'transition-timing-function'] = cssReset[animationName = prefix + 'animation-name'] = cssReset[animationDuration = prefix + 'animation-duration'] = cssReset[animationDelay = prefix + 'animation-delay'] = cssReset[animationTiming = prefix + 'animation-timing-function'] = '';
  $.fx = {
    off: eventPrefix === undefined && testEl.style.transitionProperty === undefined,
    speeds: {
      _default: 400,
      fast: 200,
      slow: 600
    },
    cssPrefix: prefix,
    transitionEnd: normalizeEvent('TransitionEnd'),
    animationEnd: normalizeEvent('AnimationEnd')
  };

  $.fn.animate = function (properties, duration, ease, callback, delay) {
    if ($.isFunction(duration)) callback = duration, ease = undefined, duration = undefined;
    if ($.isFunction(ease)) callback = ease, ease = undefined;
    if ($.isPlainObject(duration)) ease = duration.easing, callback = duration.complete, delay = duration.delay, duration = duration.duration;
    if (duration) duration = (typeof duration == 'number' ? duration : $.fx.speeds[duration] || $.fx.speeds._default) / 1000;
    if (delay) delay = parseFloat(delay) / 1000;
    return this.anim(properties, duration, ease, callback, delay);
  };

  $.fn.anim = function (properties, duration, ease, callback, delay) {
    var key,
        cssValues = {},
        cssProperties,
        transforms = '',
        that = this,
        _wrappedCallback,
        endEvent = $.fx.transitionEnd,
        fired = false;

    if (duration === undefined) duration = $.fx.speeds._default / 1000;
    if (delay === undefined) delay = 0;
    if ($.fx.off) duration = 0;

    if (typeof properties == 'string') {
      // keyframe animation
      cssValues[animationName] = properties;
      cssValues[animationDuration] = duration + 's';
      cssValues[animationDelay] = delay + 's';
      cssValues[animationTiming] = ease || 'linear';
      endEvent = $.fx.animationEnd;
    } else {
      cssProperties = []; // CSS transitions

      for (key in properties) {
        if (supportedTransforms.test(key)) transforms += key + '(' + properties[key] + ') ';else cssValues[key] = properties[key], cssProperties.push(dasherize(key));
      }

      if (transforms) cssValues[transform] = transforms, cssProperties.push(transform);

      if (duration > 0 && typeof properties === 'object') {
        cssValues[transitionProperty] = cssProperties.join(', ');
        cssValues[transitionDuration] = duration + 's';
        cssValues[transitionDelay] = delay + 's';
        cssValues[transitionTiming] = ease || 'linear';
      }
    }

    _wrappedCallback = function wrappedCallback(event) {
      if (typeof event !== 'undefined') {
        if (event.target !== event.currentTarget) return; // makes sure the event didn't bubble from "below"

        $(event.target).unbind(endEvent, _wrappedCallback);
      } else $(this).unbind(endEvent, _wrappedCallback); // triggered by setTimeout


      fired = true;
      $(this).css(cssReset);
      callback && callback.call(this);
    };

    if (duration > 0) {
      this.bind(endEvent, _wrappedCallback); // transitionEnd is not always firing on older Android phones
      // so make sure it gets fired

      setTimeout(function () {
        if (fired) return;

        _wrappedCallback.call(that);
      }, (duration + delay) * 1000 + 25);
    } // trigger page reflow so new elements can animate


    this.size() && this.get(0).clientLeft;
    this.css(cssValues);
    if (duration <= 0) setTimeout(function () {
      that.each(function () {
        _wrappedCallback.call(this);
      });
    }, 0);
    return this;
  };

  testEl = null;
})(Zepto);

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
;

(function ($, undefined) {
  var document = window.document,
      docElem = document.documentElement,
      origShow = $.fn.show,
      origHide = $.fn.hide,
      origToggle = $.fn.toggle;

  function anim(el, speed, opacity, scale, callback) {
    if (typeof speed == 'function' && !callback) callback = speed, speed = undefined;
    var props = {
      opacity: opacity
    };

    if (scale) {
      props.scale = scale;
      el.css($.fx.cssPrefix + 'transform-origin', '0 0');
    }

    return el.animate(props, speed, null, callback);
  }

  function hide(el, speed, scale, callback) {
    return anim(el, speed, 0, scale, function () {
      origHide.call($(this));
      callback && callback.call(this);
    });
  }

  $.fn.show = function (speed, callback) {
    origShow.call(this);
    if (speed === undefined) speed = 0;else this.css('opacity', 0);
    return anim(this, speed, 1, '1,1', callback);
  };

  $.fn.hide = function (speed, callback) {
    if (speed === undefined) return origHide.call(this);else return hide(this, speed, '0,0', callback);
  };

  $.fn.toggle = function (speed, callback) {
    if (speed === undefined || typeof speed == 'boolean') return origToggle.call(this, speed);else return this.each(function () {
      var el = $(this);
      el[el.css('display') == 'none' ? 'show' : 'hide'](speed, callback);
    });
  };

  $.fn.fadeTo = function (speed, opacity, callback) {
    return anim(this, speed, opacity, null, callback);
  };

  $.fn.fadeIn = function (speed, callback) {
    var target = this.css('opacity');
    if (target > 0) this.css('opacity', 0);else target = 1;
    return origShow.call(this).fadeTo(speed, target, callback);
  };

  $.fn.fadeOut = function (speed, callback) {
    return hide(this, speed, null, callback);
  };

  $.fn.fadeToggle = function (speed, callback) {
    return this.each(function () {
      var el = $(this);
      el[el.css('opacity') == 0 || el.css('display') == 'none' ? 'fadeIn' : 'fadeOut'](speed, callback);
    });
  };
})(Zepto);

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
;

(function ($) {
  var zepto = $.zepto,
      oldQsa = zepto.qsa,
      oldMatches = zepto.matches;

  function _visible(elem) {
    elem = $(elem);
    return !!(elem.width() || elem.height()) && elem.css("display") !== "none";
  } // Implements a subset from:
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
    visible: function visible() {
      if (_visible(this)) return this;
    },
    hidden: function hidden() {
      if (!_visible(this)) return this;
    },
    selected: function selected() {
      if (this.selected) return this;
    },
    checked: function checked() {
      if (this.checked) return this;
    },
    parent: function parent() {
      return this.parentNode;
    },
    first: function first(idx) {
      if (idx === 0) return this;
    },
    last: function last(idx, nodes) {
      if (idx === nodes.length - 1) return this;
    },
    eq: function eq(idx, _, value) {
      if (idx === value) return this;
    },
    contains: function contains(idx, _, text) {
      if ($(this).text().indexOf(text) > -1) return this;
    },
    has: function has(idx, _, sel) {
      if (zepto.qsa(this, sel).length) return this;
    }
  };
  var filterRe = new RegExp('(.*):(\\w+)(?:\\(([^)]+)\\))?$\\s*'),
      childRe = /^\s*>/,
      classTag = 'Zepto' + +new Date();

  function process(sel, fn) {
    // quote the hash in `a[href^=#]` expression
    sel = sel.replace(/=#\]/g, '="#"]');
    var filter,
        arg,
        match = filterRe.exec(sel);

    if (match && match[2] in filters) {
      filter = filters[match[2]], arg = match[3];
      sel = match[1];

      if (arg) {
        var num = Number(arg);
        if (isNaN(num)) arg = arg.replace(/^["']|["']$/g, '');else arg = num;
      }
    }

    return fn(sel, filter, arg);
  }

  zepto.qsa = function (node, selector) {
    return process(selector, function (sel, filter, arg) {
      try {
        var taggedParent;
        if (!sel && filter) sel = '*';else if (childRe.test(sel)) // support "> *" child queries by tagging the parent node with a
          // unique class and prepending that classname onto the selector
          taggedParent = $(node).addClass(classTag), sel = '.' + classTag + ' ' + sel;
        var nodes = oldQsa(node, sel);
      } catch (e) {
        console.error('error performing selector: %o', selector);
        throw e;
      } finally {
        if (taggedParent) taggedParent.removeClass(classTag);
      }

      return !filter ? nodes : zepto.uniq($.map(nodes, function (n, i) {
        return filter.call(n, i, nodes, arg);
      }));
    });
  };

  zepto.matches = function (node, selector) {
    return process(selector, function (sel, filter, arg) {
      return (!sel || oldMatches(node, sel)) && (!filter || filter.call(node, null, arg) === node);
    });
  };
})(Zepto);

/***/ }),

/***/ "./src/admin/index.js":
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
/*! no static exports found */
/***/ (function(module, exports) {



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
/* harmony import */ var zepto_src_selector__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! zepto/src/selector */ "./node_modules/zepto/src/selector.js");
/* harmony import */ var zepto_src_selector__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(zepto_src_selector__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var zepto_src_data__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! zepto/src/data */ "./node_modules/zepto/src/data.js");
/* harmony import */ var zepto_src_data__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(zepto_src_data__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var zepto_src_fx__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! zepto/src/fx */ "./node_modules/zepto/src/fx.js");
/* harmony import */ var zepto_src_fx__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(zepto_src_fx__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! zepto/src/fx_methods */ "./node_modules/zepto/src/fx_methods.js");
/* harmony import */ var zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(zepto_src_fx_methods__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! dayjs/plugin/relativeTime */ "./node_modules/dayjs/plugin/relativeTime.js");
/* harmony import */ var dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! dayjs/plugin/localizedFormat */ "./node_modules/dayjs/plugin/localizedFormat.js");
/* harmony import */ var dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! dayjs */ "./node_modules/dayjs/dayjs.min.js");
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(dayjs__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var _utils_patchMithril__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./utils/patchMithril */ "./src/common/utils/patchMithril.ts");








 // import './utils/patchZepto';
// import 'hc-sticky';
// import 'bootstrap/js/dropdown';
// import 'bootstrap/js/transition';




dayjs__WEBPACK_IMPORTED_MODULE_11___default.a.extend(dayjs_plugin_relativeTime__WEBPACK_IMPORTED_MODULE_9___default.a);
dayjs__WEBPACK_IMPORTED_MODULE_11___default.a.extend(dayjs_plugin_localizedFormat__WEBPACK_IMPORTED_MODULE_10___default.a);

Object(_utils_patchMithril__WEBPACK_IMPORTED_MODULE_12__["default"])(window); // import * as Extend from './extend/index';
// export { Extend };

/***/ }),

/***/ "./src/common/utils/patchMithril.ts":
/*!******************************************!*\
  !*** ./src/common/utils/patchMithril.ts ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return patchMithril; });
function patchMithril(global) {//   const mo = global.m;
  //   const m = function(comp, ...args) {
  //     console.log('m', comp, ...args);
  //     if (comp.prototype && comp.prototype instanceof Component) {
  //       let children = args.slice(1);
  //       if (children.length === 1 && Array.isArray(children[0])) {
  //         children = children[0]
  //       }
  //       return comp.component(args[0], children);
  //     }
  //     const node = mo.apply(this, arguments);
  //     if (node.attrs.bidi) {
  //       m.bidi(node, node.attrs.bidi);
  //     }
  //     if (node.attrs.route) {
  //       node.attrs.href = node.attrs.route;
  //       node.attrs.config = m.route;
  //       delete node.attrs.route;
  //     }
  //     return node;
  //   };
  //   Object.keys(mo).forEach(key => m[key] = mo[key]);
  // //   /**
  // //    * Redraw only if not in the middle of a computation (e.g. a route change).
  // //    *
  // //    * @return {void}
  // //    */
  // //   m.lazyRedraw = function() {
  // //     m.startComputation();
  // //     m.endComputation();
  // //   };
  //   global.m = m;
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