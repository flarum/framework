import Component, { type ComponentAttrs } from 'flarum/common/Component';
import AutocompleteDropdown, { type AutocompleteDropdownAttrs } from 'flarum/common/components/AutocompleteDropdown';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import type { DeckColumnConfig, DeckColumnSearch } from '../columns/DeckColumnType';

/** Waits for a pause in typing, so a search isn't sent per keystroke. */
const DEBOUNCE_MS = 250;

export interface IDeckSearchFieldAttrs extends ComponentAttrs {
  id?: string;
  search: DeckColumnSearch;
  placeholder?: string;
  onpick: (params: DeckColumnConfig['params'] | null) => void;
}

/**
 * A text input that searches as the member types and makes them pick a result,
 * so columns are always built from something that exists (a real user, a real
 * discussion) rather than a typed username or a pasted id.
 */
export default class DeckSearchField<CustomAttrs extends IDeckSearchFieldAttrs = IDeckSearchFieldAttrs> extends Component<CustomAttrs> {
  protected query = '';
  protected results: unknown[] = [];
  protected loading = false;
  protected timer: number | null = null;
  /** Ignores responses to queries the member has since typed past. */
  protected latest = 0;

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (this.timer) clearTimeout(this.timer);
  }

  view() {
    const { search, placeholder, id } = this.attrs;

    return (
      <DeckSearchSuggestions
        query={this.query}
        onchange={(value: string) => this.input(value)}
        suggestions={this.results.map((result, i) => (
          <li data-index={i}>
            <button type="button" onclick={() => this.pick(result)}>
              {search.display(result, this.query)}
            </button>
          </li>
        ))}
      >
        <input
          id={id}
          className="FormControl"
          type="search"
          autocomplete="off"
          placeholder={placeholder}
          value={this.query}
          oninput={(e: InputEvent) => this.input((e.target as HTMLInputElement).value)}
        />
        {this.loading && <LoadingIndicator size="small" display="inline" className="DeckSearchField-loading" />}
      </DeckSearchSuggestions>
    );
  }

  protected input(value: string): void {
    const { search, onpick } = this.attrs;

    this.query = value;
    this.results = [];
    onpick(null);

    if (this.timer) clearTimeout(this.timer);

    if (value.trim().length < (search.minLength ?? 2)) {
      this.loading = false;
      return;
    }

    this.loading = true;
    this.timer = window.setTimeout(() => {
      const request = ++this.latest;

      search
        .find(value.trim())
        .then((results) => {
          if (request === this.latest) this.results = results;
        })
        .catch(() => {})
        .finally(() => {
          if (request === this.latest) this.loading = false;
          m.redraw();
        });
    }, DEBOUNCE_MS);
  }

  protected pick(result: unknown): void {
    this.query = this.attrs.search.label(result);
    this.results = [];
    this.attrs.onpick(this.attrs.search.params(result));
  }
}

interface IDeckSearchSuggestionsAttrs extends AutocompleteDropdownAttrs {
  suggestions: JSX.Element[];
}

/** Core's autocomplete, for keyboard navigation and the suggestion list styling. */
class DeckSearchSuggestions extends AutocompleteDropdown<IDeckSearchSuggestionsAttrs> {
  suggestions(): JSX.Element[] {
    return this.attrs.suggestions;
  }

  selectSuggestion() {
    // Enter with nothing suggested yet does nothing, rather than throwing.
    if (this.selectableItems().length) super.selectSuggestion();
  }

  // The modal's content scrolls; the window-based height cap would clip it.
  updateMaxHeight() {}
}
