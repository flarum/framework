import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class MentionedGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
