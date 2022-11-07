/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/common/compat.js":
/*!******************************!*\
  !*** ./src/common/compat.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _utils_sortTags__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/sortTags */ "./src/common/utils/sortTags.tsx");
/* harmony import */ var _models_Tag__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./models/Tag */ "./src/common/models/Tag.ts");
/* harmony import */ var _helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./helpers/tagsLabel */ "./src/common/helpers/tagsLabel.js");
/* harmony import */ var _helpers_tagIcon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./helpers/tagIcon */ "./src/common/helpers/tagIcon.js");
/* harmony import */ var _helpers_tagLabel__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./helpers/tagLabel */ "./src/common/helpers/tagLabel.js");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'tags/utils/sortTags': _utils_sortTags__WEBPACK_IMPORTED_MODULE_0__["default"],
  'tags/models/Tag': _models_Tag__WEBPACK_IMPORTED_MODULE_1__["default"],
  'tags/helpers/tagsLabel': _helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__["default"],
  'tags/helpers/tagIcon': _helpers_tagIcon__WEBPACK_IMPORTED_MODULE_3__["default"],
  'tags/helpers/tagLabel': _helpers_tagLabel__WEBPACK_IMPORTED_MODULE_4__["default"]
});

/***/ }),

/***/ "./src/common/helpers/tagIcon.js":
/*!***************************************!*\
  !*** ./src/common/helpers/tagIcon.js ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ tagIcon)
/* harmony export */ });
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_0__);

function tagIcon(tag, attrs, settings) {
  if (attrs === void 0) {
    attrs = {};
  }

  if (settings === void 0) {
    settings = {};
  }

  var hasIcon = tag && tag.icon();
  var _settings = settings,
      _settings$useColor = _settings.useColor,
      useColor = _settings$useColor === void 0 ? true : _settings$useColor;
  attrs.className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_0___default()([attrs.className, 'icon', hasIcon ? tag.icon() : 'TagIcon']);

  if (tag && useColor) {
    attrs.style = attrs.style || {};
    attrs.style['--color'] = tag.color();

    if (hasIcon) {
      attrs.style.color = tag.color();
    }
  } else if (!tag) {
    attrs.className += ' untagged';
  }

  return hasIcon ? m("i", attrs) : m("span", attrs);
}

/***/ }),

/***/ "./src/common/helpers/tagLabel.js":
/*!****************************************!*\
  !*** ./src/common/helpers/tagLabel.js ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ tagLabel)
/* harmony export */ });
/* harmony import */ var flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/utils/extract */ "flarum/common/utils/extract");
/* harmony import */ var flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _tagIcon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./tagIcon */ "./src/common/helpers/tagIcon.js");




function tagLabel(tag, attrs) {
  if (attrs === void 0) {
    attrs = {};
  }

  attrs.style = attrs.style || {};
  attrs.className = 'TagLabel ' + (attrs.className || '');
  var link = flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0___default()(attrs, 'link');
  var tagText = tag ? tag.name() : app.translator.trans('flarum-tags.lib.deleted_tag_text');

  if (tag) {
    var color = tag.color();

    if (color) {
      attrs.style['--tag-bg'] = color;
      attrs.className += ' colored';

      if (flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_1___default()(color)) {
        attrs.className += ' tag-dark';
      } else {
        attrs.className += ' tag-light';
      }
    }

    if (link) {
      attrs.title = tag.description() || '';
      attrs.href = app.route('tag', {
        tags: tag.slug()
      });
    }

    if (tag.isChild()) {
      attrs.className += ' TagLabel--child';
    }
  } else {
    attrs.className += ' untagged';
  }

  return m(link ? (flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_2___default()) : 'span', attrs, m("span", {
    className: "TagLabel-text"
  }, tag && tag.icon() && (0,_tagIcon__WEBPACK_IMPORTED_MODULE_3__["default"])(tag, {}, {
    useColor: false
  }), " ", tagText));
}

/***/ }),

/***/ "./src/common/helpers/tagsLabel.js":
/*!*****************************************!*\
  !*** ./src/common/helpers/tagsLabel.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ tagsLabel)
/* harmony export */ });
/* harmony import */ var flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/utils/extract */ "flarum/common/utils/extract");
/* harmony import */ var flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _tagLabel__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./tagLabel */ "./src/common/helpers/tagLabel.js");
/* harmony import */ var _utils_sortTags__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/sortTags */ "./src/common/utils/sortTags.tsx");



function tagsLabel(tags, attrs) {
  if (attrs === void 0) {
    attrs = {};
  }

  var children = [];
  var link = flarum_common_utils_extract__WEBPACK_IMPORTED_MODULE_0___default()(attrs, 'link');
  attrs.className = 'TagsLabel ' + (attrs.className || '');

  if (tags) {
    (0,_utils_sortTags__WEBPACK_IMPORTED_MODULE_2__["default"])(tags).forEach(function (tag) {
      if (tag || tags.length === 1) {
        children.push((0,_tagLabel__WEBPACK_IMPORTED_MODULE_1__["default"])(tag, {
          link: link
        }));
      }
    });
  } else {
    children.push((0,_tagLabel__WEBPACK_IMPORTED_MODULE_1__["default"])());
  }

  return m("span", attrs, children);
}

/***/ }),

/***/ "./src/common/index.ts":
/*!*****************************!*\
  !*** ./src/common/index.ts ***!
  \*****************************/
/***/ (() => {



/***/ }),

/***/ "./src/common/models/Tag.ts":
/*!**********************************!*\
  !*** ./src/common/models/Tag.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Tag)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_utils_computed__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/utils/computed */ "flarum/common/utils/computed");
/* harmony import */ var flarum_common_utils_computed__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_computed__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_2__);




var Tag = /*#__PURE__*/function (_Model) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(Tag, _Model);

  function Tag() {
    return _Model.apply(this, arguments) || this;
  }

  var _proto = Tag.prototype;

  _proto.name = function name() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('name').call(this);
  };

  _proto.slug = function slug() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('slug').call(this);
  };

  _proto.description = function description() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('description').call(this);
  };

  _proto.color = function color() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('color').call(this);
  };

  _proto.backgroundUrl = function backgroundUrl() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('backgroundUrl').call(this);
  };

  _proto.backgroundMode = function backgroundMode() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('backgroundMode').call(this);
  };

  _proto.icon = function icon() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('icon').call(this);
  };

  _proto.position = function position() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('position').call(this);
  };

  _proto.parent = function parent() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().hasOne('parent').call(this);
  };

  _proto.children = function children() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().hasMany('children').call(this);
  };

  _proto.defaultSort = function defaultSort() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('defaultSort').call(this);
  };

  _proto.isChild = function isChild() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('isChild').call(this);
  };

  _proto.isHidden = function isHidden() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('isHidden').call(this);
  };

  _proto.discussionCount = function discussionCount() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('discussionCount').call(this);
  };

  _proto.lastPostedAt = function lastPostedAt() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('lastPostedAt', (flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().transformDate)).call(this);
  };

  _proto.lastPostedDiscussion = function lastPostedDiscussion() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().hasOne('lastPostedDiscussion').call(this);
  };

  _proto.isRestricted = function isRestricted() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('isRestricted').call(this);
  };

  _proto.canStartDiscussion = function canStartDiscussion() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('canStartDiscussion').call(this);
  };

  _proto.canAddToDiscussion = function canAddToDiscussion() {
    return flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default().attribute('canAddToDiscussion').call(this);
  };

  _proto.isPrimary = function isPrimary() {
    return flarum_common_utils_computed__WEBPACK_IMPORTED_MODULE_1___default()('position', 'parent', function (position, parent) {
      return position !== null && parent === false;
    }).call(this);
  };

  return Tag;
}((flarum_common_Model__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/common/utils/sortTags.tsx":
/*!***************************************!*\
  !*** ./src/common/utils/sortTags.tsx ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ sortTags)
/* harmony export */ });
function sortTags(tags) {
  return tags.slice(0).sort(function (a, b) {
    var aPos = a.position();
    var bPos = b.position(); // If they're both secondary tags, sort them by their discussions count,
    // descending.

    if (aPos === null && bPos === null) return b.discussionCount() - a.discussionCount(); // If just one is a secondary tag, then the primary tag should
    // come first.

    if (bPos === null) return -1;
    if (aPos === null) return 1; // If we've made it this far, we know they're both primary tags. So we'll
    // need to see if they have parents.

    var aParent = a.parent();
    var bParent = b.parent(); // If they both have the same parent, then their positions are local,
    // so we can compare them directly.

    if (aParent === bParent) return aPos - bPos; // If they are both child tags, then we will compare the positions of their
    // parents.
    else if (aParent && bParent) return aParent.position() - bParent.position(); // If we are comparing a child tag with its parent, then we let the parent
    // come first. If we are comparing an unrelated parent/child, then we
    // compare both of the parents.
    else if (aParent) return aParent === b ? 1 : aParent.position() - bPos;else if (bParent) return bParent === a ? -1 : aPos - bParent.position();
    return 0;
  });
}

/***/ }),

/***/ "./src/forum/addTagComposer.js":
/*!*************************************!*\
  !*** ./src/forum/addTagComposer.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/DiscussionComposer */ "flarum/forum/components/DiscussionComposer");
/* harmony import */ var flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/TagDiscussionModal */ "./src/forum/components/TagDiscussionModal.tsx");
/* harmony import */ var _common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../common/helpers/tagsLabel */ "./src/common/helpers/tagsLabel.js");
/* harmony import */ var _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./utils/getSelectableTags */ "./src/forum/utils/getSelectableTags.js");







