"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/LogInModal"],{

/***/ "./src/forum/components/LogInModal.tsx":
/*!*********************************************!*\
  !*** ./src/forum/components/LogInModal.tsx ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LogInModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _LogInButtons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./LogInButtons */ "./src/forum/components/LogInButtons.js");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");








class LogInModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    /**
     * The value of the identification input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "identification", void 0);
    /**
     * The value of the password input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "password", void 0);
    /**
     * The value of the remember me input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "remember", void 0);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.identification = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(this.attrs.identification || '');
    this.password = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(this.attrs.password || '');
    this.remember = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(!!this.attrs.remember);
  }
  className() {
    return 'LogInModal Modal--small';
  }
  title() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.title');
  }
  content() {
    return [m("div", {
      className: "Modal-body"
    }, this.body()), m("div", {
      className: "Modal-footer"
    }, this.footer())];
  }
  body() {
    return [m(_LogInButtons__WEBPACK_IMPORTED_MODULE_4__["default"], null), m("div", {
      className: "Form Form--centered"
    }, this.fields().toArray())];
  }
  fields() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    const identificationLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.username_or_email_placeholder'));
    const passwordLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.password_placeholder'));
    items.add('identification', m("div", {
      className: "Form-group"
    }, m("input", {
      className: "FormControl",
      name: "identification",
      type: "text",
      placeholder: identificationLabel,
      "aria-label": identificationLabel,
      bidi: this.identification,
      disabled: this.loading
    })), 30);
    items.add('password', m("div", {
      className: "Form-group"
    }, m("input", {
      className: "FormControl",
      name: "password",
      type: "password",
      autocomplete: "current-password",
      placeholder: passwordLabel,
      "aria-label": passwordLabel,
      bidi: this.password,
      disabled: this.loading
    })), 20);
    items.add('remember', m("div", {
      className: "Form-group"
    }, m("div", null, m("label", {
      className: "checkbox"
    }, m("input", {
      type: "checkbox",
      bidi: this.remember,
      disabled: this.loading
    }), _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.remember_me_label')))), 10);
    items.add('submit', m("div", {
      className: "Form-group"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--primary Button--block",
      type: "submit",
      loading: this.loading
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.submit_button'))), -10);
    return items;
  }
  footer() {
    return m('[', null, m("p", {
      className: "LogInModal-forgotPassword"
    }, m("a", {
      onclick: this.forgotPassword.bind(this)
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.forgot_password_link'))), _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('allowSignUp') && m("p", {
      className: "LogInModal-signUp"
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.sign_up_text', {
      a: m("a", {
        onclick: this.signUp.bind(this)
      })
    })));
  }

  /**
   * Open the forgot password modal, prefilling it with an email if the user has
   * entered one.
   */
  forgotPassword() {
    const email = this.identification();
    const attrs = email.includes('@') ? {
      email
    } : undefined;
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(() => __webpack_require__.e(/*! import() | forum/components/ForgotPasswordModal */ "forum/components/ForgotPasswordModal").then(__webpack_require__.bind(__webpack_require__, /*! ./ForgotPasswordModal */ "./src/forum/components/ForgotPasswordModal.tsx")), attrs);
  }

  /**
   * Open the sign up modal, prefilling it with an email/username/password if
   * the user has entered one.
   */
  signUp() {
    const identification = this.identification();
    const attrs = {
      [identification.includes('@') ? 'email' : 'username']: identification
    };
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(() => __webpack_require__.e(/*! import() | forum/components/SignUpModal */ "forum/components/SignUpModal").then(__webpack_require__.bind(__webpack_require__, /*! ./SignUpModal */ "./src/forum/components/SignUpModal.tsx")), attrs);
  }
  onready() {
    this.$('[name=' + (this.identification() ? 'password' : 'identification') + ']').trigger('select');
  }
  onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.login(this.loginParams(), {
      errorHandler: this.onerror.bind(this)
    }).then(() => window.location.reload(), this.loaded.bind(this));
  }
  loginParams() {
    const data = {
      identification: this.identification(),
      password: this.password(),
      remember: this.remember()
    };
    return data;
  }
  onerror(error) {
    if (error.status === 401 && error.alert) {
      error.alert.content = _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.log_in.invalid_login_message');
      this.password('');
    }
    super.onerror(error);
  }
}
flarum.reg.add('core', 'forum/components/LogInModal', LogInModal);flarum.reg.addChunkModule('forum/components/ForgotPasswordModal', './src/forum/components/ForgotPasswordModal.tsx', 'core', 'forum/components/ForgotPasswordModal');
flarum.reg.addChunkModule('forum/components/SignUpModal', './src/forum/components/SignUpModal.tsx', 'core', 'forum/components/SignUpModal');

/***/ }),

/***/ "./src/forum/components/LogInButtons.js":
/*!**********************************************!*\
  !*** ./src/forum/components/LogInButtons.js ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LogInButtons)
/* harmony export */ });
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");



/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
class LogInButtons extends _common_Component__WEBPACK_IMPORTED_MODULE_0__["default"] {
  view() {
    return m("div", {
      className: "LogInButtons"
    }, this.items().toArray());
  }

  /**
   * Build a list of LogInButton components.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    return new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_1__["default"]();
  }
}
flarum.reg.add('core', 'forum/components/LogInButtons', LogInButtons);

/***/ })

}]);
//# sourceMappingURL=LogInModal.js.map