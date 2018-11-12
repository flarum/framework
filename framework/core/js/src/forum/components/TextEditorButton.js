import Button from '../../common/components/Button';

/**
 * The `TextEditorButton` component displays a button suitable for the text
 * editor toolbar.
 */
export default class TextEditorButton extends Button {
  static initProps(props) {
    super.initProps(props);

    props.className = props.className || 'Button Button--icon Button--link';
  }

  config(isInitialized, context) {
    super.config(isInitialized, context);

    if (isInitialized) return;

    this.$().tooltip();
  }
}
