import Component from 'flarum/Component';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import ConfigDropdown from 'flarum/components/ConfigDropdown';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';
import icon from 'flarum/helpers/icon';

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
                <th>{child.icon ? icon(child.icon) : ''}{child.label}</th>
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
      label: app.trans('core.admin.permissions_read_heading'),
      children: this.viewItems().toArray()
    }, 100);

    items.add('start', {
      label: app.trans('core.admin.permissions_create_heading'),
      children: this.startItems().toArray()
    }, 90);

    items.add('reply', {
      label: app.trans('core.admin.permissions_participate_heading'),
      children: this.replyItems().toArray()
    }, 80);

    items.add('moderate', {
      label: app.trans('core.admin.permissions_moderate_heading'),
      children: this.moderateItems().toArray()
    }, 70);

    return items;
  }

  viewItems() {
    const items = new ItemList();

    items.add('view', {
      icon: 'eye',
      label: app.trans('core.admin.permissions_view_discussions_label'),
      permission: 'forum.view',
      allowGuest: true
    }, 100);

    items.add('signUp', {
      icon: 'user-plus',
      label: app.trans('core.admin.permissions_sign_up_label'),
      setting: () => ConfigDropdown.component({
        key: 'allow_sign_up',
        options: [
          {value: '1', label: app.trans('core.admin.permissions_signup_open_button')},
          {value: '0', label: app.trans('core.admin.permissions_signup_closed_button')}
        ]
      })
    }, 90);

    return items;
  }

  startItems() {
    const items = new ItemList();

    items.add('start', {
      icon: 'edit',
      label: app.trans('core.admin.permissions_start_discussions_label'),
      permission: 'forum.startDiscussion'
    }, 100);

    items.add('allowRenaming', {
      icon: 'i-cursor',
      label: app.trans('core.admin.permissions_allow_renaming_label'),
      setting: () => {
        const minutes = parseInt(app.config.allow_renaming, 10);

        return ConfigDropdown.component({
          defaultLabel: minutes
            ? app.trans('core.admin.permissions_allow_some_minutes_button', {count: minutes})
            : app.trans('core.admin.permissions_allow_indefinitely_button'),
          key: 'allow_renaming',
          options: [
            {value: '-1', label: app.trans('core.admin.permissions_allow_indefinitely_button')},
            {value: '10', label: app.trans('core.admin.permissions_allow_ten_minutes_button')},
            {value: 'reply', label: app.trans('core.admin.permissions_allow_until_reply_button')}
          ]
        });
      }
    }, 90);

    return items;
  }

  replyItems() {
    const items = new ItemList();

    items.add('reply', {
      icon: 'reply',
      label: app.trans('core.admin.permissions_reply_to_discussions_label'),
      permission: 'discussion.reply'
    }, 100);

    items.add('allowPostEditing', {
      icon: 'pencil',
      label: app.trans('core.admin.permissions_allow_post_editing_label'),
      setting: () => {
        const minutes = parseInt(app.config.allow_post_editing, 10);

        return ConfigDropdown.component({
          defaultLabel: app.trans(minutes ? 'core.admin.permissions_allow_some_minutes_button', {some: ?{minutes}} : 'core.admin.permissions_allow_indefinitely_button'),
          key: 'allow_post_editing',
          options: [
            {value: '-1', label: app.trans('core.admin.permissions_allow_indefinitely_button')},
            {value: '10', label: app.trans('core.admin.permissions_allow_ten_minutes_button')},
            {value: 'reply', label: app.trans('core.admin.permissions_allow_until_reply_button')}
          ]
        });
      }
    }, 90);

    return items;
  }

  moderateItems() {
    const items = new ItemList();

    items.add('renameDiscussions', {
      icon: 'i-cursor',
      label: app.trans('core.admin.permissions_rename_discussions_label'),
      permission: 'discussion.rename'
    }, 100);

    items.add('hideDiscussions', {
      icon: 'trash-o',
      label: app.trans('core.admin.permissions_delete_discussions_label'),
      permission: 'discussion.hide'
    }, 90);

    items.add('deleteDiscussions', {
      icon: 'times',
      label: app.trans('core.admin.permissions_delete_discussions_forever_label'),
      permission: 'discussion.delete'
    }, 80);

    items.add('editPosts', {
      icon: 'pencil',
      label: app.trans('core.admin.permissions_edit_and_delete_posts_label'),
      permission: 'discussion.editPosts'
    }, 70);

    items.add('deletePosts', {
      icon: 'times',
      label: app.trans('core.admin.permissions_delete_posts_forever_label'),
      permission: 'discussion.deletePosts'
    }, 60);

    return items;
  }

  scopeItems() {
    const items = new ItemList();

    items.add('global', {
      label: app.trans('core.admin.permissions_global_heading'),
      render: item => {
        if (item.setting) {
          return item.setting();
        } else if (item.permission) {
          return PermissionDropdown.component({
            permission: item.permission,
            allowGuest: item.allowGuest
          });
        }

        return '';
      }
    }, 100);

    return items;
  }

  scopeControlItems() {
    return new ItemList();
  }
}
