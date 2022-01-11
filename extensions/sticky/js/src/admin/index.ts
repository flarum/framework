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
});
