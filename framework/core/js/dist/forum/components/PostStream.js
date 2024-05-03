"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/PostStream"],{

/***/ "./src/forum/components/LoadingPost.js":
/*!*********************************************!*\
  !*** ./src/forum/components/LoadingPost.js ***!
  \*********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LoadingPost)
/* harmony export */ });
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_components_Avatar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/components/Avatar */ "./src/common/components/Avatar.tsx");



/**
 * The `LoadingPost` component shows a placeholder that looks like a post,
 * indicating that the post is loading.
 */
class LoadingPost extends _common_Component__WEBPACK_IMPORTED_MODULE_0__["default"] {
  view() {
    return m("div", {
      className: "Post CommentPost LoadingPost"
    }, m("header", {
      className: "Post-header"
    }, m(_common_components_Avatar__WEBPACK_IMPORTED_MODULE_1__["default"], {
      user: null,
      className: "PostUser-avatar"
    }), m("div", {
      className: "fakeText"
    })), m("div", {
      className: "Post-body"
    }, m("div", {
      className: "fakeText"
    }), m("div", {
      className: "fakeText"
    }), m("div", {
      className: "fakeText"
    })));
  }
}
flarum.reg.add('core', 'forum/components/LoadingPost', LoadingPost);

/***/ }),

/***/ "./src/forum/components/PostStream.js":
/*!********************************************!*\
  !*** ./src/forum/components/PostStream.js ***!
  \********************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ PostStream)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_utils_ScrollListener__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/utils/ScrollListener */ "./src/common/utils/ScrollListener.js");
/* harmony import */ var _LoadingPost__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./LoadingPost */ "./src/forum/components/LoadingPost.js");
/* harmony import */ var _ReplyPlaceholder__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ReplyPlaceholder */ "./src/forum/components/ReplyPlaceholder.js");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");








/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `stream`
 * - `targetPost`
 * - `onPositionChange`
 */
