import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin()
    .section(
      () => ({
        id: 'format',
        icon: 'fas fa-at',
        label: app.translator.trans('flarum-mentions.admin.sections.format.label'),
        description: app.translator.trans('flarum-mentions.admin.sections.format.description'),
      }),
      10
    )
    .setting(() => ({
      setting: 'flarum-mentions.allow_username_format',
      section: 'format',
      type: 'boolean',
      label: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_label'),
      help: app.translator.trans('flarum-mentions.admin.settings.allow_username_format_text'),
    }))
    .permission(
      () => ({
        permission: 'mentionGroups',
        label: app.translator.trans('flarum-mentions.admin.permissions.mention_groups_label'),
        icon: 'fas fa-at',
      }),
      'start'
    ),
];
