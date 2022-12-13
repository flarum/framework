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
/* harmony import */ var _components_TagSelectionModal__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/TagSelectionModal */ "./src/common/components/TagSelectionModal.tsx");
/* harmony import */ var _states_TagListState__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./states/TagListState */ "./src/common/states/TagListState.ts");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  'tags/utils/sortTags': _utils_sortTags__WEBPACK_IMPORTED_MODULE_0__["default"],
  'tags/models/Tag': _models_Tag__WEBPACK_IMPORTED_MODULE_1__["default"],
  'tags/helpers/tagsLabel': _helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_2__["default"],
  'tags/helpers/tagIcon': _helpers_tagIcon__WEBPACK_IMPORTED_MODULE_3__["default"],
  'tags/helpers/tagLabel': _helpers_tagLabel__WEBPACK_IMPORTED_MODULE_4__["default"],
  'tags/components/TagSelectionModal': _components_TagSelectionModal__WEBPACK_IMPORTED_MODULE_5__["default"],
  'tags/states/TagListState': _states_TagListState__WEBPACK_IMPORTED_MODULE_6__["default"]
});

/***/ }),

/***/ "./src/common/components/TagSelectionModal.tsx":
/*!*****************************************************!*\
  !*** ./src/common/components/TagSelectionModal.tsx ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagSelectionModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/app */ "flarum/common/app");
/* harmony import */ var flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/helpers/highlight */ "flarum/common/helpers/highlight");
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/KeyboardNavigatable */ "flarum/common/utils/KeyboardNavigatable");
/* harmony import */ var flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! flarum/common/utils/Stream */ "flarum/common/utils/Stream");
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _utils_sortTags__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/sortTags */ "./src/common/utils/sortTags.tsx");
/* harmony import */ var _helpers_tagLabel__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../helpers/tagLabel */ "./src/common/helpers/tagLabel.js");
/* harmony import */ var _helpers_tagIcon__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../helpers/tagIcon */ "./src/common/helpers/tagIcon.js");
/* harmony import */ var _forum_components_ToggleButton__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../../forum/components/ToggleButton */ "./src/forum/components/ToggleButton.js");














