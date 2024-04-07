"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/UserSecurityPage"],{

/***/ "./src/common/components/LabelValue.tsx":
/*!**********************************************!*\
  !*** ./src/common/components/LabelValue.tsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LabelValue)
/* harmony export */ });
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../app */ "./src/common/app.ts");


/**
 * A generic component for displaying a label and value inline.
 * Created to avoid reinventing the wheel.
 *
 * `label: value`
 */
class LabelValue extends _Component__WEBPACK_IMPORTED_MODULE_0__["default"] {
  view(vnode) {
    return m("div", {
      className: "LabelValue"
    }, m("div", {
      className: "LabelValue-label"
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.lib.data_segment.label', {
      label: this.attrs.label
    })), m("div", {
      className: "LabelValue-value"
    }, this.attrs.value));
  }
}
flarum.reg.add('core', 'common/components/LabelValue', LabelValue);

/***/ }),

/***/ "./src/forum/components/AccessTokensList.tsx":
/*!***************************************************!*\
  !*** ./src/forum/components/AccessTokensList.tsx ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AccessTokensList)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../app */ "./src/forum/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/helpers/humanTime */ "./src/common/helpers/humanTime.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_LabelValue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/LabelValue */ "./src/common/components/LabelValue.tsx");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_utils_classList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/utils/classList */ "./src/common/utils/classList.ts");
/* harmony import */ var _common_components_Tooltip__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/components/Tooltip */ "./src/common/components/Tooltip.tsx");
/* harmony import */ var _common_components_Icon__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/components/Icon */ "./src/common/components/Icon.tsx");











