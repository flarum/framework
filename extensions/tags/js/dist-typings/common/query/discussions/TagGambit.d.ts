import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class TagGambit extends KeyValueGambit {
    predicates: boolean;
    static RETRY_COOLDOWN_MS: number;
    static loadingTags: boolean;
    static loadedTags: boolean;
    static lastLoadFailedAt: number;
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
    values(): {
        value: string;
        label: string;
    }[];
    gambitValueToFilterValue(value: string): string[];
    fromFilter(value: any, negate: boolean): string;
    filterValueToGambitValue(value: string): string;
}
