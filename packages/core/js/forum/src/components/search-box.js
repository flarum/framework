import Component from 'flarum/component';
import DiscussionPage from 'flarum/components/discussion-page';
import IndexPage from 'flarum/components/index-page';
import ActionButton from 'flarum/components/action-button';
import LoadingIndicator from 'flarum/components/loading-indicator';
import ItemList from 'flarum/utils/item-list';
import classList from 'flarum/utils/class-list';
import listItems from 'flarum/helpers/list-items';
import icon from 'flarum/helpers/icon';
import DiscussionsSearchResults from 'flarum/components/discussions-search-results';
import UsersSearchResults from 'flarum/components/users-search-results';

/**
 * A search box, which displays a menu of as-you-type results from a variety of
 * sources.
 *
 * The search box will be 'activated' if the app's current controller implements
 * a `searching` method that returns a truthy value. If this is the case, an 'x'
 * button will be shown next to the search field, and clicking it will call the
 * `clearSearch` method on the controller.
 */
export default class SearchBox extends Component {
  constructor(props) {
    super(props);

    this.value = m.prop(this.getCurrentSearch() || '');
    this.hasFocus = m.prop(false);

    this.sources = this.sourceItems().toArray();
    this.loadingSources = 0;
    this.searched = [];

    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     */
    this.index = m.prop(0);
  }

  getCurrentSearch() {
    return typeof app.current.searching === 'function' && app.current.searching();
  }

  view() {
    var currentSearch = this.getCurrentSearch();

    return m('div.search-box.dropdown', {
      config: this.onload.bind(this),
      className: classList({
        open: this.value() && this.hasFocus(),
        active: !!currentSearch,
        loading: !!this.loadingSources,
      })
    },
      m('div.search-input',
        m('input.form-control', {
          placeholder: 'Search Forum',
          value: this.value(),
          oninput: m.withAttr('value', this.value),
          onfocus: () => this.hasFocus(true),
          onblur: () => this.hasFocus(false)
        }),
        this.loadingSources
          ? LoadingIndicator.component({size: 'tiny', className: 'btn btn-icon btn-link'})
          : currentSearch
            ? m('button.clear.btn.btn-icon.btn-link', {onclick: this.clear.bind(this)}, icon('times-circle'))
            : ''
      ),
      m('ul.dropdown-menu.dropdown-menu-right.search-results', this.sources.map(source => source.view(this.value())))
    );
  }

  onload(element, isInitialized, context) {
    this.element(element);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    if (isInitialized) return;

    var self = this;

    this.$('.search-results')
      .on('mousedown', e => e.preventDefault())
      .on('click', () => this.$('input').blur())

      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.dropdown-header)', function(e) {
        self.setIndex(
          self.selectableItems().index(this)
        );
      });

    // Handle navigation key events on the search input.
    this.$('input')
      .on('keydown', e => {
        switch (e.which) {
          case 40: case 38: // Down/Up
            this.setIndex(this.getCurrentNumericIndex() + (e.which === 40 ? 1 : -1), true);
            e.preventDefault();
            break;

          case 13: // Return
            this.$('input').blur();
            this.getItem(this.index()).find('a')[0].dispatchEvent(new Event('click'));
            break;

          case 27: // Escape
            this.clear();
            break;
        }
      })

      // Handle input key events on the search input, triggering results to
      // load.
      .on('input focus', function(e) {
        var value = this.value.toLowerCase();

        if (value) {
          clearTimeout(self.searchTimeout);
          self.searchTimeout = setTimeout(() => {
            if (self.searched.indexOf(value) === -1) {
              if (value.length >= 3) {
                self.sources.map(source => {
                  if (source.search) {
                    self.loadingSources++;
                    source.search(value).then(() => {
                      self.loadingSources--;
                      m.redraw();
                    });
                  }
                });
              }
              self.searched.push(value);
              m.redraw();
            }
          }, 500);
        }
      });
  }

  clear() {
    this.value('');
    if (this.getCurrentSearch()) {
      app.current.clearSearch();
    } else {
      m.redraw();
    }
  }

  sourceItems() {
    var items = new ItemList();

    items.add('discussions', new DiscussionsSearchResults());
    items.add('users', new UsersSearchResults());

    return items;
  }

  selectableItems() {
    return this.$('.search-results > li:not(.dropdown-header)');
  }

  getCurrentNumericIndex() {
    return this.selectableItems().index(
      this.getItem(this.index())
    );
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   *
   * @param {String} index
   * @return {DOMElement}
   */
  getItem(index) {
    var $items = this.selectableItems();
    var $item = $items.filter('[data-index='+index+']');

    if (!$item.length) {
      $item = $items.eq(index);
    }

    return $item;
  }

  setIndex(index, scrollToItem) {
    var $items = this.selectableItems();
    var $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    var $item = $items.removeClass('active').eq(index).addClass('active');

    this.index($item.attr('data-index') || index);

    if (scrollToItem) {
      var dropdownScroll = $dropdown.scrollTop();
      var dropdownTop = $dropdown.offset().top;
      var dropdownBottom = dropdownTop + $dropdown.outerHeight();
      var itemTop = $item.offset().top;
      var itemBottom = itemTop + $item.outerHeight();

      var scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'));
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'));
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({scrollTop}, 100);
      }
    }
  }
}
