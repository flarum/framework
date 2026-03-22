import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class LikedByGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
