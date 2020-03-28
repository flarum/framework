import Component, { ComponentProps } from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import Discussion from '../../common/models/Discussion';

export interface DiscussionListProps extends ComponentProps {
    /**
     * A map of parameters used to construct a refined parameter object
     * to send along in the API request to get discussion results.
     */
    params: any;
}

/**
 * The `DiscussionList` component displays a list of discussions.
 */
export default class DiscussionList<T extends DiscussionListProps = DiscussionListProps> extends Component<T> {
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

    oninit(vnode) {
        super.oninit(vnode);

        this.refresh();
    }

    view() {
        const params = this.props.params;
        let loading;

        if (this.loading) {
            loading = LoadingIndicator.component();
        } else if (this.moreResults) {
            loading = Button.component({
                children: app.translator.trans('core.forum.discussion_list.load_more_button'),
                className: 'Button',
                onclick: this.loadMore.bind(this),
            });
        }

        if (this.discussions.length === 0 && !this.loading) {
            const text = app.translator.trans('core.forum.discussion_list.empty_text');
            return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
        }

        return (
            <div className={'DiscussionList' + (this.props.params.q ? ' DiscussionList--searchResults' : '')}>
                <ul className="DiscussionList-discussions">
                    {this.discussions.map((discussion) => {
                        return (
                            <li key={discussion.id()} data-id={discussion.id()}>
                                {DiscussionListItem.component({ discussion, params })}
                            </li>
                        );
                    })}
                </ul>
                <div className="DiscussionList-loadMore">{loading}</div>
            </div>
        );
    }

    /**
     * Get the parameters that should be passed in the API request to get
     * discussion results.
     *
     * @api
     */
    requestParams(): any {
        const params = { include: ['user', 'lastPostedUser'], filter: {} };

        params.sort = this.sortMap()[this.props.params.sort];

        if (this.props.params.q) {
            params.filter.q = this.props.params.q;

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

        if (this.props.params.q) {
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
            return Promise.resolve(preloadedDiscussions);
        }

        const params = this.requestParams();
        params.page = { offset };
        params.include = params.include.join(',');

        return app.store.find('discussions', params);
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
        [].push.apply(this.discussions, results);

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
