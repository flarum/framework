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

export interface IFlagListAttrs extends ComponentAttrs {
  state: FlagListState;
}

export default class FlagList<CustomAttrs extends IFlagListAttrs = IFlagListAttrs> extends Component<CustomAttrs, FlagListState> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
    this.state = this.attrs.state;
  }

  view() {
    const flags = this.state.cache || [];

    return (
      <HeaderList
        className="FlagList"
        title={app.translator.trans('flarum-flags.forum.flagged_posts.title')}
        hasItems={flags.length}
        loading={this.state.loading}
        emptyText={app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}
      >
        <ul className="HeaderListGroup-content">
          {!this.state.loading &&
            flags.map((flag) => {
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
                      app.flags.index = post;
                      e.redraw = false;
                    }}
                  />
                </li>
              );
            })}
        </ul>
      </HeaderList>
    );
  }
}
