import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

import OAuthClient from './models/OAuthClient';
import OAuthClientsPage from './components/OAuthClientsPage';
import ConnectedGambit from './query/users/ConnectedGambit';

export default [
  new Extend.Store() //
    .add('oauth-provider-clients', OAuthClient),

  new Extend.Search() //
    .gambit('users', ConnectedGambit),

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
      setting: 'flarum-oauth-provider.allow_self_registered_clients',
      section: 'registration',
      type: 'boolean',
      label: app.translator.trans('flarum-oauth-provider.admin.settings.allow_self_registered_clients_label'),
      help: app.translator.trans('flarum-oauth-provider.admin.settings.allow_self_registered_clients_help'),
    }))
    .setting(() => ({
      setting: 'flarum-oauth-provider.allow_admin_clients',
      section: 'registration',
      type: 'boolean',
      label: app.translator.trans('flarum-oauth-provider.admin.settings.allow_admin_clients_label'),
      help: app.translator.trans('flarum-oauth-provider.admin.settings.allow_admin_clients_help'),
    })),
];
