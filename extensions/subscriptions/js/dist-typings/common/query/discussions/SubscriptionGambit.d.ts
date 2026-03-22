import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class SubscriptionGambit extends BooleanGambit {
    key(): string[];
    toFilter(matches: string[], negate: boolean): Record<string, any>;
    filterKey(): string;
    fromFilter(value: string, negate: boolean): string;
    enabled(): boolean;
}
