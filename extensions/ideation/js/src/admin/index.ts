import app from 'flarum/admin/app';
import { rememberSavedSecrets } from './extend';

/**
 * Admin entry: the settings and the three permissions are declared through
 * the `extend` export (in Flarum 2 an initializer runs before
 * `app.extensionData` exists); the initializer only extends the settings page.
 */
export { default as extend } from './extend';

app.initializers.add('flarum-ideation', () => {
  rememberSavedSecrets();
});
