import Session from 'flarum/session';

export default function(app) {
  app.session = new Session();
}
