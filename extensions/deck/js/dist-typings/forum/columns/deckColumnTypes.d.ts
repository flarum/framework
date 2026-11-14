import ItemList from 'flarum/common/utils/ItemList';
import type { DeckColumnType } from './DeckColumnType';
/**
 * Every kind of column a member can add, keyed by the `type` stored in their
 * layout. Extensions register theirs with the DeckColumns extender.
 */
declare const deckColumnTypes: ItemList<DeckColumnType>;
export default deckColumnTypes;
/**
 * A filter column's query as API filters. Only gambits are allowed: free text
 * would be a full-text search re-run on every refresh, so it's rejected when
 * the column is added, and dropped here.
 */
export declare const gambitFilters: (query: string) => Record<string, unknown>;
export declare function registerDefaultColumnTypes(): void;
