import { KeyValueGambit } from '../IGambit';
export default class AuthorGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
