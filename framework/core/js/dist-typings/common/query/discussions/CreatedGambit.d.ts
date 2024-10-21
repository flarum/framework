import { KeyValueGambit } from '../IGambit';
export default class CreatedGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    valuePattern(): string;
    filterKey(): string;
}
