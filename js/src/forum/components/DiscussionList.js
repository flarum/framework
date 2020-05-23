import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Props
 *
 * - `params` A map of parameters used to construct a refined parameter object
 *   to send along in the API request to get discussion results.
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionList extends Component {
  init() {
    this.state = this.props.state;
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
        onclick: state.loadMore.bind(state),
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
