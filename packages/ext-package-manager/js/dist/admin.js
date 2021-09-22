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
/* harmony import */ var _ComposerFailureModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ComposerFailureModal */ "./src/admin/components/ComposerFailureModal.tsx");








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
      errorHandler: function errorHandler(e) {
        var error = e.response.errors[0];

        if (error.code !== 'composer_command_failure') {
          throw e;
        }

        flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default.a.modal.show(_ComposerFailureModal__WEBPACK_IMPORTED_MODULE_6__["default"], {
          error: error
        });
      }
    }).then(function (response) {
      var extensionId = response.data.attributes.id;
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
  });
  Object(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__["extend"])(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_3___default.a.prototype, 'topItems', function (items) {
    var _this = this;

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

/***/ "flarum/common/components/Modal":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Modal']" ***!
  \****************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/components/Modal'];

/***/ }),

/***/ "flarum/common/extend":
/*!******************************************************!*\
  !*** external "flarum.core.compat['common/extend']" ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/extend'];

/***/ }),

/***/ "flarum/common/utils/Stream":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/utils/Stream']" ***!
  \************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = flarum.core.compat['common/utils/Stream'];

/***/ })

/******/ });
//# sourceMappingURL=admin.js.map