class AccessTokensList extends _common_Component__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loading", {});
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "showingTokens", {});
  }
  view(vnode) {
    return m("div", {
      className: "AccessTokensList"
    }, this.attrs.tokens.length ? this.attrs.tokens.map(this.tokenView.bind(this)) : m("div", {
      className: "AccessTokensList--empty"
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.empty_text')));
  }
  tokenView(token) {
    return m("div", {
      className: (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_8__["default"])('AccessTokensList-item', {
        'AccessTokensList-item--active': token.isCurrent()
      }),
      key: token.id()
    }, this.tokenViewItems(token).toArray());
  }
  tokenViewItems(token) {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
    items.add('icon', m("div", {
      className: "AccessTokensList-item-icon"
    }, m(_common_components_Icon__WEBPACK_IMPORTED_MODULE_10__["default"], {
      name: this.attrs.icon || 'fas fa-key'
    })), 50);
    items.add('info', m("div", {
      className: "AccessTokensList-item-info"
    }, this.tokenInfoItems(token).toArray()), 40);
    items.add('actions', m("div", {
      className: "AccessTokensList-item-actions"
    }, this.tokenActionItems(token).toArray()), 30);
    return items;
  }
  tokenInfoItems(token) {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
    if (this.attrs.type === 'session') {
      items.add('title', m("div", {
        className: "AccessTokensList-item-title"
      }, m("span", {
        className: "AccessTokensList-item-title-main"
      }, token.device()), token.isCurrent() && [' — ', m("span", {
        className: "AccessTokensList-item-title-sub"
      }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.current_active_session'))]));
    } else {
      items.add('title', m("div", {
        className: "AccessTokensList-item-title"
      }, m("span", {
        className: "AccessTokensList-item-title-main"
      }, this.generateTokenTitle(token))));
    }
    items.add('createdAt', m("div", {
      className: "AccessTokensList-item-createdAt"
    }, m(_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_6__["default"], {
      label: _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.created'),
      value: (0,_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_4__["default"])(token.createdAt())
    })));
    items.add('lastActivityAt', m("div", {
      className: "AccessTokensList-item-lastActivityAt"
    }, m(_common_components_LabelValue__WEBPACK_IMPORTED_MODULE_6__["default"], {
      label: _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.last_activity'),
      value: token.lastActivityAt() ? m('[', null, (0,_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_4__["default"])(token.lastActivityAt()), token.lastIpAddress() && " \u2014 ".concat(token.lastIpAddress()), this.attrs.type === 'developer_token' && token.device() && m('[', null, ' ', "\u2014 ", m("span", {
        className: "AccessTokensList-item-title-sub"
      }, token.device()))) : _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.never')
    })));
    return items;
  }
  tokenActionItems(token) {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
    const deleteKey = {
      session: 'terminate_session',
      developer_token: 'revoke_access_token'
    }[this.attrs.type];
    if (this.attrs.type === 'developer_token') {
      const isHidden = !this.showingTokens[token.id()];
      const displayKey = isHidden ? 'show_access_token' : 'hide_access_token';
      items.add('toggleDisplay', m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
        className: "Button Button--inverted",
        icon: isHidden ? 'fas fa-eye' : 'fas fa-eye-slash',
        onclick: () => {
          this.showingTokens[token.id()] = isHidden;
          m.redraw();
        }
      }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.security.".concat(displayKey))));
    }
    let revokeButton = m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--danger",
      disabled: token.isCurrent(),
      loading: !!this.loading[token.id()],
      onclick: () => this.revoke(token)
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.security.".concat(deleteKey)));
    if (token.isCurrent()) {
      revokeButton = m(_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_9__["default"], {
        text: _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.cannot_terminate_current_session')
      }, m("div", {
        tabindex: "0"
      }, revokeButton));
    }
    items.add('revoke', revokeButton);
    return items;
  }
  async revoke(token) {
    var _this$attrs$ondelete, _this$attrs;
    if (!confirm((0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7__["default"])(_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.revoke_access_token_confirmation')))) return;
    this.loading[token.id()] = true;
    await token.delete();
    this.loading[token.id()] = false;
    (_this$attrs$ondelete = (_this$attrs = this.attrs).ondelete) == null ? void 0 : _this$attrs$ondelete.call(_this$attrs, token);
    const key = this.attrs.type === 'session' ? 'session_terminated' : 'token_revoked';
    _app__WEBPACK_IMPORTED_MODULE_1__["default"].alerts.show({
      type: 'success'
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.security.".concat(key), {
      count: 1
    }));
    m.redraw();
  }
  generateTokenTitle(token) {
    const name = token.title() || _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.token_title_placeholder');
    const value = this.tokenValueDisplay(token);
    return _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.token_item_title', {
      title: name,
      token: value
    });
  }
  tokenValueDisplay(token) {
    const obfuscatedName = Array(12).fill('*').join('');
    const value = this.showingTokens[token.id()] ? token.token() : obfuscatedName;
    return m("code", {
      className: "AccessTokensList-item-token"
    }, value);
  }
}
flarum.reg.add('core', 'forum/components/AccessTokensList', AccessTokensList);

/***/ }),

/***/ "./src/forum/components/NewAccessTokenModal.tsx":
/*!******************************************************!*\
  !*** ./src/forum/components/NewAccessTokenModal.tsx ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ NewAccessTokenModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");
/* harmony import */ var _common_components_Form__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Form */ "./src/common/components/Form.tsx");






class NewAccessTokenModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "titleInput", (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_4__["default"])(''));
  }
  className() {
    return 'Modal--small NewAccessTokenModal';
  }
  title() {
    return _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.new_access_token_modal.title');
  }
  content() {
    const titleLabel = _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.new_access_token_modal.title_placeholder');
    return m("div", {
      className: "Modal-body"
    }, m(_common_components_Form__WEBPACK_IMPORTED_MODULE_5__["default"], {
      className: "Form--centered"
    }, m("div", {
      className: "Form-group"
    }, m("input", {
      type: "text",
      className: "FormControl",
      bidi: this.titleInput,
      placeholder: titleLabel,
      "aria-label": titleLabel
    })), m("div", {
      className: "Form-group Form-controls"
    }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_3__["default"], {
      className: "Button Button--primary Button--block",
      type: "submit",
      loading: this.loading
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.new_access_token_modal.submit_button')))));
  }
  submitData() {
    return {
      title: this.titleInput()
    };
  }
  onsubmit(e) {
    super.onsubmit(e);
    e.preventDefault();
    this.loading = true;
    _app__WEBPACK_IMPORTED_MODULE_1__["default"].store.createRecord('access-tokens').save(this.submitData()).then(token => {
      this.attrs.onsuccess(token);
      _app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.close();
    }).finally(this.loaded.bind(this));
  }
}
flarum.reg.add('core', 'forum/components/NewAccessTokenModal', NewAccessTokenModal);

/***/ }),

/***/ "./src/forum/components/UserSecurityPage.tsx":
/*!***************************************************!*\
  !*** ./src/forum/components/UserSecurityPage.tsx ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ UserSecurityPage)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _UserPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserPage */ "./src/forum/components/UserPage.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_components_FieldSet__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/components/FieldSet */ "./src/common/components/FieldSet.tsx");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _AccessTokensList__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./AccessTokensList */ "./src/forum/components/AccessTokensList.tsx");
/* harmony import */ var _common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/components/LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _NewAccessTokenModal__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./NewAccessTokenModal */ "./src/forum/components/NewAccessTokenModal.tsx");
/* harmony import */ var _common_components_Tooltip__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../common/components/Tooltip */ "./src/common/components/Tooltip.tsx");
/* harmony import */ var _states_UserSecurityPageState__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../states/UserSecurityPageState */ "./src/forum/states/UserSecurityPageState.ts");














