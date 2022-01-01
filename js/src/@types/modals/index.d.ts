/**
 * Only supported natively in Chrome. In testing in Safari Technology Preview.
 *
 * Please register modals with the dialog polyfill before use:
 *
 * ```js
 * dialogPolyfill.registerDialog(dialogElementReference);
 * ```
 *
 * ### Events
 *
 * Two events are fired by dialogs:
 * - `cancel` - Fired when the user instructs the browser that they wish to dismiss the current open dialog.
 * - `close` - Fired when the dialog is closed.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/HTMLDialogElement
 */
interface HTMLDialogElement {
  /**
   * Shows the `<dialog>` element as a top-layered element in the document.
   */
  show(): void;
  /**
   * Displays the dialog as a modal, over the top of any other dialogs that
   * might be present. Interaction outside the dialog is blocked.
   */
  showModal(): void;
  /**
   * If the `<dialog>` element is currently being shown, dismiss it.
   *
   * @param returnValue An optional return value for the dialog to hold. *This is currently unused by Flarum.*
   */
  close(returnValue?: string): void;

  /**
   * A return value for the dialog to hold.
   *
   * *This is currently unused by Flarum.*
   */
  returnValue: string;

  /**
   * Whether the dialog is currently open.
   */
  open: boolean;
}
