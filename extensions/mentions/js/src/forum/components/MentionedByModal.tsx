import app from 'flarum/forum/app';
import PostPreview from 'flarum/forum/components/PostPreview';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
import type Post from 'flarum/common/models/Post';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Button from 'flarum/common/components/Button';
import MentionedByModalState from '../state/MentionedByModalState';

export interface IMentionedByModalAttrs extends IInternalModalAttrs {
  post: Post;
}

export default class MentionedByModal<CustomAttrs extends IMentionedByModalAttrs = IMentionedByModalAttrs> extends Modal<
  CustomAttrs,
  MentionedByModalState
> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.state = new MentionedByModalState({
      filter: {
        mentionedPost: this.attrs.post.id()!,
      },
      sort: 'number',
    });

    this.state.refresh();
  }

  className(): string {
    return 'MentionedByModal';
  }

  title(): Mithril.Children {
    return app.translator.trans('flarum-mentions.forum.mentioned_by.title');
  }

  content(): Mithril.Children {
    return (
      <>
        <div className="Modal-body">
          {this.state.isInitialLoading() ? (
            <LoadingIndicator />
          ) : (
            <>
              <ul className="MentionedByModal-list Dropdown-menu Dropdown-menu--inline Post-mentionedBy-preview">
                {this.state.getPages().map((page) =>
                  page.items.map((reply) => (
                    <li data-number={reply.number()}>
                      <PostPreview post={reply} onclick={() => app.modal.close()} />
                    </li>
                  ))
                )}
              </ul>
            </>
          )}
        </div>
        {this.state.hasNext() && (
          <div className="Modal-footer">
            <div className="Form Form--centered">
              <div className="Form-group">
                <Button className="Button Button--block" onclick={() => this.state.loadNext()} loading={this.state.isLoadingNext()}>
                  {app.translator.trans('flarum-mentions.forum.mentioned_by.load_more_button')}
                </Button>
              </div>
            </div>
          </div>
        )}
      </>
    );
  }
}
