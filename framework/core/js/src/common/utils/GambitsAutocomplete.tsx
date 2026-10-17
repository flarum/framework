import app from '../app';
import { GambitType, type GroupedGambitSuggestion, type KeyValueGambitSuggestion, type GambitValueOption } from '../query/IGambit';
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

    const cursorPosition = this.jqueryInput()?.prop('selectionStart') ?? query.length;
    const lastChunk = query.slice(0, cursorPosition);
    const autocomplete = autocompleteReader.check(lastChunk, cursorPosition, /\S+$/);

    let typed = autocomplete?.typed || '';

    // Negative gambits are a thing ;)
    const negative = typed.startsWith('-');
    if (negative) {
      typed = typed.slice(1);
    }

    const colonIndex = typed.indexOf(':');

    // If the query contains a colon, we are typing or exploring specific values from that group.
    if (colonIndex !== -1) {
      const gambitKey = typed.slice(0, colonIndex).toLowerCase();
      const gambitQuery = typed.slice(colonIndex + 1).toLowerCase();

      const specificGambitSuggestions = this.specificGambitSuggestions(
        gambitKey,
        gambitQuery,
        uniqueGroups,
        groupedGambits,
        keyValueGambits,
        autocomplete!,
        negative
      );

      if (specificGambitSuggestions) {
        return specificGambitSuggestions;
      }

      // If the user typed a colon for an unknown group or a non-grouped gambit,
      // do not show unrelated top-level gambits.
      return [];
    }

    const normalizedTyped = typed.toLowerCase();

    // This is all the gambit suggestions.
    return [...instancePerGroup, ...keyValueGambits]
      .filter((gambit) => {
        if (!normalizedTyped) return true;

        const key = (
          gambit.type === GambitType.Grouped ? (gambit.suggestion() as GroupedGambitSuggestion).group : (gambit.suggestion().key as string)
        ).toLowerCase();

        return key.includes(normalizedTyped);
      })
      .map((gambit) => {
        const suggestion = gambit.suggestion();
        const key = gambit.type === GambitType.Grouped ? (suggestion as GroupedGambitSuggestion).group : (suggestion.key as string);
        const hint =
          gambit.type === GambitType.Grouped ? (suggestion as KeyValueGambitSuggestion).key : (suggestion as KeyValueGambitSuggestion).hint;

        return this.gambitSuggestion(key, hint, (negated: boolean | undefined) => {
          const isNeg = typeof negated === 'boolean' ? negated : negative;
          const fromTyped = autocomplete?.typed ?? (negative ? '-' : '') + typed;
          const start = autocomplete?.relativeStart ?? cursorPosition;

          this.suggest((isNeg ? '-' : '') + key + ':', fromTyped, start);
        });
      });
  }

  specificGambitSuggestions(
    gambitKey: string,
    gambitQuery: string,
    uniqueGroups: string[],
    groupedGambits: IGambit<GambitType.Grouped>[],
    keyValueGambits: IGambit<GambitType.KeyValue>[],
    autocomplete: AutocompleteCheck,
    negative: boolean = false
  ): JSX.Element[] | null {
    const matchingGroup = uniqueGroups.find((g) => g.toLowerCase() === gambitKey.toLowerCase());

    if (matchingGroup) {
      const prefix = (negative ? '-' : '') + matchingGroup + ':';

      const matchingKeys = groupedGambits
        .filter((gambit) => gambit.suggestion().group.toLowerCase() === matchingGroup.toLowerCase())
        .flatMap((gambit): string[] =>
          gambit.suggestion().key instanceof Array ? (gambit.suggestion().key as string[]) : [gambit.suggestion().key as string]
        )
        .filter((key) => !gambitQuery || key.toLowerCase().startsWith(gambitQuery));

      if (matchingKeys.length === 1 && matchingKeys[0].toLowerCase() === gambitQuery.toLowerCase()) {
        return [];
      }

      return matchingKeys.map((gambit) =>
        this.gambitSuggestion(gambit, null, () => this.suggest(prefix + gambit, autocomplete.typed, autocomplete.relativeStart))
      );
    }

    const matchingKeyValueGambit = keyValueGambits.find((gambit) => {
      const suggestion = gambit.suggestion();
      const key = typeof suggestion.key === 'string' ? suggestion.key : '';
      return key.toLowerCase() === gambitKey;
    });

    if (matchingKeyValueGambit) {
      const suggestion = matchingKeyValueGambit.suggestion();
      const rawValues =
        typeof suggestion.values === 'function'
          ? (suggestion.values as () => (string | GambitValueOption)[])()
          : suggestion.values ?? (typeof (matchingKeyValueGambit as any).values === 'function' ? (matchingKeyValueGambit as any).values() : []);

      if (Array.isArray(rawValues) && rawValues.length > 0) {
        const options: GambitValueOption[] = rawValues.map((opt) => (typeof opt === 'string' ? { value: opt } : opt));

        let prefixValues = '';
        let activeTerm = gambitQuery;

        if (matchingKeyValueGambit.predicates && gambitQuery.includes(',')) {
          const lastCommaIndex = gambitQuery.lastIndexOf(',');
          prefixValues = gambitQuery.slice(0, lastCommaIndex + 1);
          activeTerm = gambitQuery.slice(lastCommaIndex + 1);
        }

        const matchingOptions = options.filter(
          (opt) => !activeTerm || opt.value.toLowerCase().startsWith(activeTerm) || (opt.label && opt.label.toLowerCase().includes(activeTerm))
        );

        if (matchingOptions.length === 1 && matchingOptions[0].value.toLowerCase() === activeTerm) {
          return [];
        }

        const prefix = (negative ? '-' : '') + (suggestion.key as string) + ':' + prefixValues;

        return matchingOptions.map((opt) =>
          this.gambitSuggestion(
            opt.value,
            opt.label ?? null,
            () => this.suggest(prefix + opt.value, autocomplete.typed, autocomplete.relativeStart),
            false
          )
        );
      }
    }

    return null;
  }

  gambitSuggestion(key: string, value: string | null, suggest: (negated?: boolean) => void, showColon: boolean = true): JSX.Element {
    return (
      <li>
        <span className="Dropdown-item GambitsAutocomplete-gambit">
          <button type="button" className="Button--ua-reset" onclick={() => suggest()}>
            <span className="GambitsAutocomplete-gambit-key">
              {key}
              {!!value && showColon && ':'}
            </span>
            {!!value && <span className="GambitsAutocomplete-gambit-value">{value}</span>}
          </button>
          {!!value && showColon && (
            <span className="GambitsAutocomplete-gambit-actions">
              <Button
                class="Button Button--icon"
                onclick={() => suggest(false)}
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
    const rest = query.slice(start);
    const tokenMatch = rest.match(/^\S+/);
    const replaceLength = tokenMatch && tokenMatch[0].toLowerCase().startsWith(fromTyped.toLowerCase()) ? tokenMatch[0].length : fromTyped.length;
    const replaced = query.slice(0, start) + text + query.slice(start + replaceLength);

    this.onchange(replaced);
    if ($input && $input[0]) {
      $input[0].focus();
      setTimeout(() => {
        if ($input[0]) {
          try {
            $input[0].setSelectionRange(start + text.length, start + text.length);
          } catch (e) {}
          m.redraw();
        }
      }, 50);
    }

    this.afterSuggest(replaced);
  }
}
