export default function(app) {
  if (app.preload.data) {
    app.store.pushPayload({data: app.preload.data});
  }
  if (app.preload.session) {
    app.session.token(app.preload.session.token);
    app.session.user(app.store.getById('users', app.preload.session.userId));
  }
};