/**
 * The `UserSecurityPage` component displays the user's security control panel, in
 * the context of their user profile.
 */
class UserSecurityPage extends _UserPage__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "state", new _states_UserSecurityPageState__WEBPACK_IMPORTED_MODULE_12__["default"]());
  }
  oninit(vnode) {
    var _app$session$user;
    super.oninit(vnode);
    const routeUsername = m.route.param('username');
    if (routeUsername !== ((_app$session$user = _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user) == null ? void 0 : _app$session$user.slug()) && !_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('canModerateAccessTokens')) {
      m.route.set('/');
    }
    this.loadUser(routeUsername);
    _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].setTitle((0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.title')));
    this.loadTokens();
  }
  content() {
    return m("div", {
      className: "UserSecurityPage"
    }, m("ul", null, (0,_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__["default"])(this.settingsItems().toArray())));
  }

  /**
   * Build an item list for the user's settings controls.
   */
  settingsItems() {
    var _this$state$getDevelo;
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    if (_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('canCreateAccessToken') || _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('canModerateAccessTokens') || this.state.hasLoadedTokens() && (_this$state$getDevelo = this.state.getDeveloperTokens()) != null && _this$state$getDevelo.length) {
      items.add('developerTokens', m(_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_4__["default"], {
        className: "UserSecurityPage-developerTokens",
        label: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.security.developer_tokens_heading")
      }, this.developerTokensItems().toArray()));
    } else if (!this.state.hasLoadedTokens()) {
      items.add('developerTokens', m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__["default"], null));
    }
    items.add('sessions', m(_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_4__["default"], {
      className: "UserSecurityPage-sessions",
      label: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans("core.forum.security.sessions_heading")
    }, this.sessionsItems().toArray()));
    if (this.user.id() === _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.id()) {
      items.add('globalLogout', m(_common_components_FieldSet__WEBPACK_IMPORTED_MODULE_4__["default"], {
        className: "FieldSet--col UserSecurityPage-globalLogout",
        label: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.global_logout.heading'),
        description: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.global_logout.help_text')
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_9__["default"], {
        className: "Button",
        icon: "fas fa-sign-out-alt",
        onclick: this.globalLogout.bind(this),
        loading: this.state.loadingGlobalLogout,
        disabled: this.state.loadingTerminateSessions
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.global_logout.log_out_button'))));
    }
    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  developerTokensItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('accessTokenList', !this.state.hasLoadedTokens() ? m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__["default"], null) : m(_AccessTokensList__WEBPACK_IMPORTED_MODULE_7__["default"], {
      type: "developer_token",
      ondelete: token => {
        this.state.removeToken(token);
        m.redraw();
      },
      tokens: this.state.getDeveloperTokens(),
      icon: "fas fa-key",
      hideTokens: false
    }));
    if (this.user.id() === _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.id()) {
      items.add('newAccessToken', m("div", {
        className: "UserSecurityPage-controls"
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_9__["default"], {
        className: "Button",
        disabled: !_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('canCreateAccessToken'),
        onclick: () => _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].modal.show(_NewAccessTokenModal__WEBPACK_IMPORTED_MODULE_10__["default"], {
          onsuccess: token => {
            this.state.pushToken(token);
            m.redraw();
          }
        })
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.new_access_token_button'))));
    }
    return items;
  }

  /**
   * Build an item list for the user's access accessToken settings.
   */
  sessionsItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_3__["default"]();
    items.add('sessionsList', !this.state.hasLoadedTokens() ? m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_8__["default"], null) : m(_AccessTokensList__WEBPACK_IMPORTED_MODULE_7__["default"], {
      type: "session",
      ondelete: token => {
        this.state.removeToken(token);
        m.redraw();
      },
      tokens: this.state.getSessionTokens(),
      icon: "fas fa-laptop",
      hideTokens: true
    }));
    if (this.user.id() === _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].session.user.id()) {
      const isDisabled = !this.state.hasOtherActiveSessions();
      let terminateAllOthersButton = m(_common_components_Button__WEBPACK_IMPORTED_MODULE_9__["default"], {
        className: "Button",
        onclick: this.terminateAllOtherSessions.bind(this),
        loading: this.state.loadingTerminateSessions,
        disabled: this.state.loadingGlobalLogout || isDisabled
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.terminate_all_other_sessions'));
      if (isDisabled) {
        terminateAllOthersButton = m(_common_components_Tooltip__WEBPACK_IMPORTED_MODULE_11__["default"], {
          text: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.cannot_terminate_current_session')
        }, m("span", {
          tabindex: "0"
        }, terminateAllOthersButton));
      }
      items.add('terminateAllOtherSessions', m("div", {
        className: "UserSecurityPage-controls"
      }, terminateAllOthersButton));
    }
    return items;
  }
  loadTokens() {
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].store.find('access-tokens', {
      filter: {
        user: this.user.id()
      }
    }).then(tokens => {
      this.state.setTokens(tokens);
      m.redraw();
    });
  }
  terminateAllOtherSessions() {
    if (!confirm((0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_6__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.terminate_all_other_sessions_confirmation')))) return;
    this.state.loadingTerminateSessions = true;
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].request({
      method: 'DELETE',
      url: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('apiUrl') + '/sessions'
    }).then(() => {
      // Count terminated sessions first.
      const count = this.state.getOtherSessionTokens().length;
      this.state.removeOtherSessionTokens();
      _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].alerts.show({
        type: 'success'
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.session_terminated', {
        count
      }));
    }).catch(() => {
      _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].alerts.show({
        type: 'error'
      }, _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.security.session_termination_failed'));
    }).finally(() => {
      this.state.loadingTerminateSessions = false;
      m.redraw();
    });
  }
  globalLogout() {
    this.state.loadingGlobalLogout = true;
    return _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].request({
      method: 'POST',
      url: _forum_app__WEBPACK_IMPORTED_MODULE_1__["default"].forum.attribute('baseUrl') + '/global-logout'
    }).then(() => window.location.reload()).finally(() => {
      this.state.loadingGlobalLogout = false;
      m.redraw();
    });
  }
}
flarum.reg.add('core', 'forum/components/UserSecurityPage', UserSecurityPage);

/***/ }),

