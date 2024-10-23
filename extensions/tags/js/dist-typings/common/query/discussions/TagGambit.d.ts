import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class TagGambit extends KeyValueGambit {
    predicates: boolean;
    key(): string;
    hint(): string;
    filterKey(): string;
    gambitValueToFilterValue(value: string): string[];
    fromFilter(value: any, negate: boolean): string;
    filterValueToGambitValue(value: string): string;
}
