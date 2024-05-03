"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/SettingsPage"],{

/***/ "./src/forum/components/ChangeEmailModal.tsx":
/*!***************************************************!*\
  !*** ./src/forum/components/ChangeEmailModal.tsx ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ChangeEmailModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Form__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Form */ "./src/common/components/Form.tsx");








/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
class ChangeEmailModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    /**
     * The value of the email input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "email", void 0);
    /**
     * The value of the password input.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "password", void 0);
    /**
     * Whether or not the email has been changed successfully.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "success", false);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.email = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.email() || '');
    this.password = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__["default"])('');
  }
  className() {
    return 'ChangeEmailModal Modal--small';
  }
  title() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.title');
  }
  content() {
    return m("div", {
      className: "Modal-body"
    }, m(_common_components_Form__WEBPACK_IMPORTED_MODULE_6__["default"], {
      className: "Form--centered"
    }, this.fields().toArray()));
  }
  fields() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
    if (this.success) {
      items.add('help', m("p", {
        className: "helpText"
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.confirmation_message', {
        email: m("strong", null, this.email())
      })));
      items.add('dismiss', m("div", {
        className: "Form-group"
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
        className: "Button Button--primary Button--block",
        onclick: this.hide.bind(this)
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.dismiss_button'))));
    } else {
      items.add('email', m("div", {
        className: "Form-group"
      }, m("input", {
        type: "email",
        name: "email",
        className: "FormControl",
        placeholder: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.email(),
        bidi: this.email,
        disabled: this.loading
      })));
      items.add('password', m("div", {
        className: "Form-group"
      }, m("input", {
        type: "password",
        name: "password",
        className: "FormControl",
        autocomplete: "current-password",
        placeholder: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.confirm_password_placeholder'),
        bidi: this.password,
        disabled: this.loading
      })));
      items.add('submit', m("div", {
        className: "Form-group Form-controls"
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
        className: "Button Button--primary Button--block",
        type: "submit",
        loading: this.loading
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.submit_button'))));
    }
    return items;
  }
  onsubmit(e) {
    e.preventDefault();

    // If the user hasn't actually entered a different email address, we don't
    // need to do anything. Woot!
    if (this.email() === _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.email()) {
      this.hide();
      return;
    }
    this.loading = true;
    this.alertAttrs = null;
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.save(this.requestAttributes(), {
      errorHandler: this.onerror.bind(this),
      meta: {
        password: this.password()
      }
    }).then(() => {
      this.success = true;
    }).catch(() => {}).then(this.loaded.bind(this));
  }
  requestAttributes() {
    return {
      email: this.email()
    };
  }
  onerror(error) {
    if (error.status === 401 && error.alert) {
      error.alert.content = _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.change_email.incorrect_password_message');
    }
    super.onerror(error);
  }
}
flarum.reg.add('core', 'forum/components/ChangeEmailModal', ChangeEmailModal);

/***/ }),

/***/ "./src/forum/components/ChangePasswordModal.tsx":
/*!******************************************************!*\
  !*** ./src/forum/components/ChangePasswordModal.tsx ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ChangePasswordModal)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Form__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Form */ "./src/common/components/Form.tsx");






/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
class ChangePasswordModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_1__["default"] {
  className() {
    return 'ChangePasswordModal Modal--small';
  }
  title() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.change_password.title');
  }
  content() {
    return m("div", {
      className: "Modal-body"
    }, m(_common_components_Form__WEBPACK_IMPORTED_MODULE_4__["default"], {
      className: "Form--centered"
    }, this.fields().toArray()));
  }
  fields() {
    const fields = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    fields.add('help', m("p", {
      className: "helpText"
    }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.change_password.text')));
    fields.add('submit', m("div", {
      className: "Form-group Form-controls"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_2__["default"], {
      className: "Button Button--primary Button--block",
      type: "submit",
      loading: this.loading
    }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.change_password.send_button'))));
    return fields;
  }
  onsubmit(e) {
    e.preventDefault();
    this.loading = true;
    _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].request({
      method: 'POST',
      url: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].forum.attribute('apiUrl') + '/forgot',
      body: this.requestBody()
    }).then(this.hide.bind(this), this.loaded.bind(this));
  }
  requestBody() {
    return {
      email: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user.email()
    };
  }
}
flarum.reg.add('core', 'forum/components/ChangePasswordModal', ChangePasswordModal);

/***/ }),

