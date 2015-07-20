import Component from 'flarum/Component';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Props
 *
 * - `params` A map of parameters used to construct a refined parameter object
 *   to send along in the API request to get discussion results.
 */
export default class DiscussionList extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not discussion results are loading.
     *
     * @type {Boolean}
     */
    this.loading = true;

    /**
     * Whether or not there are more results that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;

    /**
     * The discussions in the discussion list.
     *
     * @type {Discussion[]}
     */
    this.discussions = [];

    this.refresh();

    app.session.on('loggedIn', this.loggedInHandler = this.refresh.bind(this));
  }

  onunload() {
    app.session.off('loggedIn', this.loggedInHandler);
  }

  view() {
    const params = this.props.params;
    let loading;

    if (this.loading) {
      loading = LoadingIndicator.component();
    } else if (this.moreResults) {
      loading = Button.component({
        children: app.trans('core.load_more'),
        className: 'Button',
        onclick: this.loadMore.bind(this)
      });
    }

    return (
      <div className="DiscussionList">
        <ul className="DiscussionList-discussions">
          {this.discussions.map(discussion => {
            return (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({discussion, params})}
              </li>
            );
          })}
        </ul>
        <div className="DiscussionList-loadMore">
          {loading}
        </div>
      </div>
    );
  }

  /**
   * Get the parameters that should be passed in the API request to get
   * discussion results.
   *
   * @return {Object}
   * @api
   */
  requestParams() {
    const params = Object.assign({include: ['startUser', 'lastUser']}, this.props.params);

    params.sort = this.sortMap()[params.sort];

    if (params.q) {
      params.filter = params.filter || {};
      params.filter.q = params.q;
      delete params.q;

      params.include.push('relevantPosts', 'relevantPosts.discussion', 'relevantPosts.user');
    }

    return params;
  }

  /**
   * Get a map of sort keys (which appear in the URL, and are used for
   * translation) to the API sort value that they represent.
   *
   * @return {Object}
   */
  sortMap() {
    const map = {};

    if (this.props.params.q) {
      map.relevance = '';
    }
    map.recent = '-lastTime';
    map.replies = '-commentsCount';
    map.newest = '-startTime';
    map.oldest = '+startTime';

    return map;
  }

  /**
   * Clear and reload the discussion list.
   *
   * @public
   */
  refresh() {
    this.loading = true;
    this.discussions = [];

    this.loadResults().then(
      this.parseResults.bind(this),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }

  /**
   * Load a new page of discussion results.
   *
   * @param {Integer} offset The index to start the page at.
   * @return {Promise}
   */
  loadResults(offset) {
    const preloadedDiscussions = app.preloadedDocument();

    if (preloadedDiscussions) {
      return m.deferred().resolve(preloadedDiscussions).promise;
    }

    const params = this.requestParams();
    params.page = {offset};
    params.include = params.include.join(',');

    return app.store.find('discussions', params);
  }

  /**
   * Load the next page of discussion results.
   *
   * @public
   */
  loadMore() {
    this.loading = true;

    this.loadResults(this.discussions.length)
      .then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   *
   * @param {Discussion[]} results
   * @return {Discussion[]}
   */
  parseResults(results) {
    [].push.apply(this.discussions, results);

    this.loading = false;
    this.moreResults = !!results.payload.links.next;

    // Since this may be called during the component's constructor, i.e. in the
    // middle of a redraw, forcing another redraw would not bode well. Instead
    // we start/end a computation so Mithril will only redraw if it isn't
    // already doing so.
    m.startComputation();
    m.endComputation();

    return results;
  }

  /**
   * Remove a discussion from the list if it is present.
   *
   * @param {Discussion} discussion
   * @public
   */
  removeDiscussion(discussion) {
    const index = this.discussions.indexOf(discussion);

    if (index !== -1) {
      this.discussions.splice(index, 1);
    }
  }

  /**
   * Add a discussion to the top of the list.
   *
   * @param {Discussion} discussion
   * @public
   */
  addDiscussion(discussion) {
    this.discussions.unshift(discussion);
  }
}
