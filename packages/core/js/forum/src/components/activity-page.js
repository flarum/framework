import UserPage from 'flarum/components/user-page';
import LoadingIndicator from 'flarum/components/loading-indicator';
import ActionButton from 'flarum/components/action-button';

export default class ActivityPage extends UserPage {
  /**

   */
  constructor(props) {
    super(props);

    this.user = m.prop();
    this.loading = m.prop(true);
    this.moreResults = m.prop(false);
    this.activity = m.prop([]);

    var username = m.route.param('username').toLowerCase();
    var users = app.store.all('users');
    for (var id in users) {
      if (users[id].username().toLowerCase() == username && users[id].joinTime()) {
        this.setupUser(users[id]);
        break;
      }
    }

    if (!this.user()) {
      app.store.find('users', username).then(this.setupUser.bind(this));
    }
  }

  setupUser(user) {
    m.startComputation();
    this.user(user);
    m.endComputation();

    this.refresh();
  }

  refresh() {
    m.startComputation();
    this.loading(true);
    this.activity([]);
    m.endComputation();
    this.loadResults().then(this.parseResults.bind(this));
  }

  loadResults(start) {
    return app.store.find('activity', {
      users: this.user().id(),
      start,
      type: this.props.filter
    })
  }

  loadMore() {
    var self = this;
    this.loading(true);
    this.loadResults(this.activity().length).then((results) => this.parseResults(results, true));
  }

  parseResults(results, append) {
    this.loading(false);
    [].push.apply(this.activity(), results);
    this.moreResults(!!results.length);
    m.redraw();
    return results;
  }

  content() {
    return m('div.user-activity', [
      m('ul.activity-list', this.activity().map(activity => {
        var ActivityComponent = app.activityComponentRegistry[activity.contentType()];
        return ActivityComponent ? m('li', ActivityComponent.component({activity})) : '';
      })),
      this.loading()
        ? LoadingIndicator.component()
        : (this.moreResults() ? m('div.load-more', ActionButton.component({
          label: 'Load More',
          className: 'control-loadMore btn btn-default',
          onclick: this.loadMore.bind(this)
        })) : '')
    ]);
  }
}