class PostStream extends _common_Component__WEBPACK_IMPORTED_MODULE_1__["default"] {
  oninit(vnode) {
    super.oninit(vnode);
    this.discussion = this.attrs.discussion;
    this.stream = this.attrs.stream;
    this.scrollListener = new _common_utils_ScrollListener__WEBPACK_IMPORTED_MODULE_2__["default"](this.onscroll.bind(this));
  }
  view() {
    let lastTime;
    const viewingEnd = this.stream.viewingEnd();
    const posts = this.stream.posts();
    const postIds = this.discussion.postIds();
    const postFadeIn = vnode => {
      $(vnode.dom).addClass('fadeIn');
      // 500 is the duration of the fadeIn CSS animation + 100ms,
      // so the animation has time to complete
      setTimeout(() => $(vnode.dom).removeClass('fadeIn'), 500);
    };
    const items = posts.map((post, i) => {
      let content;
      const attrs = {
        'data-index': this.stream.visibleStart + i
      };
      if (post) {
        const time = post.createdAt();
        const PostComponent = _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].postComponents[post.contentType()];
        content = !!PostComponent && m(PostComponent, {
          post: post
        });
        attrs.key = 'post' + post.id();
        attrs.oncreate = postFadeIn;
        attrs['data-time'] = time.toISOString();
        attrs['data-number'] = post.number();
        attrs['data-id'] = post.id();
        attrs['data-type'] = post.contentType();

        // If the post before this one was more than 4 days ago, we will
        // display a 'time gap' indicating how long it has been in between
        // the posts.
        const dt = time - lastTime;
        if (dt > 1000 * 60 * 60 * 24 * 4) {
          content = [m("div", {
            className: "PostStream-timeGap"
          }, m("span", null, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.post_stream.time_lapsed_text', {
            period: dayjs().add(dt, 'ms').fromNow(true)
          }))), content];
        }
        lastTime = time;
      } else {
        attrs.key = 'post' + postIds[this.stream.visibleStart + i];
        content = m(_LoadingPost__WEBPACK_IMPORTED_MODULE_3__["default"], null);
      }
      return m("div", Object.assign({
        className: "PostStream-item"
      }, attrs), content);
    });
    if (!viewingEnd && posts[this.stream.visibleEnd - this.stream.visibleStart - 1]) {
      items.push(m("div", {
        className: "PostStream-loadMore",
        key: "loadMore"
      }, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_5__["default"], {
        className: "Button",
        onclick: this.stream.loadNext.bind(this.stream)
      }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.post_stream.load_more_button'))));
    }

    // Allow extensions to add items to the end of the post stream.
    if (viewingEnd) {
      items.push(...this.endItems().toArray());
    }

    // If we're viewing the end of the discussion, the user can reply, and
    // is not already doing so, then show a 'write a reply' placeholder.
    if (viewingEnd && (!_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user || this.discussion.canReply())) {
      items.push(m("div", {
        className: "PostStream-item",
        key: "reply",
        "data-index": this.stream.count(),
        oncreate: postFadeIn
      }, m(_ReplyPlaceholder__WEBPACK_IMPORTED_MODULE_4__["default"], {
        discussion: this.discussion
      })));
    }
    return m("div", {
      className: "PostStream",
      role: "feed",
      "aria-live": "off",
      "aria-busy": this.stream.pagesLoading ? 'true' : 'false'
    }, items);
  }

  /**
   * @returns {ItemList<import('mithril').Children>}
   */
  endItems() {
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_6__["default"]();
    return items;
  }
  onupdate(vnode) {
    super.onupdate(vnode);
    this.triggerScroll();
  }
  oncreate(vnode) {
    super.oncreate(vnode);
    this.triggerScroll();

    // This is wrapped in setTimeout due to the following Mithril issue:
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());
  }
  onremove(vnode) {
    super.onremove(vnode);
    this.scrollListener.stop();
    clearTimeout(this.calculatePositionTimeout);
  }

  /**
   * Start scrolling, if appropriate, to a newly-targeted post.
   */
  triggerScroll() {
    if (!this.stream.needsScroll) return;
    const target = this.stream.targetPost;
    this.stream.needsScroll = false;
    if ('number' in target) {
      this.scrollToNumber(target.number, this.stream.animateScroll);
    } else if ('index' in target) {
      this.scrollToIndex(target.index, this.stream.animateScroll, target.reply);
    }
  }

  /**
   *
   * @param {number} top
   */
  onscroll(top) {
    if (top === void 0) {
      top = window.pageYOffset;
    }
    if (this.stream.paused || this.stream.pagesLoading) return;
    this.updateScrubber(top);
    this.loadPostsIfNeeded(top);

    // Throttle calculation of our position (start/end numbers of posts in the
    // viewport) to 100ms.
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this, top), 100);
  }

  /**
   * Check if either extreme of the post stream is in the viewport,
   * and if so, trigger loading the next/previous page.
   *
   * @param {number} top
   */
  loadPostsIfNeeded(top) {
    if (top === void 0) {
      top = window.pageYOffset;
    }
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;
    const loadAheadDistance = 300;
    if (this.stream.visibleStart > 0) {
      const $item = this.$('.PostStream-item[data-index=' + this.stream.visibleStart + ']');
      if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
        this.stream.loadPrevious();
      }
    }
    if (this.stream.visibleEnd < this.stream.count()) {
      const $item = this.$('.PostStream-item[data-index=' + (this.stream.visibleEnd - 1) + ']');
      if ($item.length && $item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.stream.loadNext();
      }
    }
  }
  updateScrubber(top) {
    if (top === void 0) {
      top = window.pageYOffset;
    }
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    const $items = this.$('.PostStream-item[data-index]');
    let visible = 0;
    let period = '';
    let indexFromViewPort = null;

    // Now loop through each of the items in the discussion. An 'item' is
    // either a single post or a 'gap' of one or more posts that haven't
    // been loaded yet.
    $items.each(function () {
      const $this = $(this);
      const top = $this.offset().top;
      const height = $this.outerHeight(true);

      // If this item is above the top of the viewport, skip to the next
      // one. If it's below the bottom of the viewport, break out of the
      // loop.
      if (top + height < viewportTop) {
        return true;
      }
      if (top > viewportTop + viewportHeight) {
        return false;
      }

      // Work out how many pixels of this item are visible inside the viewport.
      // Then add the proportion of this item's total height to the index.
      const visibleTop = Math.max(0, viewportTop - top);
      const visibleBottom = Math.min(height, viewportTop + viewportHeight - top);
      const visiblePost = visibleBottom - visibleTop;

      // We take the index of the first item that passed the previous checks.
      // It is the item that is first visible in the viewport.
      if (indexFromViewPort === null) {
        indexFromViewPort = parseFloat($this.data('index')) + visibleTop / height;
      }
      if (visiblePost > 0) {
        visible += visiblePost / height;
      }

      // If this item has a time associated with it, then set the
      // scrollbar's current period to a formatted version of this time.
      const time = $this.data('time');
      if (time) period = time;
    });

    // If indexFromViewPort is null, it means no posts are visible in the
    // viewport. This can happen, when drafting a long reply post. In that case
    // set the index to the last post.
    this.stream.index = indexFromViewPort !== null ? indexFromViewPort + 1 : this.stream.count();
    this.stream.visible = visible;
    if (period) this.stream.description = dayjs(period).format('MMMM YYYY');
  }

  /**
   * Work out which posts (by number) are currently visible in the viewport, and
   * fire an event with the information.
   */
  calculatePosition(top) {
    if (top === void 0) {
      top = window.pageYOffset;
    }
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    const viewportTop = top + marginTop;
    let startNumber;
    let endNumber;
    this.$('.PostStream-item').each(function () {
      const $item = $(this);
      const top = $item.offset().top;
      const height = $item.outerHeight(true);
      const visibleTop = Math.max(0, viewportTop - top);
      const threeQuartersVisible = visibleTop / height < 0.75;
      const coversQuarterOfViewport = (height - visibleTop) / viewportHeight > 0.25;
      if (startNumber === undefined && (threeQuartersVisible || coversQuarterOfViewport)) {
        startNumber = $item.data('number');
      }
      if (top + height > scrollTop) {
        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });
    if (startNumber) {
      this.attrs.onPositionChange(startNumber || 1, endNumber, startNumber);
    }
  }

  /**
   * Get the distance from the top of the viewport to the point at which we
   * would consider a post to be the first one visible.
   *
   * @return {number}
   */
  getMarginTop() {
    const headerId = _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].screen() === 'phone' ? '#app-navigation' : '#header';
    return this.$() && $(headerId).outerHeight() + parseInt(this.$().css('margin-top'), 10);
  }

  /**
   * Scroll down to a certain post by number and 'flash' it.
   *
   * @param {number} number
   * @param {boolean} animate
   * @return {JQueryDeferred}
   */
  scrollToNumber(number, animate) {
    const $item = this.$(".PostStream-item[data-number=".concat(number, "]"));
    return this.scrollToItem($item, animate).then(this.flashItem.bind(this, $item));
  }

  /**
   * Scroll down to a certain post by index.
   *
   * @param {number} index
   * @param {boolean} animate
   * @param {boolean} reply Whether or not to scroll to the reply placeholder.
   * @return {JQueryDeferred}
   */
  scrollToIndex(index, animate, reply) {
    const $item = reply ? $('.PostStream-item:last-child') : this.$(".PostStream-item[data-index=".concat(index, "]"));
    this.scrollToItem($item, animate, true, reply);
    if (reply) {
      this.flashItem($item);
    }
  }

  /**
   * Scroll down to the given post.
   *
   * @param {JQuery} $item
   * @param {boolean} animate
   * @param {boolean} force Whether or not to force scrolling to the item, even
   *     if it is already in the viewport.
   * @param {boolean} reply Whether or not to scroll to the reply placeholder.
   * @return {JQueryDeferred}
   */
  scrollToItem($item, animate, force, reply) {
    const $container = $('html, body').stop(true);
    const index = $item.data('index');
    if ($item.length) {
      const itemTop = $item.offset().top - this.getMarginTop();
      const itemBottom = $item.offset().top + $item.height();
      const scrollTop = $(document).scrollTop();
      const scrollBottom = scrollTop + $(window).height();

      // If the item is already in the viewport, we may not need to scroll.
      // If we're scrolling to the reply placeholder, we'll make sure its
      // bottom will line up with the top of the composer.
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        const top = reply ? itemBottom - $(window).height() + _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer.computedHeight() : $item.is(':first-child') ? 0 : itemTop;
        if (!animate) {
          $container.scrollTop(top);
        } else if (top !== scrollTop) {
          $container.animate({
            scrollTop: top
          }, 'fast');
        }
      }
    }
    const updateScrubberHeight = () => {
      // We manually set the index because we want to display the index of the
      // exact post we've scrolled to, not just that of the first post within viewport.
      this.updateScrubber();
      if (index !== undefined) this.stream.index = index + 1;
    };

    // If we don't update this before the scroll, the scrubber will start
    // at the top, and animate down, which can be confusing
    updateScrubberHeight();
    this.stream.forceUpdateScrubber = true;
    return Promise.all([$container.promise(), this.stream.loadPromise]).then(() => {
      m.redraw.sync();

      // Rendering post contents will probably throw off our position.
      // To counter this, we'll scroll either:
      //   - To the reply placeholder (aligned with composer top)
      //   - To the top of the page if we're on the first post
      //   - To the top of a post (if that post exists)
      // If the post does not currently exist, it's probably
      // outside of the range we loaded in, so we won't adjust anything,
      // as it will soon be rendered by the "load more" system.
      let itemOffset;
      if (reply) {
        const $placeholder = $('.PostStream-item:last-child');
        $(window).scrollTop($placeholder.offset().top + $placeholder.height() - $(window).height() + _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer.computedHeight());
      } else if (index === 0) {
        $(window).scrollTop(0);
      } else if (itemOffset = $(".PostStream-item[data-index=".concat(index, "]")).offset()) {
        $(window).scrollTop(itemOffset.top - this.getMarginTop());
      }

      // We want to adjust this again after posts have been loaded in
      // and position adjusted so that the scrubber's height is accurate.
      updateScrubberHeight();
      this.calculatePosition();
      this.stream.paused = false;
      // Check if we need to load more posts after scrolling.
      this.loadPostsIfNeeded();
    });
  }

  /**
   * 'Flash' the given post, drawing the user's attention to it.
   *
   * @param {JQuery} $item
   */
  flashItem($item) {
    // This might execute before the fadeIn class has been removed in PostStreamItem's
    // oncreate, so we remove it just to be safe and avoid a double animation.
    $item.removeClass('fadeIn');
    $item.addClass('flash').on('animationend webkitAnimationEnd', e => {
      $item.removeClass('flash');
    });
  }
}
flarum.reg.add('core', 'forum/components/PostStream', PostStream);

