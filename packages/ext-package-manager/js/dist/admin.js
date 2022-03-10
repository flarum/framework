module.exports =
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
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin.js":
/*!******************!*\
  !*** ./admin.js ***!
  \******************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.tsx");
/* empty/unused harmony star reexport */

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/createClass.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/createClass.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _createClass; });
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
/* harmony import */ var _setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setPrototypeOf.js */ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js");

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  Object(_setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__["default"])(subClass, superClass);
}

/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return _setPrototypeOf; });
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

/***/ }),

/***/ "./src/admin/components/ExtensionItem.tsx":
/*!************************************************!*\
  !*** ./src/admin/components/ExtensionItem.tsx ***!
  \************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ExtensionItem; });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/helpers/icon */ "flarum/common/helpers/icon");
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _WhyNotModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./WhyNotModal */ "./src/admin/components/WhyNotModal.tsx");








/*
 * @todo fix in core
 */

var ExtensionItem = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(ExtensionItem, _Component);

  function ExtensionItem() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = ExtensionItem.prototype;

  _proto.view = function view(vnode) {
    var _this$attrs = this.attrs,
        extension = _this$attrs.extension,
        updates = _this$attrs.updates,
        onClickUpdate = _this$attrs.onClickUpdate,
        whyNotWarning = _this$attrs.whyNotWarning,
        isCore = _this$attrs.isCore,
        isDanger = _this$attrs.isDanger;
    return m("div", {
      className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()({
        'PackageManager-extension': true,
        'PackageManager-extension--core': isCore,
        'PackageManager-extension--danger': isDanger
      })
    }, m("div", {
      className: "PackageManager-extension-icon ExtensionIcon",
      style: extension.icon
    }, extension.icon ? flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_4___default()(extension.icon.name) : ''), m("div", {
      className: "PackageManager-extension-info"
    }, m("div", {
      className: "PackageManager-extension-name"
    }, extension.extra['flarum-extension'].title), m("div", {
      className: "PackageManager-extension-version"
    }, m("span", {
      className: "PackageManager-extension-version-current"
    }, this.version(extension.version)), updates['latest-minor'] ? m("span", {
      className: "PackageManager-extension-version-latest PackageManager-extension-version-latest--minor"
    }, this.version(updates['latest-minor'])) : null, updates['latest-major'] && !isCore ? m("span", {
      className: "PackageManager-extension-version-latest PackageManager-extension-version-latest--major"
    }, this.version(updates['latest-major'])) : null)), m("div", {
      className: "PackageManager-extension-controls"
    }, onClickUpdate ? m(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5___default.a, {
      text: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.update')
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_6___default.a, {
      icon: "fas fa-arrow-alt-circle-up",
      className: "Button Button--icon Button--flat",
      onclick: onClickUpdate,
      "aria-label": flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.update')
    })) : null, whyNotWarning ? m(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_5___default.a, {
      text: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.check_why_it_failed_updating')
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_6___default.a, {
      icon: "fas fa-exclamation-circle",
      className: "Button Button--icon Button--flat Button--danger",
      onclick: function onclick() {
        return flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(_WhyNotModal__WEBPACK_IMPORTED_MODULE_7__["default"], {
          "package": extension.name
        });
      },
      "aria-label": flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.check_why_it_failed_updating')
    })) : null));
  };

  _proto.version = function version(v) {
    return 'v' + v.replace('v', '');
  };

  return ExtensionItem;
}(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default.a);



/***/ }),

/***/ "./src/admin/components/Installer.tsx":
/*!********************************************!*\
  !*** ./src/admin/components/Installer.tsx ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Installer; });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/Stream */ "flarum/common/utils/Stream");
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/admin/components/LoadingModal */ "flarum/admin/components/LoadingModal");
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_errorHandler__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/errorHandler */ "./src/admin/utils/errorHandler.ts");








