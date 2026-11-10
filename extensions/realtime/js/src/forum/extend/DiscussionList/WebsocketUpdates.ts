import Discussion from 'flarum/common/models/Discussion';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import app from 'flarum/forum/app';

export default class WebsocketUpdates {
  /** In arrival order, which breaks ties between activity in the same second. */
  private discussions: Map<string, Discussion> = new Map();
  private timer?: number;
  private onTimerCallback: null | ((second: number) => void) = null;
  private seconds: number = 0;

  length(): number {
    return this.discussions.size;
  }

  push(discussion: Discussion): void {
    this.discussions.set(discussion.id()!, discussion);
  }

  remove(discussion: Discussion) {
    this.discussions.delete(discussion.id()!);
  }

  has(discussion: Discussion): boolean {
    return this.discussions.has(discussion.id()!);
  }

  isEmpty(): boolean {
    return this.length() === 0;
  }

  reset(): void {
    this.discussions.clear();
  }

  /**
   * Read when needed rather than at construction: the buffer is created at
   * boot, before the forum's attributes are loaded.
   */
  getReleaseInterval(): number {
    return app.forum.attribute<number>('flarum-realtime.release-discussion-updates-interval');
  }

  /**
   * Releases new discussion updates to the discussion list.
   */
  release(state: DiscussionListState): void {
    this.stopTimer();

    // Each one goes to the top of the list, so the oldest activity goes first
    // and the most recent ends up on top. `lastPostedAt` is only precise to the
    // second; the sort is stable, so ties keep their arrival order.
    Array.from(this.discussions.values())
      .sort((a, b) => (a.lastPostedAt()?.getTime() ?? 0) - (b.lastPostedAt()?.getTime() ?? 0))
      .forEach((discussion) => state.addDiscussion(discussion));

    // Reset new discussions array.
    this.reset();

    // Reset page count.
    app.setTitleCount(0);
  }

  /**
   * Starts the timer that will release new discussion updates to the discussion list.
   */
  startTimer(): void {
    if (this.autoRelease()) {
      this.stopTimer();
      this.seconds = this.getReleaseInterval();

      this.timer = window.setInterval(() => {
        this.seconds--;

        if (this.seconds < 0) return this.stopTimer();

        this.onTimerCallback && this.onTimerCallback(this.seconds);
      }, 1000);
    }
  }

  stopTimer(): void {
    clearInterval(this.timer);
    this.timer = undefined;
  }

  isTimerRunning(): boolean {
    return this.timer !== undefined;
  }

  onTimer(callback: (second: number) => void) {
    this.onTimerCallback = callback;
  }

  autoRelease(): boolean {
    return this.getReleaseInterval() > 0;
  }
}
