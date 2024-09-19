import { KeyValueGambit } from '../IGambit';
export default class EmailGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
    enabled(): boolean;
}
