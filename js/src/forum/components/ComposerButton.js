import Button from '../../common/components/Button';

/**
 * The `ComposerButton` component displays a button suitable for the composer
 * controls.
 */
export default class ComposerButton extends Button {
  initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = attrs.className || 'Button Button--icon Button--link';
  }
}
