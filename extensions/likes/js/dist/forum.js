/******/ (() => { // webpackBootstrap
/******/ 	// runtime can't be in strict mode because a global variable is assign and maybe created.
/******/ 	var __webpack_modules__ = ({

/***/ "./src/forum/addLikeAction.js":
/*!************************************!*\
  !*** ./src/forum/addLikeAction.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/components/CommentPost */ "flarum/forum/components/CommentPost");
/* harmony import */ var flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_3__);




/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'actionItems', function (items) {
    var post = this.attrs.post;
    if (post.isHidden() || !post.canLike()) return;
    var likes = post.likes();
    var isLiked = (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user) && likes && likes.some(function (user) {
      return user === (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user);
    });
    items.add('like', flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default().component({
      className: 'Button Button--link',
      onclick: function onclick() {
        isLiked = !isLiked;
        post.save({
          isLiked: isLiked
        }); // We've saved the fact that we do or don't like the post, but in order
        // to provide instantaneous feedback to the user, we'll need to add or
        // remove the like from the relationship data manually.

        var data = post.data.relationships.likes.data;
        data.some(function (like, i) {
          if (like.id === flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user.id()) {
            data.splice(i, 1);
            return true;
          }
        });

        if (isLiked) {
          data.unshift({
            type: 'users',
            id: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user.id()
          });
        }
      }
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans(isLiked ? 'flarum-likes.forum.post.unlike_link' : 'flarum-likes.forum.post.like_link')));
  });
}

/***/ }),

/***/ "./src/forum/addLikesList.js":
/*!***********************************!*\
  !*** ./src/forum/addLikesList.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/CommentPost */ "flarum/forum/components/CommentPost");
/* harmony import */ var flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_helpers_punctuateSeries__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/helpers/punctuateSeries */ "flarum/common/helpers/punctuateSeries");
/* harmony import */ var flarum_common_helpers_punctuateSeries__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_punctuateSeries__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/username */ "flarum/common/helpers/username");
/* harmony import */ var flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/helpers/icon */ "flarum/common/helpers/icon");
/* harmony import */ var flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _components_PostLikesModal__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/PostLikesModal */ "./src/forum/components/PostLikesModal.js");








/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_CommentPost__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'footerItems', function (items) {
    var post = this.attrs.post;
    var likes = post.recentLikes();
    var count = post.likesCount();

    if (likes && likes.length) {
      // the limit is dynamic through the backend, we only load those we need
      var limit = likes.length; // overLimit indicates there are more likes than the ones we render (and load)

      var overLimit = count > likes.length; // Construct a list of names of users who have liked this post. Make sure the
      // current user is first in the list, and cap a maximum of 4 items.

      var names = likes.filter(function (a) {
        return a !== (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user);
      }).slice(0, limit).map(function (user) {
        return m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
          href: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().route.user(user)
        }, user === (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user) ? flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.post.you_text') : flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5___default()(user));
      });

      if (post.likedByActor()) {
        names.unshift(m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
          href: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().route.user((flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user))
        }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.post.you_text')));
      } // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.


      if (overLimit) {
        names.push(m("a", {
          href: "#",
          onclick: function onclick(e) {
            e.preventDefault();
            flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().modal.show(_components_PostLikesModal__WEBPACK_IMPORTED_MODULE_7__["default"], {
              post: post
            });
          }
        }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.post.others_link', {
          count: count - likes.length
        })));
      }

      items.add('liked', m("div", {
        className: "Post-likedBy"
      }, flarum_common_helpers_icon__WEBPACK_IMPORTED_MODULE_6___default()('far fa-thumbs-up'), flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.post.liked_by' + (likes[0] === (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().session.user) ? '_self' : '') + '_text', {
        count: names.length,
        users: flarum_common_helpers_punctuateSeries__WEBPACK_IMPORTED_MODULE_4___default()(names)
      })));
    }
  });
}

/***/ }),

/***/ "./src/forum/components/PostLikedNotification.js":
/*!*******************************************************!*\
  !*** ./src/forum/components/PostLikedNotification.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ PostLikedNotification)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_Notification__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/Notification */ "flarum/forum/components/Notification");
/* harmony import */ var flarum_forum_components_Notification__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_Notification__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_string__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/string */ "flarum/common/utils/string");
/* harmony import */ var flarum_common_utils_string__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_string__WEBPACK_IMPORTED_MODULE_3__);





var PostLikedNotification = /*#__PURE__*/function (_Notification) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(PostLikedNotification, _Notification);

  function PostLikedNotification() {
    return _Notification.apply(this, arguments) || this;
  }

  var _proto = PostLikedNotification.prototype;

  _proto.icon = function icon() {
    return 'far fa-thumbs-up';
  };

  _proto.href = function href() {
    return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().route.post(this.attrs.notification.subject());
  };

  _proto.content = function content() {
    var notification = this.attrs.notification;
    var user = notification.fromUser();
    return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.notifications.post_liked_text', {
      user: user,
      count: 1
    });
  };

  _proto.excerpt = function excerpt() {
    return (0,flarum_common_utils_string__WEBPACK_IMPORTED_MODULE_3__.truncate)(this.attrs.notification.subject().contentPlain(), 200);
  };

  return PostLikedNotification;
}((flarum_forum_components_Notification__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/forum/components/PostLikesModal.js":
/*!************************************************!*\
  !*** ./src/forum/components/PostLikesModal.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ PostLikesModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_helpers_avatar__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/helpers/avatar */ "flarum/common/helpers/avatar");
/* harmony import */ var flarum_common_helpers_avatar__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_avatar__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/username */ "flarum/common/helpers/username");
/* harmony import */ var flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5__);







