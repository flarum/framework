import app from '../../forum/app';
import { throttle } from 'throttle-debounce';
import anchorScroll from '../../common/utils/anchorScroll';
import type Discussion from '../../common/models/Discussion';
import type Post from '../../common/models/Post';

export default class PostStreamState {
  /**
   * The number of posts to load per page.
   */
  static loadCount = 20;

  /**
   * The discussion to display the post stream for.
   */
  discussion: Discussion;

  /**
   * Whether or not the infinite-scrolling auto-load functionality is
   * disabled.
   */
  paused = false;

  loadPageTimeouts: Record<number, NodeJS.Timeout> = {};
  pagesLoading = 0;

  index = 0;
  number = 1;

  /**
   * The number of posts that are currently visible in the viewport.
   */
  visible = 1;
  visibleStart = 0;
  visibleEnd = 0;

  animateScroll = false;
  needsScroll = false;
  targetPost: { number: number } | { index: number; reply?: boolean } | null = null;

  /**
   * The description to render on the scrubber.
   */
  description = '';

  /**
   * When the page is scrolled, goToIndex is called, or the page is loaded,
   * various listeners result in the scrubber being updated with a new
   * position and values. However, if goToNumber is called, the scrubber
   * will not be updated. Accordingly, we add logic to the scrubber's
   * onupdate to update itself, but only when needed, as indicated by this
   * property.
   *
   */
  forceUpdateScrubber = false;

  loadPromise: Promise<void> | null = null;

  loadNext: () => void;
  loadPrevious: () => void;

  constructor(discussion: Discussion, includedPosts: Post[] = []) {
    this.discussion = discussion;

    this.loadNext = throttle(300, this._loadNext);
    this.loadPrevious = throttle(300, this._loadPrevious);

    this.show(includedPosts);
  }

  /**
   * Update the stream so that it loads and includes the latest posts in the
   * discussion, if the end is being viewed.
   */
  update() {
    if (!this.viewingEnd()) return Promise.resolve();

    this.visibleEnd = this.count();

    return this.loadRange(this.visibleStart, this.visibleEnd);
  }

  /**
   * Load and scroll up to the first post in the discussion.
   */
  goToFirst(): Promise<void> {
    return this.goToIndex(0);
  }

  /**
   * Load and scroll down to the last post in the discussion.
   */
  goToLast(): Promise<void> {
    return this.goToIndex(this.count() - 1, true);
  }

  /**
   * Load and scroll to a post with a certain number.
   *
   * @param number The post number to go to. If 'reply', go to the last post and scroll the reply preview into view.
   */
  goToNumber(number: number | 'reply', noAnimation = false): Promise<void> {
    // If we want to go to the reply preview, then we will go to the end of the
    // discussion and then scroll to the very bottom of the page.
    if (number === 'reply') {
      const resultPromise = this.goToLast();
      this.targetPost = { ...(this.targetPost as { index: number }), reply: true };
      return resultPromise;
    }

    this.paused = true;

    this.loadPromise = this.loadNearNumber(number);

    this.needsScroll = true;
    this.targetPost = { number };
    this.animateScroll = !noAnimation;
    this.number = number;

    // In this case, the redraw is only called after the response has been loaded
    // because we need to know the indices of the post range before we can
    // start scrolling to items. Calling redraw early causes issues.
    // Since this is only used for external navigation to the post stream, the delay
    // before the stream is moved is not an issue.
    return this.loadPromise.then(() => m.redraw());
  }

  /**
   * Load and scroll to a certain index within the discussion.
   */
  goToIndex(index: number, noAnimation = false): Promise<void> {
    this.paused = true;

    this.loadPromise = this.loadNearIndex(index);

    this.needsScroll = true;
    this.targetPost = { index };
    this.animateScroll = !noAnimation;
    this.index = index;

    m.redraw();

    return this.loadPromise;
  }

  /**
   * Clear the stream and load posts near a certain number. Returns a promise.
   * If the post with the given number is already loaded, the promise will be
   * resolved immediately.
   */
  loadNearNumber(number: number): Promise<void> {
    if (this.posts().some((post) => post && Number(post.number()) === Number(number))) {
      return Promise.resolve();
    }

    this.reset();

    return app.store
      .find<Post[]>('posts', {
        filter: { discussion: this.discussion.id() as string },
        page: { near: number },
      })
      .then(this.show.bind(this));
  }

