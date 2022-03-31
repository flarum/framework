import Component from '../Component';

/**
 * The `ConfirmDocumentUnload` component can be used to register a global
 * event handler that prevents closing the browser window/tab based on the
 * return value of a given callback prop.
 *
 * ### Attrs
 *
 * - `when` - a callback returning true when the browser should prompt for
 *            confirmation before closing the window/tab
 */
export default class ConfirmDocumentUnload extends Component {
  handler() {
    return this.attrs.when() || undefined;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.boundHandler = this.handler.bind(this);
    $(window).on('beforeunload', this.boundHandler);
  }

  onremove(vnode) {
    super.onremove(vnode);

    $(window).off('beforeunload', this.boundHandler);
  }

  view(vnode) {
    return <>{vnode.children}</>;
  }
}
