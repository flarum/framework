import Component from 'flarum/component';

export default class AutocompleteDropdown extends Component {
  constructor(props) {
    super(props);

    this.active = m.prop(false);
    this.index = m.prop(0);
  }

  view() {
    return m('ul.dropdown-menu.mentions-dropdown', {config: this.element}, this.props.items.map(item => m('li', item)));
  }

  show(left, top) {
    this.$().show().css({
      left: left+'px',
      top: top+'px'
    });
    this.active(true);
  }

  hide() {
    this.$().hide();
    this.active(false);
  }

  navigate(e) {
    if (!this.active()) return;

    switch (e.which) {
      case 40: // Down
        this.setIndex(this.index() + 1, true);
        e.preventDefault();
        break;

      case 38: // Up
        this.setIndex(this.index() - 1, true);
        e.preventDefault();
        break;

      case 13: case 9: // Enter/Tab
        this.$('li').eq(this.index()).find('a').click();
        e.preventDefault();
        break;

      case 27: // Escape
        this.hide();
        e.stopPropagation();
        e.preventDefault();
        break;
    }
  }

  setIndex(index, scrollToItem) {
    var $dropdown = this.$();
    var $items = $dropdown.find('li');

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    this.index(index);

    var $item = $items.removeClass('active').eq(index).addClass('active');

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
