"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["common/components/EditUserModal"],{

/***/ "./src/common/components/EditUserModal.tsx":
/*!*************************************************!*\
  !*** ./src/common/components/EditUserModal.tsx ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ EditUserModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _common_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/app */ "./src/common/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _GroupBadge__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./GroupBadge */ "./src/common/components/GroupBadge.tsx");
/* harmony import */ var _models_Group__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../models/Group */ "./src/common/models/Group.ts");
/* harmony import */ var _utils_extractText__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _utils_ItemList__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _utils_Stream__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/Stream */ "./src/common/utils/Stream.ts");
/* harmony import */ var _Form__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./Form */ "./src/common/components/Form.tsx");










class EditUserModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "username", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "email", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "isEmailConfirmed", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "setPassword", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "password", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "groups", {});
  }
  oninit(vnode) {
    super.oninit(vnode);
    const user = this.attrs.user;
    this.username = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(user.username() || '');
    this.email = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(user.email() || '');
    this.isEmailConfirmed = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(user.isEmailConfirmed() || false);
    this.setPassword = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(false);
    this.password = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(user.password() || '');
    const userGroups = user.groups() || [];
    _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].store.all('groups').filter(group => ![_models_Group__WEBPACK_IMPORTED_MODULE_5__["default"].GUEST_ID, _models_Group__WEBPACK_IMPORTED_MODULE_5__["default"].MEMBER_ID].includes(group.id())).forEach(group => this.groups[group.id()] = (0,_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(userGroups.includes(group)));
  }
  className() {
    return 'EditUserModal Modal--small';
  }
  title() {
    return _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.title');
  }
  content() {
    const fields = this.fields().toArray();
    return m("div", {
      className: "Modal-body"
    }, fields.length > 1 ? m(_Form__WEBPACK_IMPORTED_MODULE_9__["default"], null, this.fields().toArray()) : _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.nothing_available'));
  }
  fields() {
    const items = new _utils_ItemList__WEBPACK_IMPORTED_MODULE_7__["default"]();
    if (this.attrs.user.canEditCredentials()) {
      items.add('username', m("div", {
        className: "Form-group"
      }, m("label", null, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.username_heading')), m("input", {
        className: "FormControl",
        placeholder: (0,_utils_extractText__WEBPACK_IMPORTED_MODULE_6__["default"])(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.username_label')),
        bidi: this.username,
        disabled: this.nonAdminEditingAdmin()
      })), 40);
      if (_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user !== this.attrs.user) {
        items.add('email', m("div", {
          className: "Form-group"
        }, m("label", null, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.email_heading')), m("input", {
          className: "FormControl",
          placeholder: (0,_utils_extractText__WEBPACK_IMPORTED_MODULE_6__["default"])(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.email_label')),
          bidi: this.email,
          disabled: this.nonAdminEditingAdmin()
        }), !this.isEmailConfirmed() && this.userIsAdmin(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user) && m(_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
          className: "Button Button--block",
          loading: this.loading,
          onclick: this.activate.bind(this)
        }, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.activate_button'))), 30);
        items.add('password', m("div", {
          className: "Form-group"
        }, m("label", null, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.password_heading')), m("div", null, m("label", {
          className: "checkbox"
        }, m("input", {
          type: "checkbox",
          onchange: e => {
            const target = e.target;
            this.setPassword(target.checked);
            m.redraw.sync();
            if (target.checked) this.$('[name=password]').select();
            e.redraw = false;
          },
          disabled: this.nonAdminEditingAdmin()
        }), _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.set_password_label'))), this.setPassword() && m("input", {
          className: "FormControl",
          type: "password",
          name: "password",
          placeholder: (0,_utils_extractText__WEBPACK_IMPORTED_MODULE_6__["default"])(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.password_label')),
          bidi: this.password,
          disabled: this.nonAdminEditingAdmin()
        })), 20);
      }
    }
    if (this.attrs.user.canEditGroups()) {
      items.add('groups', m("div", {
        className: "Form-group EditUserModal-groups"
      }, m("label", null, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.groups_heading')), m("div", null, Object.keys(this.groups).map(id => _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].store.getById('groups', id)).filter(Boolean).map(group =>
      // Necessary because filter(Boolean) doesn't narrow out falsy values.
      group && m("label", {
        className: "checkbox"
      }, m("input", {
        type: "checkbox",
        bidi: this.groups[group.id()],
        disabled: group.id() === _models_Group__WEBPACK_IMPORTED_MODULE_5__["default"].ADMINISTRATOR_ID && (this.attrs.user === _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user || !this.userIsAdmin(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user))
      }), m(_GroupBadge__WEBPACK_IMPORTED_MODULE_4__["default"], {
        group: group,
        label: null
      }), " ", group.nameSingular())))), 10);
    }
    items.add('submit', m("div", {
      className: "Form-group Form-controls"
    }, m(_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--primary",
      type: "submit",
      loading: this.loading
    }, _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.edit_user.submit_button'))), -10);
    return items;
  }
  activate() {
    this.loading = true;
    const data = {
      username: this.username(),
      isEmailConfirmed: true
    };
    this.attrs.user.save(data, {
      errorHandler: this.onerror.bind(this)
    }).then(() => {
      this.isEmailConfirmed(true);
      this.loading = false;
      m.redraw();
    }).catch(() => {
      this.loading = false;
      m.redraw();
    });
  }
  data() {
    const data = {};
    const relationships = {};
    if (this.attrs.user.canEditCredentials() && !this.nonAdminEditingAdmin()) {
      data.username = this.username();
      if (_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user !== this.attrs.user) {
        data.email = this.email();
      }
      if (this.setPassword()) {
        data.password = this.password();
      }
    }
    if (this.attrs.user.canEditGroups()) {
      relationships.groups = Object.keys(this.groups).filter(id => this.groups[id]()).map(id => _common_app__WEBPACK_IMPORTED_MODULE_1__["default"].store.getById('groups', id)).filter(g => g instanceof _models_Group__WEBPACK_IMPORTED_MODULE_5__["default"]);
    }
    data.relationships = relationships;
    return data;
  }
  onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    this.attrs.user.save(this.data(), {
      errorHandler: this.onerror.bind(this)
    }).then(this.hide.bind(this)).catch(() => {
      this.loading = false;
      m.redraw();
    });
  }
  nonAdminEditingAdmin() {
    return this.userIsAdmin(this.attrs.user) && !this.userIsAdmin(_common_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user);
  }

  /**
   * @internal
   */
  userIsAdmin(user) {
    return !!((user == null ? void 0 : user.groups()) || []).some(g => (g == null ? void 0 : g.id()) === _models_Group__WEBPACK_IMPORTED_MODULE_5__["default"].ADMINISTRATOR_ID);
  }
}
flarum.reg.add('core', 'common/components/EditUserModal', EditUserModal);

/***/ })

}]);
//# sourceMappingURL=EditUserModal.js.map