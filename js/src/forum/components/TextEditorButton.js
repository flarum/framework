import Button from '../../common/components/Button';

/**
 * The `TextEditorButton` component displays a button suitable for the text
 * editor toolbar.
 */
export default class TextEditorButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$().tooltip();
  }
}