/***/ "./src/forum/components/SettingsPage.tsx":
/*!***********************************************!*\
  !*** ./src/forum/components/SettingsPage.tsx ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SettingsPage)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _UserPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserPage */ "./src/forum/components/UserPage.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Switch__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Switch */ "./src/common/components/Switch.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/FieldSet */ "./src/common/components/FieldSet.tsx");
/* harmony import */ var _NotificationGrid__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./NotificationGrid */ "./src/forum/components/NotificationGrid.js");
/* harmony import */ var _ChangePasswordModal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./ChangePasswordModal */ "./src/forum/components/ChangePasswordModal.tsx");
/* harmony import */ var _ChangeEmailModal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./ChangeEmailModal */ "./src/forum/components/ChangeEmailModal.tsx");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_utils_classList__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../common/utils/classList */ "./src/common/utils/classList.ts");














/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
class SettingsPage extends _UserPage__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "discloseOnlineLoading", void 0);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.show(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user);
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].setTitle((0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_11__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.settings.title')));
  }
  content() {
    return m("div", {
      className: "SettingsPage"
    }, m("ul", null, (0,_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_10__["default"])(this.settingsItems().toArray())));
  }

  /**
   * Build an item list for the user's settings controls.
   */
  settingsItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    ['account', 'notifications', 'privacy'].forEach((section, index) => {
      const sectionItems = "".concat(section, "Items");
      items.add(section, m(_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_6__["default"], {
        className: (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_12__["default"])("Settings-".concat(section), {
          'FieldSet--col': section === 'account'
        }),
        label: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.settings.".concat(section, "_heading"))
      }, this[sectionItems]().toArray()), 100 - index * 10);
    });
    return items;
  }

  /**
   * Build an item list for the user's account settings.
   */
  accountItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('changePassword', m(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "Button",
      onclick: () => _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(_ChangePasswordModal__WEBPACK_IMPORTED_MODULE_8__["default"])
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.settings.change_password_button')), 100);
    items.add('changeEmail', m(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "Button",
      onclick: () => _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(_ChangeEmailModal__WEBPACK_IMPORTED_MODULE_9__["default"])
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.settings.change_email_button')), 90);
    return items;
  }

  /**
   * Build an item list for the user's notification settings.
   */
  notificationsItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('notificationGrid', m(_NotificationGrid__WEBPACK_IMPORTED_MODULE_7__["default"], {
      user: this.user
    }), 100);
    return items;
  }

  /**
   * Build an item list for the user's privacy settings.
   */
  privacyItems() {
    var _preferences;
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('discloseOnline', m(_common_components_Switch__WEBPACK_IMPORTED_MODULE_4__["default"], {
      state: (_preferences = this.user.preferences()) == null ? void 0 : _preferences.discloseOnline,
      onchange: value => {
        this.discloseOnlineLoading = true;
        this.user.savePreferences({
          discloseOnline: value
        }).then(() => {
          this.discloseOnlineLoading = false;
          m.redraw();
        });
      },
      loading: this.discloseOnlineLoading
    }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.settings.privacy_disclose_online_label')), 100);
    return items;
  }
}
flarum.reg.add('core', 'forum/components/SettingsPage', SettingsPage);

/***/ }),

/***/ "./src/forum/components/NotificationGrid.js":
/*!**************************************************!*\
  !*** ./src/forum/components/NotificationGrid.js ***!
  \**************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ NotificationGrid)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_components_Checkbox__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/Checkbox */ "./src/common/components/Checkbox.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_Icon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/Icon */ "./src/common/components/Icon.tsx");






/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 *
 * ### Attrs
 *
 * - `user`
 */
class NotificationGrid extends _common_Component__WEBPACK_IMPORTED_MODULE_1__["default"] {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * Information about the available notification methods.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.methods = this.notificationMethods().toArray();

    /**
     * A map of which notification checkboxes are loading.
     *
     * @type {Record<string, boolean>}
     */
    this.loading = {};

    /**
     * Information about the available notification types.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.types = this.notificationTypes().toArray();
  }
  view() {
    const preferences = this.attrs.user.preferences();
    return m("table", {
      className: "NotificationGrid"
    }, m("thead", null, m("tr", null, m("td", null), this.methods.map(method => m("th", {
      className: "NotificationGrid-groupToggle",
      onclick: this.toggleMethod.bind(this, method.name)
    }, m(_common_components_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
      name: method.icon
    }), " ", method.label)))), m("tbody", null, this.types.map(type => m("tr", null, m("td", {
      className: "NotificationGrid-groupToggle",
      onclick: this.toggleType.bind(this, type.name)
    }, m(_common_components_Icon__WEBPACK_IMPORTED_MODULE_4__["default"], {
      name: type.icon
    }), " ", type.label), this.methods.map(method => {
      const key = this.preferenceKey(type.name, method.name);
      return m("td", {
        className: "NotificationGrid-checkbox"
      }, m(_common_components_Checkbox__WEBPACK_IMPORTED_MODULE_2__["default"], {
        state: !!preferences[key],
        loading: this.loading[key],
        disabled: !(key in preferences),
        onchange: this.toggle.bind(this, [key])
      }, m("span", {
        className: "sr-only"
      }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.settings.notification_checkbox_a11y_label_template', {
        description: type.label,
        method: method.label
      }))));
    })))));
  }
  oncreate(vnode) {
    super.oncreate(vnode);
    this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      const i = parseInt($(this).index(), 10) + 1;
      $(this).parents('table').find('td:nth-child(' + i + ')').toggleClass('highlighted', e.type === 'mouseenter');
    });
    this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      $(this).parent().find('td').toggleClass('highlighted', e.type === 'mouseenter');
    });
  }

  /**
   * Toggle the state of the given preferences, based on the value of the first
   * one.
   *
   * @param {string[]} keys
   */
  toggle(keys) {
    const user = this.attrs.user;
    const preferences = user.preferences();
    const enabled = !preferences[keys[0]];
    keys.forEach(key => {
      this.loading[key] = true;
      preferences[key] = enabled;
    });
    m.redraw();
    user.save({
      preferences
    }).then(() => {
      keys.forEach(key => this.loading[key] = false);
      m.redraw();
    });
  }

  /**
   * Toggle all notification types for the given method.
   *
   * @param {string} method
   */
  toggleMethod(method) {
    const keys = this.types.map(type => this.preferenceKey(type.name, method)).filter(key => key in this.attrs.user.preferences());
    this.toggle(keys);
  }

  /**
   * Toggle all notification methods for the given type.
   *
   * @param {string} type
   */
  toggleType(type) {
    const keys = this.methods.map(method => this.preferenceKey(type, method.name)).filter(key => key in this.attrs.user.preferences());
    this.toggle(keys);
  }

  /**
   * Get the name of the preference key for the given notification type-method
   * combination.
   *
   * @param {string} type
   * @param {string} method
   * @return {string}
   */
  preferenceKey(type, method) {
    return 'notify_' + type + '_' + method;
  }

  /**
   * Build an item list for the notification methods to display in the grid.
   *
   * Each notification method is an object which has the following properties:
   *
   * - `name` The name of the notification method.
   * - `icon` The icon to display in the column header.
   * - `label` The label to display in the column header.
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children }>}
   */
  notificationMethods() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('alert', {
      name: 'alert',
      icon: 'fas fa-bell',
      label: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.settings.notify_by_web_heading')
    });
    items.add('email', {
      name: 'email',
      icon: 'far fa-envelope',
      label: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.settings.notify_by_email_heading')
    });
    return items;
  }

  /**
   * Build an item list for the notification types to display in the grid.
   *
   * Each notification type is an object which has the following properties:
   *
   * - `name` The name of the notification type.
   * - `icon` The icon to display in the notification grid row.
   * - `label` The label to display in the notification grid row.
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children}>}
   */
  notificationTypes() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('discussionRenamed', {
      name: 'discussionRenamed',
      icon: 'fas fa-pencil-alt',
      label: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.settings.notify_discussion_renamed_label')
    });
    return items;
  }
}
flarum.reg.add('core', 'forum/components/NotificationGrid', NotificationGrid);

/***/ })

}]);
//# sourceMappingURL=SettingsPage.js.map