"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/NotificationsPage"],{

/***/ "./src/forum/components/NotificationsPage.tsx":
/*!****************************************************!*\
  !*** ./src/forum/components/NotificationsPage.tsx ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ NotificationsPage)
/* harmony export */ });
/* harmony import */ var _forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../forum/app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_Page__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../common/components/Page */ "./src/common/components/Page.tsx");
/* harmony import */ var _NotificationList__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NotificationList */ "./src/forum/components/NotificationList.js");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");





/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
class NotificationsPage extends _common_components_Page__WEBPACK_IMPORTED_MODULE_1__["default"] {
  oninit(vnode) {
    super.oninit(vnode);
    _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].history.push('notifications', (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_3__["default"])(_forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].translator.trans('core.forum.notifications.title')));
    _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].notifications.load();
    this.bodyClass = 'App--notifications';
  }
  view() {
    return m("div", {
      className: "NotificationsPage"
    }, m(_NotificationList__WEBPACK_IMPORTED_MODULE_2__["default"], {
      state: _forum_app__WEBPACK_IMPORTED_MODULE_0__["default"].notifications
    }));
  }
}
flarum.reg.add('core', 'forum/components/NotificationsPage', NotificationsPage);

/***/ })

}]);
//# sourceMappingURL=NotificationsPage.js.map