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
    .permission(
      () => ({
        icon: 'fas fa-keyboard',
        label: app.translator.trans('flarum-realtime.admin.permission.view-all-typing'),
        permission: 'flarum-realtime.view-all-typing',
        allowGuest: false,
      }),
      'moderate'
    )
    .section(
      () => ({
        id: 'typing',
        icon: 'fas fa-keyboard',
        label: app.translator.trans('flarum-realtime.admin.sections.typing.label'),
        description: app.translator.trans('flarum-realtime.admin.sections.typing.description'),
      }),
      30
    )
    .section(
      () => ({
        id: 'discussion-list',
        icon: 'fas fa-list',
        label: app.translator.trans('flarum-realtime.admin.sections.discussion-list.label'),
        description: app.translator.trans('flarum-realtime.admin.sections.discussion-list.description'),
      }),
      20
    )
    .section(
      () => ({
        id: 'notifications',
        icon: 'fas fa-bell',
        label: app.translator.trans('flarum-realtime.admin.sections.notifications.label'),
        description: app.translator.trans('flarum-realtime.admin.sections.notifications.description'),
      }),
      10
    )
    .setting(() => ({
      setting: 'flarum-realtime.typing-indicator',
      section: 'typing',
      label: app.translator.trans('flarum-realtime.admin.settings.typing-indicator'),
      help: app.translator.trans('flarum-realtime.admin.settings.typing-indicator-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.index-typing-indicator',
      section: 'typing',
      label: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator'),
      help: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.index-typing-indicator-restricted',
      section: 'typing',
      label: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-restricted'),
      help: app.translator.trans('flarum-realtime.admin.settings.index-typing-indicator-restricted-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.release-discussion-updates',
      section: 'discussion-list',
      label: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates'),
      help: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.release-discussion-updates-interval',
      section: 'discussion-list',
      label: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-interval'),
      help: app.translator.trans('flarum-realtime.admin.settings.release-discussion-updates-interval-help'),
      type: 'number',
    }))
    .setting(() => ({
      setting: 'flarum-realtime.notification-toast-dismiss-after',
      section: 'notifications',
      label: app.translator.trans('flarum-realtime.admin.settings.notification-toast-dismiss-after'),
      help: app.translator.trans('flarum-realtime.admin.settings.notification-toast-dismiss-after-help'),
      type: 'number',
      min: 0,
    })),
];
