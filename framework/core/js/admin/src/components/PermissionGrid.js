import Component from 'flarum/Component';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import ConfigDropdown from 'flarum/components/ConfigDropdown';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';

export default class PermissionGrid extends Component {
  constructor(...args) {
    super(...args);

    this.permissions = this.permissionItems().toArray();
  }

  view() {
    const scopes = this.scopeItems().toArray();

    const permissionCells = permission => {
      return scopes.map(scope => (
        <td>
          {scope.render(permission)}
        </td>
      ));
    };

    return (
      <table className="PermissionGrid">
        <thead>
          <tr>
            <td></td>
            {scopes.map(scope => (
              <th>
                {scope.label}{' '}
                {scope.onremove ? Button.component({icon: 'times', className: 'Button Button--text PermissionGrid-removeScope', onclick: scope.onremove}) : ''}
              </th>
            ))}
            <th>{this.scopeControlItems().toArray()}</th>
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
      setting: () => ConfigDropdown.component({
        key: 'allow_sign_up',
        options: [
          {value: '1', label: 'Open'},
          {value: '0', label: 'Closed'}
        ]
      })
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
      setting: () => {
        const minutes = parseInt(app.config.allow_renaming, 10);

        return ConfigDropdown.component({
          defaultLabel: minutes ? `For ${minutes} minutes` : 'Indefinitely',
          key: 'allow_renaming',
          options: [
            {value: '-1', label: 'Indefinitely'},
            {value: '10', label: 'For 10 minutes'},
            {value: 'reply', label: 'Until next reply'}
          ]
        });
      }
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
      setting: () => {
        const minutes = parseInt(app.config.allow_post_editing, 10);

        return ConfigDropdown.component({
          defaultLabel: minutes ? `For ${minutes} minutes` : 'Indefinitely',
          key: 'allow_post_editing',
          options: [
            {value: '-1', label: 'Indefinitely'},
            {value: '10', label: 'For 10 minutes'},
            {value: 'reply', label: 'Until next reply'}
          ]
        });
      }
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

    return items;
  }

  scopeItems() {
    const items = new ItemList();

    items.add('global', {
      label: 'Global',
      render: item => {
        if (item.setting) {
          return item.setting();
        } else if (item.permission) {
          return PermissionDropdown.component(Object.assign({}, item));
        }

        return '';
      }
    });

    return items;
  }

  scopeControlItems() {
    return new ItemList();
  }
}
