import Fragment from 'flarum/common/Fragment';
import type Mithril from 'mithril';

export default class AutocompleteDropdown extends Fragment {
  items: Mithril.Vnode[] = [];
  active: boolean = false;
  index: number = 0;
  keyWasJustPressed: boolean = false;
  keyWasJustPressedTimeout: ReturnType<typeof setTimeout> | null = null;

  view(): Mithril.Vnode<Mithril.Attributes, this> {
    return (
      <ul className="Dropdown-menu EmojiDropdown">
        <li className="Dropdown-header">{app.translator.trans('flarum-emoji.forum.composer.type_to_search_text')}</li>
        {this.items.map((item) => (
          <li key={(item as any).attrs?.key}>{item}</li>
        ))}
      </ul>
    ) as Mithril.Vnode<Mithril.Attributes, this>;
  }

  show(left?: number, top?: number): void {
    this.$()
      .show()
      .css({
        left: (left ?? 0) + 'px',
        top: (top ?? 0) + 'px',
      });
    this.active = true;
  }

  hide(): void {
    this.$().hide();
    this.active = false;
  }

  navigate(delta: number): void {
    this.keyWasJustPressed = true;
    this.setIndex(this.index + delta, true);
    if (this.keyWasJustPressedTimeout) clearTimeout(this.keyWasJustPressedTimeout);
    this.keyWasJustPressedTimeout = setTimeout(() => (this.keyWasJustPressed = false), 500);
  }

  complete(): void {
    this.$('li:not(.Dropdown-header)').eq(this.index).find('button').click();
  }

  setIndex(index: number, scrollToItem?: boolean): void {
    if (this.keyWasJustPressed && !scrollToItem) return;

    const $dropdown = this.$();
    const $items = $dropdown.find('li:not(.Dropdown-header)');
    let rangedIndex = index;

    if (rangedIndex < 0) {
      rangedIndex = $items.length - 1;
    } else if (rangedIndex >= $items.length) {
      rangedIndex = 0;
    }

    this.index = rangedIndex;

    const $item = $items.removeClass('active').eq(rangedIndex).addClass('active');

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop()!;
      const dropdownTop = $dropdown.offset()!.top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight()!;
      const itemTop = $item.offset()!.top;
      const itemBottom = itemTop + $item.outerHeight()!;

      let scrollTop: number | undefined;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({ scrollTop }, 100);
      }
    }
  }
}