/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'newDiscussionAction', function (promise) {
    // From `addTagFilter
    var tag = this.currentTag();

    if (tag) {
      var parent = tag.parent();
      var tags = parent ? [parent, tag] : [tag];
      promise.then(function (composer) {
        return composer.fields.tags = tags;
      });
    } else {
      app.composer.fields.tags = [];
    }
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'oninit', function () {
    app.tagList.load(['parent']).then(function () {
      return m.redraw();
    });
  }); // Add tag-selection abilities to the discussion composer.

  (flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default().prototype.chooseTags) = function () {
    var _this = this;

    var selectableTags = (0,_utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_6__["default"])();
    if (!selectableTags.length) return;
    app.modal.show(_components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_4__["default"], {
      selectedTags: (this.composer.fields.tags || []).slice(0),
      onsubmit: function onsubmit(tags) {
        _this.composer.fields.tags = tags;

        _this.$('textarea').focus();
      }
    });
  }; // Add a tag-selection menu to the discussion composer's header, after the
  // title.


  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'headerItems', function (items) {
    var tags = this.composer.fields.tags || [];
    var selectableTags = (0,_utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_6__["default"])();
    items.add('tags', m("a", {
      className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()(['DiscussionComposer-changeTags', !selectableTags.length && 'disabled']),
      onclick: this.chooseTags.bind(this)
    }, tags.length ? (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_5__["default"])(tags) : m("span", {
      className: "TagLabel untagged"
    }, app.translator.trans('flarum-tags.forum.composer_discussion.choose_tags_link'))), 10);
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.override)((flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'onsubmit', function (original) {
    var _this2 = this;

    var chosenTags = this.composer.fields.tags || [];
    var chosenPrimaryTags = chosenTags.filter(function (tag) {
      return tag.position() !== null && !tag.isChild();
    });
    var chosenSecondaryTags = chosenTags.filter(function (tag) {
      return tag.position() === null;
    });
    var selectableTags = (0,_utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_6__["default"])();
    var minPrimaryTags = parseInt(app.forum.attribute('minPrimaryTags'));
    var minSecondaryTags = parseInt(app.forum.attribute('minSecondaryTags'));
    var maxPrimaryTags = parseInt(app.forum.attribute('maxPrimaryTags'));
    var maxSecondaryTags = parseInt(app.forum.attribute('maxSecondaryTags'));

    if ((!chosenTags.length && maxPrimaryTags !== 0 && maxSecondaryTags !== 0 || chosenPrimaryTags.length < minPrimaryTags || chosenSecondaryTags.length < minSecondaryTags) && selectableTags.length) {
      app.modal.show(_components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_4__["default"], {
        selectedTags: chosenTags,
        onsubmit: function onsubmit(tags) {
          _this2.composer.fields.tags = tags;
          original();
        }
      });
    } else {
      original();
    }
  }); // Add the selected tags as data to submit to the server.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionComposer__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'data', function (data) {
    data.relationships = data.relationships || {};
    data.relationships.tags = this.composer.fields.tags;
  });
}

/***/ }),

/***/ "./src/forum/addTagControl.js":
/*!************************************!*\
  !*** ./src/forum/addTagControl.js ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/utils/DiscussionControls */ "flarum/forum/utils/DiscussionControls");
/* harmony import */ var flarum_forum_utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/TagDiscussionModal */ "./src/forum/components/TagDiscussionModal.tsx");




/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  // Add a control allowing the discussion to be moved to another category.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_utils_DiscussionControls__WEBPACK_IMPORTED_MODULE_1___default()), 'moderationControls', function (items, discussion) {
    if (discussion.canTag()) {
      items.add('tags', m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default()), {
        icon: "fas fa-tag",
        onclick: function onclick() {
          return app.modal.show(_components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_3__["default"], {
            discussion: discussion
          });
        }
      }, app.translator.trans('flarum-tags.forum.discussion_controls.edit_tags_button')));
    }
  });
}

/***/ }),

/***/ "./src/forum/addTagFilter.tsx":
/*!************************************!*\
  !*** ./src/forum/addTagFilter.tsx ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_states_DiscussionListState__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/states/DiscussionListState */ "flarum/forum/states/DiscussionListState");
/* harmony import */ var flarum_forum_states_DiscussionListState__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_states_DiscussionListState__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_forum_states_GlobalSearchState__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/forum/states/GlobalSearchState */ "flarum/forum/states/GlobalSearchState");
/* harmony import */ var flarum_forum_states_GlobalSearchState__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_states_GlobalSearchState__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _components_TagHero__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/TagHero */ "./src/forum/components/TagHero.js");









var findTag = function findTag(slug) {
  return flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.all('tags').find(function (tag) {
    return tag.slug().localeCompare(slug, undefined, {
      sensitivity: 'base'
    }) === 0;
  });
};

/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  (flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype.currentTag) = function () {
    var _this = this;

    if (this.currentActiveTag) {
      return this.currentActiveTag;
    }

    var slug = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().search.params().tags;
    var tag = null;

    if (slug) {
      tag = findTag(slug);
    }

    if (slug && !tag || tag && !tag.isChild() && !tag.children()) {
      if (this.currentTagLoading) {
        return;
      }

      this.currentTagLoading = true; // Unlike the backend, no need to fetch parent.children because if we're on
      // a child tag page, then either:
      //    - We loaded in that child tag (and its siblings) in the API document
      //    - We first navigated to the current tag's parent, which would have loaded in the current tag's siblings.

      flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.find('tags', slug, {
        include: 'children,children.parent,parent,state'
      }).then(function () {
        _this.currentActiveTag = findTag(slug);
        m.redraw();
      })["finally"](function () {
        _this.currentTagLoading = false;
      });
    }

    if (tag) {
      this.currentActiveTag = tag;
      return this.currentActiveTag;
    }

    return;
  }; // If currently viewing a tag, insert a tag hero at the top of the view.


  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.override)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'hero', function (original) {
    var tag = this.currentTag();
    if (tag) return m(_components_TagHero__WEBPACK_IMPORTED_MODULE_7__["default"], {
      model: tag
    });
    return original();
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'view', function (vdom) {
    var tag = this.currentTag();
    if (tag) vdom.attrs.className += ' IndexPage--tag' + tag.id();
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'setTitle', function () {
    var tag = this.currentTag();

    if (tag) {
      flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().setTitle(tag.name());
    }
  }); // If currently viewing a tag, restyle the 'new discussion' button to use
  // the tag's color, and disable if the user isn't allowed to edit.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'sidebarItems', function (items) {
    var tag = this.currentTag();

    if (tag) {
      var color = tag.color();
      var canStartDiscussion = tag.canStartDiscussion() || !(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().session.user);
      var newDiscussion = items.get('newDiscussion');

      if (color) {
        newDiscussion.attrs.className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5___default()([newDiscussion.attrs.className, 'Button--tagColored']);
        newDiscussion.attrs.style = {
          '--color': color
        };

        if (flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_6___default()(color)) {
          newDiscussion.attrs.className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5___default()([newDiscussion.attrs.className, 'Button--tagDark']);
        } else {
          newDiscussion.attrs.className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_5___default()([newDiscussion.attrs.className, 'Button--tagLight']);
        }
      }

      newDiscussion.attrs.disabled = !canStartDiscussion;
      newDiscussion.children = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button');
    }
  }); // Add a parameter for the global search state to pass on to the
  // DiscussionListState that will let us filter discussions by tag.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_states_GlobalSearchState__WEBPACK_IMPORTED_MODULE_4___default().prototype), 'params', function (params) {
    params.tags = m.route.param('tags');
  }); // Translate that parameter into a gambit appended to the search query.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_states_DiscussionListState__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'requestParams', function (params) {
    if (typeof params.include === 'string') {
      params.include = [params.include];
    } else {
      var _params$include;

      (_params$include = params.include) == null ? void 0 : _params$include.push('tags', 'tags.parent');
    }

    if (this.params.tags) {
      var _params$filter;

      var filter = (_params$filter = params.filter) != null ? _params$filter : {};
      filter.tag = this.params.tags; // TODO: replace this with a more robust system.

      var q = filter.q;

      if (q) {
        filter.q = q + " tag:" + this.params.tags;
      }

      params.filter = filter;
    }
  });
}

/***/ }),

/***/ "./src/forum/addTagLabels.js":
/*!***********************************!*\
  !*** ./src/forum/addTagLabels.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_components_DiscussionListItem__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/DiscussionListItem */ "flarum/forum/components/DiscussionListItem");
/* harmony import */ var flarum_forum_components_DiscussionListItem__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionListItem__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/DiscussionHero */ "flarum/forum/components/DiscussionHero");
/* harmony import */ var flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../common/helpers/tagsLabel */ "./src/common/helpers/tagsLabel.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");






/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  // Add tag labels to each discussion in the discussion list.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionListItem__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'infoItems', function (items) {
    var tags = this.attrs.discussion.tags();

    if (tags && tags.length) {
      items.add('tags', (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_4__["default"])(tags), 10);
    }
  }); // Restyle a discussion's hero to use its first tag's color.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'view', function (view) {
    var tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_5__["default"])(this.attrs.discussion.tags());

    if (tags && tags.length) {
      var color = tags[0].color();

      if (color) {
        view.attrs.style = {
          '--hero-bg': color
        };

        if (flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_3___default()(color)) {
          view.attrs.className += ' DiscussionHero--dark';
        } else {
          view.attrs.className += ' DiscussionHero--light';
        }
      }
    }
  }); // Add a list of a discussion's tags to the discussion hero, displayed
  // before the title. Put the title on its own line.

  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'items', function (items) {
    var tags = this.attrs.discussion.tags();

    if (tags && tags.length) {
      items.add('tags', (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_4__["default"])(tags, {
        link: true
      }), 5);
    }
  });
}

