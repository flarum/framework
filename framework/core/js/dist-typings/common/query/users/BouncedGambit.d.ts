import { BooleanGambit } from '../IGambit';
export default class BouncedGambit extends BooleanGambit {
    key(): string;
    canonicalKey(): string;
    filterKey(): string;
    enabled(): boolean;
}
