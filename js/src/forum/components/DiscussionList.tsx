import Component, { ComponentProps } from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import { DiscussionListState } from '../states/DiscussionListState';

export interface DiscussionListProps extends ComponentProps {
    state: DiscussionListState;
}

/**
 * The `DiscussionList` component displays a list of discussions.
 */
export default class DiscussionList<T extends DiscussionListProps = DiscussionListProps> extends Component<T> {
    state!: DiscussionListState;

    oninit(vnode) {
        super.oninit(vnode);

        this.state = this.props.state || new DiscussionListState();
    }

    view() {
        const state = this.state;

        const params = state.params;
        let loading;

        if (state.loading) {
            loading = LoadingIndicator.component();
        } else if (state.moreResults) {
            loading = Button.component({
                children: app.translator.trans('core.forum.discussion_list.load_more_button'),
                className: 'Button',
                onclick: state.loadMore.bind(this),
            });
        }

        if (state.discussions.length === 0 && !state.loading) {
            const text = app.translator.trans('core.forum.discussion_list.empty_text');
            return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
        }

        return (
            <div className={'DiscussionList' + (state.params.q ? ' DiscussionList--searchResults' : '')}>
                <ul className="DiscussionList-discussions">
                    {state.discussions.map((discussion) => {
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
}
