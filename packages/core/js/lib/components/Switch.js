import Checkbox from 'flarum/components/Checkbox';

/**
 * The `Switch` component is a `Checkbox`, but with a switch display instead of
 * a tick/cross one.
 */
export default class Switch extends Checkbox {
  static initProps(props) {
    super.initProps(props);

    props.className += ' switch';
  }

  getDisplay() {
    return '';
  }
}