var TagSelectionModal = /*#__PURE__*/function (_Modal) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagSelectionModal, _Modal);
  function TagSelectionModal() {
    var _this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _Modal.call.apply(_Modal, [this].concat(args)) || this;
    _this.loading = true;
    _this.tags = void 0;
    _this.selected = [];
    _this.bypassReqs = false;
    _this.filter = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_9___default()('');
    _this.focused = false;
    _this.navigator = new (flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_6___default())();
    _this.indexTag = void 0;
    return _this;
  }
  TagSelectionModal.initAttrs = function initAttrs(attrs) {
    var _attrs$allowResetting, _attrs$limits$min$tot, _attrs$limits, _attrs$limits$min, _attrs$limits$min$pri, _attrs$limits2, _attrs$limits2$min, _attrs$limits$min$sec, _attrs$limits3, _attrs$limits3$min, _attrs$limits$max$tot, _attrs$limits4, _attrs$limits4$max, _attrs$limits$max$pri, _attrs$limits5, _attrs$limits5$max, _attrs$limits$max$sec, _attrs$limits6, _attrs$limits6$max;
    _Modal.initAttrs.call(this, attrs);

    // Default values for optional attributes.
    attrs.title || (attrs.title = flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.title')));
    attrs.canSelect || (attrs.canSelect = function () {
      return true;
    });
    (_attrs$allowResetting = attrs.allowResetting) != null ? _attrs$allowResetting : attrs.allowResetting = true;
    attrs.limits = {
      min: {
        total: (_attrs$limits$min$tot = (_attrs$limits = attrs.limits) == null ? void 0 : (_attrs$limits$min = _attrs$limits.min) == null ? void 0 : _attrs$limits$min.total) != null ? _attrs$limits$min$tot : -Infinity,
        primary: (_attrs$limits$min$pri = (_attrs$limits2 = attrs.limits) == null ? void 0 : (_attrs$limits2$min = _attrs$limits2.min) == null ? void 0 : _attrs$limits2$min.primary) != null ? _attrs$limits$min$pri : -Infinity,
        secondary: (_attrs$limits$min$sec = (_attrs$limits3 = attrs.limits) == null ? void 0 : (_attrs$limits3$min = _attrs$limits3.min) == null ? void 0 : _attrs$limits3$min.secondary) != null ? _attrs$limits$min$sec : -Infinity
      },
      max: {
        total: (_attrs$limits$max$tot = (_attrs$limits4 = attrs.limits) == null ? void 0 : (_attrs$limits4$max = _attrs$limits4.max) == null ? void 0 : _attrs$limits4$max.total) != null ? _attrs$limits$max$tot : Infinity,
        primary: (_attrs$limits$max$pri = (_attrs$limits5 = attrs.limits) == null ? void 0 : (_attrs$limits5$max = _attrs$limits5.max) == null ? void 0 : _attrs$limits5$max.primary) != null ? _attrs$limits$max$pri : Infinity,
        secondary: (_attrs$limits$max$sec = (_attrs$limits6 = attrs.limits) == null ? void 0 : (_attrs$limits6$max = _attrs$limits6.max) == null ? void 0 : _attrs$limits6$max.secondary) != null ? _attrs$limits$max$sec : Infinity
      }
    };

    // Prevent illogical limits from being provided.
    catchInvalidLimits(attrs.limits);
  };
  var _proto = TagSelectionModal.prototype;
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
    flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().tagList.load(['parent']).then(function (tags) {
      _this2.loading = false;
      if (_this2.attrs.selectableTags) {
        tags = _this2.attrs.selectableTags(tags);
      }
      _this2.tags = (0,_utils_sortTags__WEBPACK_IMPORTED_MODULE_10__["default"])(tags);
      if (_this2.attrs.selectedTags) {
        _this2.attrs.selectedTags.map(_this2.addTag.bind(_this2));
      }
      _this2.indexTag = tags[0];
      m.redraw();
    });
  };
  _proto.className = function className() {
    return flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()('TagSelectionModal', this.attrs.className);
  };
  _proto.title = function title() {
    return this.attrs.title;
  };
  _proto.content = function content() {
    var _this3 = this;
    if (this.loading || !this.tags) {
      return m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_7___default()), null);
    }
    var filter = this.filter().toLowerCase();
    var primaryCount = this.primaryCount();
    var secondaryCount = this.secondaryCount();
    var tags = this.getFilteredTags();
    var inputWidth = Math.max(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(this.getInstruction(primaryCount, secondaryCount)).length, this.filter().length);
    return [m("div", {
      className: "Modal-body"
    }, m("div", {
      className: "TagSelectionModal-form"
    }, m("div", {
      className: "TagSelectionModal-form-input"
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
      }, (0,_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_11__["default"])(tag));
    })), m("input", {
      className: "FormControl",
      placeholder: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(this.getInstruction(primaryCount, secondaryCount)),
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
      className: "TagSelectionModal-form-submit App-primaryControl"
    }, m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default()), {
      type: "submit",
      className: "Button Button--primary",
      disabled: !this.meetsRequirements(primaryCount, secondaryCount),
      icon: "fas fa-check"
    }, flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.submit_button'))))), m("div", {
      className: "Modal-footer"
    }, m("ul", {
      className: "TagSelectionModal-list SelectTagList"
    }, tags.map(function (tag) {
      return m("li", {
        "data-index": tag.id(),
        className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()({
          pinned: tag.position() !== null,
          child: !!tag.parent(),
          colored: !!tag.color(),
          selected: _this3.selected.includes(tag),
          active: _this3.indexTag === tag
        }),
        style: {
          color: tag.color()
        },
        onmouseover: function onmouseover() {
          return _this3.indexTag = tag;
        },
        onclick: _this3.toggleTag.bind(_this3, tag)
      }, (0,_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_12__["default"])(tag), m("span", {
        className: "SelectTagListItem-name"
      }, flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_5___default()(tag.name(), filter)), tag.description() ? m("span", {
        className: "SelectTagListItem-description"
      }, tag.description()) : '');
    })), this.attrs.limits.allowBypassing && m("div", {
      className: "TagSelectionModal-controls"
    }, m(_forum_components_ToggleButton__WEBPACK_IMPORTED_MODULE_13__["default"], {
      className: "Button",
      onclick: function onclick() {
        return _this3.bypassReqs = !_this3.bypassReqs;
      },
      isToggled: this.bypassReqs
    }, flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.bypass_requirements'))))];
  }

  /**
   * Filters the available tags on every state change.
   */;
  _proto.getFilteredTags = function getFilteredTags() {
    var _this4 = this;
    var filter = this.filter().toLowerCase();
    var primaryCount = this.primaryCount();
    var secondaryCount = this.secondaryCount();
    var tags = this.tags;
    if (this.attrs.requireParentTag) {
      // Filter out all child tags whose parents have not been selected. This
      // makes it impossible to select a child if its parent hasn't been selected.
      tags = tags.filter(function (tag) {
        var parent = tag.parent();
        return parent !== null && (parent === false || _this4.selected.includes(parent));
      });
    }
    if (!this.bypassReqs) {
      // If we reached the total maximum number of tags, we can't select anymore.
      if (this.selected.length >= this.attrs.limits.max.total) {
        tags = tags.filter(function (tag) {
          return _this4.selected.includes(tag);
        });
      }
      // If the number of selected primary/secondary tags is at the maximum, then
      // we'll filter out all other tags of that type.
      else {
        if (primaryCount >= this.attrs.limits.max.primary) {
          tags = tags.filter(function (tag) {
            return !tag.isPrimary() || _this4.selected.includes(tag);
          });
        }
        if (secondaryCount >= this.attrs.limits.max.secondary) {
          tags = tags.filter(function (tag) {
            return tag.isPrimary() || _this4.selected.includes(tag);
          });
        }
      }
    }

    // If the user has entered text in the filter input, then filter by tags
    // whose name matches what they've entered.
    if (filter) {
      tags = tags.filter(function (tag) {
        return tag.name().substring(0, filter.length).toLowerCase() === filter;
      });
    }
    if (!this.indexTag || !tags.includes(this.indexTag)) this.indexTag = tags[0];
    return tags;
  }

  /**
   * Counts the number of selected primary tags.
   */;
  _proto.primaryCount = function primaryCount() {
    return this.selected.filter(function (tag) {
      return tag.isPrimary();
    }).length;
  }

  /**
   * Counts the number of selected secondary tags.
   */;
  _proto.secondaryCount = function secondaryCount() {
    return this.selected.filter(function (tag) {
      return !tag.isPrimary();
    }).length;
  }

  /**
   * Validates the number of selected primary/secondary tags against the set min max limits.
   */;
  _proto.meetsRequirements = function meetsRequirements(primaryCount, secondaryCount) {
    if (this.bypassReqs || this.attrs.allowResetting && this.selected.length === 0) {
      return true;
    }
    if (this.selected.length < this.attrs.limits.min.total) {
      return false;
    }
    return primaryCount >= this.attrs.limits.min.primary && secondaryCount >= this.attrs.limits.min.secondary;
  }

  /**
   * Add the given tag to the list of selected tags.
   */;
  _proto.addTag = function addTag(tag) {
    if (!tag || !this.attrs.canSelect(tag)) return;
    if (this.attrs.onSelect) {
      this.attrs.onSelect(tag, this.selected);
    }

    // If this tag has a parent, we'll also need to add the parent tag to the
    // selected list if it's not already in there.
    if (this.attrs.requireParentTag) {
      var parent = tag.parent();
      if (parent && !this.selected.includes(parent)) {
        this.selected.push(parent);
      }
    }
    if (!this.selected.includes(tag)) {
      this.selected.push(tag);
    }
  }

  /**
   * Remove the given tag from the list of selected tags.
   */;
  _proto.removeTag = function removeTag(tag) {
    var index = this.selected.indexOf(tag);
    if (index !== -1) {
      this.selected.splice(index, 1);

      // Look through the list of selected tags for any tags which have the tag
      // we just removed as their parent. We'll need to remove them too.
      if (this.attrs.requireParentTag) {
        this.selected.filter(function (t) {
          return t.parent() === tag;
        }).forEach(this.removeTag.bind(this));
      }
      if (this.attrs.onDeselect) {
        this.attrs.onDeselect(tag, this.selected);
      }
    }
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
      this.indexTag = this.tags[0];
    }
    this.onready();
  }

  /**
   * Gives human text instructions based on the current number of selected tags and set limits.
   */;
  _proto.getInstruction = function getInstruction(primaryCount, secondaryCount) {
    if (this.bypassReqs) {
      return '';
    }
    if (primaryCount < this.attrs.limits.min.primary) {
      var remaining = this.attrs.limits.min.primary - primaryCount;
      return flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.choose_primary_placeholder', {
        count: remaining
      }));
    } else if (secondaryCount < this.attrs.limits.min.secondary) {
      var _remaining = this.attrs.limits.min.secondary - secondaryCount;
      return flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.choose_secondary_placeholder', {
        count: _remaining
      }));
    } else if (this.selected.length < this.attrs.limits.min.total) {
      var _remaining2 = this.attrs.limits.min.total - this.selected.length;
      return flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.lib.tag_selection_modal.choose_tags_placeholder', {
        count: _remaining2
      }));
    }
    return '';
  }

  /**
   * Submit tag selection.
   */;
  _proto.onsubmit = function onsubmit(e) {
    e.preventDefault();
    if (this.attrs.onsubmit) this.attrs.onsubmit(this.selected);
    this.hide();
  };
  _proto.select = function select(e) {
    // Ctrl + Enter submits the selection, just Enter completes the current entry
    if (e.metaKey || e.ctrlKey || this.indexTag && this.selected.includes(this.indexTag)) {
      if (this.selected.length) {
        // The DOM submit method doesn't emit a `submit event, so we
        // simulate a manual submission so our `onsubmit` logic is run.
        this.$('button[type="submit"]').click();
      }
    } else if (this.indexTag) {
      this.getItem(this.indexTag)[0].dispatchEvent(new Event('click'));
    }
  };
  _proto.selectableItems = function selectableItems() {
    return this.$('.TagSelectionModal-list > li');
  };
  _proto.getCurrentNumericIndex = function getCurrentNumericIndex() {
    if (!this.indexTag) return -1;
    return this.selectableItems().index(this.getItem(this.indexTag));
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
    this.indexTag = flarum_common_app__WEBPACK_IMPORTED_MODULE_1___default().store.getById('tags', $item.attr('data-index'));
    m.redraw();
    if (scrollToItem && this.indexTag) {
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
  return TagSelectionModal;
}((flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_8___default()));
/**
 * Catch invalid limits provided to the tag selection modal.
 */

function catchInvalidLimits(limits) {
  if (limits.min.primary > limits.max.primary) {
    throw new Error('The minimum number of primary tags allowed cannot be more than the maximum number of primary tags allowed.');
  }
  if (limits.min.secondary > limits.max.secondary) {
    throw new Error('The minimum number of secondary tags allowed cannot be more than the maximum number of secondary tags allowed.');
  }
  if (limits.min.total > limits.max.primary + limits.max.secondary) {
    throw new Error('The minimum number of tags allowed cannot be more than the maximum number of primary and secondary tags allowed together.');
  }
  if (limits.max.total < limits.min.primary + limits.min.secondary) {
    throw new Error('The maximum number of tags allowed cannot be less than the minimum number of primary and secondary tags allowed together.');
  }
  if (limits.min.total > limits.max.total) {
    throw new Error('The minimum number of tags allowed cannot be more than the maximum number of tags allowed.');
  }
}

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
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/components/Link */ "flarum/common/components/Link");
/* harmony import */ var flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _tagIcon__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./tagIcon */ "./src/common/helpers/tagIcon.js");



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
  return m(link ? (flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_1___default()) : 'span', attrs, m("span", {
    className: "TagLabel-text"
  }, tag && tag.icon() && (0,_tagIcon__WEBPACK_IMPORTED_MODULE_2__["default"])(tag, {}, {
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

/***/ "./src/common/states/TagListState.ts":
/*!*******************************************!*\
  !*** ./src/common/states/TagListState.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagListState)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_asyncToGenerator__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/asyncToGenerator */ "../../../node_modules/@babel/runtime/helpers/esm/asyncToGenerator.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/regenerator */ "../../../node_modules/@babel/runtime/regenerator/index.js");
/* harmony import */ var _babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_babel_runtime_regenerator__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_app__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/app */ "flarum/common/app");
/* harmony import */ var flarum_common_app__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_app__WEBPACK_IMPORTED_MODULE_2__);



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
              return _context.abrupt("return", Promise.resolve(flarum_common_app__WEBPACK_IMPORTED_MODULE_2___default().store.all('tags')));
            case 4:
              return _context.abrupt("return", flarum_common_app__WEBPACK_IMPORTED_MODULE_2___default().store.find('tags', {
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
    var bPos = b.position();

    // If they're both secondary tags, sort them by their discussions count,
    // descending.
    if (aPos === null && bPos === null) return b.discussionCount() - a.discussionCount();

    // If just one is a secondary tag, then the primary tag should
    // come first.
    if (bPos === null) return -1;
    if (aPos === null) return 1;

    // If we've made it this far, we know they're both primary tags. So we'll
    // need to see if they have parents.
    var aParent = a.parent();
    var bParent = b.parent();

    // If they both have the same parent, then their positions are local,
    // so we can compare them directly.
    if (aParent === bParent) return aPos - bPos;
    // If they are both child tags, then we will compare the positions of their
    // parents.
    else if (aParent && bParent) return aParent.position() - bParent.position();
    // If we are comparing a child tag with its parent, then we let the parent
    // come first. If we are comparing an unrelated parent/child, then we
    // compare both of the parents.
    else if (aParent) return aParent === b ? 1 : aParent.position() - bPos;else if (bParent) return bParent === a ? -1 : aPos - bParent.position();
    return 0;
  });
}

/***/ }),

