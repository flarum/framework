import type ForumApplication from 'flarum/forum/ForumApplication';
import type Flag from '../models/Flag';
import PaginatedListState from 'flarum/common/states/PaginatedListState';

export default class FlagListState extends PaginatedListState<Flag> {
    app: ForumApplication;
    
    constructor(app: ForumApplication);
    /**
     * Load flags into the application's cache if they haven't already
     * been loaded.
     */
    load(): void;
}