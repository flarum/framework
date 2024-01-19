import Component, { type ComponentAttrs } from '../Component';
import KeyboardNavigatable from '../utils/KeyboardNavigatable';
import type Mithril from 'mithril';
import classList from '../utils/classList';

export interface AutocompleteDropdownAttrs extends ComponentAttrs {
  query: string;
  onchange: (value: string) => void;
}

/**
 * A reusable component that wraps around an input element and displays a list
 * of suggestions based on the input's value.
 * Must be extended and the `suggestions` method implemented.
 */
export default abstract class AutocompleteDropdown<
  CustomAttrs extends AutocompleteDropdownAttrs = AutocompleteDropdownAttrs
> extends Component<CustomAttrs> {
  /**
   * The index of the currently-selected <li> in the results list. This can be
   * a unique string (to account for the fact that an item's position may jump
   * around as new results load), but otherwise it will be numeric (the
   * sequential position within the list).
   */
  protected index: number = 0;

  protected navigator!: KeyboardNavigatable;

  private updateMaxHeightHandler?: () => void;

  /**
   * Whether the input has focus.
   */
  protected hasFocus = false;

  abstract suggestions(): JSX.Element[];

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const suggestions = this.suggestions();
    const shouldShowSuggestions = !!suggestions.length;

    return (
      <div
        className={classList('AutocompleteDropdown', {
          focused: this.hasFocus,
          open: shouldShowSuggestions && this.hasFocus,
        })}
      >
        {vnode.children}
        <ul
          className="Dropdown-menu Dropdown-suggestions"
          aria-hidden={!shouldShowSuggestions || undefined}
          aria-live={shouldShowSuggestions ? 'polite' : undefined}
        >
          {suggestions}
        </ul>
      </div>
    );
  }

  updateMaxHeight() {
    // Since extensions might add elements above the search box on mobile,
    // we need to calculate and set the max height dynamically.
    const resultsElementMargin = 14;
    const maxHeight = window.innerHeight - this.element.querySelector('.FormControl')!.getBoundingClientRect().bottom - resultsElementMargin;

    this.element.querySelector<HTMLElement>('.Dropdown-suggestions')?.style?.setProperty('max-height', `${maxHeight}px`);
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    this.updateMaxHeight();
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    const component = this;

    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    this.$('.Dropdown-suggestions')
      .on('mousedown', (e) => e.preventDefault())
      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.Dropdown-header)', function () {
        component.setIndex(component.selectableItems().index(this));
      });

    const $input = this.inputElement();

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectSuggestion.bind(this), true)
      .bindTo($input);

    $input
      .on('focus', function () {
        component.hasFocus = true;
        m.redraw();

        $(this)
          .one('mouseup', (e) => e.preventDefault())
          .trigger('select');
      })
      .on('blur', function () {
        component.hasFocus = false;
        m.redraw();
      });

    this.updateMaxHeightHandler = this.updateMaxHeight.bind(this);
    window.addEventListener('resize', this.updateMaxHeightHandler);
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (this.updateMaxHeightHandler) {
      window.removeEventListener('resize', this.updateMaxHeightHandler);
    }
  }

  selectableItems(): JQuery {
    return this.$('.Dropdown-suggestions > li:not(.Dropdown-header)');
  }

  inputElement(): JQuery<HTMLInputElement> {
    return this.$('input') as JQuery<HTMLInputElement>;
  }

  selectSuggestion() {
    this.getItem(this.index).find('button')[0].click();
  }

  /**
   * Get the position of the currently selected item.
   * Returns zero if not found.
   */
  getCurrentNumericIndex(): number {
    return Math.max(0, this.selectableItems().index(this.getItem(this.index)));
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   */
  getItem(index: number): JQuery {
    const $items = this.selectableItems();
    let $item = $items.filter(`[data-index="${index}"]`);

    if (!$item.length) {
      $item = $items.eq(index);
    }

    return $item;
  }

  /**
   * Set the currently-selected search result item to the one with the given
   * index.
   */
  setIndex(index: number, scrollToItem: boolean = false) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = $items.length - 1;
    } else if (index >= $items.length) {
      fixedIndex = 0;
    }

    const $item = $items.removeClass('active').eq(fixedIndex).addClass('active');

    this.index = parseInt($item.attr('data-index') as string) || fixedIndex;

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop()!;
      const dropdownTop = $dropdown.offset()!.top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight()!;
      const itemTop = $item.offset()!.top;
      const itemBottom = itemTop + $item.outerHeight()!;

      let scrollTop;
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
