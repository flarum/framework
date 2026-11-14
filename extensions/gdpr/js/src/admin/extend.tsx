import { default as extend } from '../common/extend';
import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import GdprPage from './components/GdprPage';
import DataType from './models/DataType';
import LinkButton from 'flarum/common/components/LinkButton';

export default [
  ...extend,

  new Extend.Routes() //
    .add('gdpr', '/gdpr', GdprPage),

  new Extend.Store() //
    .add('gdpr-datatypes', DataType),

  new Extend.Admin()
    .section(
      () => ({
        id: 'overview',
        icon: 'fas fa-user-shield',
        label: app.translator.trans('flarum-gdpr.admin.sections.overview.label'),
        description: app.translator.trans('flarum-gdpr.admin.sections.overview.description'),
      }),
      30
    )
    .section(
      () => ({
        id: 'erasure',
        icon: 'fas fa-user-minus',
        label: app.translator.trans('flarum-gdpr.admin.sections.erasure.label'),
        description: app.translator.trans('flarum-gdpr.admin.sections.erasure.description'),
      }),
      20
    )
    .section(
      () => ({
        id: 'anonymization',
        icon: 'fas fa-user-secret',
        label: app.translator.trans('flarum-gdpr.admin.sections.anonymization.label'),
        description: app.translator.trans('flarum-gdpr.admin.sections.anonymization.description'),
      }),
      10
    )
    .customSetting(
      () => (
        <div className="Form-group">
          <label>{app.translator.trans('flarum-gdpr.admin.settings.gdpr_page.title')}</label>
          <p className="helpText">{app.translator.trans('flarum-gdpr.admin.settings.gdpr_page.help_text')}</p>
          <LinkButton href={app.route('gdpr')} icon="fas fa-user-shield" className="Button">
            {app.translator.trans('flarum-gdpr.admin.nav.gdpr_button')}
          </LinkButton>
        </div>
      ),
      0,
      'overview'
    )
    .setting(() => ({
      setting: 'flarum-gdpr.allow-anonymization',
      section: 'erasure',
      label: app.translator.trans('flarum-gdpr.admin.settings.allow_anonymization'),
      help: app.translator.trans('flarum-gdpr.admin.settings.allow_anonymization_help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-gdpr.allow-deletion',
      section: 'erasure',
      label: app.translator.trans('flarum-gdpr.admin.settings.allow_deletion'),
      help: app.translator.trans('flarum-gdpr.admin.settings.allow_deletion_help'),
      type: 'boolean',
    }))
    .setting(() => ({
      setting: 'flarum-gdpr.default-erasure',
      section: 'erasure',
      label: app.translator.trans('flarum-gdpr.admin.settings.default_erasure'),
      help: app.translator.trans('flarum-gdpr.admin.settings.default_erasure_help'),
      type: 'select',
      options: {
        anonymization: app.translator.trans('flarum-gdpr.admin.settings.default_erasure_options.anonymization'),
        deletion: app.translator.trans('flarum-gdpr.admin.settings.default_erasure_options.deletion'),
      },
    }))
    .setting(() => ({
      setting: 'flarum-gdpr.default-anonymous-username',
      section: 'anonymization',
      type: 'text',
      label: app.translator.trans('flarum-gdpr.admin.settings.default_anonymous_username'),
      help: app.translator.trans('flarum-gdpr.admin.settings.default_anonymous_username_help'),
    }))
    .permission(
      () => ({
        icon: 'fas fa-user-minus',
        label: app.translator.trans('flarum-gdpr.admin.permissions.process_erasure'),
        permission: 'processErasure',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'fas fa-file-export',
        label: app.translator.trans('flarum-gdpr.admin.permissions.process_export_for_others'),
        permission: 'moderateExport',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'fas fa-eye',
        label: app.translator.trans('flarum-gdpr.admin.permissions.see_anonymized_user_badges'),
        permission: 'seeAnonymizedUserBadges',
        allowGuest: true,
      }),
      'view'
    ),
];
