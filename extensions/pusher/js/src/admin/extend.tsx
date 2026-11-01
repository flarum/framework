import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .section(
      () => ({
        id: 'connection',
        icon: 'fas fa-key',
        label: app.translator.trans('flarum-pusher.admin.sections.connection.label'),
        description: app.translator.trans('flarum-pusher.admin.sections.connection.description'),
      }),
      20
    )
    .section(
      () => ({
        id: 'server',
        icon: 'fas fa-server',
        label: app.translator.trans('flarum-pusher.admin.sections.server.label'),
        description: app.translator.trans('flarum-pusher.admin.sections.server.description'),
      }),
      10
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_id',
        section: 'connection',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_id_help'),
        type: 'text',
      }),
      40
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_key',
        section: 'connection',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_key_help'),
        type: 'text',
      }),
      30
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_secret',
        section: 'connection',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_secret_help'),
        type: 'text',
      }),
      20
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.app_cluster',
        section: 'connection',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.app_cluster_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.app_cluster_help'),
        type: 'text',
      }),
      10
    )
    .setting(
      () => ({
        setting: 'flarum-pusher.server_hostname',
        section: 'server',
        label: app.translator.trans('flarum-pusher.admin.pusher_settings.server_hostname_label'),
        help: app.translator.trans('flarum-pusher.admin.pusher_settings.server_hostname_help'),
        type: 'text',
      }),
      0
    ),
];