  /**
   * Clear the stream and load posts near a certain index. A page of posts
   * surrounding the given index will be loaded. Returns a promise. If the given
   * index is already loaded, the promise will be resolved immediately.
   */
  loadNearIndex(index: number): Promise<void> {
    if (index >= this.visibleStart && index < this.visibleEnd) {
      return Promise.resolve();
    }

    const start = this.sanitizeIndex(index - PostStreamState.loadCount / 2);
    const end = start + PostStreamState.loadCount;

    this.reset(start, end);

    return this.loadRange(start, end).then(this.show.bind(this));
  }

  /**
   * Load the next page of posts.
   */
  _loadNext() {
    const start = this.visibleEnd;
    const end = (this.visibleEnd = this.sanitizeIndex(this.visibleEnd + PostStreamState.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start - PostStreamState.loadCount * 2;
    if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
      this.visibleStart = twoPagesAway + PostStreamState.loadCount + 1;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        delete this.loadPageTimeouts[twoPagesAway];
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end);
  }

  /**
   * Load the previous page of posts.
   */
  _loadPrevious() {
    const end = this.visibleStart;
    const start = (this.visibleStart = this.sanitizeIndex(this.visibleStart - PostStreamState.loadCount));

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start + PostStreamState.loadCount * 2;
    if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
      this.visibleEnd = twoPagesAway;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        delete this.loadPageTimeouts[twoPagesAway];
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end, true);
  }

  /**
   * Load a page of posts into the stream and redraw.
   */
  loadPage(start: number, end: number, backwards = false) {
    this.pagesLoading++;

    const redraw = () => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      const anchorIndex = backwards ? this.visibleEnd - 1 : this.visibleStart;
      anchorScroll(`.PostStream-item[data-index="${anchorIndex}"]`, m.redraw.sync);
    };
    redraw();

    this.loadPageTimeouts[start] = setTimeout(
      () => {
        this.loadRange(start, end).then(() => {
          redraw();
          this.pagesLoading--;
        });
        delete this.loadPageTimeouts[start];
      },
      this.pagesLoading - 1 ? 1000 : 0
    );
  }

  /**
   * Load and inject the specified range of posts into the stream, without
   * clearing it.
   */
  loadRange(start: number, end: number): Promise<Post[]> {
    const loadIds: string[] = [];
    const loaded: Post[] = [];

    this.discussion
      .postIds()
      .slice(start, end)
      .forEach((id) => {
        const post = app.store.getById<Post>('posts', id);

        if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
          loaded.push(post);
        } else {
          loadIds.push(id);
        }
      });

    if (loadIds.length) {
      return app.store.find<Post[]>('posts', loadIds).then((newPosts) => {
        return loaded.concat(newPosts).sort((a, b) => a.number() - b.number());
      });
    }

    return Promise.resolve(loaded);
  }

  /**
   * Set up the stream with the given array of posts.
   */
  show(posts: Post[]) {
    this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id() ?? '0') : 0;
    this.visibleEnd = this.sanitizeIndex(this.visibleStart + posts.length);
  }

  /**
   * Reset the stream so that a specific range of posts is displayed. If a range
   * is not specified, the first page of posts will be displayed.
   */
  reset(start?: number, end?: number) {
    this.visibleStart = start || 0;
    this.visibleEnd = this.sanitizeIndex(end || PostStreamState.loadCount);
  }

  /**
   * Get the visible page of posts.
   */
  posts(): (Post | null)[] {
    return this.discussion
      .postIds()
      .slice(this.visibleStart, this.visibleEnd)
      .map((id) => {
        const post = app.store.getById<Post>('posts', id);

        return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
      });
  }

  /**
   * Get the total number of posts in the discussion.
   */
  count(): number {
    return this.discussion.postIds().length;
  }

  /**
   * Check whether or not the scrubber should be disabled, i.e. if all of the
   * posts are visible in the viewport.
   */
  disabled(): boolean {
    return this.visible >= this.count();
  }

  /**
   * Are we currently viewing the end of the discussion?
   */
  viewingEnd(): boolean {
    // In some cases, such as if we've stickied a post, an event post
    // may have been added / removed. This means that `this.visibleEnd`
    // and`this.count()` will be out of sync by 1 post, but we are still
    // "viewing the end" of the post stream, so we should still reload
    // all posts up until the last one.
    return Math.abs(this.count() - this.visibleEnd) <= 1;
  }

  /**
   * Make sure that the given index is not outside of the possible range of
   * indexes in the discussion.
   */
  sanitizeIndex(index: number) {
    return Math.max(0, Math.min(this.count(), Math.floor(index)));
  }
}
