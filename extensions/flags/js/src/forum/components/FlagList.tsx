import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import HeaderList from 'flarum/forum/components/HeaderList';
import HeaderListItem from 'flarum/forum/components/HeaderListItem';
import type Mithril from 'mithril';
import type Post from 'flarum/common/models/Post';
import Button from 'flarum/common/components/Button';
import type FlagListState from '../states/FlagListState';
import type Flag from '../models/Flag';
import ItemList from 'flarum/common/utils/ItemList';

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
        controls={this.controlItems()}
        hasItems={state.hasItems()}
        loading={state.isLoading()}
        emptyText={app.translator.trans('flarum-flags.forum.flagged_posts.empty_text')}
        loadMore={() => state.hasNext() && !state.isLoadingNext() && state.loadNext()}
      >
        <ul className="HeaderListGroup-content">{this.content(state)}</ul>
      </HeaderList>
    );
  }

  controlItems() {
    const items = new ItemList();

    return items;
  }

  content(state: FlagListState) {
    if (!state.isLoading() && state.hasItems()) {
      const seen = new Set<string>();

      return state.getPages().map((page) => {
        return page.items
          .map((flag: Flag) => {
            const post = flag.post();
            const user = flag.targetUser();

            // Relations can be missing when a target was deleted or not included.
            // Account IDs and post IDs occupy different namespaces.
            if (!!post === !!user) return null;

            if (user) {
              const key = `user:${user.id()}`;
              if (seen.has(key)) return null;
              seen.add(key);

              return (
                <li key={key}>
                  <HeaderListItem
                    className="Flag Flag--user"
                    avatar={<Avatar user={user} />}
                    icon="fas fa-user"
                    content={app.translator.trans('flarum-flags.forum.flagged_posts.user_item_text', { username: username(user) })}
                    excerpt={
                      flag.reasonDetail() || (flag.reason() ? app.translator.trans(`flarum-flags.forum.flag_user.reason_${flag.reason()}_label`) : '')
                    }
                    datetime={flag.createdAt()}
                    href={app.route.user(user)}
                    actions={
                      user.canViewUserFlags() && (
                        <Button
                          className="Button Button--icon"
                          icon="fas fa-flag"
                          aria-label={app.translator.trans('flarum-flags.forum.user_controls.view_flags_button')}
                          onclick={(e: MouseEvent) => {
                            e.preventDefault();
                            e.stopPropagation();
                            app.modal.show(() => import('./UserFlagsModal'), { user });
                          }}
                        />
                      )
                    }
                  />
                </li>
              );
            }

            const targetPost = post as Post;
            const discussion = targetPost.discussion();
            if (!discussion) return null;
            const key = `post:${targetPost.id()}`;
            if (seen.has(key)) return null;
            seen.add(key);

            return (
              <li key={key}>
                <HeaderListItem
                  className="Flag"
                  avatar={<Avatar user={targetPost.user() || null} />}
                  icon="fas fa-flag"
                  content={app.translator.trans('flarum-flags.forum.flagged_posts.item_text', {
                    username: username(targetPost.user()),
                    em: <em />,
                    discussion: discussion.title(),
                  })}
                  excerpt={targetPost.contentPlain()}
                  datetime={flag.createdAt()}
                  href={app.route.post(targetPost)}
                  onclick={(e: MouseEvent) => {
                    e.redraw = false;
                  }}
                />
              </li>
            );
          })
          .filter((item) => item !== null);
      });
    }

    return null;
  }
}
