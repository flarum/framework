import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class SuspendedGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
    enabled(): boolean;
}
