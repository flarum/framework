import app from 'flarum/admin/app';

app.initializers.add('flarum-mentions', () => {
  app.extensionData
    .for('flarum-mentions')
    .registerSetting({
      setting: 'flarum-mentions.allow_username_format',
      type: 'boolean',
      label: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_label'),
      help: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_text'),
    })
    .registerPermission(
      {
        permission: 'mentionGroups',
        label: app.translator.trans('flarum-mentions.admin.permissions.mention_groups_label'),
        icon: 'fas fa-at',
      },
      'start'
    );
});
