import extractText from '../utils/extractText';
import Button from './Button';
import Tooltip from './Tooltip';

/**
 * The `TextEditorButton` component displays a button suitable for the text
 * editor toolbar.
 *
 * Automatically creates tooltips using the Tooltip component and provided text.
 *
 * ## Attrs
 * - `title` - Tooltip for the button
 */
export default class TextEditorButton extends Button {
  view(vnode) {
    const originalView = super.view(vnode);

    return <Tooltip text={this.attrs.tooltipText || extractText(vnode.children)}>{originalView}</Tooltip>;
  }

  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
    attrs.tooltipText = attrs.title;
  }
}
