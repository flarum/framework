import { KeyValueGambit } from 'flarum/common/query/IGambit';
/**
 * `connected:<client id>`: the members who have connected an application.
 */
export default class ConnectedGambit extends KeyValueGambit {
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
}
