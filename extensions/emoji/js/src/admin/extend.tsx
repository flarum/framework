import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import { version } from '../common/cdn';

export default [
  new Extend.Admin().setting(() => ({
    setting: 'flarum-emoji.cdn',
    type: 'text',
    label: app.translator.trans('flarum-emoji.admin.settings.cdn_label'),
    help: app.translator.trans('flarum-emoji.admin.settings.cdn_help', {
      version: version,
    }),
  })),
];
