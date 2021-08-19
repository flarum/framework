import app from '../../admin/app';
import GroupBadge from '../../common/components/GroupBadge';
import EditGroupModal from './EditGroupModal';
import Group from '../../common/models/Group';
import icon from '../../common/helpers/icon';
import PermissionGrid from './PermissionGrid';
import AdminPage from './AdminPage';

export default class PermissionsPage extends AdminPage {
  headerInfo() {
    return {
      className: 'PermissionsPage',
      icon: 'fas fa-key',
      title: app.translator.trans('core.admin.permissions.title'),
      description: app.translator.trans('core.admin.permissions.description'),
    };
  }

  content() {
    return [
      <div className="PermissionsPage-groups">
        {app.store
          .all('groups')
          .filter((group) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
          .map((group) => (
            <button className="Button Group" onclick={() => app.modal.show(EditGroupModal, { group })}>
              {GroupBadge.component({
                group,
                className: 'Group-icon',
                label: null,
              })}
              <span className="Group-name">{group.namePlural()}</span>
            </button>
          ))}
        <button className="Button Group Group--add" onclick={() => app.modal.show(EditGroupModal)}>
          {icon('fas fa-plus', { className: 'Group-icon' })}
          <span className="Group-name">{app.translator.trans('core.admin.permissions.new_group_button')}</span>
        </button>
      </div>,

      <div className="PermissionsPage-permissions">{PermissionGrid.component()}</div>,
    ];
  }
}
