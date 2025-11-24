import app from 'flarum/admin/app';
import extendUserListPage from './extendUserListPage';
import extendAdminNav from './extendAdminNav';

export { default as extend } from './extend';

app.initializers.add('flarum-gdpr', () => {
  app.registry.for('flarum-gdpr');

  extendUserListPage();
  extendAdminNav();
});
