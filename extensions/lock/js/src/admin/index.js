import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('flarum-lock', () => {
  app.extensionData.for('flarum-lock').registerPermission(
    {
      icon: 'fas fa-lock',
      label: app.translator.trans('flarum-lock.admin.permissions.lock_discussions_label'),
      permission: 'discussion.lock',
    },
    'moderate',
    95
  );
});
