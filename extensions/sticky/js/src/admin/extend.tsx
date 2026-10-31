import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-thumbtack',
        label: app.translator.trans('flarum-sticky.admin.permissions.sticky_discussions_label'),
        permission: 'discussion.sticky',
      }),
      'moderate',
      95
    )
    .setting(
      () => ({
        setting: 'flarum-sticky.pin_sticky_on_all_discussions',
        name: 'pinStickyOnAllDiscussions',
        type: 'boolean',
        label: app.translator.trans('flarum-sticky.admin.settings.pin_sticky_on_all_discussions_label'),
        help: app.translator.trans('flarum-sticky.admin.settings.pin_sticky_on_all_discussions_help'),
      }),
      95
    )
    .setting(
      () => ({
        setting: 'flarum-sticky.only_sticky_unread_discussions',
        name: 'onlyStickyUnreadDiscussions',
        type: 'boolean',
        label: app.translator.trans('flarum-sticky.admin.settings.only_sticky_unread_discussions_label'),
        help: app.translator.trans('flarum-sticky.admin.settings.only_sticky_unread_discussions_help'),
        // Only meaningful when sticky pinning is enabled on the All Discussions page.
        disabled: app.data.settings['flarum-sticky.pin_sticky_on_all_discussions'] === '0',
      }),
      90
    )
    .setting(
      () => ({
        type: 'switch',
        setting: 'flarum-sticky.enable_display_excerpt',
        label: app.translator.trans('flarum-sticky.admin.settings.enable_display_excerpt'),
      }),
      100
    ),
];
