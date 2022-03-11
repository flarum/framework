import app from '../../forum/app';
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
    /**
     * @type {import('../states/DiscussionListState').default}
     */
    const state = this.attrs.state;

    const params = state.getParams();
    let loading;

    if (state.isInitialLoading() || state.isLoadingNext()) {
      loading = <LoadingIndicator />;
    } else if (state.hasNext()) {
      loading = Button.component(
        {
          className: 'Button',
          onclick: state.loadNext.bind(state),
        },
        app.translator.trans('core.forum.discussion_list.load_more_button')
      );
    }

    if (state.isEmpty()) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
    }

    return (
      <div className={'DiscussionList' + (state.isSearchResults() ? ' DiscussionList--searchResults' : '')}>
        <ul className="DiscussionList-discussions">
          {state.getPages().map((pg) => {
            return pg.items.map((discussion) => (
              <li key={discussion.id()} data-id={discussion.id()}>
                {DiscussionListItem.component({ discussion, params })}
              </li>
            ));
          })}
        </ul>
        <div className="DiscussionList-loadMore">{loading}</div>
      </div>
    );
  }
}