var Installer = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(Installer, _Component);

  function Installer() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.packageName = void 0;
    _this.isLoading = false;
    return _this;
  }

  var _proto = Installer.prototype;

  _proto.oninit = function oninit(vnode) {
    _Component.prototype.oninit.call(this, vnode);

    this.packageName = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4___default()('');
  };

  _proto.view = function view() {
    return m("div", {
      className: "Form-group"
    }, m("label", {
      htmlFor: "install-extension"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.install')), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.install_help', {
      extiverse: m("a", {
        href: "https://extiverse.com"
      }, "extiverse.com")
    })), m("div", {
      className: "FormControl-container"
    }, m("input", {
      className: "FormControl",
      id: "install-extension",
      placeholder: "vendor/package-name",
      bidi: this.packageName
    }), m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
      className: "Button",
      icon: "fas fa-download",
      onclick: this.onsubmit.bind(this),
      loading: this.isLoading
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.proceed'))));
  };

  _proto.data = function data() {
    return {
      "package": this.packageName()
    };
  };

  _proto.onsubmit = function onsubmit() {
    var _this2 = this;

    this.isLoading = true;
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default.a);
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/extensions",
      body: {
        data: this.data()
      },
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_6__["default"]
    }).then(function (response) {
      var extensionId = response.id;
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.extensions.successful_install', {
        extension: extensionId
      }));
      window.location.href = flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('adminUrl') + "#/extension/" + extensionId;
      window.location.reload();
    })["finally"](function () {
      _this2.isLoading = false;
      m.redraw();
    });
  };

  return Installer;
}(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default.a);



/***/ }),

/***/ "./src/admin/components/MajorUpdater.tsx":
/*!***********************************************!*\
  !*** ./src/admin/components/MajorUpdater.tsx ***!
  \***********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return MajorUpdater; });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/admin/components/LoadingModal */ "flarum/admin/components/LoadingModal");
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils_errorHandler__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/errorHandler */ "./src/admin/utils/errorHandler.ts");
/* harmony import */ var flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/Alert */ "flarum/common/components/Alert");
/* harmony import */ var flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _WhyNotModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./WhyNotModal */ "./src/admin/components/WhyNotModal.tsx");
/* harmony import */ var _ExtensionItem__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./ExtensionItem */ "./src/admin/components/ExtensionItem.tsx");











var MajorUpdater = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(MajorUpdater, _Component);

  function MajorUpdater() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.isLoading = null;
    _this.updateState = void 0;
    return _this;
  }

  var _proto = MajorUpdater.prototype;

  _proto.oninit = function oninit(vnode) {
    _Component.prototype.oninit.call(this, vnode);

    this.updateState = this.attrs.updateState;
  };

  _proto.view = function view(vnode) {
    // @todo move Form-group--danger class to core for reuse
    return m("div", {
      className: "Form-group Form-group--danger PackageManager-majorUpdate"
    }, m("img", {
      alt: "flarum logo",
      src: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('baseUrl') + '/assets/extensions/flarum-package-manager/flarum.svg'
    }), m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.title', {
      version: this.attrs.coreUpdate['latest-major']
    })), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.description')), m("div", {
      className: "PackageManager-updaterControls"
    }, m(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_4___default.a, {
      text: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.dry_run_help')
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
      className: "Button",
      icon: "fas fa-vial",
      onclick: this.update.bind(this, true)
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.dry_run'))), m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
      className: "Button Button--danger",
      icon: "fas fa-play",
      onclick: this.update.bind(this, false)
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.update'))), this.updateState.incompatibleExtensions.length ? m("div", {
      className: "PackageManager-majorUpdate-incompatibleExtensions PackageManager-extensions-grid"
    }, this.updateState.incompatibleExtensions.map(function (extension) {
      return m(_ExtensionItem__WEBPACK_IMPORTED_MODULE_9__["default"], {
        extension: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.data.extensions[extension.replace('flarum-', '').replace('flarum-ext-', '').replace('/', '-')],
        updates: {},
        onClickUpdate: null,
        isDanger: true
      });
    })) : null, this.updateState.status === 'failure' ? m(flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_7___default.a, {
      type: "error",
      className: "PackageManager-majorUpdate-failure",
      dismissible: false,
      controls: [m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
        className: "Button Button--text PackageManager-majorUpdate-failure-details",
        icon: "fas fa-question-circle",
        onclick: function onclick() {
          return flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(_WhyNotModal__WEBPACK_IMPORTED_MODULE_8__["default"], {
            "package": 'flarum/core'
          });
        }
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.failure.why'))]
    }, m("p", {
      className: "PackageManager-majorUpdate-failure-desc"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.major_updater.failure.desc'))) : null);
  };

  _proto.update = function update(dryRun) {
    var _this2 = this;

    this.isLoading = "update-" + (dryRun ? 'dry-run' : 'run');
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default.a);
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/major-update",
      body: {
        data: {
          dryRun: dryRun
        }
      },
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_6__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.update_successful'));
      window.location.reload();
    })["catch"](function (e) {
      var _e$response, _e$response$errors, _e$response$errors$po;

      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.close();
      _this2.updateState.status = 'failure';
      _this2.updateState.incompatibleExtensions = (_e$response = e.response) == null ? void 0 : (_e$response$errors = _e$response.errors) == null ? void 0 : (_e$response$errors$po = _e$response$errors.pop()) == null ? void 0 : _e$response$errors$po.incompatible_extensions;
    })["finally"](function () {
      _this2.isLoading = null;
      m.redraw();
    });
  };

  return MajorUpdater;
}(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default.a);



