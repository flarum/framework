import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import { version } from '../common/cdn';

export default [
  new Extend.Admin()
    .section(
      () => ({
        id: 'images',
        icon: 'fas fa-face-smile',
        label: app.translator.trans('flarum-emoji.admin.sections.images.label'),
        description: app.translator.trans('flarum-emoji.admin.sections.images.description'),
      }),
      10
    )
    .setting(() => ({
      setting: 'flarum-emoji.cdn',
      section: 'images',
      type: 'text',
      label: app.translator.trans('flarum-emoji.admin.settings.cdn_label'),
      help: app.translator.trans('flarum-emoji.admin.settings.cdn_help', {
        version: version,
      }),
    })),
];
