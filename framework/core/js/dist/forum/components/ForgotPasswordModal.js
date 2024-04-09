"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/ForgotPasswordModal"],{

/***/ "./src/forum/components/ForgotPasswordModal.tsx":
/*!******************************************************!*\
  !*** ./src/forum/components/ForgotPasswordModal.tsx ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ForgotPasswordModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Form__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Form */ "./src/common/components/Form.tsx");








/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their email address and request a link to reset their password.
 */
class ForgotPasswordModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    /**
     * The value of the email input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "email", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "success", false);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.email = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_5__["default"])(this.attrs.email || '');
  }
  className() {
    return 'ForgotPasswordModal Modal--small';
  }
  title() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.title');
  }
  content() {
    if (this.success) {
      return m("div", {
        className: "Modal-body"
      }, m(_common_components_Form__WEBPACK_IMPORTED_MODULE_7__["default"], {
        className: "Form--centered"
      }, m("p", {
        className: "helpText"
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.email_sent_message')), m("div", {
        className: "Form-group Form-controls"
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
        className: "Button Button--primary Button--block",
        onclick: this.hide.bind(this)
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.dismiss_button')))));
    }
    return m("div", {
      className: "Modal-body"
    }, m(_common_components_Form__WEBPACK_IMPORTED_MODULE_7__["default"], {
      className: "Form--centered",
      description: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.text')
    }, this.fields().toArray()));
  }
  fields() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    const emailLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.email_placeholder'));
    items.add('email', m("div", {
      className: "Form-group"
    }, m("input", {
      className: "FormControl",
      name: "email",
      type: "email",
      placeholder: emailLabel,
      "aria-label": emailLabel,
      bidi: this.email,
      disabled: this.loading
    })), 50);
    items.add('submit', m("div", {
      className: "Form-group Form-controls"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--primary Button--block",
      type: "submit",
      loading: this.loading
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.submit_button'))), -10);
    return items;
  }
  onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].request({
      method: 'POST',
      url: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('apiUrl') + '/forgot',
      body: this.requestParams(),
      errorHandler: this.onerror.bind(this)
    }).then(() => {
      this.success = true;
      this.alertAttrs = null;
    }).catch(() => {}).then(this.loaded.bind(this));
  }
  requestParams() {
    const data = {
      email: this.email()
    };
    return data;
  }
  onerror(error) {
    if (error.status === 404 && error.alert) {
      error.alert.content = _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.forgot_password.not_found_message');
    }
    super.onerror(error);
  }
}
flarum.reg.add('core', 'forum/components/ForgotPasswordModal', ForgotPasswordModal);

/***/ })

}]);
//# sourceMappingURL=ForgotPasswordModal.js.map