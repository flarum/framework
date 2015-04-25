import Session from 'flarum/session';

export default function(app) {
  app.session = new Session();

  if (app.preload.session) {
    app.session.token(app.preload.session.token);
    app.session.user(app.store.getById('users', app.preload.session.userId));
  }
}
