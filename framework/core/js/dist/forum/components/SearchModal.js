"use strict";
(self["webpackChunkflarum_core"] = self["webpackChunkflarum_core"] || []).push([["forum/components/SearchModal"],{

/***/ "./src/forum/components/SearchModal.tsx":
/*!**********************************************!*\
  !*** ./src/forum/components/SearchModal.tsx ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ SearchModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "../../../js-packages/webpack-config/node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../app */ "./src/forum/app.ts");
/* harmony import */ var _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../common/components/FormModal */ "./src/common/components/FormModal.tsx");
/* harmony import */ var _common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../common/utils/KeyboardNavigatable */ "./src/common/utils/KeyboardNavigatable.ts");
/* harmony import */ var _common_SearchManager__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../common/SearchManager */ "./src/common/SearchManager.ts");
/* harmony import */ var _common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../common/utils/extractText */ "./src/common/utils/extractText.ts");
/* harmony import */ var _common_components_Input__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../common/components/Input */ "./src/common/components/Input.tsx");
/* harmony import */ var _common_components_Button__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../common/components/Button */ "./src/common/components/Button.tsx");
/* harmony import */ var _common_utils_Stream__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../common/utils/Stream */ "./src/common/utils/Stream.ts");
/* harmony import */ var _common_components_InfoTile__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../common/components/InfoTile */ "./src/common/components/InfoTile.tsx");
/* harmony import */ var _common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../common/components/LoadingIndicator */ "./src/common/components/LoadingIndicator.tsx");
/* harmony import */ var _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../common/utils/ItemList */ "./src/common/utils/ItemList.ts");
/* harmony import */ var _common_utils_GambitsAutocomplete__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../common/utils/GambitsAutocomplete */ "./src/common/utils/GambitsAutocomplete.tsx");













