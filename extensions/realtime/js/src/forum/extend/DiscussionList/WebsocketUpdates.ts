import Discussion from 'flarum/common/models/Discussion';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import app from 'flarum/forum/app';

export default class WebsocketUpdates {
  private discussions: Record<string, Discussion> = {};
  private releaseInterval: number = app.forum.attribute<number>('flarum-realtime.release-discussion-updates-interval');
  private timer?: number;
  private onTimerCallback: null | ((second: number) => void) = null;
  private seconds: number = this.releaseInterval;

  length(): number {
    return Object.keys(this.discussions).length;
  }

  push(discussion: Discussion): void {
    this.discussions[discussion.id()!] = discussion;
  }

  remove(discussion: Discussion) {
    delete this.discussions[discussion.id()!];
  }

  has(discussion: Discussion): boolean {
    return !!this.discussions[discussion.id()!];
  }

  isEmpty(): boolean {
    return this.length() === 0;
  }

  reset(): void {
    this.discussions = {};
  }

  getReleaseInterval(): number {
    return this.releaseInterval;
  }

  /**
   * Releases new discussion updates to the discussion list.
   */
  release(state: DiscussionListState): void {
    // Push all discussions to the UI list.
    Object.keys(this.discussions).forEach((id) => {
      state.addDiscussion(this.discussions[id]);
    });

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
      clearInterval(this.timer);
      this.seconds = this.getReleaseInterval();

      this.timer = window.setInterval(() => {
        if (this.seconds < 0 && this.timer) return clearInterval(this.timer);

        this.seconds--;
        this.onTimerCallback && this.onTimerCallback(this.seconds);
      }, 1000);
    }
  }

  onTimer(callback: (second: number) => void) {
    this.onTimerCallback = callback;
  }

  autoRelease(): boolean {
    return this.releaseInterval > 0;
  }
}
