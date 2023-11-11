import IGambit from 'flarum/common/query/IGambit';
export default class TagGambit implements IGambit {
    pattern(): string;
    toFilter(matches: string[], negate: boolean): Record<string, any>;
    filterKey(): string;
    fromFilter(value: string, negate: boolean): string;
}