/***/ }),

/***/ "./src/forum/addTagList.js":
/*!*********************************!*\
  !*** ./src/forum/addTagList.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Separator__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Separator */ "flarum/common/components/Separator");
/* harmony import */ var flarum_common_components_Separator__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Separator__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/LinkButton */ "flarum/common/components/LinkButton");
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _components_TagLinkButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/TagLinkButton */ "./src/forum/components/TagLinkButton.js");
/* harmony import */ var _components_TagsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/TagsPage */ "./src/forum/components/TagsPage.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");







/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  // Add a link to the tags page, as well as a list of all the tags,
  // to the index page's sidebar.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'navItems', function (items) {
    items.add('tags', m((flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3___default()), {
      icon: "fas fa-th-large",
      href: app.route('tags')
    }, app.translator.trans('flarum-tags.forum.index.tags_link')), -10);
    if (app.current.matches(_components_TagsPage__WEBPACK_IMPORTED_MODULE_5__["default"])) return;
    items.add('separator', flarum_common_components_Separator__WEBPACK_IMPORTED_MODULE_2___default().component(), -12);
    var params = app.search.stickyParams();
    var tags = app.store.all('tags');
    var currentTag = this.currentTag();

    var addTag = function addTag(tag) {
      var active = currentTag === tag;

      if (!active && currentTag) {
        active = currentTag.parent() === tag;
      } // tag.name() is passed here as children even though it isn't used directly
      // because when we need to get the active child in SelectDropdown, we need to
      // use its children to populate the dropdown. The problem here is that `view`
      // on TagLinkButton is only called AFTER SelectDropdown, so no children are available
      // for SelectDropdown to use at the time.


      items.add('tag' + tag.id(), _components_TagLinkButton__WEBPACK_IMPORTED_MODULE_4__["default"].component({
        model: tag,
        params: params,
        active: active
      }, tag == null ? void 0 : tag.name()), -14);
    };

    (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_6__["default"])(tags).filter(function (tag) {
      return tag.position() !== null && (!tag.isChild() || currentTag && (tag.parent() === currentTag || tag.parent() === currentTag.parent()));
    }).forEach(addTag);
    var more = tags.filter(function (tag) {
      return tag.position() === null;
    }).sort(function (a, b) {
      return b.discussionCount() - a.discussionCount();
    });
    more.splice(0, 3).forEach(addTag);

    if (more.length) {
      items.add('moreTags', m((flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_3___default()), {
        href: app.route('tags')
      }, app.translator.trans('flarum-tags.forum.index.more_link')), -16);
    }
  });
}

/***/ }),

/***/ "./src/forum/compat.js":
/*!*****************************!*\
  !*** ./src/forum/compat.js ***!
  \*****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _common_compat__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../common/compat */ "./src/common/compat.js");
/* harmony import */ var _addTagFilter__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./addTagFilter */ "./src/forum/addTagFilter.tsx");
/* harmony import */ var _addTagControl__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./addTagControl */ "./src/forum/addTagControl.js");
/* harmony import */ var _components_TagHero__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/TagHero */ "./src/forum/components/TagHero.js");
/* harmony import */ var _components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/TagDiscussionModal */ "./src/forum/components/TagDiscussionModal.tsx");
/* harmony import */ var _components_TagsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/TagsPage */ "./src/forum/components/TagsPage.js");
/* harmony import */ var _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/DiscussionTaggedPost */ "./src/forum/components/DiscussionTaggedPost.js");
/* harmony import */ var _components_TagLinkButton__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/TagLinkButton */ "./src/forum/components/TagLinkButton.js");
/* harmony import */ var _addTagList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./addTagList */ "./src/forum/addTagList.js");
/* harmony import */ var _addTagLabels__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./addTagLabels */ "./src/forum/addTagLabels.js");
/* harmony import */ var _addTagComposer__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./addTagComposer */ "./src/forum/addTagComposer.js");
/* harmony import */ var _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./utils/getSelectableTags */ "./src/forum/utils/getSelectableTags.js");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Object.assign(_common_compat__WEBPACK_IMPORTED_MODULE_0__["default"], {
  'tags/addTagFilter': _addTagFilter__WEBPACK_IMPORTED_MODULE_1__["default"],
  'tags/addTagControl': _addTagControl__WEBPACK_IMPORTED_MODULE_2__["default"],
  'tags/components/TagHero': _components_TagHero__WEBPACK_IMPORTED_MODULE_3__["default"],
  'tags/components/TagDiscussionModal': _components_TagDiscussionModal__WEBPACK_IMPORTED_MODULE_4__["default"],
  'tags/components/TagsPage': _components_TagsPage__WEBPACK_IMPORTED_MODULE_5__["default"],
  'tags/components/DiscussionTaggedPost': _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_6__["default"],
  'tags/components/TagLinkButton': _components_TagLinkButton__WEBPACK_IMPORTED_MODULE_7__["default"],
  'tags/addTagList': _addTagList__WEBPACK_IMPORTED_MODULE_8__["default"],
  'tags/addTagLabels': _addTagLabels__WEBPACK_IMPORTED_MODULE_9__["default"],
  'tags/addTagComposer': _addTagComposer__WEBPACK_IMPORTED_MODULE_10__["default"],
  'tags/utils/getSelectableTags': _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_11__["default"]
}));

/***/ }),

/***/ "./src/forum/components/DiscussionTaggedPost.js":
/*!******************************************************!*\
  !*** ./src/forum/components/DiscussionTaggedPost.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ DiscussionTaggedPost)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_forum_components_EventPost__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/EventPost */ "flarum/forum/components/EventPost");
/* harmony import */ var flarum_forum_components_EventPost__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_EventPost__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/helpers/tagsLabel */ "./src/common/helpers/tagsLabel.js");




var DiscussionTaggedPost = /*#__PURE__*/function (_EventPost) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(DiscussionTaggedPost, _EventPost);

  function DiscussionTaggedPost() {
    return _EventPost.apply(this, arguments) || this;
  }

  DiscussionTaggedPost.initAttrs = function initAttrs(attrs) {
    _EventPost.initAttrs.call(this, attrs);

    var oldTags = attrs.post.content()[0];
    var newTags = attrs.post.content()[1];

    function diffTags(tags1, tags2) {
      return tags1.filter(function (tag) {
        return tags2.indexOf(tag) === -1;
      }).map(function (id) {
        return app.store.getById('tags', id);
      });
    }

    attrs.tagsAdded = diffTags(newTags, oldTags);
    attrs.tagsRemoved = diffTags(oldTags, newTags);
  };

  var _proto = DiscussionTaggedPost.prototype;

  _proto.icon = function icon() {
    return 'fas fa-tag';
  };

  _proto.descriptionKey = function descriptionKey() {
    if (this.attrs.tagsAdded.length) {
      if (this.attrs.tagsRemoved.length) {
        return 'flarum-tags.forum.post_stream.added_and_removed_tags_text';
      }

      return 'flarum-tags.forum.post_stream.added_tags_text';
    }

    return 'flarum-tags.forum.post_stream.removed_tags_text';
  };

  _proto.descriptionData = function descriptionData() {
    var data = {};

    if (this.attrs.tagsAdded.length) {
      data.tagsAdded = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__["default"])(this.attrs.tagsAdded, {
          link: true
        }),
        count: this.attrs.tagsAdded.length
      });
    }

    if (this.attrs.tagsRemoved.length) {
      data.tagsRemoved = app.translator.trans('flarum-tags.forum.post_stream.tags_text', {
        tags: (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__["default"])(this.attrs.tagsRemoved, {
          link: true
        }),
        count: this.attrs.tagsRemoved.length
      });
    }

    return data;
  };

  return DiscussionTaggedPost;
}((flarum_forum_components_EventPost__WEBPACK_IMPORTED_MODULE_1___default()));



/***/ }),

/***/ "./src/forum/components/TagDiscussionModal.tsx":
/*!*****************************************************!*\
  !*** ./src/forum/components/TagDiscussionModal.tsx ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagDiscussionModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/components/DiscussionPage */ "flarum/forum/components/DiscussionPage");
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/helpers/highlight */ "flarum/common/helpers/highlight");
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var flarum_forum_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! flarum/forum/utils/KeyboardNavigatable */ "flarum/forum/utils/KeyboardNavigatable");
/* harmony import */ var flarum_forum_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! flarum/common/utils/Stream */ "flarum/common/utils/Stream");
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_10___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_10__);
/* harmony import */ var _common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../common/helpers/tagLabel */ "./src/common/helpers/tagLabel.js");
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");
/* harmony import */ var _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../utils/getSelectableTags */ "./src/forum/utils/getSelectableTags.js");
/* harmony import */ var _ToggleButton__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./ToggleButton */ "./src/forum/components/ToggleButton.js");

















