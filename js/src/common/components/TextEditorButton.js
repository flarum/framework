import Button from './Button';
import Tooltip from './Tooltip';

/**
 * The `TextEditorButton` component displays a button suitable for the text
 * editor toolbar.
 */
export default class TextEditorButton extends Button {
  view(vnode) {
    const originalView = super.view(vnode);

    console.log(originalView);

    // Steal tooltip label from the Button superclass
    const tooltipText = originalView.attrs.title;
    delete originalView.attrs.title;

    return (
      <Tooltip inline text={tooltipText}>
        {originalView}
      </Tooltip>
    );
  }

  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }
}
