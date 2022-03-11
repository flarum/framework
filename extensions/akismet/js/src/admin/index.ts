import app from 'flarum/admin/app';

app.initializers.add('flarum-akismet', () => {
  app.extensionData
    .for('flarum-akismet')
    .registerSetting({
      setting: 'flarum-akismet.api_key',
      type: 'text',
      label: app.translator.trans('flarum-akismet.admin.akismet_settings.api_key_label'),
    })
    .registerSetting({
      //https://blog.akismet.com/2014/04/23/theres-a-ninja-in-your-akismet/
      setting: 'flarum-akismet.delete_blatant_spam',
      type: 'boolean',
      label: app.translator.trans('flarum-akismet.admin.akismet_settings.delete_blatant_spam_label'),
      help: app.translator.trans('flarum-akismet.admin.akismet_settings.delete_blatant_spam_help'),
    })
    .registerPermission(
      {
        icon: 'fas fa-vote-yea',
        label: app.translator.trans('flarum-akismet.admin.permissions.bypass_akismet'),
        permission: 'bypassAkismet',
      },
      'start'
    );
});