/***/ "./src/forum/addComposerAutocomplete.js":
/*!**********************************************!*\
  !*** ./src/forum/addComposerAutocomplete.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ addComposerAutocomplete)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/TextEditor */ "flarum/common/components/TextEditor");
/* harmony import */ var flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_TextEditorButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/TextEditorButton */ "flarum/common/components/TextEditorButton");
/* harmony import */ var flarum_common_components_TextEditorButton__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_TextEditorButton__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/helpers/highlight */ "flarum/common/helpers/highlight");
/* harmony import */ var flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/utils/KeyboardNavigatable */ "flarum/common/utils/KeyboardNavigatable");
/* harmony import */ var flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_utils_throttleDebounce__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/throttleDebounce */ "flarum/common/utils/throttleDebounce");
/* harmony import */ var flarum_common_utils_throttleDebounce__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_throttleDebounce__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_components_Badge__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/components/Badge */ "flarum/common/components/Badge");
/* harmony import */ var flarum_common_components_Badge__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Badge__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _fragments_AutocompleteDropdown__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./fragments/AutocompleteDropdown */ "./src/forum/fragments/AutocompleteDropdown.js");
/* harmony import */ var _utils_TagMentionTextGenerator__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/TagMentionTextGenerator */ "./src/forum/utils/TagMentionTextGenerator.ts");










