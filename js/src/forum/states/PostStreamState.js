import anchorScroll from '../../common/utils/anchorScroll';

class PostStreamState {
  constructor(discussion, includedPosts = []) {
    /**
     * The discussion to display the post stream for.
     *
     * @type {Discussion}
     */
    this.discussion = discussion;

    /**
     * Whether or not the infinite-scrolling auto-load functionality is
     * disabled.
     *
     * @type {Boolean}
     */
    this.paused = false;

    this.loadPageTimeouts = {};
    this.pagesLoading = 0;

    this.locationType = null;
    this.index = 0;
    this.number = 1;

    /**
     * The number of posts that are currently visible in the viewport.
     *
     * @type {Number}
     */
    this.visible = 1;

    /**
     * The description to render on the scrubber.
     *
     * @type {String}
     */
    this.description = '';

    this.show(includedPosts);
  }

  /**
   * Load and scroll to a post with a certain number.
   *
   * @param {Integer|String} number The post number to go to. If 'reply', go to
   *     the last post and scroll the reply preview into view.
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToNumber(number, noAnimation) {
    console.log('goToNumber', number);
    // If we want to go to the reply preview, then we will go to the end of the
    // discussion and then scroll to the very bottom of the page.
    if (number === 'reply') {
      return this.goToLast();
    }

    this.paused = true;

    const promise = this.loadNearNumber(number);

    m.redraw(true);

    this.locationType = 'number';
    this.number = number;
    this.needsScroll = true;
    this.noAnimationScroll = noAnimation;

    m.redraw();

    return promise;
  }

  /**
   * Load and scroll to a certain index within the discussion.
   *
   * @param {Integer} index
   * @param {Boolean} backwards Whether or not to load backwards from the given
   *     index.
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToIndex(index, noAnimation) {
    console.log('goToIndex', index);
    this.paused = true;

    const promise = this.loadNearIndex(index);

    m.redraw(true);

    this.locationType = 'index';
    this.index = index;
    this.needsScroll = true;
    this.noAnimationScroll = noAnimation;

    m.redraw();

    return promise;
  }

  /**
   * Load and scroll up to the first post in the discussion.
   *
   * @return {Promise}
   */
  goToFirst() {
    return this.goToIndex(0);
  }

  /**
   * Load and scroll down to the last post in the discussion.
   *
   * @return {Promise}
   */
  goToLast() {
    return this.goToIndex(this.count() - 1);
  }

