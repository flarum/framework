import { KeyValueGambit } from '../IGambit';
export default class AuthorGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
}
