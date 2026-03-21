import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class LockedGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
}
