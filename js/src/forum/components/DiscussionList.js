import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionList extends Component {
  view() {
    const state = this.attrs.state;

    const params = state.getParams();
    let loading;

    if (state.isLoading()) {
      loading = LoadingIndicator.component();
    } else if (state.moreResults) {
      loading = this.getLoadButton('more', state.loadMore.bind(state));
    }

    if (state.empty()) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
    }

    console.log(state);

    return (
      <div className={'DiscussionList' + (state.isSearchResults() ? ' DiscussionList--searchResults' : '')}>
        {state.isLoadingPrev() ? (
          <LoadingIndicator />
        ) : state.pagination.pages.first !== 1 ? (
          <div className="DiscussionList-loadMore">{this.getLoadButton('prev', state.loadPrev.bind(state))}</div>
        ) : (
          ''
        )}

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

  getLoadButton(key, onclick) {
    return (
      <Button className="Button" onclick={onclick}>
        {app.translator.trans(`core.forum.discussion_list.load_${key}_button`)}
      </Button>
    );
  }
}
