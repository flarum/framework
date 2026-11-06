import app from '../../admin/app';

import type Group from '../../common/models/Group';
import GroupsDefinition from '../resource/definitions/GroupsDefinition';
import ResourceListPage from './ResourceListPage';

/**
 * Admin page listing every group, with create, edit and (for all but the admin, guest and member
 * groups) delete. Reordering stays on the permissions page. Its definition is
 * `app.resources.get('groups')`.
 */
export default class GroupListPage extends ResourceListPage<GroupsDefinition> {
  createDefinition() {
    return app.resources.get<Group>('groups') as GroupsDefinition;
  }

  page() {
    return {
      className: 'GroupListPage',
      icon: 'fas fa-user-tag',
      title: app.translator.trans('core.admin.groups.title'),
      description: app.translator.trans('core.admin.groups.description'),
    };
  }
}
