import app from '../app';
import { GambitType, type GroupedGambitSuggestion, type KeyValueGambitSuggestion } from '../query/IGambit';
import type IGambit from '../query/IGambit';
import AutocompleteReader, { type AutocompleteCheck } from '../utils/AutocompleteReader';
import Button from '../components/Button';

export default class GambitsAutocomplete {
  protected query = '';

  constructor(
    public resource: string,
    public jqueryInput: () => JQuery<HTMLInputElement>,
    public onchange: (value: string) => void,
    public afterSuggest: (value: string) => void
  ) {}

  suggestions(query: string): JSX.Element[] {
    const gambits = app.search.gambits.for(this.resource).filter((gambit) => gambit.enabled());
    this.query = query;

    // We group the boolean gambits together to produce an initial item of
    // is:unread,sticky,locked, etc.
    const groupedGambits: IGambit<GambitType.Grouped>[] = gambits.filter((gambit) => gambit.type === GambitType.Grouped);
    const keyValueGambits: IGambit<GambitType.KeyValue>[] = gambits.filter((gambit) => gambit.type !== GambitType.Grouped);

    const uniqueGroups: string[] = [];
    for (const gambit of groupedGambits) {
      if (uniqueGroups.includes(gambit.suggestion().group)) continue;
      uniqueGroups.push(gambit.suggestion().group);
    }

    const instancePerGroup: IGambit<GambitType.Grouped>[] = [];
    for (const group of uniqueGroups) {
      instancePerGroup.push({
        type: GambitType.Grouped,
        suggestion: () => ({
          group,
          key: groupedGambits
            .filter((gambit) => gambit.suggestion().group === group)
            .map((gambit) => {
              const key = gambit.suggestion().key;

              return key instanceof Array ? key.join(', ') : key;
            })
            .join(', '),
        }),
        pattern: () => '',
        filterKey: () => '',
        toFilter: () => [],
        fromFilter: () => '',
        predicates: false,
        enabled: () => true,
      });
    }

    const autocompleteReader = new AutocompleteReader(null);

    const cursorPosition = this.jqueryInput().prop('selectionStart') || query.length;
    const lastChunk = query.slice(0, cursorPosition);
    const autocomplete = autocompleteReader.check(lastChunk, cursorPosition, /\S+$/);

    let typed = autocomplete?.typed || '';

    // Negative gambits are a thing ;)
    const negative = typed.startsWith('-');
    if (negative) {
      typed = typed.slice(1);
    }

    // if the query ends with 'is:' we will only list keys from that group.
    if (typed.endsWith(':')) {
      const gambitKey = typed.replace(/:$/, '') || null;
      const gambitQuery = typed.split(':').pop() || '';

      if (gambitKey) {
        const specificGambitSuggestions = this.specificGambitSuggestions(gambitKey, gambitQuery, uniqueGroups, groupedGambits, autocomplete!);

        if (specificGambitSuggestions) {
          return specificGambitSuggestions;
        }
      }
    }

    // This is all the gambit suggestions.
    return [...instancePerGroup, ...keyValueGambits]
      .filter(
        (gambit) =>
          !autocomplete ||
          new RegExp(typed).test(
            gambit.type === GambitType.Grouped ? (gambit.suggestion() as GroupedGambitSuggestion).group : (gambit.suggestion().key as string)
          )
      )
      .map((gambit) => {
        const suggestion = gambit.suggestion();
        const key = gambit.type === GambitType.Grouped ? (suggestion as GroupedGambitSuggestion).group : (suggestion.key as string);
        const hint =
          gambit.type === GambitType.Grouped ? (suggestion as KeyValueGambitSuggestion).key : (suggestion as KeyValueGambitSuggestion).hint;

        return this.gambitSuggestion(key, hint, (negated: boolean | undefined) =>
          this.suggest(((!!negated && '-') || '') + key + ':', typed || '', (autocomplete?.relativeStart ?? cursorPosition) + Number(negative))
        );
      });
  }

  specificGambitSuggestions(
    gambitKey: string,
    gambitQuery: string,
    uniqueGroups: string[],
    groupedGambits: IGambit<GambitType.Grouped>[],
    autocomplete: AutocompleteCheck
  ): JSX.Element[] | null {
    if (uniqueGroups.includes(gambitKey)) {
      return groupedGambits
        .filter((gambit) => gambit.suggestion().group === gambitKey)
        .flatMap((gambit): string[] =>
          gambit.suggestion().key instanceof Array ? (gambit.suggestion().key as string[]) : [gambit.suggestion().key as string]
        )
        .filter((key) => !gambitQuery || key.toLowerCase().startsWith(gambitQuery))
        .map((gambit) =>
          this.gambitSuggestion(gambit, null, () => this.suggest(gambit, gambitQuery, autocomplete.relativeStart + autocomplete.typed.length))
        );
    }

    return null;
  }

  gambitSuggestion(key: string, value: string | null, suggest: (negated?: boolean) => void): JSX.Element {
    return (
      <li>
        <span className="Dropdown-item GambitsAutocomplete-gambit">
          <button type="button" className="Button--ua-reset" onclick={() => suggest()}>
            <span className="GambitsAutocomplete-gambit-key">
              {key}
              {!!value && ':'}
            </span>
            {!!value && <span className="GambitsAutocomplete-gambit-value">{value}</span>}
          </button>
          {!!value && (
            <span className="GambitsAutocomplete-gambit-actions">
              <Button
                class="Button Button--icon"
                onclick={() => suggest()}
                icon="fas fa-plus"
                aria-label={app.translator.trans('core.lib.search.gambit_plus_button_a11y_label')}
              />
              <Button
                class="Button Button--icon"
                onclick={() => suggest(true)}
                icon="fas fa-minus"
                aria-label={app.translator.trans('core.lib.search.gambit_minus_button_a11y_label')}
              />
            </span>
          )}
        </span>
      </li>
    );
  }

  suggest(text: string, fromTyped: string, start: number) {
    const $input = this.jqueryInput();

    const query = this.query;
    const replaced = query.slice(0, start) + text + query.slice(start + fromTyped.length);

    this.onchange(replaced);
    $input[0].focus();
    setTimeout(() => {
      $input[0].setSelectionRange(start + text.length, start + text.length);
      m.redraw();
    }, 50);

    this.afterSuggest(replaced);
  }
}
