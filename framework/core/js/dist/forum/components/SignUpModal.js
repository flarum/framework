"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/SignUpModal"],{

/***/ "./src/forum/components/SignUpModal.tsx":
/*!**********************************************!*\
  !*** ./src/forum/components/SignUpModal.tsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SignUpModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _LogInButtons__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./LogInButtons */ "./src/forum/components/LogInButtons.js");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");








class SignUpModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    /**
     * The value of the username input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "username", void 0);
    /**
     * The value of the email input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "email", void 0);
    /**
     * The value of the password input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "password", void 0);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.username = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(this.attrs.username || '');
    this.email = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(this.attrs.email || '');
    this.password = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_7__["default"])(this.attrs.password || '');
  }
  className() {
    return 'Modal--small SignUpModal';
  }
  title() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.title');
  }
  content() {
    return [m("div", {
      className: "Modal-body"
    }, this.body()), m("div", {
      className: "Modal-footer"
    }, this.footer())];
  }
  isProvided(field) {
    var _this$attrs$provided$, _this$attrs$provided;
    return (_this$attrs$provided$ = (_this$attrs$provided = this.attrs.provided) == null ? void 0 : _this$attrs$provided.includes(field)) != null ? _this$attrs$provided$ : false;
  }
  body() {
    return [!this.attrs.token && m(_LogInButtons__WEBPACK_IMPORTED_MODULE_4__["default"], null), m("div", {
      className: "Form Form--centered"
    }, this.fields().toArray())];
  }
  fields() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    const usernameLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.username_placeholder'));
    const emailLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.email_placeholder'));
    const passwordLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.password_placeholder'));
    items.add('username', m("div", {
      className: "Form-group"
    }, m("input", {
      className: "FormControl",
      name: "username",
      type: "text",
      placeholder: usernameLabel,
      "aria-label": usernameLabel,
      bidi: this.username,
      disabled: this.loading || this.isProvided('username')
    })), 30);
    items.add('email', m("div", {
      className: "Form-group"
    }, m("input", {
      className: "FormControl",
      name: "email",
      type: "email",
      placeholder: emailLabel,
      "aria-label": emailLabel,
      bidi: this.email,
      disabled: this.loading || this.isProvided('email')
    })), 20);
    if (!this.attrs.token) {
      items.add('password', m("div", {
        className: "Form-group"
      }, m("input", {
        className: "FormControl",
        name: "password",
        type: "password",
        autocomplete: "new-password",
        placeholder: passwordLabel,
        "aria-label": passwordLabel,
        bidi: this.password,
        disabled: this.loading
      })), 10);
    }
    items.add('submit', m("div", {
      className: "Form-group"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--primary Button--block",
      type: "submit",
      loading: this.loading
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.submit_button'))), -10);
    return items;
  }
  footer() {
    return [m("p", {
      className: "SignUpModal-logIn"
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.sign_up.log_in_text', {
      a: m("a", {
        onclick: this.logIn.bind(this)
      })
    }))];
  }

  /**
   * Open the log in modal, prefilling it with an email/username/password if
   * the user has entered one.
   */
  logIn() {
    const attrs = {
      identification: this.email() || this.username()
    };
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(() => __webpack_require__.e(/*! import() | forum/components/LogInModal */ "forum/components/LogInModal").then(__webpack_require__.bind(__webpack_require__, /*! ./LogInModal */ "./src/forum/components/LogInModal.tsx")), attrs);
  }
  onready() {
    if (this.attrs.username && !this.attrs.email) {
      this.$('[name=email]').select();
    } else {
      this.$('[name=username]').select();
    }
  }
  onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    const body = this.submitData();
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].request({
      url: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('baseUrl') + '/register',
      method: 'POST',
      body,
      errorHandler: this.onerror.bind(this)
    }).then(() => window.location.reload(), this.loaded.bind(this));
  }

  /**
   * Get the data that should be submitted in the sign-up request.
   */
  submitData() {
    const authData = this.attrs.token ? {
      token: this.attrs.token
    } : {
      password: this.password()
    };
    const data = {
      username: this.username(),
      email: this.email(),
      ...authData
    };
    return data;
  }
}
flarum.reg.add('core', 'forum/components/SignUpModal', SignUpModal);flarum.reg.addChunkModule('forum/components/LogInModal', './src/forum/components/LogInModal.tsx', 'core', 'forum/components/LogInModal');

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
//# sourceMappingURL=SignUpModal.js.map