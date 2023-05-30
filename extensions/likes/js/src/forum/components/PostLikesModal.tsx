import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Link from 'flarum/common/components/Link';
import avatar from 'flarum/common/helpers/avatar';
import username from 'flarum/common/helpers/username';
import type { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import PostLikesModalState from '../states/PostLikesModalState';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export interface IPostLikesModalAttrs extends IInternalModalAttrs {
  post: Post;
}

export default class PostLikesModal<CustomAttrs extends IPostLikesModalAttrs = IPostLikesModalAttrs> extends Modal<CustomAttrs, PostLikesModalState> {
  oninit(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oninit(vnode);

    this.state = new PostLikesModalState({
      filter: {
        liked: this.attrs.post.id()!,
      },
    });

    this.state.refresh();
  }

  className() {
    return 'PostLikesModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-likes.forum.post_likes.title');
  }

  content() {
    return (
      <>
        <div className="Modal-body">
          {this.state.isInitialLoading() ? (
            <LoadingIndicator />
          ) : (
            <ul className="PostLikesModal-list">
              {this.state.getPages().map((page) =>
                page.items.map((user) => (
                  <li>
                    <Link href={app.route.user(user)}>
                      {avatar(user)} {username(user)}
                    </Link>
                  </li>
                ))
              )}
            </ul>
          )}
        </div>
        {this.state.hasNext() ? (
          <div className="Modal-footer">
            <div className="Form Form--centered">
              <div className="Form-group">
                <Button className="Button Button--block" onclick={() => this.state.loadNext()} loading={this.state.isLoadingNext()}>
                  {app.translator.trans('flarum-likes.forum.post_likes.load_more_button')}
                </Button>
              </div>
            </div>
          </div>
        ) : null}
      </>
    );
  }
}
