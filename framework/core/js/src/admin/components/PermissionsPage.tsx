import app from '../../admin/app';
import GroupBadge from '../../common/components/GroupBadge';
import EditGroupModal from './EditGroupModal';
import Group from '../../common/models/Group';
import PermissionGrid from './PermissionGrid';
import AdminPage from './AdminPage';
import Icon from '../../common/components/Icon';
import SettingDropdown from './SettingDropdown';

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
    return (
      <>
        <div className="PermissionsPage-groups">
          {app.store
            .all<Group>('groups')
            .filter((group) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()!) === -1)
            .map((group) => (
              <button className="Button Group" type="button" onclick={() => app.modal.show(EditGroupModal, { group })}>
                <GroupBadge group={group} className="Group-icon" label={null} />
                <span className="Group-name">{group.namePlural()}</span>
              </button>
            ))}
          <button className="Button Group Group--add" type="button" onclick={() => app.modal.show(EditGroupModal)}>
            <Icon name="fas fa-plus" className="Group-icon" />
            <span className="Group-name">{app.translator.trans('core.admin.permissions.new_group_button')}</span>
          </button>
        </div>

        <div className="PermissionsPage-permissions">
          <PermissionGrid />
        </div>
      </>
    );
  }

  static register() {
    app.generalIndex.group('core-permissions', {
      label: app.translator.trans('core.admin.permissions.title', {}, true),
      icon: {
        name: 'fas fa-key',
      },
      link: app.route('permissions'),
    });

    app.registry.for('core-permissions');

    PermissionsPage.registerViewPermissions();
    PermissionsPage.registerStartPermissions();
    PermissionsPage.registerReplyPermissions();
    PermissionsPage.registerModeratePermissions();
  }

  static registerViewPermissions() {
    app.registry.registerPermission(
      {
        icon: 'fas fa-eye',
        label: app.translator.trans('core.admin.permissions.view_forum_label'),
        permission: 'viewForum',
        allowGuest: true,
      },
      'view',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-users',
        label: app.translator.trans('core.admin.permissions.view_hidden_groups_label'),
        permission: 'viewHiddenGroups',
      },
      'view',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-users',
        label: app.translator.trans('core.admin.permissions.search_users_label'),
        permission: 'searchUsers',
        allowGuest: true,
      },
      'view',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-user-plus',
        label: app.translator.trans('core.admin.permissions.sign_up_label'),
        id: 'allow_sign_up',
        setting: () => (
          <SettingDropdown
            key="allow_sign_up"
            options={[
              { value: '1', label: app.translator.trans('core.admin.permissions_controls.signup_open_button') },
              { value: '0', label: app.translator.trans('core.admin.permissions_controls.signup_closed_button') },
            ]}
            lazyDraw
          />
        ),
      },
      'view',
      90
    );

    app.registry.registerPermission(
      {
        icon: 'far fa-clock',
        label: app.translator.trans('core.admin.permissions.view_last_seen_at_label'),
        permission: 'user.viewLastSeenAt',
      },
      'view'
    );
  }

  static registerStartPermissions() {
    app.registry.registerPermission(
      {
        icon: 'fas fa-edit',
        label: app.translator.trans('core.admin.permissions.start_discussions_label'),
        permission: 'startDiscussion',
      },
      'start',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-i-cursor',
        label: app.translator.trans('core.admin.permissions.allow_renaming_label'),
        id: 'allow_renaming',
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_renaming, 10);

          return (
            <SettingDropdown
              defaultLabel={
                minutes
                  ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
                  : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button')
              }
              key="allow_renaming"
              options={[
                { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
                { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
                { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
              ]}
              lazyDraw
            />
          );
        },
      },
      'start',
      90
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-key',
        label: app.translator.trans('core.admin.permissions.create_access_token_label'),
        permission: 'createAccessToken',
      },
      'start',
      80
    );
  }

  static registerReplyPermissions() {
    app.registry.registerPermission(
      {
        icon: 'fas fa-reply',
        label: app.translator.trans('core.admin.permissions.reply_to_discussions_label'),
        permission: 'discussion.reply',
      },
      'reply',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('core.admin.permissions.allow_post_editing_label'),
        id: 'allow_post_editing',
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_post_editing, 10);

          return (
            <SettingDropdown
              defaultLabel={
                minutes
                  ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
                  : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button')
              }
              key="allow_post_editing"
              options={[
                { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
                { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
                { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
              ]}
            />
          );
        },
      },
      'reply',
      90
    );

    app.registry.registerPermission(
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('core.admin.permissions.allow_hide_own_posts_label'),
        id: 'allow_hide_own_posts',
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_hide_own_posts, 10);

          return SettingDropdown.component({
            defaultLabel: minutes
              ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
              : app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button'),
            key: 'allow_hide_own_posts',
            options: [
              { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
              { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
              { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
              { value: '0', label: app.translator.trans('core.admin.permissions_controls.allow_never_button') },
            ],
          });
        },
      },
      'reply',
      80
    );
  }

  static registerModeratePermissions() {
    app.registry.registerPermission(
      {
        icon: 'fas fa-bullseye',
        label: app.translator.trans('core.admin.permissions.view_post_ips_label'),
        permission: 'discussion.viewIpsPosts',
      },
      'moderate',
      110
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-i-cursor',
        label: app.translator.trans('core.admin.permissions.rename_discussions_label'),
        permission: 'discussion.rename',
      },
      'moderate',
      100
    );

    app.registry.registerPermission(
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('core.admin.permissions.delete_discussions_label'),
        permission: 'discussion.hide',
      },
      'moderate',
      90
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-times',
        label: app.translator.trans('core.admin.permissions.delete_discussions_forever_label'),
        permission: 'discussion.delete',
      },
      'moderate',
      80
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-swimmer',
        label: app.translator.trans('core.admin.permissions.post_without_throttle_label'),
        permission: 'postWithoutThrottle',
      },
      'moderate',
      70
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-pencil-alt',
        label: app.translator.trans('core.admin.permissions.edit_posts_label'),
        permission: 'discussion.editPosts',
      },
      'moderate',
      70
    );

    app.registry.registerPermission(
      {
        icon: 'far fa-trash-alt',
        label: app.translator.trans('core.admin.permissions.delete_posts_label'),
        permission: 'discussion.hidePosts',
      },
      'moderate',
      60
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-times',
        label: app.translator.trans('core.admin.permissions.delete_posts_forever_label'),
        permission: 'discussion.deletePosts',
      },
      'moderate',
      60
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-user-cog',
        label: app.translator.trans('core.admin.permissions.edit_users_credentials_label'),
        permission: 'user.editCredentials',
      },
      'moderate',
      60
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-users-cog',
        label: app.translator.trans('core.admin.permissions.edit_users_groups_label'),
        permission: 'user.editGroups',
      },
      'moderate',
      60
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-address-card',
        label: app.translator.trans('core.admin.permissions.edit_users_label'),
        permission: 'user.edit',
      },
      'moderate',
      60
    );

    app.registry.registerPermission(
      {
        icon: 'fas fa-key',
        label: app.translator.trans('core.admin.permissions.moderate_access_tokens_label'),
        permission: 'moderateAccessTokens',
      },
      'moderate',
      60
    );
  }
}
