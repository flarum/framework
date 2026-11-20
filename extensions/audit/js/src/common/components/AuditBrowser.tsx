import app from 'flarum/common/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { ApiResponsePlural } from 'flarum/common/Store';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';
import extractText from 'flarum/common/utils/extractText';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import generateElementId from 'flarum/common/utils/generateElementId';
import AuditItem from './AuditItem';
import AuditLog from '../models/AuditLog';

const translationPrefix = 'flarum-audit.lib.browser.';

interface AuditFilter {
  key: string;
  example: string;
  description: string | null;
  values: string[];
  extension: string | null;
}

interface AuditBrowserAttrs extends ComponentAttrs {
  baseQ?: string;
}

export default class AuditBrowser extends Component<AuditBrowserAttrs> {
  q: string = '';
  loading: boolean = true;
  moreResults: boolean = false;
  logs: AuditLog[] = [];
  showHelp: boolean = false;
  caret: number = 0;
  autocompleteFocused: boolean = false;
  suggestionIndex: number = -1;
  navigator!: KeyboardNavigatable;
  escapeHandled: boolean = false;
  requestId: number = 0;
  listId: string = generateElementId();

  oninit(vnode: any) {
    super.oninit(vnode);

    this.navigator = new KeyboardNavigatable()
      .when((event) => {
        if (event.isComposing) return false;
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
          const modified = event.shiftKey || event.altKey || event.ctrlKey || event.metaKey;
          return !modified && !!this.autocompleteGroups();
        }
        // An Escape with nothing to close or clear falls through, so it still closes a surrounding modal.
        if (event.key === 'Escape') return !!this.autocompleteGroups() || this.q !== '';
        return true;
      })
      .onUp(() => this.moveSuggestion(-1))
      .onDown(() => this.moveSuggestion(1))
      .onSelect(() => this.select(), true)
      .onCancel(() => this.cancel());