/***/ }),

/***/ "./src/forum/components/ReplyPlaceholder.js":
/*!**************************************************!*\
  !*** ./src/forum/components/ReplyPlaceholder.js ***!
  \**************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ReplyPlaceholder)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/Component */ "./src/common/Component.ts");
/* harmony import */ var _common_helpers_username__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/helpers/username */ "./src/common/helpers/username.tsx");
/* harmony import */ var _utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/DiscussionControls */ "./src/forum/utils/DiscussionControls.js");
/* harmony import */ var _ComposerPostPreview__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./ComposerPostPreview */ "./src/forum/components/ComposerPostPreview.js");
/* harmony import */ var _common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/helpers/listItems */ "./src/common/helpers/listItems.tsx");
/* harmony import */ var _common_components_Avatar__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Avatar */ "./src/common/components/Avatar.tsx");








/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 *
 * ### Attrs
 *
 * - `discussion`
 */
class ReplyPlaceholder extends _common_Component__WEBPACK_IMPORTED_MODULE_1__["default"] {
  view() {
    if (_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer.composingReplyTo(this.attrs.discussion)) {
      return m("article", {
        className: "Post CommentPost editing",
        "aria-busy": "true"
      }, m("div", {
        className: "Post-container"
      }, m("div", {
        className: "Post-side"
      }, m(_common_components_Avatar__WEBPACK_IMPORTED_MODULE_6__["default"], {
        user: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user,
        className: "Post-avatar"
      })), m("div", {
        className: "Post-main"
      }, m("header", {
        className: "Post-header"
      }, m("div", {
        className: "PostUser"
      }, m("h3", {
        className: "PostUser-name"
      }, (0,_common_helpers_username__WEBPACK_IMPORTED_MODULE_2__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user)), m("ul", {
        className: "PostUser-badges badges badges--packed"
      }, (0,_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user.badges().toArray())))), m("div", {
        className: "Post-body"
      }, m(_ComposerPostPreview__WEBPACK_IMPORTED_MODULE_4__["default"], {
        className: "Post-body",
        composer: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].composer,
        surround: this.anchorPreview.bind(this)
      })))));
    }
    const reply = () => {
      _utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_3__["default"].replyAction.call(this.attrs.discussion, true).catch(() => {});
    };
    return m("button", {
      className: "Post ReplyPlaceholder",
      onclick: reply
    }, m("div", {
      className: "Post-container"
    }, m("div", {
      className: "Post-side"
    }, m(_common_components_Avatar__WEBPACK_IMPORTED_MODULE_6__["default"], {
      user: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].session.user,
      className: "Post-avatar"
    })), m("div", {
      className: "Post-main"
    }, m("span", {
      className: "Post-header"
    }, _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.post_stream.reply_placeholder')))));
  }
  anchorPreview(preview) {
    const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();
    preview();
    if (anchorToBottom) {
      $(window).scrollTop($(document).height());
    }
  }
}
flarum.reg.add('core', 'forum/components/ReplyPlaceholder', ReplyPlaceholder);

/***/ })

}]);
//# sourceMappingURL=PostStream.js.map