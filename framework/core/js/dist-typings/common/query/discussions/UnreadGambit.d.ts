import { BooleanGambit } from '../IGambit';
export default class UnreadGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
    enabled(): boolean;
}