var throttledSearch = (0,flarum_common_utils_throttleDebounce__WEBPACK_IMPORTED_MODULE_6__.throttle)(250,
// 250ms timeout
function (typed, searched, returnedTags, returnedTagIds, dropdown, buildSuggestions) {
  var typedLower = typed.toLowerCase();
  if (!searched.includes(typedLower)) {
    flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.find('tags', {
      filter: {
        q: typed
      },
      page: {
        limit: 5
      }
    }).then(function (results) {
      results.forEach(function (u) {
        if (!returnedTagIds.has(u.id())) {
          returnedTagIds.add(u.id());
          returnedTags.push(u);
        }
      });
      buildSuggestions();
    });
    searched.push(typedLower);
  }
});
function addComposerAutocomplete() {
  var $container = $('<div class="ComposerBody-mentionsDropdownContainer"></div>');
  var dropdown = new _fragments_AutocompleteDropdown__WEBPACK_IMPORTED_MODULE_8__["default"]();
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'oncreate', function () {
    var $editor = this.$('.TextEditor-editor').wrap('<div class="ComposerBody-mentionsWrapper"></div>');
    this.navigator = new (flarum_common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_5___default())();
    this.navigator.when(function () {
      return dropdown.active;
    }).onUp(function () {
      return dropdown.navigate(-1);
    }).onDown(function () {
      return dropdown.navigate(1);
    }).onSelect(dropdown.complete.bind(dropdown)).onCancel(dropdown.hide.bind(dropdown)).bindTo($editor);
    $editor.after($container);
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'buildEditorParams', function (params) {
    var _this = this;
    var searched = [];
    var relMentionStart;
    var absMentionStart;
    var typed;
    var matchTyped;
    var mentionTextGenerator = new _utils_TagMentionTextGenerator__WEBPACK_IMPORTED_MODULE_9__["default"]();

    // Store tags..
    var returnedTags = Array.from(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.all('tags'));
    var returnedTagIds = new Set(returnedTags.map(function (t) {
      return t.id();
    }));
    var applySuggestion = function applySuggestion(replacement) {
      _this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');
      dropdown.hide();
    };
    params.inputListeners.push(function () {
      var selection = _this.attrs.composer.editor.getSelectionRange();
      var cursor = selection[0];
      if (selection[1] - cursor > 0) return;

      // Search backwards from the cursor for an '#' symbol. If we find one,
      // we will want to show the autocomplete dropdown!
      var lastChunk = _this.attrs.composer.editor.getLastNChars(30);
      absMentionStart = 0;
      for (var i = lastChunk.length - 1; i >= 0; i--) {
        var character = lastChunk.substr(i, 1);
        if (character === '#' && (i == 0 || /\s/.test(lastChunk.substr(i - 1, 1)))) {
          relMentionStart = i + 1;
          absMentionStart = cursor - lastChunk.length + i + 1;
          break;
        }
      }
      dropdown.hide();
      dropdown.active = false;
      if (absMentionStart) {
        typed = lastChunk.substring(relMentionStart).toLowerCase();
        matchTyped = typed.match(/^["|“]((?:(?!"#).)+)$/);
        typed = matchTyped && matchTyped[1] || typed;
        var makeTagSuggestion = function makeTagSuggestion(tag, replacement, content, className) {
          if (className === void 0) {
            className = '';
          }
          var tagName = tag.name().toLowerCase();
          if (typed) {
            tagName = flarum_common_helpers_highlight__WEBPACK_IMPORTED_MODULE_4___default()(tagName, typed);
          }
          return m("button", {
            className: 'PostPreview ' + className,
            onclick: function onclick() {
              return applySuggestion(replacement);
            },
            onmouseenter: function onmouseenter() {
              dropdown.setIndex($(this).parent().index());
            }
          }, m("span", {
            className: "PostPreview-content"
          }, m((flarum_common_components_Badge__WEBPACK_IMPORTED_MODULE_7___default()), {
            "class": "Avatar Badge Badge--tag--" + tag.id() + " Badge-icon ",
            color: tag.color(),
            type: "tag",
            icon: tag.icon()
          }), m("span", {
            className: "username"
          }, tagName)));
        };
        var tagMatches = function tagMatches(tag) {
          var names = [tag.name()];
          return names.some(function (name) {
            return name.toLowerCase().substr(0, typed.length) === typed;
          });
        };
        var buildSuggestions = function buildSuggestions() {
          var suggestions = [];

          // If the user has started to type a tag name, then suggest tags
          // matching that name.
          if (typed) {
            returnedTags.forEach(function (tag) {
              if (!tagMatches(tag)) return;
              suggestions.push(makeTagSuggestion(tag, mentionTextGenerator.forTag(tag), '', 'MentionsDropdown-tag'));
            });
          }
          if (suggestions.length) {
            dropdown.items = suggestions;
            m.render($container[0], dropdown.render());
            dropdown.show();
            var coordinates = _this.attrs.composer.editor.getCaretCoordinates(absMentionStart);
            var width = dropdown.$().outerWidth();
            var height = dropdown.$().outerHeight();
            var parent = dropdown.$().offsetParent();
            var left = coordinates.left;
            var top = coordinates.top + 15;

            // Keep the dropdown inside the editor.
            if (top + height > parent.height()) {
              top = coordinates.top - height - 15;
            }
            if (left + width > parent.width()) {
              left = parent.width() - width;
            }

            // Prevent the dropdown from going off screen on mobile
            top = Math.max(-(parent.offset().top - $(document).scrollTop()), top);
            left = Math.max(-parent.offset().left, left);
            dropdown.show(left, top);
          } else {
            dropdown.active = false;
            dropdown.hide();
          }
        };
        dropdown.active = true;
        buildSuggestions();
        dropdown.setIndex(0);
        dropdown.$().scrollTop(0);

        // Don't send API calls searching for users or tags until at least 2 characters have been typed.
        // This focuses the mention results on users and posts in the discussion.
        if (typed.length > 1) {
          throttledSearch(typed, searched, returnedTags, returnedTagIds, dropdown, buildSuggestions);
        }
      }
    });
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_common_components_TextEditor__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'toolbarItems', function (items) {
    var _this2 = this;
    items.add('mentionTag', m((flarum_common_components_TextEditorButton__WEBPACK_IMPORTED_MODULE_3___default()), {
      onclick: function onclick() {
        return _this2.attrs.composer.editor.insertAtCursor(' #');
      },
      icon: "fas fa-tags"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('flarum-tags.forum.composer.mention_tooltip')));
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
  });

  // Add tag-selection abilities to the discussion composer.
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
  };

  // Add a tag-selection menu to the discussion composer's header, after the
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
  });

  // Add the selected tags as data to submit to the server.
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
/* harmony import */ var _components_TagHero__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/TagHero */ "./src/forum/components/TagHero.js");







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
      this.currentTagLoading = true;

      // Unlike the backend, no need to fetch parent.children because if we're on
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
  };

  // If currently viewing a tag, insert a tag hero at the top of the view.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.override)((flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'hero', function (original) {
    var tag = this.currentTag();
    if (tag) return m(_components_TagHero__WEBPACK_IMPORTED_MODULE_6__["default"], {
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
  });

  // If currently viewing a tag, restyle the 'new discussion' button to use
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
      }
      newDiscussion.attrs.disabled = !canStartDiscussion;
      newDiscussion.children = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button');
    }
  });

  // Add a parameter for the global search state to pass on to the
  // DiscussionListState that will let us filter discussions by tag.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_states_GlobalSearchState__WEBPACK_IMPORTED_MODULE_4___default().prototype), 'params', function (params) {
    params.tags = m.route.param('tags');
  });

  // Translate that parameter into a gambit appended to the search query.
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
      filter.tag = this.params.tags;
      // TODO: replace this with a more robust system.
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
/* harmony import */ var _common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../common/helpers/tagsLabel */ "./src/common/helpers/tagsLabel.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");





