"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/ReplyComposer"],{

/***/ "./src/common/components/ConfirmDocumentUnload.js":
/*!********************************************************!*\
  !*** ./src/common/components/ConfirmDocumentUnload.js ***!
  \********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ConfirmDocumentUnload)
/* harmony export */ });
/* harmony import */ var _Component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../Component */ "./src/common/Component.ts");


/**
 * The `ConfirmDocumentUnload` component can be used to register a global
 * event handler that prevents closing the browser window/tab based on the
 * return value of a given callback prop.
 *
 * ### Attrs
 *
 * - `when` - a callback returning true when the browser should prompt for
 *            confirmation before closing the window/tab
 */
class ConfirmDocumentUnload extends _Component__WEBPACK_IMPORTED_MODULE_0__["default"] {
  handler() {
    return this.attrs.when() || undefined;
  }
  oncreate(vnode) {
    super.oncreate(vnode);
    this.boundHandler = this.handler.bind(this);
    $(window).on('beforeunload', this.boundHandler);
  }
  onremove(vnode) {
    super.onremove(vnode);
    $(window).off('beforeunload', this.boundHandler);
  }
  view(vnode) {
    return m('[', null, vnode.children);
  }
}
flarum.reg.add('core', 'common/components/ConfirmDocumentUnload', ConfirmDocumentUnload);

/***/ }),

/***/ "./src/forum/components/ComposerBody.js":
/*!**********************************************!*\
  !*** ./src/forum/components/ComposerBody.js ***!
  \**********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ComposerBody)
/* harmony export */ });
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/components/LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _common_components_ConfirmDocumentUnload__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/ConfirmDocumentUnload */ "./src/common/components/ConfirmDocumentUnload.js");
/* harmony import */ var _common_components_TextEditor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/TextEditor */ "./src/common/components/TextEditor.js");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_utils_classList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/classList */ "./src/common/utils/classList.ts");
/* harmony import */ var _common_components_Avatar__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Avatar */ "./src/common/components/Avatar.tsx");









/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 *
 * ### Attrs
 *
 * - `composer`
 * - `originalContent`
 * - `submitLabel`
 * - `placeholder`
 * - `user`
 * - `confirmExit`
 * - `disabled`
 *
 * @abstract
 */
class ComposerBody extends _common_Component__WEBPACK_IMPORTED_MODULE_0__["default"] {
  oninit(vnode) {
    super.oninit(vnode);
    this.composer = this.attrs.composer;

    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    this.loading = false;

    // Let the composer state know to ask for confirmation under certain
    // circumstances, if the body supports / requires it and has a corresponding
    // confirmation question to ask.
    if (this.attrs.confirmExit) {
      this.composer.preventClosingWhen(() => this.hasChanges(), this.attrs.confirmExit);
    }
    this.composer.fields.content(this.attrs.originalContent || '');
  }
  view() {
    var _this$jumpToPreview;
    return m(_common_components_ConfirmDocumentUnload__WEBPACK_IMPORTED_MODULE_2__["default"], {
      when: this.hasChanges.bind(this)
    }, m("div", {
      className: (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_6__["default"])('ComposerBody', this.attrs.className)
    }, m(_common_components_Avatar__WEBPACK_IMPORTED_MODULE_7__["default"], {
      user: this.attrs.user,
      className: "ComposerBody-avatar"
    }), m("div", {
      className: "ComposerBody-content"
    }, m("ul", {
      className: "ComposerBody-header"
    }, (0,_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__["default"])(this.headerItems().toArray())), m("div", {
      className: "ComposerBody-editor"
    }, m(_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_3__["default"], {
      submitLabel: this.attrs.submitLabel,
      placeholder: this.attrs.placeholder,
      disabled: this.loading || this.attrs.disabled,
      composer: this.composer,
      preview: (_this$jumpToPreview = this.jumpToPreview) == null ? void 0 : _this$jumpToPreview.bind(this),
      onchange: this.composer.fields.content,
      onsubmit: this.onsubmit.bind(this),
      value: this.composer.fields.content()
    }))), m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_1__["default"], {
      display: "unset",
      containerClassName: (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_6__["default"])('ComposerBody-loading', this.loading && 'active'),
      size: "large"
    })));
  }

  /**
   * Check if there is any unsaved data.
   *
   * @return {boolean}
   */
  hasChanges() {
    const content = this.composer.fields.content();
    return content && content !== this.attrs.originalContent;
  }

  /**
   * Build an item list for the composer's header.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  headerItems() {
    return new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_5__["default"]();
  }

  /**
   * Handle the submit event of the text editor.
   *
   * @abstract
   */
  onsubmit() {}

  /**
   * Stop loading.
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
flarum.reg.add('core', 'forum/components/ComposerBody', ComposerBody);

/***/ }),

