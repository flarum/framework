import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('flarum-messages', () => {
  app.extensionData.for('flarum-messages').registerPermission(
    {
      icon: 'fas fa-envelope-open-text',
      label: app.translator.trans('flarum-messages.admin.permissions.send_messages'),
      permission: 'dialog.sendMessage',
      allowGuest: false,
    },
    'start',
    98
  );
});
