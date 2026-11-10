import { BooleanGambit } from '../IGambit';
export default class HiddenGambit extends BooleanGambit {
    key(): string;
    canonicalKey(): string;
    filterKey(): string;
    enabled(): boolean;
}