/***/ }),

/***/ "./src/admin/components/Updater.tsx":
/*!******************************************!*\
  !*** ./src/admin/components/Updater.tsx ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Updater; });
/* harmony import */ var _babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/humanTime */ "flarum/common/helpers/humanTime");
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/admin/components/LoadingModal */ "flarum/admin/components/LoadingModal");
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _utils_errorHandler__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/errorHandler */ "./src/admin/utils/errorHandler.ts");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _MajorUpdater__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./MajorUpdater */ "./src/admin/components/MajorUpdater.tsx");
/* harmony import */ var _ExtensionItem__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./ExtensionItem */ "./src/admin/components/ExtensionItem.tsx");












var Updater = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__["default"])(Updater, _Component);

  function Updater() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.isLoading = null;
    _this.packageUpdates = {};
    _this.lastUpdateCheck = JSON.parse(flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.data.settings['flarum-package-manager.last_update_check']);
    return _this;
  }

  var _proto = Updater.prototype;

  _proto.oninit = function oninit(vnode) {
    _Component.prototype.oninit.call(this, vnode);
  };

  _proto.view = function view() {
    var _this$lastUpdateCheck,
        _this2 = this;

    var extensions = this.getExtensionUpdates();
    var coreUpdate = this.getCoreUpdate();
    var core;

    if (coreUpdate) {
      core = {
        id: 'flarum-core',
        name: 'flarum/core',
        version: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.data.settings.version,
        icon: {
          backgroundImage: "url(" + flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.forum.attribute('baseUrl') + "/assets/extensions/flarum-package-manager/flarum.svg"
        },
        extra: {
          'flarum-extension': {
            title: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.flarum')
          }
        }
      };
    }

    return [m("div", {
      className: "Form-group"
    }, m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.updater_title')), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.updater_help')), ((_this$lastUpdateCheck = this.lastUpdateCheck) == null ? void 0 : _this$lastUpdateCheck.checkedAt) && m("p", {
      className: "PackageManager-lastUpdatedAt"
    }, m("span", {
      className: "PackageManager-lastUpdatedAt-label"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.last_update_checked_at')), m("span", {
      className: "PackageManager-lastUpdatedAt-value"
    }, flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5___default()(this.lastUpdateCheck.checkedAt))), m("div", {
      className: "PackageManager-updaterControls"
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      className: "Button",
      icon: "fas fa-sync-alt",
      onclick: this.checkForUpdates.bind(this),
      loading: this.isLoading === 'check',
      disabled: this.isLoading !== null && this.isLoading !== 'check'
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.check_for_updates')), m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      className: "Button",
      icon: "fas fa-play",
      onclick: this.updateGlobally.bind(this),
      loading: this.isLoading === 'global-update',
      disabled: this.isLoading !== null && this.isLoading !== 'global-update'
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.run_global_update'))), this.isLoading !== null ? m("div", {
      className: "PackageManager-extensions"
    }, m(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8___default.a, null)) : extensions.length || core ? m("div", {
      className: "PackageManager-extensions"
    }, m("div", {
      className: "PackageManager-extensions-grid"
    }, core ? m(_ExtensionItem__WEBPACK_IMPORTED_MODULE_10__["default"], {
      extension: core,
      updates: coreUpdate,
      isCore: true,
      onClickUpdate: this.updateCoreMinor.bind(this),
      whyNotWarning: this.lastUpdateRun.limitedPackages().includes('flarum/core')
    }) : null, extensions.map(function (extension) {
      return m(_ExtensionItem__WEBPACK_IMPORTED_MODULE_10__["default"], {
        extension: extension,
        updates: _this2.packageUpdates[extension.id],
        onClickUpdate: _this2.updateExtension.bind(_this2, extension),
        whyNotWarning: _this2.lastUpdateRun.limitedPackages().includes(extension.name)
      });
    }))) : null), coreUpdate && coreUpdate['latest-major'] ? m(_MajorUpdater__WEBPACK_IMPORTED_MODULE_9__["default"], {
      coreUpdate: coreUpdate,
      updateState: this.lastUpdateRun.major
    }) : null];
  };

  _proto.getExtensionUpdates = function getExtensionUpdates() {
    var _this$lastUpdateCheck2,
        _this$lastUpdateCheck3,
        _this$lastUpdateCheck4,
        _this3 = this;

    (_this$lastUpdateCheck2 = this.lastUpdateCheck) == null ? void 0 : (_this$lastUpdateCheck3 = _this$lastUpdateCheck2.updates) == null ? void 0 : (_this$lastUpdateCheck4 = _this$lastUpdateCheck3.installed) == null ? void 0 : _this$lastUpdateCheck4.filter(function (composerPackage) {
      var id = composerPackage.name.replace('/', '-').replace(/(flarum-ext-)|(flarum-)/, '');
      var extension = flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.data.extensions[id];
      var safeToUpdate = ['semver-safe-update', 'update-possible'].includes(composerPackage['latest-status']);

      if (extension && safeToUpdate) {
        _this3.packageUpdates[extension.id] = composerPackage;
      }

      return extension && safeToUpdate;
    });
    return Object.values(flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.data.extensions).filter(function (extension) {
      return _this3.packageUpdates[extension.id];
    });
  };

  _proto.getCoreUpdate = function getCoreUpdate() {
    var _this$lastUpdateCheck5, _this$lastUpdateCheck6, _this$lastUpdateCheck7;

    return (_this$lastUpdateCheck5 = this.lastUpdateCheck) == null ? void 0 : (_this$lastUpdateCheck6 = _this$lastUpdateCheck5.updates) == null ? void 0 : (_this$lastUpdateCheck7 = _this$lastUpdateCheck6.installed) == null ? void 0 : _this$lastUpdateCheck7.filter(function (composerPackage) {
      return composerPackage.name === 'flarum/core';
    }).pop();
  };

  _proto.checkForUpdates = function checkForUpdates() {
    var _this4 = this;

    this.isLoading = 'check';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.forum.attribute('apiUrl') + "/package-manager/check-for-updates",
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_7__["default"]
    }).then(function (response) {
      _this4.lastUpdateCheck = response;
    })["finally"](function () {
      _this4.isLoading = null;
      m.redraw();
    });
  };

  _proto.updateCoreMinor = function updateCoreMinor() {
    var _this5 = this;

    if (confirm(flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.minor_update_confirmation.content'))) {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
      this.isLoading = 'minor-update';
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.request({
        method: 'POST',
        url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.forum.attribute('apiUrl') + "/package-manager/minor-update",
        errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_7__["default"]
      }).then(function () {
        flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.alerts.show({
          type: 'success'
        }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.update_successful'));
        window.location.reload();
      })["finally"](function () {
        _this5.isLoading = null;
        m.redraw();
      });
    }
  };

  _proto.updateExtension = function updateExtension(extension) {
    var _this6 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
    this.isLoading = 'extension-update';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.request({
      method: 'PATCH',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.forum.attribute('apiUrl') + "/package-manager/extensions/" + extension.id,
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_7__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.extensions.successful_update', {
        extension: extension.extra['flarum-extension'].title
      }));
      window.location.reload();
    })["finally"](function () {
      _this6.isLoading = null;
      m.redraw();
    });
  };

  _proto.updateGlobally = function updateGlobally() {
    var _this7 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
    this.isLoading = 'global-update';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.forum.attribute('apiUrl') + "/package-manager/global-update",
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_7__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.translator.trans('flarum-package-manager.admin.updater.global_update_successful'));
      window.location.reload();
    })["finally"](function () {
      _this7.isLoading = null;
      m.redraw();
    });
  };

  Object(_babel_runtime_helpers_esm_createClass__WEBPACK_IMPORTED_MODULE_0__["default"])(Updater, [{
    key: "lastUpdateRun",
    get: function get() {
      var lastUpdateRun = JSON.parse(flarum_admin_app__WEBPACK_IMPORTED_MODULE_2___default.a.data.settings['flarum-package-manager.last_update_run']);

      lastUpdateRun.limitedPackages = function () {
        return [].concat(lastUpdateRun.major.limitedPackages, lastUpdateRun.minor.limitedPackages, lastUpdateRun.global.limitedPackages);
      };

      return lastUpdateRun;
    }
  }]);

  return Updater;
}(flarum_common_Component__WEBPACK_IMPORTED_MODULE_3___default.a);



