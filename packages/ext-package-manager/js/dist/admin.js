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
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.js");
/* empty/unused harmony star reexport */

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

/***/ "./src/admin/components/ComposerFailureModal.tsx":
/*!*******************************************************!*\
  !*** ./src/admin/components/ComposerFailureModal.tsx ***!
  \*******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ComposerFailureModal; });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);




var ComposerFailureModal = /*#__PURE__*/function (_Modal) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(ComposerFailureModal, _Modal);

  function ComposerFailureModal() {
    return _Modal.apply(this, arguments) || this;
  }

  var _proto = ComposerFailureModal.prototype;

  _proto.oninit = function oninit(vnode) {
    _Modal.prototype.oninit.call(this, vnode);

    if (this.attrs.error.guessed_cause) {
      this.alertAttrs = {
        type: 'error',
        content: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans("sycho-package-manager.admin.failure_modal.guessed_cause." + this.attrs.error.guessed_cause)
      };
    }
  };

  _proto.className = function className() {
    return 'Modal--large ComposerFailureModal';
  };

  _proto.title = function title() {
    return flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.failure_modal.title');
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, m("details", null, m("summary", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.failure_modal.show_composer_output')), m("pre", {
      className: "ComposerFailureModal-output"
    }, this.attrs.error.output)));
  };

  return ComposerFailureModal;
}(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default.a);



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
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.install')), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.install_help', {
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
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.proceed'))));
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
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.successful_install', {
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






var MajorUpdater = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(MajorUpdater, _Component);

  function MajorUpdater() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = MajorUpdater.prototype;

  _proto.view = function view(vnode) {
    return m("div", {
      className: "Form-group PackageManager-majorUpdate"
    }, m("img", {
      alt: "flarum logo",
      src: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('baseUrl') + '/assets/extensions/sycho-package-manager/flarum.svg'
    }), m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.major_updater.title', {
      version: this.attrs.coreUpdate['latest-major']
    })), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.major_updater.description')), m("div", {
      className: "PackageManager-updaterControls"
    }, m(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_4___default.a, {
      text: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.major_updater.dry_run_help')
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
      className: "Button",
      icon: "fas fa-vial"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.major_updater.dry_run'))), m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default.a, {
      className: "Button",
      icon: "fas fa-play"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.major_updater.update'))));
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
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "./node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/helpers/icon */ "flarum/common/helpers/icon");
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/humanTime */ "flarum/common/helpers/humanTime");
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/admin/components/LoadingModal */ "flarum/admin/components/LoadingModal");
/* harmony import */ var flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/Tooltip */ "flarum/common/components/Tooltip");
/* harmony import */ var flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _utils_errorHandler__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/errorHandler */ "./src/admin/utils/errorHandler.ts");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _MajorUpdater__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./MajorUpdater */ "./src/admin/components/MajorUpdater.tsx");













