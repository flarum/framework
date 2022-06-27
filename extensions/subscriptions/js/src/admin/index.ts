import app from 'flarum/admin/app';

app.extensionData.for('flarum-subscriptions').registerSetting({
  type: 'boolean',
  setting: 'flarum-subscriptions.dont_notify_unless_caught_up',
  label: app.translator.trans('flarum-subscriptions.admin.settings.dont_notify_unless_caught_up.label'),
  help: app.translator.trans('flarum-subscriptions.admin.settings.dont_notify_unless_caught_up.help'),
});
