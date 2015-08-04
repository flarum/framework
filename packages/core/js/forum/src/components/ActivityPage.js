import UserPage from 'flarum/components/UserPage';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import Button from 'flarum/components/Button';

/**
 * The `ActivityPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class ActivityPage extends UserPage {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the activity feed is currently loading.
     *
     * @type {Boolean}
     */
    this.loading = true;

    /**
     * Whether or not there are any more activity items that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;

    /**
     * The Activity models in the feed.
     * @type {Activity[]}
     */
    this.activity = [];

    /**
     * The number of activity items to load per request.
     *
     * @type {Integer}
     */
    this.loadLimit = 20;

    this.loadUser(m.route.param('username'));
  }

  content() {
    let footer;

    if (this.loading) {
      footer = LoadingIndicator.component();
    } else if (this.moreResults) {
      footer = (
        <div className="ActivityPage-loadMore">
          {Button.component({
            children: app.trans('core.load_more'),
            className: 'Button',
            onclick: this.loadMore.bind(this)
          })}
        </div>
      );
    }

    return (
      <div className="ActivityPage">
        <ul className="ActivityPage-list">
          {this.activity.map(activity => {
            const ActivityComponent = app.activityComponents[activity.contentType()];
            return ActivityComponent ? <li>{ActivityComponent.component({activity})}</li> : '';
          })}
        </ul>
        {footer}
      </div>
    );
  }

  /**
   * Initialize the component with a user, and trigger the loading of their
   * activity feed.
   */
  init(user) {
    super.init(user);

    this.refresh();
  }

  /**
   * Clear and reload the user's activity feed.
   *
   * @public
   */
  refresh() {
    this.loading = true;
    this.activity = [];

    // Redraw, but only if we're not in the middle of a route change.
    m.startComputation();
    m.endComputation();

    this.loadResults().then(this.parseResults.bind(this));
  }

  /**
   * Load a new page of the user's activity feed.
   *
   * @param {Integer} [offset] The position to start getting results from.
   * @return {Promise}
   * @protected
   */
  loadResults(offset) {
    return app.store.find('activity', {
      filter: {
        user: this.user.id(),
        type: this.props.filter
      },
      page: {offset, limit: this.loadLimit}
    });
  }

  /**
   * Load the next page of results.
   *
   * @public
   */
  loadMore() {
    this.loading = true;
    this.loadResults(this.activity.length).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the activity feed.
   *
   * @param {Activity[]} results
   * @return {Activity[]}
   */
  parseResults(results) {
    this.loading = false;

    [].push.apply(this.activity, results);

    this.moreResults = results.length >= this.loadLimit;
    m.redraw();

    return results;
  }
}