/***/ "./src/forum/components/ReplyComposer.js":
/*!***********************************************!*\
  !*** ./src/forum/components/ReplyComposer.js ***!
  \***********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ReplyComposer)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _ComposerBody__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ComposerBody */ "./src/forum/components/ComposerBody.js");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_components_Link__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/components/Link */ "./src/common/components/Link.js");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_components_Icon__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Icon */ "./src/common/components/Icon.tsx");






function minimizeComposerIfFullScreen(e) {
  if (_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer.isFullScreen()) {
    _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer.minimize();
    e.stopPropagation();
  }
}

/**
 * The `ReplyComposer` component displays the composer content for replying to a
 * discussion.
 *
 * ### Attrs
 *
 * - All of the attrs of ComposerBody
 * - `discussion`
 */
class ReplyComposer extends _ComposerBody__WEBPACK_IMPORTED_MODULE_1__["default"] {
  static initAttrs(attrs) {
    super.initAttrs(attrs);
    attrs.placeholder = attrs.placeholder || (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer_reply.body_placeholder'));
    attrs.submitLabel = attrs.submitLabel || _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer_reply.submit_button');
    attrs.confirmExit = attrs.confirmExit || (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer_reply.discard_confirmation'));
  }
  headerItems() {
    const items = super.headerItems();
    const discussion = this.attrs.discussion;
    items.add('title', m("h3", null, m(_common_components_Icon__WEBPACK_IMPORTED_MODULE_5__["default"], {
      name: 'fas fa-reply'
    }), ' ', m(_common_components_Link__WEBPACK_IMPORTED_MODULE_3__["default"], {
      href: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].route.discussion(discussion),
      onclick: minimizeComposerIfFullScreen
    }, discussion.title())));
    return items;
  }

  /**
   * Jump to the preview when triggered by the text editor.
   */
  jumpToPreview(e) {
    minimizeComposerIfFullScreen(e);
    m.route.set(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].route.discussion(this.attrs.discussion, 'reply'));
  }

  /**
   * Get the data to submit to the server when the reply is saved.
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      content: this.composer.fields.content(),
      relationships: {
        discussion: this.attrs.discussion
      }
    };
  }
  onsubmit() {
    const discussion = this.attrs.discussion;
    this.loading = true;
    m.redraw();
    const data = this.data();
    _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].store.createRecord('posts').save(data).then(post => {
      // If we're currently viewing the discussion which this reply was made
      // in, then we can update the post stream and scroll to the post.
      if (_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].viewingDiscussion(discussion)) {
        const stream = _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].current.get('stream');
        stream.update().then(() => stream.goToNumber(post.number()));
      } else {
        // Otherwise, we'll create an alert message to inform the user that
        // their reply has been posted, containing a button which will
        // transition to their new post when clicked.
        let alert;
        const viewButton = m(_common_components_Button__WEBPACK_IMPORTED_MODULE_2__["default"], {
          className: "Button Button--link",
          onclick: () => {
            m.route.set(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].route.post(post));
            _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].alerts.dismiss(alert);
          }
        }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer_reply.view_button'));
        alert = _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].alerts.show({
          type: 'success',
          controls: [viewButton]
        }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer_reply.posted_message'));
      }
      this.composer.hide();
    }, this.loaded.bind(this));
  }
}
flarum.reg.add('core', 'forum/components/ReplyComposer', ReplyComposer);

/***/ })

}]);
//# sourceMappingURL=ReplyComposer.js.map