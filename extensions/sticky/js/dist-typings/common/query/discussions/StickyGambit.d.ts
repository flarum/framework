import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class StickyGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
}