/***/ }),

/***/ "./src/admin/components/WhyNotModal.tsx":
/*!**********************************************!*\
  !*** ./src/admin/components/WhyNotModal.tsx ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return WhyNotModal; });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_errorHandler__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/errorHandler */ "./src/admin/utils/errorHandler.ts");






var WhyNotModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(WhyNotModal, _Modal);

  function WhyNotModal() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.loading = true;
    _this.whyNot = null;
    return _this;
  }

  var _proto = WhyNotModal.prototype;

  _proto.className = function className() {
    return 'Modal--large WhyNotModal';
  };

  _proto.title = function title() {
    return flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('flarum-package-manager.admin.why_not_modal.title');
  };

  _proto.oncreate = function oncreate(vnode) {
    _Modal.prototype.oncreate.call(this, vnode);

    this.requestWhyNot();
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, this.loading ? m(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3___default.a, null) : m("pre", {
      className: "WhyNotModal-contents"
    }, this.whyNot));
  };

  _proto.requestWhyNot = function requestWhyNot() {
    var _this2 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/why-not",
      body: {
        data: {
          "package": this.attrs["package"]
        }
      },
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_4__["default"]
    }).then(function (response) {
      _this2.loading = false;
      _this2.whyNot = response.data.whyNot;
      m.redraw();
    });
  };

  return WhyNotModal;
}(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default.a);



