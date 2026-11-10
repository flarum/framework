import sortable from 'sortablejs';

/**
 * Re-exports sortablejs from a local module so it can be lazy-loaded via Flarum's
 * chunk system. Dynamically importing a node_modules package directly does not get
 * a correctly-namespaced chunk URL (the chunk-name loader only rewrites `src/`-relative
 * and `flarum:`/`ext:` imports); importing this local wrapper does.
 *
 * Extensions can reuse this chunk by lazy-importing `flarum/admin/utils/loadSortable`
 * rather than bundling their own copy of sortablejs.
 */
export default sortable;
