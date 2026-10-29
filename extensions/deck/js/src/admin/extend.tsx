import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .setting(
      () => ({
        setting: 'flarum-deck.max_columns',
        type: 'number',
        min: 1,
        max: 12,
        label: app.translator.trans('flarum-deck.admin.settings.max_columns_label'),
        help: app.translator.trans('flarum-deck.admin.settings.max_columns_help'),
      }),
      20
    )
    .setting(
      () => ({
        setting: 'flarum-deck.poll_interval',
        type: 'number',
        min: 0,
        label: app.translator.trans('flarum-deck.admin.settings.poll_interval_label'),
        help: app.translator.trans('flarum-deck.admin.settings.poll_interval_help'),
      }),
      10
    )
    .permission(
      () => ({
        icon: 'fas fa-table-columns',
        label: app.translator.trans('flarum-deck.admin.permissions.use_deck_label'),
        permission: 'deck.use',
        allowGuest: false,
      }),
      'view',
      40
    ),
];
