import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'flarum-pusher.app_id',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_help'),
        type: 'text',
      }),
      40
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_key',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_help'),
        type: 'text',
      }),
      30
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_secret',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_help'),
        type: 'text',
      }),
      20
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_cluster',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_cluster_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_cluster_help'),
        type: 'text',
      }),
      10
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.server_hostname',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.server_hostname_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.server_hostname_help'),
        type: 'text',
      }),
      0
    ),
];