/***/ "./src/forum/states/UserSecurityPageState.ts":
/*!***************************************************!*\
  !*** ./src/forum/states/UserSecurityPageState.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ UserSecurityPageState)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");

class UserSecurityPageState {
  constructor() {
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "tokens", null);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loadingTerminateSessions", false);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loadingGlobalLogout", false);
  }
  hasLoadedTokens() {
    return this.tokens !== null;
  }
  getTokens() {
    return this.tokens;
  }
  setTokens(tokens) {
    this.tokens = tokens;
  }
  pushToken(token) {
    var _this$tokens;
    (_this$tokens = this.tokens) == null ? void 0 : _this$tokens.push(token);
  }
  removeToken(token) {
    this.tokens = this.tokens.filter(t => t !== token);
  }
  getSessionTokens() {
    var _this$tokens2;
    return ((_this$tokens2 = this.tokens) == null ? void 0 : _this$tokens2.filter(token => token.isSessionToken()).sort((a, b) => b.isCurrent() ? 1 : -1)) || [];
  }
  getDeveloperTokens() {
    var _this$tokens3;
    return ((_this$tokens3 = this.tokens) == null ? void 0 : _this$tokens3.filter(token => !token.isSessionToken())) || null;
  }

  /**
   * Look up session tokens other than the current one.
   */
  getOtherSessionTokens() {
    var _this$tokens4;
    return ((_this$tokens4 = this.tokens) == null ? void 0 : _this$tokens4.filter(token => token.isSessionToken() && !token.isCurrent())) || [];
  }
  hasOtherActiveSessions() {
    return (this.getOtherSessionTokens() || []).length > 0;
  }
  removeOtherSessionTokens() {
    this.tokens = this.tokens.filter(token => !token.isSessionToken() || token.isCurrent());
  }
}
flarum.reg.add('core', 'forum/states/UserSecurityPageState', UserSecurityPageState);

/***/ })

}]);
//# sourceMappingURL=UserSecurityPage.js.map