import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'flarum-flags.guidelines_url',
        type: 'text',
        label: app.translator.trans('flarum-flags.admin.settings.guidelines_url_label'),
      }),
      15
    )
    .setting(() => ({
      setting: 'flarum-flags.can_flag_own',
      type: 'boolean',
      label: app.translator.trans('flarum-flags.admin.settings.flag_own_posts_label'),
    }))
    .permission(
      () => ({
        icon: 'fas fa-flag',
        label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
        permission: 'discussion.viewFlags',
      }),
      'moderate',
      65
    )
    .permission(
      () => ({
        icon: 'fas fa-flag',
        label: app.translator.trans('flarum-flags.admin.permissions.flag_posts_label'),
        permission: 'discussion.flagPosts',
      }),
      'reply',
      65
    ),
];
