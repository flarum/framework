import IGambit from '../IGambit';
export default class HiddenGambit implements IGambit {
    pattern(): string;
    toFilter(_matches: string[], negate: boolean): Record<string, any>;
    filterKey(): string;
    fromFilter(value: string, negate: boolean): string;
}
