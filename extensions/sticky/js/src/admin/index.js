import app from 'flarum/admin/app';

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

  app.extensionData.for('flarum-sticky')
    .registerSetting({
      setting: 'flarum-sticky.filter_read_from_stickied',
      name: 'filterReadFromStickied',
      type: 'boolean',
      label: app.translator.trans('flarum-sticky.admin.settings.filter_read_from_stickied_label'),
    });
});