class SearchModal extends _common_components_FormModal__WEBPACK_IMPORTED_MODULE_2__["default"] {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "searchState", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "query", void 0);
    /**
     * An array of SearchSources.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "sources", void 0);
    /**
     * The key of the currently-active search source.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "activeSource", void 0);
    /**
     * The sources that are still loading results.
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loadingSources", []);
    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     */
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "index", 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "navigator", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "searchTimeout", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "inputScroll", (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(0));
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "gambitsAutocomplete", {});
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.searchState = this.attrs.searchState;
    this.sources = this.attrs.sources;
    this.query = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(this.searchState.getValue() || '');
  }
  title() {
    return _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.title');
  }
  className() {
    return 'SearchModal Modal--flat';
  }
  content() {
    var _this$gambitsAutocomp, _this$activeSource$re;
    // Initialize the active source.
    if (!this.activeSource) this.activeSource = (0,_common_utils_Stream__WEBPACK_IMPORTED_MODULE_8__["default"])(this.sources[0]);
    (_this$gambitsAutocomp = this.gambitsAutocomplete)[_this$activeSource$re = this.activeSource().resource] || (_this$gambitsAutocomp[_this$activeSource$re] = new _common_utils_GambitsAutocomplete__WEBPACK_IMPORTED_MODULE_12__["default"](this.activeSource().resource, () => this.inputElement(), this.query, value => this.search(value)));
    const searchLabel = (0,_common_utils_extractText__WEBPACK_IMPORTED_MODULE_5__["default"])(_app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.placeholder'));
    return m("div", {
      className: "Modal-body SearchModal-body"
    }, m("div", {
      className: "SearchModal-form"
    }, m(_common_components_Input__WEBPACK_IMPORTED_MODULE_6__["default"], {
      key: "search",
      type: "search",
      loading: !!this.loadingSources.length,
      clearable: true,
      clearLabel: _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.header.search_clear_button_accessible_label'),
      prefixIcon: "fas fa-search",
      "aria-label": searchLabel,
      placeholder: searchLabel,
      value: this.query(),
      onchange: value => {
        var _this$inputElement$0$, _this$inputElement$;
        this.query(value);
        this.inputScroll((_this$inputElement$0$ = (_this$inputElement$ = this.inputElement()[0]) == null ? void 0 : _this$inputElement$.scrollLeft) != null ? _this$inputElement$0$ : 0);
      },
      inputAttrs: {
        className: 'SearchModal-input'
      },
      renderInput: attrs => m('[', null, m("input", Object.assign({}, attrs, {
        onscroll: e => this.inputScroll(e.target.scrollLeft)
      })), m("div", {
        className: "SearchModal-visual-wrapper"
      }, m("div", {
        className: "SearchModal-visual-input",
        style: {
          left: '-' + this.inputScroll() + 'px'
        }
      }, this.gambifyInput())))
    })), this.tabs());
  }
  tabs() {
    return m("div", {
      className: "SearchModal-tabs"
    }, m("div", {
      className: "SearchModal-tabs-nav"
    }, this.tabItems().toArray()), m("div", {
      className: "SearchModal-tabs-content"
    }, this.activeTabItems().toArray()));
  }
  tabItems() {
    var _this$sources;
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_11__["default"]();
    (_this$sources = this.sources) == null ? void 0 : _this$sources.map((source, index) => items.add(source.resource, m(_common_components_Button__WEBPACK_IMPORTED_MODULE_7__["default"], {
      className: "Button Button--link",
      active: this.activeSource() === source,
      onclick: () => this.switchSource(source)
    }, source.title()), 100 - index));
    return items;
  }
  activeTabItems() {
    var _this$activeSource;
    const items = new _common_utils_ItemList__WEBPACK_IMPORTED_MODULE_11__["default"]();
    const loading = this.loadingSources.includes(this.activeSource().resource);
    const shouldShowResults = !!this.query() && !loading;
    const gambits = this.gambits();
    const fullPageLink = this.activeSource().fullPage(this.query());
    const results = (_this$activeSource = this.activeSource()) == null ? void 0 : _this$activeSource.view(this.query());
    if (shouldShowResults && fullPageLink) {
      items.add('fullPageLink', m("div", {
        className: "SearchModal-section"
      }, m("hr", {
        className: "Modal-divider"
      }), m("ul", {
        className: "Dropdown-menu SearchModal-fullPage"
      }, fullPageLink)), 80);
    }
    if (!!gambits.length) {
      items.add('gambits', m("div", {
        className: "SearchModal-section"
      }, m("hr", {
        className: "Modal-divider"
      }), m("ul", {
        className: "Dropdown-menu SearchModal-options",
        "aria-live": gambits.length ? 'polite' : undefined
      }, m("li", {
        className: "Dropdown-header"
      }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.options_heading')), gambits)), 60);
    }
    items.add('results', m("div", {
      className: "SearchModal-section"
    }, m("hr", {
      className: "Modal-divider"
    }), m("ul", {
      className: "Dropdown-menu SearchModal-results",
      "aria-live": shouldShowResults ? 'polite' : undefined
    }, m("li", {
      className: "Dropdown-header"
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.preview_heading')), !shouldShowResults && m("li", {
      className: "Dropdown-message"
    }, m(_common_components_InfoTile__WEBPACK_IMPORTED_MODULE_9__["default"], {
      icon: "fas fa-search"
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.no_search_text'))), shouldShowResults && results, shouldShowResults && !(results != null && results.length) && m("li", {
      className: "Dropdown-message"
    }, m(_common_components_InfoTile__WEBPACK_IMPORTED_MODULE_9__["default"], {
      icon: "far fa-tired"
    }, _app__WEBPACK_IMPORTED_MODULE_1__["default"].translator.trans('core.forum.search.no_results_text'))), loading && m("li", {
      className: "Dropdown-message"
    }, m(_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_10__["default"], null)))), 40);
    return items;
  }
  switchSource(source) {
    if (this.activeSource() !== source) {
      this.activeSource(source);
      this.search(this.query());
      this.inputElement().focus();
      m.redraw();
    }
  }
  gambits() {
    return this.gambitsAutocomplete[this.activeSource().resource].suggestions(this.query());
  }

  /**
   * Transforms a simple search text to wrap valid gambits in a mark tag.
   * @example `lorem ipsum is:unread dolor` => `lorem ipsum <mark>is:unread</mark> dolor`
   */
  gambifyInput() {
    const query = this.query();
    let marked = query;
    _app__WEBPACK_IMPORTED_MODULE_1__["default"].search.gambits.match(this.activeSource().resource, query, (gambit, matches, negate, bit) => {
      marked = marked.replace(bit, "<mark>".concat(bit, "</mark>"));
    });
    const jsx = [];
    marked.split(/(<mark>.*?<\/mark>)/).forEach(chunk => {
      if (chunk.startsWith('<mark>')) {
        jsx.push(m("mark", null, chunk.replace(/<\/?mark>/g, '')));
      } else {
        jsx.push(chunk);
      }
    });
    return jsx;
  }
  onupdate(vnode) {
    var _this$sources2;
    super.onupdate(vnode);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());
    const component = this;
    this.$('.Dropdown-menu')
    // Whenever the mouse is hovered over a search result, highlight it.
    .on('mouseenter', '> li:not(.Dropdown-header):not(.Dropdown-message)', function () {
      component.setIndex(component.selectableItems().index(this));
    });

    // If there are no sources, the search view is not shown.
    if (!((_this$sources2 = this.sources) != null && _this$sources2.length)) return;
  }
  oncreate(vnode) {
    var _this$sources3;
    super.oncreate(vnode);

    // If there are no sources, we shouldn't initialize logic for
    // search elements, as they will not be shown.
    if (!((_this$sources3 = this.sources) != null && _this$sources3.length)) return;
    const search = this.search.bind(this);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());
    const $input = this.inputElement();
    this.navigator = new _common_utils_KeyboardNavigatable__WEBPACK_IMPORTED_MODULE_3__["default"]();
    this.navigator.onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true)).onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true)).onSelect(this.selectResult.bind(this), true).onCancel(this.clear.bind(this)).bindTo($input);

    // Handle input key events on the search input, triggering results to load.
    $input.on('input focus', function () {
      search(this.value.toLowerCase());
    });
  }
  onremove(vnode) {
    this.searchState.setValue(this.query());
    super.onremove(vnode);
  }
  search(query) {
    if (!query) return;
    const source = this.activeSource();
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    this.searchTimeout = window.setTimeout(() => {
      if (source.isCached(query)) return;
      if (query.length >= _common_SearchManager__WEBPACK_IMPORTED_MODULE_4__["default"].MIN_SEARCH_LEN) {
        if (!source.search) return;
        this.loadingSources.push(source.resource);
        source.search(query, SearchModal.LIMIT).then(() => {
          this.loadingSources = this.loadingSources.filter(resource => resource !== source.resource);
          m.redraw();
        });
      }
      this.searchState.cache(query);
      m.redraw();
    }, 250);
  }

  /**
   * Navigate to the currently selected search result and close the list.
   */
  selectResult() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    this.loadingSources = [];
    const item = this.getItem(this.index);
    const isResult = !!item.attr('data-id');
    let selectedUrl = null;
    if (isResult) {
      const id = item.attr('data-id');
      selectedUrl = id && this.activeSource().gotoItem(id);
    } else if (item.find('a').length) {
      selectedUrl = item.find('a').attr('href');
    }
    const query = this.query();
    if (query && selectedUrl) {
      m.route.set(selectedUrl);
    } else {
      item.find('button')[0].click();
    }
  }

  /**
   * Clear the search
   */
  clear() {
    this.query('');
  }

  /**
   * Get all of the search result items that are selectable.
   */
  selectableItems() {
    return this.$('.Dropdown-menu > li:not(.Dropdown-header):not(.Dropdown-message)');
  }

  /**
   * Get the position of the currently selected search result item.
   * Returns zero if not found.
   */
  getCurrentNumericIndex() {
    return Math.max(0, this.selectableItems().index(this.getItem(this.index)));
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   */
  getItem(index) {
    const $items = this.selectableItems();
    let $item = $items.filter("[data-index=\"".concat(index, "\"]"));
    if (!$item.length) {
      $item = $items.eq(index);
    }
    return $item;
  }

  /**
   * Set the currently-selected search result item to the one with the given
   * index.
   */
  setIndex(index, scrollToItem) {
    if (scrollToItem === void 0) {
      scrollToItem = false;
    }
    const $items = this.selectableItems();
    const $dropdown = $items.parent();
    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = $items.length - 1;
    } else if (index >= $items.length) {
      fixedIndex = 0;
    }
    const $item = $items.removeClass('active').eq(fixedIndex).addClass('active');
    this.index = parseInt($item.attr('data-index')) || fixedIndex;
    if (scrollToItem && $dropdown) {
      const dropdownScroll = $dropdown.scrollTop();
      const dropdownTop = $dropdown.offset().top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight();
      const itemTop = $item.offset().top;
      const itemBottom = itemTop + $item.outerHeight();
      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }
      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({
          scrollTop
        }, 100);
      }
    }
  }
  inputElement() {
    return this.$('.SearchModal-input');
  }
}
(0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(SearchModal, "LIMIT", 6);
flarum.reg.add('core', 'forum/components/SearchModal', SearchModal);

/***/ })

}]);
//# sourceMappingURL=SearchModal.js.map