/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__() {
  // Add tag labels to each discussion in the discussion list.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionListItem__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'infoItems', function (items) {
    var tags = this.attrs.discussion.tags();
    if (tags && tags.length) {
      items.add('tags', (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_3__["default"])(tags), 10);
    }
  });

  // Restyle a discussion's hero to use its first tag's color.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'view', function (view) {
    var tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_4__["default"])(this.attrs.discussion.tags());
    if (tags && tags.length) {
      var color = tags[0].color();
      if (color) {
        view.attrs.style = {
          '--hero-bg': color
        };
        view.attrs.className += ' DiscussionHero--colored';
      }
    }
  });

  // Add a list of a discussion's tags to the discussion hero, displayed
  // before the title. Put the title on its own line.
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionHero__WEBPACK_IMPORTED_MODULE_2___default().prototype), 'items', function (items) {
    var tags = this.attrs.discussion.tags();
    if (tags && tags.length) {
      items.add('tags', (0,_common_helpers_tagsLabel__WEBPACK_IMPORTED_MODULE_3__["default"])(tags, {
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
      }

      // tag.name() is passed here as children even though it isn't used directly
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
/* harmony import */ var _utils_textFormatter__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./utils/textFormatter */ "./src/forum/utils/textFormatter.js");













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
  'tags/utils/getSelectableTags': _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_11__["default"],
  'tags/utils/textFormatter': _utils_textFormatter__WEBPACK_IMPORTED_MODULE_12__
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
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/forum/components/DiscussionPage */ "flarum/forum/components/DiscussionPage");
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/getSelectableTags */ "./src/forum/utils/getSelectableTags.js");
/* harmony import */ var _common_components_TagSelectionModal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/TagSelectionModal */ "./src/common/components/TagSelectionModal.tsx");







