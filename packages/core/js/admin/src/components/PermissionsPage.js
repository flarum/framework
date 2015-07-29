import Component from 'flarum/Component';
import Badge from 'flarum/components/Badge';
import Select from 'flarum/components/Select';
import Button from 'flarum/components/Button';
import Group from 'flarum/models/Group';
import icon from 'flarum/helpers/icon';
import ItemList from 'flarum/utils/ItemList';

export default class PermissionsPage extends Component {
  constructor(...args) {
    super(...args);

    this.groups = app.store.all('groups')
      .filter(group => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(Number(group.id())) === -1);

    this.permissions = this.permissionItems().toArray();
    this.scopes = this.scopeItems().toArray();
    this.scopeControls = this.scopeControlItems().toArray();
  }

  view() {
    const permissionCells = permission => {
      return this.scopes.map(scope => (
        <td>
          {scope.render(permission)}
        </td>
      ));
    };

    return (
      <div className="PermissionsPage">
        <div className="PermissionsPage-groups">
          <div className="container">
            {this.groups.map(group => (
              <button className="Button Group">
                {Badge.component({
                  className: 'Group-icon',
                  icon: group.icon(),
                  style: {backgroundColor: group.color()}
                })}
                <span className="Group-name">{group.namePlural()}</span>
              </button>
            ))}
            <button className="Button Group Group--add">
              {icon('plus', {className: 'Group-icon'})}
              <span className="Group-name">New Group</span>
            </button>
          </div>
        </div>

        <div className="PermissionsPage-permissions">
          <div className="container">
            <table className="PermissionGrid">
              <thead>
                <tr>
                  <td></td>
                  {this.scopes.map(scope => <th>{scope.label}</th>)}
                  <th>{this.scopeControls}</th>
                </tr>
              </thead>
              {this.permissions.map(section => (
                <tbody>
                  <tr className="PermissionGrid-section">
                    <th>{section.label}</th>
                    {permissionCells(section)}
                    <td/>
                  </tr>
                  {section.children.map(child => (
                    <tr className="PermissionGrid-child">
                      <th>{child.label}</th>
                      {permissionCells(child)}
                      <td/>
                    </tr>
                  ))}
                </tbody>
              ))}
            </table>
          </div>
        </div>
      </div>
    );
  }

  permissionItems() {
    const items = new ItemList();

    items.add('view', {
      label: 'View the forum',
      children: this.viewItems().toArray()
    });

    items.add('start', {
      label: 'Start discussions',
      children: this.startItems().toArray()
    });

    items.add('reply', {
      label: 'Reply to discussions',
      children: this.replyItems().toArray()
    });

    items.add('moderate', {
      label: 'Moderate',
      children: this.moderateItems().toArray()
    });

    return items;
  }

  viewItems() {
    const items = new ItemList();

    items.add('view', {
      label: 'View discussions',
      permission: 'forum.view',
      allowGuest: true
    });

    items.add('signUp', {
      label: 'Sign up',
      setting: Select.component({options: ['Open']})
    });

    return items;
  }

  startItems() {
    const items = new ItemList();

    items.add('start', {
      label: 'Start discussions',
      permission: 'forum.startDiscussion'
    });

    items.add('allowRenaming', {
      label: 'Allow renaming',
      setting: Select.component({options: ['Indefinitely']})
    });

    return items;
  }

  replyItems() {
    const items = new ItemList();

    items.add('reply', {
      label: 'Reply to discussions',
      permission: 'discussion.reply'
    });

    items.add('allowPostEditing', {
      label: 'Allow post editing',
      setting: Select.component({options: ['Indefinitely']})
    });

    return items;
  }

  moderateItems() {
    const items = new ItemList();

    items.add('editPosts', {
      label: 'Edit posts',
      permission: 'discussion.editPosts'
    });

    items.add('deletePosts', {
      label: 'Delete posts',
      permission: 'discussion.deletePosts'
    });

    items.add('renameDiscussions', {
      label: 'Rename discussions',
      permission: 'discussion.rename'
    });

    items.add('deleteDiscussions', {
      label: 'Delete discussions',
      permission: 'discussion.delete'
    });

    items.add('suspendUsers', {
      label: 'Suspend users',
      permission: 'user.suspend'
    });

    return items;
  }

  scopeItems() {
    const items = new ItemList();

    const groupBadge = id => {
      const group = app.store.getById('groups', id);

      return Badge.component({
        icon: group.icon(),
        style: {backgroundColor: group.color()},
        label: group.namePlural()
      });
    };

    const groupBadges = groupIds => {
      let content;

      if (groupIds.indexOf(String(Group.GUEST_ID)) !== -1) {
        content = 'Everyone';
      } else if (groupIds.indexOf(String(Group.MEMBER_ID)) !== -1) {
        content = 'Members';
      } else {
        content = [
          groupBadge(Group.ADMINISTRATOR_ID),
          groupIds.map(groupBadge)
        ];
      }

      return (
        <button className="Button Button--text">
          {content}
          {icon('sort', {className: 'GroupsButton-caret'})}
        </button>
      );
    };

    items.add('global', {
      label: 'Global',
      render: permission => {
        if (permission.setting) {
          return permission.setting;
        } else if (permission.permission) {
          const groupIds = app.forum.attribute('permissions')[permission.permission] || [];

          return groupBadges(groupIds);
        }

        return '';
      }
    });

    items.add('tag1', {
      label: 'Blog',
      render: permission => {
        if (permission.setting) {
          return '';
        } else if (permission.permission) {
          const groupIds = app.forum.attribute('permissions')[permission.permission] || [];

          return groupBadges(groupIds);
        }

        return '';
      }
    });

    return items;
  }

  scopeControlItems() {
    const items = new ItemList();

    items.add('addTag', Button.component({
      children: 'Restrict by Tag',
      icon: 'plus',
      className: 'Button Button--text'
    }))

    return items;
  }
}
