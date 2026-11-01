import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .section(
      () => ({
        id: 'spam-filtering',
        icon: 'fas fa-shield-halved',
        label: app.translator.trans('flarum-akismet.admin.sections.spam_filtering.label'),
        description: app.translator.trans('flarum-akismet.admin.sections.spam_filtering.description'),
      }),
      10
    )
    .setting(() => ({
      setting: 'flarum-akismet.api_key',
      section: 'spam-filtering',
      type: 'text',
      label: app.translator.trans('flarum-akismet.admin.akismet_settings.api_key_label'),
    }))
    .setting(() => ({
      // https://blog.akismet.com/2014/04/23/theres-a-ninja-in-your-akismet/
      setting: 'flarum-akismet.delete_blatant_spam',
      section: 'spam-filtering',
      type: 'boolean',
      label: app.translator.trans('flarum-akismet.admin.akismet_settings.delete_blatant_spam_label'),
      help: app.translator.trans('flarum-akismet.admin.akismet_settings.delete_blatant_spam_help'),
    }))
    .permission(
      () => ({
        icon: 'fas fa-vote-yea',
        label: app.translator.trans('flarum-akismet.admin.permissions.bypass_akismet'),
        permission: 'bypassAkismet',
      }),
      'start'
    ),
];