var TagDiscussionModal = /*#__PURE__*/function (_TagSelectionModal) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(TagDiscussionModal, _TagSelectionModal);
  function TagDiscussionModal() {
    return _TagSelectionModal.apply(this, arguments) || this;
  }
  TagDiscussionModal.initAttrs = function initAttrs(attrs) {
    var _attrs$selectedTags, _attrs$discussion;
    _TagSelectionModal.initAttrs.call(this, attrs);
    var title = attrs.discussion ? flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.edit_title', {
      title: m("em", null, attrs.discussion.title())
    }) : flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('flarum-tags.forum.choose_tags.title');
    attrs.className = flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_3___default()(attrs.className, 'TagDiscussionModal');
    attrs.title = flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_4___default()(title);
    attrs.allowResetting = !!flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('canBypassTagCounts');
    attrs.limits = {
      allowBypassing: attrs.allowResetting,
      max: {
        primary: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('minPrimaryTags'),
        secondary: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('maxSecondaryTags')
      },
      min: {
        primary: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('maxPrimaryTags'),
        secondary: flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('minSecondaryTags')
      }
    };
    attrs.requireParentTag = true;
    attrs.selectableTags = function () {
      return (0,_utils_getSelectableTags__WEBPACK_IMPORTED_MODULE_5__["default"])(attrs.discussion);
    };
    (_attrs$selectedTags = attrs.selectedTags) != null ? _attrs$selectedTags : attrs.selectedTags = ((_attrs$discussion = attrs.discussion) == null ? void 0 : _attrs$discussion.tags()) || [];
    attrs.canSelect = function (tag) {
      return tag.canStartDiscussion();
    };
    var suppliedOnsubmit = attrs.onsubmit || null;

    // Save changes.
    attrs.onsubmit = function (tags) {
      var discussion = attrs.discussion;
      if (discussion) {
        discussion.save({
          relationships: {
            tags: tags
          }
        }).then(function () {
          if (flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().current.matches((flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_2___default()))) {
            flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().current.get('stream').update();
          }
          m.redraw();
        });
      }
      if (suppliedOnsubmit) suppliedOnsubmit(tags);
    };
  };
  return TagDiscussionModal;
}(_common_components_TagSelectionModal__WEBPACK_IMPORTED_MODULE_6__["default"]);


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
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");



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
      className: 'Hero TagHero' + (color ? ' TagHero--colored' : ''),
      style: color ? {
        '--hero-bg': color
      } : ''
    }, m("div", {
      className: "container"
    }, m("div", {
      className: "containerNarrow"
    }, m("h2", {
      className: "Hero-title"
    }, tag.icon() && (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_2__["default"])(tag, {}, {
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
/* harmony import */ var _common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/helpers/tagIcon */ "./src/common/helpers/tagIcon.js");
/* harmony import */ var _common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/helpers/tagLabel */ "./src/common/helpers/tagLabel.js");
/* harmony import */ var _common_utils_sortTags__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/utils/sortTags */ "./src/common/utils/sortTags.tsx");










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
      this.tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_9__["default"])(preloaded.filter(function (tag) {
        return !tag.isChild();
      }));
      return;
    }
    this.loading = true;
    app.tagList.load(['children', 'lastPostedDiscussion', 'parent']).then(function () {
      _this.tags = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_9__["default"])(app.store.all('tags').filter(function (tag) {
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
      var children = (0,_common_utils_sortTags__WEBPACK_IMPORTED_MODULE_9__["default"])(tag.children() || []);
      return m("li", {
        className: 'TagTile ' + (tag.color() ? 'colored' : ''),
        style: {
          '--tag-bg': tag.color()
        }
      }, m((flarum_common_components_Link__WEBPACK_IMPORTED_MODULE_3___default()), {
        className: "TagTile-info",
        href: app.route.tag(tag)
      }, tag.icon() && (0,_common_helpers_tagIcon__WEBPACK_IMPORTED_MODULE_7__["default"])(tag, {}, {
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
      return [(0,_common_helpers_tagLabel__WEBPACK_IMPORTED_MODULE_8__["default"])(tag, {
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

/***/ "./src/forum/fragments/AutocompleteDropdown.js":
/*!*****************************************************!*\
  !*** ./src/forum/fragments/AutocompleteDropdown.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AutocompleteDropdown)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/inheritsLoose */ "../../../node_modules/@babel/runtime/helpers/esm/inheritsLoose.js");
/* harmony import */ var flarum_common_Fragment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Fragment */ "flarum/common/Fragment");
/* harmony import */ var flarum_common_Fragment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Fragment__WEBPACK_IMPORTED_MODULE_1__);


var AutocompleteDropdown = /*#__PURE__*/function (_Fragment) {
  (0,_babel_runtime_helpers_esm_inheritsLoose__WEBPACK_IMPORTED_MODULE_0__["default"])(AutocompleteDropdown, _Fragment);
  function AutocompleteDropdown() {
    var _this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _Fragment.call.apply(_Fragment, [this].concat(args)) || this;
    _this.items = [];
    _this.active = false;
    _this.index = 0;
    _this.keyWasJustPressed = false;
    return _this;
  }
  var _proto = AutocompleteDropdown.prototype;
  _proto.view = function view() {
    return m("ul", {
      className: "Dropdown-menu MentionsDropdown"
    }, this.items.map(function (item) {
      return m("li", null, item);
    }));
  };
  _proto.show = function show(left, top) {
    this.$().show().css({
      left: left + 'px',
      top: top + 'px'
    });
    this.active = true;
  };
  _proto.hide = function hide() {
    this.$().hide();
    this.active = false;
  };
  _proto.navigate = function navigate(delta) {
    var _this2 = this;
    this.keyWasJustPressed = true;
    this.setIndex(this.index + delta, true);
    clearTimeout(this.keyWasJustPressedTimeout);
    this.keyWasJustPressedTimeout = setTimeout(function () {
      return _this2.keyWasJustPressed = false;
    }, 500);
  };
  _proto.complete = function complete() {
    this.$('li').eq(this.index).find('button').click();
  };
  _proto.setIndex = function setIndex(index, scrollToItem) {
    if (this.keyWasJustPressed && !scrollToItem) return;
    var $dropdown = this.$();
    var $items = $dropdown.find('li');
    var rangedIndex = index;
    if (rangedIndex < 0) {
      rangedIndex = $items.length - 1;
    } else if (rangedIndex >= $items.length) {
      rangedIndex = 0;
    }
    this.index = rangedIndex;
    var $item = $items.removeClass('active').eq(rangedIndex).addClass('active');
    if (scrollToItem) {
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
  return AutocompleteDropdown;
}((flarum_common_Fragment__WEBPACK_IMPORTED_MODULE_1___default()));


/***/ }),

/***/ "./src/forum/index.ts":
/*!****************************!*\
  !*** ./src/forum/index.ts ***!
  \****************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "filterTagMentions": () => (/* reexport safe */ _utils_textFormatter__WEBPACK_IMPORTED_MODULE_14__.filterTagMentions)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/Model */ "flarum/common/Model");
/* harmony import */ var flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Model__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/models/Discussion */ "flarum/common/models/Discussion");
/* harmony import */ var flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/components/IndexPage */ "flarum/forum/components/IndexPage");
/* harmony import */ var flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_IndexPage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _common_states_TagListState__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../common/states/TagListState */ "./src/common/states/TagListState.ts");
/* harmony import */ var _common_models_Tag__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../common/models/Tag */ "./src/common/models/Tag.ts");
/* harmony import */ var _components_TagsPage__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/TagsPage */ "./src/forum/components/TagsPage.js");
/* harmony import */ var _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/DiscussionTaggedPost */ "./src/forum/components/DiscussionTaggedPost.js");
/* harmony import */ var _addTagList__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./addTagList */ "./src/forum/addTagList.js");
/* harmony import */ var _addTagFilter__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./addTagFilter */ "./src/forum/addTagFilter.tsx");
/* harmony import */ var _addTagLabels__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./addTagLabels */ "./src/forum/addTagLabels.js");
/* harmony import */ var _addTagControl__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./addTagControl */ "./src/forum/addTagControl.js");
/* harmony import */ var _addTagComposer__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./addTagComposer */ "./src/forum/addTagComposer.js");
/* harmony import */ var _utils_textFormatter__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./utils/textFormatter */ "./src/forum/utils/textFormatter.js");
/* harmony import */ var _compat__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./compat */ "./src/forum/compat.js");
/* harmony import */ var _flarum_core_forum__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @flarum/core/forum */ "@flarum/core/forum");
/* harmony import */ var _flarum_core_forum__WEBPACK_IMPORTED_MODULE_16___default = /*#__PURE__*/__webpack_require__.n(_flarum_core_forum__WEBPACK_IMPORTED_MODULE_16__);
/* harmony import */ var _addComposerAutocomplete__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./addComposerAutocomplete */ "./src/forum/addComposerAutocomplete.js");













flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('flarum-tags', function () {
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().routes.tags) = {
    path: '/tags',
    component: _components_TagsPage__WEBPACK_IMPORTED_MODULE_6__["default"]
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
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().postComponents.discussionTagged) = _components_DiscussionTaggedPost__WEBPACK_IMPORTED_MODULE_7__["default"];
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.models.tags) = _common_models_Tag__WEBPACK_IMPORTED_MODULE_5__["default"];
  (flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().tagList) = new _common_states_TagListState__WEBPACK_IMPORTED_MODULE_4__["default"]();
  (flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default().prototype.tags) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().hasMany('tags');
  (flarum_common_models_Discussion__WEBPACK_IMPORTED_MODULE_2___default().prototype.canTag) = flarum_common_Model__WEBPACK_IMPORTED_MODULE_1___default().attribute('canTag');
  (0,_addTagList__WEBPACK_IMPORTED_MODULE_8__["default"])();
  (0,_addTagFilter__WEBPACK_IMPORTED_MODULE_9__["default"])();
  (0,_addTagLabels__WEBPACK_IMPORTED_MODULE_10__["default"])();
  (0,_addTagControl__WEBPACK_IMPORTED_MODULE_11__["default"])();
  (0,_addTagComposer__WEBPACK_IMPORTED_MODULE_12__["default"])();
  (0,_addComposerAutocomplete__WEBPACK_IMPORTED_MODULE_13__["default"])();
});


// Expose compat API



Object.assign(_flarum_core_forum__WEBPACK_IMPORTED_MODULE_16__.compat, _compat__WEBPACK_IMPORTED_MODULE_15__["default"]);

/***/ }),

/***/ "./src/forum/utils/TagMentionTextGenerator.ts":
/*!****************************************************!*\
  !*** ./src/forum/utils/TagMentionTextGenerator.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ TagMentionTextGenerator)
/* harmony export */ });
/**
 * Fetches the mention text for a specified model.
 */
