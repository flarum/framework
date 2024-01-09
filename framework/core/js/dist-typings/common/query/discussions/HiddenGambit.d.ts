import { BooleanGambit } from '../IGambit';
export default class HiddenGambit extends BooleanGambit {
    key(): string;
    filterKey(): string;
    enabled(): boolean;
}
