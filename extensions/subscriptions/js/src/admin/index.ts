import app from 'flarum/admin/app';

app.extensionData
  .for('flarum-subscriptions')
  .registerSetting({
    type: 'select',
    setting: 'flarum-subscriptions.notification_criteria',
    label: app.translator.trans('flarum-subscriptions.admin.settings.subscription_notification_criteria.label'),
    help: app.translator.trans('flarum-subscriptions.admin.settings.subscription_notification_criteria.help'),
    options: {
      all_new: app.translator.trans('flarum-subscriptions.admin.settings.subscription_notification_criteria.options.all_new'),
      first_new: app.translator.trans('flarum-subscriptions.admin.settings.subscription_notification_criteria.options.first_new'),
    },
  })
  .registerSetting({
    type: 'switch',
    setting: 'flarum-subscriptions.enforce_notification_criteria',
    label: app.translator.trans('flarum-subscriptions.admin.settings.enforce_subscription_notification_criteria.label'),
    help: app.translator.trans('flarum-subscriptions.admin.settings.enforce_subscription_notification_criteria.help'),
  });
