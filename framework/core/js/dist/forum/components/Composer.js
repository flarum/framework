"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/Composer"],{

/***/ "./src/forum/components/Composer.js":
/*!******************************************!*\
  !*** ./src/forum/components/Composer.js ***!
  \******************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Composer)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _ComposerButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ComposerButton */ "./src/forum/components/ComposerButton.js");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_utils_classList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/classList */ "./src/common/utils/classList.ts");
/* harmony import */ var _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../states/ComposerState */ "./src/forum/states/ComposerState.js");








/**
 * The `Composer` component displays the composer. It can be loaded with a
 * content component with `load` and then its position/state can be altered with
 * `show`, `hide`, `close`, `minimize`, `fullScreen`, and `exitFullScreen`.
 */
class Composer extends _common_Component__WEBPACK_IMPORTED_MODULE_1__["default"] {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The composer's "state".
     *
     * @type {ComposerState}
     */
    this.state = this.attrs.state;

    /**
     * Whether or not the composer currently has focus.
     *
     * @type {Boolean}
     */
    this.active = false;

    // Store the initial position so that we can trigger animations correctly.
    this.prevPosition = this.state.position;
  }
  view() {
    const body = this.state.body;
    const classes = {
      normal: this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.NORMAL,
      minimized: this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.MINIMIZED,
      fullScreen: this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.FULLSCREEN,
      active: this.active,
      visible: this.state.isVisible()
    };

    // Set up a handler so that clicks on the content will show the composer.
    const showIfMinimized = this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.MINIMIZED ? this.state.show.bind(this.state) : undefined;
    const ComposerBody = body.componentClass;
    return m("div", {
      className: 'Composer ' + (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_5__["default"])(classes)
    }, m("div", {
      className: "Composer-handle",
      oncreate: this.configHandle.bind(this)
    }), m("ul", {
      className: "Composer-controls"
    }, (0,_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_4__["default"])(this.controlItems().toArray())), m("div", {
      className: "Composer-content",
      onclick: showIfMinimized
    }, ComposerBody && m(ComposerBody, Object.assign({}, body.attrs, {
      composer: this.state,
      disabled: classes.minimized
    }))));
  }
  onupdate(vnode) {
    super.onupdate(vnode);
    if (this.state.position === this.prevPosition) {
      // Set the height of the Composer element and its contents on each redraw,
      // so that they do not lose it if their DOM elements are recreated.
      this.updateHeight();
    } else {
      this.animatePositionChange();
      this.prevPosition = this.state.position;
    }
  }
  oncreate(vnode) {
    super.oncreate(vnode);
    this.initializeHeight();
    this.$().hide().css('bottom', -this.state.computedHeight());

    // Whenever any of the inputs inside the composer are have focus, we want to
    // add a class to the composer to draw attention to it.
    this.$().on('focus blur', ':input,.TextEditor-editorContainer', e => {
      this.active = e.type === 'focusin';
      m.redraw();
    });

    // When the escape key is pressed on any inputs, close the composer.
    this.$().on('keydown', ':input,.TextEditor-editorContainer', 'esc', () => this.state.close());
    this.handlers = {};
    $(window).on('resize', this.handlers.onresize = this.updateHeight.bind(this)).resize();
    $(document).on('mousemove', this.handlers.onmousemove = this.onmousemove.bind(this)).on('mouseup', this.handlers.onmouseup = this.onmouseup.bind(this));
  }
  onremove(vnode) {
    super.onremove(vnode);
    $(window).off('resize', this.handlers.onresize);
    $(document).off('mousemove', this.handlers.onmousemove).off('mouseup', this.handlers.onmouseup);
  }

  /**
   * Add the necessary event handlers to the composer's handle so that it can
   * be used to resize the composer.
   */
  configHandle(vnode) {
    const composer = this;
    $(vnode.dom).css('cursor', 'row-resize').bind('dragstart mousedown', e => e.preventDefault()).mousedown(function (e) {
      composer.mouseStart = e.clientY;
      composer.heightStart = composer.$().height();
      composer.handle = $(this);
      $('body').css('cursor', 'row-resize');
    });
  }

  /**
   * Resize the composer according to mouse movement.
   *
   * @param {MouseEvent} e
   */
  onmousemove(e) {
    if (!this.handle) return;

    // Work out how much the mouse has been moved, and set the height
    // relative to the old one based on that. Then update the content's
    // height so that it fills the height of the composer, and update the
    // body's padding.
    const deltaPixels = this.mouseStart - e.clientY;
    this.changeHeight(this.heightStart + deltaPixels);

    // Update the body's padding-bottom so that no content on the page will ever
    // get permanently hidden behind the composer. If the user is already
    // scrolled to the bottom of the page, then we will keep them scrolled to
    // the bottom after the padding has been updated.
    const scrollTop = $(window).scrollTop();
    const anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
    this.updateBodyPadding(anchorToBottom);
  }

  /**
   * Finish resizing the composer when the mouse is released.
   */
  onmouseup() {
    if (!this.handle) return;
    this.handle = null;
    $('body').css('cursor', '');
  }

  /**
   * Draw focus to the first focusable content element (the text editor).
   */
  focus() {
    this.$('.Composer-content :input:enabled:visible, .TextEditor-editor').first().focus();
  }

  /**
   * Update the DOM to reflect the composer's current height. This involves
   * setting the height of the composer's root element, and adjusting the height
   * of any flexible elements inside the composer's body.
   */
  updateHeight() {
    const height = this.state.computedHeight();
    const $flexible = this.$('.Composer-flexible');
    this.$().height(height);
    if ($flexible.length) {
      const headerHeight = $flexible.offset().top - this.$().offset().top;
      const paddingBottom = parseInt($flexible.css('padding-bottom'), 10);
      const footerHeight = this.$('.Composer-footer').outerHeight(true);
      $flexible.height(this.$().outerHeight() - headerHeight - paddingBottom - footerHeight);
    }
  }

  /**
   * Update the amount of padding-bottom on the body so that the page's
   * content will still be visible above the composer when the page is
   * scrolled right to the bottom.
   */
  updateBodyPadding() {
    const visible = this.state.position !== _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.HIDDEN && this.state.position !== _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.MINIMIZED && _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].screen() !== 'phone';
    const paddingBottom = visible ? this.state.computedHeight() - parseInt($('#app').css('padding-bottom'), 10) : 0;
    $('#content').css({
      paddingBottom
    });
  }

  /**
   * Trigger the right animation depending on the desired new position.
   */
  animatePositionChange() {
    // When exiting full-screen mode: focus content
    if (this.prevPosition === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.FULLSCREEN && this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.NORMAL) {
      this.focus();
      return;
    }
    switch (this.state.position) {
      case _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.HIDDEN:
        return this.hide();
      case _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.MINIMIZED:
        return this.minimize();
      case _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.FULLSCREEN:
        return this.focus();
      case _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.NORMAL:
        return this.show();
    }
  }

  /**
   * Animate the Composer into the new position by changing the height.
   */
  animateHeightChange() {
    const $composer = this.$().stop(true);
    const oldHeight = $composer.outerHeight();
    const scrollTop = $(window).scrollTop();
    $composer.show();
    this.updateHeight();
    const newHeight = $composer.outerHeight();
    if (this.prevPosition === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.HIDDEN) {
      $composer.css({
        bottom: -newHeight,
        height: newHeight
      });
    } else {
      $composer.css({
        height: oldHeight
      });
    }
    const animation = $composer.animate({
      bottom: 0,
      height: newHeight
    }, 'fast').promise();
    this.updateBodyPadding();
    $(window).scrollTop(scrollTop);
    return animation;
  }

  /**
   * Show the Composer backdrop.
   */
  showBackdrop() {
    this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
  }

  /**
   * Hide the Composer backdrop.
   */
  hideBackdrop() {
    if (this.$backdrop) this.$backdrop.remove();
  }

  /**
   * Animate the composer sliding up from the bottom to take its normal height.
   *
   * @private
   */
  show() {
    this.animateHeightChange().then(() => this.focus());
    if (_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].screen() === 'phone') {
      // On safari fixed position doesn't properly work on mobile,
      // So we use absolute and set the top value.
      // https://github.com/flarum/core/issues/2652

      // Due to another safari bug, `scrollTop` is unreliable when
      // at the very bottom of the page AND opening the composer.
      // So we fallback to a calculated version of scrollTop.
      // https://github.com/flarum/core/issues/2683
      const scrollElement = document.documentElement;
      const topOfViewport = Math.min(scrollElement.scrollTop, scrollElement.scrollHeight - scrollElement.clientHeight);
      this.$().css('top', $('.App').is('.mobile-safari') ? topOfViewport : 0);
      this.showBackdrop();
    }
  }

  /**
   * Animate closing the composer.
   *
   * @private
   */
  hide() {
    const $composer = this.$();

    // Animate the composer sliding down off the bottom edge of the viewport.
    // Only when the animation is completed, update other elements on the page.
    $composer.stop(true).animate({
      bottom: -$composer.height()
    }, 'fast', () => {
      $composer.hide();
      this.hideBackdrop();
      this.updateBodyPadding();
    });
  }

  /**
   * Shrink the composer until only its title is visible.
   *
   * @private
   */
  minimize() {
    this.animateHeightChange();
    this.$().css('top', 'auto');
    this.hideBackdrop();
  }

  /**
   * Build an item list for the composer's controls.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controlItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_2__["default"]();
    if (this.state.position === _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.FULLSCREEN) {
      items.add('exitFullScreen', m(_ComposerButton__WEBPACK_IMPORTED_MODULE_3__["default"], {
        icon: "fas fa-compress",
        title: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer.exit_full_screen_tooltip'),
        onclick: this.state.exitFullScreen.bind(this.state)
      }));
    } else {
      if (this.state.position !== _states_ComposerState__WEBPACK_IMPORTED_MODULE_6__["default"].Position.MINIMIZED) {
        items.add('minimize', m(_ComposerButton__WEBPACK_IMPORTED_MODULE_3__["default"], {
          icon: (0,_common_utils_classList__WEBPACK_IMPORTED_MODULE_5__["default"])('fas minimize', {
            'fa-minus': _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].screen() !== 'phone',
            'fa-times': _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].screen() === 'phone'
          }),
          title: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer.minimize_tooltip'),
          onclick: this.state.minimize.bind(this.state),
          itemClassName: "App-backControl"
        }));
        items.add('fullScreen', m(_ComposerButton__WEBPACK_IMPORTED_MODULE_3__["default"], {
          icon: "fas fa-expand",
          title: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer.full_screen_tooltip'),
          onclick: this.state.fullScreen.bind(this.state)
        }));
      }
      items.add('close', m(_ComposerButton__WEBPACK_IMPORTED_MODULE_3__["default"], {
        icon: "fas fa-times",
        title: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.composer.close_tooltip'),
        onclick: this.state.close.bind(this.state)
      }));
    }
    return items;
  }

  /**
   * Initialize default Composer height.
   */
  initializeHeight() {
    this.state.height = localStorage.getItem('composerHeight');
    if (!this.state.height) {
      this.state.height = this.defaultHeight();
    }
  }

  /**
   * Default height of the Composer in case none is saved.
   * @returns {number}
   */
  defaultHeight() {
    return this.$().height();
  }

  /**
   * Save a new Composer height and update the DOM.
   * @param {number} height
   */
  changeHeight(height) {
    this.state.height = height;
    this.updateHeight();
    localStorage.setItem('composerHeight', this.state.height);
  }
}
flarum.reg.add('core', 'forum/components/Composer', Composer);

/***/ }),

/***/ "./src/forum/components/ComposerButton.js":
/*!************************************************!*\
  !*** ./src/forum/components/ComposerButton.js ***!
  \************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ComposerButton)
/* harmony export */ });
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");


/**
 * The `ComposerButton` component displays a button suitable for the composer
 * controls.
 */
class ComposerButton extends _common_components_Button__WEBPACK_IMPORTED_MODULE_0__["default"] {
  static initAttrs(attrs) {
    super.initAttrs(attrs);
    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }
}
flarum.reg.add('core', 'forum/components/ComposerButton', ComposerButton);

/***/ })

}]);
//# sourceMappingURL=Composer.js.map