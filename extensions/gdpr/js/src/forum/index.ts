import app from 'flarum/forum/app';
import ErasureRequestsListState from './states/ErasureRequestsListState';
import extendUserSettingsPage from './extenders/extendUserSettingsPage';
import extendHeaderSecondary from './extenders/extendHeaderSecondary';
import extendPage from './extenders/extendPage';
import extendUserControls from './extenders/extendUserControls';
import addAnonymousBadges from './extenders/addAnonymousBadges';

export { default as extend } from './extend';

app.initializers.add('flarum-gdpr', () => {
  app.erasureRequests = new ErasureRequestsListState();

  extendUserSettingsPage();
  extendHeaderSecondary();
  extendPage();
  extendUserControls();
  addAnonymousBadges();
});
