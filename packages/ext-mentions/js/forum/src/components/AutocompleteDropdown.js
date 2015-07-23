import Component from 'flarum/Component';

export default class AutocompleteDropdown extends Component {
  constructor(...args) {
    super(...args);

    this.active = false;
    this.index = 0;
    this.keyWasJustPressed = false;
  }

  view() {
    return (
      <ul className="Dropdown-menu MentionsDropdown">
        {this.props.items.map(item => <li>{item}</li>)}
      </ul>
    );
  }

  show(left, top) {
    this.$().show().css({
      left: left + 'px',
      top: top + 'px'
    });
    this.active = true;
  }

  hide() {
    this.$().hide();
    this.active = false;
  }

  navigate(e) {
    if (!this.active) return;

    switch (e.which) {
      case 40: case 38: // Down/Up
        this.keyWasJustPressed = true;
        this.setIndex(this.index + (e.which === 40 ? 1 : -1), true);
        clearTimeout(this.keyWasJustPressedTimeout);
        this.keyWasJustPressedTimeout = setTimeout(() => this.keyWasJustPressed = false, 500);
        e.preventDefault();
        break;

      case 13: case 9: // Enter/Tab
        this.$('li').eq(this.index).find('button').click();
        e.preventDefault();
        break;

      case 27: // Escape
        this.hide();
        e.stopPropagation();
        e.preventDefault();
        break;

      default:
        // no default
    }
  }

  setIndex(index, scrollToItem) {
    if (this.keyWasJustPressed && !scrollToItem) return;

    const $dropdown = this.$();
    const $items = $dropdown.find('li');
    let rangedIndex = index;

    if (rangedIndex < 0) {
      rangedIndex = $items.length - 1;
    } else if (rangedIndex >= $items.length) {
      rangedIndex = 0;
    }

    this.index = rangedIndex;

    const $item = $items.removeClass('active').eq(rangedIndex).addClass('active');

    if (scrollToItem) {
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
        $dropdown.stop(true).animate({scrollTop}, 100);
      }
    }
  }
}
