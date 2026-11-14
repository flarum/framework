import { KeyValueGambit } from '../IGambit';
export default class EmailGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
    enabled(): boolean;
}
