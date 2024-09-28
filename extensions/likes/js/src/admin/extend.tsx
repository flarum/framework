import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'far fa-thumbs-up',
        label: app.translator.trans('flarum-likes.admin.permissions.like_posts_label'),
        permission: 'discussion.likePosts',
      }),
      'reply'
    )
    .setting(() => ({
      setting: 'flarum-likes.like_own_post',
      type: 'bool',
      label: app.translator.trans('flarum-likes.admin.settings.like_own_posts_label'),
      help: app.translator.trans('flarum-likes.admin.settings.like_own_posts_help'),
    })),
];
