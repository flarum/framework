/**
 * The resource tables, loaded together as one async chunk: `ResourceListPage` and `UserListPage`
 * import this module dynamically and show a loading indicator until it arrives. An extension reaches
 * a table class the same way, or extends one by its module path, e.g.
 * `extend('flarum/admin/components/resource/ResourceTable', 'columns', ...)`.
 */
import ResourceTable from './ResourceTable';
import UserListTable from '../UserListTable';
import AccessTokenTable from '../AccessTokenTable';

export { ResourceTable, UserListTable, AccessTokenTable };
