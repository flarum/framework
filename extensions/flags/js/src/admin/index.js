import app from 'flarum/app';

app.initializers.add('flarum-flags', () => {
  app.extensionData.for('flarum-flags')
    .registerSetting({
      setting: 'flarum-flags.guidelines_url',
      type: 'text',
      label: app.translator.trans('flarum-flags.admin.settings.guidelines_url_label')
    }, 15)
    .registerSetting({
      setting: 'flarum-flags.can_flag_own',
      type: 'boolean',
      label: app.translator.trans('flarum-flags.admin.settings.flag_own_posts_label')
    })
    .registerPermission({
        icon: 'fas fa-flag',
        label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
        permission: 'discussion.viewFlags'
      }, 'moderate', 65)

    .registerPermission({
        icon: 'fas fa-flag',
        label: app.translator.trans('flarum-flags.admin.permissions.flag_posts_label'),
        permission: 'discussion.flagPosts'
      }, 'reply', 65);
});
