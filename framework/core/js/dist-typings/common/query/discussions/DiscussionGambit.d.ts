import { KeyValueGambit } from '../IGambit';
export default class DiscussionGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
