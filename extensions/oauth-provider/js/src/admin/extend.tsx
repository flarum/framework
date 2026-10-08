import Extend from 'flarum/common/extenders';

import OAuthClient from './models/OAuthClient';
import OAuthClientsPage from './components/OAuthClientsPage';

export default [
  new Extend.Store() //
    .add('oauth-provider-clients', OAuthClient),

  new Extend.Admin() //
    .page(OAuthClientsPage),
];
