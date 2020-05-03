import Discussion from '../../common/models/Discussion';

export class DiscussionListState {
    /**
     * Whether or not discussion results are loading.
     */
    loading = true;

    /**
     * Whether or not there are more results that can be loaded.
     */
    moreResults = false;

    /**
     * The discussions in the discussion list.
     */
    discussions: Discussion[] = [];

    /**
     * A map of parameters used to construct a refined parameter object
     * to send along in the API request to get discussion results.
     */
    params: any;

    constructor({ params = {} } = {}) {
        this.params = params;

        this.refresh();
    }

    /**
     * Get the parameters that should be passed in the API request to get
     * discussion results.
     *
     * @api
     */
    requestParams(): any {
        const params: any = { include: ['user', 'lastPostedUser'], filter: {} };

        params.sort = this.sortMap()[this.params.sort];

        if (this.params.q) {
            params.filter.q = this.params.q;

            params.include.push('mostRelevantPost', 'mostRelevantPost.user');
        }

        return params;
    }

    /**
     * Get a map of sort keys (which appear in the URL, and are used for
     * translation) to the API sort value that they represent.
     */
    sortMap() {
        const map: any = {};

        if (this.params.q) {
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
     */
    public refresh(clear = true) {
        if (clear) {
            this.loading = true;
            this.discussions = [];
        }

        return this.loadResults().then(
            (results) => {
                this.discussions = [];
                this.parseResults(results);
            },
            () => {
                this.loading = false;
                m.redraw();
            }
        );
    }

    /**
     * Load a new page of discussion results.
     *
     * @param offset The index to start the page at.
     */
    loadResults(offset?: number): Promise<Discussion[]> {
        const preloadedDiscussions = app.preloadedApiDocument();

        if (preloadedDiscussions) {
            return Promise.resolve(preloadedDiscussions as Discussion[]);
        }

        const params = this.requestParams();
        params.page = { offset };
        params.include = params.include.join(',');

        return app.store.find('discussions', params) as Promise<Discussion[]>;
    }

    /**
     * Load the next page of discussion results.
     */
    public loadMore() {
        this.loading = true;

        this.loadResults(this.discussions.length).then(this.parseResults.bind(this));
    }

    /**
     * Parse results and append them to the discussion list.
     */
    parseResults(results: Discussion[]): Discussion[] {
        this.discussions.push(...results);

        this.loading = false;
        this.moreResults = !!results.payload.links.next;

        m.redraw();

        return results;
    }

    /**
     * Remove a discussion from the list if it is present.
     */
    public removeDiscussion(discussion: Discussion) {
        const index = this.discussions.indexOf(discussion);

        if (index !== -1) {
            this.discussions.splice(index, 1);
        }
    }

    /**
     * Add a discussion to the top of the list.
     */
    public addDiscussion(discussion: Discussion) {
        this.discussions.unshift(discussion);
    }
}