var TagMentionTextGenerator = /*#__PURE__*/function () {
  function TagMentionTextGenerator() {}
  var _proto = TagMentionTextGenerator.prototype;
  /**
   * Generates the mention syntax for a tag mention.
   *
   * @example <caption>Tag mention</caption>
   * // '#"General"#t1'
   * // #"Name"#tTagID
   * forTag(tag) // Tag display name is 'General', tag ID is 1
   *
   * @param tag
   * @returns
   */
  _proto.forTag = function forTag(tag) {
    return "#\"" + tag.name() + "\"#t" + tag.id();
  };
  return TagMentionTextGenerator;
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

/***/ "./src/forum/utils/textFormatter.js":
/*!******************************************!*\
  !*** ./src/forum/utils/textFormatter.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "filterTagMentions": () => (/* binding */ filterTagMentions)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/utils/isDark */ "flarum/common/utils/isDark");
/* harmony import */ var flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2__);



function filterTagMentions(tag) {
  var tagModel = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().store.getById('tags', tag.getAttribute('id'));
  if (tagModel) {
    tag.setAttribute('tagname', flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_1___default()(tagModel.name()));
    tag.setAttribute('icon', tagModel.icon());
    tag.setAttribute('color', tagModel.color());
    tag.setAttribute('slug', tagModel.slug());
    tag.setAttribute('class', flarum_common_utils_isDark__WEBPACK_IMPORTED_MODULE_2___default()(tagModel.color()) ? 'TagMention--light' : 'TagMention--dark');
    return true;
  }
  tag.invalidate();
}

/***/ }),

/***/ "@flarum/core/forum":
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

/***/ "flarum/common/Fragment":
/*!********************************************************!*\
  !*** external "flarum.core.compat['common/Fragment']" ***!
  \********************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Fragment'];

/***/ }),

/***/ "flarum/common/Model":
/*!*****************************************************!*\
  !*** external "flarum.core.compat['common/Model']" ***!
  \*****************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/Model'];

/***/ }),

/***/ "flarum/common/app":
/*!***************************************************!*\
  !*** external "flarum.core.compat['common/app']" ***!
  \***************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/app'];

/***/ }),

/***/ "flarum/common/components/Badge":
/*!****************************************************************!*\
  !*** external "flarum.core.compat['common/components/Badge']" ***!
  \****************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/Badge'];

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

/***/ "flarum/common/components/TextEditor":
/*!*********************************************************************!*\
  !*** external "flarum.core.compat['common/components/TextEditor']" ***!
  \*********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/TextEditor'];

/***/ }),

/***/ "flarum/common/components/TextEditorButton":
/*!***************************************************************************!*\
  !*** external "flarum.core.compat['common/components/TextEditorButton']" ***!
  \***************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/components/TextEditorButton'];

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

/***/ "flarum/common/utils/KeyboardNavigatable":
/*!*************************************************************************!*\
  !*** external "flarum.core.compat['common/utils/KeyboardNavigatable']" ***!
  \*************************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/KeyboardNavigatable'];

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

/***/ "flarum/common/utils/throttleDebounce":
/*!**********************************************************************!*\
  !*** external "flarum.core.compat['common/utils/throttleDebounce']" ***!
  \**********************************************************************/
