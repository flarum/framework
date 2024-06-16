import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('flarum-sticky', () => {
  app.extensionData.for('flarum-sticky').registerPermission(
    {
      icon: 'fas fa-thumbtack',
      label: app.translator.trans('flarum-sticky.admin.permissions.sticky_discussions_label'),
      permission: 'discussion.sticky',
    },
    'moderate',
    95
  );

  app.extensionData.for('flarum-sticky').registerSetting(
    {
      setting: 'flarum-sticky.only_sticky_unread_discussions',
      name: 'onlyStickyUnreadDiscussions',
      type: 'boolean',
      label: app.translator.trans('flarum-sticky.admin.settings.only_sticky_unread_discussions_label'),
      help: app.translator.trans('flarum-sticky.admin.settings.only_sticky_unread_discussions_help'),
    }
  );
});
