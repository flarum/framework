import app from 'flarum/admin/app';

app.initializers.add('flarum-sticky', () => {
  app.extensionData
    .for('flarum-sticky')
    .registerSetting({
      setting: 'flarum-sticky.pin_sticky_on_all_discussions',
      label: app.translator.trans('flarum-sticky.admin.settings.pin_sticky_on_all_discussions_label'),
      help: app.translator.trans('flarum-sticky.admin.settings.pin_sticky_on_all_discussions_help'),
      type: 'boolean',
    })
    .registerPermission(
      {
        icon: 'fas fa-thumbtack',
        label: app.translator.trans('flarum-sticky.admin.permissions.sticky_discussions_label'),
        permission: 'discussion.sticky',
      },
      'moderate',
      95
    );
});
