import Component from '../Component';
import { Spinner } from 'spin.js';

/**
 * The `LoadingIndicator` component displays a loading spinner with spin.js. It
 * may have the following special props:
 *
 * - `size` The spin.js size preset to use. Defaults to 'small'.
 *
 * All other props will be assigned as attributes on the element.
 */
export default class LoadingIndicator extends Component {
  view() {
    const attrs = Object.assign({}, this.props);

    attrs.className = 'LoadingIndicator ' + (attrs.className || '');
    delete attrs.size;

    return <div {...attrs}>{m.trust('&nbsp;')}</div>;
  }

  config(isInitialized) {
    if (isInitialized) return;

    const options = { zIndex: 'auto', color: this.$().css('color') };

    switch (this.props.size) {
      case 'large':
        Object.assign(options, { lines: 10, length: 8, width: 4, radius: 8 });
        break;

      case 'tiny':
        Object.assign(options, { lines: 8, length: 2, width: 2, radius: 3 });
        break;

      default:
        Object.assign(options, { lines: 8, length: 4, width: 3, radius: 5 });
    }

    new Spinner(options).spin(this.element);
  }
}
