import Component from '../Component';

/**
 * The `ConfirmDocumentUnload` component can be used to register a global
 * event handler that prevents closing the browser window/tab based on the
 * return value of a given callback prop.
 *
 * ### Props
 *
 * - `when` - a callback returning true when the browser should prompt for
 *            confirmation before closing the window/tab
 *
 * ### Children
 *
 * NOTE: Only the first child will be rendered. (Use this component to wrap
 * another component / DOM element.)
 *
 */
export default class ConfirmDocumentUnload extends Component {
  config(isInitialized, context) {
    if (isInitialized) return;

    const handler = () => this.props.when() || undefined;

    $(window).on('beforeunload', handler);

    context.onunload = () => {
      $(window).off('beforeunload', handler);
    };
  }

  view() {
    // To avoid having to render another wrapping <div> here, we assume that
    // this component is only wrapped around a single element / component.
    return this.props.children[0];
  }
}
