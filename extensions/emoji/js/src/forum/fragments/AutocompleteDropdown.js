import Fragment from 'flarum/common/Fragment';

export default class AutocompleteDropdown extends Fragment {
  items = [];
  active = false;
  index = 0;
  keyWasJustPressed = false;

  view() {
    return (
      <ul className="Dropdown-menu EmojiDropdown">
        <li className="Dropdown-header">{app.translator.trans('flarum-emoji.forum.composer.type_to_search_text')}</li>
        {this.items.map((item) => (
          <li key={item.attrs.key}>{item}</li>
        ))}
      </ul>
    );
  }

  show(left, top) {
    const style = this.element.style;
    style.display = 'block';
    style.left = left + 'px';
    style.top = top + 'px';
    this.active = true;
  }

  hide() {
    this.element.style.display = 'none';
    this.active = false;
  }

  navigate(delta) {
    this.keyWasJustPressed = true;
    this.setIndex(this.index + delta, true);
    clearTimeout(this.keyWasJustPressedTimeout);
    this.keyWasJustPressedTimeout = setTimeout(() => (this.keyWasJustPressed = false), 500);
  }

  complete() {
    this.element.querySelectorAll('li:not(.Dropdown-header)')[this.index].querySelector('button').click();
  }

  // todo: check if copied implementation matches the original behavior
  setIndex(index, scrollToItem) {
    if (this.keyWasJustPressed && !scrollToItem) return;

    const dropdown = this.element;
    const items = dropdown.querySelectorAll('li:not(.Dropdown-header)');
    let rangedIndex = index;

    if (rangedIndex < 0) {
      rangedIndex = items.length - 1;
    } else if (rangedIndex >= items.length) {
      rangedIndex = 0;
    }

    this.index = rangedIndex;

    items.forEach((el) => el.classList.remove('active'));
    const item = items[rangedIndex];
    item.classList.add('active');

    if (scrollToItem) {
      const documentScrollTop = document.documentElement.scrollTop;
      const dropdownScroll = dropdown.scrollTop;
      const dropdownRect = dropdown.getBoundingClientRect();
      const dropdownTop = dropdownRect.top + documentScrollTop;
      const dropdownBottom = dropdownTop + dropdownRect.height;
      const itemRect = item.getBoundingClientRect();
      const itemTop = itemRect.top + documentScrollTop;
      const itemBottom = itemTop + itemRect.height;

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt(getComputedStyle(dropdown).paddingTop, 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt(getComputedStyle(dropdown).paddingBottom, 10);
      }

      if (typeof scrollTop !== 'undefined') {
        dropdown.scrollTo({
          top: scrollTop,
          behavior: 'smooth',
        });
      }
    }
  }
}