var TagDiscussionModal = /*#__PURE__*/function (_Modal) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagDiscussionModal, _Modal);

  function TagDiscussionModal() {
    var _this;

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.tagsLoading = true;
    _this.selected = [];
    _this.filter = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_10___default()('');
    _this.focused = false;
    _this.minPrimary = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('minPrimaryTags');
    _this.maxPrimary = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('maxPrimaryTags');
    _this.minSecondary = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('minSecondaryTags');
    _this.maxSecondary = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('maxSecondaryTags');
    _this.bypassReqs = false;
    _this.navigator = new (flarum_forum_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_9___default())();
    _this.tags = void 0;
    _this.selectedTag = void 0;
    return _this;
  }

  var _proto = TagDiscussionModal.prototype;

  _proto.oninit = function oninit(vnode) {
    var _this2 = this;

    _Modal.prototype.oninit.call(this, vnode);

    this.navigator.onUp(function () {
      return _this2.setIndex(_this2.getCurrentNumericIndex() - 1, true);
    }).onDown(function () {
      return _this2.setIndex(_this2.getCurrentNumericIndex() + 1, true);
    }).onSelect(this.select.bind(this)).onRemove(function () {
      return _this2.selected.splice(_this2.selected.length - 1, 1);
    });
    flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().tagList.load(['parent']).then(function () {
      var _this2$attrs$discussi;

      _this2.tagsLoading = false;
      var tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_13__["default"])((0,_utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_14__["default"])(_this2.attrs.discussion));
      _this2.tags = tags;
      var discussionTags = (_this2$attrs$discussi = _this2.attrs.discussion) == null ? void 0 : _this2$attrs$discussi.tags();

      if (_this2.attrs.selectedTags) {
        _this2.attrs.selectedTags.map(_this2.addTag.bind(_this2));
      } else if (discussionTags) {
        discussionTags.forEach(function (tag) {
          return tag && _this2.addTag(tag);
        });
      }

      _this2.selectedTag = tags[0];
      m.redraw();
    });
  };

  _proto.primaryCount = function primaryCount() {
    return this.selected.filter(function (tag) {
      return tag.isPrimary();
    }).length;
  };

  _proto.secondaryCount = function secondaryCount() {
    return this.selected.filter(function (tag) {
      return !tag.isPrimary();
    }).length;
  }
  /**
   * Add the given tag to the list of selected tags.
   */
  ;

  _proto.addTag = function addTag(tag) {
    if (!tag.canStartDiscussion()) return; // If this tag has a parent, we'll also need to add the parent tag to the
    // selected list if it's not already in there.

    var parent = tag.parent();

    if (parent && !this.selected.includes(parent)) {
      this.selected.push(parent);
    }

    if (!this.selected.includes(tag)) {
      this.selected.push(tag);
    }
  }
  /**
   * Remove the given tag from the list of selected tags.
   */
  ;

  _proto.removeTag = function removeTag(tag) {
    var index = this.selected.indexOf(tag);

    if (index !== -1) {
      this.selected.splice(index, 1); // Look through the list of selected tags for any tags which have the tag
      // we just removed as their parent. We'll need to remove them too.

      this.selected.filter(function (selected) {
        return selected.parent() === tag;
      }).forEach(this.removeTag.bind(this));
    }
  };

  _proto.className = function className() {
    return 'TagDiscussionModal';
  };

  _proto.title = function title() {
    return this.attrs.discussion ? flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.edit_title', {
      title: m("em", null, this.attrs.discussion.title())
    }) : flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.title');
  };

  _proto.getInstruction = function getInstruction(primaryCount, secondaryCount) {
    if (this.bypassReqs) {
      return '';
    }

    if (primaryCount < this.minPrimary) {
      var remaining = this.minPrimary - primaryCount;
      return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.choose_primary_placeholder', {
        count: remaining
      });
    } else if (secondaryCount < this.minSecondary) {
      var _remaining = this.minSecondary - secondaryCount;

      return flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.choose_secondary_placeholder', {
        count: _remaining
      });
    }

    return '';
  };

  _proto.content = function content() {
    var _this3 = this;

    if (this.tagsLoading || !this.tags) {
      return m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_5___default()), null);
    }

    var tags = this.tags;
    var filter = this.filter().toLowerCase();
    var primaryCount = this.primaryCount();
    var secondaryCount = this.secondaryCount(); // Filter out all child tags whose parents have not been selected. This
    // makes it impossible to select a child if its parent hasn't been selected.

    tags = tags.filter(function (tag) {
      var parent = tag.parent();
      return parent !== null && (parent === false || _this3.selected.includes(parent));
    }); // If the number of selected primary/secondary tags is at the maximum, then
    // we'll filter out all other tags of that type.

    if (primaryCount >= this.maxPrimary && !this.bypassReqs) {
      tags = tags.filter(function (tag) {
        return !tag.isPrimary() || _this3.selected.includes(tag);
      });
    }

    if (secondaryCount >= this.maxSecondary && !this.bypassReqs) {
      tags = tags.filter(function (tag) {
        return tag.isPrimary() || _this3.selected.includes(tag);
      });
    } // If the user has entered text in the filter input, then filter by tags
    // whose name matches what they've entered.


    if (filter) {
      tags = tags.filter(function (tag) {
        return tag.name().substr(0, filter.length).toLowerCase() === filter;
      });
    }

    if (!this.selectedTag || !tags.includes(this.selectedTag)) this.selectedTag = tags[0];
    var inputWidth = Math.max(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_8___default()(this.getInstruction(primaryCount, secondaryCount)).length, this.filter().length);
    return [m("div", {
      className: "Modal-body"
    }, m("div", {
      className: "TagDiscussionModal-form"
    }, m("div", {
      className: "TagDiscussionModal-form-input"
    }, m("div", {
      className: 'TagsInput FormControl ' + (this.focused ? 'focus' : ''),
      onclick: function onclick() {
        return _this3.$('.TagsInput input').focus();
      }
    }, m("span", {
      className: "TagsInput-selected"
    }, this.selected.map(function (tag) {
      return m("span", {
        className: "TagsInput-tag",
        onclick: function onclick() {
          _this3.removeTag(tag);

          _this3.onready();
        }
      }, (0,_common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_11__["default"])(tag));
    })), m("input", {
      className: "FormControl",
      placeholder: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_8___default()(this.getInstruction(primaryCount, secondaryCount)),
      bidi: this.filter,
      style: {
        width: inputWidth + 'ch'
      },
      onkeydown: this.navigator.navigate.bind(this.navigator),
      onfocus: function onfocus() {
        return _this3.focused = true;
      },
      onblur: function onblur() {
        return _this3.focused = false;
      }
    }))), m("div", {
      className: "TagDiscussionModal-form-submit App-primaryControl"
    }, m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_4___default()), {
      type: "submit",
      className: "Button Button--primary",
      disabled: !this.meetsRequirements(primaryCount, secondaryCount),
      icon: "fas fa-check"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.submit_button'))))), m("div", {
      className: "Modal-footer"
    }, m("ul", {
      className: "TagDiscussionModal-list SelectTagList"
    }, tags.filter(function (tag) {
      return filter || !tag.parent() || _this3.selected.includes(tag.parent());
    }).map(function (tag) {
      return m("li", {
        "data-index": tag.id(),
        className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_7___default()({
          pinned: tag.position() !== null,
          child: !!tag.parent(),
          colored: !!tag.color(),
          selected: _this3.selected.includes(tag),
          active: _this3.selectedTag === tag
        }),
        style: {
          color: tag.color()
        },
        onmouseover: function onmouseover() {
          return _this3.selectedTag = tag;
        },
        onclick: _this3.toggleTag.bind(_this3, tag)
      }, (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_12__["default"])(tag), m("span", {
        className: "SelectTagListItem-name"
      }, flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_6___default()(tag.name(), filter)), tag.description() ? m("span", {
        className: "SelectTagListItem-description"
      }, tag.description()) : '');
    })), !!flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('canBypassTagCounts') && m("div", {
      className: "TagDiscussionModal-controls"
    }, m(_ToggleButton__WEBPACK_IMPORTED_MODULE_15__["default"], {
      className: "Button",
      onclick: function onclick() {
        return _this3.bypassReqs = !_this3.bypassReqs;
      },
      isToggled: this.bypassReqs
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.bypass_requirements'))))];
  };

  _proto.meetsRequirements = function meetsRequirements(primaryCount, secondaryCount) {
    if (this.bypassReqs) {
      return true;
    }

    return primaryCount >= this.minPrimary && secondaryCount >= this.minSecondary;
  };

  _proto.toggleTag = function toggleTag(tag) {
    // Won't happen, needed for type safety.
    if (!this.tags) return;

    if (this.selected.includes(tag)) {
      this.removeTag(tag);
    } else {
      this.addTag(tag);
    }

    if (this.filter()) {
      this.filter('');
      this.selectedTag = this.tags[0];
    }

    this.onready();
  };

  _proto.select = function select(e) {
    // Ctrl + Enter submits the selection, just Enter completes the current entry
    if (e.metaKey || e.ctrlKey || this.selectedTag && this.selected.includes(this.selectedTag)) {
      if (this.selected.length) {
        // The DOM submit method doesn't emit a `submit event, so we
        // simulate a manual submission so our `onsubmit` logic is run.
        this.$('button[type="submit"]').click();
      }
    } else if (this.selectedTag) {
      this.getItem(this.selectedTag)[0].dispatchEvent(new Event('click'));
    }
  };

  _proto.selectableItems = function selectableItems() {
    return this.$('.TagDiscussionModal-list > li');
  };

  _proto.getCurrentNumericIndex = function getCurrentNumericIndex() {
    if (!this.selectedTag) return -1;
    return this.selectableItems().index(this.getItem(this.selectedTag));
  };

  _proto.getItem = function getItem(selectedTag) {
    return this.selectableItems().filter("[data-index=\"" + selectedTag.id() + "\"]");
  };

  _proto.setIndex = function setIndex(index, scrollToItem) {
    var $items = this.selectableItems();
    var $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    var $item = $items.eq(index);
    this.selectedTag = flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().store.getById('tags', $item.attr('data-index'));
    m.redraw();

    if (scrollToItem && this.selectedTag) {
      var dropdownScroll = $dropdown.scrollTop();
      var dropdownTop = $dropdown.offset().top;
      var dropdownBottom = dropdownTop + $dropdown.outerHeight();
      var itemTop = $item.offset().top;
      var itemBottom = itemTop + $item.outerHeight();
      var scrollTop;

      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({
          scrollTop: scrollTop
        }, 100);
      }
    }
  };

  _proto.onsubmit = function onsubmit(e) {
    e.preventDefault();
    var discussion = this.attrs.discussion;
    var tags = this.selected;

    if (discussion) {
      discussion.save({
        relationships: {
          tags: tags
        }
      }).then(function () {
        if (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().current.matches((flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_3___default()))) {
          flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().current.get('stream').update();
        }

        m.redraw();
      });
    }

    if (this.attrs.onsubmit) this.attrs.onsubmit(tags);
    this.hide();
  };

  return TagDiscussionModal;
}((flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/forum/components/TagHero.js":
/*!*****************************************!*\
  !*** ./src/forum/components/TagHero.js ***!
  \*****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagHero)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");





var TagHero = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagHero, _Component);

  function TagHero() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = TagHero.prototype;

  _proto.view = function view() {
    var tag = this.attrs.model;
    var color = tag.color();
    return m("header", {
      className: 'Hero TagHero' + (color ? ' TagHero--colored' : '') + (flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2___default()(color) ? ' TagHero--dark' : ' TagHero--light'),
      style: color ? {
        '--hero-bg': color
      } : ''
    }, m("div", {
      className: "container"
    }, m("div", {
      className: "containerNarrow"
    }, m("h2", {
      className: "Hero-title"
    }, tag.icon() && (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_3__["default"])(tag, {}, {
      useColor: false
    }), " ", tag.name()), m("div", {
      className: "Hero-subtitle"
    }, tag.description()))));
  };

  return TagHero;
}((flarum_common_Component__WEBPACK_IMPORTED_MODULE_1___default()));



/***/ }),

/***/ "./src/forum/components/TagLinkButton.js":
/*!***********************************************!*\
  !*** ./src/forum/components/TagLinkButton.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagLinkButton)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/LinkButton */ "flarum/common/components/LinkButton");
/* harmony import */ var flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");






var TagLinkButton = /*#__PURE__*/function (_LinkButton) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagLinkButton, _LinkButton);

  function TagLinkButton() {
    return _LinkButton.apply(this, arguments) || this;
  }

  var _proto = TagLinkButton.prototype;

  _proto.view = function view(vnode) {
    var tag = this.attrs.model;
    var active = this.constructor.isActive(this.attrs);
    var description = tag && tag.description();
    var className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()(['TagLinkButton', 'hasIcon', this.attrs.className, tag.isChild() && 'child']);
    return m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1___default()), {
      className: className,
      href: this.attrs.route,
      style: tag ? {
        '--color': tag.color()
      } : '',
      title: description || ''
    }, (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_4__["default"])(tag, {
      className: 'Button-icon'
    }), m("span", {
      className: "Button-label"
    }, tag ? tag.name() : app.translator.trans('flarum-tags.forum.index.untagged_link')));
  };

  TagLinkButton.initAttrs = function initAttrs(attrs) {
    _LinkButton.initAttrs.call(this, attrs);

    var tag = attrs.model;
    attrs.params.tags = tag ? tag.slug() : 'untagged';
    attrs.route = app.route('tag', attrs.params);
  };

  return TagLinkButton;
}((flarum_common_components_LinkButton__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/forum/components/TagsPage.js":
/*!******************************************!*\
  !*** ./src/forum/components/TagsPage.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagsPage)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_components_Page__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/Page */ "flarum/common/components/Page");
/* harmony import */ var flarum_common_components_Page__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Page__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/listItems */ "flarum/common/helpers/listItems");
/* harmony import */ var flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/helpers/humanTime */ "flarum/common/helpers/humanTime");
/* harmony import */ var flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");
/* harmony import */ var _common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/helpers/tagLabel */ "./src/common/helpers/tagLabel.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");












