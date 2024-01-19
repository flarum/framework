import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('flarum-suspend', () => {
  app.extensionData.for('flarum-suspend').registerPermission(
    {
      icon: 'fas fa-ban',
      label: app.translator.trans('flarum-suspend.admin.permissions.suspend_users_label'),
      permission: 'user.suspend',
    },
    'moderate'
  );
});
