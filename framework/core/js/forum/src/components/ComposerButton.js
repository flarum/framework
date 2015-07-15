import Button from 'flarum/components/Button';

/**
 * The `ComposerButton` component displays a button suitable for the composer
 * controls.
 */
export default class ComposerButton extends Button {
  static initProps(props) {
    super.initProps(props);

    props.className = props.className || 'btn btn-icon btn-link';
  }
}
