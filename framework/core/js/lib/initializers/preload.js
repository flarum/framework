export default function(app) {
  app.store.pushPayload({data: app.preload.data});
  app.forum = app.store.getById('forums', 1);

  if (app.preload.session) {
    app.session.token(app.preload.session.token);
    app.session.user(app.store.getById('users', app.preload.session.userId));
  }
}
