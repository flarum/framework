import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';

/**
 * How many discussions do we show / load per page?
 *
 * @type {number}
 */
const DISCUSSIONS_PER_PAGE = 20;

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
     * Whether or not discussion results are loading for the next page.
     *
     * @type {Boolean}
     */
    this.loadingNext = true;

    /**
     * Whether or not discussion results are loading for the previous page.
     *
     * @type {Boolean}
     */
    this.loadingPrev = false;

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
     * Number of discussions to offset for pagination
     *
     * @type {number}
     */
    this.offset = (this.page - 1) * DISCUSSIONS_PER_PAGE;

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

    if (this.discussions.length === 0 && !this.loadingNext) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
    }

    return (
      <div className={'DiscussionList' + (this.props.params.q ? ' DiscussionList--searchResults' : '')}>
        {this.loadingPrev
          ? LoadingIndicator.component()
          : this.firstLoadedPage !== 1 && <div className="DiscussionList-loadMore">{this.getLoadPrevButton()}</div>}
        <ul className="DiscussionList-discussions">
          {this.discussions.map((discussion) => {
            return (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({ discussion, params })}
              </li>
            );
          })}
        </ul>
        <div className="DiscussionList-loadMore">
          {this.loadingNext ? LoadingIndicator.component() : this.moreResults && this.getLoadNextButton()}
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
      this.loadingNext = true;
      this.discussions = [];
    }

    return this.loadResults().then(
      (results) => {
        this.discussions = [];
        this.parseResults(results);
      },
      () => {
        this.loadingNext = false;
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
   * Load the previous page of discussion results.
   *
   * @public
   */
  loadPrev() {
    if (this.firstLoadedPage !== 1) {
      this.loadingPrev = true;
      this.page = --this.firstLoadedPage;
      this.addResultsToBeginning = true;
    }

    this.loadResults((this.offset = (this.page - 1) * DISCUSSIONS_PER_PAGE)).then(this.parseResults.bind(this));
  }

  /**
   * Load the next page of discussion results.
   *
   * @public
   */
  loadNext() {
    this.loadingNext = true;
    this.page = ++this.lastLoadedPage;

    this.loadResults((this.offset = (this.page - 1) * DISCUSSIONS_PER_PAGE)).then(this.parseResults.bind(this));
  }

  /**
   * Parse results and append them to the discussion list.
   *
   * @param {Discussion[]} results
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

    this.loadingNext = false;
    this.loadingPrev = false;
    this.moreResults = !!results.payload.links.next;

    m.lazyRedraw();
    this.updateUrl();
  }

  /**
   * Update the "page" parameter in the URL shown to the user.
   *
   * Constructs a URL to this discussion with the updated page, then
   * replaces it into the window's history and our own history stack.
   */
  updateUrl() {
    // Bail out if the browser does not support updating the URL.
    if (typeof window.URL !== 'function') return;

    const query = m.route.parseQueryString(document.location.search);
    query.page = this.page;

    if (this.page === 1) {
      delete query.page;
    }

    const url = new URL(document.location.href);
    url.search = m.route.buildQueryString(query);

    window.history.replaceState(null, '', url.toString());
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
   * Get the "Load Previous" page button.
   *
   * @return {Button}
   */
  getLoadPrevButton() {
    return Button.component({
      children: app.translator.trans(`core.forum.discussion_list.load_prev_button`),
      className: 'Button',
      onclick: this.loadPrev.bind(this),
    });
  }

  /**
   * Get the "Load More" page button.
   *
   * @return {Button}
   */
  getLoadNextButton() {
    return Button.component({
      children: app.translator.trans(`core.forum.discussion_list.load_more_button`),
      className: 'Button',
      onclick: this.loadNext.bind(this),
    });
  }
}
