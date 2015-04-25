export default function(app) {
  if (app.preload.data) {
    app.store.pushPayload({data: app.preload.data});
  }
};
