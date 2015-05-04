import App from 'flarum/utils/app';
import store from 'flarum/initializers/store';
import preload from 'flarum/initializers/preload';
import session from 'flarum/initializers/session';
import routes from 'flarum/initializers/routes';
import timestamps from 'flarum/initializers/timestamps';
import boot from 'flarum/initializers/boot';

var app = new App();

app.initializers.add('store', store);
app.initializers.add('session', session);
app.initializers.add('routes', routes);
app.initializers.add('timestamps', timestamps);
app.initializers.add('preload', preload, {last: true});
app.initializers.add('boot', boot, {last: true});

export default app;
