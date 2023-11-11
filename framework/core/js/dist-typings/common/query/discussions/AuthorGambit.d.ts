import IGambit from '../IGambit';
export default class AuthorGambit implements IGambit {
    pattern(): string;
    toFilter(matches: string[], negate: boolean): Record<string, any>;
    filterKey(): string;
    fromFilter(value: string, negate: boolean): string;
}
