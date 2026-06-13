import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-keyboard',
        label: app.translator.trans('flarum-realtime.admin.permission.view-who-types'),
        permission: 'discussion.flarum-realtime.view-who-types',
        allowGuest: true,
      }),
      'view'
    )
    .setting(() => ({
      setting: 'flarum-realtime.typing-indicator',
      label: app.translator.trans('flarum-realtime.admin.settings.typing-indicator'),
      help: app.translator.trans('flarum-realtime.admin.settings.typing-indicator-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.index-typing-indicator',
      label: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator'),
      help: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.index-typing-indicator-restricted',
      label: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-restricted'),
      help: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-restricted-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.release-discussion-updates',
      label: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates'),
      help: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.release-discussion-updates-interval',
      label: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-interval'),
      help: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-interval-help'),
      type: 'number',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.notification-toast-dismiss-after',
      label: app.translator.trans('flarum-realtime.admin.settings.notification-toast-dismiss-after'),
      help: app.translator.trans('flarum-realtime.admin.settings.notification-toast-dismiss-after-help'),
      type: 'number',
      min: 0,
    })),
];
