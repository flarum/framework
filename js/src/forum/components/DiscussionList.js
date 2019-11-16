import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Props
 *
 * - `params` A map of parameters used to construct a refined parameter object
 *   to send along in the API request to get discussion results.
 */
export default class DiscussionList extends Component {
  init() {
    /**
     * Whether or not discussion results are loading.
     *
     * @type {Boolean}
     */
    this.loading = true;

    /**
     * Whether or not discussion results are loading for the previous page.
     *
     * @type {Boolean}
     */
    this.loadingPrev = false;

    /**
     * Whether or not there are previous results that can be loaded
     *
     * @type {boolean}
     */
    this.previousResults = false;

    /**
     * Whether or not there are more results that can be loaded.
     *
     * @type {Boolean}
     */
    this.moreResults = false;

    /**
     * Current page in discussion list
     *
     * @type {number}
     */
    this.page = Number(m.route.param('page')) || 1;

    /**
     * First page loaded in discussion list
     *
     * @type {number}
     */
    this.firstLoadedPage = this.page;

    /**
     * Last page loaded in discussion list
     *
     * @type {number}
     */
    this.lastLoadedPage = this.page;

    /**
     * Discussions per page
     *
     * @type {number}
     */
    this.offsetBy = 20;

    /**
     * Number of discussions to offset for pagination
     *
     * @type {number}
     */
    this.offset = (this.page - 1) * this.offsetBy;

    /**
     * The discussions in the discussion list.
     *
     * @type {Discussion[]}
     */
    this.discussions = [];

    /**
     * When getting more discussions, put the new discussions at the top of the discussion list
     *
     * @type {boolean}
     */
    this.addResultsToBeginning = false;

    this.refresh();
  }

  view() {
    const params = this.props.params;

    if (this.discussions.length === 0 && !this.loading) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
    }

    return (
      <div className={'DiscussionList' + (this.props.params.q ? ' DiscussionList--searchResults' : '')}>
        {this.loadingPrev
          ? LoadingIndicator.component()
          : this.firstLoadedPage !== 1 && <div className="DiscussionList-loadPrev">{this.getLoadButton(false)}</div>}
        <ul className="DiscussionList-discussions">
          {this.discussions.map((discussion) => {
            return (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({ discussion, params })}
              </li>
            );
          })}
        </ul>
        <div className="DiscussionList-loadMore">{this.loading ? LoadingIndicator.component() : this.moreResults && this.getLoadButton()}</div>
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
    const params = { include: ['user', 'lastPostedUser'], filter: {} };

    params.sort = this.sortMap()[this.props.params.sort];

    if (this.props.params.q) {
      params.filter.q = this.props.params.q;

      params.include.push('mostRelevantPost', 'mostRelevantPost.user');
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
    map.latest = '-lastPostedAt';
    map.top = '-commentCount';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';

    return map;
  }

  /**
   * Clear and reload the discussion list.
   *
   * @public
   */
  refresh(clear = true) {
    if (clear) {
      this.loading = true;
      this.discussions = [];
    }

    return this.loadResults().then(
      (results) => {
        this.discussions = [];
        this.parseResults(results);
      },
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }

  /**
   * Load a new page of discussion results.
   *
   * @param {Number} offset The index to start the page at.
   * @return {Promise}
   */
  loadResults(offset = this.offset) {
    const preloadedDiscussions = app.preloadedApiDocument();

    if (preloadedDiscussions) {
      return m.deferred().resolve(preloadedDiscussions).promise;
    }

    const params = this.requestParams();
    params.page = { offset };
    params.include = params.include.join(',');

    return app.store.find('discussions', params);
  }

  /**
   * Load the next page of discussion results.
   *
   * @param isNext the page to load is the next page, false for previous page
   * @public
   */
  load(isNext = true) {
    if (isNext) {
      this.loading = true;
      this.page = ++this.lastLoadedPage;
    } else if (this.firstLoadedPage !== 1) {
      this.loadingPrev = true;
      this.page = --this.firstLoadedPage;
      this.addResultsToBeginning = true;
    }

    this.loadResults((this.offset = (this.page - 1) * this.offsetBy)).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   *
   * @param {Discussion[]} results
   * @return {Discussion[]}
   */
  parseResults(results) {
    // If the results need to be added to the beginning of the discussion list
    // do so, and reset the value for the variable keeping track of this necessity
    if (this.addResultsToBeginning) {
      [].unshift.apply(this.discussions, results);
      this.addResultsToBeginning = false;
    } else {
      [].push.apply(this.discussions, results);
    }

    this.loading = false;
    this.loadingPrev = false;
    this.previousResults = !!results.payload.links.prev;
    this.moreResults = !!results.payload.links.next;

    // Construct a URL to this discussion with the updated page, then
    // replace it into the window's history and our own history stack.
    m.lazyRedraw();

    // Update page parameter in URL
    // not supported in  IE
    if (typeof window.URL === 'function') {
      const query = m.route.parseQueryString(document.location.search);

      if (this.page !== 1) query.page = this.page;
      else delete query.page;

      const url = new URL(document.location.href);

      url.search = m.route.buildQueryString(query);

      window.history.replaceState(null, document.title, url);
    }

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

  /**
   * Get the "Load More" or "Load Previous" page buttons
   *
   * @param isNext
   */
  getLoadButton(isNext = true) {
    return Button.component({
      children: app.translator.trans(`core.forum.discussion_list.load_${isNext ? 'more' : 'prev'}_button`),
      className: 'Button',
      onclick: this.load.bind(this, isNext),
    });
  }
}
