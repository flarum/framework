import Component, { ComponentProps } from '../Component';
import Alert from './Alert';
import { Vnode } from 'mithril';

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager extends Component {
    /**
     * An array of Alert components which are currently showing.
     */
    protected components: Vnode<ComponentProps, Alert>[] = [];

    view() {
        return (
            <div className="AlertManager">
                {this.components.map((vnode) => (
                    <div className="AlertManager-alert">{vnode}</div>
                ))}
            </div>
        );
    }

    /**
     * Show an Alert in the alerts area.
     */
    public show(vnode: Vnode<ComponentProps, Alert>) {
        vnode.attrs.ondismiss = this.dismiss.bind(this, vnode);

        this.components.push(vnode);
        m.redraw();
    }

    /**
     * Dismiss an alert.
     */
    public dismiss(vnode) {
        const index = this.components.indexOf(vnode);

        if (index !== -1) {
            this.components.splice(index, 1);
            m.redraw();
        }
    }

    /**
     * Clear all alerts.
     */
    public clear() {
        this.components = [];
        m.redraw();
    }
}
