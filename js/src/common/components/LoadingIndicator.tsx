import Component from '../Component';
import { Spinner, SpinnerOptions } from 'spin.js';

/**
 * The `LoadingIndicator` component displays a loading spinner with spin.js. It
 * may have the following special props:
 *
 * - `size` The spin.js size preset to use. Defaults to 'small'.
 *
 * All other props will be assigned as attributes on the element.
 */
export default class LoadingIndicator extends Component {
    view(vnode) {
        const attrs = vnode.attrs;

        attrs.className = 'LoadingIndicator ' + (attrs.className || '');
        delete attrs.size;

        return <div {...attrs}>{m.trust('&nbsp;')}</div>;
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        const options: SpinnerOptions = { zIndex: 'auto', color: this.$().css('color') };
        let sizeOptions: SpinnerOptions = {};

        switch (vnode.attrs.size) {
            case 'large':
                sizeOptions = { lines: 10, length: 8, width: 4, radius: 8 };
                break;

            case 'tiny':
                sizeOptions = { lines: 8, length: 2, width: 2, radius: 3 };
                break;

            default:
                sizeOptions = { lines: 8, length: 4, width: 3, radius: 5 };
        }

        new Spinner({ ...options, ...sizeOptions }).spin(this.element);
    }
}
