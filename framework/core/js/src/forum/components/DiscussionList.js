import app from '../../forum/app';
import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import classList from '../../common/utils/classList';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionList extends Component {
  view() {
    /**
     * @type {import('../states/DiscussionListState').default}
     */
    const state = this.attrs.state;

    const params = state.getParams();
    const isLoading = state.isInitialLoading() || state.isLoadingNext();

    let loading;

    if (isLoading) {
      loading = <LoadingIndicator />;
    } else if (state.hasNext()) {
      loading = (
        <Button className="Button" onclick={state.loadNext.bind(state)}>
          {app.translator.trans('core.forum.discussion_list.load_more_button')}
        </Button>
      );
    }

    if (state.isEmpty()) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return (
        <div className="DiscussionList">
          <Placeholder text={text} />
        </div>
      );
    }

    const pageSize = state.pageSize;

    return (
      <div className={classList('DiscussionList', { 'DiscussionList--searchResults': state.isSearchResults() })}>
        <ul role="feed" aria-busy={isLoading} className="DiscussionList-discussions">
          {state.getPages().map((pg, pageNum) => {
            return pg.items.map((discussion, itemNum) => (
              <li key={discussion.id()} data-id={discussion.id()} role="article" aria-setsize="-1" aria-posinset={pageNum * pageSize + itemNum + 1}>
                <DiscussionListItem discussion={discussion} params={params} />
              </li>
            ));
          })}
        </ul>
        <div className="DiscussionList-loadMore">{loading}</div>
      </div>
    );
  }
}