var TagsPage = /*#__PURE__*/function (_Page) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagsPage, _Page);

  function TagsPage() {
    return _Page.apply(this, arguments) || this;
  }

  var _proto = TagsPage.prototype;

  _proto.oninit = function oninit(vnode) {
    var _this = this;

    _Page.prototype.oninit.call(this, vnode);

    app.history.push('tags', app.translator.trans('flarum-tags.forum.header.back_to_tags_tooltip'));
    this.tags = [];
    var preloaded = app.preloadedApiDocument();

    if (preloaded) {
      this.tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_10__["default"])(preloaded.filter(function (tag) {
        return !tag.isChild();
      }));
      return;
    }

    this.loading = true;
    app.tagList.load(['children', 'lastPostedDiscussion', 'parent']).then(function () {
      _this.tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_10__["default"])(app.store.all('tags').filter(function (tag) {
        return !tag.isChild();
      }));
      _this.loading = false;
      m.redraw();
    });
  };

  _proto.view = function view() {
    if (this.loading) {
      return m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4___default()), null);
    }

    var pinned = this.tags.filter(function (tag) {
      return tag.position() !== null;
    });
    var cloud = this.tags.filter(function (tag) {
      return tag.position() === null;
    });
    return m("div", {
      className: "TagsPage"
    }, flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype.hero(), m("div", {
      className: "container"
    }, m("nav", {
      className: "TagsPage-nav IndexPage-nav sideNav"
    }, m("ul", null, flarum_common_helpers_listItems__WEBPACK_IMPORTED_MODULE_5___default()(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype.sidebarItems().toArray()))), m("div", {
      className: "TagsPage-content sideNavOffset"
    }, m("ul", {
      className: "TagTiles"
    }, pinned.map(function (tag) {
      var lastPostedDiscussion = tag.lastPostedDiscussion();
      var children = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_10__["default"])(tag.children() || []);
      return m("li", {
        className: 'TagTile ' + (tag.color() ? 'colored ' : '') + (flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_7___default()(tag.color()) ? 'tag-dark' : 'tag-light'),
        style: {
          '--tag-bg': tag.color()
        }
      }, m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
        className: "TagTile-info",
        href: app.route.tag(tag)
      }, tag.icon() && (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_8__["default"])(tag, {}, {
        useColor: false
      }), m("h3", {
        className: "TagTile-name"
      }, tag.name()), m("p", {
        className: "TagTile-description"
      }, tag.description()), children ? m("div", {
        className: "TagTile-children"
      }, children.map(function (child) {
        return [m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
          href: app.route.tag(child)
        }, child.name()), ' '];
      })) : ''), lastPostedDiscussion ? m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
        className: "TagTile-lastPostedDiscussion",
        href: app.route.discussion(lastPostedDiscussion, lastPostedDiscussion.lastPostNumber())
      }, m("span", {
        className: "TagTile-lastPostedDiscussion-title"
      }, lastPostedDiscussion.title()), flarum_common_helpers_humanTime__WEBPACK_IMPORTED_MODULE_6___default()(lastPostedDiscussion.lastPostedAt())) : m("span", {
        className: "TagTile-lastPostedDiscussion"
      }));
    })), cloud.length ? m("div", {
      className: "TagCloud"
    }, cloud.map(function (tag) {
      return [(0,_common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_9__["default"])(tag, {
        link: true
      }), ' '];
    })) : '')));
  };

  _proto.oncreate = function oncreate(vnode) {
    _Page.prototype.oncreate.call(this, vnode);

    app.setTitle(app.translator.trans('flarum-tags.forum.all_tags.meta_title_text'));
    app.setTitleCount(0);
  };

  return TagsPage;
}((flarum_common_components_Page__WEBPACK_IMPORTED_MODULE_1___default()));



/***/ }),

/***/ "./src/forum/components/ToggleButton.js":
/*!**********************************************!*\
  !*** ./src/forum/components/ToggleButton.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ToggleButton)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/objectWithoutPropertiesLoose */ "../../../node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js");
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4__);


var _excluded = ["className", "isToggled"];



/**
 * @TODO move to core
 */

var ToggleButton = /*#__PURE__*/function (_Component) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_1__["default"])(ToggleButton, _Component);

  function ToggleButton() {
    return _Component.apply(this, arguments) || this;
  }

  var _proto = ToggleButton.prototype;

  _proto.view = function view(vnode) {
    var _this$attrs = this.attrs,
        className = _this$attrs.className,
        isToggled = _this$attrs.isToggled,
        attrs = (0,_babel_runtime_helpers_esm_objectWithoutPropertiesLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(_this$attrs, _excluded);

    var icon = isToggled ? 'far fa-check-circle' : 'far fa-circle';
    return m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_3___default()), Object.assign({}, attrs, {
      icon: icon,
      className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4___default()([className, isToggled && 'Button--toggled'])
    }), vnode.children);
  };

  return ToggleButton;
}((flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default()));



/***/ }),

/***/ "./src/forum/index.ts":
/*!****************************!*\
  !*** ./src/forum/index.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/models/Discussion */ "flarum/common/models/Discussion");
/* harmony import */ var flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _common_models_Tag__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../common/models/Tag */ "./src/common/models/Tag.ts");
/* harmony import */ var _components_TagsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/TagsPage */ "./src/forum/components/TagsPage.js");
/* harmony import */ var _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/DiscussionTaggedPost */ "./src/forum/components/DiscussionTaggedPost.js");
/* harmony import */ var _states_TagListState__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./states/TagListState */ "./src/forum/states/TagListState.ts");
/* harmony import */ var _addTagList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./addTagList */ "./src/forum/addTagList.js");
/* harmony import */ var _addTagFilter__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./addTagFilter */ "./src/forum/addTagFilter.tsx");
/* harmony import */ var _addTagLabels__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./addTagLabels */ "./src/forum/addTagLabels.js");
/* harmony import */ var _addTagControl__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./addTagControl */ "./src/forum/addTagControl.js");
/* harmony import */ var _addTagComposer__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./addTagComposer */ "./src/forum/addTagComposer.js");
/* harmony import */ var _compat__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./compat */ "./src/forum/compat.js");
/* harmony import */ var _flarum_core_forum__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @flarum/core/forum */ "@flarum/core/admin");
/* harmony import */ var _flarum_core_forum__WEBPACK_IMPORTED_MODULE_14___default = /*#__PURE__*/__webpack_require__.n(_flarum_core_forum__WEBPACK_IMPORTED_MODULE_14__);













flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('flarum-tags', function () {
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().routes.tags) = {
    path: '/tags',
    component: _components_TagsPage__WEBPACK_IMPORTED_MODULE_5__["default"]
  };
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().routes.tag) = {
    path: '/t/:tags',
    component: (flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3___default())
  };

  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().route.tag) = function (tag) {
    return flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().route('tag', {
      tags: tag.slug()
    });
  };

  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().postComponents.discussionTagged) = _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_6__["default"];
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.models.tags) = _common_models_Tag__WEBPACK_IMPORTED_MODULE_4__["default"];
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().tagList) = new _states_TagListState__WEBPACK_IMPORTED_MODULE_7__["default"]();
  (flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default().prototype.tags) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().hasMany('tags');
  (flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default().prototype.canTag) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('canTag');
  (0,_addTagList__WEBPACK_IMPORTED_MODULE_8__["default"])();
  (0,_addTagFilter__WEBPACK_IMPORTED_MODULE_9__["default"])();
  (0,_addTagLabels__WEBPACK_IMPORTED_MODULE_10__["default"])();
  (0,_addTagControl__WEBPACK_IMPORTED_MODULE_11__["default"])();
  (0,_addTagComposer__WEBPACK_IMPORTED_MODULE_12__["default"])();
}); // Expose compat API



Object.assign(_flarum_core_forum__WEBPACK_IMPORTED_MODULE_14__.compat, _compat__WEBPACK_IMPORTED_MODULE_13__["default"]);

/***/ }),

/***/ "./src/forum/states/TagListState.ts":
/*!******************************************!*\
  !*** ./src/forum/states/TagListState.ts ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagListState)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/asyncToGenerator */ "../../../node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "../../../node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_2__);




var TagListState = /*#__PURE__*/function () {
  function TagListState() {
    this.loadedIncludes = new Set();
  }

  var _proto = TagListState.prototype;

  _proto.load = /*#__PURE__*/function () {
    var _load = (0,_babel_runtime_helpers_esm_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__["default"])( /*#__PURE__*/_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default().mark(function _callee(includes) {
      var _this = this;

      var unloadedIncludes;
      return _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default().wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              if (includes === void 0) {
                includes = [];
              }

              unloadedIncludes = includes.filter(function (include) {
                return !_this.loadedIncludes.has(include);
              });

              if (!(unloadedIncludes.length === 0)) {
                _context.next = 4;
                break;
              }

              return _context.abrupt("return", Promise.resolve(flarum_forum_app__WEBPACK_IMPORTED_MODULE_2___default().store.all('tags')));

            case 4:
              return _context.abrupt("return", flarum_forum_app__WEBPACK_IMPORTED_MODULE_2___default().store.find('tags', {
                include: unloadedIncludes.join(',')
              }).then(function (val) {
                unloadedIncludes.forEach(function (include) {
                  return _this.loadedIncludes.add(include);
                });
                return val;
              }));

            case 5:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    function load(_x) {
      return _load.apply(this, arguments);
    }

    return load;
  }();

  return TagListState;
}();



/***/ }),

/***/ "./src/forum/utils/getSelectableTags.js":
/*!**********************************************!*\
  !*** ./src/forum/utils/getSelectableTags.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ getSelectableTags)
/* harmony export */ });
function getSelectableTags(discussion) {
  var tags = app.store.all('tags');

  if (discussion) {
    tags = tags.filter(function (tag) {
      return tag.canAddToDiscussion() || discussion.tags().indexOf(tag) !== -1;
    });
  } else {
    tags = tags.filter(function (tag) {
      return tag.canStartDiscussion();
    });
  }

  return tags;
}

/***/ }),

/***/ "../../../node_modules/@babel/runtime/regenerator/index.js":
/*!*****************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/regenerator/index.js ***!
  \*****************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

module.exports = __webpack_require__(/*! regenerator-runtime */ "../../../node_modules/regenerator-runtime/runtime.js");

/***/ }),

/***/ "../../../node_modules/regenerator-runtime/runtime.js":
/*!************************************************************!*\
  !*** ../../../node_modules/regenerator-runtime/runtime.js ***!
  \************************************************************/
