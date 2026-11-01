import Extend from 'flarum/common/extenders';

import OAuthConsent from './models/OAuthConsent';

export default [
  new Extend.Store() //
    .add('oauth-consents', OAuthConsent),
];
