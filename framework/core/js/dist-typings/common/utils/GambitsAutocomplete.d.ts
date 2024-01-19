/// <reference types="mithril" />
import { GambitType } from '../query/IGambit';
import type IGambit from '../query/IGambit';
import { type AutocompleteCheck } from '../utils/AutocompleteReader';
export default class GambitsAutocomplete {
    resource: string;
    jqueryInput: () => JQuery<HTMLInputElement>;
    onchange: (value: string) => void;
    afterSuggest: (value: string) => void;
    protected query: string;
    constructor(resource: string, jqueryInput: () => JQuery<HTMLInputElement>, onchange: (value: string) => void, afterSuggest: (value: string) => void);
    suggestions(query: string): JSX.Element[];
    specificGambitSuggestions(gambitKey: string, gambitQuery: string, uniqueGroups: string[], groupedGambits: IGambit<GambitType.Grouped>[], autocomplete: AutocompleteCheck): JSX.Element[] | null;
    gambitSuggestion(key: string, value: string | null, suggest: (negated?: boolean) => void): JSX.Element;
    suggest(text: string, fromTyped: string, start: number): void;
}
