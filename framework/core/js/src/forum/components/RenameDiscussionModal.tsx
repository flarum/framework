import app from '../../forum/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import Discussion from '../../common/models/Discussion';

export interface IRenameDiscussionModalAttrs extends IInternalModalAttrs {
  discussion: Discussion;
  currentTitle: string;
}

/**
 * The 'RenameDiscussionModal' displays a modal dialog with an input to rename a discussion
 */
export default class RenameDiscussionModal<CustomAttrs extends IRenameDiscussionModalAttrs = IRenameDiscussionModalAttrs> extends Modal<CustomAttrs> {
  discussion!: Discussion;
  currentTitle!: string;
  newTitle!: Stream<string>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.discussion = this.attrs.discussion;
    this.currentTitle = this.attrs.currentTitle;
    this.newTitle = Stream(this.currentTitle);
  }

  className() {
    return 'RenameDiscussionModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.rename_discussion.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input className="FormControl" bidi={this.newTitle} type="text" />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
              {app.translator.trans('core.forum.rename_discussion.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e: SubmitEvent): Promise<void> | void {
    e.preventDefault();

    this.loading = true;

    const title = this.newTitle();
    const currentTitle = this.currentTitle;

    // If the title is different to what it was before, then save it. After the
    // save has completed, update the post stream as there will be a new post
    // indicating that the discussion was renamed.
    if (title && title !== currentTitle) {
      return this.discussion
        .save({ title })
        .then(() => {
          if (app.viewingDiscussion(this.discussion)) {
            app.current.get('stream').update();
          }
          m.redraw();
          this.hide();
        })
        .catch(() => {
          this.loading = false;
          m.redraw();
        });
    } else {
      this.hide();
    }
  }
}
