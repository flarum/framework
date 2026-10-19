import Sortable from 'sortablejs';

/**
 * Loaded on demand by DeckPage, so SortableJS is only fetched on the Deck page.
 * Same approach as core's admin `loadSortable`, which the forum can't use: a
 * local wrapper module gets a correctly namespaced chunk, where a dynamic
 * import of the package itself would not.
 *
 * (flarum-webpack-config rewrites dynamic import calls even inside comments,
 * so don't spell one out in this file's docs.)
 */
export default Sortable;
