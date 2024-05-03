"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/DiscussionsUserPage"],{

/***/ "./src/forum/components/DiscussionsUserPage.tsx":
/*!******************************************************!*\
  !*** ./src/forum/components/DiscussionsUserPage.tsx ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DiscussionsUserPage)
/* harmony export */ });
/* harmony import */ var _UserPage__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserPage */ "./src/forum/components/UserPage.tsx");
/* harmony import */ var _DiscussionList__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./DiscussionList */ "./src/forum/components/DiscussionList.js");
/* harmony import */ var _states_DiscussionListState__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../states/DiscussionListState */ "./src/forum/states/DiscussionListState.ts");



/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
class DiscussionsUserPage extends _UserPage__WEBPACK_IMPORTED_MODULE_0__["default"] {
  oninit(vnode) {
    super.oninit(vnode);
    this.loadUser(m.route.param('username'));
  }
  show(user) {
    super.show(user);
    this.state = new _states_DiscussionListState__WEBPACK_IMPORTED_MODULE_2__["default"]({
      filter: {
        author: user.username()
      },
      sort: 'newest'
    });
    this.state.refresh();
  }
  content() {
    return m("div", {
      className: "DiscussionsUserPage"
    }, m(_DiscussionList__WEBPACK_IMPORTED_MODULE_1__["default"], {
      state: this.state
    }));
  }
}
flarum.reg.add('core', 'forum/components/DiscussionsUserPage', DiscussionsUserPage);

/***/ })

}]);
//# sourceMappingURL=DiscussionsUserPage.js.map