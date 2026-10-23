import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class LikedByGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
}
