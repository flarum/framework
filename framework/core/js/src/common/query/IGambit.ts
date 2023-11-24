export default interface IGambit<Type extends GambitType = GambitType> {
  type: GambitType;
  pattern(): string;
  toFilter(matches: string[], negate: boolean): Record<string, any>;
  filterKey(): string;
  fromFilter(value: string, negate: boolean): string;
  suggestion(): Type extends GambitType.KeyValue ? KeyValueGambitSuggestion : GroupedGambitSuggestion;
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
  group: 'is' | 'has' | string;
  key: string | string[];
};
