import Discussion from 'flarum/common/models/Discussion';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
export default class WebsocketUpdates {
    private discussions;
    private releaseInterval;
    private timer?;
    private onTimerCallback;
    private seconds;
    length(): number;
    push(discussion: Discussion): void;
    remove(discussion: Discussion): void;
    has(discussion: Discussion): boolean;
    isEmpty(): boolean;
    reset(): void;
    getReleaseInterval(): number;
    /**
     * Releases new discussion updates to the discussion list.
     */
    release(state: DiscussionListState): void;
    /**
     * Starts the timer that will release new discussion updates to the discussion list.
     */
    startTimer(): void;
    onTimer(callback: (second: number) => void): void;
    autoRelease(): boolean;
}
