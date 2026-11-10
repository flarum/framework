import { KeyValueGambit } from '../IGambit';
export default class DiscussionGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
}
