import { BooleanGambit } from '../IGambit';
export default class UnreadGambit extends BooleanGambit {
    key(): string;
    canonicalKey(): string;
    filterKey(): string;
    enabled(): boolean;
}