/***/ ((module) => {

/**
 * Copyright (c) 2014-present, Facebook, Inc.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var runtime = function (exports) {
  "use strict";

  var Op = Object.prototype;
  var hasOwn = Op.hasOwnProperty;
  var undefined; // More compressible than void 0.

  var $Symbol = typeof Symbol === "function" ? Symbol : {};
  var iteratorSymbol = $Symbol.iterator || "@@iterator";
  var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
  var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

  function define(obj, key, value) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
    return obj[key];
  }

  try {
    // IE 8 has a broken Object.defineProperty that only works on DOM objects.
    define({}, "");
  } catch (err) {
    define = function define(obj, key, value) {
      return obj[key] = value;
    };
  }

  function wrap(innerFn, outerFn, self, tryLocsList) {
    // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
    var generator = Object.create(protoGenerator.prototype);
    var context = new Context(tryLocsList || []); // The ._invoke method unifies the implementations of the .next,
    // .throw, and .return methods.

    generator._invoke = makeInvokeMethod(innerFn, self, context);
    return generator;
  }

  exports.wrap = wrap; // Try/catch helper to minimize deoptimizations. Returns a completion
  // record like context.tryEntries[i].completion. This interface could
  // have been (and was previously) designed to take a closure to be
  // invoked without arguments, but in all the cases we care about we
  // already have an existing method we want to call, so there's no need
  // to create a new function object. We can even get away with assuming
  // the method takes exactly one argument, since that happens to be true
  // in every case, so we don't have to touch the arguments object. The
  // only additional allocation required is the completion record, which
  // has a stable shape and so hopefully should be cheap to allocate.

  function tryCatch(fn, obj, arg) {
    try {
      return {
        type: "normal",
        arg: fn.call(obj, arg)
      };
    } catch (err) {
      return {
        type: "throw",
        arg: err
      };
    }
  }

  var GenStateSuspendedStart = "suspendedStart";
  var GenStateSuspendedYield = "suspendedYield";
  var GenStateExecuting = "executing";
  var GenStateCompleted = "completed"; // Returning this object from the innerFn has the same effect as
  // breaking out of the dispatch switch statement.

  var ContinueSentinel = {}; // Dummy constructor functions that we use as the .constructor and
  // .constructor.prototype properties for functions that return Generator
  // objects. For full spec compliance, you may wish to configure your
  // minifier not to mangle the names of these two functions.

  function Generator() {}

  function GeneratorFunction() {}

  function GeneratorFunctionPrototype() {} // This is a polyfill for %IteratorPrototype% for environments that
  // don't natively support it.


  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });
  var getProto = Object.getPrototypeOf;
  var NativeIteratorPrototype = getProto && getProto(getProto(values([])));

  if (NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
    // This environment has a native %IteratorPrototype%; use it instead
    // of the polyfill.
    IteratorPrototype = NativeIteratorPrototype;
  }

  var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);
  GeneratorFunction.prototype = GeneratorFunctionPrototype;
  define(Gp, "constructor", GeneratorFunctionPrototype);
  define(GeneratorFunctionPrototype, "constructor", GeneratorFunction);
  GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"); // Helper for defining the .next, .throw, and .return methods of the
  // Iterator interface in terms of a single ._invoke method.

  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function (method) {
      define(prototype, method, function (arg) {
        return this._invoke(method, arg);
      });
    });
  }

  exports.isGeneratorFunction = function (genFun) {
    var ctor = typeof genFun === "function" && genFun.constructor;
    return ctor ? ctor === GeneratorFunction || // For the native GeneratorFunction constructor, the best we can
    // do is to check its .name property.
    (ctor.displayName || ctor.name) === "GeneratorFunction" : false;
  };

  exports.mark = function (genFun) {
    if (Object.setPrototypeOf) {
      Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
    } else {
      genFun.__proto__ = GeneratorFunctionPrototype;
      define(genFun, toStringTagSymbol, "GeneratorFunction");
    }

    genFun.prototype = Object.create(Gp);
    return genFun;
  }; // Within the body of any async function, `await x` is transformed to
  // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
  // `hasOwn.call(value, "__await")` to determine if the yielded value is
  // meant to be awaited.


  exports.awrap = function (arg) {
    return {
      __await: arg
    };
  };

  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);

      if (record.type === "throw") {
        reject(record.arg);
      } else {
        var result = record.arg;
        var value = result.value;

        if (value && typeof value === "object" && hasOwn.call(value, "__await")) {
          return PromiseImpl.resolve(value.__await).then(function (value) {
            invoke("next", value, resolve, reject);
          }, function (err) {
            invoke("throw", err, resolve, reject);
          });
        }

        return PromiseImpl.resolve(value).then(function (unwrapped) {
          // When a yielded Promise is resolved, its final value becomes
          // the .value of the Promise<{value,done}> result for the
          // current iteration.
          result.value = unwrapped;
          resolve(result);
        }, function (error) {
          // If a rejected Promise was yielded, throw the rejection back
          // into the async generator function so it can be handled there.
          return invoke("throw", error, resolve, reject);
        });
      }
    }

    var previousPromise;

    function enqueue(method, arg) {
      function callInvokeWithMethodAndArg() {
        return new PromiseImpl(function (resolve, reject) {
          invoke(method, arg, resolve, reject);
        });
      }

      return previousPromise = // If enqueue has been called before, then we want to wait until
      // all previous Promises have been resolved before calling invoke,
      // so that results are always delivered in the correct order. If
      // enqueue has not been called before, then it is important to
      // call invoke immediately, without waiting on a callback to fire,
      // so that the async generator function has the opportunity to do
      // any necessary setup in a predictable way. This predictability
      // is why the Promise constructor synchronously invokes its
      // executor callback, and why async functions synchronously
      // execute code before the first await. Since we implement simple
      // async functions in terms of async generators, it is especially
      // important to get this right, even though it requires care.
      previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, // Avoid propagating failures to Promises returned by later
      // invocations of the iterator.
      callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
    } // Define the unified helper method that is used to implement .next,
    // .throw, and .return (see defineIteratorMethods).


    this._invoke = enqueue;
  }

  defineIteratorMethods(AsyncIterator.prototype);
  define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  });
  exports.AsyncIterator = AsyncIterator; // Note that simple async functions are implemented on top of
  // AsyncIterator objects; they just return a Promise for the value of
  // the final result produced by the iterator.

  exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    if (PromiseImpl === void 0) PromiseImpl = Promise;
    var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);
    return exports.isGeneratorFunction(outerFn) ? iter // If outerFn is a generator, return the full iterator.
    : iter.next().then(function (result) {
      return result.done ? result.value : iter.next();
    });
  };

  function makeInvokeMethod(innerFn, self, context) {
    var state = GenStateSuspendedStart;
    return function invoke(method, arg) {
      if (state === GenStateExecuting) {
        throw new Error("Generator is already running");
      }

      if (state === GenStateCompleted) {
        if (method === "throw") {
          throw arg;
        } // Be forgiving, per 25.3.3.3.3 of the spec:
        // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume


        return doneResult();
      }

      context.method = method;
      context.arg = arg;

      while (true) {
        var delegate = context.delegate;

        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);

          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }

        if (context.method === "next") {
          // Setting context._sent for legacy support of Babel's
          // function.sent implementation.
          context.sent = context._sent = context.arg;
        } else if (context.method === "throw") {
          if (state === GenStateSuspendedStart) {
            state = GenStateCompleted;
            throw context.arg;
          }

          context.dispatchException(context.arg);
        } else if (context.method === "return") {
          context.abrupt("return", context.arg);
        }

        state = GenStateExecuting;
        var record = tryCatch(innerFn, self, context);

        if (record.type === "normal") {
          // If an exception is thrown from innerFn, we leave state ===
          // GenStateExecuting and loop back for another invocation.
          state = context.done ? GenStateCompleted : GenStateSuspendedYield;

          if (record.arg === ContinueSentinel) {
            continue;
          }

          return {
            value: record.arg,
            done: context.done
          };
        } else if (record.type === "throw") {
          state = GenStateCompleted; // Dispatch the exception by looping back around to the
          // context.dispatchException(context.arg) call above.

          context.method = "throw";
          context.arg = record.arg;
        }
      }
    };
  } // Call delegate.iterator[context.method](context.arg) and handle the
  // result, either by returning a { value, done } result from the
  // delegate iterator, or by modifying context.method and context.arg,
  // setting context.delegate to null, and returning the ContinueSentinel.


  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];

    if (method === undefined) {
      // A .throw or .return when the delegate iterator has no .throw
      // method always terminates the yield* loop.
      context.delegate = null;

      if (context.method === "throw") {
        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined;
          maybeInvokeDelegate(delegate, context);

          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }

        context.method = "throw";
        context.arg = new TypeError("The iterator does not provide a 'throw' method");
      }

      return ContinueSentinel;
    }

    var record = tryCatch(method, delegate.iterator, context.arg);

    if (record.type === "throw") {
      context.method = "throw";
      context.arg = record.arg;
      context.delegate = null;
      return ContinueSentinel;
    }

    var info = record.arg;

    if (!info) {
      context.method = "throw";
      context.arg = new TypeError("iterator result is not an object");
      context.delegate = null;
      return ContinueSentinel;
    }

    if (info.done) {
      // Assign the result of the finished delegate to the temporary
      // variable specified by delegate.resultName (see delegateYield).
      context[delegate.resultName] = info.value; // Resume execution at the desired location (see delegateYield).

      context.next = delegate.nextLoc; // If context.method was "throw" but the delegate handled the
      // exception, let the outer generator proceed normally. If
      // context.method was "next", forget context.arg since it has been
      // "consumed" by the delegate iterator. If context.method was
      // "return", allow the original .return call to continue in the
      // outer generator.

      if (context.method !== "return") {
        context.method = "next";
        context.arg = undefined;
      }
    } else {
      // Re-yield the result returned by the delegate method.
      return info;
    } // The delegate iterator is finished, so forget it and continue with
    // the outer generator.


    context.delegate = null;
    return ContinueSentinel;
  } // Define Generator.prototype.{next,throw,return} in terms of the
  // unified ._invoke helper method.


  defineIteratorMethods(Gp);
  define(Gp, toStringTagSymbol, "Generator"); // A Generator should always return itself as the iterator object when the
  // @@iterator function is called on it. Some browsers' implementations of the
  // iterator prototype chain incorrectly implement this, causing the Generator
  // object to not be returned from this call. This ensures that doesn't happen.
  // See https://github.com/facebook/regenerator/issues/274 for more details.

  define(Gp, iteratorSymbol, function () {
    return this;
  });
  define(Gp, "toString", function () {
    return "[object Generator]";
  });

  function pushTryEntry(locs) {
    var entry = {
      tryLoc: locs[0]
    };

    if (1 in locs) {
      entry.catchLoc = locs[1];
    }

    if (2 in locs) {
      entry.finallyLoc = locs[2];
      entry.afterLoc = locs[3];
    }

    this.tryEntries.push(entry);
  }

  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal";
    delete record.arg;
    entry.completion = record;
  }

  function Context(tryLocsList) {
    // The root entry object (effectively a try statement without a catch
    // or a finally block) gives us a place to store values thrown from
    // locations where there is no enclosing try statement.
    this.tryEntries = [{
      tryLoc: "root"
    }];
    tryLocsList.forEach(pushTryEntry, this);
    this.reset(true);
  }

  exports.keys = function (object) {
    var keys = [];

    for (var key in object) {
      keys.push(key);
    }

    keys.reverse(); // Rather than returning an object with a next method, we keep
    // things simple and return the next function itself.

    return function next() {
      while (keys.length) {
        var key = keys.pop();

        if (key in object) {
          next.value = key;
          next.done = false;
          return next;
        }
      } // To avoid creating an additional object, we just hang the .value
      // and .done properties off the next function object itself. This
      // also ensures that the minifier will not anonymize the function.


      next.done = true;
      return next;
    };
  };

  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];

      if (iteratorMethod) {
        return iteratorMethod.call(iterable);
      }

      if (typeof iterable.next === "function") {
        return iterable;
      }

      if (!isNaN(iterable.length)) {
        var i = -1,
            next = function next() {
          while (++i < iterable.length) {
            if (hasOwn.call(iterable, i)) {
              next.value = iterable[i];
              next.done = false;
              return next;
            }
          }

          next.value = undefined;
          next.done = true;
          return next;
        };

        return next.next = next;
      }
    } // Return an iterator with no values.


    return {
      next: doneResult
    };
  }

  exports.values = values;

  function doneResult() {
    return {
      value: undefined,
      done: true
    };
  }

  Context.prototype = {
    constructor: Context,
    reset: function reset(skipTempReset) {
      this.prev = 0;
      this.next = 0; // Resetting context._sent for legacy support of Babel's
      // function.sent implementation.

      this.sent = this._sent = undefined;
      this.done = false;
      this.delegate = null;
      this.method = "next";
      this.arg = undefined;
      this.tryEntries.forEach(resetTryEntry);

      if (!skipTempReset) {
        for (var name in this) {
          // Not sure about the optimal order of these conditions:
          if (name.charAt(0) === "t" && hasOwn.call(this, name) && !isNaN(+name.slice(1))) {
            this[name] = undefined;
          }
        }
      }
    },
    stop: function stop() {
      this.done = true;
      var rootEntry = this.tryEntries[0];
      var rootRecord = rootEntry.completion;

      if (rootRecord.type === "throw") {
        throw rootRecord.arg;
      }

      return this.rval;
    },
    dispatchException: function dispatchException(exception) {
      if (this.done) {
        throw exception;
      }

      var context = this;

      function handle(loc, caught) {
        record.type = "throw";
        record.arg = exception;
        context.next = loc;

        if (caught) {
          // If the dispatched exception was caught by a catch block,
          // then let that catch block handle the exception normally.
          context.method = "next";
          context.arg = undefined;
        }

        return !!caught;
      }

      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        var record = entry.completion;

        if (entry.tryLoc === "root") {
          // Exception thrown outside of any try block that could handle
          // it, so set the completion value of the entire function to
          // throw the exception.
          return handle("end");
        }

        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc");
          var hasFinally = hasOwn.call(entry, "finallyLoc");

          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            } else if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }
          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) {
              return handle(entry.catchLoc, true);
            }
          } else if (hasFinally) {
            if (this.prev < entry.finallyLoc) {
              return handle(entry.finallyLoc);
            }
          } else {
            throw new Error("try statement without catch or finally");
          }
        }
      }
    },
    abrupt: function abrupt(type, arg) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];

        if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) {
          var finallyEntry = entry;
          break;
        }
      }

      if (finallyEntry && (type === "break" || type === "continue") && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc) {
        // Ignore the finally entry if control is not jumping to a
        // location outside the try/catch block.
        finallyEntry = null;
      }

      var record = finallyEntry ? finallyEntry.completion : {};
      record.type = type;
      record.arg = arg;

      if (finallyEntry) {
        this.method = "next";
        this.next = finallyEntry.finallyLoc;
        return ContinueSentinel;
      }

      return this.complete(record);
    },
    complete: function complete(record, afterLoc) {
      if (record.type === "throw") {
        throw record.arg;
      }

      if (record.type === "break" || record.type === "continue") {
        this.next = record.arg;
      } else if (record.type === "return") {
        this.rval = this.arg = record.arg;
        this.method = "return";
        this.next = "end";
      } else if (record.type === "normal" && afterLoc) {
        this.next = afterLoc;
      }

      return ContinueSentinel;
    },
    finish: function finish(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];

        if (entry.finallyLoc === finallyLoc) {
          this.complete(entry.completion, entry.afterLoc);
          resetTryEntry(entry);
          return ContinueSentinel;
        }
      }
    },
    "catch": function _catch(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];

        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;

          if (record.type === "throw") {
            var thrown = record.arg;
            resetTryEntry(entry);
          }

          return thrown;
        }
      } // The context.catch method must only be called with a location
      // argument that corresponds to a known catch block.


      throw new Error("illegal catch attempt");
    },
    delegateYield: function delegateYield(iterable, resultName, nextLoc) {
      this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      };

      if (this.method === "next") {
        // Deliberately forget the last sent value so that we don't
        // accidentally pass it on to the delegate.
        this.arg = undefined;
      }

      return ContinueSentinel;
    }
  }; // Regardless of whether this script is executing as a CommonJS module
  // or not, return the runtime object so that we can declare the variable
  // regeneratorRuntime in the outer scope, which allows this module to be
  // injected easily by `bin/regenerator --include-runtime script.js`.

  return exports;
}( // If this script is executing as a CommonJS module, use module.exports
// as the regeneratorRuntime namespace. Otherwise create a new empty
// object. Either way, the resulting object will be used to initialize
// the regeneratorRuntime variable at the top of this file.
 true ? module.exports : 0);

try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  // This module should not be running in strict mode, so the above
  // assignment should always work unless something is misconfigured. Just
  // in case runtime.js accidentally runs in strict mode, in modern engines
  // we can explicitly access globalThis. In older engines we can escape
  // strict mode using a global Function call. This could conceivably fail
  // if a Content Security Policy forbids using Function, but in that case
  // the proper solution is to fix the accidental strict mode problem. If
  // you've misconfigured your bundler to force strict mode and applied a
  // CSP to forbid Function, and you're not willing to fix either of those
  // problems, please detail your unique predicament in a GitHub issue.
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}

/***/ }),

