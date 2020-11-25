import app from 'flarum/app';

app.initializers.add('flarum-akismet', () => {
  app.extensionData
    .for('flarum-akismet')
    .registerSetting({
      setting: 'flarum-akismet.api_key',
      type: 'text',
      label: app.translator.trans('flarum-akismet.admin.akismet_settings.api_key_label')
    });
});