var Updater = /*#__PURE__*/function (_Component) {
  Object(_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(Updater, _Component);

  function Updater() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Component.call.apply(_Component, [this].concat(args)) || this;
    _this.isLoading = null;
    _this.lastUpdateCheck = flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.data.lastUpdateCheck || {};
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
    var core = null;

    if (coreUpdate) {
      core = {
        title: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.flarum'),
        version: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.data.settings.version,
        icon: {
          backgroundImage: "url(" + flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('baseUrl') + "/assets/extensions/sycho-package-manager/flarum.svg"
        },
        newPackageUpdate: coreUpdate
      };
    }

    return [m("div", {
      className: "Form-group"
    }, m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.updater_title')), m("p", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.updater_help')), Object.keys(this.lastUpdateCheck).length ? m("p", {
      className: "PackageManager-lastUpdatedAt"
    }, m("span", {
      className: "PackageManager-lastUpdatedAt-label"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.last_update_checked_at')), m("span", {
      className: "PackageManager-lastUpdatedAt-value"
    }, flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_5___default()((_this$lastUpdateCheck = this.lastUpdateCheck) == null ? void 0 : _this$lastUpdateCheck.checkedAt))) : null, m("div", {
      className: "PackageManager-updaterControls"
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      className: "Button",
      icon: "fas fa-sync-alt",
      onclick: this.checkForUpdates.bind(this),
      loading: this.isLoading === 'check',
      disabled: this.isLoading !== null && this.isLoading !== 'check'
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.check_for_updates')), m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      className: "Button",
      icon: "fas fa-play",
      onclick: this.updateGlobally.bind(this),
      loading: this.isLoading === 'global-update',
      disabled: this.isLoading !== null && this.isLoading !== 'global-update'
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.run_global_update'))), this.isLoading !== null ? m("div", {
      className: "PackageManager-extensions"
    }, m(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10___default.a, null)) : extensions.length || core ? m("div", {
      className: "PackageManager-extensions"
    }, m("div", {
      className: "PackageManager-extensions-grid"
    }, core ? this.extensionItem(core, true) : null, extensions.map(function (extension) {
      return _this2.extensionItem(extension);
    }))) : null), coreUpdate && coreUpdate['latest-major'] ? m(_MajorUpdater__WEBPACK_IMPORTED_MODULE_11__["default"], {
      coreUpdate: coreUpdate
    }) : null];
  };

  _proto.extensionItem = function extensionItem(extension, isCore) {
    if (isCore === void 0) {
      isCore = false;
    }

    return m("div", {
      className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_9___default()({
        'PackageManager-extension': true,
        'PackageManager-extension--core': isCore
      })
    }, m("div", {
      className: "PackageManager-extension-icon ExtensionIcon",
      style: extension.icon
    }, extension.icon ? flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_3___default()(extension.icon.name) : ''), m("div", {
      className: "PackageManager-extension-info"
    }, m("div", {
      className: "PackageManager-extension-name"
    }, extension.title || extension.extra['flarum-extension'].title), m("div", {
      className: "PackageManager-extension-version"
    }, m("span", {
      className: "PackageManager-extension-version-current"
    }, this.version(extension.version)), extension.newPackageUpdate['latest-minor'] ? m("span", {
      className: "PackageManager-extension-version-latest PackageManager-extension-version-latest--minor"
    }, this.version(extension.newPackageUpdate['latest-minor'])) : null, extension.newPackageUpdate['latest-major'] && !isCore ? m("span", {
      className: "PackageManager-extension-version-latest PackageManager-extension-version-latest--major"
    }, this.version(extension.newPackageUpdate['latest-major'])) : null)), m("div", {
      className: "PackageManager-extension-controls"
    }, m(flarum_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_7___default.a, {
      text: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.update')
    }, m(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default.a, {
      icon: "fas fa-arrow-alt-circle-up",
      className: "Button Button--icon Button--flat",
      onclick: isCore ? this.updateCoreMinor.bind(this) : this.updateExtension.bind(this, extension),
      "aria-label": flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.update')
    }))));
  };

  _proto.version = function version(v) {
    return 'v' + v.replace('v', '');
  };

  _proto.getExtensionUpdates = function getExtensionUpdates() {
    var _this$lastUpdateCheck2, _this$lastUpdateCheck3, _this$lastUpdateCheck4;

    (_this$lastUpdateCheck2 = this.lastUpdateCheck) == null ? void 0 : (_this$lastUpdateCheck3 = _this$lastUpdateCheck2.updates) == null ? void 0 : (_this$lastUpdateCheck4 = _this$lastUpdateCheck3.installed) == null ? void 0 : _this$lastUpdateCheck4.filter(function (composerPackage) {
      var extension = flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.data.extensions[composerPackage.name.replace('/', '-').replace(/(flarum-ext-)|(flarum-)/, '')];
      var safeToUpdate = ['semver-safe-update', 'update-possible'].includes(composerPackage['latest-status']);

      if (extension && safeToUpdate) {
        extension.newPackageUpdate = composerPackage;
      }

      return extension && safeToUpdate;
    });
    return Object.values(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.data.extensions).filter(function (extension) {
      return extension.newPackageUpdate;
    });
  };

  _proto.getCoreUpdate = function getCoreUpdate() {
    var _this$lastUpdateCheck5, _this$lastUpdateCheck6, _this$lastUpdateCheck7;

    return (_this$lastUpdateCheck5 = this.lastUpdateCheck) == null ? void 0 : (_this$lastUpdateCheck6 = _this$lastUpdateCheck5.updates) == null ? void 0 : (_this$lastUpdateCheck7 = _this$lastUpdateCheck6.installed) == null ? void 0 : _this$lastUpdateCheck7.filter(function (composerPackage) {
      return composerPackage.name === 'flarum/core';
    }).pop();
  };

  _proto.checkForUpdates = function checkForUpdates() {
    var _this3 = this;

    this.isLoading = 'check';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/check-for-updates",
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_8__["default"]
    }).then(function (response) {
      _this3.lastUpdateCheck = response;
    })["finally"](function () {
      _this3.isLoading = null;
      m.redraw();
    });
  };

  _proto.updateCoreMinor = function updateCoreMinor() {
    var _this4 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
    this.isLoading = 'minor-update';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/minor-update",
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_8__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.minor_update_successful'));
      window.location.reload();
    })["finally"](function () {
      _this4.isLoading = null;
      m.redraw();
    });
  };

  _proto.updateExtension = function updateExtension(extension) {
    var _this5 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
    this.isLoading = 'extension-update';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'PATCH',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/extensions/" + extension.id,
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_8__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.extensions.successful_update', {
        extension: extension.extra['flarum-extension'].title
      }));
      window.location.reload();
    })["finally"](function () {
      _this5.isLoading = null;
      m.redraw();
    });
  };

  _proto.updateGlobally = function updateGlobally() {
    var _this6 = this;

    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(flarum_admin_components_LoadingModal__WEBPACK_IMPORTED_MODULE_6___default.a);
    this.isLoading = 'global-update';
    flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.request({
      method: 'POST',
      url: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.forum.attribute('apiUrl') + "/package-manager/global-update",
      errorHandler: _utils_errorHandler__WEBPACK_IMPORTED_MODULE_8__["default"]
    }).then(function () {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.alerts.show({
        type: 'success'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.translator.trans('sycho-package-manager.admin.updater.global_update_successful'));
      window.location.reload();
    })["finally"](function () {
      _this6.isLoading = null;
      m.redraw();
    });
  };

  return Updater;
}(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default.a);