/***/ "@flarum/core/admin":
/*!******************************!*\
  !*** external "flarum.core" ***!
  \******************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core;

/***/ }),

/***/ "flarum/common/Component":
/*!*********************************************************!*\
  !*** external "flarum.core.compat['common/Component']" ***!
  \*********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Component'];

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

/***/ "flarum/common/components/LinkButton":
/*!*********************************************************************!*\
  !*** external "flarum.core.compat['common/components/LinkButton']" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/LinkButton'];

/***/ }),

/***/ "flarum/common/components/LoadingIndicator":
/*!***************************************************************************!*\
  !*** external "flarum.core.compat['common/components/LoadingIndicator']" ***!
  \***************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/LoadingIndicator'];

/***/ }),

/***/ "flarum/common/components/Modal":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Modal']" ***!
  \****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Modal'];

/***/ }),

/***/ "flarum/common/components/Page":
/*!***************************************************************!*\
  !*** external "flarum.core.compat['common/components/Page']" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Page'];

/***/ }),

/***/ "flarum/common/components/Separator":
/*!********************************************************************!*\
  !*** external "flarum.core.compat['common/components/Separator']" ***!
  \********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Separator'];

/***/ }),

/***/ "flarum/common/extend":
/*!******************************************************!*\
  !*** external "flarum.core.compat['common/extend']" ***!
  \******************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/extend'];

/***/ }),

/***/ "flarum/common/helpers/highlight":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/highlight']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/highlight'];

/***/ }),

/***/ "flarum/common/helpers/humanTime":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/humanTime']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/humanTime'];

/***/ }),

/***/ "flarum/common/helpers/listItems":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/helpers/listItems']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/helpers/listItems'];

/***/ }),

/***/ "flarum/common/models/Discussion":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/models/Discussion']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/models/Discussion'];

/***/ }),

/***/ "flarum/common/utils/Stream":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/utils/Stream']" ***!
  \************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/Stream'];

/***/ }),

/***/ "flarum/common/utils/classList":
/*!***************************************************************!*\
  !*** external "flarum.core.compat['common/utils/classList']" ***!
  \***************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/classList'];

/***/ }),

/***/ "flarum/common/utils/computed":
/*!**************************************************************!*\
  !*** external "flarum.core.compat['common/utils/computed']" ***!
  \**************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/computed'];

/***/ }),

/***/ "flarum/common/utils/extract":
/*!*************************************************************!*\
  !*** external "flarum.core.compat['common/utils/extract']" ***!
  \*************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/extract'];

/***/ }),

/***/ "flarum/common/utils/extractText":
/*!*****************************************************************!*\
  !*** external "flarum.core.compat['common/utils/extractText']" ***!
  \*****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/extractText'];

/***/ }),

/***/ "flarum/common/utils/isDark":
/*!************************************************************!*\
  !*** external "flarum.core.compat['common/utils/isDark']" ***!
  \************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/isDark'];

/***/ }),

/***/ "flarum/forum/app":
/*!**************************************************!*\
  !*** external "flarum.core.compat['forum/app']" ***!
  \**************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/app'];

/***/ }),

/***/ "flarum/forum/components/DiscussionComposer":
/*!****************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/DiscussionComposer']" ***!
  \****************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/DiscussionComposer'];

/***/ }),

/***/ "flarum/forum/components/DiscussionHero":
/*!************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/DiscussionHero']" ***!
  \************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/DiscussionHero'];

/***/ }),

/***/ "flarum/forum/components/DiscussionListItem":
/*!****************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/DiscussionListItem']" ***!
  \****************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/DiscussionListItem'];

/***/ }),

/***/ "flarum/forum/components/DiscussionPage":
/*!************************************************************************!*\
  !*** external "flarum.core.compat['forum/components/DiscussionPage']" ***!
  \************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/DiscussionPage'];

/***/ }),

/***/ "flarum/forum/components/EventPost":
/*!*******************************************************************!*\
  !*** external "flarum.core.compat['forum/components/EventPost']" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/EventPost'];

/***/ }),

/***/ "flarum/forum/components/IndexPage":
/*!*******************************************************************!*\
  !*** external "flarum.core.compat['forum/components/IndexPage']" ***!
  \*******************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/components/IndexPage'];

/***/ }),

/***/ "flarum/forum/states/DiscussionListState":
/*!*************************************************************************!*\
  !*** external "flarum.core.compat['forum/states/DiscussionListState']" ***!
  \*************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/states/DiscussionListState'];

/***/ }),

/***/ "flarum/forum/states/GlobalSearchState":
/*!***********************************************************************!*\
  !*** external "flarum.core.compat['forum/states/GlobalSearchState']" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/states/GlobalSearchState'];

/***/ }),

/***/ "flarum/forum/utils/DiscussionControls":
/*!***********************************************************************!*\
  !*** external "flarum.core.compat['forum/utils/DiscussionControls']" ***!
  \***********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/utils/DiscussionControls'];

/***/ }),

/***/ "flarum/forum/utils/KeyboardNavigatable":
/*!************************************************************************!*\
  !*** external "flarum.core.compat['forum/utils/KeyboardNavigatable']" ***!
  \************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['forum/utils/KeyboardNavigatable'];

/***/ }),

/***/ "../../../node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js":
/*!****************************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js ***!
  \****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _asyncToGenerator)
/* harmony export */ });
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
  try {
    var info = gen[key](arg);
    var value = info.value;
  } catch (error) {
    reject(error);
    return;
  }

  if (info.done) {
    resolve(value);
  } else {
    Promise.resolve(value).then(_next, _throw);
  }
}

function _asyncToGenerator(fn) {
  return function () {
    var self = this,
        args = arguments;
    return new Promise(function (resolve, reject) {
      var gen = fn.apply(self, args);

      function _next(value) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
      }

      function _throw(err) {
        asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
      }

      _next(undefined);
    });
  };
}

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

/***/ "../../../node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js":
/*!****************************************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/esm/objectWithoutPropertiesLoose.js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _objectWithoutPropertiesLoose)
/* harmony export */ });
function _objectWithoutPropertiesLoose(source, excluded) {
  if (source == null) return {};
  var target = {};
  var sourceKeys = Object.keys(source);
  var key, i;

  for (i = 0; i < sourceKeys.length; i++) {
    key = sourceKeys[i];
    if (excluded.indexOf(key) >= 0) continue;
    target[key] = source[key];
  }

  return target;
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
  !*** ./forum.ts ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_common__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/common */ "./src/common/index.ts");
/* harmony import */ var _src_common__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_src_common__WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ var __WEBPACK_REEXPORT_OBJECT__ = {};
/* harmony reexport (unknown) */ for(const __WEBPACK_IMPORT_KEY__ in _src_common__WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== "default") __WEBPACK_REEXPORT_OBJECT__[__WEBPACK_IMPORT_KEY__] = () => _src_common__WEBPACK_IMPORTED_MODULE_0__[__WEBPACK_IMPORT_KEY__]
/* harmony reexport (unknown) */ __webpack_require__.d(__webpack_exports__, __WEBPACK_REEXPORT_OBJECT__);
/* harmony import */ var _src_forum__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./src/forum */ "./src/forum/index.ts");


})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=forum.js.map