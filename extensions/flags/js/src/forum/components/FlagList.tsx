import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import HeaderList from 'flarum/forum/components/HeaderList';
import HeaderListItem from 'flarum/forum/components/HeaderListItem';
import type Mithril from 'mithril';
import type Post from 'flarum/common/models/Post';
import type FlagListState from '../states/FlagListState';
import type Flag from '../models/Flag';
import { Page } from 'flarum/common/states/PaginatedListState';

export interface IFlagListAttrs extends ComponentAttrs {
  state: FlagListState;
}

export default class FlagList<CustomAttrs extends IFlagListAttrs = IFlagListAttrs> extends Component<CustomAttrs, FlagListState> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    const state = this.attrs.state;

    return (
      <HeaderList
        className="FlagList"
        title={app.translator.trans('flarum-flags.forum.flagged_posts.title')}
        hasItems={state.hasItems()}
        loading={state.isLoading()}
        emptyText={app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}
        loadMore={() => state.hasNext() && !state.isLoadingNext() && state.loadNext()}
      >
        <ul className="HeaderListGroup-content">{this.content(state)}</ul>
      </HeaderList>
    );
  }

  content(state: FlagListState) {
    if (!state.isLoading() && state.hasItems()) {
      return state.getPages().map((page: Page<Flag>) => {
        return page.items.map((flag: Flag) => {
          const post = flag.post() as Post;

          return (
            <li>
              <HeaderListItem
                className="Flag"
                avatar={<Avatar user={post.user() || null} />}
                icon="fas fa-flag"
                content={app.translator.trans('flarum-flags.forum.flagged_posts.item_text', {
                  username: username(post.user()),
                  em: <em />,
                  discussion: post.discussion().title(),
                })}
                excerpt={post.contentPlain()}
                datetime={flag.createdAt()}
                href={app.route.post(post)}
                onclick={(e: MouseEvent) => {
                  e.redraw = false;
                }}
              />
            </li>
          );
        });
      });
    }

    return null;
  }
}