/***/ }),

/***/ "./src/admin/index.tsx":
/*!*****************************!*\
  !*** ./src/admin/index.tsx ***!
  \*****************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Alert */ "flarum/common/components/Alert");
/* harmony import */ var flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/admin/components/ExtensionPage */ "flarum/admin/components/ExtensionPage");
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/admin/components/LoadingModal */ "flarum/admin/components/LoadingModal");
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _components_Installer__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/Installer */ "./src/admin/components/Installer.tsx");
/* harmony import */ var _components_Updater__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/Updater */ "./src/admin/components/Updater.tsx");
/* harmony import */ var flarum_admin_utils_isExtensionEnabled__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! flarum/admin/utils/isExtensionEnabled */ "flarum/admin/utils/isExtensionEnabled");
/* harmony import */ var flarum_admin_utils_isExtensionEnabled__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_utils_isExtensionEnabled__WEBPACK_IMPORTED_MODULE_8__);









flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.initializers.add('flarum-package-manager', function (app) {
  app.extensionData["for"]('flarum-package-manager').registerSetting(function () {
    if (!app.data.isRequiredDirectoriesWritable) {
      return m("div", {
        className: "Form-group"
      }, m(flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_2___default.a, {
        type: "warning",
        dismissible: false
      }, app.translator.trans('flarum-package-manager.admin.file_permissions')));
    }

    return null;
  }).registerSetting(function () {
    if (app.data.isRequiredDirectoriesWritable) {
      return m(_components_Installer__WEBPACK_IMPORTED_MODULE_6__["default"], null);
    }

    return null;
  }).registerSetting(function () {
    if (app.data.isRequiredDirectoriesWritable) {
      return m(_components_Updater__WEBPACK_IMPORTED_MODULE_7__["default"], null);
    }

    return null;
  });
  Object(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__["extend"])(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default.a.prototype, 'topItems', function (items) {
    var _this = this;

    if (this.extension.id === 'flarum-package-manager' || flarum_admin_utils_isExtensionEnabled__WEBPACK_IMPORTED_MODULE_8___default()(this.extension.id)) {
      return;
    }

    items.add('remove', m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      className: "Button Button--danger",
      icon: "fas fa-times",
      onclick: function onclick() {
        app.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_5___default.a);
        app.request({
          url: app.forum.attribute('apiUrl') + "/package-manager/extensions/" + _this.extension.id,
          method: 'DELETE'
        }).then(function () {
          app.alerts.show({
            type: 'success'
          }, app.translator.trans('flarum-package-manager.admin.extensions.successful_remove'));
          window.location = app.forum.attribute('adminUrl');
        })["finally"](function () {
          app.modal.close();
        });
      }
    }, "Remove"));
  });
});

