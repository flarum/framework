import app from 'flarum/admin/app';

app.initializers.add('flarum-likes', () => {
  app.extensionData
    .for('flarum-likes')
    .registerPermission(
      {
        icon: 'far fa-thumbs-up',
        label: app.translator.trans('flarum-likes.admin.permissions.like_posts_label'),
        permission: 'discussion.likePosts',
      },
      'reply'
    )
    .registerSetting({
      setting: 'flarum-likes.like_own_post',
      type: 'bool',
      label: app.translator.trans('flarum-likes.admin.settings.like_own_posts_label'),
      help: app.translator.trans('flarum-likes.admin.settings.like_own_posts_help'),
    });
});