var PostLikesModal = /*#__PURE__*/function (_Modal) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(PostLikesModal, _Modal);

  function PostLikesModal() {
    return _Modal.apply(this, arguments) || this;
  }

  var _proto = PostLikesModal.prototype;

  _proto.className = function className() {
    return 'PostLikesModal Modal--small';
  };

  _proto.title = function title() {
    return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.post_likes.title');
  };

  _proto.content = function content() {
    return m("div", {
      className: "Modal-body"
    }, m("ul", {
      className: "PostLikesModal-list"
    }, this.attrs.post.likes().map(function (user) {
      return m("li", null, m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
        href: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().route.user(user)
      }, flarum_common_helpers_avatar__WEBPACK_IMPORTED_MODULE_4___default()(user), " ", flarum_common_helpers_username__WEBPACK_IMPORTED_MODULE_5___default()(user)));
    })));
  };

  return PostLikesModal;
}((flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/forum/index.js":
/*!****************************!*\
  !*** ./src/forum/index.js ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/models/Post */ "flarum/common/models/Post");
/* harmony import */ var flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_forum_components_NotificationGrid__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/forum/components/NotificationGrid */ "flarum/forum/components/NotificationGrid");
/* harmony import */ var flarum_forum_components_NotificationGrid__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_NotificationGrid__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _addLikeAction__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./addLikeAction */ "./src/forum/addLikeAction.js");
/* harmony import */ var _addLikesList__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./addLikesList */ "./src/forum/addLikesList.js");
/* harmony import */ var _components_PostLikedNotification__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/PostLikedNotification */ "./src/forum/components/PostLikedNotification.js");








flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().initializers.add('flarum-likes', function () {
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().notificationComponents.postLiked) = _components_PostLikedNotification__WEBPACK_IMPORTED_MODULE_7__["default"];
  (flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default().prototype.canLike) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default().attribute('canLike');
  (flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default().prototype.likes) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default().hasMany('likes');
  (flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default().prototype.likesCount) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default().attribute('likesCount');
  (flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default().prototype.recentLikes) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default().hasMany('recentLikes');
  (flarum_common_models_Post__WEBPACK_IMPORTED_MODULE_2___default().prototype.likedByActor) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_3___default().attribute('likedByActor');
  (0,_addLikeAction__WEBPACK_IMPORTED_MODULE_5__["default"])();
  (0,_addLikesList__WEBPACK_IMPORTED_MODULE_6__["default"])();
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_NotificationGrid__WEBPACK_IMPORTED_MODULE_4___default().prototype), 'notificationTypes', function (items) {
    items.add('postLiked', {
      name: 'postLiked',
      icon: 'far fa-thumbs-up',
      label: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-likes.forum.settings.notify_post_liked_label')
    });
  });
});

/***/ }),

/***/ "flarum/common/Model":
/*!*****************************************************!*\
  !*** external "flarum.core.compat['common/Model']" ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Model'];

/***/ }),

/***/ "flarum/common/components/Button":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Button']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Button'];

/***/ }),

/***/ "flarum/common/components/Link":
/*!***************************************************************!*\
  !*** external "flarum.core.compat['common/components/Link']" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Link'];

/***/ }),

/***/ "flarum/common/components/Modal":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Modal']" ***!
  \****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Modal'];

/***/ }),

/***/ "flarum/common/extend":
/*!******************************************************!*\
  !*** external "flarum.core.compat['common/extend']" ***!
  \******************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/extend'];

/***/ }),

/***/ "flarum/common/helpers/avatar":
/*!**************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/avatar']" ***!
  \**************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/avatar'];

/***/ }),

/***/ "flarum/common/helpers/icon":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/icon']" ***!
  \************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/icon'];

/***/ }),

/***/ "flarum/common/helpers/punctuateSeries":
/*!***********************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/punctuateSeries']" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/punctuateSeries'];

/***/ }),

/***/ "flarum/common/helpers/username":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/username']" ***!
  \****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/username'];

/***/ }),

/***/ "flarum/common/models/Post":
/*!***********************************************************!*\
  !*** external "flarum.core.compat['common/models/Post']" ***!
  \***********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/models/Post'];

/***/ }),

/***/ "flarum/common/utils/string":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/utils/string']" ***!
  \************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/string'];

/***/ }),

/***/ "flarum/forum/app":
/*!**************************************************!*\
  !*** external "flarum.core.compat['forum/app']" ***!
  \**************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/app'];

/***/ }),

/***/ "flarum/forum/components/CommentPost":
/*!*********************************************************************!*\
  !*** external "flarum.core.compat['forum/components/CommentPost']" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/CommentPost'];

/***/ }),

/***/ "flarum/forum/components/Notification":
/*!**********************************************************************!*\
  !*** external "flarum.core.compat['forum/components/Notification']" ***!
  \**********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/Notification'];

/***/ }),

/***/ "flarum/forum/components/NotificationGrid":
/*!**************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/NotificationGrid']" ***!
  \**************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/NotificationGrid'];

/***/ }),

/***/ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js":
/*!*************************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js ***!
  \*************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _inheritsLoose)
/* harmony export */ });
/* harmony import */ var _setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./setPrototypeOf.js */ "../../../node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js");

function _inheritsLoose(subClass, superClass) {
  subClass.prototype = Object.create(superClass.prototype);
  subClass.prototype.constructor = subClass;
  (0,_setPrototypeOf_js__WEBPACK_IMPORTED_MODULE_0__["default"])(subClass, superClass);
}

/***/ }),

/***/ "../../../node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js":
/*!**************************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/esm/setPrototypeOf.js ***!
  \**************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _setPrototypeOf)
/* harmony export */ });
function _setPrototypeOf(o, p) {
  _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
(() => {
"use strict";
/*!******************!*\
  !*** ./forum.js ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_forum__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/forum */ "./src/forum/index.js");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=forum.js.map