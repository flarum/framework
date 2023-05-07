import classList from '../utils/classList';
import Checkbox, { ICheckboxAttrs } from './Checkbox';

/**
 * The `Switch` component is a `Checkbox`, but with a switch display instead of
 * a tick/cross one.
 */
export default class Switch extends Checkbox {
  static initAttrs(attrs: ICheckboxAttrs) {
    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Checkbox--switch');
  }

  getDisplay() {
    return !!this.attrs.loading && super.getDisplay();
  }
}
