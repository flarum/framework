import app from 'flarum/forum/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import Form from 'flarum/common/components/Form';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Dialog from '../../common/models/Dialog';

export interface IRenameDialogModalAttrs extends IFormModalAttrs {
  dialog: Dialog;
  /** Once the name is saved, and the group has been told. */
  onsave?: () => void;
}

/** The server's limit, so the field stops where it would be refused. */
const MAX_LENGTH = 50;

/**
 * Names a group, or takes its name away so its members title it again.
 */
export default class RenameDialogModal<CustomAttrs extends IRenameDialogModalAttrs = IRenameDialogModalAttrs> extends FormModal<CustomAttrs> {
  protected name!: Stream<string>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.name = Stream(this.attrs.dialog.name() || '');
  }

  className() {
    return 'RenameDialogModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-messages.forum.rename_dialog_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">
          <div className="Form-group">
            <label className="sr-only" for="RenameDialogModal-name">
              {app.translator.trans('flarum-messages.forum.rename_dialog_modal.name_label')}
            </label>
            <input id="RenameDialogModal-name" className="FormControl" bidi={this.name} type="text" maxlength={MAX_LENGTH} />
            <div className="helpText">{app.translator.trans('flarum-messages.forum.rename_dialog_modal.help_text')}</div>
          </div>
          <div className="Form-group Form-controls">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
              {app.translator.trans('flarum-messages.forum.rename_dialog_modal.submit_button')}
            </Button>
          </div>
        </Form>
      </div>
    );
  }

  onsubmit(e: SubmitEvent): Promise<void> | void {
    e.preventDefault();

    const name = this.name().trim();

    if (name === (this.attrs.dialog.name() || '')) {
      this.hide();

      return;
    }

    this.loading = true;

    return this.attrs.dialog.save({ name: name || null }).then(
      () => {
        this.attrs.onsave?.();
        this.hide();
      },
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }
}