/***/ }),

/***/ "./src/admin/index.js":
/*!****************************!*\
  !*** ./src/admin/index.js ***!
  \****************************/
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
/* harmony import */ var _components_MajorUpdater__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./components/MajorUpdater */ "./src/admin/components/MajorUpdater.tsx");










flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.initializers.add('sycho-package-manager', function (app) {
  app.extensionData["for"]('sycho-package-manager').registerSetting(function () {
    if (!app.data.isRequiredDirectoriesWritable) {
      return m("div", {
        className: "Form-group"
      }, m(flarum_common_components_Alert__WEBPACK_IMPORTED_MODULE_2___default.a, {
        type: "warning",
        dismissible: false
      }, app.translator.trans('sycho-package-manager.admin.file_permissions')));
    }
  }).registerSetting(function () {
    if (app.data.isRequiredDirectoriesWritable) {
      return m(_components_Installer__WEBPACK_IMPORTED_MODULE_6__["default"], null);
    }
  }).registerSetting(function () {
    if (app.data.isRequiredDirectoriesWritable) {
      return m(_components_Updater__WEBPACK_IMPORTED_MODULE_7__["default"], null);
    }
  });
  Object(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__["extend"])(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default.a.prototype, 'topItems', function (items) {
    var _this = this;

    if (this.extension.id === 'sycho-package-manager' || flarum_admin_utils_isExtensionEnabled__WEBPACK_IMPORTED_MODULE_8___default()(this.extension.id)) {
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
          }, app.translator.trans('sycho-package-manager.admin.extensions.successful_remove'));
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
/* harmony import */ var _components_ComposerFailureModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/ComposerFailureModal */ "./src/admin/components/ComposerFailureModal.tsx");


/* harmony default export */ __webpack_exports__["default"] = (function (e) {
  var error = e.response.errors[0];

  if (!['composer_command_failure', 'extension_already_installed', 'extension_not_installed'].includes(error.code)) {
    throw e;
  }

  switch (error.code) {
    case 'composer_command_failure':
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.show(_components_ComposerFailureModal__WEBPACK_IMPORTED_MODULE_1__["default"], {
        error: error
      });
      break;

    case 'extension_already_installed':
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
        type: 'error'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('sycho-package-manager.admin.exceptions.extension_already_installed'));
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.modal.close();
      break;

    case 'extension_not_installed':
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.alerts.show({
        type: 'error'
      }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default.a.translator.trans('sycho-package-manager.admin.exceptions.extension_not_installed'));
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