/***/ ((module) => {

"use strict";
module.exports = flarum.core.compat['common/utils/throttleDebounce'];

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

/***/ "../../../node_modules/@babel/runtime/helpers/regeneratorRuntime.js":
/*!**************************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/regeneratorRuntime.js ***!
  \**************************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

var _typeof = (__webpack_require__(/*! ./typeof.js */ "../../../node_modules/@babel/runtime/helpers/typeof.js")["default"]);
function _regeneratorRuntime() {
  "use strict";

  /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/facebook/regenerator/blob/main/LICENSE */
  module.exports = _regeneratorRuntime = function _regeneratorRuntime() {
    return exports;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports;
  var exports = {},
    Op = Object.prototype,
    hasOwn = Op.hasOwnProperty,
    defineProperty = Object.defineProperty || function (obj, key, desc) {
      obj[key] = desc.value;
    },
    $Symbol = "function" == typeof Symbol ? Symbol : {},
    iteratorSymbol = $Symbol.iterator || "@@iterator",
    asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator",
    toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";
  function define(obj, key, value) {
    return Object.defineProperty(obj, key, {
      value: value,
      enumerable: !0,
      configurable: !0,
      writable: !0
    }), obj[key];
  }
  try {
    define({}, "");
  } catch (err) {
    define = function define(obj, key, value) {
      return obj[key] = value;
    };
  }
  function wrap(innerFn, outerFn, self, tryLocsList) {
    var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator,
      generator = Object.create(protoGenerator.prototype),
      context = new Context(tryLocsList || []);
    return defineProperty(generator, "_invoke", {
      value: makeInvokeMethod(innerFn, self, context)
    }), generator;
  }
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
  exports.wrap = wrap;
  var ContinueSentinel = {};
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}
  var IteratorPrototype = {};
  define(IteratorPrototype, iteratorSymbol, function () {
    return this;
  });
  var getProto = Object.getPrototypeOf,
    NativeIteratorPrototype = getProto && getProto(getProto(values([])));
  NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype);
  var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);
  function defineIteratorMethods(prototype) {
    ["next", "throw", "return"].forEach(function (method) {
      define(prototype, method, function (arg) {
        return this._invoke(method, arg);
      });
    });
  }
  function AsyncIterator(generator, PromiseImpl) {
    function invoke(method, arg, resolve, reject) {
      var record = tryCatch(generator[method], generator, arg);
      if ("throw" !== record.type) {
        var result = record.arg,
          value = result.value;
        return value && "object" == _typeof(value) && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) {
          invoke("next", value, resolve, reject);
        }, function (err) {
          invoke("throw", err, resolve, reject);
        }) : PromiseImpl.resolve(value).then(function (unwrapped) {
          result.value = unwrapped, resolve(result);
        }, function (error) {
          return invoke("throw", error, resolve, reject);
        });
      }
      reject(record.arg);
    }
    var previousPromise;
    defineProperty(this, "_invoke", {
      value: function value(method, arg) {
        function callInvokeWithMethodAndArg() {
          return new PromiseImpl(function (resolve, reject) {
            invoke(method, arg, resolve, reject);
          });
        }
        return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
      }
    });
  }
  function makeInvokeMethod(innerFn, self, context) {
    var state = "suspendedStart";
    return function (method, arg) {
      if ("executing" === state) throw new Error("Generator is already running");
      if ("completed" === state) {
        if ("throw" === method) throw arg;
        return doneResult();
      }
      for (context.method = method, context.arg = arg;;) {
        var delegate = context.delegate;
        if (delegate) {
          var delegateResult = maybeInvokeDelegate(delegate, context);
          if (delegateResult) {
            if (delegateResult === ContinueSentinel) continue;
            return delegateResult;
          }
        }
        if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) {
          if ("suspendedStart" === state) throw state = "completed", context.arg;
          context.dispatchException(context.arg);
        } else "return" === context.method && context.abrupt("return", context.arg);
        state = "executing";
        var record = tryCatch(innerFn, self, context);
        if ("normal" === record.type) {
          if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue;
          return {
            value: record.arg,
            done: context.done
          };
        }
        "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg);
      }
    };
  }
  function maybeInvokeDelegate(delegate, context) {
    var method = delegate.iterator[context.method];
    if (undefined === method) {
      if (context.delegate = null, "throw" === context.method) {
        if (delegate.iterator["return"] && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method)) return ContinueSentinel;
        context.method = "throw", context.arg = new TypeError("The iterator does not provide a 'throw' method");
      }
      return ContinueSentinel;
    }
    var record = tryCatch(method, delegate.iterator, context.arg);
    if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel;
    var info = record.arg;
    return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel);
  }
  function pushTryEntry(locs) {
    var entry = {
      tryLoc: locs[0]
    };
    1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry);
  }
  function resetTryEntry(entry) {
    var record = entry.completion || {};
    record.type = "normal", delete record.arg, entry.completion = record;
  }
  function Context(tryLocsList) {
    this.tryEntries = [{
      tryLoc: "root"
    }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0);
  }
  function values(iterable) {
    if (iterable) {
      var iteratorMethod = iterable[iteratorSymbol];
      if (iteratorMethod) return iteratorMethod.call(iterable);
      if ("function" == typeof iterable.next) return iterable;
      if (!isNaN(iterable.length)) {
        var i = -1,
          next = function next() {
            for (; ++i < iterable.length;) {
              if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next;
            }
            return next.value = undefined, next.done = !0, next;
          };
        return next.next = next;
      }
    }
    return {
      next: doneResult
    };
  }
  function doneResult() {
    return {
      value: undefined,
      done: !0
    };
  }
  return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", {
    value: GeneratorFunctionPrototype,
    configurable: !0
  }), defineProperty(GeneratorFunctionPrototype, "constructor", {
    value: GeneratorFunction,
    configurable: !0
  }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) {
    var ctor = "function" == typeof genFun && genFun.constructor;
    return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name));
  }, exports.mark = function (genFun) {
    return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun;
  }, exports.awrap = function (arg) {
    return {
      __await: arg
    };
  }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
    return this;
  }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {
    void 0 === PromiseImpl && (PromiseImpl = Promise);
    var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);
    return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) {
      return result.done ? result.value : iter.next();
    });
  }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () {
    return this;
  }), define(Gp, "toString", function () {
    return "[object Generator]";
  }), exports.keys = function (val) {
    var object = Object(val),
      keys = [];
    for (var key in object) {
      keys.push(key);
    }
    return keys.reverse(), function next() {
      for (; keys.length;) {
        var key = keys.pop();
        if (key in object) return next.value = key, next.done = !1, next;
      }
      return next.done = !0, next;
    };
  }, exports.values = values, Context.prototype = {
    constructor: Context,
    reset: function reset(skipTempReset) {
      if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) {
        "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined);
      }
    },
    stop: function stop() {
      this.done = !0;
      var rootRecord = this.tryEntries[0].completion;
      if ("throw" === rootRecord.type) throw rootRecord.arg;
      return this.rval;
    },
    dispatchException: function dispatchException(exception) {
      if (this.done) throw exception;
      var context = this;
      function handle(loc, caught) {
        return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught;
      }
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i],
          record = entry.completion;
        if ("root" === entry.tryLoc) return handle("end");
        if (entry.tryLoc <= this.prev) {
          var hasCatch = hasOwn.call(entry, "catchLoc"),
            hasFinally = hasOwn.call(entry, "finallyLoc");
          if (hasCatch && hasFinally) {
            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
          } else if (hasCatch) {
            if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
          } else {
            if (!hasFinally) throw new Error("try statement without catch or finally");
            if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
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
      finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null);
      var record = finallyEntry ? finallyEntry.completion : {};
      return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record);
    },
    complete: function complete(record, afterLoc) {
      if ("throw" === record.type) throw record.arg;
      return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel;
    },
    finish: function finish(finallyLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel;
      }
    },
    "catch": function _catch(tryLoc) {
      for (var i = this.tryEntries.length - 1; i >= 0; --i) {
        var entry = this.tryEntries[i];
        if (entry.tryLoc === tryLoc) {
          var record = entry.completion;
          if ("throw" === record.type) {
            var thrown = record.arg;
            resetTryEntry(entry);
          }
          return thrown;
        }
      }
      throw new Error("illegal catch attempt");
    },
    delegateYield: function delegateYield(iterable, resultName, nextLoc) {
      return this.delegate = {
        iterator: values(iterable),
        resultName: resultName,
        nextLoc: nextLoc
      }, "next" === this.method && (this.arg = undefined), ContinueSentinel;
    }
  }, exports;
}
module.exports = _regeneratorRuntime, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../../../node_modules/@babel/runtime/helpers/typeof.js":
/*!**************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/helpers/typeof.js ***!
  \**************************************************************/
/***/ ((module) => {

function _typeof(obj) {
  "@babel/helpers - typeof";

  return (module.exports = _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  }, module.exports.__esModule = true, module.exports["default"] = module.exports), _typeof(obj);
}
module.exports = _typeof, module.exports.__esModule = true, module.exports["default"] = module.exports;

/***/ }),

/***/ "../../../node_modules/@babel/runtime/regenerator/index.js":
/*!*****************************************************************!*\
  !*** ../../../node_modules/@babel/runtime/regenerator/index.js ***!
  \*****************************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

// TODO(Babel 8): Remove this file.

var runtime = __webpack_require__(/*! ../helpers/regeneratorRuntime */ "../../../node_modules/@babel/runtime/helpers/regeneratorRuntime.js")();
module.exports = runtime;

// Copied from https://github.com/facebook/regenerator/blob/main/packages/runtime/runtime.js#L736=
try {
  regeneratorRuntime = runtime;
} catch (accidentalStrictMode) {
  if (typeof globalThis === "object") {
    globalThis.regeneratorRuntime = runtime;
  } else {
    Function("r", "regeneratorRuntime = r")(runtime);
  }
}

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
  _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) {
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
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "filterTagMentions": () => (/* reexport safe */ _src_forum__WEBPACK_IMPORTED_MODULE_1__.filterTagMentions)
/* harmony export */ });
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