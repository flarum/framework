import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/**
 * The `MarkAllReadModal` component shows a modal dialog to confirm user
 * action
 */
export default class MarkAllReadModal extends Modal {
  className() {
    return 'MarkAllReadModal Modal--small';
  }

  title() {
    return app.translator.trans('core.forum.mark_all_as_read.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <p className="helpText">{app.translator.trans('core.forum.mark_all_as_read.text')}</p>
          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
              children: app.translator.trans('core.forum.mark_all_as_read.send_button')
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    app.session.user.save({readTime: new Date()}).then(
      this.hide.bind(this),
      this.loaded.bind(this)
    );
  }
}
