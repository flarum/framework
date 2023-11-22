import type ForumApplication from 'flarum/forum/ForumApplication';
import type Flag from '../models/Flag';
import type Post from 'flarum/common/models/Post';
export default class FlagListState {
    app: ForumApplication;
    loading: boolean;
    cache: Flag[] | null;
    index: Post | false | null;
    constructor(app: ForumApplication);
    /**
     * Load flags into the application's cache if they haven't already
     * been loaded.
     */
    load(): void;
}