  /**
   * Set up the stream with the given array of posts.
   *
   * @param {Post[]} posts
   */
  show(posts) {
    this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id()) : 0;
    this.visibleEnd = this.sanitizeIndex(this.visibleStart + posts.length);
  }

  /**
   * Reset the stream so that a specific range of posts is displayed. If a range
   * is not specified, the first page of posts will be displayed.
   *
   * @param {Integer} [start]
   * @param {Integer} [end]
   */
  reset(start, end) {
    this.visibleStart = start || 0;
    this.visibleEnd = this.sanitizeIndex(end || this.constructor.loadCount);
  }

  /**
   * Get the visible page of posts.
   *
   * @return {Post[]}
   */
  posts() {
    return this.discussion
      .postIds()
      .slice(this.visibleStart, this.visibleEnd)
      .map((id) => {
        const post = app.store.getById('posts', id);

        return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
      });
  }

  /**
   * Get the total number of posts in the discussion.
   *
   * @return {Integer}
   */
  count() {
    return this.discussion.postIds().length;
  }

  /**
   * Check whether or not the scrubber should be disabled, i.e. if all of the
   * posts are visible in the viewport.
   *
   * @return {Boolean}
   */
  allVisible() {
    return this.visible >= this.count();
  }

  /**
   * Are we currently viewing the end of the discussion?
   *
   * @return {boolean}
   */
  viewingEnd() {
    return this.visibleEnd === this.count();
  }

  /**
   * Make sure that the given index is not outside of the possible range of
   * indexes in the discussion.
   *
   * @param {Integer} index
   * @protected
   */
  sanitizeIndex(index) {
    return Math.max(0, Math.min(this.count(), index));
  }

  /**
   * Update the stream so that it loads and includes the latest posts in the
   * discussion, if the end is being viewed.
   *
   * @public
   */
  update() {
    if (!this.viewingEnd()) return m.deferred().resolve().promise;

    this.visibleEnd = this.count();

    return this.loadRange(this.visibleStart, this.visibleEnd).then(() => m.redraw());
  }

  /**
   * Load the next page of posts.
   */
  loadNext() {
    console.log('loadNext');
    const start = this.visibleEnd;
    const end = (this.visibleEnd = this.sanitizeIndex(this.visibleEnd + this.constructor.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start - this.constructor.loadCount * 2;
    if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
      this.visibleStart = twoPagesAway + this.constructor.loadCount + 1;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end);
  }

  /**
   * Load the previous page of posts.
   */
  loadPrevious() {
    console.log('loadPrevious');
    const end = this.visibleStart;
    const start = (this.visibleStart = this.sanitizeIndex(this.visibleStart - this.constructor.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start + this.constructor.loadCount * 2;
    if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
      this.visibleEnd = twoPagesAway;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end, true);
  }

  /**
   * Load a page of posts into the stream and redraw.
   *
   * @param {Integer} start
   * @param {Integer} end
   * @param {Boolean} backwards
   */
  loadPage(start, end, backwards) {
    console.log('loadPage');
    const redraw = () => {};

    this.loadPageTimeouts[start] = setTimeout(
      () => {
        this.loadRange(start, end).then(() => {
          if (start >= this.visibleStart && end <= this.visibleEnd) {
            const anchorIndex = backwards ? this.visibleEnd - 1 : this.visibleStart;
            anchorScroll(`.PostStream-item[data-index="${anchorIndex}"]`, () => m.redraw(true));
          }
          this.pagesLoading--;
        });
        this.loadPageTimeouts[start] = null;
      },
      this.pagesLoading ? 1000 : 0
    );

    this.pagesLoading++;
  }

  /**
   * Load and inject the specified range of posts into the stream, without
   * clearing it.
   *
   * @param {Integer} start
   * @param {Integer} end
   * @return {Promise}
   */
  loadRange(start, end) {
    const loadIds = [];
    const loaded = [];

    this.discussion
      .postIds()
      .slice(start, end)
      .forEach((id) => {
        const post = app.store.getById('posts', id);

        if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
          loaded.push(post);
        } else {
          loadIds.push(id);
        }
      });

    return loadIds.length ? app.store.find('posts', loadIds) : m.deferred().resolve(loaded).promise;
  }

  /**
   * Clear the stream and load posts near a certain number. Returns a promise.
   * If the post with the given number is already loaded, the promise will be
   * resolved immediately.
   *
   * @param {Integer} number
   * @return {Promise}
   */
  loadNearNumber(number) {
    console.log('loadNearNumber', number);
    if (this.posts().some((post) => post && Number(post.number()) === Number(number))) {
      return m.deferred().resolve().promise;
    }

    this.reset();

    return app.store
      .find('posts', {
        filter: { discussion: this.discussion.id() },
        page: { near: number },
      })
      .then(this.show.bind(this));
  }

  /**
   * Clear the stream and load posts near a certain index. A page of posts
   * surrounding the given index will be loaded. Returns a promise. If the given
   * index is already loaded, the promise will be resolved immediately.
   *
   * @param {Integer} index
   * @return {Promise}
   */
  loadNearIndex(index) {
    console.log('loadNearIndex', index);
    if (index >= this.visibleStart && index <= this.visibleEnd) {
      return m.deferred().resolve().promise;
    }

    const start = this.sanitizeIndex(index - this.constructor.loadCount / 2);
    const end = start + this.constructor.loadCount;

    this.reset(start, end);

    return this.loadRange(start, end).then(this.show.bind(this));
  }

  /**
   * Resume the stream's ability to auto-load posts on scroll.
   */
  unpause() {
    this.paused = false;
  }
}

/**
 * The number of posts to load per page.
 *
 * @type {Integer}
 */
PostStreamState.loadCount = 20;

export default PostStreamState;
