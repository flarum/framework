import app from 'flarum/admin/app';

app.initializers.add('flarum-mentions', function () {
  app.extensionData.for('flarum-mentions').registerSetting({
    setting: 'flarum-mentions.allow_username_format',
    type: 'boolean',
    label: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_label'),
    help: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_text'),
  });
});
