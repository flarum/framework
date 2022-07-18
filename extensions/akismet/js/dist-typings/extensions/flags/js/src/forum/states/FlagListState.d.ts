export default class FlagListState {
    constructor(app: any);
    app: any;
    /**
     * Whether or not the flags are loading.
     *
     * @type {Boolean}
     */
    loading: boolean;
    /**
     * Load flags into the application's cache if they haven't already
     * been loaded.
     */
    load(): void;
    cache: any;
}
