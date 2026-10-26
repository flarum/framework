import { KeyValueGambit } from '../IGambit';
export default class GroupGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
}
