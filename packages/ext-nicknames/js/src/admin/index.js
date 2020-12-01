app.initializers.add('flarum/nicknames', () => {
  app.extensionData
    .for('flarum-nicknames')
    .registerSetting({
      setting: 'flarum-nicknames.unique',
      type: 'boolean',
      label: app.translator.trans('flarum-nicknames.admin.settings.unique_label')
    })
    .registerSetting({
      setting: 'flarum-nicknames.regex',
      type: 'text',
      label: app.translator.trans('flarum-nicknames.admin.settings.regex_label')
    })
    .registerSetting({
      setting: 'flarum-nicknames.min',
      type: 'number',
      label: app.translator.trans('flarum-nicknames.admin.settings.min_label')
    })
    .registerSetting({
      setting: 'flarum-nicknames.max',
      type: 'number',
      label: app.translator.trans('flarum-nicknames.admin.settings.max_label')
    })
    .registerPermission({
      icon: 'fas fa-user-tag',
      label: app.translator.trans('flarum-nicknames.admin.permissions.edit_own_nickname_label'),
      permission: 'user.editOwnNickname'
    }, 'start')
});
