import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/**
 * The 'DiscussionRenameModal' displays a modal dialog with an input to rename a discussion
 */
export default class DiscussionRenameModal extends Modal {
  init() {
    super.init();

    this.discussion = this.props.discussion;
    this.currentTitle = this.props.currentTitle;
    this.newTitle = m.prop(this.currentTitle);
  }

  className() {
    return 'DiscussionRenameModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.discussion_controls.rename_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <input className="FormControl title" placeholder={this.currentTitle} bidi={this.newTitle} />
          </div>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.discussion_controls.rename_modal.submit_button')
            })}
          </div>
        </div>
      </div>
    )
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const title = this.newTitle;
    const currentTitle = this.currentTitle;

    // If the title is different to what it was before, then save it. After the
    // save has completed, update the post stream as there will be a new post
    // indicating that the discussion was renamed.
    if (title && title !== currentTitle) {
      return this.discussion.save({title}).then(() => {
        if (app.viewingDiscussion(this.discussion)) {
          app.current.stream.update();
        }
        m.redraw();
        this.hide();
      });
    } else {
      this.hide();
    }
  }
}
