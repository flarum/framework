import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import classList from '../../common/utils/classList';
import PostListState from '../states/PostListState';
import PostListItem from './PostListItem';

export interface IPostListAttrs extends ComponentAttrs {
  state: PostListState;
}

export default class PostList<CustomAttrs extends IPostListAttrs = IPostListAttrs> extends Component<CustomAttrs> {
  view() {
    const state = this.attrs.state;

    const params = state.getParams();
    const isLoading = state.isInitialLoading() || state.isLoadingNext();

    let loading;

    if (isLoading) {
      loading = <LoadingIndicator />;
    } else if (state.hasNext()) {
      loading = (
        <Button className="Button" onclick={state.loadNext.bind(state)}>
          {app.translator.trans('core.forum.post_list.load_more_button')}
        </Button>
      );
    }

    if (state.isEmpty()) {
      return (
        <div className="PostList">
          <Placeholder text={app.translator.trans('core.forum.post_list.empty_text')} />
        </div>
      );
    }

    const pageSize = state.pageSize || 0;

    return (
      <div className={classList('PostList', { 'PostList--searchResults': state.isSearchResults() })}>
        <ul role="feed" aria-busy={isLoading} className="PostList-discussions">
          {state.getPages().map((pg, pageNum) => {
            return pg.items.map((post, itemNum) => (
              <li key={post.id()} data-id={post.id()} role="article" aria-setsize="-1" aria-posinset={pageNum * pageSize + itemNum}>
                <PostListItem post={post} params={params} />
              </li>
            ));
          })}
        </ul>
        <div className="PostList-loadMore">{loading}</div>
      </div>
    );
  }
}
