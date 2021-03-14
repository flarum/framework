import Component from '../Component';
import extractText from '../utils/extractText';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
  view() {
    const modal = this.attrs.state.modal;

    return (
      <div className="ModalManager modal fade">
        {modal
          ? modal.componentClass.component({
              ...modal.attrs,
              animateShow: this.animateShow.bind(this),
              animateHide: this.animateHide.bind(this),
              closeWithConfirmation: this.closeWithConfirmation.bind(this),
              state: this.attrs.state,
            })
          : ''}
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Ensure the modal state is notified about a closed modal, even when the
    // DOM-based Bootstrap JavaScript code triggered the closing of the modal,
    // e.g. via ESC key or a click on the modal backdrop.
    this.$().on('hidden.bs.modal', this.attrs.state.close.bind(this.attrs.state));
  }

  animateShow(readyCallback) {
    const requireCloseConfirmation = !!this.attrs.state.modal.componentClass.requireImplicitCloseConfirmation;
    const isDismissible = !requireCloseConfirmation && !!this.attrs.state.modal.componentClass.isDismissible;

    // If we are opening this modal while another modal is already open,
    // the shown event will not run, because the modal is already open.
    // So, we need to manually trigger the readyCallback.
    if (this.$().hasClass('in')) {
      readyCallback();
      return;
    }

    this.$()
      .one('shown.bs.modal', readyCallback)
      .modal({
        // 'static' means that a backdrop click doesn't dismiss the modal
        backdrop: isDismissible || 'static',
        keyboard: isDismissible,
        focus: true,
      })
      .modal('show');

    if (requireCloseConfirmation) {
      // We need to watch `body` for keypress
      $('body').on('keydown.flarum-core.modal-close', (e) => this.closeWithConfirmation(e));
      this.$().on('click.flarum-core.modal-close', (e) => this.closeWithConfirmation(e));
    }
  }

  animateHide() {
    this.$().modal('hide');

    // Remove event handlers
    $('body').off('keydown.flarum-core.modal-close');
    this.$().off('click.flarum-core.modal-close');
  }

  /**
   * Asks user to confirm they wish to close the modal before
   * actually closing the modal.
   *
   * @param {*} e jQuery event
   * @param {*} forceRun force-run the confirmation code, even if the event doesn't match
   */
  closeWithConfirmation(e, forceRun) {
    /**
     * Show native dialog to confirm modal closure, and closes it if confirmed.
     *
     * @returns {boolean} Whether the modal was closed
     */
    function confirmClose() {
      const result = confirm(extractText(app.translator.trans('core.lib.modal.close_confirmation')));

      if (result) this.animateHide();
      return result;
    }

    if (forceRun) {
      // Used within the Modal class to execute this
      // on a close button click, if desired.
      confirmClose.bind(this)();
    } else if (e.originalEvent instanceof KeyboardEvent) {
      /**
       * @type {KeyboardEvent}
       */
      const event = e.originalEvent;

      // If ESC pressed
      if (event.key === 'Escape') {
        e.stopPropagation();
        confirmClose.bind(this)();
      }
    } else if (e.originalEvent instanceof MouseEvent) {
      /**
       * @type {MouseEvent}
       */
      const event = e.originalEvent;

      // If left click, and is actually on the backdrop
      if (event.button === 0 && this.$().is(event.target)) {
        e.stopPropagation();
        confirmClose.bind(this)();
      }
    }
  }
}