/***/ }),

/***/ "./src/admin/utils/errorHandler.ts":
/*!*****************************************!*\
  !*** ./src/admin/utils/errorHandler.ts ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ __webpack_exports__["default"] = (function (e) {
  var error = e.response.errors[0];

  if (!['composer_command_failure', 'extension_already_installed', 'extension_not_installed'].includes(error.code)) {
    throw e;
  }

  switch (error.code) {
    case 'composer_command_failure':
      if (error.guessed_cause) {
        flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
          type: 'error'
        }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans("flarum-package-manager.admin.exceptions.guessed_cause." + error.guessed_cause));
        flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.close();
      } else {
        flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
          type: 'error'
        }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('flarum-package-manager.admin.exceptions.composer_command_failure'));
      }

      break;

    case 'extension_already_installed':
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
        type: 'error'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('flarum-package-manager.admin.exceptions.extension_already_installed'));
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.close();
      break;

    case 'extension_not_installed':
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
        type: 'error'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('flarum-package-manager.admin.exceptions.extension_not_installed'));
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.close();
  }
});

/***/ }),

/***/ "flarum/admin/app":
/*!**************************************************!*\
  !*** external "flarum.core.compat['admin/app']" ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['admin/app'];

/***/ }),

/***/ "flarum/admin/components/ExtensionPage":
/*!***********************************************************************!*\
  !*** external "flarum.core.compat['admin/components/ExtensionPage']" ***!
  \***********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['admin/components/ExtensionPage'];

/***/ }),

/***/ "flarum/admin/components/LoadingModal":
/*!**********************************************************************!*\
  !*** external "flarum.core.compat['admin/components/LoadingModal']" ***!
  \**********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['admin/components/LoadingModal'];

/***/ }),

/***/ "flarum/admin/utils/isExtensionEnabled":
/*!***********************************************************************!*\
  !*** external "flarum.core.compat['admin/utils/isExtensionEnabled']" ***!
  \***********************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['admin/utils/isExtensionEnabled'];

/***/ }),

/***/ "flarum/common/Component":
/*!*********************************************************!*\
  !*** external "flarum.core.compat['common/Component']" ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/Component'];

/***/ }),

/***/ "flarum/common/components/Alert":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Alert']" ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/Alert'];

/***/ }),

/***/ "flarum/common/components/Button":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Button']" ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/Button'];

/***/ }),

/***/ "flarum/common/components/LoadingIndicator":
/*!***************************************************************************!*\
  !*** external "flarum.core.compat['common/components/LoadingIndicator']" ***!
  \***************************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/LoadingIndicator'];

/***/ }),

/***/ "flarum/common/components/Modal":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Modal']" ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/Modal'];

/***/ }),

/***/ "flarum/common/components/Tooltip":
/*!******************************************************************!*\
  !*** external "flarum.core.compat['common/components/Tooltip']" ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/Tooltip'];

/***/ }),

/***/ "flarum/common/extend":
/*!******************************************************!*\
  !*** external "flarum.core.compat['common/extend']" ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/extend'];

/***/ }),

/***/ "flarum/common/helpers/humanTime":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/humanTime']" ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/helpers/humanTime'];

/***/ }),

/***/ "flarum/common/helpers/icon":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/icon']" ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/helpers/icon'];

/***/ }),

/***/ "flarum/common/utils/Stream":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/utils/Stream']" ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/utils/Stream'];

/***/ }),

/***/ "flarum/common/utils/classList":
/*!***************************************************************!*\
  !*** external "flarum.core.compat['common/utils/classList']" ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/utils/classList'];

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map