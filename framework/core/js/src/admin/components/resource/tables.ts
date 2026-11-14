/**
 * The resource tables and their list states, loaded together as one async chunk: `ResourceListPage`,
 * `UserListPage` and `AccessTokenListPage` import this module dynamically and show a loading indicator
 * until it arrives. An extension reaches a table or list state class the same way, or extends one by its
 * module path, e.g. `extend('flarum/admin/components/resource/ResourceTable', 'columns', ...)`.
 */
import ResourceTable from './ResourceTable';
import UserListTable from '../UserListTable';
import AccessTokenTable from '../AccessTokenTable';
import ResourceListState from '../../../common/resource/ResourceListState';
import AccessTokenListState from '../../states/AccessTokenListState';

export { ResourceTable, UserListTable, AccessTokenTable, ResourceListState, AccessTokenListState };
