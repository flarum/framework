import Checkbox from './Checkbox';

/**
 * The `Switch` component is a `Checkbox`, but with a switch display instead of
 * a tick/cross one.
 */
export default class Switch extends Checkbox {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = (attrs.className || '') + ' Checkbox--switch';
  }

  getDisplay() {
    return this.attrs.loading ? super.getDisplay() : '';
  }
}
