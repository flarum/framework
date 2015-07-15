import Session from 'flarum/Session';

/**
 * The `session` initializer creates the application session and preloads it
 * with data that has been set on the application's `preload` property.
 *
 * `app.preload.session` should be the same as the response from the /api/token
 * endpoint: it should contain `token` and `userId` keys.
 *
 * @param {App} app
 */
export default function session(app) {
  app.session = new Session(
    app.preload.session.token,
    app.store.getById('users', app.preload.session.userId)
  );
}
