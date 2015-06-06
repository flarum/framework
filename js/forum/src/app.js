import App from 'flarum/utils/app';
import store from 'flarum/initializers/store';
import stateHelpers from 'flarum/initializers/state-helpers';
import discussionControls from 'flarum/initializers/discussion-controls';
import postControls from 'flarum/initializers/post-controls';
import preload from 'flarum/initializers/preload';
import session from 'flarum/initializers/session';
import routes from 'flarum/initializers/routes';
import components from 'flarum/initializers/components';
import timestamps from 'flarum/initializers/timestamps';
import boot from 'flarum/initializers/boot';

var app = new App();

app.initializers.add('store', store);
app.initializers.add('state-helpers', stateHelpers);
app.initializers.add('discussion-controls', discussionControls);
app.initializers.add('post-controls', postControls);
app.initializers.add('session', session);
app.initializers.add('routes', routes);
app.initializers.add('components', components);
app.initializers.add('timestamps', timestamps);
app.initializers.add('preload', preload, {last: true});
app.initializers.add('boot', boot, {last: true});

export default app;
