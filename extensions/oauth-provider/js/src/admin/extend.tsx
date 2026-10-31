import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

import OAuthClient from './models/OAuthClient';
import OAuthClientsPage from './components/OAuthClientsPage';

export default [
  new Extend.Store() //
    .add('oauth-provider-clients', OAuthClient),

  new Extend.Admin() //
    .page(OAuthClientsPage)
    .tab(() => ({
      id: 'settings',
      icon: 'fas fa-sliders-h',
      label: app.translator.trans('flarum-oauth-provider.admin.tabs.settings'),
    }))
    .section(() => ({
      id: 'registration',
      tab: 'settings',
      icon: 'fas fa-id-card',
      label: app.translator.trans('flarum-oauth-provider.admin.sections.registration.label'),
      description: app.translator.trans('flarum-oauth-provider.admin.sections.registration.description'),
    }))
    .setting(() => ({
      setting: 'flarum-oauth-provider.allow_dynamic_registration',
      section: 'registration',
      type: 'boolean',
      label: app.translator.trans('flarum-oauth-provider.admin.settings.allow_dynamic_registration_label'),
      help: app.translator.trans('flarum-oauth-provider.admin.settings.allow_dynamic_registration_help'),
    })),
];
