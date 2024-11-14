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

    const suggestions = this.element.querySelector('.Dropdown-suggestions')! as HTMLDivElement;
    suggestions.addEventListener('mousedown', (e) => e.preventDefault());
    // Whenever the mouse is hovered over a search result, highlight it.
    suggestions.addEventListener('mouseenter', (e) => {
      const el = e.target as HTMLElement;
      if (el.parentElement != suggestions || el.tagName != 'LI' || el.classList.contains('Dropdown-header')) return;
      component.setIndex(component.selectableItems().indexOf(el as HTMLLIElement));
    });

    const input = this.inputElement();

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectSuggestion.bind(this), true)
      .bindTo(input);

    input.addEventListener('focus', function() {
      component.hasFocus = true;
      m.redraw();

      this.addEventListener('mouseup', (e) => e.preventDefault(), { once: true });
      this.dispatchEvent(new Event('select'));
    });
    input.addEventListener('blur', () => {
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

  selectableItems(): HTMLLIElement[] {
    return Array.from(this.element.querySelectorAll('.Dropdown-suggestions > li:not(.Dropdown-header)')) as HTMLLIElement[];
  }

  inputElement(): HTMLInputElement {
    return this.element.querySelector('input') as HTMLInputElement;
  }

  selectSuggestion() {
    this.getItem(this.index).querySelector('button')!.click();
  }

  /**
   * Get the position of the currently selected item.
   * Returns zero if not found.
   */
  getCurrentNumericIndex(): number {
    return Math.max(0, this.selectableItems().indexOf(this.getItem(this.index)));
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   */
  getItem(index: number): HTMLLIElement {
    const items = this.selectableItems();
    const filtered = items.filter((v) => v.getAttribute('data-index') == index.toString());

    if (!filtered.length) {
      return items[index];
    }

    return filtered[0];
  }

  /**
   * Set the currently-selected search result item to the one with the given
   * index.
   */
  setIndex(index: number, scrollToItem: boolean = false) {
    const items = this.selectableItems();

    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = items.length - 1;
    } else if (index >= items.length) {
      fixedIndex = 0;
    }

    items.forEach(el => el.classList.remove('active'));
    const item = items[fixedIndex];
    const dropdown = item.parentElement!;
    item.classList.add('active');

    this.index = parseInt(item.getAttribute('data-index') as string) || fixedIndex;

    if (scrollToItem) {
      const documentScrollTop = document.documentElement.scrollTop;
      const dropdownScroll = dropdown.scrollTop!;
      const dropdownRect = dropdown.getBoundingClientRect();
      const dropdownTop = dropdownRect.top + documentScrollTop;
      const dropdownBottom = dropdownTop + dropdownRect.height;
      const itemRect = item.getBoundingClientRect();
      const itemTop = itemRect.top + documentScrollTop;
      const itemBottom = itemTop + itemRect.height;

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt(dropdown.style.paddingTop, 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt(dropdown.style.paddingBottom, 10);
      }

      if (typeof scrollTop !== 'undefined') {
        dropdown.scrollTo({
          top: scrollTop,
          behavior: 'smooth'
        });
      }
    }
  }
}
