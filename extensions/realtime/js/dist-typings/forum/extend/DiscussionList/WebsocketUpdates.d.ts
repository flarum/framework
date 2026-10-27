import Discussion from 'flarum/common/models/Discussion';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
export default class WebsocketUpdates {
    /** In arrival order, which breaks ties between activity in the same second. */
    private discussions;
    private timer?;
    private onTimerCallback;
    private seconds;
    length(): number;
    push(discussion: Discussion): void;
    remove(discussion: Discussion): void;
    has(discussion: Discussion): boolean;
    isEmpty(): boolean;
    reset(): void;
    /**
     * Read when needed rather than at construction: the buffer is created at
     * boot, before the forum's attributes are loaded.
     */
    getReleaseInterval(): number;
    /**
     * Releases new discussion updates to the discussion list.
     */
    release(state: DiscussionListState): void;
    /**
     * Starts the timer that will release new discussion updates to the discussion list.
     */
    startTimer(): void;
    stopTimer(): void;
    isTimerRunning(): boolean;
    onTimer(callback: (second: number) => void): void;
    autoRelease(): boolean;
}
