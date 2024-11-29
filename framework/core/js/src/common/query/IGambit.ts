import app from '../app';

export default interface IGambit<Type extends GambitType = GambitType> {
  type: GambitType;

  /**
   * This is the regular expression pattern that will be used to match the gambit.
   * The pattern language can be localized. for example, the pattern for the
   * author gambit is `author:(.+)` in English, but `auteur:(.+)` in
   * French.
   */
  pattern(): string;

  /**
   * This is the method to transform a gambit into a filter format.
   */
  toFilter(matches: string[], negate: boolean): Record<string, any>;

  /**
   * This is the server standardised filter key for this gambit.
   * The filter key must not be localized.
   */
  filterKey(): string;

  /**
   * This is the method to transform a filter into a gambit format.
   * The gambit format can be localized.
   */
  fromFilter(value: any, negate: boolean): string;

  /**
   * This returns information about how the gambit is structured for the UI.
   * Use localized values.
   */
  suggestion(): Type extends GambitType.KeyValue ? KeyValueGambitSuggestion : GroupedGambitSuggestion;

  /**
   * Whether this gambit can use logical operators.
   * For example, the tag gambit can be used as such:
   * `tag:foo,bar tag:baz` which translates to `(foo OR bar) AND baz`.
   *
   * The info allows generation of the correct filtering format, which would be
   * ```
   * {
   *   tag: [
   *     'foo,bar', // OR because of the comma.
   *     'baz', // AND because it's a separate item.
   *   ]
   * }
   * ```
   *
   * The backend filter must be able to handle this format.
   * Checkout the TagGambit and TagFilter classes for an example.
   */
  predicates: boolean;

  /**
   * Whether this gambit can be used by the actor.
   */
  enabled(): boolean;
}

export enum GambitType {
  KeyValue = 'key:value',
  Grouped = 'grouped',
}

export type KeyValueGambitSuggestion = {
  key: string;
  hint: string;
};

export type GroupedGambitSuggestion = {
  group: 'is' | 'has' | 'allows' | string;
  key: string | string[];
};

export abstract class BooleanGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;
  predicates = false;

  abstract key(): string | string[];
  abstract filterKey(): string;

  booleanKey(): 'is' | 'has' | 'allows' {
    return 'is';
  }

  groupKey(): string {
    return app.translator.trans('core.lib.gambits.group_keys.' + this.booleanKey(), {}, true);
  }

  pattern(): string {
    const is = this.groupKey();
    let key = this.key();

    if (Array.isArray(key)) {
      key = key.join('|');
    }

    return `${is}:(${key})`;
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + this.filterKey();

    return {
      [key]: true,
    };
  }

  fromFilter(value: string, negate: boolean): string {
    const is = this.groupKey();
    const key = this.key();

    return `${negate ? '-' : ''}${is}:${key}`;
  }

  suggestion() {
    return {
      group: this.groupKey(),
      key: this.key(),
    };
  }

  enabled(): boolean {
    return true;
  }
}

export abstract class KeyValueGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;
  predicates = false;

  abstract key(): string;
  abstract hint(): string;
  abstract filterKey(): string;

  valuePattern(): string {
    return '(.+)';
  }

  gambitValueToFilterValue(value: string): string | number | boolean | Array<any> {
    return value;
  }

  filterValueToGambitValue(value: any): string {
    return Array.isArray(value) ? value.join(',') : value.toString();
  }

  pattern(): string {
    const key = this.key();

    return `${key}:` + this.valuePattern();
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + this.filterKey();

    return {
      [key]: this.gambitValueToFilterValue(matches[1]),
    };
  }

  fromFilter(value: any, negate: boolean): string {
    return `${negate ? '-' : ''}${this.key()}:${this.filterValueToGambitValue(value)}`;
  }

  suggestion() {
    return {
      key: this.key(),
      hint: this.hint(),
    };
  }

  enabled(): boolean {
    return true;
  }
}
