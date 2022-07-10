import app from 'flarum/admin/app';

app.extensionData.for('flarum-subscriptions').registerSetting({
  type: 'boolean',
  setting: 'flarum-subscriptions.notify_first_new_unread_post_only',
  label: app.translator.trans('flarum-subscriptions.admin.settings.notify_first_new_unread_post_only.label'),
  help: app.translator.trans('flarum-subscriptions.admin.settings.notify_first_new_unread_post_only.help'),
});
