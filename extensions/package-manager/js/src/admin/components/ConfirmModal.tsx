import app from 'flarum/admin/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';

export interface IConfirmModalAttrs extends IInternalModalAttrs {
  title: Mithril.Children;
  /** What will happen, and what it means, in a sentence or two. */
  message: Mithril.Children;
  /** Anything worth listing under the message, e.g. the requirements that will change. */
  details?: Mithril.Children;
  /** The confirm button's label: the action itself, never just "OK". */
  confirmText: Mithril.Children;
  /** Styles the confirm button as destructive. */
  danger?: boolean;
  onconfirm: () => void;
}

/**
 * Asks before an action that changes the forum, in place of the browser's
 * `confirm()`: themed, translatable, and able to show what will change.
 */
export default class ConfirmModal extends Modal<IConfirmModalAttrs> {
  className() {
    return 'Modal--small ExtensionManager-confirm';
  }

  title() {
    return this.attrs.title;
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="ExtensionManager-confirm-message">{this.attrs.message}</div>
        {this.attrs.details ? <div className="ExtensionManager-confirm-details">{this.attrs.details}</div> : null}
        <div className="ExtensionManager-confirm-actions">
          <Button
            className={'Button ' + (this.attrs.danger ? 'Button--danger' : 'Button--primary')}
            onclick={() => {
              this.hide();
              this.attrs.onconfirm();
            }}
          >
            {this.attrs.confirmText}
          </Button>
          <Button className="Button" onclick={() => this.hide()}>
            {app.translator.trans('flarum-extension-manager.admin.actions.cancel')}
          </Button>
        </div>
      </div>
    );
  }

  onready() {
    // The safe choice has focus, so Return does not confirm by accident.
    this.$('.ExtensionManager-confirm-actions .Button:last-child').trigger('focus');
  }
}

/**
 * Show a confirmation. Core's `app.modal.show` is typed for modals without
 * required attributes, so a modal with its own needs the cast core's bundled
 * extensions use (gdpr's DeleteUserModal does the same); it is made once,
 * here, rather than at every call.
 */
export function confirmAction(attrs: Omit<IConfirmModalAttrs, keyof IInternalModalAttrs>): void {
  app.modal.show(ConfirmModal as unknown as Parameters<typeof app.modal.show>[0], attrs);
}