    this.refresh();
  }

  view() {
    let loading;

    if (this.loading) {
      loading = <LoadingIndicator />;
    } else if (this.moreResults) {
      loading = (
        <Button className="Button Button--block" onclick={this.loadMore.bind(this)}>
          {app.translator.trans(translationPrefix + 'loadMore')}
        </Button>
      );
    }

    const groups = this.autocompleteGroups();

    return (
      <div>
        <div className="AuditSearch">
          <div className="AuditSearchWrapper">
            <input
              className="FormControl"
              role="combobox"
              aria-autocomplete="list"
              aria-expanded={groups ? 'true' : 'false'}
              aria-controls={this.listId}
              aria-activedescendant={groups && this.suggestionIndex >= 0 ? this.optionId(this.suggestionIndex) : undefined}
              value={this.q}
              oninput={(event: InputEvent) => {
                const el = event.target as HTMLInputElement;
                this.q = el.value;
                this.caret = el.selectionStart ?? el.value.length;
                this.autocompleteFocused = true;
                this.suggestionIndex = -1;
              }}
              onkeydown={(event: KeyboardEvent) => {
                // A held key would otherwise resend the request, or step Escape through closing the
                // suggestions, clearing the filter and then closing the surrounding modal.
                if (event.repeat && (event.key === 'Enter' || event.key === 'Escape')) {
                  if (event.key === 'Enter' || this.escapeHandled) event.preventDefault();
                  return;
                }

                this.navigator.navigate(event);

                if (event.key === 'Escape') this.escapeHandled = event.defaultPrevented;
                // moveSuggestion() has already redrawn, to scroll the highlight into view.
                if ((event.key === 'ArrowUp' || event.key === 'ArrowDown') && event.defaultPrevented) (event as any).redraw = false;
              }}
              onkeyup={(event: KeyboardEvent) => {
                // ModalManager closes the modal on a body keyup when CloseWatcher is unavailable.
                if (event.key === 'Escape' && this.escapeHandled) {
                  event.stopPropagation();
                  this.escapeHandled = false;
                }
                this.setCaret((event.target as HTMLInputElement).selectionStart ?? this.q.length);
              }}
              onclick={(event: MouseEvent) => {
                this.setCaret((event.target as HTMLInputElement).selectionStart ?? this.q.length);
              }}
              onfocus={() => {
                this.autocompleteFocused = true;
                this.suggestionIndex = -1;
              }}
              onblur={() => {
                // Delay so a click on a suggestion registers before the dropdown closes.
                setTimeout(() => {
                  this.autocompleteFocused = false;
                  m.redraw();
                }, 150);
              }}
              placeholder={app.translator.trans(translationPrefix + 'filterPlaceholder')}
            />
            {this.q ? (
              <Button
                className="Search-clear Button Button--icon Button--link"
                icon="fas fa-times-circle"
                aria-label={app.translator.trans(translationPrefix + 'filterClear')}
                onclick={() => this.clear()}
              />
            ) : null}
            {this.autocomplete(groups)}
          </div>
          <Button className="Button" onclick={() => this.refresh()}>
            {app.translator.trans(translationPrefix + 'filterApply')}
          </Button>
          <Button
            className="Button Button--icon AuditSearch-refresh"
            icon={this.loading ? 'fas fa-sync fa-spin' : 'fas fa-sync'}
            aria-label={app.translator.trans(translationPrefix + 'refresh')}
            title={extractText(app.translator.trans(translationPrefix + 'refresh'))}
            disabled={this.loading}
            onclick={() => this.refresh()}
          />
        </div>
        {this.filterHints()}
        {this.logs.length === 0 && !this.loading ? <Placeholder text={app.translator.trans(translationPrefix + 'empty')} /> : null}
        <div className="AuditList">
          {this.logs.map((log) => (
            <AuditItem
              log={log}
              changeQuery={(q: string) => {
                this.q = q;
                this.refresh();
              }}
            />
          ))}
        </div>
        <div className="AuditMore">{loading}</div>
      </div>
    );
  }

  /**
   * The whitespace-delimited token currently under the caret.
   */
  activeToken(): { start: number; end: number; text: string } {
    const start = this.q.lastIndexOf(' ', this.caret - 1) + 1;
    let end = this.q.indexOf(' ', this.caret);
    if (end === -1) end = this.q.length;

    return { start, end, text: this.q.slice(start, end) };
  }

  /**
   * Suggestions for the active token, when it's an `action:` or `client:` filter being typed.
   * Returns groups of values (by extension for actions, a single group for client), already
   * filtered by what the user has typed after the colon.
   */
  autocompleteGroups(): { extension: string | null; key: string; values: string[] }[] | null {
    if (!this.autocompleteFocused) return null;

    const { text } = this.activeToken();
    // Allow a leading "-" (negation) before the prefix.
    const match = text.match(/^-?(action|client):(.*)$/);
    if (!match) return null;

    const [, key, partial] = match;
    const needle = partial.toLowerCase();
    const groups: { extension: string | null; key: string; values: string[] }[] = [];

    if (key === 'action') {
      // Prefer the forum-serializer attribute (works in forum + admin), falling back to the
      // admin-only payload injected by Content\AdminPayload.
      const actions =
        (app.forum?.attribute('auditActions') as Record<string, string[]> | undefined) ||
        ((app.data as any)?.auditLogActions as Record<string, string[]> | undefined) ||
        {};
      Object.keys(actions).forEach((extension) => {
        const values = actions[extension].filter((a) => a.toLowerCase().includes(needle));
        if (values.length) groups.push({ extension, key, values });
      });
    } else {
      // client: values come from the registered filter metadata.
      const filters = (app.forum.attribute('auditFilters') as AuditFilter[] | undefined) || [];
      const clientFilter = filters.find((f) => f.key === 'client');
      const values = (clientFilter?.values || []).filter((v) => v.toLowerCase().includes(needle));
      if (values.length) groups.push({ extension: null, key, values });
    }

    return groups.length ? groups : null;
  }

  suggestions(): { key: string; value: string }[] {
    return (this.autocompleteGroups() || []).flatMap((group) => group.values.map((value) => ({ key: group.key, value })));
  }

  optionId(index: number) {
    return `${this.listId}-${index}`;
  }

  autocomplete(groups: ReturnType<AuditBrowser['autocompleteGroups']>) {
    if (!groups) return null;

    let index = 0;

    const options = (group: (typeof groups)[number]) =>
      group.values.map((value) => {
        const optionIndex = index++;
        const active = optionIndex === this.suggestionIndex;

        return (
          <li role="presentation">
            <button
              type="button"
              id={this.optionId(optionIndex)}
              role="option"
              aria-selected={active ? 'true' : 'false'}
              tabindex="-1"
              className={'AuditAutocomplete-item' + (active ? ' active' : '')}
              // Not mouseenter: scrolling the list with the arrow keys moves options under a resting pointer.
              onmousemove={() => (this.suggestionIndex = optionIndex)}
              onclick={() => this.applySuggestion(group.key, value)}
            >
              <code>{value}</code>
            </button>
          </li>
        );
      });

    return (
      <ul className="AuditAutocomplete" id={this.listId} role="listbox">
        {groups.map((group) => {
          if (!group.extension) return options(group);

          const headerId = `${this.listId}-${group.extension}`;

          return (
            <li role="group" aria-labelledby={headerId}>
              <div className="AuditAutocomplete-group" id={headerId}>
                {group.extension}
              </div>
              <ul className="AuditAutocomplete-options" role="presentation">
                {options(group)}
              </ul>
            </li>
          );
        })}
      </ul>
    );
  }

  moveSuggestion(delta: number) {
    const count = this.suggestions().length;
    if (!count) return;

    this.suggestionIndex = this.suggestionIndex < 0 && delta < 0 ? count - 1 : (this.suggestionIndex + delta + count) % count;

    m.redraw.sync();
    this.element.querySelector('.AuditAutocomplete-item.active')?.scrollIntoView({ block: 'nearest' });
  }

  select() {
    const suggestion = this.suggestions()[this.suggestionIndex];

    if (suggestion) {
      this.applySuggestion(suggestion.key, suggestion.value);
      return;
    }

    this.autocompleteFocused = false;
    this.refresh();
  }

  cancel() {
    if (this.autocompleteGroups()) {
      this.autocompleteFocused = false;
      this.suggestionIndex = -1;
      return;
    }

    this.clear();
  }

  /**
   * Moving to another token drops the highlight, which belonged to the old token's suggestions.
   */
  setCaret(caret: number) {
    const token = this.activeToken().start;
    this.caret = caret;

    if (this.activeToken().start !== token) this.suggestionIndex = -1;
  }

  clear() {
    this.q = '';
    this.caret = 0;
    this.suggestionIndex = -1;
    this.refresh();
  }

  applySuggestion(key: string, value: string) {
    const { start, end, text } = this.activeToken();
    const negate = text.startsWith('-') ? '-' : '';
    const completed = `${negate}${key}:${value}`;

    this.q = this.q.slice(0, start) + completed + this.q.slice(end);
    this.caret = start + completed.length;
    this.suggestionIndex = -1;
    this.autocompleteFocused = true;
    // Write the completed value first: setting it afterwards would move the caret to the end.
    m.redraw.sync();

    const input = this.element.querySelector<HTMLInputElement>('.AuditSearch .FormControl');
    if (input) {
      input.focus();
      input.setSelectionRange(this.caret, this.caret);
    }
  }

  filterHints() {
    const filters = (app.forum.attribute('auditFilters') as AuditFilter[] | undefined) || [];

    if (!filters.length) {
      return null;
    }

    return (
      <div className="AuditFilters">
        <div className="AuditFilters-quick">
          <span className="AuditFilters-label">{app.translator.trans(translationPrefix + 'filtersHint')}</span>
          {filters.map((filter) => this.filterChip(filter))}
          <Button
            className="Button Button--text AuditFilters-toggle"
            icon={this.showHelp ? 'fas fa-caret-down' : 'fas fa-caret-right'}
            onclick={() => (this.showHelp = !this.showHelp)}
          >
            {app.translator.trans(translationPrefix + 'help.toggle')}
          </Button>
        </div>
        {this.showHelp ? this.filterHelp(filters) : null}
      </div>
    );
  }

  filterHelp(filters: AuditFilter[]) {
    return (
      <div className="AuditFilters-help">
        <dl className="AuditFilters-list">
          {filters.map((filter) => (
            <>
              <dt>{this.filterChip(filter)}</dt>
              <dd>
                {filter.description ? app.translator.trans(filter.description) : null}
                {filter.values.length ? (
                  <span className="AuditFilters-values">
                    {filter.values.map((value) => (
                      <Button className="Button AuditFilters-filter" onclick={() => this.applyExample(filter.key + ':' + value)}>
                        <code>{value}</code>
                      </Button>
                    ))}
                  </span>
                ) : null}
              </dd>
            </>
          ))}
        </dl>
        <ul className="AuditFilters-syntax">
          <li>{app.translator.trans(translationPrefix + 'help.multiple')}</li>
          <li>{app.translator.trans(translationPrefix + 'help.negate')}</li>
        </ul>
      </div>
    );
  }

  /**
   * Whether a filter offers value autocomplete (a known, enumerable value set).
   */
  filterHasAutocomplete(filter: AuditFilter): boolean {
    return filter.key === 'action' || filter.key === 'client';
  }

  /**
   * Render a quick-filter chip. Filters with autocomplete open the suggestion dropdown on
   * click; the rest apply their example query (or prime the input for free-text values).
   */
  filterChip(filter: AuditFilter) {
    const autocompletes = this.filterHasAutocomplete(filter);
    const label = autocompletes ? filter.key + ':' : filter.example;

    return (
      <Button
        className="Button AuditFilters-filter"
        icon={autocompletes ? 'fas fa-list-ul' : undefined}
        onclick={() => (autocompletes ? this.openAutocomplete(filter.key) : this.applyExample(filter.example))}
      >
        <code>{label}</code>
      </Button>
    );
  }

  /**
   * Prime the input with a filter prefix and open its value autocomplete.
   */
  openAutocomplete(key: string) {
    const prefix = key + ':';
    this.q = prefix;
    this.caret = prefix.length;
    this.suggestionIndex = -1;
    this.autocompleteFocused = true;

    const input = this.element.querySelector<HTMLInputElement>('.AuditSearch .FormControl');
    if (input) {
      input.focus();
      input.setSelectionRange(this.caret, this.caret);
    }
    m.redraw();
  }

  applyExample(example: string) {
    this.q = example;

    // A complete example (e.g. "actor:guest") searches immediately; a prefix-only example
    // (e.g. "user:") just primes the input and focuses it so the user can type the value.
    if (example.endsWith(':')) {
      const input = this.element.querySelector<HTMLInputElement>('.AuditSearch .FormControl');
      input?.focus();
      this.caret = example.length;
      this.autocompleteFocused = true;
      m.redraw();
    } else {
      this.refresh();
    }
  }

  requestParams(): any {
    const params: any = { filter: {} };

    let q = this.attrs.baseQ || '';

    if (this.q) {
      q += ' ' + this.q;
    }

    q = q.trim();

    if (!q) {
      return params;
    }

    // The audit browser uses a single free-text box with a 1.x-style `key:value` gambit
    // syntax (e.g. `action:extension.enabled actor:admin`). In 2.x the backend dispatches
    // discrete `filter[<key>]` params to the registered FilterInterface implementations —
    // it does NOT parse gambits out of `filter[q]` (which only feeds the no-op fulltext
    // filter). So we parse the recognised `key:value` tokens here into discrete filters,
    // mirroring the keys the backend advertises via `auditFilters`. Negation is `-key:value`.
    const knownKeys = ((app.forum.attribute('auditFilters') as AuditFilter[] | undefined) || []).map((f) => f.key);

    // Split on whitespace, but keep quoted segments (e.g. action:"a b") intact.
    const tokens = q.match(/(?:[^\s"]+|"[^"]*")+/g) || [];

    const leftover: string[] = [];

    for (const token of tokens) {
      const match = token.match(/^(-?)([a-zA-Z_]+):(.*)$/);

      if (match && knownKeys.includes(match[2])) {
        const negate = match[1] === '-';
        const key = (negate ? '-' : '') + match[2];
        // Strip surrounding quotes from the value.
        const value = match[3].replace(/^"(.*)"$/, '$1');

        // Repeated filters of the same key combine (the backend filters split on commas).
        params.filter[key] = params.filter[key] ? params.filter[key] + ',' + value : value;
      } else {
        leftover.push(token);
      }
    }

    if (leftover.length) {
      params.filter.q = leftover.join(' ');
    }

    return params;
  }

  refresh(clear = true) {
    if (clear) {
      this.loading = true;
      this.logs = [];
    }

    // Only the latest query's results are shown, whatever order the responses arrive in.
    const requestId = ++this.requestId;

    return this.loadResults(undefined, requestId).then(
      (results) => {
        if (requestId !== this.requestId) return;
        this.logs = [];
        this.parseResults(results);
      },
      () => {
        if (requestId !== this.requestId) return;
        this.loading = false;
        m.redraw();
      }
    );
  }

  loadResults(offset: number | undefined = undefined, requestId: number = this.requestId) {
    const params = this.requestParams();
    params.page = { offset };

    return app.store.find<AuditLog[]>('audit', params, undefined, {
      // Returning false falls back to core's error alert; a superseded request fails silently.
      errorHandler: () => (requestId === this.requestId ? false : undefined),
    });
  }

  loadMore() {
    this.loading = true;
    const requestId = this.requestId;

    this.loadResults(this.logs.length, requestId).then(
      (results) => {
        if (requestId === this.requestId) this.parseResults(results);
      },
      () => {
        if (requestId !== this.requestId) return;
        this.loading = false;
        m.redraw();
      }
    );
  }

  parseResults(results: ApiResponsePlural<AuditLog>) {
    [].push.apply(this.logs, results as any);

    this.loading = false;
    this.moreResults = !!results.payload.links?.next;

    m.redraw();

    return results;
  }
}
